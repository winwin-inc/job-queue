<?php

declare(strict_types=1);

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

    public function getError(): \Throwable
    {
        return $this->error;
    }

    public function getJob(): Job
    {
        return $this->job;
    }
}
