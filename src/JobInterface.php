<?php

namespace winwin\jobQueue;

interface JobInterface
{
    /**
     * @param array $arguments
     */
    public function process(array $arguments);
}
