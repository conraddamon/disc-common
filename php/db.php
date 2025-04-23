<?php
#-----------------------------------------------------------------------------#
# File: db.php
# Author: Conrad Damon
# Date: 1/27/04
#
# This file provides connectivity from PHP to a database, using mysqli.
# Including this library creates a connection to the database
# which is stored in a global $db variable, so there's no real need to call
# db_connect() explicitly.
#-----------------------------------------------------------------------------#

require_once('log.php');

# Connect to the database using mysqli. That is done as part of
# loading this file, so this function should not have to be called 
# explicitly.
function db_connect($dbname='db') {
  
    global $conf, $db;

    if ($db) {
	return $db;
    }

    $db = new mysqli("localhost", "cdamon", "I8YorC&y!", $conf[$dbname]['name']);
    if ($db -> connect_errno) {
      echo "Failed to connect to MySQL: " . $db -> connect_error;
      exit();
    }
    return $db;
}

$dbname = (preg_match('|^/trivia/|', $_SERVER['PHP_SELF'])) ? 'trivia_db' : 'db';
if (preg_match('|^/ddc/|', $_SERVER['PHP_SELF']) || preg_match('|^doubledisccourt.com|', $_SERVER['SERVER_NAME'])) {
  $dbname = 'ddc_db';
}
else if (preg_match('|^/dgw/|', $_SERVER['PHP_SELF'])) {
  $dbname = 'dgw_db';
}
else if (preg_match('|^/overall/|', $_SERVER['PHP_SELF']) || preg_match('|^overalldisc.com|', $_SERVER['SERVER_NAME'])) {
  $dbname = 'overall_db';
}
# next line is for DB calls from JS
elseif (preg_match('/^\/data\/(\w+)\.php/', $_SERVER['PHP_SELF'], $m)) {
  $dbname = $m[1] . '_db';
}
if (isset($_GET['test'])) {
  $dbname .= '_test';
}
plog("DB: $dbname");
if ($dbname == 'db') {
   error_log("DB: $dbname");
   error_log("URL: " . $_SERVER['PHP_SELF']);
}

$_db = db_connect($dbname);

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

  plog($sql);

  $count = strstr($options, 'count');
  $one = strstr($options, 'one');
  
  $q = $_db->query($sql);
  if (!$q) {
    error_log('Query error: ' . $_db->error);
    return;
  }

  if (preg_match('/^\s*INSERT\s+/i', $sql)) {
    return $_db->insert_id;
  }
  if (preg_match('/^\s*DELETE\s+/i', $sql)) {
    return $_db->affected_rows;
  }
  if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
    return;
  }

  plog("Num rows: " . $q->num_rows);
  if ($count) {
    $row = $q->fetch_row();
    return $row[0];
  }

  while (null !== ($row = $q->fetch_assoc())) {
    $results[] = $row;
  }
  return $one ? $results[0] : $results;

  while ($row = $q->fetchRow(DB_FETCHMODE_ASSOC)) {
    if (DB::iserror($row)) {
      plog('Query result error: ' . $row->getMessage());
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

    return "'$value'";
}

# Convert data to UTF8 so that json_encode() doesn't choke on it
function db_encode($value) {

  if (is_array($value)) {
    return (array_map('db_encode', $value));
  }

  return mb_check_encoding($value,"UTF-8") == true ? $value : utf8_encode((string) $value);
}
?>
