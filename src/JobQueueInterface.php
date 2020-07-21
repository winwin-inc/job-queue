<?php


namespace winwin\jobQueue;

interface JobQueueInterface
{
    /**
     * Creates the job instance
     *
     * @return JobOption
     */
    public function create(JobInterface $job): JobOption;

    /**
     * @param string $jobClass
     * @param mixed $arguments
     * @param int $delay
     * @param int $priority
     * @param int $ttr
     * @param string|null $tube
     * @return JobId
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
