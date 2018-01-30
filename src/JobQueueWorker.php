<?php

namespace winwin\jobQueue;

use Pheanstalk\Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class JobQueueWorker extends AbstractWorker
{
    /**
     * @var JobQueueInterface
     */
    private $jobQueue;

    /**
     * @var JobFactoryInterface
     */
    private $jobFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var int
     */
    private $sleepInterval = 1;

    public function __construct(JobQueueInterface $jobQueue, JobFactoryInterface $jobFactory, EventDispatcherInterface $eventDispatcher, $maxRequests = 100)
    {
        $this->jobQueue = $jobQueue;
        $this->jobFactory = $jobFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->maxRequests = $maxRequests;
    }

    public function work()
    {
        $job = $this->grabJob();
        if (!$job) {
            return true;
        }
        $event = new GenericEvent($this);
        $event['job'] = $job;
        try {
            $data = json_decode($job->getData(), true);
            if (!isset($data['job'], $data['payload'])) {
                throw new \UnexpectedValueException('invalid job');
            }
            if (!class_exists($data['job'])) {
                throw new \UnexpectedValueException("job {$data['job']} does not exist");
            }
            $consumer = $this->jobFactory->create($data['job']);
            if (!$consumer instanceof JobInterface) {
                throw new \UnexpectedValueException("job {$data['job']} does not implement " . JobInterface::class);
            }
            $event['consumer'] = $consumer;
            $event['payload'] = $data['payload'];
            $this->eventDispatcher->dispatch(Events::BEFORE_PROCESS_JOB, $event);
            $consumer->process($data['payload']);
            $this->jobQueue->delete($job);
            $this->eventDispatcher->dispatch(Events::AFTER_PROCESS_JOB, $event);
            $this->processedJobs++;
            return true;
        } catch (\Pheanstalk\Exception $e) {
            $event['error'] = $e;
            $this->eventDispatcher->dispatch(Events::JOB_FAILED, $event);
            sleep(10);
        } catch (\Exception $e) {
            $event['error'] = $e;
            $this->eventDispatcher->dispatch(Events::JOB_FAILED, $event);
            $this->jobQueue->bury($job);
        } catch (\Error $e) {
            $event['error'] = $e;
            $this->eventDispatcher->dispatch(Events::JOB_FAILED, $event);
            $this->jobQueue->bury($job);
        }
        return false;
    }

    private function grabJob()
    {
        try {
            $job = $this->jobQueue->reserve(1);
            $this->sleepInterval = 1;
            return $job;
        } catch (Exception $e) {
            $event = new GenericEvent($this->jobQueue);
            $event['error'] = $e;
            $this->eventDispatcher->dispatch(Events::SERVER_ERROR, $event);
            if ($this->sleepInterval < 64) {
                $this->sleepInterval *= 2;
            }
            sleep($this->sleepInterval);
            return false;
        }
    }
}
