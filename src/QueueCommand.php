<?php

namespace winwin\jobQueue;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function configure()
    {
        $this->setDescription('Job queue control')
            ->addOption('reload', null, InputOption::VALUE_NONE, 'reload queue worker')
            ->addOption('stop', null, InputOption::VALUE_NONE, 'stop queue worker')
            ->addOption('no-schedule', null, InputOption::VALUE_NONE, 'won\'t start schedule worker')
            ->addOption('queue-workers', null, InputOption::VALUE_REQUIRED, 'number of job queue worker', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->container) {
            throw new RuntimeException("Job processor was not setup");
        }
        $jobProcessor = $this->container->get(JobProcessorInterface::class);
                      
        if ($input->getOption('reload')) {
            $jobProcessor->reload();
        } elseif ($input->getOption('stop')) {
            $jobProcessor->stop();
        } else {
            if (!$input->getOption('no-schedule') && $this->container->has(ScheduleWorker::class)) {
                if ($output->isVerbose()) {
                    $output->writeln("<info>Start schedule worker</info>");
                }
                $jobProcessor->addWorker($this->container->get(ScheduleWorker::class));
            }
            if ($this->container->has(JobQueueWorker::class)) {
                $workers = $input->getOption('queue-workers');
                if ($workers > 0) {
                    if ($output->isVerbose()) {
                        $output->writeln("<info>Start $workers job queue worker</info>");
                    }
                    $worker = $this->container->get(JobQueueWorker::class);
                    foreach (range(0, $workers-1) as $i) {
                        $jobProcessor->addWorker($worker);
                    }
                }
            }
            $jobProcessor->start();
        }
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }
}
