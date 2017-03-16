<?php

namespace winwin\jobQueue;

use Psr\Container\ContainerInterface;

class JobFactory implements JobFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * {@inheritdoc}
     */
    public function create($jobClass)
    {
        $job = $this->container->get($jobClass);
        if (method_exists($job, 'setContainer')) {
            $job->setContainer($this->container);
        }
        return $job;
    }
}
