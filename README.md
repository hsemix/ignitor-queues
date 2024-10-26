# Laravel Like Queues for CodeIgniter 4

This is a Laravel like Queues for CodeIgniter 4.

## Installation

You can install the package via composer:

```bash
composer require ignitor/queues:^1.0@dev

php spark queue:table

php spark migrate
```

## Usage

```php
// In your method
public function testQueue()
{
    toQueue(function () {
        sleep(10);
        // log to file or send an email
    });

    return "Request is being Processed";
}
```

```bash
php spark queue:work
```

## Dedicated Job Class

If you want to use a dedicated Job class, you can use the `make:job` command.

```bash
php spark make:job TestJob
```

### Job Class

```php
<?php

namespace App\Jobs;

use Igniter\Queues\Queue\DispatchableTrait;
// use Igniter\Queues\Queue\IsEncryptedInterface;
use Igniter\Queues\Queue\ShouldQueueInterface;

class TestJob implements ShouldQueueInterface
{
    use DispatchableTrait;

    /**
     * The queue to run the job on.
     *
     * @var string
     */
    public string $queue = 'default';

    /**
     * Delay the job by a given amount of seconds.
     *
     * @param int $delay
     *
     */
    public int $delay = 0;

    /**
     * Delay the job by a given amount of seconds.
     *
     * @param string $delayType
     *
     */
    public string $delayType = 'minutes';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
        $this->data = $data;
    }

    /**
     * Run the job.
     *
     * @return void
     */
    public function run()
    {
        // log to file or send an email, etc.
    }
}
```

Then you can use it like this:

```php
public function testQueue()
{
    TestJob::dispatch();

    return "Request is being Processed";
}
```

## Sending Data to the Queue

You can send data to the queue by using the `toQueue` method.

```php
public function testQueue()
{
    toQueue(function ($data) {
        sleep(10);
        // log to file or send an email
    }, ['data' => 'some data']);

    return "Request is being Processed";
}
```

For a dedicated job, you can use pass as arguments to the dispatch method.

```php
TestJob::dispatch('some data');

TestJob::dispatch('some data', 'more data', 'even more data');
```
This data will be available in the job as constructor arguments in your job class.

## Delaying the Job

You can delay the job by using the `delay` method.

```php
public function testQueue()
{
    toQueue(function () {
        sleep(10);
        // log to file or send an email
    }, delay: 10);

    return "Request is being Processed";
}
```

## Encrypting Sensitive Data

You can encrypt sensitive data by implementing the `IsEncryptedInterface` interface.

```php
<?php

namespace App\Jobs;

use Igniter\Queues\Queue\DispatchableTrait;
use Igniter\Queues\Queue\IsEncryptedInterface;
use Igniter\Queues\Queue\ShouldQueueInterface;

class TestJob implements ShouldQueueInterface, IsEncryptedInterface
{
    use DispatchableTrait;

    /**
     * The queue to run the job on.
     *
     * @var string
     */
    public string $queue = 'default';

    /**
     * Delay the job by a given amount of seconds.
     *
     * @param int $delay
     *
     */
    public int $delay = 0;

    /**
     * Delay the job by a given amount of seconds.
     *
     * @param string $delayType
     *
     */
    public string $delayType = 'minutes';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
        $this->data = $data;
    }

    /**
     * Run the job.
     *
     * @return void
     */
    public function run()
    {
        // log to file or send an email, etc.
    }
}
```

## Running Multiple Workers

You can run multiple workers by using the `queue:work` command.

```bash
php spark queue:work --workers 2
```
### You can also just run the workers in single-worker mode. and run the command multiple times.

```bash
php spark queue:work

php spark queue:work

php spark queue:work

php spark queue:work

php spark queue:work
```

### You can use the `--retry` option to retry failed jobs.
```bash
php spark queue:work --retry 5
```

## Restarting Workers

You can restart automatically workers when they stop by using the `queue:work` command with the `--restart` option.

```bash
php spark queue:work --restart
```

## Queue Table

You can create the queue table by using the `queue:table` command.

```bash
php spark queue:table
```

## Specifying the Queue name

You can specify the queue name by using the `queue` option.

```php
public function testQueue()
{
    toQueue(function () {
        sleep(10);
        // log to file or send an email
    }, queue: 'emails');

    return "Request is being Processed";
}
```

For Dedicated Jobs, you can use the `queue` property on the job class.

To specify the queue name when working the queue, you can use the `--queue` option.

```bash
php spark queue:work --queue emails
```

## Handling Failed Jobs
You can handle a failed job at a per-job basis by implementing the `CanFailInterface` interface.

```php
<?php

namespace App\Jobs;

use Igniter\Queues\Queue\DispatchableTrait;
use Igniter\Queues\Queue\ShouldQueueInterface;
use Igniter\Queues\Queue\CanFailInterface;

class TestJob implements ShouldQueueInterface, CanFailInterface
{
    use DispatchableTrait;

    /**
     * The queue to run the job on.
     *
     * @var string
     */
    public string $queue = 'default';

    /**
     * Delay the job by a given amount of seconds.
     *
     * @param int $delay
     *
     */
    public int $delay = 0;

    /**
     * Delay the job by a given amount of seconds.
     *
     * @param string $delayType
     *
     */
    public string $delayType = 'minutes';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
        $this->data = $data;
    }

    /**
     * Run the job.
     *
     * @return void
     */
    public function run()
    {
        // log to file or send an email, etc.
    }

    /**
     * Triggered when this job fails for whatever reason and only if this job is queueable
     * 
     * @param stdClass $job
     * @param string $message
     */
    public function onFailure($job, $message)
    {
        if ($job->attempts >= 3) { // It totally failed
            $email = service('email');
            $email->setTo('info@example.com');
            $email->setSubject('Some Subject');
            $email->setMessage('Some Message: job-id(' . $job->id . ') <br /> Data:' . json_encode($this->data) . ' with messege: ' . $message);
            $email->send();
        }
    }
}
```

For non-dedicated jobs, you can pass a closure to the toQueue method.

```php
public function testQueue()
{
    toQueue(
        callback: function ($data) {
            sleep(10);
            // log to file or send an email
        },
        data: ['data' => 'some data'],
        onFailure: function ($job, $message) {
            if ($job->attempts >= 3) { // It totally failed
                $email = service('email');
                $email->setTo('info@example.com');
                $email->setSubject('Some Subject');
                $email->setMessage('Some Message: job-id(' . $job->id . ') <br /> Data:' . json_encode($data) . ' with message: ' . $message);
                $email->send();
            }
        }
    );

    return "Request is being Processed";
}
```

## Running a Queue as a Cron Job

You can run a queue as a cron job by using the `queue:work` command.

In your crontab file, add the following line:

```bash
*/5 * * * * cd /var/www/html/your-project && php spark queue:work
```

This will run the queue every 5 minutes.

You can also specify the queue name by using the `--queue` option.

```bash
*/5 * * * * cd /var/www/html/your-project && php spark queue:work --queue emails
```
You can also specify the number of workers to run by using the `--workers` option.

```bash
*/5 * * * * cd /var/www/html/your-project && php spark queue:work --workers 4
```



## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.