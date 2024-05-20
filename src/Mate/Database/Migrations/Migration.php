<?php

namespace Mate\Database\Migrations;

interface Migration
{
    public function up();
    public function down();
}
