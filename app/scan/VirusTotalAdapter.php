<?php
declare(strict_types=1);

final class VirusTotalAdapter implements FileScanProvider
{
    private const ORIGIN = 'https://www.virustotal.com/api/v3/analyses/';
    private const COUNTERS = ['malicious','suspicious','harmless','undetected','timeout','confirmed-timeout','failure','type-unsupported'];
    private Closure $transport;

    public function __construct(private string $apiKey, callable $transport)
    {
        if ($apiKey==='' || preg_match('/[\x00-\x20\x7f]/',$apiKey)) throw new InvalidArgumentException('Invalid provider key.');
        $this->transport=Closure::fromCallable($transport);
    }

    private function result(string $status,string $reason,bool $retryable=false,?int $retryAfter=null): array
    {
        return ['status'=>$status,'reason'=>$reason,'retryable'=>$retryable,'retryAfter'=>$retryAfter];
    }

    private function read(string $url): array
    {
        return ($this->transport)('GET',$url,['x-apikey: '.$this->apiKey],null);
    }

    private function failure(array $response,bool $identity=false): ?array
    {
        $code=$response['httpStatus']??0;
        if ($code===200 && ($response['transportError']??null)===null) return null;
        $headers=$response['headers']??[];
        $hint=is_array($headers)?($headers['retry-after']??null):null;
        $retryAfter=is_string($hint) && preg_match('/\A[0-9]{1,9}\z/',$hint) ? (int)$hint : null;
        if ($retryAfter===null && is_string($hint)) {
            foreach (['D, d M Y H:i:s \\G\\M\\T','l, d-M-y H:i:s \\G\\M\\T','D M j H:i:s Y'] as $format) {
                $date=DateTimeImmutable::createFromFormat('!'.$format,$hint,new DateTimeZone('UTC'));
                $errors=DateTimeImmutable::getLastErrors();
                if ($date && ($errors===false || ($errors['warning_count']===0 && $errors['error_count']===0))) { $retryAfter=max(0,$date->getTimestamp()-time());break; }
            }
        }
        $providerError=json_decode($response['body']??'',true)['error']['code']??null;
        if ($code===401 || $code===403) return $this->result('Unresolved','provider_auth_failed');
        if ($code===429) {
            $result=$this->result('Unresolved','quota_deferred',true,$retryAfter);
            $result['quotaKind']=match ($providerError) {'TooManyRequestsError'=>'rate','QuotaExceededError'=>'quota',default=>'unknown'};
            return $result;
        }
        if ($code===400 && $providerError==='NotAvailableYet') return $this->result('Unresolved',$identity?'identity_unavailable':'provider_unavailable',true,$retryAfter);
        if ($identity && ($code===0 || $code>=500)) return $this->result('Unresolved','identity_unavailable',true,$retryAfter);
        if (($response['transportError']??null)==='timeout') return $this->result('Unresolved','provider_timeout',true,$retryAfter);
        if ($code===0 || $code>=500) return $this->result('Unresolved','provider_unavailable',true,$retryAfter);
        return $this->result('Unresolved',$identity?'identity_unavailable':'provider_unavailable');
    }

    public function getResult(string $analysisId,string $expectedSha256): array
    {
        if (!preg_match('/\A[\x21-\x7e]{1,512}\z/',$analysisId) || !preg_match('/\A[0-9a-fA-F]{64}\z/',$expectedSha256)) {
            return $this->result('Unresolved','invalid_input');
        }
        $segment=rawurlencode($analysisId);
        if ($segment==='.' || $segment==='..') $segment=str_replace('.','%2E',$segment);
        $url=self::ORIGIN.$segment;
        $response=$this->read($url);
        if ($failure=$this->failure($response)) return $failure;
        $data=json_decode($response['body']??'',true);
        $analysis=$data['data']??null;
        if (!is_array($analysis) || ($analysis['type']??null)!=='analysis' || !is_string($analysis['id']??null)) return $this->result('Unresolved','malformed_response');
        if ($analysis['id']!==$analysisId) return $this->result('Unresolved','analysis_mismatch');
        $attributes=$analysis['attributes']??null;
        $state=is_array($attributes)?($attributes['status']??null):null;
        if ($state==='queued' || $state==='in-progress') return $this->result('Unresolved','analysis_pending',true);
        $stats=is_array($attributes)?($attributes['stats']??null):null;
        $results=is_array($attributes)?($attributes['results']??null):null;
        if ($state!=='completed' || !is_array($stats) || !is_array($results) || $results===[] || array_is_list($results)) return $this->result('Unresolved','malformed_response');
        $observed=array_fill_keys(self::COUNTERS,0);
        foreach ($results as $name=>$engine) {
            if (!is_string($name) || $name==='' || !is_array($engine) || !is_string($engine['category']??null)
                || !in_array($engine['category'],self::COUNTERS,true) || !is_string($engine['engine_name']??null) || $engine['engine_name']===''
                || !is_string($engine['method']??null) || !array_key_exists('result',$engine)
                || ($engine['result']!==null && !is_string($engine['result']))) return $this->result('Unresolved','malformed_response');
            $observed[$engine['category']]++;
        }
        $counts=[];
        foreach (self::COUNTERS as $category) {
            if (!isset($stats[$category]) || !is_int($stats[$category]) || $stats[$category]<0) return $this->result('Unresolved','malformed_response');
            $counts[$category]=$stats[$category];
            if ($counts[$category]!==$observed[$category]) return $this->result('Unresolved','malformed_response');
        }
        $itemResponse=$this->read($url.'/item');
        if ($failure=$this->failure($itemResponse,true)) return $failure;
        $item=json_decode($itemResponse['body']??'',true)['data']??null;
        $sha=is_array($item)?($item['attributes']['sha256']??null):null;
        if (!is_array($item) || ($item['type']??null)!=='file' || !is_string($sha) || !preg_match('/\A[0-9a-fA-F]{64}\z/',$sha)) return $this->result('Unresolved','identity_unavailable');
        $itemId=$item['id']??null;
        if (!is_string($itemId) || !preg_match('/\A[0-9a-fA-F]{64}\z/',$itemId)) return $this->result('Unresolved','identity_unavailable');
        if (strtolower($sha)!==strtolower($expectedSha256) || strtolower($itemId)!==strtolower($expectedSha256)) return $this->result('Unresolved','file_mismatch');
        if ($counts['malicious']>0) $result=$this->result('Flagged','malicious_detected');
        elseif ($counts['suspicious']>0) $result=$this->result('Review Recommended','suspicious_detected');
        elseif (($counts['harmless']>0 || $counts['undetected']>0) && $counts['timeout']===0 && $counts['confirmed-timeout']===0 && $counts['failure']===0 && $counts['type-unsupported']===0) $result=$this->result('No Detections Reported','no_detections_reported');
        else $result=$this->result('Unresolved','inconclusive_engines');
        $result['stats']=$counts;
        return $result;
    }
}
