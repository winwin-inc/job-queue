<?php


namespace winwin\jobQueue;

use kuiper\swoole\pool\PoolInterface;

class JobQueuePool implements JobQueueInterface
{
    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * JobQueuePool constructor.
     * @param PoolInterface $pool
     */
    public function __construct(PoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @inheritDoc
     */
    public function create(JobInterface $job): JobOption
    {
        return $this->pool->take()->create($job);
    }

    public function put(string $jobClass, $arguments, int $delay = 0, int $priority = 1024, int $ttr = 60, ?string $tube = null): JobId
    {
        return $this->pool->take()->put($jobClass, $arguments, $delay, $priority, $ttr, $tube);
    }
}
