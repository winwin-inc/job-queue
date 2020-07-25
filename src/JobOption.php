<?php

declare(strict_types=1);

namespace winwin\jobQueue;

final class JobOption
{
    /**
     * @var JobQueueInterface
     */
    private $jobQueue;
    /**
     * @var JobInterface
     */
    private $job;
    /**
     * @var int
     */
    private $priority;
    /**
     * @var string
     */
    private $tube;
    /**
     * @var int
     */
    private $ttr;
    /**
     * @var int
     */
    private $delay;

    public function __construct(JobQueueInterface $jobQueue, JobInterface $job)
    {
        $this->jobQueue = $jobQueue;
        $this->job = $job;
        $this->priority = 1024;
        $this->ttr = 60;
        $this->delay = 0;
    }

    public function priority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function tube(string $tube): self
    {
        $this->tube = $tube;

        return $this;
    }

    public function timeToRun(int $ttr): self
    {
        $this->ttr = $ttr;

        return $this;
    }

    public function delay(int $delaySeconds): self
    {
        $this->delay = $delaySeconds;

        return $this;
    }

    public function put(): JobId
    {
        return $this->jobQueue->put(
            get_class($this->job),
            $this->job->jsonSerialize(),
            $this->delay,
            $this->priority,
            $this->ttr,
            $this->tube
        );
    }
}
