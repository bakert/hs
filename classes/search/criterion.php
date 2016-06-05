<?php

class Criterion {
  public static function is($chars) {
    if (!Key::is($chars)) {
      return false;
    }
    $chars = array_slice($chars, Key::length($chars));
    if (!Operator::is($chars)) {
      return false;
    }
    return count($chars) > 0;
  }
}
