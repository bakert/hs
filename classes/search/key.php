<?php

class Key extends Token {
  const NUMBER = 'number';
  const SPECIAL = 'special';
  const TEXT = 'text';

  protected static function values() {
    static $values;
    if ($values === null) {
      $values = [];
      foreach (Attribute::attributes() as $attr) {
        $values = array_merge($values, $attr->keys());
      }
    }
    return $values;
  }
}
