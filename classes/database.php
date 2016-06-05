<?php

class Database {
  protected $connection;

  public function __construct() {
    $connectionString = C()->databasetype() . ':host=' . C()->databasehost() . ';dbname=' . C()->databasename() . ';charset=utf8';
    $this->connection = new PDO($connectionString, C()->databaseusername(), C()->databasepassword());
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

  public function quote($s) {
    return $this->connection->quote($s);
  }
}
