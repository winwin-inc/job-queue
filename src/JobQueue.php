<?php

namespace winwin\jobQueue;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Pheanstalk;
use winwin\jobQueue\integration\Job;
use winwin\jobQueue\integration\JobQueueServant;

class JobQueue implements JobFactoryInterface, JobQueueInterface
{
    /**
     * @var array
     */
    private $options;
    /**
     * @var JobQueueServant|null
     */
    private $jobQueueServant;

    /**
     * @var Pheanstalk
     */
    private $beanstalk;

    public function __construct(array $options)
    {
        $this->options = $options + [
                'host' => 'localhost',
                'port' => 11300,
                'tube' => 'default'
            ];
    }

    /**
     * @param JobQueueServant|null $jobQueueServant
     */
    public function setJobQueueServant(?JobQueueServant $jobQueueServant): void
    {
        $this->jobQueueServant = $jobQueueServant;
    }

    /**
     * @inheritDoc
     */
    public function create(JobInterface $job): JobOption
    {
        return new JobOption($this, $job);
    }

    /**
     * @inheritDoc
     */
    public function put(
        string $jobClass,
        $arguments,
        int $delay = 0,
        int $priority = 1024,
        int $ttr = 60,
        ?string $tube = null
    ): JobId {
        $put = function () use ($jobClass, $arguments, $delay, $priority, $ttr, $tube) {
            try {
                $id = $this->getBeanstalk()->put(json_encode([
                    'job' => $jobClass,
                    'payload' => $arguments,
                ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), $priority, $delay, $ttr)->getId();
                return new JobId(JobType::NORMAL, $id);
            } catch (ServerException $e) {
                if (!$this->jobQueueServant) {
                    throw $e;
                }
                $jobDto = new Job();
                $jobDto->jobClass = $jobClass;
                $jobDto->payload = json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $jobDto->priority = $priority;
                $jobDto->ttr = $ttr;
                $jobDto->delay = $delay;
                $jobDto->tube = $tube ?? $this->options['tube'];
                $jobDto->serverHost = $this->options['host'];
                $jobDto->serverPort = (int)$this->options['port'];
                $id = $this->jobQueueServant->put($jobDto);
                return new JobId(JobType::PENDING, $id);
            }
        };
        return $tube ? $this->getBeanstalk()->withUsedTube($tube, $put) : $put();
    }

    private function getBeanstalk(): Pheanstalk
    {
        if (!$this->beanstalk) {
            $beanstalk = Pheanstalk::create($this->options['host'], $this->options['port']);
            $beanstalk->useTube($this->options['tube']);
            $this->beanstalk = $beanstalk;
        }
        return $this->beanstalk;
    }
}
