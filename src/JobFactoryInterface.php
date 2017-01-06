<?php

namespace winwin\jobQueue;

interface JobFactoryInterface
{
    /**
     * @var string $jobClass
     */
    public function create($jobClass);
}
