<?php


namespace winwin\jobQueue;

interface JobProcessorInterface
{
    public function process(JobInterface $job): void;
}
