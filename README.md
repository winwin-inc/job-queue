# Job Queue using Beanstalkd

## Install

Require it from your command line:

```bash
composer require winwin/job-queue
```

## Usage

To put a job to queue, you must create a job class first. The job class must implement `winwin\jobQueue\JobInterface` :

```php
use winwin\jobQueue\JobInterface;

class MyJob implements JobInterface
{
    public function process(array $args)
    {
        error_log("run job with ". json_encode($args));
    }
}
```

The method `process` should receive the arguments which job was put to the queue.

With the job class, we can put the job to queue like:

```php
use winwin\jobQueue\JobQueue;

$queue = new JobQueue();
$queue->put(MyJob::class, ['arg1' => 'val1']);
```

The `JobQueue` use [beastalkd](http://kr.github.io/beanstalkd/) internal.

## Job Processor

To consume the job from the queue, create `JobProcessor` like:

```php
use Symfony\Component\EventDispatcher\EventDispatcher;
use winwin\jobQueue\SimpleJobFactory;
use winwin\jobQueue\JobProcessor;

$processor = new JobProcessor($queue, new SimpleJobFactory(), new EventDispatcher(), 'queue.pid');
$processor->start();
```

The `JobProcessor` create a master process and serval workers processes. The master pid will be writen to `queue.pid` file. To reload workers processes, call `reload` method:

```php
$processor->reload();
```

Or send `USR1` signal to the master process:

```bash
kill -USR1 `cat queue.pid`
```

To stop master process, call `stop` method or send `INT` signal to master process.

The `QueueCommand` use to create console command using [symfony/console](https://symfony.com/doc/current/components/console/index.html). Create a file console with content: 

```php
#!/usr/bin/env php
<?php
use winwin\jobQueue\JobQueue;
use Symfony\Component\Console\Application;
use winwin\jobQueue\QueueCommand;
use Symfony\Component\EventDispatcher\EventDispatcher;
use winwin\jobQueue\SimpleJobFactory;
use winwin\jobQueue\JobProcessor;

$queue = new JobQueue();
$processor = new JobProcessor($queue, new SimpleJobFactory(), new EventDispatcher(), 'queue.pid');

$app = new Application();
$command = new QueueCommand('queue');
$command->setJobProcessor($processor);
$app->add($command);
$app->run();
```

Start queue process:

```bash
php console queue 
```

Reload queue:

```bash
php console queue --reload
```

Stop queue:

```bash
php console queue --stop
```
