<?php

namespace Mate\Cli;

use Dotenv\Dotenv;
use Mate\App;
use Mate\Cli\Commands\KeyGenerate;
use Mate\Cli\Commands\MakeController;
use Mate\Cli\Commands\MakeMigration;
use Mate\Cli\Commands\MakeModel;
use Mate\Cli\Commands\Migrate;
use Mate\Cli\Commands\MigrateRollback;
use Mate\Cli\Commands\Serve;
use Mate\Config\Config;
use Mate\Database\Drivers\DatabaseDriver;
use Mate\Database\Migrations\Migrator;
use Symfony\Component\Console\Application;

class Cli
{
    public static function bootstrap(string $root): self
    {
        App::$root = $root;
        Dotenv::createImmutable($root)->load();
        Config::load($root . "/config");

        foreach (config("providers.cli") as $provider) {
            (new $provider())->registerServices();
        }

        app(DatabaseDriver::class)->connect(
            config("database.connection"),
            config("database.host"),
            config("database.port"),
            config("database.database"),
            config("database.username"),
            config("database.password"),
        );

        singleton(
            Migrator::class,
            fn () => new Migrator(
                "$root/database/migrations",
                resourcesDirectory() . "/templates",
                app(DatabaseDriver::class)
            )
        );

        return new self();
    }

    public function run()
    {
        $cli = new Application("Mate");

        $cli->addCommands([
            new MakeMigration(),
            new Migrate(),
            new MigrateRollback(),
            new MakeModel(),
            new MakeController(),
            new KeyGenerate(),
            new Serve(),
        ]);

        $cli->run();
    }
}
