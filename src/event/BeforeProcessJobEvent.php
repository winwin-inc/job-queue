<?php


namespace winwin\jobQueue\event;

use Pheanstalk\Job as BeanstalkJob;

class BeforeProcessJobEvent
{
    /**
     * @var BeanstalkJob
     */
    private $job;

    /**
     * BeforeProcessJobEvent constructor.
     * @param BeanstalkJob $job
     */
    public function __construct(BeanstalkJob $job)
    {
        $this->job = $job;
    }

    /**
     * @return BeanstalkJob
     */
    public function getJob(): BeanstalkJob
    {
        return $this->job;
    }

    public function setJob(BeanstalkJob $job): void
    {
        $this->job = $job;
    }
}
