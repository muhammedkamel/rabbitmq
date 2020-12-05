<?php

declare(strict_types=1);

namespace Almatar\RabbitMQ\Adapters;

use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;
use Almatar\RabbitMQ\Connectors\Connector;

use function PHPSTORM_META\argumentsSet;

/**
 * Class BaseAmqp.
 *
 * @author Mohamed Kamel <muhamed.kamel.elsayed@gmail.com>
 */
class BaseAmqp
{
    /**
     * @var Connector
     */
    private $connector;

    /**
     * @var \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    protected $connection;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    protected $channel;

    /**
     * @var array
     */
    protected $basicProperties = [
        'content_encoding' => 'UTF-8',
        'content_type'     => 'application/json',
        'delivery_mode'    => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    ];

    /**
     * @var array
     */
    protected $exchangeOptions = [
        'passive'     => false,
        'durable'     => true,
        'auto_delete' => false,
        'internal'    => false,
        'nowait'      => false,
        'arguments'   => null,
        'ticket'      => null,
        'declare'     => true,
    ];

    /**
     * @var array
     */
    protected $queueOptions = [
        'name'        => '',
        'passive'     => false,
        'durable'     => true,
        'exclusive'   => false,
        'auto_delete' => false,
        'nowait'      => false,
        'arguments'   => null,
        'ticket'      => null,
        'declare'     => true,
    ];

    /**
     * BaseAmqp constructor.
     *
     * @param Connector $connector
     *
     * @throws \Exception
     */
    public function __construct(Connector $connector)
    {
        $this->connector = $connector;

        $this->connect($this->connector);
    }

    /**
     * Close connection after finishing.
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * @param Connector $connector
     *
     * @throws \Exception
     */
    private function connect(Connector $connector)
    {
        $this->connection = $connector->connect();
        $this->channel = $this->connection->channel();
    }

    /**
     * Close connection then connect to rabbitmq server.
     *
     * @throws \Exception
     */
    public function reconnect()
    {
        $this->closeConnection();

        $this->connect($this->connector);
    }

    /**
     * Close connection with rabbitmq server.
     */
    private function closeConnection()
    {
        if ($this->channel) {
            try {
                $this->channel->close();
            } catch (\Exception $e) {
                // ignore on shutdown
            }
        }
        if ($this->connection && $this->connection->isConnected()) {
            try {
                $this->connection->close();
            } catch (\Exception $e) {
                // ignore on shutdown
            }
        }
    }

    /**
     * @return \PhpAmqpLib\Channel\AMQPChannel
     */
    public function getChannel()
    {
        if (empty($this->channel) || null === $this->channel->getChannelId()) {
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    protected function declareExchange(array $options)
    {
        $this->exchangeOptions = array_merge($this->exchangeOptions, $options);

        $this->getChannel()->exchange_declare(
            $this->exchangeOptions['name'],
            $this->exchangeOptions['type'],
            $this->exchangeOptions['passive'],
            $this->exchangeOptions['durable'],
            $this->exchangeOptions['auto_delete'],
            $this->exchangeOptions['internal'],
            $this->exchangeOptions['nowait'],
            $this->exchangeOptions['arguments'],
            $this->exchangeOptions['ticket']
        );

        if (isset($this->queueOptions['bindings'])) {
            $this->bindQueues($this->queueOptions['name'], $this->queueOptions['bindings']);
        }
    }

    protected function bindQueues(string $exchange, array $bindings)
    {
        foreach ($bindings as $binding) {
            $arguments  = [];

            if (isset($binding['headers'])) {
                $arguments = new AMQPTable($binding['headers']);
            }

            $this->getChannel()
                ->queue_bind($binding['name'], $exchange, $binding['routing_key'] ?? '', false, $arguments);
        }
    }

    protected function declareQueue(array $options)
    {
        $this->queueOptions = array_merge($this->queueOptions, $options);

        $this->getChannel()->queue_declare(
            $this->queueOptions['name'],
            $this->queueOptions['passive'],
            $this->queueOptions['durable'],
            $this->queueOptions['exclusive'],
            $this->queueOptions['auto_delete'],
            $this->queueOptions['nowait'],
            new AMQPTable($this->queueOptions['arguments']),
            $this->queueOptions['ticket']
        );

        if (isset($this->queueOptions['bindings'])) {
            $this->bindExchanges($this->queueOptions['name'], $this->queueOptions['bindings']);
        }
    }

    protected function bindExchanges(string $queue, array $bindings)
    {
        foreach ($bindings as $binding) {
            $arguments  = [];

            if (isset($binding['headers'])) {
                $arguments = new AMQPTable($binding['headers']);
            }

            $this->getChannel()
                ->queue_bind($queue, $binding['name'], $binding['routing_key'] ?? '', false, $arguments);
        }
    }

    /**
     * @return array
     */
    protected function getBasicProperties()
    {
        return $this->basicProperties;
    }

    /**
     * Close channel and connection.
     */
    public function shutdown()
    {
        try {
            $this->channel->close();
            $this->connection->close();
        } catch (\Exception $e) {
            // Log::warning($e->getMessage());
        }
    }
}
