<?php
declare(strict_types=1);

$group = null;
foreach (array_slice($argv, 1) as $argument) {
    if (!str_starts_with($argument, '--group=')) {
        fwrite(STDERR, "Usage: php tests/run.php [--group=name]\n");
        exit(2);
    }
    $group = substr($argument, 8);
}
$passed = $failed = 0;
foreach (glob(__DIR__ . '/*Test.php') as $file) {
    $suite = require $file;
    if ($group !== null && $suite['group'] !== $group) {
        continue;
    }
    foreach ($suite['tests'] as $name => $test) {
        try {
            $test();
            echo "PASS {$name}\n";
            $passed++;
        } catch (Throwable $error) {
            echo "FAIL {$name}: {$error->getMessage()}\n";
            $failed++;
        }
    }
}
echo "{$passed} passed; {$failed} failed\n";
exit($passed + $failed === 0 ? 2 : ($failed > 0 ? 1 : 0));
