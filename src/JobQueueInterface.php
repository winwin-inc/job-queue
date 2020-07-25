<?php

declare(strict_types=1);

namespace winwin\jobQueue;

interface JobQueueInterface
{
    /**
     * Creates the job instance.
     */
    public function create(JobInterface $job): JobOption;

    /**
     * @param mixed $arguments
     */
    public function put(
        string $jobClass,
        $arguments,
        int $delay = 0,
        int $priority = 1024,
        int $ttr = 60,
        ?string $tube = null
    ): JobId;
}
