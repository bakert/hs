<?php

class Singletons {
  private static $instances;

  public static function __callStatic($name, $arguments) {
    static $bindings = [
      'C' => 'Config',
      'D' => 'Database',
      'T' => 'Template',
      'U' => 'Url',
    ];
    if (!is_array(static::$instances)) {
      static::$instances = [];
    }
    if (!isset(static::$instances[$name])) {
      static::$instances[$name] = new $bindings[$name]();
    }
    return static::$instances[$name];
  }
}
