<?php


namespace winwin\jobQueue;

use kuiper\helper\Arrays;

abstract class AbstractJob implements JobInterface
{
    /**
     * AbstractJob constructor.
     *
     * @param array $arguments
     */
    public function __construct($arguments)
    {
        if (!is_array($arguments)) {
            throw new \InvalidArgumentException("expect an array, got " . gettype($arguments));
        }
        Arrays::assign($this, $arguments);
    }

    public function jsonSerialize()
    {
        return Arrays::toArray($this, true, true);
    }
}
