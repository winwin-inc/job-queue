<?php

namespace winwin\jobQueue;

use Pheanstalk\Pheanstalk;

class JobQueue implements JobFactoryInterface, JobQueueInterface
{
    private $beanstalk;

    /**
     * JobQueue constructor.
     * @param $beanstalk
     */
    public function __construct(Pheanstalk $beanstalk)
    {
        $this->beanstalk = $beanstalk;
    }

    /**
     * 创建任务
     * @param string $jobClass
     * @return JobInterface
     */
    public function create(string $jobClass, array $arguments): JobInterface
    {
        return new Job($this, $jobClass, $arguments);
    }

    public function put(
        string $jobClass,
        array $arguments,
        int $delay = 0,
        int $priority = 1024,
        int $ttr = 60,
        ?string $tube = null
    ): int {
        $put = function () use ($jobClass, $arguments, $delay, $priority, $ttr) {
            return $this->beanstalk->put(json_encode([
                'job' => $jobClass,
                'payload' => $arguments,
            ]), $priority, $delay, $ttr)->getId();
        };
        if ($tube) {
            $this->beanstalk->withUsedTube($tube, $put);
        }
        return $put();
    }
}
