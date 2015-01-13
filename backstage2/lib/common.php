<?php 
require_once ("cfg.php");
require_once ("shared2/autoload.php");

/* connect to the database */
db_connect ( $CFG->dbhost, $CFG->dbname, $CFG->dbuser, $CFG->dbpass );
mysql_set_charset('utf8');
session_start();
session_regenerate_id();

Settings::assign ($CFG);
Settings::importTable('app_configuration');

$CFG->libdir = "lib";
$CFG->img_dir = "images";
$CFG->self = basename($_SERVER['SCRIPT_FILENAME']);
$CFG->method_id = 0;
$CFG->backstage_mode = true;
$CFG->ajax = true;

$DB_DEBUG = ($CFG->db_debug == 'Y');
$DB_DIE_ON_FAIL = ($CFG->db_debug == 'Y');

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


/* Constants */
$CFG->request_widthdrawal_id = $CFG->request_withdrawal_id;
$CFG->form_email = $CFG->support_email;
$CFG->request_widthdrawal_id = $CFG->request_withdrawal_id;

User::logIn($_REQUEST['loginform']['user'],$_REQUEST['loginform']['pass']);
User::logOut($_REQUEST['logout']);
?>