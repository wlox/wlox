<?php 
/* Load Libraries */
include '../cfg/cfg.php';
include 'stdlib.php';
include 'session.php';
include 'autoload.php';

/* HTTP Headers */
$hostname = (!empty($_SERVER["HTTP_HOST"])) ? $_SERVER["HTTP_HOST"] : $_SERVER["SERVER_NAME"];
if (!empty($hostname) && !stristr($hostname,'localhost')) {
	$hostname = str_ireplace('www.','',$hostname);
	
	if (strstr($hostname,':'))
		$hostname = substr($hostname,0,strpos($hostname,':'));
	
	$hostname_parts = explode('.',$hostname);
	$c = count($hostname_parts);

	if ($c > 2) {
		if ((strlen($hostname_parts[($c - 1)]) == 2 && strlen($hostname_parts[($c - 2)]) == 3) || (strlen($hostname_parts[($c - 1)]) == 2 && strlen($hostname_parts[($c - 2)]) == 2)) {
			if ($c > 3) {
				$hostname_parts = array_slice($hostname_parts,($c - 3));
				$hostname = implode('.',$hostname_parts);
			}
		}
		else {
			$hostname_parts = array_slice($hostname_parts,($c - 2));
			$hostname = implode('.',$hostname_parts);
		}
	}
	
	ini_set('session.cookie_domain','.'.$hostname);
}

if (!empty($_SERVER["HTTPS"]))
	ini_set('session.cookie_secure',1);

ini_set('session.cookie_httponly',1);
ini_set('session.cookie_path','/');
ini_set('expose_php','off');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Powered-By: WLOX');

/* Readonly Sessions */
if (empty($ajax)) {
	session_start();
	session_regenerate();
}
else {
	session_readonly();
}

/* Current File Name */
$CFG->self = basename($_SERVER['SCRIPT_FILENAME']);

/* Check for Email Auth */
if (!empty($_REQUEST['email_auth']) && !empty($_REQUEST['authcode'])) {
	$email_authcode = urlencode($_REQUEST['authcode']);
	$email_authcode_request = ($CFG->self == 'withdraw.php');
	User::logIn(false,false,$email_authcode,$email_authcode_request);
}

/* Common Info */
API::add('Lang','getTable');
API::add('Currencies','get');
API::add('User','verifyLogin');
API::add('Settings','get');
$query = API::send();

if (empty($ajax))
	API::apiUpdateNonce();

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
$lang = (!empty($_REQUEST['lang'])) ? preg_replace("/[^a-z]/", "",strtolower($_REQUEST['lang'])) : false;
if ($lang && in_array($lang,array('en','es','ru','zh')))  {
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
?>
