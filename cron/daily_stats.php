#!/usr/bin/php
<?php
echo "Beginning Daily Stats Processing...".PHP_EOL;

include 'cfg.php';

/* should run at the very start of every day */

//create currency conversion ledger
$sql = 'INSERT INTO conversions (currency,currency1,amount,amount_needed,`date`) (SELECT IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', currency, currency1) AS currency, IF( transactions.transaction_type = '.$CFG->transactions_buy_id.', currency1, currency) AS currency1, SUM(IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', transactions.btc_price * transactions.btc, transactions.orig_btc_price * transactions.btc )) AS amount, SUM(IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', transactions.orig_btc_price * transactions.btc, transactions.btc_price * transactions.btc)) AS amount_needed, CURDATE() FROM transactions WHERE DATE(transactions.date) = (CURDATE() - INTERVAL 1 DAY) AND conversion = \'Y\' GROUP BY CONCAT(IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', currency1, currency) , \'-\', IF( transactions.transaction_type = '.$CFG->transactions_buy_id.', currency, currency1)))';
db_query($sql);

// get total of each currency
$currencies = Currencies::get();
foreach ($currencies as $currency) {
	$totals[] = 'SUM('.strtolower($currency['currency']).' * '.$currency['usd_ask'].') AS '.strtolower($currency['currency']);
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
$sql = 'SELECT SUM(transactions.btc) AS total_btc, AVG(transactions.btc) AS avg_btc, SUM((transactions.fee + transactions.fee1)  * transactions.btc_price * currencies.usd_ask) AS total_fees FROM transactions LEFT JOIN currencies ON (transactions.currency = currencies.id) WHERE DATE(transactions.date) = (CURDATE() - INTERVAL 1 DAY)';
$result = db_query_array($sql);
$transactions_btc = $result[0]['total_btc'];
$avg_transaction = $result[0]['avg_btc'];
$trans_per_user = $transactions_btc / $total_users;
$total_fees = $result[0]['total_fees'];
$fees_per_user = $total_fees / $total_users;

// get currency conversion commision in usd
$sql = 'SELECT SUM(IFNULL(conversions.amount_received - conversions.amount_needed,0) * currencies.usd_ask) AS conversion_fees FROM conversions LEFT JOIN currencies ON (currencies.id = conversions.currency1) WHERE conversions.is_active = \'Y\' AND factored = 0';
$result = db_query_array($sql);
$conversion_fees = $result[0]['conversion_fees'];
// set conversion ledger to not be crawled again
$sql = 'UPDATE conversions SET factored = 1 WHERE conversions.is_active = \'Y\'';
db_query($sql);

// get fees incurred from the Bitcoin network for internal movements
$sql = 'SELECT SUM(fees.fee*currencies.usd_ask) AS fees_incurred FROM fees LEFT JOIN currencies ON (currencies.id = 28) WHERE DATE(fees.date) = (CURDATE() - INTERVAL 1 DAY)';
$result = db_query_array($sql);
$gross_profit = $total_fees - $result[0]['fees_incurred'] + $conversion_fees;

db_insert('daily_reports',array('date'=>date('Y-m-d',strtotime('-1 day')),'total_btc'=>$total_btc,'total_fiat_usd'=>$total_usd,'btc_per_user'=>$btc_per_user,'usd_per_user'=>$usd_per_user,'open_orders_btc'=>$open_orders_btc,'transactions_btc'=>$transactions_btc,'avg_transaction_size_btc'=>$avg_transaction,'transaction_volume_per_user'=>$trans_per_user,'total_fees_btc'=>$total_fees,'fees_per_user_btc'=>$fees_per_user,'gross_profit_btc'=>$gross_profit));

echo 'done'.PHP_EOL;