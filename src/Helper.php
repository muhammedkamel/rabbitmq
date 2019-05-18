<?php

namespace Almatar\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class Helper
 * @package Almatar\RabbitMQ
 * @author Mohamed Kamel <mohamed.kamel@almtar.com>
 */
class Helper
{

    /**
     *
     * @param AMQPMessage $message
     * @return array
     */
    public static function getHeaders(AMQPMessage $message): array
    {

        $headers = $message->get_properties()['application_headers'] ?? [];

        if ($headers) {
            $headers = $headers->getNativeData();
        }

        return $headers;
    }
}
