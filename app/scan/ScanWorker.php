<?php
declare(strict_types=1);

final class ScanWorker
{
    private ScanAttemptRepository $repository;
    private ScanProviderBudget $budget;
    private ScanProviderClient $client;
    private ScanResultProcessor $processor;
    private AutomaticSubmissionResult $automatic;
    private TeacherRescanResult $teacher;
    private Closure $clock;

    public function __construct(PDO $db,private string $privateRoot,string $apiKey,callable $transport,?callable $clock=null,int $perMinute=4,int $perDay=500,?callable $jitter=null)
    {
        $this->clock=Closure::fromCallable($clock??static fn():string=>gmdate('Y-m-d\TH:i:s\Z'));
        $this->repository=new ScanAttemptRepository($db,$this->clock);
        $this->budget=new ScanProviderBudget($db,$this->clock,$perMinute,$perDay);
        $metered=$this->budget->metered($transport);
        $this->client=new ScanProviderClient($apiKey,$metered);
        $this->processor=new ScanResultProcessor($this->repository,new VirusTotalAdapter($apiKey,$metered),$this->budget,$this->clock,$jitter);
        $this->automatic=new AutomaticSubmissionResult($this->processor);
        $this->teacher=new TeacherRescanResult($this->processor);
    }
    public function runOnce(): bool
    {
        $now=($this->clock)();$token=bin2hex(random_bytes(24));
        $claim=$this->repository->claimDue($token,$now,gmdate('Y-m-d\TH:i:s\Z',strtotime($now)+90));
        if ($claim===null) return false;
        if ($claim['analysisId']!==null) {
            ($claim['origin']==='automatic'?$this->automatic:$this->teacher)->process($claim['attemptId'],$token);
            return true;
        }
        if ($claim['startInFlight']) {$this->processor->apply($claim,ScanProviderClient::error('start_outcome_unknown'));return true;}
        $file=$this->repository->getFileContext($claim['fileId']);
        $root=realpath($this->privateRoot);$path=$root===false?false:realpath($root.DIRECTORY_SEPARATOR.$claim['storageKey']);
        $public=defined('PUBLIC_DIR')?realpath(PUBLIC_DIR):realpath(dirname(__DIR__,2).'/public');
        if ($file===null || !preg_match('/\A[a-f0-9]{32,64}\z/',$claim['storageKey']) || $root===false || $path===false
            || ($public!==false&&($root===$public||str_starts_with($root,$public.DIRECTORY_SEPARATOR)))
            || !str_starts_with($path,$root.DIRECTORY_SEPARATOR)||is_link($root.DIRECTORY_SEPARATOR.$claim['storageKey'])||!is_file($path)||!is_readable($path)
            || filesize($path)!==$file['sizeBytes']||hash_file('sha256',$path)!==$claim['sha256']) {
            $this->processor->apply($claim,ScanProviderClient::error('file_integrity_failed'));return true;
        }
        $startMarked=false;
        try {
            $this->budget->within(2,function()use($claim,$token,$file,$path,&$startMarked):void {
                $lookup=$this->client->lookup($claim['sha256']);
                if (!array_key_exists('exists',$lookup)) {$this->processor->apply($claim,$lookup);return;}
                if (!$this->repository->saveProgress($claim['attemptId'],$token,['startInFlight'=>true,'step'=>'start'])) return;
                $startMarked=true;
                $started=$this->client->start($claim['sha256'],$lookup['exists'],$path,$file['mimeType']);
                if (!isset($started['analysisId'])) {
                    // Explicit rejection responses are replay-safe; unknown transport/descriptor outcomes are terminal.
                    if ($started['reason']!=='start_outcome_unknown') $this->repository->saveProgress($claim['attemptId'],$token,['startInFlight'=>false,'step'=>'lookup']);
                    $this->processor->apply($claim,$started);return;
                }
                if ($this->repository->hasAnalysisId($claim['fileId'],$started['analysisId'])) {
                    $this->processor->apply($claim,ScanProviderClient::error('analysis_mismatch'));return;
                }
                $now=strtotime(($this->clock)());
                $this->repository->saveProgress($claim['attemptId'],$token,['analysisId'=>$started['analysisId'],'startInFlight'=>false,
                    'pollDeadlineAt'=>gmdate('Y-m-d\TH:i:s\Z',$now+1800),'state'=>'waiting','step'=>'poll','nextRunAt'=>gmdate('Y-m-d\TH:i:s\Z',$now+60),'failureCount'=>0,'reason'=>'analysis_pending']);
            },null,fn():bool=>$this->repository->getClaimContext($claim['attemptId'],$token)!==null);
        } catch(ScanBudgetDeferred $error) {
            // No POST was sent when the metered transport rejects its reservation.
            if ($startMarked) $this->repository->saveProgress($claim['attemptId'],$token,['startInFlight'=>false,'step'=>'lookup']);
            $this->processor->apply($claim,ScanProviderClient::error('quota_deferred',true),$error->availableAt);
        } catch(ScanAnalysisReused) {$this->processor->apply($claim,ScanProviderClient::error('analysis_mismatch'));}
        catch(ScanLeaseLost) {return true;}
        catch(Throwable) {$this->processor->apply($claim,ScanProviderClient::error($startMarked?'start_outcome_unknown':'provider_unavailable',!$startMarked));}
        return true;
    }
}
