<?php


namespace winwin\jobQueue\event;

use Pheanstalk\Job as BeanstalkJob;

class AfterProcessJobEvent
{
    /**
     * @var BeanstalkJob
     */
    private $job;

    /**
     * AfterProcessJobEvent constructor.
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
}
