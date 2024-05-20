<?php
namespace Mate\Support;

class MateTapProxy {
  private $value;

  public function __construct($value) {
      $this->value = $value;
  }

  public function __call($method, $arguments) {
      call_user_func_array([$this->value, $method], $arguments);
      return $this->value;
  }
}