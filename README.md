# almatar/rabbitmq
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

## Roadmap
- Support publishing to queues directly
- Support HTTPS connection
- Support transactions
- Support batching
- Add unit testing
- Adding default logger and can be customized
