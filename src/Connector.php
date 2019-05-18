<?php

namespace Almatar\RabbitMQ;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class Connector
 * @package Almatar\RabbitMQ
 * @author elhassan.mohamed
 */
class Connector
{
    /**
     * @var AMQPStreamConnection
     */
    private $connection = null;

    /**
     * @var array
     */
    private $parameters = array(
        'host' => 'localhost',
        'port' => 5672,
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/',
        'connection_timeout' => 3,
        'read_write_timeout' => 3,
        'ssl_context' => null,
        'keepalive' => false,
        'heartbeat' => 0,
    );

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
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        if (isset($parameters['connection_attempts'])) {
            $this->connectionAttemps = (int)$parameters['connection_attempts'];
            unset($parameters['connection_attempts']);
        }

        if (isset($parameters['reconnect_waiting_seconds'])) {
            $this->reconnectWaitingSecs = (int)$parameters['reconnect_waiting_seconds'];
            unset($parameters['reconnect_waiting_seconds']);
        }

        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * get AMQP connection
     *
     * @return AMQPStreamConnection
     * @throws \Exception
     */
    public function getConnection()
    {
        $connectionAttempts = 0;

        while (true) {
            try {
                $connectionAttempts++;
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
            } catch (\Exception $e) {
                if ($connectionAttempts < $this->connectionAttemps) {
                    Log::warning($e->getMessage());

                    if ($this->connection !== null) {
                        $this->connection->close();
                    }

                    sleep($this->reconnectWaitingSecs);
                    continue;
                } else {
                    throw $e;
                }
            }
        }
    }
}
