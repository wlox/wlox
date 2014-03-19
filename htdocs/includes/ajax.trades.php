<?php
chdir('..');
include '../cfg/cfg.php';

$currency1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['currency']);
$notrades = $_REQUEST['notrades'];
$limit_7 = $_REQUEST['get10'];
$user = ($_REQUEST['user']) ? ' AND site_user = '.User::$info['id'].' ' : '';
$currency_info = $CFG->currencies[strtoupper($currency1)];

if ($limit_7)
	$limit = " LIMIT 0,10";
elseif (!$notrades)
	$limit = " LIMIT 0,5 ";

if (!$notrades) {
	$sql = "
			SELECT transactions.id AS id, transactions.btc AS btc, transactions.btc_price AS btc_price, currencies.fa_symbol AS fa_symbol, (UNIX_TIMESTAMP(transactions.date) * 1000) AS time_since 
			FROM transactions 
			LEFT JOIN currencies ON (currencies.id = transactions.currency) 
			WHERE 1 
			AND transactions_currency = {$currency_info['id']} 
			AND (transactions.transaction_type = $CFG->transactions_buy_id OR transactions.transaction_type1 = $CFG->transactions_buy_id) 
			ORDER BY transactions.date DESC $limit ";
	$result = db_query_array($sql);
	$return['transactions'][] = $result;
}
$sql = "
		SELECT orders.id AS id, orders.btc AS btc, orders.btc_price AS btc_price, orders.order_type AS type, currencies.fa_symbol AS fa_symbol
		FROM orders
		LEFT JOIN currencies ON (currencies.id = orders.currency)
		WHERE 1
		AND orders.currency = {$currency_info['id']}
		AND orders.order_type = $CFG->order_type_bid 
		$user
		ORDER BY orders.btc_price DESC $limit ";
$result = db_query_array($sql);
$return['bids'][] = $result;

$sql = "
		SELECT orders.id AS id, orders.btc AS btc, orders.btc_price AS btc_price, orders.order_type AS type, currencies.fa_symbol AS fa_symbol
		FROM orders
		LEFT JOIN currencies ON (currencies.id = orders.currency)
		WHERE 1
		AND orders.currency = {$currency_info['id']}
		AND orders.order_type = $CFG->order_type_ask 
		$user
		ORDER BY orders.btc_price ASC $limit ";
$result = db_query_array($sql);
$return['asks'][] = $result;

if (!$notrades) {
	$sql = "SELECT SUM(btc) AS total_btc_traded FROM transactions WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) ORDER BY `date` ASC LIMIT 0,1";
	$result4 = db_query_array($sql);
	$return['btc_traded'] = $result4[0]['total_btc_traded'];
}

echo json_encode($return);