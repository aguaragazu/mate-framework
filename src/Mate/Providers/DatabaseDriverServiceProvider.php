<?php

namespace Mate\Providers;

use Mate\Database\Drivers\DatabaseDriver;
use Mate\Database\Drivers\PdoDriver;

class DatabaseDriverServiceProvider {
    public function registerServices() {
        match (config("database.driver", "mysql")) {
            "mysql", "pgsql" => singleton(DatabaseDriver::class, PdoDriver::class),
        };
    }
}
