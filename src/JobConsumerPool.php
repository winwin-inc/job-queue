<?php


namespace winwin\jobQueue;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\swoole\coroutine\Coroutine;
use Pheanstalk\Pheanstalk;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Swoole\Process;
use Swoole\Process\Pool;

class JobConsumerPool implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '[' . __CLASS__ . '] ';

    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var JobStatService
     */
    private $jobStatService;
    /**
     * @var callable
     */
    private $beanstalkFactory;
    /**
     * @var int
     */
    private $workerNum;
    /**
     * @var int
     */
    private $sleepInterval;
    /**
     * @var Pheanstalk
     */
    private $beanstalk;
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;
    /**
     * @var string
     */
    private $consumerName;

    public function __construct(
        string $consumerName,
        ContainerInterface $container,
        JobStatService $jobStatService,
        AnnotationReaderInterface $annotationReader,
        EventDispatcherInterface $eventDispatcher,
        callable $beanstalkFactory,
        int $workerNum = 1,
        int $sleepInterval = 1
    ) {
        $this->consumerName = $consumerName;
        $this->container = $container;
        $this->annotationReader = $annotationReader;
        $this->jobStatService = $jobStatService;
        $this->eventDispatcher = $eventDispatcher;
        $this->beanstalkFactory = $beanstalkFactory;
        $this->workerNum = $workerNum;
        $this->sleepInterval = $sleepInterval;
    }

    public function start(): void
    {
        $this->logger->info(static::TAG . 'start job queue processor');
        Coroutine::disable();
        $pool = new Pool($this->workerNum);
        $pool->on("WorkerStart", function ($pool, $workerId) {
            $this->createConsumer($pool, $workerId)->start();
        });
        $pool->start();
    }

    private function createConsumer(Pool $pool, $workerId): JobConsumer
    {
        $beanstalk = call_user_func($this->beanstalkFactory, $workerId);

        $consumer = new JobConsumer(
            $this->consumerName,
            $workerId,
            $beanstalk,
            $this->container,
            $this->annotationReader,
            $this->jobStatService,
            $this->eventDispatcher,
            $this->sleepInterval
        );
        $consumer->setLogger($this->logger);

        return $consumer;
    }
}
