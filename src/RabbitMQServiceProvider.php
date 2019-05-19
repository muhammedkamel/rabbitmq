<?php

namespace Almatar\RabbitMQ;

use Illuminate\Support\ServiceProvider;

/**
 * Class RabbitMQServiceProvider.
 *
 * @author Mohamed Kamel <muhamed.kamel.elsayed@gmail.com>
 */
class RabbitMQServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Connector::class, function ($app) {
            return new Connector(config('rabbitmq.connections.default'));
        });
    }
}
