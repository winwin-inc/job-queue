<?php

namespace winwin\jobQueue;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use winwin\jobQueue\exception\ServerException;

class JobQueueCluster implements JobQueueInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var JobQueue[]
     */
    private $jobQueues;

    /**
     * @var int
     */
    private $current;

    /**
     * @var array
     */
    private $statuses;

    /**
     * @var int
     */
    private $retryInterval = 10;

    public function __construct(array $servers, $tube = null)
    {
        foreach ($servers as $server) {
            $this->jobQueues[] = new JobQueue(
                isset($server['host']) ? $server['host'] : 'localhost',
                isset($server['port']) ? $server['port'] : 11300,
                $tube
            );
        }
        $this->current = mt_rand(0, count($servers)-1);
        $this->statuses = [];
    }

    /**
     * @return int
     */
    public function getRetryInterval()
    {
        return $this->retryInterval;
    }

    /**
     * @param int $retryInterval
     */
    public function setRetryInterval($retryInterval)
    {
        $this->retryInterval = $retryInterval;
    }

    /**
     * Puts job to queue
     *
     * @param string $jobClass
     * @param array $payload
     * @param int $delay
     * @param int $priority
     * @param int $ttr
     *
     * @return int the job Id
     */
    public function put($jobClass, array $payload, $delay = 0, $priority = 1024, $ttr = 60)
    {
        while (true) {
            try {
                return $this->getCurrentJobQueue()->put($jobClass, $payload, $delay, $priority, $ttr);
            } catch (\Pheanstalk\Exception $e) {
                $this->logger && $this->logger->error("[JobQueue] Cannot put job: " . $e->getMessage());
                try {
                    $this->nextJobQueue();
                } catch (ServerException $serverException) {
                    $this->logger && $this->logger->critical("[JobQueue] Put job failed", [
                        'job' => $jobClass,
                        'payload' => $payload
                    ]);
                    throw new ServerException($e->getMessage(), 0, $e);
                }
            }
        }
    }

    public function getJobQueueList()
    {
        return $this->jobQueues;
    }

    private function getCurrentJobQueue()
    {
        return $this->jobQueues[$this->current];
    }

    private function nextJobQueue()
    {
        $this->statuses[$this->current] = time();
        $retries = 0;
        while ($retries < count($this->jobQueues)) {
            $retries++;
            $this->current++;
            if ($this->current >= count($this->jobQueues)) {
                $this->current = 0;
            }
            if (!isset($this->statuses[$this->current])
                || time() - $this->statuses[$this->current] > $this->retryInterval) {
                return;
            }
        }
        throw new ServerException();
    }

    /**
     * @param int $timeout
     *
     * @return \Pheanstalk\Job
     */
    public function reserve($timeout = null)
    {
        throw new \BadMethodCallException("Cannot reserve from cluster");
    }

    /**
     * @param \Pheanstalk\Job|int $job
     *
     * @return bool
     */
    public function delete($job)
    {
        throw new \BadMethodCallException("Cannot delete from cluster");
    }

    /**
     * @param \Pheanstalk\Job $job
     *
     * @return bool
     */
    public function bury($job)
    {
        throw new \BadMethodCallException("Cannot bury from cluster");
    }
}
