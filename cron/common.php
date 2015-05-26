<?php 
require_once ("cfg.php");
require_once ("lib/dblib.php");
require_once ("lib/stdlib.php");
require_once ("lib/DB.php");
require_once ("lib/BitcoinAddresses.php");
require_once ("lib/easybitcoin.php");
require_once ("lib/Orders.php");
require_once ("lib/Status.php");
require_once ("lib/Currencies.php");
require_once ("lib/User.php");
require_once ("lib/Settings.php");
require_once ("lib/SiteEmail.php");
require_once ("lib/Email.php");
require_once ("lib/FeeSchedule.php");
require_once ("lib/Lang.php");

/* Connect to the database */
db_connect($CFG->dbhost,$CFG->dbname,$CFG->dbuser,$CFG->dbpass);

/* Currencies */
$CFG->currencies = Currencies::get();

/* Load settings and timezone */
Settings::assign();
date_default_timezone_set($CFG->default_timezone);
$dtz = new DateTimeZone($CFG->default_timezone);
$dtz1 = new DateTime('now', $dtz);
$CFG->timezone_offset = $dtz->getOffset($dtz1);

/* Current URL */
$CFG->self = basename($_SERVER['SCRIPT_FILENAME']);
$CFG->cross_currency_trades = ($CFG->cross_currency_trades == 'Y');
$CFG->currency_conversion_fee = $CFG->currency_conversion_fee * 0.01;
$CFG->form_email = $CFG->support_email;
$CFG->request_widthdrawal_id = $CFG->request_withdrawal_id;
$CFG->bitcoin_reserve_ratio = $CFG->bitcoin_reserve_ratio * 0.01;


/* Wait for other cron jobs and create lock file when ready */
$start_time = time();
$lock_file = $CFG->dirroot.'lock/lock';
$lock_file_info = false;
$last_notify = $start_time;

require_once ("lib/shutdown.php");

if ($CFG->self != 'get_stats.php') {
	while (file_exists($lock_file)) {
		sleep(1);
		if (time() - $last_notify >= 60) {
			$lock_file_info = explode('|',file_get_contents($lock_file));
			$last_notify = time();
			
			$content = 'Script '.$CFG->self.' has been waiting for'.(time() - $start_time).'s for '.$lock_file_info[0].' ('.(time() - $lock_file_info[1]).'s) to complete.';
			//Email::send($CFG->support_email,$CFG->support_email,'Cron Bottleneck @'.$CFG->self,$CFG->exchange_name.' Cron',false,$content);
			trigger_error($content,E_USER_WARNING);
		}
	}
	
	$created = file_put_contents($lock_file,$CFG->self.'|'.$start_time);
	if ($created === false) {
		trigger_error('Cannot access lock file. Check "lock" directory permissions.',E_USER_ERROR);
		exit;
	}
}
?>