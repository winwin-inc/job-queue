<?php

namespace winwin\jobQueue;

interface JobProcessorInterface
{
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
     * @param int $workers
     */
    public function setWorkers($workers);
}
