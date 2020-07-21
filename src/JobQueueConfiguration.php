<?php


namespace winwin\jobQueue;

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\Configuration;
use kuiper\swoole\pool\PoolFactoryInterface;
use wenbinye\tars\rpc\TarsClientFactoryInterface;
use winwin\jobQueue\integration\JobQueueServant;

/**
 * @Configuration()
 */
class JobQueueConfiguration
{
    /**
     * @Bean()
     * @Inject({"config": "application.beanstalk", "jobQueueServerName": "application.tars.servers.job-queue-server"})
     */
    public function jobQueue(
        TarsClientFactoryInterface $tarsClientFactory,
        PoolFactoryInterface $poolFactory,
        ?string $jobQueueServerName,
        array $config
    ): JobQueueInterface
    {
        return new JobQueuePool($poolFactory->create("job-queue", static function () use ($tarsClientFactory, $config, $jobQueueServerName) {
            $jobQueue = new JobQueue($config);
            if ($jobQueueServerName) {
                /** @noinspection PhpParamsInspection */
                $jobQueue->setJobQueueServant($tarsClientFactory->create(
                    JobQueueServant::class,
                    $jobQueueServerName . ".JobQueueObj"
                ));
            }
            return $jobQueue;
        }));
    }
}
