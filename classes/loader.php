<?php

require_once(__DIR__ . '/../hs.php');

class Loader {
  const URL = 'https://api.hearthstonejson.com/v1/latest/enUS/cards.json';

  private $cards;
  private $transaction;
  private $systemIdToIdMap;

  public function load() {
    $this->cards = $this->scrapeCards();
    $this->transaction = new Transaction();
    $this->deleteAll();
    $this->insertCards();
    $this->setupSystemIdToIdMap();
    $this->insertMechanics();
    $this->insertCardMechanics();
    $this->insertPlayRequirements();
    $this->insertCardPlayRequirements();
    $this->insertEntourage();
    $this->insertFormats();
    $this->insertSetFormats();
    return $this->transaction->commit();
  }

  private function scrapeCards() {
    $file = file_get_contents(static::URL);
    return json_decode($file, true /* as array */);
  }

  private function deleteAll() {
    $sql = 'DELETE FROM card';
    $this->transaction->execute($sql);
    $sql = 'DELETE FROM mechanic';
    $this->transaction->execute($sql);
    $sql = 'DELETE FROM play_requirement';
    $this->transaction->execute($sql);
    $sql = 'DELETE FROM format';
    $this->transaction->execute($sql);
    $sql = 'DELETE FROM format_set';
    return $this->transaction->execute($sql);
  }

  private function insertCards() {
    $sql = 'INSERT INTO card (';
    $sql .= '`' . implode('`, `', Attribute::dbAttributes()) . '`';
    $sql .= ') VALUES ';
    $args = [];
    foreach ($this->cards as $card) {
      $sql .= '(' . rtrim(str_repeat('?, ', count(Attribute::jsonAttributes())), ', ') . '), ';
      foreach (Attribute::jsonAttributes() as $attr) {
        $args[] = isset($card[$attr]) ? $card[$attr] : null;
      }
    }
    $sql = rtrim($sql, ', ');
    return $this->transaction->execute($sql, $args);
  }

  private function setupSystemIdToIdMap() {
    $sql = 'SELECT system_id, id FROM card';
    $systemIdToIdMap = [];
    $f = function ($x) use (&$systemIdToIdMap) { $systemIdToIdMap[$x['system_id']] = $x['id']; };
    array_map($f, $this->transaction->execute($sql));
    $this->systemIdToIdMap = $systemIdToIdMap;
  }

  private function insertMechanics() {
    $sql = 'INSERT INTO mechanic (name) VALUES ';
    $args = $this->allValues('mechanics');
    $sql .= str_repeat('(?), ', count($args));
    $sql  = rtrim($sql, ', ');
    return $this->transaction->execute($sql, $args);
  }

  private function insertCardMechanics() {
    $sql = 'SELECT name, id FROM mechanic';
    $nameToIdMap = [];
    $f = function ($x) use (&$nameToIdMap) { $nameToIdMap[$x['name']] = $x['id']; };
    array_map($f, $this->transaction->execute($sql));
    $args = [];
    foreach ($this->cards as $card) {
      if (!isset($card['mechanics'])) {
        continue;
      }
      foreach ($card['mechanics'] as $mechanic) {
        $args = array_merge($args, [$this->systemIdToIdMap[$card['id']], $nameToIdMap[$mechanic]]);
      }
    }
    $sql = 'INSERT INTO card_mechanic (card_id, mechanic_id) VALUES ';
    $sql .= str_repeat('(?, ?), ', count($args) / 2);
    $sql = rtrim($sql, ', ');
    return $this->transaction->execute($sql, $args);
  }

  private function insertPlayRequirements() {
    $sql = 'INSERT INTO play_requirement (name) VALUES ';
    $args = $this->allValues('playRequirements', true /* use key */);
    $sql .= str_repeat('(?), ', count($args));
    $sql  = rtrim($sql, ', ');
    return $this->transaction->execute($sql, $args);
  }

  private function insertCardPlayRequirements() {
    $sql = 'SELECT name, id FROM play_requirement';
    $nameToIdMap = [];
    $f = function ($x) use (&$nameToIdMap) { $nameToIdMap[$x['name']] = $x['id']; };
    array_map($f, $this->transaction->execute($sql));
    $args = [];
    foreach ($this->cards as $card) {
      if (!isset($card['playRequirements'])) {
        continue;
      }
      foreach ($card['playRequirements'] as $key => $value) {
        $args = array_merge($args, [$this->systemIdToIdMap[$card['id']], $nameToIdMap[$key], $value]);
      }
    }
    $sql = 'INSERT INTO card_play_requirement (card_id, play_requirement_id, value) VALUES ';
    $sql .= str_repeat('(?, ?, ?), ', count($args) / 3);
    $sql = rtrim($sql, ', ');
    return $this->transaction->execute($sql, $args);
  }

  private function insertEntourage() {
    $sql = 'INSERT INTO entourage (card_id, entourage_id) VALUES ';
    $args = [];
    foreach ($this->cards as $card) {
      if (!isset($card['entourage'])) {
        continue;
      }
      $cardId = $this->systemIdToIdMap[$card['id']];
      foreach ($card['entourage'] as $entourageSystemId) {
        $entourageId = $this->systemIdToIdMap[$entourageSystemId];
        $args = array_merge($args, [$cardId, $entourageId]);
      }
    }
    $sql .= str_repeat('(?, ?), ', count($args) / 2);
    $sql = rtrim($sql, ', ');
    return $this->transaction->execute($sql, $args);
  }

  private function insertFormats() {
    // Hardcoded for now because there isn't a good online source.
    $sql = 'INSERT INTO format (name) VALUES (?), (?)';
    return $this->transaction->execute($sql, ['WILD', 'STANDARD']);
  }

  private function insertSetFormats() {
    // Hardcoded for now because there isn't a good online source
    $sql = 'SELECT name, id FROM format';
    $nameToIdMap = [];
    $f = function ($x) use (&$nameToIdMap) { $nameToIdMap[$x['name']] = $x['id']; };
    array_map($f, $this->transaction->execute($sql));
    $standardSets = ['CORE', 'BRM', 'LOE', 'OG', 'TGT', 'EXPERT1', 'MISSIONS'];
    $wildSets = array_merge($standardSets, ['GVG', 'NAXX', 'PROMO', 'REWARD']);
    $sql = 'INSERT INTO format_set (format_id, `set`) VALUES ';
    $sql .= str_repeat('(?, ?), ', count($standardSets) + count($wildSets));
    $sql = rtrim($sql, ', ');
    $args = [];
    foreach ($standardSets as $set) {
      $args = array_merge($args, [$nameToIdMap['STANDARD'], $set]);
    }
    foreach ($wildSets as $set) {
      $args = array_merge($args, [$nameToIdMap['WILD'], $set]);
    }
    return $this->transaction->execute($sql, $args);
  }

  private function allValues($key, $useKey = false) {
    $values = [];
    foreach ($this->cards as $card) {
      if (!isset($card[$key])) {
        continue;
      }
      foreach ($card[$key] as $k => $v) {
        $values[$useKey ? $k : $v] = true;
      }
    }
    return array_keys($values);
  }
}

((new Loader())->load());