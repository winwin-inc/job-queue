<?php


namespace winwin\jobQueue\event;

use Pheanstalk\Exception;
use Pheanstalk\Job;

class BeanstalkErrorEvent
{
    /**
     * @var Exception
     */
    private $error;

    /**
     * @var Job
     */
    private $job;

    public function __construct(Exception $error, ?Job $job)
    {
        $this->error = $error;
        $this->job = $job;
    }

    /**
     * @return Exception
     */
    public function getError(): Exception
    {
        return $this->error;
    }

    /**
     * @return Job
     */
    public function getJob(): ?Job
    {
        return $this->job;
    }
}
