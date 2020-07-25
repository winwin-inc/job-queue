<?php

declare(strict_types=1);

namespace winwin\jobQueue;

use kuiper\helper\Enum;

class JobType extends Enum
{
    public const PENDING = 'pending';
    public const NORMAL = 'normal';
}
