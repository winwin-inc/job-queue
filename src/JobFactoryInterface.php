<?php

namespace winwin\jobQueue;

interface JobFactoryInterface
{
    /**
     * Creates the job instance
     *
     * @param string $jobClass
     *
     * @return JobInterface
     */
    public function create($jobClass);
}
