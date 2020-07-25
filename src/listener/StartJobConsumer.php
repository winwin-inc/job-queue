<?php

declare(strict_types=1);

namespace winwin\jobQueue\listener;

use kuiper\event\annotation\EventListener;
use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\BootstrapEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Swoole\Process;
use winwin\jobQueue\JobConsumerPool;

/**
 * @EventListener()
 */
class StartJobConsumer implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var JobConsumerPool
     */
    private $jobConsumerPool;

    /**
     * @var int
     */
    private $pid;

    /**
     * StartJobProcessor constructor.
     */
    public function __construct(JobConsumerPool $jobProcessor)
    {
        $this->jobConsumerPool = $jobProcessor;
    }

    /**
     * {@inheritdoc}
     *
     * @param BootstrapEvent
     */
    public function __invoke($event): void
    {
        $process = new Process(function () {
            $this->jobConsumerPool->start();
        });
        $this->pid = $process->start();
        $this->logger->info(static::TAG.'start job consumer', ['pid' => $this->pid]);
    }

    public function getJobConsumerPid(): int
    {
        return $this->pid;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvent(): string
    {
        return BootstrapEvent::class;
    }
}
