<?php
declare(strict_types=1);
require_once __DIR__.'/Support/ScanFixture.php';
return ['group'=>'claim','tests'=>[
    'freshness checks recognize descriptor and canonical representations of one analysis' => function():void {
        $fixture=new ScanFixture();$fixture->migrate();$file=$fixture->revision();$repo=$fixture->repository();
        $descriptor=base64_encode(str_repeat('b',32).':1795072950');
        $canonical='f-'.hash('sha256','synthetic').'-1795072950';
        $claim=$repo->claimDue('alias-token','2026-11-18T00:00:00Z','2026-11-18T00:01:30Z');
        $repo->saveProgress($claim['attemptId'],'alias-token',['analysisId'=>$descriptor]);
        scanCheck($repo->hasAnalysisId($file,$canonical),'Canonical alias bypasses fresh-scan detection.');
        $repo->saveProgress($claim['attemptId'],'alias-token',['analysisId'=>$canonical]);
        scanCheck($repo->hasAnalysisId($file,$descriptor),'Descriptor alias bypasses fresh-scan detection.');
        scanCheck(!$repo->hasAnalysisId($file,'f-'.hash('sha256','synthetic').'-1795072951'),'Distinct scan time merged.');
        $repo->requestRescan(1,2,str_repeat('f',64));
        $next=$repo->claimDue('next-alias-token','2026-11-18T00:00:00Z','2026-11-18T00:01:30Z');
        scanThrows(fn()=>$repo->saveProgress($next['attemptId'],'next-alias-token',['analysisId'=>$descriptor]));
        scanCheck($repo->getClaimContext($next['attemptId'],'next-alias-token')['analysisId']===null,'Duplicate handle was stored after another attempt claimed it.');
    },
    'fresh scan start can detect a reused handle for its own revision' => function(): void {
        $fixture=new ScanFixture(); $fixture->migrate(); $file=$fixture->revision();
        $repo=$fixture->repository();
        scanCheck(!$repo->hasAnalysisId($file,'provider-old'),'Invented historical handle.');
        $claim=$repo->claimDue('consumer-token','2026-11-18T00:00:00Z','2026-11-18T00:01:30Z');
        $repo->saveProgress($claim['attemptId'],'consumer-token',['analysisId'=>'provider-old']);
        scanCheck($repo->hasAnalysisId($file,'provider-old'),'Stored handle missing.');
        scanCheck(!$repo->hasAnalysisId($file+1,'provider-old'),'Different revision identity merged.');
    },
    'result consumers read only their current nonexpired durable claim' => function(): void {
        $fixture=new ScanFixture(); $fixture->migrate(); $fixture->revision();
        $repo=$fixture->repository();
        $claim=$repo->claimDue('consumer-token','2026-11-18T00:00:00Z','2026-11-18T00:01:30Z');
        scanCheck($repo->getClaimContext($claim['attemptId'],'wrong-token')===null,'Foreign token read a claim.');
        scanCheck($repo->getClaimContext($claim['attemptId'],'consumer-token')===$claim,'Claim projection changed.');
        $expired=new ScanAttemptRepository($fixture->db,static fn(): string=>'2026-11-18T00:01:30Z');
        scanCheck($expired->getClaimContext($claim['attemptId'],'consumer-token')===null,'Expired claim remains readable.');
        $repo->saveProgress($claim['attemptId'],'consumer-token',['state'=>'waiting','nextRunAt'=>'2026-11-18T00:02:00Z']);
        scanCheck($repo->getClaimContext($claim['attemptId'],'consumer-token')===null,'Yielded claim remains readable.');
    },
]];
