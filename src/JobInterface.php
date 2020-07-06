<?php

namespace winwin\jobQueue;

interface JobInterface
{
    public function priority(int $priority): JobInterface;

    public function tube(string $tube): JobInterface;

    public function timeToRun(int $ttr): JobInterface;

    public function delay(int $delaySeconds): JobInterface;

    public function put(): int;
}
