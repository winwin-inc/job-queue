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
     *
     * @return int the job Id
     */
    public function put($jobClass, array $payload, $delay = 0, $priority = 1024, $ttr = 60);

    /**
     * @param int $timeout
     *
     * @return \Pheanstalk\Job
     */
    public function reserve($timeout = null);

    /**
     * @param \Pheanstalk\Job|int $job
     *
     * @return bool
     */
    public function delete($job);

    /**
     * @param \Pheanstalk\Job $job
     *
     * @return bool
     */
    public function bury($job);
}
