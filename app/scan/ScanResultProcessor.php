<?php
declare(strict_types=1);

/** Shared scheduling/persistence for both origin-specific result consumers. */
final class ScanResultProcessor
{
    private Closure $clock;
    private Closure $jitter;
    public function __construct(private ScanAttemptRepository $repository,private FileScanProvider $provider,private ScanProviderBudget $budget,?callable $clock=null,?callable $jitter=null)
    {
        $this->clock=Closure::fromCallable($clock??static fn():string=>gmdate('Y-m-d\TH:i:s\Z'));
        $this->jitter=Closure::fromCallable($jitter??static fn(int $max):int=>random_int(0,$max));
    }
    private function timestamp(): int {return (new DateTimeImmutable(($this->clock)()))->getTimestamp();}
    public function process(int $attemptId,string $leaseToken,string $origin): void
    {
        $claim=$this->repository->getClaimContext($attemptId,$leaseToken);
        if ($claim===null || $claim['origin']!==$origin || $claim['analysisId']===null) return;
        $deadline=$claim['pollDeadlineAt']===null?null:strtotime($claim['pollDeadlineAt']);
        if ($deadline===null || $deadline<=$this->timestamp()) {$this->apply($claim,ScanProviderClient::error('polling_exhausted'));return;}
        try {
            $result=$this->budget->within(2,fn():array=>$this->provider->getResult($claim['analysisId'],$claim['sha256']),$deadline,
                fn():bool=>$this->repository->getClaimContext($attemptId,$leaseToken)!==null);
            if ($this->timestamp()>=$deadline) $result=ScanProviderClient::error('polling_exhausted');
            $this->apply($claim,$result);
        } catch(ScanBudgetDeferred $error) {$this->apply($claim,ScanProviderClient::error('quota_deferred',true),$error->availableAt);}
        catch(ScanDeadlineExceeded) {$this->apply($claim,ScanProviderClient::error('polling_exhausted'));}
        catch(ScanLeaseLost) {return;}
        catch(Throwable) {$this->apply($claim,ScanProviderClient::error('provider_unavailable',true));}
    }
    public function apply(array $claim,array $result,?int $availableAt=null): void
    {
        $now=$this->timestamp();$failures=$claim['failureCount'];$next=null;
        if ($result['retryable']) {
            if ($result['reason']==='analysis_pending') $delay=60;
            elseif ($result['reason']==='quota_deferred') $delay=60;
            else {
                $failures++;
                $base=30*(2**min($failures-1,4));
                $jitter=($this->jitter)((int)floor($base*0.2));
                $delay=$base+max(0,min((int)floor($base*0.2),$jitter));
            }
            if ($failures<=5) $next=max($now+$delay,$now+($result['retryAfter']??0),$availableAt??0);
            // An indefinite operator pause is checked locally every five minutes, never by HTTP.
            if ($claim['analysisId']===null && $next!==null && $next>253402214400) $next=$now+300;
        }
        if ($claim['pollDeadlineAt']!==null && ($now>=strtotime($claim['pollDeadlineAt'])||($next!==null&&$next>=strtotime($claim['pollDeadlineAt'])))) {
            $result=ScanProviderClient::error('polling_exhausted');$next=null;
        }
        $state=$next!==null?'waiting':(isset($result['stats'])?'completed':'failed');
        $this->repository->saveResult($claim['attemptId'],$claim['leaseToken'],$result,[
            'state'=>$state,'nextRunAt'=>$next===null?null:gmdate('Y-m-d\TH:i:s\Z',$next),'failureCount'=>$failures,
            'completedAt'=>$next===null?gmdate('Y-m-d\TH:i:s\Z',$now):null]);
    }
}
