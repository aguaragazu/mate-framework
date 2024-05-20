<?php

namespace Mate\Database\Migrations;

use Mate\Database\Database;
use Mate\Database\Migrations\MateStructure;

class Schema {
  public static function create($table, callable $callback) {
      $blueprint = new MateStructure();
      $callback($blueprint);

      $columns = $blueprint->getColumns();
      $foreignKeys = $blueprint->getForeignKeys();
      $indices = $blueprint->getIndices();

      $sql = "CREATE TABLE $table (";
      $columnsSql = [];
      foreach ($columns as $name => $column) {
        $type = $column->type;
        $attributes = [];
        $attributes[] = $name . " " . $type;

        if (isset($column->autoincrement)) {
            $attributes[] = 'AUTOINCREMENT PRIMARY KEY';
        }

        if (isset($column->nullable) && $column->nullable) {
            $attributes[] = 'NULL';
        } else {
            $attributes[] = 'NOT NULL';
        }

        $cols[] = implode(" ", $attributes);
    }

      // Agregar definiciones de llaves foráneas
      foreach ($foreignKeys as $fk) {
          $columnsSql[] = "FOREIGN KEY ({$fk['column']}) REFERENCES {$fk['on']}({$fk['references']}) ON DELETE {$fk['onDelete']}";
      }

      $sql .= implode(", ", $columnsSql);
      $sql .= ");";

      // Ejecutar definiciones de índices
      foreach ($indices as $index) {
          $indexColumns = implode(', ', $index['columns']);
          $sql .= "CREATE INDEX {$index['name']} ON $table ($indexColumns);";
      }
      
      // Aquí ejecutarías $sql en tu base de datos
      Database::statement($sql);
  }

  public static function drop($table) {
      $sql = "DROP TABLE IF EXISTS $table;";
      Database::statement($sql);
  }

  public static function rename($table, $newTable) {
      $sql = "ALTER TABLE $table RENAME TO $newTable;";
      Database::statement($sql);
  }
}