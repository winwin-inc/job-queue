<?php

declare(strict_types=1);

namespace winwin\jobQueue\exception;

class RetryException extends \RuntimeException
{
    private $priority = 1024;

    private $delay = 60;

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }
}
