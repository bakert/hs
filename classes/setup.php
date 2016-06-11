<?php

require_once(__DIR__ . '/../hs.php');

class Setup {
  public function main() {
    if (!C()->databasename() || !C()->databaseusername()
        || !C()->databasehost() || !C()->databasepassword()) {
      return '<p>Add databasename, datausername, databasehose and '
        . 'databasepassword values to config.json and reload this page.<p>';
    }

    $this->useSuccessful = false;
    register_shutdown_function([$this, 'databaseUnavailable']);
    D()->execute("USE " . C()->databasename());
    $this->useSuccessful = true;

    foreach (['card_set', 'set_name', 'card_mechanic', 'mechanic', 'entourage', 'card_play_requirement', 'play_requirement', 'format_set', 'format', 'set', 'card'] as $table) {
      $sql = "DROP TABLE IF EXISTS `$table`";
      D()->execute($sql);
    }

    $card = "CREATE TABLE card ("
      . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, ";
    foreach (Attribute::attributes() as $attr) {
      if ($attr->dbName() === null) {
        continue;
      }
      $card .= '`' . $attr->dbName() . '` ';
      if ($attr->type() === Key::NUMBER) {
        $card .= 'INT';
      } else {
        $card .= 'NVARCHAR(64)';
      }
      if (in_array($attr->dbName(), ['name', 'type', 'system_id'])) {
        $card .= ' NOT NULL';
      }
      if (in_array($attr->dbName(), ['system_id'])) {
        $card .= ' UNIQUE';
      }
      $card .= ', ';
    }
    $card = rtrim($card, ', ');
    $card .= ") Engine = InnoDB DEFAULT CHARSET=UTF8";

    $statements = [

      $card,

      "CREATE TABLE `set` ("
        . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, "
        . "name NVARCHAR(64) NOT NULL UNIQUE"
        . ") Engine = InnoDB DEFAULT CHARSET=UTF8",

      "CREATE TABLE card_set ("
        . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, "
        . "card_id INT NOT NULL, "
        . "set_id INT NOT NULL, "
        . "FOREIGN KEY (card_id) REFERENCES card (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE, "
        . "FOREIGN KEY (set_id) REFERENCES `set` (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE"
        . ") Engine = InnoDB DEFAULT CHARSET=UTF8",

      "CREATE TABLE set_name ("
        . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, "
        . "set_id INT NOT NULL, "
        . "name NVARCHAR(255) NOT NULL UNIQUE, "
        . "FOREIGN KEY (set_id) REFERENCES `set` (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE"
        . ") Engine = InnoDB DEFAULT CHARSET=UTF8",

      "CREATE TABLE mechanic ("
        . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, "
        . "name NVARCHAR(64) NOT NULL "
        . ") Engine = InnoDB DEFAULT CHARSET=UTF8",

      "CREATE TABLE card_mechanic ("
        . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, "
        . "card_id INT NOT NULL, "
        . "mechanic_id INT NOT NULL, "
        . "FOREIGN KEY (card_id) REFERENCES card (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE, "
        . "FOREIGN KEY (mechanic_id) REFERENCES mechanic (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE"
        . ") Engine = InnoDB DEFAULT CHARSET=UTF8",

      "CREATE TABLE entourage ("
        . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, "
        . "card_id INT NOT NULL, "
        . "entourage_id INT NOT NULL, "
        . "FOREIGN KEY (card_id) REFERENCES card (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE, "
        . "FOREIGN KEY (entourage_id) REFERENCES card (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE"
        . ") Engine = InnoDB DEFAULT CHARSET=UTF8",

      "CREATE TABLE play_requirement ("
        . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, "
        . "name NVARCHAR(255) NOT NULL "
        . ") Engine = InnoDB DEFAULT CHARSET=UTF8",

      "CREATE TABLE card_play_requirement ("
        . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, "
        . "card_id INT NOT NULL, "
        . "play_requirement_id INT NOT NULL, "
        . "value INT NOT NULL, "
        . "FOREIGN KEY (card_id) REFERENCES card (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE, "
        . "FOREIGN KEY (play_requirement_id) REFERENCES play_requirement (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE"
        . ") Engine = InnoDB DEFAULT CHARSET=UTF8",

      "CREATE TABLE format ("
        . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, "
        . "name NVARCHAR(64)"
        . ") Engine = InnoDB DEFAULT CHARSET=UTF8",

      "CREATE TABLE format_set ("
        . "id INT PRIMARY KEY AUTO_INCREMENT UNIQUE NOT NULL, "
        . "format_id INT NOT NULL, "
        . "set_id INT NOT NULL, "
        . "FOREIGN KEY (format_id) REFERENCES format (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE, "
        . "FOREIGN KEY (set_id) REFERENCES `set` (id) "
        . "ON UPDATE NO ACTION ON DELETE CASCADE"
        . ") Engine = InnoDB DEFAULT CHARSET=UTF8",

    ];

    foreach ($statements as $statement) {
      try {
        echo "$statement<p>";
        var_dump(D()->execute($statement));
      } catch (DatabaseException $e) {
        if (mb_substr($statement, 0, 5) !== 'GRANT') {
          throw $e;
        }
      }
      echo "<hr>";
    }
  }

  public function databaseUnavailable() {
    if (!$this->useSuccessful) {
      echo '<p>Issue the following commands in MySQL, then reload this '
        . 'page:</p>'
        . '<pre>'
        . 'CREATE DATABASE ' . C()->databasename() . ';'
        . 'GRANT ALL ON ' . C()->databasename() . '' . ".* TO "
        . C()->databaseusername() . "@" . C()->databasehost()
        . " IDENTIFIED BY '" . addslashes(C()->databasepassword()) . "';"
        . '</pre>';
      }
  }
}

echo (new Setup())->main();
