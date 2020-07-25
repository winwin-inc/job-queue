<?php

declare(strict_types=1);

namespace winwin\jobQueue;

use DI\Annotation\Inject;
use function DI\get;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnProperty;
use kuiper\di\annotation\Configuration;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\ServerConfig;
use Pheanstalk\Pheanstalk;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use wenbinye\tars\server\Config;
use winwin\jobQueue\listener\StartJobConsumer;
use winwin\jobQueue\listener\StopJobConsumer;
use winwin\jobQueue\servant\JobStatServant;

/**
 * @ConditionalOnProperty(value="application.job-processor.enabled", hasValue=true)
 * @Configuration()
 */
class JobProcessorConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $properties = Config::getInstance();
        if ($properties->getBool('application.job-processor.enabled')) {
            $properties->merge([
                'application' => [
                    'tars' => [
                        'servants' => [
                            'JobStatObj' => JobStatServant::class,
                        ],
                    ],
                ],
            ]);
        }

        return [
            JobStatServant::class => get(JobStatService::class),
        ];
    }

    /**
     * @Bean()
     * @Inject({"config": "application.job-processor"})
     */
    public function jobStatService(array $config): JobStatService
    {
        $workers = $this->getWorkers($config);

        return new JobStatService(array_sum($workers), $config['heartbeat-interval'] ?? 60);
    }

    /**
     * @Bean()
     * @Inject({"beanstalkConfig": "application.beanstalk", "config": "application.job-processor"})
     */
    public function jobConsumerPool(
        ServerConfig $serverConfig,
        ContainerInterface $container,
        JobStatService $jobStatService,
        AnnotationReaderInterface $annotationReader,
        EventDispatcherInterface $eventDispatcher,
        LoggerFactoryInterface $loggerFactory,
        array $beanstalkConfig,
        array $config
    ): JobConsumerPool {
        $tubeList = [];
        foreach ($this->getWorkers($config, $beanstalkConfig['tube'] ?? 'default') as $tube => $num) {
            $tubeList[] = array_fill(0, $num, $tube);
        }
        $tubeList = array_merge(...$tubeList);
        $beanstalkFactory = static function ($workerId) use ($tubeList, $beanstalkConfig) {
            $beanstalk = Pheanstalk::create($beanstalkConfig['host'], $beanstalkConfig['port'] ?? 11300);
            $beanstalk->watchOnly($tubeList[$workerId]);

            return $beanstalk;
        };
        $jobProcessor = new JobConsumerPool(
            $serverConfig->getServerName(),
            $container,
            $jobStatService,
            $annotationReader,
            $eventDispatcher,
            $beanstalkFactory,
            count($tubeList)
        );
        $jobProcessor->setLogger($loggerFactory->create(JobConsumerPool::class));

        return $jobProcessor;
    }

    /**
     * @Bean()
     */
    public function startJobConsumer(JobConsumerPool $jobProcessor, LoggerFactoryInterface $loggerFactory): StartJobConsumer
    {
        $listener = new StartJobConsumer($jobProcessor);
        $listener->setLogger($loggerFactory->create(StartJobConsumer::class));

        return $listener;
    }

    /**
     * @Bean()
     */
    public function stopJobConsumer(StartJobConsumer $startJobConsumer, LoggerFactoryInterface $loggerFactory): StopJobConsumer
    {
        $listener = new StopJobConsumer($startJobConsumer);
        $listener->setLogger($loggerFactory->create(StopJobConsumer::class));

        return $listener;
    }

    private function getWorkers(array $config, string $defaultTube = 'default'): array
    {
        if (!isset($config['workers'])) {
            $workers[$defaultTube] = 1;
        } elseif (is_array($config['workers'])) {
            $workers = $config['workers'];
        } else {
            $workers[$defaultTube] = (int) $config['workers'];
        }

        return $workers;
    }
}
