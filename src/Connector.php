<?php

namespace Almatar\RabbitMQ;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolConnectionException;

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
    protected $connection;

    /**
     * get AMQP connection
     *
     * @return AMQPStreamConnection
     * @throws \Exception
     */
    public function getConnection()
    {
        $connection = null;
        $connectionAttempts = 0;

        while (true) {
            try {
                $connectionAttempts++;
                $connection = new AMQPStreamConnection(
                    config('rabbitmq.connections.default.host'),
                    config('rabbitmq.connections.default.port'),
                    config('rabbitmq.connections.default.username'),
                    config('rabbitmq.connections.default.password'),
                    config('rabbitmq.connections.default.vhost'),
                    false,
                    'AMQPLAIN',
                    null,
                    'en_US',
                    3.0,
                    config('rabbitmq.connections.default.read_write_timeout'),
                    null,
                    false,
                    config('rabbitmq.connections.default.heartbeat')
                );

                return $connection;
            } catch (AMQPProtocolConnectionException $e) { // authentication exception so there is no need to retry
                Log::warning($e->getMessage());

                throw $e;
            } catch (\Exception $e) {
                if ($connectionAttempts < config('rabbitmq.connections.default.connection_attempts')) {
                    Log::warning($e->getMessage());

                    if ($connection !== null) {
                        $connection->close();
                    }

                    sleep(config('rabbitmq.connections.default.reconnect_waiting_seconds'));
                    continue;
                } else {
                    throw $e;
                }
            }
        }
    }
}
