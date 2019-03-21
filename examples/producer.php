<?php

require(__DIR__.'/queue.php');

while (true) {
    $jobQueue->put(winwin\jobQueue\TestJob::class, [
        'file' => '/tmp/foo-' . date('YmdHis'),
    ]);
    sleep(1);
}
