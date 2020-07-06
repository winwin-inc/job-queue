<?php


namespace winwin\jobQueue\event;

use Pheanstalk\Job;

class JobFailedEvent
{
    /**
     * @var \Throwable
     */
    private $error;

    /**
     * @var Job
     */
    private $job;

    public function __construct(\Throwable $error, Job $job)
    {
        $this->error = $error;
        $this->job = $job;
    }

    /**
     * @return \Throwable
     */
    public function getError(): \Throwable
    {
        return $this->error;
    }

    /**
     * @return Job
     */
    public function getJob(): Job
    {
        return $this->job;
    }
}
