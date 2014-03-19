<?
error_reporting(E_PARSE | E_ERROR);
if (!empty($_GET['db'])) {
	setcookie("db", $_GET['db'],time()+2592000);
	setcookie("user", $_GET['user'],time()+2592000);
	setcookie("pass", $_GET['pass'],time()+2592000);
}

class object {
}
$CFG = new object ( );

$CFG->dbhost = "localhost";
$CFG->dbname = ($_GET['db']) ? $_GET['db'] : $_COOKIE['db'];
$CFG->dbuser = ($_GET['user']) ? $_GET['user'] : $_COOKIE['user'];
$CFG->dbpass = ($_GET['pass']) ? $_GET['pass'] : $_COOKIE['pass'];

$CFG->baseurl = "";
$CFG->dirroot = "";
$CFG->libdir = "lib";
$CFG->img_dir = "images";
$CFG->self = basename($_SERVER['SCRIPT_FILENAME']);
$CFG->method_id = 0;

$DB_DEBUG = true;
$DB_DIE_ON_FAIL = true;

require_once ("../shared2/autoload.php");

/* connect to the database */
if (!empty($CFG->dbname)) {
	db_connect ( $CFG->dbhost, $CFG->dbname, $CFG->dbuser, $CFG->dbpass );

	$ses_class = new Session ( );
	session_set_save_handler ( array (&$ses_class, '_open' ), array (&$ses_class, '_close' ), array (&$ses_class, '_read' ), array (&$ses_class, '_write' ), array (&$ses_class, '_destroy' ), array (&$ses_class, '_gc' ) );
	session_start ();
	
	Settings::assign ( $CFG );
}

/* url options */
$CFG->backstage_mode = true;

/* turn on AJAX */
$CFG->ajax = true;

/* header vars */
$CFG->default_meta_desc = 'Backstage2';
$CFG->default_meta_keywords = 'Flexible management program.';
$CFG->default_meta_author = 'Organic Technologies';
$CFG->default_title = 'Organic Technologies';

/* permission selector */
$CFG->permissions = array(
	2 => 'Edit',
	1 => 'View',
	0 => 'No Access');
		

?>