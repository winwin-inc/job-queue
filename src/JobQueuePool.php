<?php


namespace winwin\jobQueue;

use kuiper\swoole\pool\PoolInterface;

class JobQueuePool implements JobFactoryInterface, JobQueueInterface
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
    public function create(string $handlerClass, array $arguments): JobInterface
    {
        return $this->pool->take()->create($handlerClass, $arguments);
    }

    public function put(string $jobClass, array $arguments, int $delay = 0, int $priority = 1024, int $ttr = 60, ?string $tube = null): int
    {
        return $this->pool->take()->put($jobClass, $arguments, $delay, $priority, $ttr, $tube);
    }
}
