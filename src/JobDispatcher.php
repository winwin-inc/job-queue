<?php


namespace winwin\jobQueue;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\swoole\coroutine\Coroutine;
use Pheanstalk\Exception as BeanstalkException;
use Pheanstalk\Job as BeanstalkJob;
use Pheanstalk\Pheanstalk;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Swoole\Process\Pool;
use winwin\jobQueue\annotation\JobProcessor;
use winwin\jobQueue\event\AfterProcessJobEvent;
use winwin\jobQueue\event\BeanstalkErrorEvent;
use winwin\jobQueue\event\BeforeProcessJobEvent;
use winwin\jobQueue\event\JobFailedEvent;
use winwin\jobQueue\exception\RetryException;

class JobDispatcher implements LoggerAwareInterface
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
     * JobProcessor constructor.
     * @param ContainerInterface $container
     * @param EventDispatcherInterface $eventDispatcher
     * @param callable $beanstalkFactory
     * @param int $workerNum
     * @param int $sleepInterval
     */
    public function __construct(
        ContainerInterface $container,
        AnnotationReaderInterface $annotationReader,
        JobStatService $jobStatService,
        EventDispatcherInterface $eventDispatcher,
        callable $beanstalkFactory,
        int $workerNum = 1,
        int $sleepInterval = 1
    ) {
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
        $pool->on("WorkerStart", [$this, 'onWorkerStart']);
        $pool->start();
    }

    public function onWorkerStart($pool, $workerId): void
    {
        $this->beanstalk = call_user_func($this->beanstalkFactory, $workerId);
        $this->jobStatService->register($workerId, getmypid());
        while (true) {
            $job = $this->grabJob();
            if (!$job) {
                $this->jobStatService->heartbeat($workerId);
                continue;
            }
            $action = null;
            $args = [];
            try {
                $startTime = microtime(true);
                $this->handle($job);
                $action = 'delete';
                $this->jobStatService->success($workerId, (microtime(true)-$startTime)*1000);
                $this->logger->info(static::TAG . 'job process successfully', ['worker' => $workerId, 'job_id' => $job->getId()]);
            } catch (RetryException $e) {
                $action = 'release';
                $args = [$e->getPriority(), $e->getDelay()];
                $this->logger->warning(static::TAG . 'retry job', ['job_id' => $job->getId(), 'delay' => $e->getDelay()]);
            } catch (\Throwable $e) {
                $this->eventDispatcher->dispatch(new JobFailedEvent($e, $job));
                $this->jobStatService->failure($workerId);
                $action = 'bury';
                $this->logger->error(static::TAG . 'job process failed: ' . $e, [
                    'job_id' => $job->getId(),
                    'data' => $job->getData()
                ]);
            }
            try {
                call_user_func_array([$this->beanstalk, $action], array_merge([$job], $args));
            } catch (BeanstalkException $e) {
                $this->eventDispatcher->dispatch(new BeanstalkErrorEvent($e, $job));
                $this->logger->error(static::TAG . "beanstalk $action failed", ['job_id' => $job->getId()]);
            }
        }
    }

    private function handle(BeanstalkJob $job): void
    {
        /** @var BeforeProcessJobEvent $event */
        $event = $this->eventDispatcher->dispatch(new BeforeProcessJobEvent($job));
        $job = $event->getJob();

        $data = json_decode($job->getData(), true);
        if (!isset($data['job'], $data['payload'])) {
            throw new \UnexpectedValueException('invalid job');
        }

        if (!class_exists($data['job'])) {
            throw new \UnexpectedValueException("job {$data['job']} does not exist");
        }
        $jobClass = new \ReflectionClass($data['job']);
        /** @var JobProcessor $annotation */
        $annotation = $this->annotationReader->getClassAnnotation($jobClass, JobProcessor::class);
        if ($annotation) {
            $jobProcessorClass = $annotation->value;
        } else {
            $jobProcessorClass = $data['job'] . 'Processor';
        }
        $this->logger->info(static::TAG . 'handle job', [
            'job' => $data['job'], 'processor' => $jobProcessorClass, 'job_id' => $job->getId()]);
        if (!$this->container->has($jobProcessorClass)) {
            throw new \UnexpectedValueException("Job processor $jobProcessorClass does not exist");
        }
        $handler = $this->container->get($jobProcessorClass);
        if (!$handler instanceof JobProcessorInterface) {
            throw new \UnexpectedValueException("job {$data['job']} does not implement " . JobProcessorInterface::class);
        }
        /** @noinspection PhpParamsInspection */
        $handler->process($jobClass->newInstance($data['payload']));

        $this->eventDispatcher->dispatch(new AfterProcessJobEvent($job));
    }

    private function grabJob(): ?BeanstalkJob
    {
        try {
            return $this->beanstalk->reserveWithTimeout(1);
        } catch (BeanstalkException $e) {
            $this->eventDispatcher->dispatch(new BeanstalkErrorEvent($e, null));
            $this->logger->error(static::TAG . 'beanstalk reserve failed');
            sleep($this->sleepInterval);
            return null;
        }
    }
}
