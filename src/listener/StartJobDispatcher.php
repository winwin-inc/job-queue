<?php


namespace winwin\jobQueue\listener;

use kuiper\event\annotation\EventListener;
use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\BootstrapEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Swoole\Process;
use winwin\jobQueue\JobDispatcher;

/**
 * Class StartJobProcessor
 * @package winwin\jobQueue\listener
 * @EventListener()
 */
class StartJobDispatcher implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '[' . __CLASS__ . '] ';

    /**
     * @var JobDispatcher
     */
    private $jobProcessor;

    /**
     * StartJobProcessor constructor.
     * @param JobDispatcher $jobProcessor
     */
    public function __construct(JobDispatcher $jobProcessor)
    {
        $this->jobProcessor = $jobProcessor;
    }

    /**
     * @inheritDoc
     *
     * @param BootstrapEvent
     */
    public function __invoke($event): void
    {
        $process = new Process(function () {
            $this->jobProcessor->start();
        });
        $pid = $process->start();
        $this->logger->info(static::TAG . 'start job processor', ['pid' => $pid]);
    }

    /**
     * @inheritDoc
     */
    public function getSubscribedEvent(): string
    {
        return BootstrapEvent::class;
    }
}
