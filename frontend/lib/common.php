<?php 
/* Load Libraries */
include '../cfg/cfg.php';
include 'dblib.php';
include 'stdlib.php';
include 'session.php';
include 'autoload.php';

session_start();
session_regenerate_id(true);

/* Common Info */
API::add('Lang','getTable');
API::add('Currencies','get');
API::add('User','verifyLogin');
API::add('Settings','get');
$query = API::send();

/* Assign Settings To CFG */
Settings::assign($query['Settings']['get']['results'][0]);

/* Check Login */
User::verifyLogIn($query);
User::logOut(isset($_REQUEST['log_out']));

/* Set Timezone */
date_default_timezone_set($CFG->default_timezone);
$dtz = new DateTimeZone($CFG->default_timezone);
$dtz1 = new DateTime('now', $dtz);
$CFG->timezone_offset = $dtz->getOffset($dtz1);

/* Detect Language */
$CFG->lang_table = $query['Lang']['getTable']['results'][0];
$lang = (!empty($_REQUEST['lang'])) ? preg_replace("/[^a-z]/", "",$_REQUEST['lang']) : false;
if ($lang)  {
	$CFG->language = $lang;
	$_SESSION['language'] = $lang;
	if (User::isLoggedIn())
		API::add('User','setLang',array($lang));
}
elseif (!empty($_SESSION['language']))
	$CFG->language = $_SESSION['language'];
elseif (empty($_SESSION['language'])) {
	$_SESSION['language'] = 'en';
	$CFG->language = 'en';
}

/* Get Currencies */
$CFG->currencies = $query['Currencies']['get']['results'][0];

/* Current File Name */
$CFG->self = basename($_SERVER['SCRIPT_FILENAME']);
?>