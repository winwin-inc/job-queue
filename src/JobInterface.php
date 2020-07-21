<?php


namespace winwin\jobQueue;

interface JobInterface extends \JsonSerializable
{
    public function __construct($data);
}
