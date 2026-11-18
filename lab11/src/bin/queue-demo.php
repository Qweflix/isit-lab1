<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$mode = match (true) {
    in_array('--fail', $argv, true) => 'fail',
    in_array('--flaky', $argv, true) => 'flaky',
    in_array('--slow', $argv, true) => 'slow',
    default => 'normal',
};
$queue = new App\Core\Queue();
$count = $mode === 'normal' ? 3 : 1;
for ($i = 0; $i < $count; $i++) {
    echo $queue->push('demo', ['mode' => $mode, 'seconds' => 20]) . " $mode\n";
}
