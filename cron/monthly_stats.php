#!/usr/bin/php
<?php
echo "Beginning Monthly Stats processing...".PHP_EOL;

include 'common.php';

/* should run at the very start of every month */

// get total users
$sql = 'SELECT COUNT(id) AS total_users FROM site_users';
$result = db_query_array($sql);
$total_users = $result[0]['total_users'];

// get total transactions for the month
$sql = 'SELECT SUM(transactions.btc * transactions.btc_price * currencies.usd_ask) AS total_btc, AVG(transactions.btc * transactions.btc_price * currencies.usd_ask) AS avg_btc, SUM((transactions.fee + transactions.fee1)  * transactions.btc_price * currencies.usd_ask) AS total_fees FROM transactions LEFT JOIN currencies ON (transactions.currency = currencies.id) WHERE MONTH(transactions.date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(transactions.date) = YEAR(CURDATE() - INTERVAL 1 MONTH)';
$result = db_query_array($sql);
$transactions_btc = $result[0]['total_btc'];
$avg_transaction = $result[0]['avg_btc'];
$trans_per_user = $transactions_btc / $total_users;
$total_fees = $result[0]['total_fees'];
$fees_per_user = $total_fees / $total_users;

// get current currency ledger
$sql = 'SELECT conversions.*, currencies.currency AS currency_abbr FROM conversions LEFT JOIN currencies ON (currencies.id = conversions.currency) WHERE conversions.is_active = "Y" AND conversions.factored != "Y"';
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		$ledger[$row['currency_abbr']] = $row;
	}
}

// get total currency conversion commision in usd
$sql = 'SELECT SUM(conversions.profit_to_factor * currencies.usd_ask) AS conversion_fees FROM conversions LEFT JOIN currencies ON (currencies.id = conversions.currency) WHERE conversions.is_active = "Y" AND factored != "Y"';
$result = db_query_array($sql);
$conversion_fees = $result[0]['conversion_fees'];

// close this month's currency ledger
$sql = 'UPDATE conversions SET factored = "Y" WHERE conversions.is_active = "Y" AND factored != "Y"';
db_query($sql);

// move factored profits to individual currency escrows (these tell you how much of each currency you can safely withdraw as profit)
// create new ledger entries for next month
$status = DB::getRecord('status',1,0,1,false,false,false,1);
if (!empty($ledger)) {
	foreach ($ledger as $currency_abbr => $row) {
		$escrows[] = strtolower($currency_abbr).'_escrow = '.($row['profit_to_factor'] + $status[strtolower($currency_abbr).'_escrow']).' ';
		db_insert('conversions',array('amount'=>($row['amount'] - $row['profit_to_factor']),'total_withdrawals'=>'0','date'=>date('Y-m-d H:i:s'),'date1'=>date('Y-m-d H:i:s'),'currency'=>$row['currency'],'is_active'=>'N','factored'=>'N'));
	}
	$sql = 'UPDATE `status` SET '.(implode(',',$escrows)).' WHERE id = 1';
	db_query($sql);
}

// get fees incurred from the Bitcoin network for internal movements
$sql = 'SELECT SUM(fees.fee * currencies.usd_ask) AS fees_incurred FROM fees LEFT JOIN currencies ON (currencies.id = 28) WHERE MONTH(fees.date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(fees.date) = YEAR(CURDATE() - INTERVAL 1 MONTH)';
$result = db_query_array($sql);
$gross_profit = $total_fees - $result[0]['fees_incurred'];

db_insert('monthly_reports',array('date'=>date('Y-m-d',strtotime('-1 day')),'transactions_btc'=>$transactions_btc,'avg_transaction_size_btc'=>$avg_transaction,'transaction_volume_per_user'=>$trans_per_user,'total_fees_btc'=>$total_fees,'fees_per_user_btc'=>$fees_per_user,'gross_profit_btc'=>$gross_profit));

db_update('status',1,array('cron_monthly_stats'=>date('Y-m-d H:i:s')));
echo 'done'.PHP_EOL;
