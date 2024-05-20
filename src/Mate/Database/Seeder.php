<?php
namespace Mate\Database;

use Mate\Database\Database;

abstract class Seeder {
  // abstract public function run();

  // protected function execute($sql) {
      
  //     Database::statement($sql);
  // }

  protected function runSeeder($seederClass) {
    $seeder = new $seederClass();
    $seeder->run();
  }
}