<?php

require_once(__DIR__  . '/../../hs.php');

function tests() {
  assert(Key::is(['class']));
  assert(Key::is(['cl']));
  assert(Key::is(['f']));
  assert(!Key::is(['magic']));
  assert(!Key::is(['']));
  assert(!Key::is([' ']));
  assert(Criterion::is(['a', '>', '=', '2']));

  doTest("a OR b", "(name LIKE '%a%') OR (name LIKE '%b%') AND (collectible)");
  doTest('text:"taunt"', "(text LIKE '%taunt%') AND (collectible)");
  doTest('tension turtle', "(name LIKE '%tension%')  AND  (name LIKE '%turtle%') AND (collectible)");
  doTest('x OR (a OR (b AND c))', "(name LIKE '%x%') OR ((name LIKE '%a%') OR ((name LIKE '%b%') AND (name LIKE '%c%'))) AND (collectible)");
  doTest('class:r h>2', "(player_class LIKE '%r%')  AND  (health IS NOT NULL AND health <> '' AND health > '2') AND (collectible)");
  doTest('(class:paladin OR class:warrior)  "Beyond lies  the wub" text:battlecry', "((player_class LIKE '%paladin%') OR (player_class LIKE '%warrior%'))  AND  (name LIKE '%Beyond lies  the wub%')  AND  (text LIKE '%battlecry%') AND (collectible)");
  doTest('type:"mech beast"', "(type LIKE '%mech beast%') AND (collectible)");
  doTest('type:beast a<2', "(type LIKE '%beast%')  AND  (attack IS NOT NULL AND attack <> '' AND attack < '2') AND (collectible)");
  doTest('type:beast cost=10 text:charge', "(type LIKE '%beast%')  AND  (cost IS NOT NULL AND cost <> '' AND cost = '10')  AND  (text LIKE '%charge%') AND (collectible)");
  doTest('text:"damaged minion" OR (cost=1 AND (health>2 OR attack>2))', "(text LIKE '%damaged minion%') OR ((cost IS NOT NULL AND cost <> '' AND cost = '1') AND ((health IS NOT NULL AND health <> '' AND health > '2') OR (attack IS NOT NULL AND attack <> '' AND attack > '2'))) AND (collectible)");
  doTest('text:battlecry NOT text:taunt', "(text LIKE '%battlecry%') AND NOT (text LIKE '%taunt%') AND (collectible)");
  doTest('class:paladin OR (class:warlock AND NOT health>3)', "(player_class LIKE '%paladin%') OR ((player_class LIKE '%warlock%') AND NOT (health IS NOT NULL AND health <> '' AND health > '3')) AND (collectible)");
  doTest('f:wild (NOT f:standard) class:mage'), "");
  echo "\n";
}

function doTest($input, $expected) {
  $search = new Search($input);
  if ($search->whereClause() !== $expected) {
    echo "\nInput: $input\nExpected: $expected\nActual: " . $search->whereClause() . "\n";
  } else {
    echo ".";
  }
}

tests();
