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
            Events::BEFORE_SCHEDULE_JOB => 'beforeScheduleJob',
            Events::AFTER_SCHEDULE_JOB => 'afterScheduleJob',
            Events::SCHEDULE_JOB_FAILED => 'onScheduleJobFailed',
        ];
    }

    public function onWorkerStart($event)
    {
        $this->logger->info(sprintf("[Worker] start pid=%d", getmypid()));
    }

    public function onWorkerStop($event)
    {
        $this->logger->info(sprintf("[Worker] stop pid=%d", getmypid()));
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
        $this->logger->info(sprintf("[Processor] start pid=%d", getmypid()));
    }

    public function beforeProcessorStop($event)
    {
        $this->logger->info(sprintf("[Processor] stop pid=%d", getmypid()));
    }

    public function afterProcessorStop($event)
    {
    }

    public function beforeProcessorReload($event)
    {
        $this->logger->info(sprintf("[Processor] reload pid=%d", getmypid()));
    }

    public function afterProcessorReload($event)
    {
    }

    public function beforeScheduleJob($event)
    {
        $job = $event['job'];
        $this->logger->info(sprintf("[ScheduleWorker] process job=" . $job->getSummaryForDisplay()));
    }

    public function afterScheduleJob($event)
    {
        $job = $event['job'];
        $response = $event['response'];
        if (is_numeric($response)) {
            if ($response == 0) {
                $this->logger->info("[ScheduleWorker] finish job=" . $job->getSummaryForDisplay());
            } else {
                $this->logger->error(sprintf("[ScheduleWorker] failed job=%s ret=%d", $job->getSummaryForDisplay(), $ret));
            }
        } else {
            $this->logger->info("[ScheduleWorker] finish job=" . $job->getSummaryForDisplay());
        }
    }

    public function onScheduleJobFailed($event)
    {
        $job = $event['job'];
        $error = $event['error'];
        $this->logger->info(sprintf("[ScheduleWorker] job-failed job=%s error=%s", $job->getSummaryForDisplay(), $error));
    }
}
