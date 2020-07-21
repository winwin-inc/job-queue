<?php

namespace winwin\jobQueue;

use winwin\jobQueue\fixtures\FooJob;

class JobTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $fooJob = new FooJob(['job_arg' => 1]);
        $this->assertEquals(1, $fooJob->getJobArg());
        $this->assertEquals(['job_arg' => 1], $fooJob->jsonSerialize());
    }
}
