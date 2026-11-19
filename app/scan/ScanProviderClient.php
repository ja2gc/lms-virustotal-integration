<?php
declare(strict_types=1);

/** Scan-start protocol only. Result interpretation belongs to VirusTotalAdapter. */
final class ScanProviderClient
{
    private Closure $transport;
    public function __construct(private string $apiKey,callable $transport)
    {
        if ($apiKey===''||preg_match('/[\x00-\x20\x7f]/',$apiKey)) throw new InvalidArgumentException('Invalid provider key.');
        $this->transport=Closure::fromCallable($transport);
    }
    public function lookup(string $sha): array
    {
        $response=($this->transport)('GET','https://www.virustotal.com/api/v3/files/'.$sha,['x-apikey: '.$this->apiKey],null);
        if (($response['httpStatus']??0)===404 && ($response['transportError']??null)===null) return ['exists'=>false];
        if ($error=$this->failure($response)) return $error;
        $file=json_decode($response['body']??'',true)['data']??null;
        if (!is_array($file)||($file['type']??null)!=='file'||($file['id']??null)!==$sha||($file['attributes']['sha256']??null)!==$sha) return self::error('file_mismatch');
        return ['exists'=>true];
    }
    public function start(string $sha,bool $exists,string $path,string $mime): array
    {
        $url='https://www.virustotal.com/api/v3/files'.($exists?'/'.$sha.'/analyse':'');
        if (!$exists && !class_exists('CURLFile')) return self::error('provider_unavailable');
        $response=($this->transport)('POST',$url,['x-apikey: '.$this->apiKey],$exists?null:['file'=>new CURLFile($path,$mime,'submission')]);
        if ($error=$this->failure($response)) {
            $code=$response['httpStatus']??0;
            return $code===0||$code>=500?self::error('start_outcome_unknown'):$error;
        }
        $analysis=json_decode($response['body']??'',true)['data']??null;
        $id=is_array($analysis)?($analysis['id']??null):null;
        if (!is_array($analysis)||($analysis['type']??null)!=='analysis'||!is_string($id)||!preg_match('/\A[\x21-\x7e]{1,512}\z/',$id)) return self::error('start_outcome_unknown');
        return ['analysisId'=>$id];
    }
    private function failure(array $response): ?array
    {
        $code=$response['httpStatus']??0;
        if (in_array($code,[200,201],true)&&($response['transportError']??null)===null) return null;
        $body=json_decode($response['body']??'',true);
        $error=$body['error']['code']??null;
        $hint=$response['headers']['retry-after']??null;
        $retryAfter=is_string($hint)&&preg_match('/\A\d{1,9}\z/',$hint)?(int)$hint:null;
        if ($retryAfter===null&&is_string($hint)) {$time=strtotime($hint);if($time!==false)$retryAfter=max(0,$time-time());}
        if ($code===401||$code===403) return self::error('provider_auth_failed');
        if ($code===429) return self::error('quota_deferred',true,$retryAfter)+['quotaKind'=>match($error){'TooManyRequestsError'=>'rate','QuotaExceededError'=>'quota',default=>'unknown'}];
        if (($response['transportError']??null)==='timeout') return self::error('provider_timeout',true,$retryAfter);
        return self::error('provider_unavailable',$code===0||$code>=500||($code===400&&$error==='NotAvailableYet'),$retryAfter);
    }
    public static function error(string $reason,bool $retryable=false,?int $retryAfter=null): array
    {
        return ['status'=>'Unresolved','reason'=>$reason,'retryable'=>$retryable,'retryAfter'=>$retryAfter];
    }
}
