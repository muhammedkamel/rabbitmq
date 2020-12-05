<?php

declare(strict_types=1);

namespace Almatar\RabbitMQ;

use Illuminate\Support\ServiceProvider;
use Almatar\RabbitMQ\Connectors\Connector;

class RabbitMQServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $connectionName = config('connection', 'default');
        $connectionConfig = config("rabbitmq.connections.{$connectionName}");

        $this->app->singleton(Connector::class, function () use ($connectionConfig) {
            return new Connector($connectionConfig);
        });
    }
}
