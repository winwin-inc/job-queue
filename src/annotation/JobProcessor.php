<?php

declare(strict_types=1);

namespace winwin\jobQueue\annotation;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @IgnoreAnnotation()
 * @Target({"CLASS"})
 */
class JobProcessor
{
    /**
     * @var string
     * @Required()
     */
    public $value;
}
