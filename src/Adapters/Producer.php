<?php

declare(strict_types=1);

namespace Almatar\RabbitMQ\Adapters;

use InvalidArgumentException;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;

class Producer extends BaseAmqp
{
    public function publishOnQueue(string $queue, $msgBody, array $headers = [], array $additionalProperties = [])
    {
        $queueDefinition = config("rabbitmq.queues.{$queue}");

        if (!$queueDefinition) throw new InvalidArgumentException('Invalid queue definition');

        $this->declareQueue($queueDefinition);

        $msg = $this->prepareMessage($msgBody, $headers, $additionalProperties);

        $this->getChannel()->basic_publish($msg, '', $queueDefinition['name']);
    }

    public function publishOnExchange(string $exchange, $msgBody, array $headers = [], array $additionalProperties = [])
    {
        $exchangeDefinition = config("rabbitmq.exchanges.{$exchange}");

        if (!$exchangeDefinition) throw new InvalidArgumentException('Invalid exchange definition');

        $this->declareExchange($exchangeDefinition);

        $msg = $this->prepareMessage($msgBody, $headers, $additionalProperties);

        $this->getChannel()->basic_publish($msg, $exchangeDefinition['name'], $exchangeDefinition['routing_key'] ?? '');
    }

    protected function prepareMessage($msgBody, array $headers, array $additionalProperties)
    {
        if (!is_string($msgBody)) {
            $msgBody = json_encode($msgBody);
        }

        $msg = new AMQPMessage($msgBody, array_merge($this->getBasicProperties(), $additionalProperties));

        if (!empty($headers)) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }

        return $msg;
    }
}
