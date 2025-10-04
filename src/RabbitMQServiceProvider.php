<?php

namespace Almatar\RabbitMQ;

use Illuminate\Support\ServiceProvider;

/**
 * Class RabbitMQServiceProvider.
 *
 * @author Mohamed Kamel <muhamed.kamel.elsayed@gmail.com>
 * @deprecated This package is deprecated. Use vladimir-yuldashev/laravel-queue-rabbitmq instead.
 */
class RabbitMQServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     * @deprecated This package is deprecated. Use vladimir-yuldashev/laravel-queue-rabbitmq instead.
     */
    public function register()
    {
        // Log deprecation warning
        if (function_exists('logger')) {
            logger()->warning(
                'almatar/rabbitmq is deprecated and will not receive updates. ' .
                'Please migrate to vladimir-yuldashev/laravel-queue-rabbitmq for continued support.'
            );
        }

        $this->app->bind(Connector::class, function ($app) {
            return new Connector(config('rabbitmq.connections.default'));
        });
    }
}
