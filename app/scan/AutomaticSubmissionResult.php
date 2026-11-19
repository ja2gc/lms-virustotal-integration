<?php
declare(strict_types=1);

final class AutomaticSubmissionResult
{
    public function __construct(private ScanResultProcessor $processor) {}
    public function process(int $attemptId,string $leaseToken): void
    {
        $this->processor->process($attemptId,$leaseToken,'automatic');
    }
}
