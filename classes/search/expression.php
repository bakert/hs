<?php

class Expression {
  public function __construct(array $tokens) {
    $this->tokens = $tokens;
  }

  public function tokens() {
    return $this->tokens;
  }

  public function type() {
    return 'Expression';
  }
}
