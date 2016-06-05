<?php

class Operator extends Token {
  // Strict substrings of other operators must appear later in the list.
  protected static function values() {
   return ['<=', '>=', ':', '!', '<', '>', '='];
  }
}
