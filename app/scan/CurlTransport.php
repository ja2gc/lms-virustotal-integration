<?php
declare(strict_types=1);

final class CurlTransport
{
    public function __invoke(string $method,string $url,array $headers,?array $multipart): array
    {
        $error=static fn(string $reason):array=>['httpStatus'=>0,'headers'=>[],'body'=>'','transportError'=>$reason];
        $parts=parse_url($url);
        if (!is_array($parts) || ($parts['scheme']??'')!=='https' || ($parts['host']??'')!=='www.virustotal.com'
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['port']) || isset($parts['query']) || isset($parts['fragment'])
            || !str_starts_with($parts['path']??'','/api/v3/') || !in_array($method,['GET','POST'],true)
            || ($method==='GET' && $multipart!==null)) return $error('invalid_request');
        if (!function_exists('curl_init')) return $error('transport_unavailable');
        $handle=curl_init($url);
        if ($handle===false) return $error('transport_unavailable');
        $body='';$responseHeaders=[];$tooLarge=false;
        $options=[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers,CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_TIMEOUT=>30,CURLOPT_CONNECTTIMEOUT=>30,
            CURLOPT_WRITEFUNCTION=>static function($ch,string $chunk)use(&$body,&$tooLarge):int {
                if (strlen($body)+strlen($chunk)>2097152) { $tooLarge=true;return 0; }
                $body.=$chunk;return strlen($chunk);
            },
            CURLOPT_HEADERFUNCTION=>static function($ch,string $line)use(&$responseHeaders):int {
                $length=strlen($line);
                if (str_starts_with($line,'HTTP/')) $responseHeaders=[];
                elseif (($pos=strpos($line,':'))!==false) {
                    $name=strtolower(trim(substr($line,0,$pos)));
                    if ($name==='retry-after') $responseHeaders[$name]=trim(substr($line,$pos+1));
                }
                return $length;
            }];
        if ($multipart!==null) $options[CURLOPT_POSTFIELDS]=$multipart;
        curl_setopt_array($handle,$options);
        $ok=curl_exec($handle);
        $code=(int)curl_getinfo($handle,CURLINFO_RESPONSE_CODE);
        $errno=curl_errno($handle);
        curl_close($handle);
        if ($ok===false) return $error($tooLarge?'response_too_large':($errno===CURLE_OPERATION_TIMEDOUT?'timeout':'transport_failure'));
        return ['httpStatus'=>$code,'headers'=>$responseHeaders,'body'=>$body,'transportError'=>null];
    }
}
