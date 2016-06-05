<?php

require_once(__DIR__ . '/../hs.php');

session_start();

function T() {
  return Singletons::T();
}

function U($path, $absolute = false, $querystring = []) {
  return Singletons::U()->u($path, $absolute, $querystring);
}
