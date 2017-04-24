<?php

namespace winwin\jobQueue;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueCommand extends Command
{
    /**
     * @var JobProcessorInterface
     */
    protected $jobProcessor;

    protected function configure()
    {
        $this->setDescription('Job queue control')
            ->addOption('reload', null, InputOption::VALUE_NONE, 'reload queue worker')
            ->addOption('stop', null, InputOption::VALUE_NONE, 'stop queue worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->jobProcessor) {
            throw new RuntimeException("Job processor was not setup");
        }
        if ($input->getOption('reload')) {
            $this->jobProcessor->reload();
        } elseif ($input->getOption('stop')) {
            $this->jobProcessor->stop();
        } else {
            $this->jobProcessor->start();
        }
    }

    public function setJobProcessor(JobProcessorInterface $jobProcessor)
    {
        $this->jobProcessor = $jobProcessor;
        return $this;
    }
}
