<?php


namespace winwin\jobQueue;

use DI\Annotation\Inject;
use function DI\get;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\Configuration;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\swoole\pool\PoolFactoryInterface;
use Pheanstalk\Pheanstalk;

/**
 * @Configuration()
 */
class JobQueueConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            JobFactoryInterface::class => get(JobQueueInterface::class)
        ];
    }

    /**
     * @Bean()
     * @Inject({"config": "application.beanstalk"})
     */
    public function jobQueue(PoolFactoryInterface $poolFactory, array $config): JobQueueInterface
    {
        return new JobQueuePool($poolFactory->create("job-queue", static function () use ($config) {
            $beanstalk = Pheanstalk::create($config['host'], $config['port'] ?? 11300);
            if (isset($config['tube'])) {
                $beanstalk->useTube($config['tube']);
            }
            return new JobQueue($beanstalk);
        }));
    }
}
