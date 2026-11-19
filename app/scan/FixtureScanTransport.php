<?php
declare(strict_types=1);

/** Deterministic, harmless offline provider protocol; uses the real adapter classifier. */
final class FixtureScanTransport
{
    public function __construct(private string $scenario='no-detections')
    {
        if (!in_array($scenario,['no-detections','malicious','suspicious','inconclusive','pending','timeout','auth','quota','malformed'],true)) throw new InvalidArgumentException('Unknown fixture scenario.');
    }
    public function __invoke(string $method,string $url,array $headers,?array $multipart): array
    {
        $reply=static fn(int $code,array $body,array $headers=[]):array=>['httpStatus'=>$code,'headers'=>$headers,'body'=>json_encode($body,JSON_THROW_ON_ERROR),'transportError'=>null];
        if ($this->scenario==='timeout') return ['httpStatus'=>0,'headers'=>[],'body'=>'','transportError'=>'timeout'];
        if ($this->scenario==='auth') return $reply(401,['error'=>['code'=>'WrongCredentialsError']]);
        if ($this->scenario==='quota') return $reply(429,['error'=>['code'=>'TooManyRequestsError']],['retry-after'=>'60']);
        if ($this->scenario==='malformed') return $reply(200,['data'=>null]);
        $path=parse_url($url,PHP_URL_PATH);
        if ($method==='GET'&&preg_match('~/files/([a-f0-9]{64})$~',$path,$m)) return $reply(200,['data'=>['id'=>$m[1],'type'=>'file','attributes'=>['sha256'=>$m[1]]]]);
        if ($method==='POST') {
            if (!preg_match('~/files/([a-f0-9]{64})/analyse$~',$path,$m)) {
                $file=$multipart['file']??null;
                if (!$file instanceof CURLFile) return $reply(400,['error'=>['code'=>'InvalidArgumentError']]);
                $sha=hash_file('sha256',$file->getFilename());
            } else $sha=$m[1];
            return $reply(200,['data'=>['type'=>'analysis','id'=>'fixture-'.$sha.'-'.bin2hex(random_bytes(8))]]);
        }
        if ($method==='GET'&&preg_match('~/analyses/(fixture-([a-f0-9]{64})-[a-f0-9]{16})(/item)?$~',$path,$m)) {
            if (!empty($m[3])) return $reply(200,['data'=>['type'=>'file','id'=>$m[2],'attributes'=>['sha256'=>$m[2]]]]);
            if ($this->scenario==='pending') return $reply(200,['data'=>['type'=>'analysis','id'=>$m[1],'attributes'=>['status'=>'in-progress']]]);
            $category=match($this->scenario){'malicious'=>'malicious','suspicious'=>'suspicious','inconclusive'=>'type-unsupported',default=>'undetected'};
            $stats=array_fill_keys(['malicious','suspicious','harmless','undetected','timeout','confirmed-timeout','failure','type-unsupported'],0);$stats[$category]=1;
            return $reply(200,['data'=>['type'=>'analysis','id'=>$m[1],'attributes'=>['status'=>'completed','stats'=>$stats,'results'=>['OfflineFixture'=>['category'=>$category,'engine_name'=>'OfflineFixture','method'=>'blacklist','result'=>null]]]]]);
        }
        return $reply(404,['error'=>['code'=>'NotFoundError']]);
    }
}
