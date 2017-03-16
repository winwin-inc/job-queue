<?php

namespace winwin\jobQueue;

interface WorkerInterface
{
    /**
     * call worker
     */
    public function work();

    public function start();

    public function stop();

    /**
     * @return bool
     */
    public function shouldRun();
}
