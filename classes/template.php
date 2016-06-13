<?php

class Template {
  public function __construct() {
    $loader = new Mustache_Loader_FilesystemLoader(__DIR__ . '/../views');
    $this->engine = new Mustache_Engine(['loader' => $loader]);
  }

  public function __call($name, $arguments) {
    return $this->render($name, $arguments[0] ?: []);
  }

  private function render($template, $vars = []) {
    return
      $this->renderHeader($vars)
      . $this->engine->render($template, $vars)
      . $this->renderFooter();
  }

  private function renderHeader($vars) {
    $args = [
      'homeUrl' => U('/'),
      'cssUrl' => U('/css/hs.css')
    ];
    $args = array_merge($args, $vars);
    return $this->engine->render('header', $args);
  }

  private function renderFooter() {
    $args = [
      'jsUrl' => U('/js/hs.js')
    ];
    return $this->engine->render('footer', $args);
  }
}
