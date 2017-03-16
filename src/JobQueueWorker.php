<?php

namespace winwin\jobQueue;

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

    public function __construct(JobQueueInterface $jobQueue, JobFactoryInterface $jobFactory, EventDispatcherInterface $eventDispatcher, $maxRequests = 100)
    {
        $this->jobQueue = $jobQueue;
        $this->jobFactory = $jobFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->maxRequests = $maxRequests;
    }

    public function work()
    {
        $job = $this->jobQueue->reserve(1);
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
                throw new \UnexpectedValueException("job {$data['job']} does not implement ".JobInterface::class);
            }
            $event['consumer'] = $consumer;
            $event['payload'] = $data['payload'];
            $this->eventDispatcher->dispatch(Events::BEFORE_PROCESS_JOB, $event);
            $consumer->process($data['payload']);
            $this->jobQueue->delete($job);
            $this->eventDispatcher->dispatch(Events::AFTER_PROCESS_JOB, $event);
            $this->processedJobs++;
        } catch (\Pheanstalk\Exception $e) {
            $event['error'] = $e;
            $this->eventDispatcher->dispatch(Events::JOB_FAILED, $event);
            sleep(100);
        } catch (\Exception $e) {
            $event['error'] = $e;
            $this->eventDispatcher->dispatch(Events::JOB_FAILED, $event);
            $this->jobQueue->bury($job);
        } catch (\Error $e) {
            $event['error'] = $e;
            $this->eventDispatcher->dispatch(Events::JOB_FAILED, $event);
            $this->jobQueue->bury($job);
        }
    }
}
