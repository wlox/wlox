#!/usr/bin/php
<?php
echo "Beginning Maintenance processing...".PHP_EOL;

include 'common.php';
$CFG->session_active = 1;
$CFG->in_cron = 1;

// get 24 hour BTC volume
$sql = "SELECT IFNULL(SUM(btc),0) AS total_btc_traded FROM transactions WHERE `date` >= DATE_SUB(DATE_ADD(NOW(), INTERVAL ".((($CFG->timezone_offset)/60)/60)." HOUR), INTERVAL 1 DAY) LIMIT 0,1";
$result = db_query_array($sql);
$total_btc_traded = ($result[0]['total_btc_traded']) ? $result[0]['total_btc_traded'] : '0';
db_update('status',1,array('btc_24h'=>$total_btc_traded));

// determine users' monthly volume
$sql = 'SELECT id, global_btc, from_usd, to_usd FROM fee_schedule ORDER BY global_btc ASC, from_usd ASC';
$result = db_query_array($sql);
if ($result && count($result) > 1) {
	$sql = 'SELECT ROUND(SUM(IF(transactions.id IS NOT NULL,transactions.btc * transactions.btc_price * currencies.usd_ask,transactions1.btc * transactions1.btc_price * currencies1.usd_ask)),2) AS volume, site_users.id AS user_id
			FROM site_users
			LEFT JOIN transactions ON (transactions.site_user = site_users.id AND transactions.date >= DATE_SUB(CURDATE(),INTERVAL 1 MONTH))
			LEFT JOIN transactions transactions1 ON (transactions1.site_user1 = site_users.id AND transactions1.date >= DATE_SUB(CURDATE(),INTERVAL 1 MONTH))
			LEFT JOIN currencies ON (currencies.id = transactions.currency)
			LEFT JOIN currencies currencies1 ON (currencies1.id = transactions1.currency)
			WHERE transactions.id IS NOT NULL OR transactions1.id IS NOT NULL
			GROUP BY site_users.id';
	$volumes = db_query_array($sql);
	
	$global_fc_id = false;
	$fee_schedule = false;
	if ($volumes) {
		foreach ($volumes as $volume) {
			foreach ($result as $row) {
				$global_fc_id = ($row['global_btc'] <= $total_btc_traded) ? $row['id'] : $global_fc_id;
				$fee_schedule = ($row['from_usd'] <= $volume['volume']) ? $row['id'] : $fee_schedule;
			}
			$fee_schedule = ($fee_schedule >= $global_fc_id) ? $fee_schedule : $global_fc_id;
			$sql = 'UPDATE site_users SET site_users.fee_schedule = '.$fee_schedule.' WHERE site_users.id = '.$volume['user_id'];
			db_query($sql);
		}
	}
}

// expire settings change request
$sql = 'DELETE FROM change_settings WHERE `date` <= ("'.date('Y-m-d H:i:s').'" - INTERVAL 1 DAY)';
$result = db_query($sql);

// expire unautharized requests
$sql = 'UPDATE requests SET request_status = '.$CFG->request_cancelled_id.' WHERE request_status = '.$CFG->request_awaiting_id.' AND `date` <= (NOW() - INTERVAL 1 DAY)';
$result = db_query($sql);

// 30 day token don't ask
//$sql = 'UPDATE site_users SET dont_ask_30_days = "N" WHERE dont_ask_date <= (NOW() - INTERVAL 1 MONTH) AND dont_ask_30_days = "Y" ';
//$result = db_query($sql);

// delete old sessions
$sql = "DELETE FROM sessions WHERE session_time < ('".date('Y-m-d H:i:s')."' - INTERVAL 15 MINUTE) ";
db_query($sql);

// delete ip access log
$timeframe = (!empty($CFG->cloudflare_blacklist_timeframe)) ? $CFG->cloudflare_blacklist_timeframe : 15;
$sql = "DELETE FROM ip_access_log WHERE `timestamp` < ('".date('Y-m-d H:i:s')."' - INTERVAL $timeframe MINUTE) ";
db_query($sql);

// set market price orders at market price
$sql = "SELECT id,btc,currency,order_type,site_user FROM orders WHERE orders.market_price = 'Y' ORDER BY orders.id ASC";
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		$buy = ($row['order_type'] == $CFG->order_type_bid);
		$operations = Orders::executeOrder($buy,false,$row['btc'],$CFG->currencies[$row['currency']]['currency'],false,1,$row['id'],$row['site_user'],true);
	}
}

// notify pending withdrawals
if ($CFG->email_notify_fiat_withdrawals == 'Y') {
	$sql = 'SELECT 1 FROM requests WHERE notified = 0 AND request_type = '.$CFG->request_widthdrawal_id.' AND request_status = '.$CFG->request_pending_id.' AND `date` < DATE_SUB(DATE_ADD(NOW(), INTERVAL '.((($CFG->timezone_offset)/60)/60).' HOUR), INTERVAL 5 MINUTE) AND done != \'Y\' LIMIT 0,1';
	$result = db_query_array($sql);
	
	if ($result) {
		$sql = 'SELECT ROUND(SUM(requests.amount),2) AS amount, LOWER(currencies.currency) AS currency FROM requests LEFT JOIN currencies ON (currencies.id = requests.currency) WHERE requests.request_type = '.$CFG->request_widthdrawal_id.' AND requests.request_status = '.$CFG->request_pending_id.' AND requests.done != \'Y\' GROUP BY requests.currency';
		$result = db_query_array($sql);
		
		if ($result) {
			$info['pending_withdrawals'] = '';
			foreach ($result as $row) {
				$info['pending_withdrawals'] .= strtoupper($row['currency']).': '.$row['amount'].'<br/>';
			}
			
			$CFG->language = 'en';
			$email = SiteEmail::getRecord('pending-withdrawals');
			Email::send($CFG->form_email,$CFG->contact_email,$email['title'],$CFG->form_email_from,false,$email['content'],$info);
			
			$sql = 'UPDATE requests SET notified = 1 WHERE notified = 0';
			db_query($sql);
		}
	}
}

// subtract withdrawals
$sql = 'SELECT site_users_balances.balance AS balance, site_users_balances.id AS balance_id, requests.id AS request_id, requests.site_user AS site_user, requests.currency AS currency, ROUND(requests.amount,2) AS amount FROM requests LEFT JOIN site_users_balances ON (site_users_balances.id = requests.site_user AND site_users_balances.currency = requests.currency) WHERE requests.request_type = '.$CFG->request_widthdrawal_id.' AND requests.currency != '.$CFG->btc_currency_id.' AND requests.request_status = '.$CFG->request_pending_id.' AND requests.done = \'Y\'';
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		if (empty($old_balance[$row['site_user']][$row['currency']]))
			$old_balance[$row['site_user']][$row['currency']] = $row[$row['currency']];
		
		$sql = 'UPDATE site_users_balances SET balance = balance - '.$row['amount'].' WHERE id = '.$row['balance_id'];
		db_query($sql);
		
		$sql = 'UPDATE history SET balance_before = '.$old_balance[$row['site_user']][$row['currency']].', balance_after = '.($old_balance[$row['site_user']][$row['currency']] - $row['amount']).' WHERE request_id = '.$row['request_id'];
		db_query($sql);
		
		$old_balance[$row['site_user']][$row['currency']] = $old_balance[$row['site_user']][$row['currency']] - $row['amount'];
	}
	$sql = 'UPDATE requests SET request_status = '.$CFG->request_completed_id.' WHERE requests.request_type = '.$CFG->request_widthdrawal_id.' AND requests.currency != '.$CFG->btc_currency_id.' AND requests.request_status = '.$CFG->request_pending_id.' AND requests.done = \'Y\' ';
	db_query($sql);
}

// currency ledger
if ((date('H') == 7 || date('H') == 16) && (date('i') >= 0 && date('i') < 5)) {
	// check total fiat needed for withdrawals
	$sql = "SELECT currency, SUM(amount) AS amount FROM requests WHERE requests.request_status = {$CFG->request_pending_id} AND currency != {$CFG->btc_currency_id} AND request_type = {$CFG->request_withdrawal_id} GROUP BY currency";
	$result = db_query_array($sql);
	if ($result) {
		foreach ($result as $row) {
			$withdrawals[$row['currency']] = $row['amount'];
		}
	}
	
	// get escrow balances from CryptoCapital
	//////////////////////////////////////////
	
	// get current currency ledger
	$ledger = array();
	$sql = 'SELECT * FROM conversions WHERE is_active != "Y"';
	$result = db_query_array($sql);
	if ($result) {
		foreach ($result as $row) {
			$ledger[$row['currency']] = $row['amount'];
		}
	}
	
	// factor new transactions into ledger balances
	$sql = 'SELECT IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', currency, currency1) AS currency, IF( transactions.transaction_type = '.$CFG->transactions_buy_id.', currency1, currency) AS currency1, SUM(IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', transactions.btc_price * transactions.btc, transactions.orig_btc_price * transactions.btc )) AS amount, SUM(IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', transactions.orig_btc_price * transactions.btc, transactions.btc_price * transactions.btc)) AS amount_needed FROM transactions WHERE factored != "Y" AND conversion = \'Y\' GROUP BY CONCAT(IF(transactions.transaction_type = '.$CFG->transactions_buy_id.', currency1, currency) , \'-\', IF( transactions.transaction_type = '.$CFG->transactions_buy_id.', currency, currency1))';
	$result = db_query_array($sql);
	if ($result) {
		foreach ($result as $row) {
			$ledger[$row['currency']] = (!empty($ledger[$row['currency']])) ? $ledger[$row['currency']] + $row['amount'] : $row['amount'];
			$ledger[$row['currency1']] = (!empty($ledger[$row['currency1']])) ? $ledger[$row['currency1']] - $row['amount_needed'] : ($row['amount_needed'] * -1);
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
				db_update('conversions',$result[0]['id'],array('amount'=>$amount,'total_withdrawals'=>((!empty($withdrawals[$currency])) ? $withdrawals[$currency] : 0),'date1'=>date('Y-m-d H:i:s')));
			else
				db_insert('conversions',array('amount'=>$amount,'total_withdrawals'=>((!empty($withdrawals[$currency])) ? $withdrawals[$currency] : 0),'date'=>date('Y-m-d H:i:s'),'date1'=>date('Y-m-d H:i:s'),'currency'=>$currency,'is_active'=>'N','factored'=>'N'));
		}
	}
	
	$sql = 'UPDATE transactions SET factored = "Y" WHERE factored != "Y"';
	db_query($sql);
}

db_update('status',1,array('cron_maintenance'=>date('Y-m-d H:i:s')));

echo 'done'.PHP_EOL;
