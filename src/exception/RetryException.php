<?php


namespace winwin\jobQueue\exception;

class RetryException extends \RuntimeException
{
    private $priority = 1024;

    private $delay = 60;

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }
}
