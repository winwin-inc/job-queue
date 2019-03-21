<?php

require __DIR__ . '/queue.php';

use Symfony\Component\EventDispatcher\EventDispatcher;
use winwin\jobQueue\JobProcessor;
use winwin\jobQueue\JobQueueWorker;
use winwin\jobQueue\LogEventSubscriber;
use winwin\jobQueue\SimpleJobFactory;

$logger = new \Monolog\Logger("queue");
$logger->pushHandler(new \Monolog\Handler\StreamHandler("php://stderr"));

$jobProcessor = new JobProcessor(
    $eventDispatcher = new EventDispatcher(),
    __DIR__.'/queue.pid'
);
$subscriber = new LogEventSubscriber();
$subscriber->setLogger($logger);
$eventDispatcher->addSubscriber($subscriber);
$jobFactory = new SimpleJobFactory();

foreach ($jobQueue->getJobQueueList() as $queue) {
    $worker = new JobQueueWorker($queue, $jobFactory, $eventDispatcher);
    $jobProcessor->addWorker($worker);
}

$jobProcessor->start();
