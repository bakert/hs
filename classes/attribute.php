<?php

class Attribute {
  private static $attributes;

  private $name;
  private $jsonName;
  private $dbName;
  private $keys;
  private $type;

  public static function attributes() {
    if (static::$attributes === null) {
      static::$attributes = [];
      // Strict substrings of other keys must appear later in the list.
      static::$attributes[] = new Attribute('playerClass', 'playerClass', 'player_class', ['class', 'cl', 'playerclass', 'pc'], Key::TEXT);
      static::$attributes[] = new Attribute('type', 'type', 'type', ['type', 'ty'], Key::TEXT);
      static::$attributes[] = new Attribute('text', 'text', 'text', ['text', 'te'], Key::TEXT);
      static::$attributes[] = new Attribute('set', 'set', 'set', ['set', 's'], Key::SPECIAL);
      static::$attributes[] = new Attribute('name', 'name', 'name', ['name'], Key::TEXT);
      static::$attributes[] = new Attribute('id', 'id', 'system_id', ['id'], Key::TEXT);
      static::$attributes[] = new Attribute('health', 'health', 'health', ['health', 'h'], Key::NUMBER);
      static::$attributes[] = new Attribute('rarity', 'rarity', 'rarity', ['rarity', 'rare'], Key::TEXT);
      static::$attributes[] = new Attribute('cost', 'cost', 'cost', ['cost'], Key::NUMBER);
      static::$attributes[] = new Attribute('attack', 'attack', 'attack', ['attack', 'a'], Key::NUMBER);
      static::$attributes[] = new Attribute('race', 'race', 'race', ['race'], Key::TEXT);
      static::$attributes[] = new Attribute('collectible', 'collectible', 'collectible', ['collectible', 'col'], Key::BOOLEAN);
      static::$attributes[] = new Attribute('artist', 'artist', 'artist', ['artist', 'art'], Key::TEXT);
      static::$attributes[] = new Attribute('flavor', 'flavor', 'flavor', ['flavor'], Key::TEXT);
      static::$attributes[] = new Attribute('faction', 'faction', 'faction', ['faction', 'fac'], Key::TEXT);
      static::$attributes[] = new Attribute('howToEarn', 'howToEarn', 'how_to_earn', ['earn'], Key::TEXT);
      static::$attributes[] = new Attribute('targetingArrowText', 'targetingArrowText', 'targeting_arrow_text', ['arrow'], Key::TEXT);
      static::$attributes[] = new Attribute('howToEarnGolden', 'howToEarnGolden', 'how_to_earn_golden', ['eg'], Key::TEXT);
      static::$attributes[] = new Attribute('durability', 'durability', 'durability', ['durability', 'd'], Key::NUMBER);
      static::$attributes[] = new Attribute('spellDamage', 'spellDamage', 'spell_damage', ['spelldamage', 'sd'], Key::NUMBER);
      static::$attributes[] = new Attribute('overload', 'overload', 'overload', ['overload', 'o'], Key::NUMBER);
      static::$attributes[] = new Attribute('playable', null, null, ['playable', 'play', 'p'], Key::SPECIAL);
      static::$attributes[] = new Attribute('format', null, null, ['format', 'f'], Key::SPECIAL);
    }
    return static::$attributes;
  }

  public static function jsonAttributes() {
    return array_filter(array_map(function($attr) { return $attr->jsonName(); }, static::attributes()));
  }

  public static function dbAttributes() {
    return array_filter(array_map(function($attr) { return $attr->dbName(); }, static::attributes()));
  }

  public static function fromKey($key) {
    foreach (static::attributes() as $attr) {
      if (in_array($key, $attr->keys())) {
        return $attr;
      }
    }
    return null;
  }

  public function name() {
    return $this->name;
  }

  private function jsonName() {
    return $this->jsonName;
  }

  public function dbName() {
    return $this->dbName;
  }

  public function keys() {
    return $this->keys;
  }

  public function type() {
    return $this->type;
  }

  private function __construct($name, $jsonName, $dbName, $keys, $type) {
    $this->name = $name;
    $this->jsonName = $jsonName;
    $this->dbName = $dbName;
    $this->keys = $keys;
    $this->type = $type;
  }
}
