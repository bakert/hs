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
    return T()->index($args);
  }

  private function search($q) {
    $search = new Search($q);
    $where = $search->whereClause();
    if ($where === null || trim($q) === '') {
      return [];
    }
    $sql = 'SELECT * FROM card AS c INNER JOIN card_set AS cs ON c.id = cs.card_id WHERE ' . $where . " ORDER BY IF(player_class = '', 'Z_NEUTRAL', player_class), cost, name";
    try {
      $cards = D()->execute($sql);
    } catch (DatabaseException $e) {
      return [];
    }
    $results = [];
    foreach ($cards as $card) {
      $results[] = ['systemId' => $card['system_id']];
    }
    return $results;
  }
}

echo (new HearthstoneTextSearch())->go();
