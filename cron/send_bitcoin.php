#!/usr/bin/php
<?php
echo "Beginning Send Bitcoin processing...".PHP_EOL;

include 'common.php';

$sql = "SELECT requests.site_user, requests.amount, requests.send_address, requests.id, site_users_balances.balance, site_users_balances.id AS balance_id FROM requests LEFT JOIN site_users_balances ON (site_users_balances.site_user = requests.site_user AND site_users_balances.currency = requests.currency) WHERE requests.request_status = {$CFG->request_pending_id} AND requests.currency = {$CFG->btc_currency_id} AND requests.request_type = {$CFG->request_withdrawal_id}";
$result = db_query_array($sql);
if (!$result) {
	echo 'done'.PHP_EOL;
	exit;
}

$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
$status = DB::getRecord('status',1,0,1);
$available = $status['hot_wallet_btc'];
$deficit = $status['deficit_btc'];
$bitcoin->settxfee($CFG->bitcoin_sending_fee);
$users = array();
$transactions = array();
$user_balances = array();
$addresses = array();

if ($result) {
	$pending = 0;
	
	foreach ($result as $row) {
		// check if user sending to himself
		$addr_info = BitcoinAddresses::getAddress($row['send_address']);
		if (!empty($addr_info['site_user']) && $addr_info['site_user'] == $row['site_user']) {
			db_update('requests',$row['id'],array('request_status'=>$CFG->request_completed_id));
			continue;
		}
		
		// check if sending to another wlox user
		if (!empty($addr_info['site_user'])) {
			if (empty($user_balances[$addr_info['site_user']])) {
				$bal_info = User::getBalance($addr_info['site_user'],$CFG->btc_currency_id,true);
				$user_balances[$addr_info['site_user']] = $bal_info['balance'];
			}
			
			User::updateBalances($row['site_user'],array('btc'=>(-1 * $row['amount'])),true);
			User::updateBalances($addr_info['site_user'],array('btc'=>($row['amount'])),true);
			db_update('requests',$row['id'],array('request_status'=>$CFG->request_completed_id));
			
			$rid = db_insert('requests',array('date'=>date('Y-m-d H:i:s'),'site_user'=>$addr_info['site_user'],'currency'=>$CFG->btc_currency_id,'amount'=>$row['amount'],'description'=>$CFG->deposit_bitcoin_desc,'request_status'=>$CFG->request_completed_id,'request_type'=>$CFG->request_deposit_id));
			if ($rid)
				db_insert('history',array('date'=>date('Y-m-d H:i:s'),'history_action'=>$CFG->history_deposit_id,'site_user'=>$addr_info['site_user'],'request_id'=>$rid,'balance_before'=>$user_balances[$addr_info['site_user']],'balance_after'=>($user_balances[$addr_info['site_user']] + $row['amount']),'bitcoin_address'=>$row['send_address']));
			
			$user_balances[$addr_info['site_user']] = $user_balances[$addr_info['site_user']] + $row['amount'];
			continue;
		}
		
		// check if hot wallet has enough to send
		$pending += $row['amount'];
		if ($row['amount'] > $available)
			continue;
		
		if (bcsub($row['amount'],$CFG->bitcoin_sending_fee,8) > 0) {
			$transactions[$row['send_address']] = (!empty($transactions[$row['send_address']])) ? bcadd($row['amount'],$transactions[$row['send_address']],8) : $row['amount'];
		}
		
		$users[$row['site_user']] = (!empty($users[$row['site_user']])) ? bcadd($row['amount'],$users[$row['site_user']],8) : $row['amount'];
		$requests[] = $row['id'];
		$available = bcsub($available,$row['amount'],8);
	}

	if ($pending > $available) {
		db_update('status',1,array('deficit_btc'=>($pending - $available),'pending_withdrawals'=>$pending));
		echo 'Deficit: '.($pending - $available).PHP_EOL;
	}
}

if (!empty($transactions)) {
	if (count($transactions) > 1) {
		$bitcoin->walletpassphrase($CFG->bitcoin_passphrase,3);
		$json_arr = array();
		$fees_charged = 0;
		foreach ($transactions as $address => $amount) {
			$json_arr[$address] = ($amount - $CFG->bitcoin_sending_fee);
			$fees_charged += $CFG->bitcoin_sending_fee;
		}
		$response = $bitcoin->sendmany($CFG->bitcoin_accountname,json_decode(json_encode($json_arr)));
		
		if (!empty($bitcoin->error))
			echo $bitcoin->error.PHP_EOL;
	}
	elseif (count($transactions) == 1) {
		$bitcoin->walletpassphrase($CFG->bitcoin_passphrase,3);
		$fees_charged = 0;
		foreach ($transactions as $address => $amount) {
			$response = $bitcoin->sendfrom($CFG->bitcoin_accountname,$address,(float)bcsub($amount,$CFG->bitcoin_sending_fee,8));
			$fees_charged += $CFG->bitcoin_sending_fee;
			
			if (!empty($bitcoin->error))
				echo $bitcoin->error.PHP_EOL;
		}
	}
}

if (!empty($response) && $users && !$bitcoin->error) {
	echo 'Transactions sent: '.$response.PHP_EOL;
	
	$total = 0;
	$transaction = $bitcoin->gettransaction($response);
	$actual_fee_difference = $fees_charged - abs($transaction['fee']);

	foreach ($users as $site_user => $amount) {
		$total += $amount;
		User::updateBalances($site_user,array('btc'=>(-1 * $amount)),true);
	}
	
	foreach ($requests as $request_id) {
		db_update('requests',$request_id,array('request_status'=>$CFG->request_completed_id));
	}
	
	if ($total > 0) {
		Status::sumFields(array('hot_wallet_btc'=>(0 - $total + $actual_fee_difference),'total_btc'=>(0 - $total + $actual_fee_difference)));
		Status::updateEscrows(array($CFG->btc_currency_id=>$actual_fee_difference));
		db_update('status',1,array('pending_withdrawals'=>($pending - $total)));
	}
}

if (empty($pending)) db_update('status',1,array('deficit_btc'=>'0'));


db_update('status',1,array('cron_send_bitcoin'=>date('Y-m-d H:i:s')));

echo 'done'.PHP_EOL;
