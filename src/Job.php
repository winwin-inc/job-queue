<?php

namespace winwin\jobQueue;

final class Job implements JobInterface
{
    /**
     * @var JobQueueInterface
     */
    private $jobQueue;
    /**
     * @var string
     */
    private $jobClass;
    /**
     * @var array
     */
    private $arguments;
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

    public function __construct(JobQueueInterface $jobQueue, string $jobClass, array $arguments)
    {
        $this->jobQueue = $jobQueue;
        $this->jobClass = $jobClass;
        $this->arguments = $arguments;
        $this->priority = 1024;
        $this->ttr = 60;
        $this->delay = 0;
    }

    public function priority(int $priority): JobInterface
    {
        $this->priority = $priority;
        return $this;
    }

    public function tube(string $tube): JobInterface
    {
        $this->tube = $tube;

        return $this;
    }

    public function timeToRun(int $ttr): JobInterface
    {
        $this->ttr = $ttr;

        return $this;
    }

    public function delay(int $delaySeconds): JobInterface
    {
        $this->delay = $delaySeconds;
        return $this;
    }

    public function put(): int
    {
        return $this->jobQueue->put(
            $this->jobClass,
            $this->arguments,
            $this->delay,
            $this->priority,
            $this->ttr,
            $this->tube
        );
    }
}
