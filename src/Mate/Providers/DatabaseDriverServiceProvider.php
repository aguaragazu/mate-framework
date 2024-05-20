<?php

namespace Mate\Providers;

use Mate\Database\Drivers\DatabaseDriver;
use Mate\Database\Drivers\PdoDriver;
use Mate\Database\Drivers\MysqliDriver;

class DatabaseDriverServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        match (config("database.driver", "pdo")) {
            "pdo" => singleton(DatabaseDriver::class, PdoDriver::class),
            "mysqli" => singleton(DatabaseDriver::class, MysqliDriver::class),
        };
    }
}
