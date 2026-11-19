<?php
declare(strict_types=1);

final class ScanBudgetDeferred extends RuntimeException
{
    public function __construct(public int $availableAt) { parent::__construct('Provider budget deferred.'); }
}
final class ScanDeadlineExceeded extends RuntimeException {}
final class ScanLeaseLost extends RuntimeException {}

/** Durable, shared reservations. No database lock is held while the transport runs. */
final class ScanProviderBudget
{
    private Closure $clock;
    private ?string $active=null;
    private ?int $deadline=null;
    private ?Closure $guard=null;

    public function __construct(private PDO $db,?callable $clock=null,private int $perMinute=4,private int $perDay=500)
    {
        if ($perMinute<2 || $perMinute>4 || $perDay<2 || $perDay>500) throw new InvalidArgumentException('Provider limits must allow two reads and cannot exceed 4/minute or 500/day.');
        $this->clock=Closure::fromCallable($clock??static fn():string=>gmdate('Y-m-d\TH:i:s\Z'));
        $db->exec('PRAGMA busy_timeout=5000');
    }
    private function now(): int { return (new DateTimeImmutable(($this->clock)()))->getTimestamp(); }
    private function edit(callable $work): mixed
    {
        if ($this->db->inTransaction()) throw new LogicException('Provider budget requires its own short transaction.');
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $row=$this->db->query('SELECT * FROM scan_provider_state WHERE id=1')->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Provider state missing; run scan migration.');
            $times=json_decode($row['request_timestamps_json'],true,512,JSON_THROW_ON_ERROR);
            $reservations=json_decode($row['reservations_json'],true,512,JSON_THROW_ON_ERROR);
            if (!is_array($times)||!is_array($reservations)) throw new RuntimeException('Invalid provider state.');
            $now=$this->now();$day=strtotime(gmdate('Y-m-d',$now).'T00:00:00Z');
            $times=array_values(array_filter($times,static fn($t):bool=>is_int($t)&&$t>=min($day,$now-60)));
            $reservations=array_filter($reservations,static fn($r):bool=>is_array($r)&&is_int($r['remaining']??null)&&is_int($r['expiresAt']??null)&&$r['remaining']>0&&$r['expiresAt']>$now);
            $pause=$row['paused_until'];
            $result=$work($times,$reservations,$pause,$now,$day);
            $stmt=$this->db->prepare('UPDATE scan_provider_state SET request_timestamps_json=?,reservations_json=?,paused_until=?,updated_at=? WHERE id=1');
            $stmt->execute([json_encode($times,JSON_THROW_ON_ERROR),json_encode($reservations,JSON_THROW_ON_ERROR),$pause,gmdate('Y-m-d\TH:i:s\Z',$now)]);
            $this->db->exec('COMMIT'); return $result;
        } catch(Throwable $error) { $this->db->exec('ROLLBACK');throw $error; }
    }
    private function availability(array $times,array $reservations,?string $pause,int $now,int $day,int $count): int
    {
        $reserved=array_sum(array_column($reservations,'remaining'));
        if ($pause!==null && strtotime($pause)>$now) return strtotime($pause);
        $minute=array_values(array_filter($times,static fn(int $t):bool=>$t>$now-60));
        $daily=count(array_filter($times,static fn(int $t):bool=>$t>=$day));
        $until=$now;
        if (count($minute)+$reserved+$count>$this->perMinute) {
            $expiries=array_merge(array_map(static fn(int $t):int=>$t+60,$minute),array_column($reservations,'expiresAt'));
            $until=max($until,$expiries?min($expiries):$now+60);
        }
        if ($daily+$reserved+$count>$this->perDay) $until=max($until,$day+86400);
        return $until;
    }
    public function within(int $count,callable $work,?int $deadline=null,?callable $guard=null): mixed
    {
        if ($this->active!==null || $count<1 || $count>2) throw new LogicException('Invalid provider reservation.');
        $token=bin2hex(random_bytes(16));
        $this->edit(function(array &$times,array &$reservations,?string &$pause,int $now,int $day)use($token,$count):void {
            $until=$this->availability($times,$reservations,$pause,$now,$day,$count);
            if ($until>$now) throw new ScanBudgetDeferred($until);
            $reservations[$token]=['remaining'=>$count,'expiresAt'=>$now+90];
        });
        $this->active=$token;$this->deadline=$deadline;$this->guard=$guard===null?null:Closure::fromCallable($guard);
        try { return $work(); }
        finally {
            $this->active=null;$this->deadline=null;$this->guard=null;
            $this->edit(static function(array &$times,array &$reservations)use($token):void {unset($reservations[$token]);});
        }
    }
    public function metered(callable $transport): Closure
    {
        return function(string $method,string $url,array $headers,?array $multipart)use($transport):array {
            if ($this->deadline!==null && $this->now()>=$this->deadline) throw new ScanDeadlineExceeded();
            if ($this->guard!==null && !($this->guard)()) throw new ScanLeaseLost();
            $token=$this->active;
            if ($token===null) throw new LogicException('HTTP requires a provider reservation.');
            $this->edit(function(array &$times,array &$reservations,?string &$pause,int $now,int $day)use($token):void {
                if (!isset($reservations[$token])) throw new ScanBudgetDeferred($now+60);
                // This request already owns its capacity; exclude its own remaining slots.
                $others=$reservations;unset($others[$token]);
                $until=$this->availability($times,$others,$pause,$now,$day,1);
                if ($until>$now) throw new ScanBudgetDeferred($until);
                $times[]=$now;
                if (--$reservations[$token]['remaining']===0) unset($reservations[$token]);
            });
            $response=$transport($method,$url,$headers,$multipart);
            $code=$response['httpStatus']??0;
            if ($code===401||$code===403) $this->pause();
            elseif ($code===429) {
                $error=json_decode($response['body']??'',true)['error']['code']??null;
                $hint=$response['headers']['retry-after']??null;
                $delay=is_string($hint)&&ctype_digit($hint)?min((int)$hint,86400):0;
                if (is_string($hint)&&!ctype_digit($hint)) {$date=strtotime($hint);if($date!==false)$delay=max(0,$date-$this->now());}
                $this->pause($error==='TooManyRequestsError'?max(60,$delay):null);
            }
            return $response;
        };
    }
    public function pause(?int $seconds=null): void
    {
        $this->edit(static function(array &$times,array &$reservations,?string &$pause,int $now)use($seconds):void {
            $until=$seconds===null?'9999-12-31T23:59:59Z':gmdate('Y-m-d\TH:i:s\Z',$now+$seconds);
            if ($pause===null || $pause<$until) $pause=$until;
        });
    }
    public function resume(): void
    {
        $this->edit(static function(array &$times,array &$reservations,?string &$pause):void {$pause=null;});
    }
}
