<?php

namespace winwin\jobQueue;

class TestJob implements JobInterface
{
    public function process(array $arguments)
    {
        if (isset($arguments['file'])) {
            file_put_contents($arguments['file'], json_encode($arguments));
        }
    }
}
