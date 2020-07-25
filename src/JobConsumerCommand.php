<?php


namespace winwin\jobQueue;

use DI\Annotation\Inject;
use kuiper\di\annotation\Command;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @Command()
 */
class JobConsumerCommand extends ConsoleCommand
{
    /**
     * @Inject()
     * @var JobConsumerPool
     */
    private $jobConsumerPool;

    protected function configure()
    {
        $this->setDescription("Start consumer");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->jobConsumerPool->start();
        return 0;
    }
}
