<?php

namespace winwin\jobQueue;

interface JobProcessorInterface
{
    /**
     * @param WorkerInterface $worker
     */
    public function addWorker(WorkerInterface $worker);

    /**
     * Starts processor
     */
    public function start();

    /**
     * Stops processor
     */
    public function stop();

    /**
     * Reload workers
     */
    public function reload();

    /**
     * Check the processor is alive
     */
    public function isAlive();
}
