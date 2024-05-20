<?php

namespace Mate\Database\Migrations;

use Mate\Support\MateFluent;

class MateColumn extends MateFluent
{
  public function autoIncrement()
    {
        $this->type = 'int'; // Asegura que el tipo sea int para autoincremento
        $this->auto_increment = true;
        return $this;
    }

    public function nullable($value = true)
    {
        $this->nullable = $value;
        return $this;
    }
}
