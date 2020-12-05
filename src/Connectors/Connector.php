<?php

declare(strict_types=1);

namespace Almatar\RabbitMQ\Connectors;

use Exception;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Connector
{
    /**
     * @var AMQPStreamConnection
     */
    private $connection = null;

    /**
     * @var array
     */
    private $parameters = [
        'host'               => 'localhost',
        'port'               => 5672,
        'user'               => 'guest',
        'password'           => 'guest',
        'vhost'              => '/',
        'connection_timeout' => 3,
        'read_write_timeout' => 3,
        'ssl_context'        => null,
        'keepalive'          => false,
        'heartbeat'          => 0,
    ];

    /**
     * @var int
     */
    private $connectionAttemps = 0;

    /**
     * @var int
     */
    private $reconnectWaitingSecs = 0;

    /**
     * Connector constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        if (isset($parameters['connection_attempts'])) {
            $this->connectionAttemps = (int) $parameters['connection_attempts'];
            unset($parameters['connection_attempts']);
        }

        if (isset($parameters['reconnect_waiting_seconds'])) {
            $this->reconnectWaitingSecs = (int) $parameters['reconnect_waiting_seconds'];
            unset($parameters['reconnect_waiting_seconds']);
        }

        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * Get AMQP connection.
     *
     * @throws Exception
     *
     * @return AMQPStreamConnection
     */
    public function connect()
    {
        try {
            $this->connectionAttemps--;
            $this->connection = new AMQPStreamConnection(
                $this->parameters['host'],
                $this->parameters['port'],
                $this->parameters['user'],
                $this->parameters['password'],
                $this->parameters['vhost'],
                false,
                'AMQPLAIN',
                null,
                'en_US',
                $this->parameters['connection_timeout'],
                $this->parameters['read_write_timeout'],
                $this->parameters['ssl_context'],
                $this->parameters['keepalive'],
                $this->parameters['heartbeat']
            );

            return $this->connection;
        } catch (Exception $e) {
            Log::error($e->getMessage());

            if ($this->connectionAttemps <= 0) throw $e;

            if (!is_null($this->connection)) {
                $this->connection->close();
            }

            sleep($this->reconnectWaitingSecs);

            return $this->connect();
        }
    }
}
