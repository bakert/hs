<?php

class Transaction extends Database {
  private static $running = false;

  public function __construct() {
    if (static::$running) {
      throw new DatabaseException("Nested transactions not supported at this time.");
    }
    static::$running = true;
    parent::__construct();
    $this->connection->beginTransaction();
  }

  public function rollback() {
    $result = $this->connection->rollback();
    static::$running = false;
    return $result;
  }

  public function commit() {
    $result = $this->connection->commit();
    static::$running = false;
    return $result;
  }
}
