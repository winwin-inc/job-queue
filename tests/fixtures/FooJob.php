<?php


namespace winwin\jobQueue\fixtures;

use winwin\jobQueue\JobInterface;
use winwin\jobQueue\JobTrait;

class FooJob implements JobInterface
{
    use JobTrait;

    /**
     * @var string
     */
    private $jobArg;

    /**
     * @return string
     */
    public function getJobArg(): string
    {
        return $this->jobArg;
    }
}
