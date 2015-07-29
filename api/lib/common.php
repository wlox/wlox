<?php 

include '../cfg/cfg.php';
include '../lib/dblib.php';
include '../lib/stdlib.php';
include '../lib/autoload.php';

/* Connect to DB */
db_connect($CFG->dbhost,$CFG->dbname,$CFG->dbuser,$CFG->dbpass);

// memcached check
$CFG->memcached = (class_exists('Memcached'));
$CFG->m = false;
if ($CFG->memcached) {
	$CFG->m = (class_exists('MemcachedFallback')) ? new MemcachedFallback() : new Memcached();
	$CFG->m->addServer('localhost', 11211);
}

/* Load settings and timezone */
Settings::assign();
date_default_timezone_set($CFG->default_timezone);
$dtz = new DateTimeZone($CFG->default_timezone);
$dtz1 = new DateTime('now', $dtz);
$CFG->timezone_offset = $dtz->getOffset($dtz1);

/* Get Currencies */
$CFG->currencies = Currencies::get();

/* Current URL */
$CFG->self = basename($_SERVER['SCRIPT_FILENAME']);
$CFG->cross_currency_trades = ($CFG->cross_currency_trades == 'Y');
$CFG->currency_conversion_fee = $CFG->currency_conversion_fee * 0.01;
$CFG->form_email = $CFG->support_email;
$CFG->request_widthdrawal_id = $CFG->request_withdrawal_id;

?>