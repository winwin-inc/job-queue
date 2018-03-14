<?php

namespace winwin\jobQueue;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QueueCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure()
    {
        $this->setDescription('Job queue control')
            ->addOption('reload', null, InputOption::VALUE_NONE, 'reload queue worker')
            ->addOption('stop', null, InputOption::VALUE_NONE, 'stop queue worker')
            ->addOption("tube", null, InputOption::VALUE_REQUIRED, "beanstalk tube")
            ->addOption('no-schedule', null, InputOption::VALUE_NONE, 'won\'t start schedule worker')
            ->addOption('queue-workers', null, InputOption::VALUE_REQUIRED, 'number of job queue worker', 1);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->container) {
            throw new RuntimeException("Job processor was not setup");
        }
        $this->input = $input;
        $this->output = $output;

        if ($input->getOption('reload')) {
            $this->reload();
        } elseif ($input->getOption('stop')) {
            $this->stop();
        } else {
            $this->start();
        }
    }

    private function addJobQueueWorkers($jobProcessor, $workers)
    {
        $tubes = $this->getTubes();
        $jobQueue = $this->container->get(JobQueueInterface::class);
        $jobFactory = $this->container->get(JobFactoryInterface::class);
        $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        if ($jobQueue instanceof JobQueueCluster) {
            foreach ($jobQueue->getJobQueueList() as $queue) {
                if ($tubes) {
                    $this->setWatchTubes($queue, $tubes);
                }
                $worker = new JobQueueWorker($queue, $jobFactory, $eventDispatcher);
                foreach (range(1, $workers) as $i) {
                    $jobProcessor->addWorker($worker);
                }
            }
        } else {
            if ($tubes) {
                $this->setWatchTubes($jobQueue, $tubes);
            }
            $worker = new JobQueueWorker($jobQueue, $jobFactory, $eventDispatcher);
            foreach (range(1, $workers) as $i) {
                $jobProcessor->addWorker($worker);
            }
        }
    }

    private function setWatchTubes($jobQueue, $tubes)
    {
        if (!empty($tubes)) {
            $jobQueue->setTube($tubes[0])
                ->setWatchTubes($tubes);
        }
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    protected function getTubes()
    {
        $tube = $this->input->getOption("tube");
        $tubes = [];
        if ($tube) {
            $tubes = array_unique(array_filter(explode(",", $tube)));
            sort($tubes);
        }
        return $tubes;
    }

    /**
     * @return JobProcessorInterface
     */
    protected function getJobProcessor()
    {
        /** @var JobProcessor $jobProcessor */
        $jobProcessor = $this->container->get(JobProcessorInterface::class);
        $tubes = $this->getTubes();
        if ($tubes) {
            $jobProcessor->setPidfile(sprintf("%s/queue-%s.pid",
                dirname($jobProcessor->getPidfile()), md5(implode(",", $tubes))));
        }
        return $jobProcessor;
    }

    protected function reload()
    {
        $this->getJobProcessor()->reload();
    }

    protected function stop()
    {
        $this->getJobProcessor()->stop();
    }

    protected function start()
    {
        $jobProcessor = $this->getJobProcessor();
        if (!$this->input->getOption('no-schedule')
            && $this->container->has(ScheduleWorker::class)) {
            if ($this->output->isVerbose()) {
                $this->output->writeln("<info>Start schedule worker</info>");
            }
            $jobProcessor->addWorker($this->container->get(ScheduleWorker::class));
        }

        if ($this->container->has(JobQueueWorker::class)) {
            $workers = $this->input->getOption('queue-workers');
            if ($workers > 0) {
                if ($this->output->isVerbose()) {
                    $this->output->writeln("<info>Start $workers job queue worker</info>");
                }
                $this->addJobQueueWorkers($jobProcessor, $workers);
            }
        }
        $jobProcessor->start();
    }
}
