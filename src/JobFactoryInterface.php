<?php

namespace winwin\jobQueue;

interface JobFactoryInterface
{
    /**
     * Creates the job instance
     *
     * @param string $handlerClass
     *
     * @return JobInterface
     */
    public function create(string $handlerClass, array $arguments): JobInterface;
}
