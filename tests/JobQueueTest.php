<?php

namespace winwin\jobQueue;

class JobQueueTest extends TestCase
{
    public function testPut()
    {
        $queue = $this->createQueue();
        $queue->put(TestJob::class, $args = ['arg1' => 'val1']);

        $job = $queue->getBeanstalk()->peekReady();
        // print_r($job);
        $this->assertInstanceOf(\Pheanstalk\Job::class, $job);
        $this->assertEquals('{"job":"winwin\\\\jobQueue\\\\TestJob","payload":{"arg1":"val1"}}', $job->getData());
    }
}
