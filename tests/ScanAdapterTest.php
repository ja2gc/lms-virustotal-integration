<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/scan/bootstrap.php';

const VT_ANALYSIS_ID = 'analysis/fixture?01';
const VT_SHA256 = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

function vtFixture(string $name): string
{
    $body=file_get_contents(__DIR__.'/fixtures/virustotal/'.$name.'.json');
    if ($body===false) throw new RuntimeException('Missing VirusTotal fixture.');
    json_decode($body,true,512,JSON_THROW_ON_ERROR);
    return $body;
}

function vtResponse(string $fixture,int $status=200,array $headers=[]): array
{
    return ['httpStatus'=>$status,'headers'=>$headers,'body'=>vtFixture($fixture),'transportError'=>null];
}

function vtAdapter(array $responses,array &$requests): FileScanProvider
{
    $transport=static function(string $method,string $url,array $headers,?array $multipart)use(&$responses,&$requests):array {
        $requests[]=['method'=>$method,'url'=>$url,'headers'=>$headers,'multipart'=>$multipart];
        if (!$responses) throw new RuntimeException('Unexpected provider request.');
        return array_shift($responses);
    };
    return new VirusTotalAdapter('fixture-api-key',$transport);
}

function vtAssertResult(array $result,string $status,string $reason,bool $retryable,?int $retryAfter): void
{
    foreach (['status','reason','retryable','retryAfter'] as $field) {
        if (!array_key_exists($field,$result)) throw new RuntimeException('Missing result field: '.$field);
    }
    if ($result['status']!==$status || $result['reason']!==$reason || $result['retryable']!==$retryable || $result['retryAfter']!==$retryAfter) {
        throw new RuntimeException('Normalized result mismatch.');
    }
    if (!is_string($result['reason']) || !preg_match('/\A[a-z][a-z0-9_]{0,99}\z/',$result['reason'])) throw new RuntimeException('Reason is not a stable machine code.');
    if ($result['retryAfter']!==null && (!is_int($result['retryAfter']) || $result['retryAfter']<0)) throw new RuntimeException('Retry hint must be nonnegative integer seconds.');
}

function vtHasApiKey(array $headers): bool
{
    foreach ($headers as $name=>$value) {
        if (is_string($name) && strtolower($name)==='x-apikey' && $value==='fixture-api-key') return true;
        if (is_int($name) && is_string($value) && strtolower($value)==='x-apikey: fixture-api-key') return true;
    }
    return false;
}

return ['group'=>'adapter','tests'=>[
    'completed provider upload descriptor aliases require matching timestamp SHA and item MD5' => function (): void {
        $stamp='1795072950';$md5=str_repeat('b',32);$descriptor=base64_encode($md5.':'.$stamp);
        $analysis=json_decode(vtFixture('analysis-no-detections'),true);$analysis['data']['id']='f-'.VT_SHA256.'-'.$stamp;
        $item=json_decode(vtFixture('item-match'),true);$item['data']['attributes']['md5']=$md5;
        $reply=static fn(array $body):array=>['httpStatus'=>200,'headers'=>[],'body'=>json_encode($body,JSON_THROW_ON_ERROR),'transportError'=>null];
        $requests=[];$adapter=vtAdapter([$reply($analysis),$reply($item)],$requests);
        vtAssertResult($adapter->getResult($descriptor,VT_SHA256),'No Detections Reported','no_detections_reported',false,null);
        foreach ([null,str_repeat('c',32)] as $wrong) {
            $item['data']['attributes']['md5']=$wrong;$requests=[];$adapter=vtAdapter([$reply($analysis),$reply($item)],$requests);
            if($adapter->getResult($descriptor,VT_SHA256)['status']!=='Unresolved')throw new RuntimeException('Unverified descriptor MD5 alias became favorable.');
        }
        foreach (['f-'.VT_SHA256.'-1795072951','f-'.str_repeat('d',64).'-'.$stamp] as $wrong) {
            $analysis['data']['id']=$wrong;$requests=[];$adapter=vtAdapter([$reply($analysis)],$requests);
            vtAssertResult($adapter->getResult($descriptor,VT_SHA256),'Unresolved','analysis_mismatch',false,null);
        }
    },
    'CT01 completed matching analysis returns normalized no-detection advisory' => function (): void {
        $requests=[];$adapter=vtAdapter([vtResponse('analysis-no-detections'),vtResponse('item-match')],$requests);
        $result=$adapter->getResult(VT_ANALYSIS_ID,VT_SHA256);
        vtAssertResult($result,'No Detections Reported','no_detections_reported',false,null);
        if (count($requests)!==2) throw new RuntimeException('Completed evidence must use one analysis and one identity read.');
        if ($requests[0]['method']!=='GET' || $requests[0]['url']!=='https://www.virustotal.com/api/v3/analyses/analysis%2Ffixture%3F01') throw new RuntimeException('Analysis request origin or ID encoding mismatch.');
        if ($requests[1]['url']!=='https://www.virustotal.com/api/v3/analyses/analysis%2Ffixture%3F01/item') throw new RuntimeException('Identity request mismatch.');
        if (!vtHasApiKey($requests[0]['headers']) || $requests[0]['multipart']!==null) throw new RuntimeException('Provider authentication or GET transport contract mismatch.');
    },
    'pending analyses remain unresolved and do not request file identity' => function (): void {
        foreach (['analysis-pending-queued','analysis-pending-in-progress'] as $fixture) {
            $requests=[];$adapter=vtAdapter([vtResponse($fixture)],$requests);
            vtAssertResult($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256),'Unresolved','analysis_pending',true,null);
            if (count($requests)!==1) throw new RuntimeException('Pending analysis requested unnecessary identity evidence.');
        }
    },
    'invalid inputs return invalid_input without making HTTP requests' => function (): void {
        $cases=[['',VT_SHA256],["analysis id",VT_SHA256],[str_repeat('x',513),VT_SHA256],[VT_ANALYSIS_ID,'short'],[VT_ANALYSIS_ID,str_repeat('g',64)]];
        foreach ($cases as [$analysisId,$sha256]) {
            $requests=[];$adapter=vtAdapter([],$requests);
            vtAssertResult($adapter->getResult($analysisId,$sha256),'Unresolved','invalid_input',false,null);
            if ($requests!==[]) throw new RuntimeException('Invalid input reached the provider transport.');
        }
    },
    'CT02 malformed and incomplete analysis evidence stays unresolved' => function (): void {
        foreach (['analysis-missing-counter','analysis-negative-counter','analysis-string-counter','analysis-empty','analysis-unknown-status'] as $fixture) {
            $requests=[];$adapter=vtAdapter([vtResponse($fixture)],$requests);
            vtAssertResult($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256),'Unresolved','malformed_response',false,null);
        }
        $requests=[];$adapter=vtAdapter([['httpStatus'=>200,'headers'=>[],'body'=>'{"data":','transportError'=>null]],$requests);
        vtAssertResult($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256),'Unresolved','malformed_response',false,null);
    },
    'CT02 mismatched analysis and file identities cannot become favorable' => function (): void {
        $requests=[];$adapter=vtAdapter([vtResponse('analysis-wrong-id')],$requests);
        vtAssertResult($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256),'Unresolved','analysis_mismatch',false,null);
        if (count($requests)!==1) throw new RuntimeException('Mismatched analysis should not request item evidence.');

        $requests=[];$adapter=vtAdapter([vtResponse('analysis-no-detections'),vtResponse('item-mismatch')],$requests);
        vtAssertResult($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256),'Unresolved','file_mismatch',false,null);
    },
    'CT03 malicious suspicious and inconclusive engine counts follow exact policy' => function (): void {
        $cases=[
            ['analysis-malicious','Flagged','malicious_detected'],
            ['analysis-suspicious','Review Recommended','suspicious_detected'],
            ['analysis-failed-engine','Unresolved','inconclusive_engines'],
            ['analysis-unsupported-engine','Unresolved','inconclusive_engines'],
        ];
        foreach ($cases as [$fixture,$status,$reason]) {
            $requests=[];$adapter=vtAdapter([vtResponse($fixture),vtResponse('item-match')],$requests);
            vtAssertResult($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256),$status,$reason,false,null);
        }
    },
    'provider failures return sanitized stable reasons and bounded retry hints' => function (): void {
        $cases=[
            [['httpStatus'=>0,'headers'=>[],'body'=>'','transportError'=>'timeout'],'provider_timeout',true,null],
            [['httpStatus'=>401,'headers'=>[],'body'=>'{"error":{"message":"secret upstream detail"}}','transportError'=>null],'provider_auth_failed',false,null],
            [['httpStatus'=>429,'headers'=>['retry-after'=>'120'],'body'=>'{}','transportError'=>null],'quota_deferred',true,120],
            [['httpStatus'=>429,'headers'=>['retry-after'=>'-5'],'body'=>'{}','transportError'=>null],'quota_deferred',true,null],
            [['httpStatus'=>503,'headers'=>[],'body'=>'private provider outage text','transportError'=>null],'provider_unavailable',true,null],
        ];
        foreach ($cases as [$response,$reason,$retryable,$retryAfter]) {
            $requests=[];$adapter=vtAdapter([$response],$requests);$result=$adapter->getResult(VT_ANALYSIS_ID,VT_SHA256);
            vtAssertResult($result,'Unresolved',$reason,$retryable,$retryAfter);
            if (str_contains(json_encode($result,JSON_THROW_ON_ERROR),'secret upstream detail') || str_contains(json_encode($result,JSON_THROW_ON_ERROR),'private provider outage text')) throw new RuntimeException('Raw provider error escaped normalization.');
        }
    },
    'completed analysis with unavailable identity remains unresolved and retryable' => function (): void {
        $requests=[];$adapter=vtAdapter([vtResponse('analysis-no-detections'),['httpStatus'=>503,'headers'=>[],'body'=>'','transportError'=>null]],$requests);
        vtAssertResult($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256),'Unresolved','identity_unavailable',true,null);
    },
    'empty engine evidence and wrong item type never produce favorable results' => function (): void {
        $analysis=json_decode(vtFixture('analysis-no-detections'),true,512,JSON_THROW_ON_ERROR);
        foreach ($analysis['data']['attributes']['stats'] as $key=>$value) $analysis['data']['attributes']['stats'][$key]=0;
        $response=['httpStatus'=>200,'headers'=>[],'body'=>json_encode($analysis,JSON_THROW_ON_ERROR),'transportError'=>null];
        $requests=[];$adapter=vtAdapter([$response,vtResponse('item-match')],$requests);
        if ($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256)['status']!=='Unresolved') throw new RuntimeException('Empty counts became favorable.');
        $item=json_decode(vtFixture('item-match'),true,512,JSON_THROW_ON_ERROR);$item['data']['type']='url';
        $requests=[];$adapter=vtAdapter([vtResponse('analysis-no-detections'),['httpStatus'=>200,'headers'=>[],'body'=>json_encode($item,JSON_THROW_ON_ERROR),'transportError'=>null]],$requests);
        if ($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256)['status']!=='Unresolved') throw new RuntimeException('Wrong item type became favorable.');
    },
    'uppercase expected hash is normalized and identity authentication failure is not hidden' => function (): void {
        $requests=[];$adapter=vtAdapter([vtResponse('analysis-no-detections'),vtResponse('item-match')],$requests);
        vtAssertResult($adapter->getResult(VT_ANALYSIS_ID,strtoupper(VT_SHA256)),'No Detections Reported','no_detections_reported',false,null);
        $requests=[];$adapter=vtAdapter([vtResponse('analysis-no-detections'),['httpStatus'=>403,'headers'=>[],'body'=>'','transportError'=>null]],$requests);
        vtAssertResult($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256),'Unresolved','provider_auth_failed',false,null);
    },
    'transport rejects non-provider URLs without issuing a request' => function (): void {
        $transport=new CurlTransport();
        foreach (['http://www.virustotal.com/api/v3/files','https://www.virustotal.com.evil.test/api/v3/files','https://user@www.virustotal.com/api/v3/files'] as $url) {
            $response=$transport('GET',$url,[],null);
            if ($response['httpStatus']!==0 || $response['transportError']===null) throw new RuntimeException('Untrusted URL accepted.');
        }
    },
    'invalid API key cannot inject request headers' => function (): void {
        $requests=[];
        try { new VirusTotalAdapter("bad\r\nInjected: value",static fn():array=>[]); }
        catch (InvalidArgumentException $error) { if (str_contains($error->getMessage(),'Injected')) throw new RuntimeException('Credential leaked in error.'); return; }
        throw new RuntimeException('API key containing a newline was accepted.');
    },
    'missing or empty completed engine results cannot be favorable' => function (): void {
        $analysis=json_decode(vtFixture('analysis-no-detections'),true,512,JSON_THROW_ON_ERROR);
        foreach ([null,[],(object)[]] as $invalid) {
            $analysis['data']['attributes']['results']=$invalid;
            $requests=[];$adapter=vtAdapter([['httpStatus'=>200,'headers'=>[],'body'=>json_encode($analysis,JSON_THROW_ON_ERROR),'transportError'=>null],vtResponse('item-match')],$requests);
            if ($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256)['status']!=='Unresolved') throw new RuntimeException('Empty results produced a favorable advisory.');
        }
        unset($analysis['data']['attributes']['results']);
        $requests=[];$adapter=vtAdapter([['httpStatus'=>200,'headers'=>[],'body'=>json_encode($analysis,JSON_THROW_ON_ERROR),'transportError'=>null],vtResponse('item-match')],$requests);
        if ($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256)['status']!=='Unresolved') throw new RuntimeException('Missing results produced a favorable advisory.');
    },
    'quota error codes distinguish request rate and exhausted quota without exposing messages' => function (): void {
        foreach (['TooManyRequestsError'=>'rate','QuotaExceededError'=>'quota','UnknownError'=>'unknown'] as $code=>$expected) {
            $body=json_encode(['error'=>['code'=>$code,'message'=>'private provider detail']],JSON_THROW_ON_ERROR);
            $requests=[];$adapter=vtAdapter([['httpStatus'=>429,'headers'=>[],'body'=>$body,'transportError'=>null]],$requests);
            $result=$adapter->getResult(VT_ANALYSIS_ID,VT_SHA256);
            if (($result['quotaKind']??null)!==$expected || $result['status']!=='Unresolved' || str_contains(json_encode($result,JSON_THROW_ON_ERROR),'private provider detail')) throw new RuntimeException('Quota classification mismatch.');
        }
    },
    'NotAvailableYet is retryable on analysis or identity reads' => function (): void {
        $response=['httpStatus'=>400,'headers'=>[],'body'=>'{"error":{"code":"NotAvailableYet","message":"private"}}','transportError'=>null];
        foreach ([[$response],[vtResponse('analysis-no-detections'),$response]] as $responses) {
            $requests=[];$adapter=vtAdapter($responses,$requests);$result=$adapter->getResult(VT_ANALYSIS_ID,VT_SHA256);
            if ($result['status']!=='Unresolved' || $result['retryable']!==true) throw new RuntimeException('Temporarily unavailable evidence was not retryable.');
        }
    },
    'dot-only analysis IDs remain one encoded path segment' => function (): void {
        foreach (['.'=>'%2E','..'=>'%2E%2E'] as $id=>$encoded) {
            $analysis=json_decode(vtFixture('analysis-pending-queued'),true,512,JSON_THROW_ON_ERROR);$analysis['data']['id']=$id;
            $requests=[];$adapter=vtAdapter([['httpStatus'=>200,'headers'=>[],'body'=>json_encode($analysis,JSON_THROW_ON_ERROR),'transportError'=>null]],$requests);
            $adapter->getResult($id,VT_SHA256);
            if (($requests[0]['url']??null)!=='https://www.virustotal.com/api/v3/analyses/'.$encoded) throw new RuntimeException('Dot-only ID could be normalized as a URL path.');
        }
    },
    'malformed engine results cannot produce a favorable advisory' => function (): void {
        $analysis=json_decode(vtFixture('analysis-no-detections'),true,512,JSON_THROW_ON_ERROR);
        foreach ([['bogus'=>null],['bogus'=>['category'=>'undetected']],['bogus'=>['category'=>[]]]] as $results) {
            $analysis['data']['attributes']['results']=$results;
            $requests=[];$adapter=vtAdapter([['httpStatus'=>200,'headers'=>[],'body'=>json_encode($analysis,JSON_THROW_ON_ERROR),'transportError'=>null],vtResponse('item-match')],$requests);
            if ($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256)['status']!=='Unresolved') throw new RuntimeException('Malformed engine evidence accepted.');
        }
    },
    'conflicting or missing file object ID cannot produce favorable results' => function (): void {
        $item=json_decode(vtFixture('item-match'),true,512,JSON_THROW_ON_ERROR);
        foreach ([str_repeat('b',64),null] as $id) {
            $item['data']['id']=$id;
            $requests=[];$adapter=vtAdapter([vtResponse('analysis-no-detections'),['httpStatus'=>200,'headers'=>[],'body'=>json_encode($item,JSON_THROW_ON_ERROR),'transportError'=>null]],$requests);
            if ($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256)['status']!=='Unresolved') throw new RuntimeException('Conflicting file identity accepted.');
        }
    },
    'HTTP-date retry hints are converted to seconds' => function (): void {
        $date=gmdate('D, d M Y H:i:s',time()+300).' GMT';
        $requests=[];$adapter=vtAdapter([['httpStatus'=>429,'headers'=>['retry-after'=>$date],'body'=>'{}','transportError'=>null]],$requests);
        $result=$adapter->getResult(VT_ANALYSIS_ID,VT_SHA256);
        if (!is_int($result['retryAfter']) || $result['retryAfter']<295 || $result['retryAfter']>300) throw new RuntimeException('HTTP-date retry hint ignored.');
    },
    'contradictory engine categories and counters remain unresolved' => function (): void {
        $analysis=json_decode(vtFixture('analysis-no-detections'),true,512,JSON_THROW_ON_ERROR);
        $name=array_key_first($analysis['data']['attributes']['results']);
        $analysis['data']['attributes']['results'][$name]['category']='malicious';
        $requests=[];$adapter=vtAdapter([['httpStatus'=>200,'headers'=>[],'body'=>json_encode($analysis,JSON_THROW_ON_ERROR),'transportError'=>null],vtResponse('item-match')],$requests);
        if ($adapter->getResult(VT_ANALYSIS_ID,VT_SHA256)['status']!=='Unresolved') throw new RuntimeException('Contradictory engine evidence accepted.');
    },
    'obsolete HTTP-date retry hints are honored' => function (): void {
        $future=time()+300;
        foreach ([gmdate('l, d-M-y H:i:s',$future).' GMT',gmdate('D M',$future).' '.str_pad(gmdate('j',$future),2,' ',STR_PAD_LEFT).gmdate(' H:i:s Y',$future)] as $date) {
            $requests=[];$adapter=vtAdapter([['httpStatus'=>429,'headers'=>['retry-after'=>$date],'body'=>'{}','transportError'=>null]],$requests);
            $result=$adapter->getResult(VT_ANALYSIS_ID,VT_SHA256);
            if (!is_int($result['retryAfter']) || $result['retryAfter']<295 || $result['retryAfter']>300) throw new RuntimeException('HTTP-date retry hint ignored.');
        }
    },
]];
