<?php

namespace winwin\jobQueue;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Process\ProcessUtils;

class ScheduleWorker extends AbstractWorker
{
    /**
     * All of the events on the schedule.
     *
     * @var array
     */
    protected $jobs = [];

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var JobQueueInterface
     */
    private $jobQueue;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $mutexPath;

    public function __construct(EventDispatcherInterface $eventDispatcher, $basePath, $mutexPath, $maxRequests = 100)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->basePath = $basePath;
        $this->mutexPath = $mutexPath;
        $this->maxRequests = $maxRequests;
    }

    /**
     * Add a new callback job to the schedule.
     *
     * @param  string  $callback
     * @param  array   $parameters
     * @return ScheduleJob
     */
    public function call($callback, array $parameters = [])
    {
        return $this->addJob($job = new CallbackScheduleJob($callback, $parameters));
    }

    /**
     * Add job to schedule
     *
     * @return ScheduleJob
     */
    public function job($jobName, array $parameters)
    {
        if ($this->jobQueue === null) {
            throw new \LogicException("jobQueue is null, call setJobQueue() method first");
        }
        $job = new CallbackScheduleJob(function () use ($jobName, $parameters) {
            $this->jobQueue->put($jobName, $parameters);
        });
        $job->description = $jobName;

        return $this->addJob($job);
    }

    /**
     * Add a new command job to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return ScheduleJob
     */
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            $command .= ' '.$this->compileParameters($parameters);
        }

        return $this->addJob($job = new ScheduleJob($command));
    }

    /**
     * {@inheritdoc}
     */
    public function work()
    {
        $startTime = time();

        foreach ($this->jobs as $job) {
            if (!$job->isDue()) {
                continue;
            }
            if (!$job->filtersPass()) {
                continue;
            }
            $event = new GenericEvent($this);
            $event['job'] = $job;
            $shouldRun = $this->eventDispatcher->dispatch(Events::BEFORE_SCHEDULE_JOB, $event);
            if ($shouldRun === false) {
                continue;
            }

            try {
                $response = $job->run();
                $event->setArgument('response', $response);
                $this->eventDispatcher->dispatch(Events::AFTER_SCHEDULE_JOB, $event);
            } catch (\Exception $e) {
                $event['error'] = $e;
                $this->eventDispatcher->dispatch(Events::SCHEDULE_JOB_FAILED, $event);
            }
        }
        // skip to next minute
        $time = time() - $startTime;
        if ($time < 60) {
            sleep(60 - $time);
        }
        $this->processedJobs++;
    }

    /**
     * Compile parameters for a command.
     *
     * @param  array  $parameters
     * @return string
     */
    protected function compileParameters(array $parameters)
    {
        $options = [];
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $value = implode(' ', array_map([ProcessUtils::class, 'escapeArgument'], $value));
            } elseif (!is_numeric($value) && !preg_match('/^(-.$|--.*)/i', $value)) {
                $value = ProcessUtils::escapeArgument($value);
            }

            $options[] = is_numeric($key) ? $value : "{$key}={$value}";
        }
        return implode(' ', $options);
    }

    protected function addJob($job)
    {
        $job->basePath = $this->basePath;
        $job->mutexPath = $this->mutexPath;
        $this->jobs[] = $job;

        return $job;
    }

    /**
     * Get all of the jobs on the schedule.
     *
     * @return ScheduleJob[]
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    public function setJobQueue(JobQueueInterface $jobQueue)
    {
        $this->jobQueue = $jobQueue;
        return $this;
    }
}