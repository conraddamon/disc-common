<?php
#-----------------------------------------------------------------------------#
# File: log.php
# Author: Conrad Damon
# Date: 6/30/02, rewritten in Sep 2024
#
# Log a message to the webserver PHP error log if there is a 'test' or 'log'
# query param.
#-----------------------------------------------------------------------------#

function plog($text) {
  if (isset($_GET['test']) || isset($_GET['log'])) {
    error_log($text);
  }
}

# Pretty-print a var to the error log.
function show_var($var, $name="") {

  ob_start();
  if ($name) {
    print "$name: ";
  }
  var_dump($var);
  $x = ob_get_contents();
  ob_end_clean();
  $x = preg_replace("/=>\n/", " => ", $x);
  plog($x);
}

# Pretty-print a var to the browser.
function show_me($var, $intro="") {
  echo $intro;
  echo("<pre>");
  var_dump($var);
  echo("</pre>");
}
?>
