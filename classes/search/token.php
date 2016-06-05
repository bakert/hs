<?php

abstract class Token {
  public static function is($chars) {
    return (bool) static::find($chars);
  }

  public static function length($chars) {
    return strlen(static::find($chars));
  }

  protected static function find($chars) {
    $s = implode($chars);
    foreach (static::values() as $value) {
      if (static::startsWith($s, $value)) {
        return $value;
      }
    }
    return '';
  }

  protected static function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  public function __construct($chars) {
    $this->value = static::find($chars);
  }

  public function type() {
    return (string)get_class($this);
  }

  public function value() {
    return $this->value;
  }

  // Strict substrings of other values MUST appear later in the list.
  // abstract protected static function values();
  // @see http://stackoverflow.com/a/31235907/375262, fixed in PHP 7.
}
