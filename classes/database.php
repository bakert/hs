<?php

class Database {
  protected $connection;

  public function __construct() {
    $connectionString = C()->databaseType() . ':host=' . C()->databaseHost() . ';dbname=' . C()->databaseName() . ';charset=utf8';
    $this->connection = new PDO($connectionString, C()->databaseUsername(), C()->databasePassword());
  }

  public function execute($sql, $args = []) {
    $statement = $this->connection->prepare($sql);
    $result = $statement->execute($args);
    if (C()->debug()) {
      echo "<hr>";
      echo $sql;
      echo "<br>";
      var_dump($args);
      echo "<br>";
      var_dump($result);
      echo "<hr>";
    }
    if ($result === false) {
      throw new DatabaseException("Database failure: '$sql'");
    }
    if ($statement->columnCount() > 0) {
      return $statement->fetchAll();
    }
    return $result;
  }

  public function value($sql, $default = 'ERROR') {
    $rs = $this->execute($sql);
    if (!isset($rs[0][0])) {
      if ($default === 'ERROR') {
        $msg = "Asked for value but none present with '$sql'";
        throw new DatabaseException($msg);
      } else {
        return $default;
      }
    }
    return $rs[0][0];
  }

  public function values($sql) {
    $rs = $this->execute($sql);
    if (!is_array($rs)) {
      return $rs;
    }
    $results = [];
    foreach ($rs as $row) {
      $results[] = $row[0];
    }
    return $results;
  }

  public function quote($s) {
    return $this->connection->quote($s);
  }
}
