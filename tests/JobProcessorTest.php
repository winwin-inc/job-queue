<?php

namespace winwin\jobQueue;

use Symfony\Component\EventDispatcher\EventDispatcher;

class JobProcessorTest extends TestCase
{
    public function createProcessor()
    {
        return $processor = new JobProcessor(
            $this->queue = $this->createQueue(),
            new SimpleJobFactory(),
            new EventDispatcher(),
            $this->pidfile = __DIR__.'/queue.pid'
        );
    }

    public function testStart()
    {
        $processor = $this->createProcessor();

        $pid = pcntl_fork();
        if ($pid > 0) {
            $this->queue->put(TestJob::class, $args = [
                'file' => $tmpfile = tempnam(sys_get_temp_dir(), 'job')
            ]);
            usleep(100000);
            $this->assertTrue(file_exists($this->pidfile));
            $content = file_get_contents($tmpfile);
            $this->assertEquals(json_encode($args), $content);
            $processor->stop();
            usleep(1000000);
            $this->assertFalse(file_exists($this->pidfile));
        } elseif ($pid == 0) {
            $processor->start();
            exit;
        } else {
            throw new \RuntimeException("Cannot fork");
        }
    }
}
