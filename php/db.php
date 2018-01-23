<?php
#-----------------------------------------------------------------------------#
# File: db.php
# Author: Conrad Damon
# Date: 1/27/04
#
# This file provides connectivity from PHP to a database, using the 
# PEAR DB library. Including this library creates a connection to the database
# which is stored in a global $db variable, so there's no real need to call
# db_connect() explicitly.
#-----------------------------------------------------------------------------#

require_once('DB.php');
require_once('log.php');

# Connect to the database using the configured DSN. That is done as part of
# loading this file, so this function should not have to be called 
# explicitly.
function db_connect($dbname='db') {
  
    global $conf, $db;

    if ($db) {
	return $db;
    }
    $dsn = $conf[$dbname]['dsn'];
    $persist = $conf[$dbname]['persist'] ? TRUE : FALSE;
    $db = DB::connect($dsn, $persist);
    if (DB::iserror($db)) {
	elog(LOG_ERR, "Error connecting to $dsn: " . $db->getMessage());
    }

    return $db;
}

$dbname = (preg_match('|^/trivia/|', $_SERVER['PHP_SELF'])) ? 'trivia_db' : 'db';
if (preg_match('|^doubledisccourt.com|', $_SERVER['SERVER_NAME'])) {
  $dbname = 'ddc_db';
}
else if (preg_match('|^/netflix/|', $_SERVER['PHP_SELF'])) {
  $dbname = 'netflix_db';
}
else if (preg_match('|^/dgw/|', $_SERVER['PHP_SELF'])) {
  $dbname = 'dgw_db';
}
else if (preg_match('|^/overall/|', $_SERVER['PHP_SELF']) || preg_match('|^overalldisc.com|', $_SERVER['SERVER_NAME'])) {
  $dbname = 'overall_db';
}
elseif (preg_match('/^\/data\/(\w+)\.php/', $_SERVER['PHP_SELF'], $m)) {
  $dbname = $m[1] . '_db';
}
elog(LOG_INFO, "DB: $dbname");
$_db = db_connect($dbname);

#$db_no_writes = 'ddc_db';

# Generic query function that handles SELECT, INSERT, UPDATE and DELETE
# statements. For SELECTs, it checks for errors, frees the query, and returns
# an array of hashes with the matching rows. It takes the following options:
#
#      count - return the number that results from a COUNT being selected
#      one   - return only the first row
#
# For INSERTs, the last insert id is returned (all tables have a field called
# 'id' which is auto-increment). It's retrieved with a mysql API function - 
# we save doing another query but introduce a possible race condition.
function db_query($sql, $options='') {

  global $conf, $_db, $dbname, $db_no_writes;

  if ($dbname == 'dgw_db' || $dbname == 'overall_db' || $dbname == 'ddc_db') {
    elog(LOG_INFO, $sql);
    if ($db_no_writes == $dbname && !preg_match('/^\s*SELECT\s+/i', $sql)) {
      $msg =  'db writes disabled';
      return $one ? $msg : array($msg);
    }
  }

  $count = strstr($options, 'count');
  $one = strstr($options, 'one');
  if ($conf['log']['sql']) {
    $sql1 = preg_replace('/\s+/', ' ', $sql);
    elog(LOG_DEBUG, $sql1);
  }
  if ($conf['log']['profiling']) {
    require_once('Benchmark/Timer.php');
    $timer = new Benchmark_Timer;
    $timer->start();
  }
  $q = $_db->query($sql);
  if ($conf['log']['profiling']) {
    $timer->stop();
    $ms = intval(round($timer->timeElapsed(), 3) * 1000);
    elog(LOG_DEBUG, "Time elapsed in ms: $ms");
  }
  if (DB::iserror($q)) {
    elog(LOG_INFO, '***** Query error: ' . $q->getMessage() . "\n[$sql]");
    return '';
  }
  if (preg_match('/^\s*INSERT\s+/i', $sql)) {
    return mysql_insert_id();
  }
  if (preg_match('/^\s*DELETE\s+/i', $sql)) {
    return mysql_affected_rows();
  }
  if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
    return;
  }
  if ($count) {
    $row = $q->fetchRow(DB_FETCHMODE_ORDERED);
    $q->free();
    return $row[0];
  }
  while ($row = $q->fetchRow(DB_FETCHMODE_ASSOC)) {
    if (DB::iserror($row)) {
      elog(LOG_INFO, 'Query result error: ' . $row->getMessage());
      $q->free();
      return '';
    } else {
      if ($dbname == "movie_dbx") {
	$results[] = json_encode($row);
      }
      else {
	$results[] = $row;
      }
    }
  }
  $q->free();
  
  return $one ? $results[0] : $results;
}

# Quote a value so it doesn't break the SQL query.
function db_quote($value) {

    global $_db;

#    return str_replace("'", "''", $value);
    return $_db->quote($value);
}
?>
