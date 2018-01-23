<?php
#-----------------------------------------------------------------------------#
# File: log.php
# Author: Conrad Damon
# Date: 6/30/02
# 
# PHP logging routines
#
# Public functions:
#
#   elog($level, $text, $file='', $line='', $extra='')
#     
#      level = log level, one of LOG_ERR, LOG_WARNING, LOG_INFO, LOG_DEBUG,
#                                LOG_DEBUG1, LOG_DEBUG2
#      text  = message to log
#      file  = file name (optional)
#      line  = line number (optional)
#      extra = header for array (optional)
#
#      Log a message to the webserver error log. If the level of the message is
#      less urgent than the configured log level, nothing will be logged. If it
#      is more urgent than the configured admin log level, mail will be sent
#      to notify the webmaster.
#-----------------------------------------------------------------------------#

# levels through LOG_DEBUG are already defined by the OS and can't 
# be changed. That's okay because order matters, numbers don't.
define('LOG_ERR', 3);
define('LOG_WARNING', 4);
define('LOG_INFO', 6);
define('LOG_DEBUG', 7);
define('LOG_DEBUG1', 8);
define('LOG_DEBUG2', 9);

# labels for the log levels
$log_level[LOG_ERR] = 'error';
$log_level[LOG_WARNING] = 'warning';
$log_level[LOG_INFO] = 'info';
$log_level[LOG_DEBUG] = 'debug';
$log_level[LOG_DEBUG1] = 'debug1';
$log_level[LOG_DEBUG2] = 'debug2';

# set constants based on configured log levels
# LOG_LEVEL controls what goes to the server's error log
# ADMIN_LOG_LEVEL controls what webmaster is notified about
while (list($level, $tag) = each($log_level)) {
  if ($tag == $conf['log']['level']) {
    define('LOG_LEVEL', $level);
  }
  if ($tag == $conf['log']['admin_level']) {
    define('ADMIN_LOG_LEVEL', $level);
  }
}

# main logging routine
# file and line can be passed with __FILE__ and __LINE__
function elog($level, $text, $file='', $line='') {

  global $log_level, $conf;

  # see if we need to log this message
  if ($level > LOG_LEVEL) return;

  # construct the message
  $msg = '[' . $log_level[$level] . '] ';
  $msg .= $text;
  if ($file) $msg .= "; $file, line $line";

  # log it to error log
  error_log($msg);

  # notify admin if level is severe enough
  if ($level <= ADMIN_LOG_LEVEL) {
    $to = $conf['log']['admin_email'];
    $subject = 'Jumbalaya webserver error';
    $date = date('D M j G:i:s T Y');
    $pid = getmypid();
    $text = "[$date] [$pid] $msg";
    mail($to, $subject, $text);
  }
}

# Pretty-print a var to the error log.
function show_var($var, $name="", $level=LOG_DEBUG) {

  ob_start();
  if ($name) {
    print "$name: ";
  }
  var_dump($var);
  $x = ob_get_contents();
  ob_end_clean();
  $x = preg_replace("/=>\n/", " => ", $x);
  elog($level, $x);
}

# Pretty-print a var to the browser.
function show_me($var, $intro="") {
  echo $intro;
  echo("<pre>");
  var_dump($var);
  echo("</pre>");
}
?>
