<?php

namespace winwin\jobQueue;

use Webmozart\Assert\Assert;

class TestJobOption implements JobOptionInterface
{
    public function process(array $arguments)
    {
        Assert::keyExists($arguments, 'file');
        if (isset($arguments['file'])) {
            file_put_contents($arguments['file'], json_encode($arguments));
        }
    }
}
