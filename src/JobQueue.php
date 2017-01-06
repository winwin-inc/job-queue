<?php

namespace winwin\jobQueue;

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
        $this->getBeanstalk()->put(json_encode([
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
        return $this->getBeanstalk()->delete($job);
    }

    public function getBeanstalk($watch = false)
    {
        if (null === $this->beanstalk) {
            $this->beanstalk = new Pheanstalk($this->host, $this->port);
            if ($this->tube) {
                $this->beanstalk->useTube($this->tube);
            }
        }

        if ($watch && !$this->watched) {
            if ($this->tube) {
                $this->beanstalk->watchOnly($this->tube);
            }
            $this->watched = true;
        }

        return $this->beanstalk;
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
}
