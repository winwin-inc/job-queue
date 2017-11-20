<?php

namespace winwin\jobQueue;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class JobProcessor implements JobProcessorInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $pidfile;

    /**
     * @var LockHandler
     */
    private $lock;

    /**
     * @var int
     */
    private $lastHeartbeatTime;

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

    /**
     * @var WorkerInterface[]
     */
    private $workers;

    public function __construct(EventDispatcherInterface $eventDispatcher, $pidfile, $heartbeatInterval = 60)
    {
        if (strpos(strtolower(PHP_OS), 'win') === 0) {
            throw new \RuntimeException("This application not support windows");
        }

        // 检查扩展
        if (!extension_loaded('pcntl')) {
            throw new \RuntimeException("Please install pcntl extension");
        }

        if (!extension_loaded('posix')) {
            throw new \RuntimeException("Please install posix extension");
        }

        $this->eventDispatcher = $eventDispatcher;
        $this->pidfile = $pidfile;
        $this->lock = new LockHandler(md5($pidfile), $heartbeatInterval);
    }

    /**
     * {@inheritdoc}
     */
    public function addWorker(WorkerInterface $worker, $num = 1)
    {
        foreach (range(1, $num) as $i) {
            $this->workers[] = $worker;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        declare(ticks=1);

        if (empty($this->workers)) {
            throw new \RuntimeException("No workers added.");
        }

        $this->checkProcess();
        $this->eventDispatcher->dispatch(Events::PROCESSOR_START, new GenericEvent($this));
        $this->installSignal();
        try {
            while (!$this->stopped) {
                if (!$this->heartbeat()) {
                    throw new \RuntimeException("Heartbeat failed");
                }
                $this->startWorkers();
                $pid = pcntl_wait($status, WNOHANG);
                if ($pid) {
                    $key = array_search($pid, $this->workerPids);
                    if ($key) {
                        unset($this->workerPids[$key]);
                    }
                } else {
                    sleep(1);
                }
            }
        } catch (\Exception $e) {
            $this->stopWorkers();
        }
        $this->lock->release();
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
            throw new \RuntimeException("Job processor is not running");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAlive()
    {
        return $this->lock->isAlive();
    }

    protected function getPid()
    {
        if (is_file($this->pidfile)) {
            return (int) file_get_contents($this->pidfile);
        }
        return -1;
    }

    /**
     * check whether the processor already started
     */
    protected function checkProcess()
    {
        if ($this->lock->lock(false)) {
            $this->lastHeartbeatTime = time();
            $pid = $this->getPid();
            if ($pid > 0 && posix_kill($pid, 0)) {
                throw new \RuntimeException("Job processor is running, pid=$pid");
            }
            $dir = dirname($this->pidfile);
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                throw new \RuntimeException("Cannot create pid file directory '$dir'");
            }
            file_put_contents($this->pidfile, getmypid());
        } else {
            throw new \RuntimeException("Lock exists, is the processor running?");
        }
    }

    protected function heartbeat()
    {
        if (time() - $this->lastHeartbeatTime > $this->lock->getHeartbeatInterval() - 5) {
            return $this->lock->heartbeat();
        }
        return true;
    }

    protected function startWorkers()
    {
        foreach ($this->workers as $i => $worker) {
            if (isset($this->workerPids[$i])) {
                $pid = $this->workerPids[$i];
                if (posix_kill($pid, 0)) {
                    continue;
                }
            }
            $pid = pcntl_fork();
            if ($pid == -1) {
                throw new \RuntimeException('Cannot fork queue worker');
            } elseif ($pid) {
                $this->workerPids[$i] = $pid;
            } else {
                $this->reinstallSignal();
                $this->startWorker($worker);
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
        pcntl_signal(SIGUSR1, [$this, 'workerSignalHandler']);
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
                $this->reloadWorkers();
                $this->eventDispatcher->dispatch(Events::AFTER_PROCESSOR_RELOAD, new GenericEvent($this));
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
        case SIGUSR1:
            $this->eventDispatcher->dispatch(EVENTS::WORKER_RELOAD, new GenericEvent($this));
            $this->workerStopped = true;
            break;
        }
    }

    protected function stopWorkers()
    {
        foreach ($this->workerPids as $i => $workerPid) {
            if (posix_kill($workerPid, SIGINT)) {
                pcntl_waitpid($workerPid, $status);
                unset($this->workerPids[$i]);
            }
        }
    }

    protected function reloadWorkers()
    {
        foreach ($this->workerPids as $i => $workerPid) {
            posix_kill($workerPid, SIGUSR1);
        }
    }

    protected function startWorker(WorkerInterface $worker)
    {
        $this->eventDispatcher->dispatch(Events::WORKER_START, new GenericEvent($worker));
        $worker->start();
        while (!$this->workerStopped && $worker->shouldRun()) {
            $worker->work();
        }
        $worker->stop();
        $this->eventDispatcher->dispatch(Events::WORKER_STOP, new GenericEvent($worker));
    }
}
