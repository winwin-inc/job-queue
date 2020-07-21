# Job Queue 

## Install

Require it from your command line:

```bash
composer require winwin/job-queue
```

## Usage

任务对象是一个普通的 PHP

```php
<?php
use winwin\jobQueue\JobProcessorInterface;

class MyJob implements JobProcessorInterface
{
public function handle(array $arguments) : void {
 // TODO: Implement handle() method.
}
}

/** @var \winwin\jobQueue\JobFactoryInterface $jobFactory */
$jobFactory->create(MyJob::class, [])->put();
```

## Job Processor

