<?php

namespace winwin\jobQueue;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LogEventSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    public static function getSubscribedEvents()
    {
        return [
            Events::WORKER_START => 'onWorkerStart',
            Events::WORKER_STOP => 'onWorkerStop',
            Events::BEFORE_PROCESS_JOB => 'beforeProcessJob',
            Events::AFTER_PROCESS_JOB => 'afterProcessJob',
            Events::JOB_FAILED => 'onJobFailed',
            Events::PROCESSOR_START => 'onProcessorStart',
            Events::BEFORE_PROCESSOR_STOP => 'beforeProcessorStop',
            Events::AFTER_PROCESSOR_STOP => 'afterProcessorStop',
            Events::BEFORE_PROCESSOR_RELOAD => 'beforeProcessorReload',
            Events::AFTER_PROCESSOR_RELOAD => 'afterProcessorReload',
        ];
    }

    public function onWorkerStart($event)
    {
        $this->logger->info(sprintf("[QueueWorker] start pid=%d", getmypid()));
    }

    public function onWorkerStop($event)
    {
        $this->logger->info(sprintf("[QueueWorker] stop pid=%d", getmypid()));
    }

    public function beforeProcessJob($event)
    {
        $job = $event['job'];
        $this->logger->info(sprintf("[QueueWorker] process job={id: %d, payload: %s} pid=%d", $job->getId(), $job->getData(), getmypid()));
    }

    public function afterProcessJob($event)
    {
        $job = $event['job'];
        $this->logger->info(sprintf("[QueueWorker] finish job={id: %d, payload: %s} pid=%d", $job->getId(), $job->getData(), getmypid()));
    }

    public function onJobFailed($event)
    {
        $job = $event['job'];
        $error = $event['error'];
        $this->logger->info(sprintf("[QueueWorker] job-failed job={id: %d, payload: %s} pid=%d, error=%s", $job->getId(), $job->getData(), getmypid(), $error));
    }

    public function onProcessorStart($event)
    {
        $this->logger->info(sprintf("[QueueProcessor] start pid=%d", getmypid()));
    }

    public function beforeProcessorStop($event)
    {
        $this->logger->info(sprintf("[QueueProcessor] stop pid=%d", getmypid()));
    }

    public function afterProcessorStop($event)
    {
    }

    public function beforeProcessorReload($event)
    {
        $this->logger->info(sprintf("[QueueProcessor] reload pid=%d", getmypid()));
    }

    public function afterProcessorReload($event)
    {
    }
}
