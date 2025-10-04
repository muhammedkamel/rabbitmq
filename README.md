# almatar/rabbitmq - ⚠️ DEPRECATED

> **⚠️ This package is deprecated and no longer maintained.**  
> We recommend using [vladimir-yuldashev/laravel-queue-rabbitmq](https://github.com/vyuldashev/laravel-queue-rabbitmq) as a modern, actively maintained alternative.

A [rabbitmq](https://rabbitmq.com) adapter for [Laravel](laravel.com)/[Lumen](lumen.laravel.com) PHP framework

## Requirements
- PHP >= 7.2
- php-amqplib/php-amqplib >= 2.7
- Laravel >= 6.0

## Install

Run the following command to install the package:

```sh
composer require almatar/rabbitmq
```

## Configure

### Create the config file
create `config/rabbitmq.php` where you can define rabbitmq connections, producers, and consumers.

Example of `config/rabbitmq.php`

```php
<?php

return [
    'connections' => [
        'default' => [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'password',
            'vhost' => '/',
            'connection_attempts' => 10,
            'reconnect_waiting_seconds' => 3,
            'read_write_timeout' => 30, // heartbeat * 2 at least
            'heartbeat' => 15,
        ],
    ],
    'producers' => [
        'test_producer' => [
            'exchange_options' => [
                'name' => 'test_exchange',
                'type' => 'fanout'
            ],
            'queue_options' => [
                'name' => 'test_queue',
                'routing_key' => '',
            ]
        ]
    ],
    'consumers' => [
        'test_consumer' => [
            'qos_prefetch_count' => 5,
            'exchange_options' => [
                'name' => 'test_exchange',
                'type' => 'fanout'
            ],
            'queue_options' => [
                'name' => 'test_queue',
                'routing_key' => '',
            ]
        ]
    ],
];

```

in case of `Lumen` load the configuration file manually `bootstrap/app.php`.

```php
$app->configure('rabbitmq');
```

### Register the Service Provider for Lumen
Add the following line to `bootstrap/app.php`:

```php
$app->register(Almatar\RabbitMQ\RabbitMQServiceProvider::class);
```

## Use

### Example of `consumer`

```php
<?php

namespace App\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;
use Almatar\RabbitMQ\Adapters\Consumer;

class TestConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:consumer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test rabbitmq consumer';

    /**
     *
     * @var Consumer
     */
    private $consumer;

    /**
     * TestCommand constructor.
     * @param Consumer $consumer
     * @param TestService $service
     */
    public function __construct(Consumer $consumer)
    {
        $this->consumer = $consumer;
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->info('[x] Test rabbitmq command consumer is up');
        
        $this->consumer->subscribe(
            config('rabbitmq.consumers.test_consumer'),
            [$this, 'consume']
        );
    }

    public function consume(AMQPMessage $message)
    {
        try {
            $this->info('Message Consumed'); $this->info($message->getBody());
            $message->ack();
        } catch (Throwable $t) {
            die($t->getMessage());
        }
    }
}

```

### Example of `producer`

```php

<?php

namespace App\Services;

use Almatar\RabbitMQ\Adapters\Producer;

class TestService
{
    /**
     * @var Producer
     */
    private $producer;

    /**
     * TestService constructor.
     * @param Producer $producer
     */
    public function __construct(Producer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param AMQPMessage $message
     * @throws Exception
     */
    public function execute()
    {
        $testMessageBody = [
            'name' => 'John Doe',
            'Age' => 7000
        ];

        $messageBody = json_encode($testMessageBody);

        $this->producer->publish(
            config('rabbitmq.producers.test_producer'), 
            $messageBody
        );
    }
}

```

## Contributing
Please note the following guidelines before submitting pull requests:
- Use the [PSR-2 coding style](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)

## License
See [LICENSE](LICENSE).

## Migration to vladimir-yuldashev/laravel-queue-rabbitmq

### Why Migrate?
The recommended package offers several advantages:
- **Laravel Queue Integration**: Seamless integration with Laravel's built-in queue system
- **Active Maintenance**: Regularly updated with bug fixes and new features
- **Better Documentation**: Comprehensive documentation and examples
- **Modern PHP Support**: Supports modern PHP versions and Laravel releases
- **Queue Jobs**: Native support for Laravel job classes
- **Better Error Handling**: Improved error handling and retry mechanisms
- **Testing Support**: Built-in testing capabilities

### Migration Steps

#### 1. Install the New Package
```bash
composer require vladimir-yuldashev/laravel-queue-rabbitmq
```

#### 2. Update Configuration
Replace your `config/rabbitmq.php` with the new queue configuration in `config/queue.php`:

```php
'connections' => [
    // ... other connections
    'rabbitmq' => [
        'driver' => 'rabbitmq',
        'hosts' => [
            [
                'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                'port' => env('RABBITMQ_PORT', 5672),
                'user' => env('RABBITMQ_USER', 'guest'),
                'password' => env('RABBITMQ_PASSWORD', 'guest'),
                'vhost' => env('RABBITMQ_VHOST', '/'),
            ],
        ],
        'options' => [
            'exchange' => [
                'name' => env('RABBITMQ_EXCHANGE_NAME', 'default'),
                'type' => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
                'declare' => env('RABBITMQ_EXCHANGE_DECLARE', true),
                'passive' => env('RABBITMQ_EXCHANGE_PASSIVE', false),
                'durable' => env('RABBITMQ_EXCHANGE_DURABLE', true),
                'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
                'internal' => env('RABBITMQ_EXCHANGE_INTERNAL', false),
                'nowait' => env('RABBITMQ_EXCHANGE_NOWAIT', false),
            ],
            'queue' => [
                'declare' => env('RABBITMQ_QUEUE_DECLARE', true),
                'passive' => env('RABBITMQ_QUEUE_PASSIVE', false),
                'durable' => env('RABBITMQ_QUEUE_DURABLE', true),
                'exclusive' => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                'auto_delete' => env('RABBITMQ_QUEUE_AUTODELETE', false),
                'bind' => env('RABBITMQ_QUEUE_BIND', true),
                'routing_key' => env('RABBITMQ_QUEUE_ROUTING_KEY', ''),
            ],
        ],
    ],
],
```

#### 3. Convert Your Code

**Old Producer Code:**
```php
use Almatar\RabbitMQ\Adapters\Producer;

class TestService
{
    public function execute()
    {
        $producer = app(Producer::class);
        $producer->publish(
            config('rabbitmq.producers.test_producer'), 
            json_encode(['name' => 'John Doe', 'Age' => 7000])
        );
    }
}
```

**New Laravel Job:**
```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        // Your message processing logic here
        Log::info('Processing message', $this->data);
    }
}

// Dispatch the job
ProcessMessageJob::dispatch(['name' => 'John Doe', 'Age' => 7000]);
```

**Old Consumer Code:**
```php
use Almatar\RabbitMQ\Adapters\Consumer;

class TestConsumer extends Command
{
    public function handle()
    {
        $consumer = app(Consumer::class);
        $consumer->subscribe(
            config('rabbitmq.consumers.test_consumer'),
            [$this, 'consume']
        );
    }

    public function consume(AMQPMessage $message)
    {
        $this->info('Message Consumed: ' . $message->getBody());
        $message->ack();
    }
}
```

**New Laravel Queue Worker:**
```bash
# Simply run Laravel's queue worker
php artisan queue:work rabbitmq
```

#### 4. Remove Old Package
```bash
composer remove almatar/rabbitmq
```

### Need Help?
- [vladimir-yuldashev/laravel-queue-rabbitmq Documentation](https://github.com/vyuldashev/laravel-queue-rabbitmq)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)

## Roadmap
- ~~Support publishing to queues directly~~
- ~~Support HTTPS connection~~
- ~~Support transactions~~
- ~~Support batching~~
- ~~Add unit testing~~
- ~~Adding default logger and can be customized~~

**Note: This package is deprecated. Please migrate to vladimir-yuldashev/laravel-queue-rabbitmq.**
