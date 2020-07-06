<?php

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 1.0-SNAPSHOT
 */

namespace winwin\jobQueue\servant;

use wenbinye\tars\protocol\annotation\TarsProperty;

final class JobStat
{
    /**
     * @TarsProperty(order = 0, required = true, type = "int")
     * @var int
     */
    public $activeWorkers;

    /**
     * @TarsProperty(order = 1, required = true, type = "int")
     * @var int
     */
    public $pid;

    /**
     * @TarsProperty(order = 2, required = true, type = "int")
     * @var int
     */
    public $successCount;

    /**
     * @TarsProperty(order = 3, required = true, type = "int")
     * @var int
     */
    public $failureCount;

    /**
     * @TarsProperty(order = 4, required = true, type = "int")
     * @var int
     */
    public $successCount1m;

    /**
     * @TarsProperty(order = 5, required = true, type = "int")
     * @var int
     */
    public $failureCount1m;

    /**
     * @TarsProperty(order = 6, required = true, type = "int")
     * @var int
     */
    public $successCount15m;

    /**
     * @TarsProperty(order = 7, required = true, type = "int")
     * @var int
     */
    public $failureCount15m;

    /**
     * @TarsProperty(order = 8, required = true, type = "int")
     * @var int
     */
    public $averageTime;
}
