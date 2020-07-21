<?php


namespace winwin\jobQueue;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use winwin\jobQueue\exception\ServerException;

class JobQueueClusterTest extends TestCase
{
    public function testPut()
    {
        $queue = $this->createQueue();
        foreach (range(1, 2) as $i) {
            try {
                $id = $queue->put(TestJobOption::class, $args = ['arg1' => 'val1']);
                error_log("put job: " . $id);
                $ret = $queue->delete($id);
                error_log("delete job: " . $ret);
            } catch (ServerException $e) {
                sleep(2);
            }
        }
    }

    protected function createQueue()
    {
        $jobQueue = new JobQueueCluster([
            ['host' => 'localhost', 'port' => 11300],
            ['host' => 'localhost', 'port' => 11302],
        ], 'testing');
        $logger = new Logger("Test");
        $logger->pushHandler(new StreamHandler("php://stderr"));
        $jobQueue->setLogger($logger);
        $jobQueue->setRetryInterval(1);
        return $jobQueue;
    }
}
