<?php

require_once(__DIR__ . '/hs-www.php');

class HearthstoneTextSearch {
  public function go() {
    $q = isset($_GET['q']) ? $_GET['q'] : '';
    $args['q'] = $q;
    $args['results'] = $this->search($q);
    $args['numResults'] = count($args['results']);
    $args['pluralResults'] = count($args['results']) === 1 ? '' : 's';
    $args['urlPrefix'] = U('');
    $args['attributes'] = [];
    foreach (array_reverse(Attribute::attributes()) as $attr) {
      list($humanize, $numeric, $textual) = [true, false, false];
      if (in_array($attr->keys()[0], ['overload', 'durability', 'attack', 'health', 'cost', 'spelldamage'])) {
        $numeric = true;
        $options = [];
      } elseif (in_array($attr->keys()[0], ['id', 'text', 'artist', 'name', 'flavor'])) {
        $textual = true;
        $options = [];
      } elseif (in_array($attr->keys()[0], ['playable', 'class'])) {
        $sql = "SELECT DISTINCT player_class FROM card WHERE player_class <> 'Dream' ORDER BY player_class";
        $options = D()->values($sql);
      } elseif ($attr->dbName() === 'collectible') {
        $options = ['YES', 'NO', 'BOTH'];
      } elseif ($attr->keys()[0] === 'rarity') {
        $options = ['COMMON', 'RARE', 'EPIC', 'LEGENDARY'];
      } elseif ($attr->dbName()) {
        $sql = 'SELECT DISTINCT ' . $attr->dbName() . ' FROM card ORDER BY ' . $attr->dbName();
        $options = D()->values($sql);
      } elseif ($attr->keys()[0] === 'format') {
        $sql = 'SELECT name FROM format ORDER BY name';
        $options = D()->values($sql);
      } elseif ($attr->keys()[0] === 'set') {
        $options = ['Basic', 'Classic', 'Naxx', 'GVG', 'Blackrock', 'TGT', 'League', 'WOTOG'];
        $humanize = false;
      }
      if (!in_array($attr->keys()[0], ['eg', 'earn', 'arrow'])) {
        if ($humanize) {
          $options = array_map([$this, 'humanize'], $options);
        }
        $args['attributes'][] = [
          'name' => $attr->keys()[0],
          'numeric' => $numeric,
          'textual' => $textual,
          'options' => array_values(array_filter($options)),
        ];
      }
    }
    return T()->index($args);
  }

  private function search($q) {
    $search = new Search($q);
    $where = $search->whereClause();
    if ($where === null || trim($q) === '') {
      return [];
    }
    $sql = 'SELECT * '
      . 'FROM card AS c '
      . 'INNER JOIN card_set AS cs ON c.id = cs.card_id '
      . 'WHERE ' . $where
      . " ORDER BY IF(player_class = '', 'Z_NEUTRAL', player_class), cost, name";
    try {
      $cards = D()->execute($sql);
    } catch (DatabaseException $e) {
      return [];
    }
    $results = [];
    foreach ($cards as $card) {
      $results[] = [
        'imgHeight' => 465,
        'imgWidth' => 307,
        'name' => $card['name'],
        'systemId' => $card['system_id'],
      ];
    }
    return $results;
  }

  private function humanize($s) {
    return mb_convert_case(mb_ereg_replace('_', ' ', $s), MB_CASE_TITLE);
  }
}

echo (new HearthstoneTextSearch())->go();
