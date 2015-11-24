#!/usr/bin/php
<?php
echo "Beginning Daily Stats Processing...".PHP_EOL;

include 'common.php';

/* should run at the very start of every day */

// compile historical data
$sql = "INSERT INTO historical_data (`date`,usd) (SELECT '".(date('Y-m-d',strtotime('-1 day')))."',(transactions.btc_price * currencies.usd_ask) AS btc_price FROM transactions LEFT JOIN currencies ON (transactions.currency = currencies.id) WHERE transactions.date <= (CURDATE()) ORDER BY transactions.date DESC LIMIT 0,1) ";
$result = db_query($sql);

// get total of each currency
$sql = 'SELECT COUNT(DISTINCT site_users.id) AS total_users, SUM(IF(site_users_balances.currency = '.$CFG->btc_currency_id.',site_users_balances.balance,0)) AS btc, SUM(IF(site_users_balances.currency != '.$CFG->btc_currency_id.',site_users_balances.balance * currencies.usd_ask,0)) AS usd FROM site_users LEFT JOIN site_users_balances ON (site_users.id = site_users_balances.site_user) LEFT JOIN currencies ON (currencies.id = site_users_balances.currency)';
$result = db_query_array($sql);
if ($result) {
	$total_users = $result[0]['total_users'];
	$total_btc = $result[0]['btc'];
	$total_usd = $result[0]['usd'];
	$btc_per_user = $total_btc / $total_users;
	$usd_per_user = $total_usd / $total_users;
}

// get open orders BTC
$sql = 'SELECT SUM(btc) AS btc FROM orders';
$result = db_query_array($sql);
$open_orders_btc = $result[0]['btc'];

// get total transactions for the day
$sql = 'SELECT SUM(transactions.btc) AS total_btc, AVG(transactions.btc) AS avg_btc, SUM((transactions.fee + transactions.fee1)  * transactions.btc_price * currencies.usd_ask) AS total_fees FROM transactions LEFT JOIN currencies ON (transactions.currency = currencies.id) WHERE DATE(transactions.date) = (CURDATE() - INTERVAL 1 DAY)';
$result = db_query_array($sql);
$transactions_btc = $result[0]['total_btc'];
$avg_transaction = $result[0]['avg_btc'];
$trans_per_user = $transactions_btc / $total_users;
$total_fees = $result[0]['total_fees'];
$fees_per_user = $total_fees / $total_users;

// get fees incurred from the Bitcoin network for internal movements
$sql = 'SELECT SUM(fees.fee*currencies.usd_ask) AS fees_incurred FROM fees LEFT JOIN currencies ON (currencies.id = 28) WHERE DATE(fees.date) = (CURDATE() - INTERVAL 1 DAY)';
$result = db_query_array($sql);
$gross_profit = $total_fees - $result[0]['fees_incurred'];

db_insert('daily_reports',array('date'=>date('Y-m-d',strtotime('-1 day')),'total_btc'=>$total_btc,'total_fiat_usd'=>$total_usd,'btc_per_user'=>$btc_per_user,'usd_per_user'=>$usd_per_user,'open_orders_btc'=>$open_orders_btc,'transactions_btc'=>$transactions_btc,'avg_transaction_size_btc'=>$avg_transaction,'transaction_volume_per_user'=>$trans_per_user,'total_fees_btc'=>$total_fees,'fees_per_user_btc'=>$fees_per_user,'gross_profit_btc'=>$gross_profit));

db_update('status',1,array('cron_daily_stats'=>date('Y-m-d H:i:s')));
echo 'done'.PHP_EOL;