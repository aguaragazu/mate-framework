<?php

namespace Mate\Cli;

use Dotenv\Dotenv;
use Mate\App;
use Mate\Cli\Commands\MakeController;
use Mate\Cli\Commands\MakeMigration;
use Mate\Cli\Commands\MakeModel;
use Mate\Cli\Commands\Migrate;
use Mate\Cli\Commands\MigrateRollback;
use Mate\Cli\Commands\Serve;
use Mate\Config\Config;
use Mate\Database\DB;
use Mate\Database\Migrations\Migrator;
use Mate\Providers\DatabaseDriverServiceProvider;
use Symfony\Component\Console\Application;

/**
 * Mate development command line interface.
 */
class Cli {
    /**
     * Bootstrap CLI app.
     *
     * @param string $root
     * @return self
     */
    public static function bootstrap(string $root): self {
        App::setRoot($root);
        Dotenv::createImmutable(App::getRoot())->load();
        Config::load("$root/config");

        (new DatabaseDriverServiceProvider())->registerServices();
        DB::connect(config("database"));

        singleton(
            Migrator::class,
            fn () => new Migrator(
                "$root/database/migrations",
                "$root/resources/templates"
            )
        );

        return new self();
    }

    /**
     * Run CLI app.
     *
     * @return void
     */
    public function run() {
        $cli = new Application("Mate");

        $cli->addCommands([
            new MakeController(),
            new MakeMigration(),
            new MakeModel(),
            new Migrate(),
            new MigrateRollback(),
            new Serve(),
        ]);

        $cli->run();
    }
}
