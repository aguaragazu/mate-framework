<?php

namespace Mate\Providers;

use Mate\Server\PhpNativeServer;
use Mate\Server\ServerData;

class ServerDataServiceProvider {
    public function registerServices() {
        singleton(ServerData::class, PhpNativeServer::class);
    }
}
