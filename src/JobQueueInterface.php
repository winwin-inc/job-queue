<?php


namespace winwin\jobQueue;

interface JobQueueInterface
{
    public function put(
        string $jobClass,
        array $arguments,
        int $delay = 0,
        int $priority = 1024,
        int $ttr = 60,
        ?string $tube = null
    ): int;
}
