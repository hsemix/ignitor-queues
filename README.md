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