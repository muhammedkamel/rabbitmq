# Rabbitmq
[Rabbitmq](https://rabbitmq.com) adapter for [Lumen PHP framework](lumen.laravel.com)


## Requirements
- PHP >= 7.0
- Lumen/Laravel >= 5.0
- php-amqplib/php-amqplib >= 2.7

## Usage
### Install through Composer
Run the following command to install the package:

```sh
composer require almatar/rabbitmq
```

### Register the Service Provider
Add the following line to `bootstrap/app.php`:

```php
$app->register(Almatar\RabbitMQ\RabbitMQServiceProvider::class);
```

### Configure
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

### Example of `consumer`

```php

<?php

namespace App\Console\Commands;

use App\Services\TestService;
use Almatar\RabbitMQ\Adapters\Consumer;
use Illuminate\Console\Command;

class TestCommand extends Command
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
     * @var TestService
     */
    private $service;

    /**
     * TestCommand constructor.
     * @param Consumer $consumer
     * @param TestService $service
     */
    public function __construct(Consumer $consumer, TestService $service)
    {
        $this->consumer = $consumer;
        $this->service = $service;
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
            [$this->service, 'execute']
        );
    }
}

```

### Example of `producer`

```php

<?php

namespace App\Services;

use Almatar\RabbitMQ\Adapters\Producer;
use PhpAmqpLib\Message\AMQPMessage;

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
    public function execute(AMQPMessage $message)
    {
        $this->producer->publish(
            config('rabbitmq.producers.test_producer'), 
            $message->getBody()
        );
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag'], false);
    }
}

```

## Contributing
Please note the following guidelines before submitting pull requests:
- Use the [PSR-2 coding style](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)

## License
See [LICENSE](LICENSE).
