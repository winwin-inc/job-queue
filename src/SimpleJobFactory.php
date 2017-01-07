<?php

namespace winwin\jobQueue;

class SimpleJobFactory implements JobFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($jobClass)
    {
        return new $jobClass();
    }
}
