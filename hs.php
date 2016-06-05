<?php

date_default_timezone_set('UTC');

require_once(__DIR__ . '/vendor/autoload.php');
spl_autoload_register([new Autoloader(), 'load']);

class Autoloader {
  public function load($name) {
    foreach (['', 'classes/', 'classes/exception/', 'classes/search/', 'www/'] as $dir) {
      $path = __DIR__ . '/' . $dir . mb_strtolower($name) . '.php';
      if (file_exists($path)) {
        require_once($path);
      }
    }
  }
}

function C() {
  return Singletons::C();
}

function D() {
  return Singletons::D();
}
