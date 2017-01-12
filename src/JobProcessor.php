<?php

namespace winwin\jobQueue;

use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class JobProcessor implements JobProcessorInterface
{
    /**
     * @var JobQueueInterface
     */
    private $jobQueue;
    
    /**
     * @var string
     */
    private $pidfile;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var JobFactoryInterface
     */
    private $jobFactory;

    /**
     * @var int
     */
    private $maxRequests;

    /**
     * @var int
     */
    private $workers;

    /**
     * @var bool
     */
    private $stopped = false;

    /**
     * @var bool
     */
    private $workerStopped = false;

    /**
     * @var array
     */
    private $workerPids = [];

    public function __construct(JobQueueInterface $jobQueue, JobFactoryInterface $jobFactory, EventDispatcherInterface $eventDispatcher, $pidfile, $workers = 1, $maxRequests = 100)
    {
        if (strpos(strtolower(PHP_OS), 'win') === 0) {
            throw new RuntimeException("This application not support windows");
        }

        // 检查扩展
        if (!extension_loaded('pcntl')) {
            throw new RuntimeException("Please install pcntl extension");
        }

        if (!extension_loaded('posix')) {
            throw new RuntimeException("Please install posix extension");
        }

        $this->jobQueue = $jobQueue;
        $this->eventDispatcher = $eventDispatcher;
        $this->jobFactory = $jobFactory;
        $this->pidfile = $pidfile;
        $this->workers = $workers;
        $this->maxRequests = $maxRequests;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        declare(ticks=1);

        $this->checkProcess();
        $this->eventDispatcher->dispatch(Events::PROCESSOR_START, new GenericEvent($this));
        $this->installSignal();
        try {
            while (!$this->stopped) {
                $this->startWorkers();
                $pid = pcntl_wait($status, WNOHANG);
                if ($pid) {
                    unset($this->workerPids[$pid]);
                } else {
                    sleep(1);
                }
            }
        } catch (\Exception $e) {
            $this->stopWorkers();
        }
        unlink($this->pidfile);
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $pid = $this->getPid();
        if ($pid) {
            return posix_kill($pid, SIGINT);
        } else {
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function reload()
    {
        $pid = $this->getPid();
        if ($pid) {
            return posix_kill($pid, SIGUSR1);
        } else {
            throw new RuntimeException("Job processor is not running");
        }
    }

    protected function getPid()
    {
        if (is_file($this->pidfile)) {
            return (int) file_get_contents($this->pidfile);
        }
    }

    protected function checkProcess()
    {
        $pid = $this->getPid();
        if ($pid > 0 && posix_kill($pid, 0)) {
            throw new RuntimeException("Job processor is running, pid=$pid");
        }
        $dir = dirname($this->pidfile);
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            throw new RuntimeException("Cannot create pid file directory '$dir'");
        }
        file_put_contents($this->pidfile, getmypid());
    }

    protected function startWorkers()
    {
        while (count($this->workerPids) < $this->workers) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                throw new RuntimeException('Cannot fork queue worker');
            } elseif ($pid) {
                $this->workerPids[$pid] = true;
            } else {
                $this->reinstallSignal();
                $this->startWorker();
                exit;
            }
        }
    }

    protected function installSignal()
    {
        // stop
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        // reload
        pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN);
    }

    protected function reinstallSignal()
    {
        // uninstall stop signal handler
        pcntl_signal(SIGINT, SIG_IGN);
        // uninstall reload signal handler
        pcntl_signal(SIGUSR1, SIG_IGN);

        pcntl_signal(SIGINT, [$this, 'workerSignalHandler']);
    }

    public function signalHandler($signal)
    {
        switch ($signal) {
            // Stop.
            case SIGINT:
                $this->eventDispatcher->dispatch(Events::BEFORE_PROCESSOR_STOP, new GenericEvent($this));
                $this->stopped = true;
                $this->stopWorkers();
                $this->eventDispatcher->dispatch(Events::AFTER_PROCESSOR_STOP, new GenericEvent($this));
                break;
            // Reload.
            case SIGUSR1:
                $this->eventDispatcher->dispatch(Events::BEFORE_PROCESSOR_RELOAD, new GenericEvent($this));
                $this->stopWorkers();
                $this->eventDispatcher->dispatch(Events::BEFORE_PROCESSOR_RELOAD, new GenericEvent($this));
                break;
        }
    }

    public function workerSignalHandler($signal)
    {
        switch ($signal) {
            // Stop.
            case SIGINT:
                $this->workerStopped = true;
                break;
        }
    }

    protected function stopWorkers()
    {
        foreach ($this->workerPids as $workerPid => $ignore) {
            if (posix_kill($workerPid, SIGINT)) {
                pcntl_waitpid($workerPid, $status);
                unset($this->workerPids[$workerPid]);
            }
        }
    }

    protected function startWorker()
    {
        $this->eventDispatcher->dispatch(Events::WORKER_START, new GenericEvent($this));
        $processedJobs = 0;
        while (!$this->workerStopped && $processedJobs < $this->maxRequests) {
            $job = $this->jobQueue->reserve(1);
            if (!$job) {
                continue;
            }
            $event = new GenericEvent($this);
            $event['job'] = $job;
            try {
                $data = json_decode($job->getData(), true);
                if (!isset($data['job'], $data['payload'])) {
                    throw new \UnexpectedValueException('invalid job');
                }
                if (!class_exists($data['job'])) {
                    throw new \UnexpectedValueException("job {$data['job']} does not exist");
                }
                $consumer = $this->jobFactory->create($data['job']);
                if (!$consumer instanceof JobInterface) {
                    throw new \UnexpectedValueException("job {$data['job']} does not implement ".JobInterface::class);
                }
                $event['consumer'] = $consumer;
                $event['payload'] = $data['payload'];
                $this->eventDispatcher->dispatch(Events::BEFORE_PROCESS_JOB, $event);
                $consumer->process($data['payload']);
                $this->jobQueue->delete($job);
                $this->eventDispatcher->dispatch(Events::AFTER_PROCESS_JOB, $event);
                ++$processedJobs;
            } catch (\Pheanstalk\Exception $e) {
                $event['error'] = $e;
                $this->eventDispatcher->dispatch(Events::JOB_FAILED, $event);
                sleep(100);
            } catch (\Exception $e) {
                $event['error'] = $e;
                $this->eventDispatcher->dispatch(Events::JOB_FAILED, $event);
                $this->jobQueue->bury($job);
            } catch (\Error $e) {
                $event['error'] = $e;
                $this->eventDispatcher->dispatch(Events::JOB_FAILED, $event);
                $this->jobQueue->bury($job);
            }
        }
        $this->eventDispatcher->dispatch(Events::WORKER_STOP, new GenericEvent($this));
    }

    public function getMaxRequests()
    {
        return $this->maxRequests;
    }

    public function setMaxRequests($maxRequests)
    {
        $this->maxRequests = $maxRequests;
        return $this;
    }

    public function getWorkers()
    {
        return $this->workers;
    }

    public function setWorkers($workers)
    {
        $this->workers = (int) $workers;
        if ($this->workers < 1) {
            $this->workers = 1;
        }
        return $this;
    }
}
