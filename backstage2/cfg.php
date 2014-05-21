<?

class object {
}
$CFG = new object ( );

$CFG->dbhost = "localhost";
$CFG->dbname = "1btcxe";
$CFG->dbuser = "1btcxedb";
$CFG->dbpass = "KfRnwWsS8uTuXDHp";

$CFG->baseurl = "http://www.1btcxe.com/wlox/frontend/htdocs/";
$CFG->dirroot = "/var/www/wlox/frontend/htdocs/";
$CFG->libdir = "lib";
$CFG->img_dir = "images";
$CFG->self = basename($_SERVER['SCRIPT_FILENAME']);
$CFG->method_id = 0;

$DB_DEBUG = true;
$DB_DIE_ON_FAIL = true;

require_once ("shared2/autoload.php");

/* connect to the database */
db_connect ( $CFG->dbhost, $CFG->dbname, $CFG->dbuser, $CFG->dbpass );
session_start();
session_regenerate_id();
Settings::assign ( $CFG );

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
		

/* Constants */
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
$CFG->deposit_fiat_desc = 3;
$CFG->withdraw_fiat_desc = 1;
$CFG->withdraw_btc_desc = 2;
$CFG->default_fee_schedule_id = 1;

/* authy */
$CFG->authy_api_key = 'b218b2b72cb5ca05e90126b3643e44b8';

User::logIn($_REQUEST['loginform']['user'],$_REQUEST['loginform']['pass']);
User::logOut($_REQUEST['logout']);

?>