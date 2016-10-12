<?php

class Text extends Token {
  public function __construct($string) {
    $this->value = $string;
  }
}
