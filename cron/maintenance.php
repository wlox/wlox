#!/usr/bin/php
<?php
echo "Beginning Maintenance processing...".PHP_EOL;

include 'cfg.php';
$CFG->session_active = 1;
$CFG->in_cron = 1;

// get 24 hour BTC volume
$sql = "SELECT IFNULL(SUM(btc),0) AS total_btc_traded FROM transactions WHERE `date` >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY `date` ASC LIMIT 0,1";
$result = db_query_array($sql);
$total_btc_traded = ($result[0]['total_btc_traded']) ? $result[0]['total_btc_traded'] : '0';

// determine users' monthly volume
$sql = 'UPDATE site_users s1 JOIN transactions ON (s1.id = transactions.site_user OR s1.id = transactions.site_user1) SET s1.fee_schedule = (SELECT IF(fee_schedule.from_usd >= fee_schedule1.from_usd,fee_schedule.id, fee_schedule1.id) AS id FROM (SELECT ROUND(SUM(transactions.btc * transactions.btc_price * currencies.usd_ask),2) AS volume, site_users.id AS user_id FROM site_users LEFT JOIN transactions ON (site_users.id = transactions.site_user OR site_users.id = transactions.site_user1) LEFT JOIN currencies ON (currencies.id = transactions.currency) WHERE transactions.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) GROUP BY site_users.id) AS volumes LEFT JOIN fee_schedule ON (volumes.volume >= fee_schedule.from_usd AND (volumes.volume <= fee_schedule.to_usd OR fee_schedule.to_usd = 0)) LEFT JOIN (SELECT id, global_btc, from_usd FROM fee_schedule WHERE global_btc <= '.$total_btc_traded.' ORDER BY global_btc DESC, from_usd ASC LIMIT 0,1) AS fee_schedule1 ON (fee_schedule1.global_btc <= '.$total_btc_traded.') WHERE volumes.user_id = s1.id GROUP BY volumes.volume)';
$result = db_query($sql);
$sql = 'SELECT id FROM fee_schedule ORDER BY from_usd ASC LIMIT 0,1';
$result = db_query_array($sql);
$sql = 'UPDATE site_users SET fee_schedule = '.$result[0]['id'].' WHERE fee_schedule = 0';
$result = db_query($sql);

// expire settings change request
$sql = 'DELETE FROM change_settings WHERE `date` <= ("'.date('Y-m-d H:i:s').'" - INTERVAL 1 DAY)';
$result = db_query($sql);

// expire unautharized requests
$sql = 'UPDATE requests SET request_status = '.$CFG->request_cancelled_id.' WHERE request_status = '.$CFG->request_awaiting_id.' AND `date` <= (NOW() - INTERVAL 1 DAY)';
$result = db_query($sql);

// 30 day token don't ask
$sql = 'UPDATE site_users SET dont_ask_30_days = "N" WHERE dont_ask_date <= (NOW() - INTERVAL 1 MONTH) AND dont_ask_30_days = "Y" ';
$result = db_query($sql);

// delete old sessions
$sql = "DELETE FROM sessions WHERE session_time < ('".date('Y-m-d H:i:s')."' - INTERVAL 15 MINUTE) ";
db_query($sql);

// set market price orders at market price
db_start_transaction();
$sql = "SELECT orders.id AS id,orders.btc AS btc,orders.order_type AS order_type,currencies.currency AS currency, fee_schedule.fee AS fee, orders.site_user AS site_user FROM orders LEFT JOIN currencies ON (currencies.id = orders.currency) LEFT JOIN site_users ON (orders.site_user = site_users.id) LEFT JOIN fee_schedule ON (site_users.fee_schedule = fee_schedule.id) WHERE orders.market_price = 'Y' ORDER BY orders.date ASC FOR UPDATE";
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		if ($row['order_type'] == $CFG->order_type_bid) {
			$price = Orders::getCurrentAsk($row['currency']);
			$buy = true;
		}
		else {
			$price = Orders::getCurrentBid($row['currency']);
			$buy = false;
		}
		
		if ($price > 0)
			$operations = Orders::executeOrder($buy,$price,$row['btc'],$row['currency'],$row['fee'],1,$row['id'],$row['site_user'],1);
	}
}
db_commit();

// subtract withdrawals
db_start_transaction();
$sql = 'SELECT requests.site_user AS site_user, LOWER(currencies.currency) AS currency, SUM(requests.amount) AS amount FROM requests LEFT JOIN currencies ON (currencies.id = requests.currency) WHERE requests.request_type = '.$CFG->request_widthdrawal_id.' AND requests.currency != '.$CFG->btc_currency_id.' AND requests.request_status = '.$CFG->request_pending_id.' AND requests.done = \'Y\' GROUP BY requests.site_user, requests.currency FOR UPDATE';
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		$sql = 'UPDATE site_users SET '.$row['currency'].' = '.$row['currency'].' - '.$row['amount'].' WHERE id = '.$row['site_user'];
		db_query($sql);
	}
	$sql = 'UPDATE requests SET request_status = '.$CFG->request_completed_id.' WHERE requests.request_type = '.$CFG->request_widthdrawal_id.' AND requests.currency != '.$CFG->btc_currency_id.' AND requests.request_status = '.$CFG->request_pending_id.' AND requests.done = \'Y\' ';
	db_query($sql);
}
db_commit();

// currency ledger
if ((date('H') == 0 || date('H') == 12) && (date('i') >= 0 && date('i') < 5)) {
	db_start_transaction();
	// check total fiat needed for withdrawals
	$sql = "SELECT currency, SUM(amount) AS amount FROM requests WHERE requests.request_status = {$CFG->request_pending_id} AND currency != {$CFG->btc_currency_id} AND request_type = {$CFG->request_withdrawal_id} GROUP BY currency FOR UPDATE";
	$result = db_query_array($sql);
	if ($result) {
		foreach ($result as $row) {
			$withdrawals[$row['currency']] = $row['amount'];
		}
	}
	
	// get escrow balances from CryptoCapital
	//////////////////////////////////////////
	
	// get current currency ledger
	$sql = 'SELECT * FROM conversions WHERE is_active != "Y" FOR UPDATE';
	$result = db_query_array($sql);
	if ($result) {
		foreach ($result as $row) {
			$ledger[$row['currency']] = $row['amount'];
		}
	}
	
	// factor new transactions into ledger balances
	$sql = 'SELECT IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', currency, currency1) AS currency, IF( transactions.transaction_type = '.$CFG->transactions_buy_id.', currency1, currency) AS currency1, SUM(IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', transactions.btc_price * transactions.btc, transactions.orig_btc_price * transactions.btc )) AS amount, SUM(IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', transactions.orig_btc_price * transactions.btc, transactions.btc_price * transactions.btc)) AS amount_needed FROM transactions WHERE factored != "Y" AND conversion = \'Y\' GROUP BY CONCAT(IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', currency1, currency) , \'-\', IF( transactions.transaction_type = '.$CFG->transactions_buy_id.', currency, currency1)) FOR UPDATE';
	$result = db_query_array($sql);
	if ($result) {
		foreach ($result as $row) {
			$ledger[$row['currency']] = ($ledger[$row['currency']]) ? $ledger[$row['currency']] + $row['amount'] : $row['amount'];
			$ledger[$row['currency1']] = ($ledger[$row['currency1']]) ? $ledger[$row['currency1']] - $row['amount_needed'] : ($row['amount_needed'] * -1);
		}
	}
	
	if ($ledger) {
		foreach ($ledger as $currency => $amount) {
			/*
			 if ($withdrawals[$currency] > $amount) {
			// consolidate that particular currency to satisfy withdrawals
			/////////////////////////////////////////////
			}
			*/
	
			$sql = 'SELECT id FROM conversions WHERE currency = '.$currency.' AND is_active != "Y" LIMIT 0,1';
			$result = db_query_array($sql);
			if ($result)
				db_update('conversions',$result[0]['id'],array('amount'=>$amount,'total_withdrawals'=>$withdrawals[$currency],'date1'=>date('Y-m-d H:i:s')));
			else
				db_insert('conversions',array('amount'=>$amount,'total_withdrawals'=>$withdrawals[$currency],'date'=>date('Y-m-d H:i:s'),'date1'=>date('Y-m-d H:i:s'),'currency'=>$currency,'is_active'=>'N','factored'=>'N'));
		}
	}
	
	$sql = 'UPDATE transactions SET factored = "Y" WHERE factored != "Y"';
	db_query($sql);
	db_commit();
}

echo 'done'.PHP_EOL;
