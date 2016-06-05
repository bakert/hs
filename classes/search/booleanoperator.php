<?php

class BooleanOperator extends Token {
  // Strict substrings of other operators must appear later in the list.
  protected static function values() {
    return ['AND', 'OR', 'NOT'];
  }

  public function value($existingWhere = false) {
    if ($this->value === 'NOT' && $existingWhere) {
      return 'AND NOT';
    } else {
      return $this->value;
    }
  }
}
