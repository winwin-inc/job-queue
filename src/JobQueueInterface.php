<?php

namespace winwin\jobQueue;

interface JobQueueInterface
{
    /**
     * Puts job to queue
     *
     * @param string $jobClass
     * @param array  $payload
     * @param int    $delay
     * @param int    $priority
     * @param int    $ttr
     */
    public function put($jobClass, array $payload, $delay = 0, $priority = 1024, $ttr = 60);

    /**
     * @param int $timeout
     *
     * @return object
     */
    public function reserve($timeout = null);

    /**
     * @param object $job
     */
    public function delete($job);

    /**
     * @param object $job
     */
    public function bury($job);
}
