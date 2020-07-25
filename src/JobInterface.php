<?php

declare(strict_types=1);

namespace winwin\jobQueue;

interface JobInterface extends \JsonSerializable
{
    public function __construct($data);
}
