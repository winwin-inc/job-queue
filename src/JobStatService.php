<?php

declare(strict_types=1);

namespace winwin\jobQueue;

use kuiper\helper\Arrays;
use Swoole\Table;
use winwin\jobQueue\servant\JobStat;
use winwin\jobQueue\servant\JobStatServant;

class JobStatService implements JobStatServant
{
    /**
     * @var Table
     */
    private $table;
    /**
     * @var int
     */
    private $healthyInterval;

    /**
     * JobStat constructor.
     */
    public function __construct(int $size = 256, int $healthyInterval = 60)
    {
        $table = new Table($size);
        $table->column('pid', Table::TYPE_INT);
        $table->column('worker_id', Table::TYPE_INT);
        $table->column('heartbeat', Table::TYPE_INT);
        $table->column('success', Table::TYPE_INT);
        $table->column('failure', Table::TYPE_INT);
        $table->column('window1', Table::TYPE_INT);
        $table->column('window15', Table::TYPE_INT);
        $table->column('success1', Table::TYPE_INT);
        $table->column('failure1', Table::TYPE_INT);
        $table->column('success15', Table::TYPE_INT);
        $table->column('failure15', Table::TYPE_INT);
        $table->column('ttr', Table::TYPE_INT, 8);
        $table->create();
        $this->table = $table;
        $this->healthyInterval = $healthyInterval;
    }

    public function register(int $workerId, int $pid): void
    {
        $this->table->set((string) $workerId, [
            'worker_id' => $workerId,
            'pid' => $pid,
            'heartbeat' => time(),
        ]);
    }

    public function heartbeat(int $workerId): void
    {
        $this->table->set((string) $workerId, ['heartbeat' => time()]);
    }

    public function success(int $workerId, int $ttr): void
    {
        $key = (string) $workerId;
        $this->heartbeat($workerId);
        $this->table->incr($key, 'success');
        $this->table->incr($key, 'ttr', $ttr);
        $this->addWindow($key, 1, 'success');
        $this->addWindow($key, 15, 'success');
    }

    public function failure(int $workerId): void
    {
        $key = (string) $workerId;
        $this->heartbeat($workerId);
        $this->table->incr($key, 'failure');
        $this->addWindow($key, 1, 'failure');
        $this->addWindow($key, 15, 'failure');
    }

    private function addWindow(string $key, int $windowSize, string $field): void
    {
        $window = (int) (time() / (60 * $windowSize));
        if ($window == $this->table->get($key, 'window'.$windowSize)) {
            $this->table->incr($key, $field.$windowSize);
        } else {
            $this->table->set($key, [
                $field.$windowSize => 1,
                'window'.$windowSize => $window,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stat()
    {
        $jobStat = new JobStat();
        $jobStat->pid = 0;
        $workerStat = [];
        foreach ($this->table as $row) {
            $workerStat[] = $this->toJobStat($row);
        }

        foreach (['activeWorkers', 'successCount', 'failureCount', 'successCount1m', 'failureCount1m',
                     'successCount15m', 'failureCount15m', ] as $field) {
            $jobStat->$field = array_sum(Arrays::pullField($workerStat, $field));
        }
        $timeArray = array_filter(Arrays::pullField($workerStat, 'averageTime'), function ($time) {
            return $time > 0;
        });
        $jobStat->averageTime = empty($timeArray) ? -1 : (int) (array_sum($timeArray) / count($timeArray));

        return $jobStat;
    }

    /**
     * {@inheritdoc}
     */
    public function statWorker($workerId)
    {
        $row = $this->table->get((string) $workerId);
        if (!$row) {
            return null;
        }

        return $this->toJobStat($row);
    }

    private function toJobStat(array $row): JobStat
    {
        $jobStat = new JobStat();
        $jobStat->activeWorkers = (time() - $row['heartbeat'] < $this->healthyInterval ? 1 : 0);
        $jobStat->pid = $row['pid'];
        $jobStat->successCount = $row['success'];
        $jobStat->failureCount = $row['failure'];
        $jobStat->successCount1m = $row['success1'];
        $jobStat->failureCount1m = $row['failure1'];
        $jobStat->successCount15m = $row['success15'];
        $jobStat->failureCount15m = $row['success15'];
        $jobStat->averageTime = ($jobStat->successCount > 0 ? $row['ttr'] / $row['success'] : -1);

        return $jobStat;
    }
}
