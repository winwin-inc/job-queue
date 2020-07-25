<?php

declare(strict_types=1);

namespace winwin\jobQueue\listener;

use kuiper\event\annotation\EventListener;
use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\BootstrapEvent;
use kuiper\swoole\event\ShutdownEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Swoole\Process;

/**
 * @EventListener()
 */
class StopJobConsumer implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var StartJobConsumer
     */
    private $startJobConsumer;

    /**
     * {@inheritdoc}
     *
     * @param BootstrapEvent
     */
    public function __invoke($event): void
    {
        Process::kill($this->startJobConsumer->getJobConsumerPid());
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvent(): string
    {
        return ShutdownEvent::class;
    }
}