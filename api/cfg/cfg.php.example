<?php
class object {
}

$CFG = new object ( );

$CFG->dbhost = "";
$CFG->dbname = "";
$CFG->dbuser = "";
$CFG->dbpass = "";

$CFG->dirroot = "";
$CFG->libdir = "../lib/";
$CFG->self = basename($_SERVER['SCRIPT_FILENAME']);

require_once ("../shared2/autoload.php");

/* debugging */
$DB_DEBUG = true;
$DB_DIE_ON_FAIL = true;

db_connect($CFG->dbhost,$CFG->dbname,$CFG->dbuser,$CFG->dbpass);

/* Load settings and timezone */
Settings::assign ($CFG);
date_default_timezone_set($CFG->default_timezone);
$dtz = new DateTimeZone($CFG->default_timezone);
$dtz1 = new DateTime('now', $dtz);
$CFG->timezone_offset = $dtz->getOffset($dtz1);
$CFG->pass_regex = '/[\p{L}!@#$%&*?+-_.=| ]{8,}/';

/* Currencies */
$CFG->currencies = Currencies::get();

/* Constants */
$CFG->exchange_name = 'WLOX';
$CFG->btc_currency_id = 28;
$CFG->order_type_bid = 1;
$CFG->order_type_ask = 2;
$CFG->transactions_buy_id = 1;
$CFG->transactions_sell_id = 2;
$CFG->request_widthdrawal_id = 1;
$CFG->request_pending_id = 1;
$CFG->request_deposit_id = 2;
$CFG->request_awaiting_id = 4;
$CFG->request_withdrawal_id = 1;
$CFG->request_pending_id = 1;
$CFG->request_completed_id = 2;
$CFG->request_cancelled_id = 3;
$CFG->deposit_bitcoin_desc = 4;
$CFG->withdraw_fiat_desc = 1;
$CFG->withdraw_btc_desc = 2;
$CFG->default_fee_schedule_id = 1;
$CFG->req_img = '<em>*</em>';

/* Bitcoin */
$CFG->bitcoin_username = '';
$CFG->bitcoin_accountname = '';
$CFG->bitcoin_passphrase = '';
$CFG->bitcoin_host = 'localhost';
$CFG->bitcoin_port = 8332;
$CFG->bitcoin_protocol = 'http';
$CFG->bitcoin_reserve_ratio = 0.1;
$CFG->bitcoin_reserve_min = 1;
$CFG->bitcoin_directory = '';
$CFG->bitcoin_sending_fee = 0.0001;
$CFG->bitcoin_warm_wallet_address = '';

/* Emails */
$CFG->accounts_email = '';
$CFG->support_email = '';

/* API Keys */
$CFG->quandl_api_key = '';
$CFG->authy_api_key = '';


?>