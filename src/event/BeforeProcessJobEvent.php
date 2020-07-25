<?php

declare(strict_types=1);

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
     */
    public function __construct(BeanstalkJob $job)
    {
        $this->job = $job;
    }

    public function getJob(): BeanstalkJob
    {
        return $this->job;
    }

    public function setJob(BeanstalkJob $job): void
    {
        $this->job = $job;
    }
}
