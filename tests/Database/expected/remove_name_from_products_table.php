<?php

use Mate\Database\DB;
use Mate\Database\Migrations\Migration;

return new class () implements Migration {
    /**
     * Run the migration.
     */
    public function up() {
        DB::statement('ALTER TABLE products');
    }

    /**
     * Reverse the migration.
     */
    public function down() {
        DB::statement('ALTER TABLE products');
    }
};
