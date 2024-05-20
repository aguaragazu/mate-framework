<?php

namespace Mate\Providers;

use Mate\Server\PhpNativeServer;
use Mate\Server\Server;

class ServerServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        singleton(Server::class, PhpNativeServer::class);
    }
}
