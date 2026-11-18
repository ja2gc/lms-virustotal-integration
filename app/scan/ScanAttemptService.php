<?php
declare(strict_types=1);
final class ScanAttemptService
{
    public function __construct(private ScanAttemptRepository $repository) {}
    public function requestRescan(int $submissionId,int $actorId,string $requestToken): array
    {
        return $this->repository->requestRescan($submissionId,$actorId,$requestToken);
    }
}
