<?php

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 1.0-SNAPSHOT
 */

namespace winwin\jobQueue\integration;

use wenbinye\tars\protocol\annotation\TarsProperty;

final class Job
{
    /**
     * @TarsProperty(order = 0, required = true, type = "string")
     * @var string
     */
    public $serverHost;

    /**
     * @TarsProperty(order = 1, required = true, type = "int")
     * @var int
     */
    public $serverPort;

    /**
     * @TarsProperty(order = 1, required = true, type = "string")
     * @var string
     */
    public $jobClass;

    /**
     * @TarsProperty(order = 2, required = true, type = "string")
     * @var string
     */
    public $payload;

    /**
     * @TarsProperty(order = 3, required = true, type = "int")
     * @var int
     */
    public $delay;

    /**
     * @TarsProperty(order = 4, required = true, type = "int")
     * @var int
     */
    public $priority;

    /**
     * @TarsProperty(order = 5, required = true, type = "int")
     * @var int
     */
    public $ttr;

    /**
     * @TarsProperty(order = 6, required = true, type = "int")
     * @var int
     */
    public $tube;

    /**
     * @TarsProperty(order = 7, required = false, type = "int")
     * @var int
     */
    public $jobId;
}
