<?php

namespace Mate\Tests\Database;

use Dotenv\Dotenv;
use Mate\Container\Container;
use Mate\Database\DB;
use Mate\Database\Drivers\DatabaseDriver;
use Mate\Database\Drivers\PdoDriver;
use PDOException;

trait RefreshDatabase {
    protected function setUp(): void {
        Container::singleton(DatabaseDriver::class, PdoDriver::class);
        $root = __DIR__ . "/../..";
        if (file_exists("$root/.env.testing")) {
            Dotenv::createImmutable($root, ".env.testing")->load();
        }
        try {
            $config = [
                'connection' => env("DB_CONNECTION", "mysql"),
                'host' => env("DB_HOST", "127.0.0.1"),
                'port' => env("DB_PORT", "3306"),
                'database' => env("DB_DATABASE", "mate_tests"),
                'username' => env("DB_USERNAME", "root"),
                'password' => env("DB_PASSWORD", "root"),
            ];

            DB::connect($config);
        } catch (PDOException $e) {
            $this->markTestSkipped("Cannot connect to test database: {$e->getMessage()}");
        }
    }

    protected function tearDown(): void {
        DB::statement("DROP DATABASE IF EXISTS mate_tests");
        DB::statement("CREATE DATABASE mate_tests");
    }
}
