<?php


namespace winwin\jobQueue;

interface JobHandlerInterface
{
    public function handle(array $arguments): void;
}
