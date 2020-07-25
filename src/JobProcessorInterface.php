<?php

declare(strict_types=1);

namespace winwin\jobQueue;

interface JobProcessorInterface
{
    public function process(JobInterface $job): void;
}
