<?php

class Config {
  public function __construct() {
    $raw_config = file_get_contents(__DIR__ . '/../config.json');
    if (!$raw_config) {
      throw new FileNotFoundException('Unable to load config.');
    }
    $vars = json_decode($raw_config, true /* as array */);
    $this->vars = [];
    foreach ($vars as $k => $v) {
      $f = function($match) { return mb_strtoupper($match{1}); };
      $key = preg_replace_callback('/\\.([a-z])/', $f, $k);
      $this->vars[$key] = $v;
    }
    if (!$this->vars) {
      throw new UnexpectedValueException('Unable to parse config.');
    }
  }

  public function __call($name, $arguments) {
    if (isset($this->vars[$name])) {
      return $this->vars[$name];
    }
    if (isset($arguments[0])) {
      return $arguments[0];
    }
    return null;
  }
}
