<?php

namespace winwin\jobQueue;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

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
            Events::LOCK_CREATED => 'onLockCreated',
            Events::LOCK_RELEASED => 'onLockReleased',
            Events::SCHEDULE_JOB_ADDED => 'onScheduleJobAdded'
        ];
    }

    /**
     * @param GenericEvent $event
     */
    public function onWorkerStart($event)
    {
        $worker = $event->getSubject();
        cli_set_process_title("worker: " . get_class($worker));
        $this->logger->info(sprintf("[Worker] start %s pid=%d", get_class($worker), getmypid()));
    }

    /**
     * @param GenericEvent $event
     */
    public function onWorkerStop($event)
    {
        $worker = $event->getSubject();
        $this->logger->info(sprintf("[Worker] stop %s pid=%d", get_class($worker), getmypid()));
    }

    /**
     * @param GenericEvent $event
     */
    public function beforeProcessJob($event)
    {
        $job = $event['job'];
        $this->logger->info(sprintf("[QueueWorker] process job={id: %d, payload: %s} pid=%d", $job->getId(), $job->getData(), getmypid()));
    }

    /**
     * @param GenericEvent $event
     */
    public function afterProcessJob($event)
    {
        $job = $event['job'];
        $this->logger->info(sprintf("[QueueWorker] finish job={id: %d, payload: %s} pid=%d", $job->getId(), $job->getData(), getmypid()));
    }

    /**
     * @param GenericEvent $event
     */
    public function onJobFailed($event)
    {
        $job = $event['job'];
        $error = $event['error'];
        $this->logger->info(sprintf("[QueueWorker] job-failed job={id: %d, payload: %s} pid=%d, error=%s", $job->getId(), $job->getData(), getmypid(), $error));
    }

    /**
     * @param GenericEvent $event
     */
    public function onProcessorStart($event)
    {
        $this->logger->info(sprintf("[Processor] start pid=%d", getmypid()));
    }

    /**
     * @param GenericEvent $event
     */
    public function beforeProcessorStop($event)
    {
        $this->logger->info(sprintf("[Processor] stop pid=%d", getmypid()));
    }

    /**
     * @param GenericEvent $event
     */
    public function afterProcessorStop($event)
    {
    }

    /**
     * @param GenericEvent $event
     */
    public function beforeProcessorReload($event)
    {
        $this->logger->info(sprintf("[Processor] reload pid=%d", getmypid()));
    }

    /**
     * @param GenericEvent $event
     */
    public function afterProcessorReload($event)
    {
    }

    /**
     * @param GenericEvent $event
     */
    public function beforeScheduleJob($event)
    {
        $job = $event['job'];
        $this->logger->info(sprintf("[ScheduleWorker] process job=" . $job->getSummaryForDisplay()));
    }

    /**
     * @param GenericEvent $event
     */
    public function afterScheduleJob($event)
    {
        $job = $event['job'];
        $response = $event['response'];
        if (is_numeric($response)) {
            if ($response == 0) {
                $this->logger->info("[ScheduleWorker] finish job=" . $job->getSummaryForDisplay());
            } else {
                $this->logger->error($message = sprintf("[ScheduleWorker] failed job=%s error=%s", $job->getSummaryForDisplay(), $response));
                @file_put_contents($job->output, date('c') . ' ' . $message.PHP_EOL, FILE_APPEND);
            }
        } else {
            $this->logger->info("[ScheduleWorker] finish job=" . $job->getSummaryForDisplay());
        }
    }

    /**
     * @param GenericEvent $event
     */
    public function onScheduleJobFailed($event)
    {
        $job = $event['job'];
        $error = $event['error'];
        $this->logger->info($message = sprintf("[ScheduleWorker] job-failed job=%s error=%s", $job->getSummaryForDisplay(), $error));
        @file_put_contents($job->output, date('c') . ' ' . $message.PHP_EOL, FILE_APPEND);
    }

    /**
     * @param GenericEvent $event
     */
    public function onScheduleJobAdded($event)
    {
        /** @var ScheduleJob $job */
        $job = $event->getSubject();
        $this->logger->info(sprintf("[ScheduleWorker] schedule command '%s' with '%s'", $job->getCommand(), $job->getExpression()));
    }

    /**
     * @param GenericEvent $event
     */
    public function onLockCreated($event)
    {
        $lock = $event->getSubject();
        $this->logger->info(sprintf("[Processor] create lock %s", $lock->getFile()));
    }

    /**
     * @param GenericEvent $event
     */
    public function onLockReleased($event)
    {
        $lock = $event->getSubject();
        $this->logger->info(sprintf("[Processor] release lock %s", $lock->getFile()));
    }
}
