<?php

namespace Almatar\RabbitMQ\Adapters;

use Almatar\RabbitMQ\Connector;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class BaseAmqp
 * @package Almatar\RabbitMQ\Adapters
 * @author Mohamed Kamel <mohamed.kamel@almtar.com>
 */
class BaseAmqp
{

    /**
     *
     * @var Connector
     */
    private $connector;

    /**
     * @var \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    protected $connection;

    /**
     *
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    protected $channel;

    /**
     *
     * @var array
     */
    protected $basicProperties = [
        'content_encoding' => 'UTF-8',
        'content_type' => 'application/json',
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
    ];

    /**
     *
     * @var array
     */
    protected $exchangeOptions = [
        'passive' => false,
        'durable' => true,
        'auto_delete' => false,
        'internal' => false,
        'nowait' => false,
        'arguments' => null,
        'ticket' => null,
        'declare' => true,
    ];

    /**
     *
     * @var array
     */
    protected $queueOptions = [
        'name' => '',
        'passive' => false,
        'durable' => true,
        'exclusive' => false,
        'auto_delete' => false,
        'nowait' => false,
        'arguments' => null,
        'ticket' => null,
        'declare' => true,
    ];

    /**
     * BaseAmqp constructor.
     * @param Connector $connector
     * @throws \Exception
     */
    public function __construct(Connector $connector)
    {
        $this->connector = $connector;

        $this->connect($this->connector);
    }

    /**
     * Close connection after finishing
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * @param Connector $connector
     * @throws \Exception
     */
    private function connect(Connector $connector)
    {
        $this->connection = $connector->getConnection();
        $this->channel = $this->connection->channel();
    }

    /**
     * Close connection then connect to rabbitmq server
     * @throws \Exception
     */
    public function reconnect()
    {
        $this->closeConnection();

        $this->connect($this->connector);
    }

    /**
     * Close connection with rabbitmq server
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

    /**
     * Declares exchange
     * @param array $options
     */
    protected function exchangeDeclare(array $options)
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
    }

    /**
     * Declares queue, creates if needed
     * @param array $options
     */
    protected function queueDeclare(array $options)
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

        $this->queueBind(
            $this->queueOptions['name'],
            $this->exchangeOptions['name'],
            $this->queueOptions['routing_key']
        );
    }

    /**
     * Binds queue to an exchange
     *
     * @param string $queue
     * @param string $exchange
     * @param string $routing_key
     */
    protected function queueBind($queue, $exchange, $routing_key)
    {
        // queue binding is not permitted on the default exchange
        if ('' !== $exchange) {
            $this->getChannel()->queue_bind($queue, $exchange, $routing_key);
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
     * Close channel and connection
     */
    public function shutdown()
    {
        try {
            $this->channel->close();
            $this->connection->close();
        } catch (\Exception $e) {
            Log::warning($e->getMessage());
        }
    }
}
