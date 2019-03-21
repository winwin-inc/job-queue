<?php

namespace winwin\jobQueue;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;

class JobQueue implements JobQueueInterface
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $tube;

    /**
     * @var string[]
     */
    private $watchTubes = [];

    /**
     * @var PheanstalkInterface
     */
    private $beanstalk;

    /**
     * @var bool
     */
    private $watched = false;

    public function __construct($host = 'localhost', $port = 11300, $tube = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->tube = $tube;
    }

    /**
     * {@inheritdoc}
     */
    public function put($jobClass, array $payload, $delay = 0, $priority = 1024, $ttr = 60)
    {
        return $this->getBeanstalk()->put(json_encode([
            'job' => $jobClass,
            'payload' => $payload,
        ]), $priority, $delay, $ttr);
    }

    /**
     * {@inheritdoc}
     */
    public function reserve($timeout = null)
    {
        return $this->getBeanstalk($watch = true)->reserve($timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($job)
    {
        try {
            if (!is_object($job)) {
                $job = $this->getBeanstalk()->peek($job);
            }
            $this->getBeanstalk()->delete($job);
            return true;
        } catch (ServerException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bury($job)
    {
        return $this->getBeanstalk()->bury($job);
    }

    public function getBeanstalk($watch = false)
    {
        if (null === $this->beanstalk) {
            $this->beanstalk = new Pheanstalk($this->host, $this->port);
            if ($watch) {
                $tubes = $this->watchTubes;
                if ($this->tube) {
                    $tubes[] = $this->tube;
                }

                $tubes = array_diff($tubes, array_keys($this->beanstalk->listTubesWatched()));
                if (!empty($tubes)) {
                    $tube = array_shift($tubes);
                    $this->beanstalk->watchOnly($tube);

                    foreach ($tubes as $tube) {
                        $this->beanstalk->watch($tube);
                    }
                }
            }
        }

        if ($this->tube && $this->tube != $this->beanstalk->listTubeUsed()) {
            $this->beanstalk->useTube($this->tube);
        }
        return $this->beanstalk;
    }

    public function disconnect()
    {
        $this->beanstalk = null;
    }

    public function setBeanstalk(PheanstalkInterface $beanstalk)
    {
        $this->beanstalk = $beanstalk;
        return $this;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    public function getTube()
    {
        return $this->tube;
    }

    public function setTube($tube)
    {
        $this->tube = $tube;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getWatchTubes()
    {
        return $this->watchTubes;
    }

    /**
     * @param string[] $watchTubes
     * @return $this
     */
    public function setWatchTubes(array $watchTubes)
    {
        $this->watchTubes = $watchTubes;
        return $this;
    }
}
