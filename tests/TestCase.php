<?php

namespace winwin\jobQueue;

use Pheanstalk\Exception\ServerException;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        (new \Dotenv\Dotenv(__DIR__))->load();
    }

    protected function createQueue()
    {
        $host = getenv('BEANSTALK_HOST') ?: 'localhost';
        $port = getenv('BEANSTALK_PORT') ?: 11300;
        $tube = getenv('BEANSTALK_TUBE') ?: 'testing';
        $beanstalk = new \Pheanstalk\Pheanstalk($host, $port);
        $beanstalk->watchOnly($tube);

        try {
            while ($job = $beanstalk->peekReady()) {
                $beanstalk->delete($job);
            }
        } catch (ServerException $e) {
        }
        return new JobQueue($host, $port, $tube);
    }
}
