<?php

declare(strict_types=1);

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
     */
    public function __construct(BeanstalkJob $job)
    {
        $this->job = $job;
    }

    public function getJob(): BeanstalkJob
    {
        return $this->job;
    }
}
