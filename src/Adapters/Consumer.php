<?php

namespace Almatar\RabbitMQ\Adapters;

use Illuminate\Support\Facades\Log;

/**
 * Class Consumer.
 *
 * @author Mohamed Kamel <muhamed.kamel.elsayed@gmail.com>
 * @deprecated This package is deprecated. Use vladimir-yuldashev/laravel-queue-rabbitmq instead.
 */
class Consumer extends BaseAmqp
{
    /**
     * @param array    $config
     * @param callable $callback
     *
     * @throws \Exception
     */
    public function subscribe(array $config, $callback)
    {
        try {
            $this->exchangeDeclare($config['exchange_options']);

            $this->channel->basic_qos(null, $config['qos_prefetch_count'], null);

            $this->queueDeclare($config['queue_options']);

            $this->channel->basic_consume($config['queue_options']['name'], '', false, false, false, false, $callback);

            register_shutdown_function([$this, 'reconnect']);

            while (count($this->channel->callbacks)) {
                $this->channel->wait();
            }
        } catch (\Exception $e) {
            Log::warning($e->getMessage());

            $this->reconnect();
            $this->subscribe($config, $callback);
        }
    }
}
