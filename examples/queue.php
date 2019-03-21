<?php

require __DIR__ . '/../vendor/autoload.php';

use winwin\jobQueue\JobQueueCluster;

$jobQueue = new JobQueueCluster([
    ['host' => 'localhost', 'port' => 11300],
    ['host' => 'localhost', 'port' => 11302],
], 'my-tube');
