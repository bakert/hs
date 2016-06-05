<?php

require_once(__DIR__ . '/../../hs.php');

/**
 * Search query parser for magiccards.info-like searching over Hearthstone cards.
 *
 * (t:minion AND race:beast) OR o:taunt
 *
 * Expression: class:paladin OR cost:2, o:trample
 * BooleanOperator: OR, AND, NOT
 * OpenParenthesis: (
 * CloseParenthesis: )
 * Key: t, class, race, cost, o
 * Operator: !, :, >, <, =, >=, <=
 *
 * Does not support ! operator for criteria (should AND parts together instead of OR) ... class!paladin
 */
class Search {

  const EXPECT_OPERATOR = 'expect_operator';
  const EXPECT_TERM = 'expect_term';
  const EXPECT_EXPRESSION = 'expect_expression';
  const QUOTED_STRING = 'quoted_string';
  const UNQUOTED_STRING = 'unquoted_string';

  private $whereClause;

  public function __construct($s) {
    $this->s = $s;
  }

  public function whereClause() {
    if (!$this->whereClause) {
      try {
        $this->whereClause = $this->parse($this->tokenize($this->s));
        // Bit of a hack but we do want this by default.
        if (mb_strpos($this->whereClause, 'collectible') === false) {
          $this->whereClause = '(' . $this->whereClause . ') AND (collectible)';
        }
      } catch (QueryException $e) {
        $this->whereClause = null;
      }
    }
    return $this->whereClause;
  }

  private function parse($expression) {
    $where = '';
    $tokens = $expression->tokens();
    $activeBooleanOperator = '';
    for ($i = 0; $i < count($tokens); $i++) {
      $token = $tokens[$i];
      if ($token->type() !== 'BooleanOperator') {
        $where .= " $activeBooleanOperator ";
      }
      if ($token->type() === 'String') {
        $where .= $this->where('name', '=', $token->value());
      } elseif ($token->type() === 'Key') {
        if (!isset($tokens[$i + 1]) || !isset($tokens[$i + 2])) {
            throw new ParseException("Insufficient tokens to complete key at $i");
        }
        $where .= $this->parseCriterion($token, $tokens[$i + 1], $tokens[$i + 2]);
        $i += 2;
      } elseif ($token->type() === 'BooleanOperator') {
        $activeBooleanOperator = $token->value(mb_strlen($where) > 0);
      } elseif ($token->type() === 'Expression') {
        $where .= '(' . $this->parse($token) . ')';
      } else {
        throw new ParseException("Invalid token: " . $token->type() . " at $i");
      }
      if (!$activeBooleanOperator) {
        $activeBooleanOperator = ' AND ';
      }
    }
    return trim($where);
  }

  private function parseCriterion($key, $operator, $term) {
    $attr = Attribute::fromKey($key->value());
    if ($attr === null) {
      throw new ParseException('Bad key: ' . $key->value());
    } elseif ($attr->type() === Key::TEXT) {
      return $this->where($attr->dbName(), $operator->value(), $term->value());
    } elseif ($attr->type() === Key::NUMBER) {
      return $this->mathWhere($attr->dbName(), $operator->value(), $term->value());
    } elseif ($attr->name() === 'collectible') {
      if (in_array($term->value(), ['both', 'either', '2'])) {
        return '(collectible IS NULL OR collectible IS NOT NULL)'; // Always true but avoids the later check for presence of 'collectible' in query.
      }
      return '(collectible IS ' . ($this->truthiness($term->value()) ? 'NOT ' : '') . 'NULL)';
    } elseif ($key->value() === 'set') {
      return '(set_id IN (SELECT set_id FROM set_name WHERE ' . $this->parseCriterion(new Key(str_split('name')), $operator, $term) . '))';
    } elseif ($attr->name() === 'playable') {
      return ('(player_class IS NULL OR ' . $this->parseCriterion(new Key(str_split('class')), $operator, $term)) . ')';
    } elseif ($attr->name() === 'format') {
      return ('`set` IN (SELECT `set` FROM format_set WHERE format_id IN (SELECT id FROM format WHERE ' . $this->where('name', $operator->value(), $term->value()) . '))');
    } else {
      throw new ParseException('Unrecognized key: `' . $key->value() . '` in parseCriterion.');
    }
  }

  private function tokenize($s) {
    $depth = 0;
    $chars = preg_split('//', $s);
    $chars[count($chars) - 1] = ' ';
    $mode = static::EXPECT_EXPRESSION;
    $tokens = [0 => []];
    for ($i = 1; $i < count($chars); $i++) {
      $c = $chars[$i];
      $rest = array_slice($chars, $i);
      if ($mode === static::EXPECT_EXPRESSION) {
        if ($c === '(') {
          $depth += 1;
          $tokens[$depth] = [];
        } elseif ($c === ')') {
          $expression = new Expression($tokens[$depth]);
          unset($tokens[$depth]);
          $depth -= 1;
          $tokens[$depth][] = $expression;
        } elseif (Criterion::is($rest)) {
          $tokens[$depth][] = new Key($rest);
          $mode = static::EXPECT_OPERATOR;
          $i += Key::length($rest) - 1;
        } elseif (BooleanOperator::is($rest)) {
          $tokens[$depth][] = new BooleanOperator($rest);
          $mode = static::EXPECT_EXPRESSION;
          $i += BooleanOperator::length($rest) - 1;
        } elseif ($c === '"') {
          $string = [];
          $mode = static::QUOTED_STRING;
        } elseif ($c === ' ') {
          // noop
        } elseif (preg_match('/[A-Za-z0-9]/', $c)) {
          $string = [$c];
          $mode = static::UNQUOTED_STRING;
        } else {
          throw new TokenizeException("Invalid input '$c' at $i");
        }
      } elseif ($mode === static::EXPECT_OPERATOR) {
        if (Operator::is($rest)) {
          $tokens[$depth][] = new Operator($rest);
          $mode = static::EXPECT_TERM;
          $i += Operator::length($rest) - 1;
        } else {
          throw new TokenizeException("Expected Operator got '$c' at $i");
        }
      } elseif ($mode === static::EXPECT_TERM) {
        if ($c === '"') {
          $string = '';
          $mode = static::QUOTED_STRING;
        } else {
          $string = [$c];
          $mode = static::UNQUOTED_STRING;
        }
      } elseif ($mode === static::QUOTED_STRING) {
        if ($c === '"') {
          $tokens[$depth][] = new String(implode($string));
          $mode = static::EXPECT_EXPRESSION;
        } else {
          $string[] = $c;
        }
      } elseif ($mode === static::UNQUOTED_STRING) {
        if ($c === ' ') {
          $tokens[$depth][] = new String(implode($string));
          $mode = static::EXPECT_EXPRESSION;
        } elseif ($c === ')') {
          $tokens[$depth][] = new String(implode($string));
          $mode = static::EXPECT_EXPRESSION;
          $i -= 1;
        } else {
          $string[] = $c;
        }
      } else {
        throw new TokenizeException("Bad mode: $mode");
      }
    }
    return new Expression(isset($tokens[0]) ? $tokens[0] : []);
  }

  private function where($column, $operator, $term, $exactMatch = false) {
    $q = $exactMatch ? $term : '%' . $term . '%';
    $subsequent = false;
    $where = "($column ";
    if ($operator === '!') {
      $where .= 'NOT ';
    }
    $where .= 'LIKE ' . D()->quote($q) . ')';
    return $where;
  }

  private function mathWhere($column, $operator, $term) {
    if (!in_array($operator, ['>', '<', '=', '<=', '>='])) {
        return 'FALSE';
    }
    return "($column IS NOT NULL AND $column <> '' AND $column $operator " . D()->quote($term) . ")";
  }

  private function truthiness($v) {
    if (in_array(mb_strtolower($v), ['', '0', 'n', 'no'])) {
      return false;
    } else {
      return (boolean)$v;
    }
  }
}
