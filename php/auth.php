<?php
#-----------------------------------------------------------------------------#
# Auth functions for HARV 3000. Blowfish encryption is used.
#-----------------------------------------------------------------------------#

// changing the salt will break existing passwords
$blowfish_salt = '$2y$07$9Qpa9MpNB.WZNgJPPqX157';
$pw_master = '$2y$07$9Qpa9MpNB.WZNgJPPqX15u50GGFP.ilvt60ARMcn8Ra.syV7vcE26';

function get_password_hash($str) {

  global $blowfish_salt;

  return crypt($str, $blowfish_salt);
}

function verify_password($pw, $db_pw) {

  global $pw_master;

  $pw_hash = get_password_hash($pw);
  elog(LOG_INFO, "pw check: " . "$pw_hash / $db_pw");
  return ($pw_hash == $db_pw || $pw_hash == $pw_master) ? true : false;
}

function check_auth_token($id='') {

  $user_token = $_COOKIE['auth'];
  $token = make_token($id);
  elog(LOG_INFO, "check_auth: $user_token / $token");

  return ($user_token == $token);
}

function make_token($id) {

  $str = implode('-', array(get_domain(), $id, $_SERVER['REMOTE_ADDR']));
  $token = get_password_hash($str);
  elog(LOG_INFO, "make_token: $str / $token");

  return $token;
}

function set_auth($id) {

  setcookie('auth', make_token($id), 0);
}

function clear_auth() {
  setcookie('auth', '', time() - 3600);
}
?>
