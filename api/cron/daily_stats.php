#!/usr/bin/php
<?php

include '../cfg/cfg.php';

/* should run at the very start of every day */

// get total of each currency
foreach ($CFG->currencies as $currency) {
	$totals[] = 'SUM('.strtolower($currency['currency']).' * '.$currency['usd'].') AS '.strtolower($currency['currency']);
}
$sql = 'SELECT COUNT(id) AS total_users, SUM(btc) AS btc, '.implode(',',$totals).' FROM site_users';
$result = db_query_array($sql);
if ($result[0]) {
	$total_usd = 0;
	foreach ($result[0] as $currency => $amount) {
		if ($currency == 'total_users')
			$total_users = $amount;
		elseif ($currency == 'btc') 
			$total_btc = $amount;
		else
			$total_usd += $amount;
	}
	$btc_per_user = $total_btc / $total_users;
	$usd_per_user = $total_usd / $total_users;
}

// get open orders BTC
$sql = 'SELECT SUM(btc) AS btc FROM orders';
$result = db_query_array($sql);
$open_orders_btc = $result[0]['btc'];

// get total transactions for the day
$sql = 'SELECT SUM(btc) AS total_btc, AVG(transactions.btc) AS avg_btc, SUM(fee + fee1) AS total_fees FROM transactions WHERE DATE(`date`) = (CURDATE() - INTERVAL 1 DAY)';
$result = db_query_array($sql);
$transactions_btc = $result[0]['total_btc'];
$avg_transaction = $result[0]['avg_btc'];
$trans_per_user = $transactions_btc / $total_users;
$total_fees = $result[0]['total_fees'];
$fees_per_user = $total_fees / $total_users;

$sql = 'SELECT SUM(fee) AS fees_incurred FROM fees WHERE DATE(`date`) = (CURDATE() - INTERVAL 1 DAY)';
$result = db_query_array($sql);
$gross_profit = $total_fees - $result[0]['fees_incurred'];

db_insert('daily_reports',array('date'=>date('Y-m-d',strtotime('-1 day')),'total_btc'=>$total_btc,'total_fiat_usd'=>$total_usd,'btc_per_user'=>$btc_per_user,'usd_per_user'=>$usd_per_user,'open_orders_btc'=>$open_orders_btc,'transactions_btc'=>$transactions_btc,'avg_transaction_size_btc'=>$avg_transaction,'transaction_volume_per_user'=>$trans_per_user,'total_fees_btc'=>$total_fees,'fees_per_user_btc'=>$fees_per_user,'gross_profit_btc'=>$gross_profit));
