#!/usr/bin/php
<?php
echo "Beginning Monthly Stats processing...".PHP_EOL;

include 'cfg.php';

/* should run at the very start of every month */

// get total users
$sql = 'SELECT COUNT(id) AS total_users FROM site_users';
$result = db_query_array($sql);
$total_users = $result[0]['total_users'];

// get total transactions for the month
$sql = 'SELECT SUM(transactions.btc * transactions.btc_price * currencies.usd_ask) AS total_btc, AVG(transactions.btc  * transactions.btc_price * currencies.usd_ask) AS avg_btc, SUM(((transactions.fee + transactions.fee1)  * transactions.btc_price * currencies.usd_ask) + IFNULL(conversions.amount_received - conversions.amount_needed,0)) AS total_fees FROM transactions LEFT JOIN currencies ON (transactions.currency = currencies.id) LEFT JOIN conversions ON (conversions.transaction = transactions.id AND conversions.is_active = "Y") WHERE MONTH(transactions.date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(transactions.date) = YEAR(CURDATE() - INTERVAL 1 MONTH)';
$result = db_query_array($sql);
$transactions_btc = $result[0]['total_btc'];
$avg_transaction = $result[0]['avg_btc'];
$trans_per_user = $transactions_btc / $total_users;
$total_fees = $result[0]['total_fees'];
$fees_per_user = $total_fees / $total_users;

$sql = 'SELECT SUM(fees.fee*currencies.usd_ask) AS fees_incurred FROM fees LEFT JOIN currencies ON (currencies.id = 28) WHERE MONTH(fees.date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(fees.date) = YEAR(CURDATE() - INTERVAL 1 MONTH)';
$result = db_query_array($sql);
$gross_profit = $total_fees - $result[0]['fees_incurred'];

db_insert('monthly_reports',array('date'=>date('Y-m-d',strtotime('-1 day')),'transactions_btc'=>$transactions_btc,'avg_transaction_size_btc'=>$avg_transaction,'transaction_volume_per_user'=>$trans_per_user,'total_fees_btc'=>$total_fees,'fees_per_user_btc'=>$fees_per_user,'gross_profit_btc'=>$gross_profit));
echo 'done'.PHP_EOL;
