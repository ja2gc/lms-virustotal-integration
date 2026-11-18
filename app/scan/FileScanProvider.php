<?php
declare(strict_types=1);

interface FileScanProvider
{
    public function getResult(string $analysisId, string $expectedSha256): array;
}
