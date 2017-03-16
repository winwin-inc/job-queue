<?php

namespace winwin\jobQueue;

abstract class AbstractWorker implements WorkerInterface
{
    /**
     * @var int
     */
    protected $maxRequests;

    /**
     * @var int
     */
    protected $processedJobs;

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        $this->processedJobs = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function shouldRun()
    {
        return $this->processedJobs < $this->maxRequests;
    }
}
