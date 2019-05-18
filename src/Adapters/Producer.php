<?php

namespace Almatar\RabbitMQ\Adapters;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class Producer
 * @package Almatar\RabbitMQ\Adapters
 * @author Mohamed Kamel <mohamed.kamel@almtar.com>
 */
class Producer extends BaseAmqp
{

    /**
     *
     * @param array $config
     * @param string $msgBody
     * @param array $additionalProperties
     * @param array $headers
     */
    public function publish(array $config, string $msgBody, array $additionalProperties = [], array $headers = [])
    {
        $this->exchangeDeclare($config['exchange_options']);

        $this->queueDeclare($config['queue_options']);

        $msg = new AMQPMessage((string)$msgBody, array_merge($this->getBasicProperties(), $additionalProperties));

        if (!empty($headers)) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }

        $this->getChannel()
            ->basic_publish($msg, $this->exchangeOptions['name'], (string)$this->queueOptions['routing_key']);
    }
}
