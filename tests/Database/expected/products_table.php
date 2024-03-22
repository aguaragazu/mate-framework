<?php

use Mate\Database\DB;
use Mate\Database\Migrations\Migration;

return new class () implements Migration {
    /**
     * Run the migration.
     */
    public function up() {
        DB::statement('CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY)');
    }

    /**
     * Reverse the migration.
     */
    public function down() {
        DB::statement('DROP TABLE products');
    }
};
