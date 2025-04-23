<?php

require_once('log.php');

function is_present($param) {
  $value = get_input($param, 'get');
  return isset($value);
}

// help pass along certain args in a URL
function propagate_params($first = false, $params = array('test', 'log')) {
  $present = array_filter($params, 'is_present');
  $start = $first ? '?' : '&';
  if (count($present) > 0) {
    return $start . implode('&', $present);
  }
  return '';
}

function is_valid_email($email) {
  return preg_match('/^[!-;A-Z_a-~]+\@([0-9a-z][\w\-]+\.)+[a-z]{2,6}$/', $email);
}

// Return a formatted dollar amount. If the amount is zero, return the given default string. If the
// default is set to 0, then "$0.00" will be returned.
function formatMoney($amount, $none='') {
  return ($amount != 0 || $none === 0) ? '$' . number_format((float)$amount, 2, '.', '') : $none;
}

// Returns a date formatted like: Feb 13, 2017
function formatDate($date) {
  return date("M j, Y", strtotime($date));
}

// puts text in single quotes
function quote($text) {
  return $text === '' ? "''" : "\"" . $text . "\"";
}

function array_union($a, $b) {

  return array_merge(array_intersect($a, $b), array_diff($a, $b), array_diff($b, $a));
}

// returns the first word in the current URL
function get_domain() {

  $parts = explode('/', $_SERVER['PHP_SELF']);
  return $parts[1];
}

function clip($str, $from, $to, $clip_to=true, $offset=0) {

  $start = strpos($str, $from, $offset);
  $end = strpos($str, $to);
  $extra = $clip_to ? strlen($to) : 0;
  $str = substr_replace($str, '', $start, $end - $start + $extra);

  return $str;
}

function by_name($a, $b) {

  if (!$a || !$b) {
    return $a == $b ? 0 : !$a ? -1 : 1;
  }

  if (strpos($a, '/') !== false) {
    $members = preg_split("/\s*\/\s*/", $a);
    $a = $members[0];
  }
  if (strpos($b, '/') !== false) {
    $members = preg_split("/\s*\/\s*/", $b);
    $b = $members[0];
  }

  $aIdx = getNameSplitIndex($a);
  $aLast = $aIdx != -1 ? substr($a, $aIdx + 1) : $a;
  $aFirst = $aIdx != -1 ? substr($a, 0, $aIdx) : '';
  $bIdx = getNameSplitIndex($b);
  $bLast = $bIdx != -1 ? substr($b, $bIdx + 1) : $b;
  $bFirst = $bIdx != -1 ? substr($b, 0, $bIdx) : '';

  return $aLast != $bLast ? strcmp($aLast, $bLast) : strcmp($aFirst, $bFirst);

}

function getNameSplitIndex($name) {

  $name = strtolower($name);
  if (preg_match("/\s+(van|von|de|da)\s+/", $name, $matches, PREG_OFFSET_CAPTURE)) {
    return $matches[0][1];
  }

  if (preg_match("/(,? jr| sr| ii| iii)$/", $name, $matches, PREG_OFFSET_CAPTURE)) {
    return strrpos($name, ' ', $matches[0][1]);
  }
  else {
    return strrpos($name, ' ');
  }
}

function hasHtml($text) {

  return $text != strip_tags($text);
}

?>
