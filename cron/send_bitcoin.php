#!/usr/bin/php
<?php
echo "Beginning Send Bitcoin processing...".PHP_EOL;

include 'cfg.php';

db_start_transaction();

$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
$status = DB::getRecord('status',1,0,1,false,false,false,1);
$available = $status['hot_wallet_btc'];
$deficit = $status['deficit_btc'];

$sql = "SELECT id, btc FROM site_users FOR UPDATE";
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		$user_balances[$row['id']] = $row['btc'];
	}
}

$sql = "SELECT address, site_user FROM bitcoin_addresses WHERE system_address != 'Y' ";
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		$addresses[$row['address']] = $row['site_user'];
	}
}

$sql = "SELECT site_user,amount,send_address,id FROM requests WHERE requests.request_status = {$CFG->request_pending_id} AND currency = {$CFG->btc_currency_id} AND request_type = {$CFG->request_withdrawal_id} FOR UPDATE";
$result = db_query_array($sql);

if ($result) {
	$pending = 0;
	
	foreach ($result as $row) {
		// check if user has enough available
		if (bcadd($row['amount'],$users[$row['site_user']],8) > $user_balances[$row['site_user']])
			continue;
		
		// check if user sending to himself
		if ($addresses[$row['send_address']] == $row['site_user']) {
			db_update('requests',$row['id'],array('request_status'=>$CFG->request_completed_id));
			continue;
		}
		
		// check if sending to another wlox user
		if ($addresses[$row['send_address']] > 0) {
			db_update('site_users',$row['site_user'],array('btc'=>$user_balances[$row['site_user']] - $row['amount']));
			db_update('site_users',$addresses[$row['send_address']],array('btc'=>$user_balances[$addresses[$row['send_address']]] + $row['amount']));
			db_update('requests',$row['id'],array('request_status'=>$CFG->request_completed_id));
			db_insert('requests',array('date'=>date('Y-m-d H:i:s'),'site_user'=>$addresses[$row['send_address']],'currency'=>$CFG->btc_currency_id,'amount'=>$row['amount'],'description'=>$CFG->deposit_bitcoin_desc,'request_status'=>$CFG->request_completed_id,'request_type'=>$CFG->request_deposit_id));
			continue;
		}
		
		// check if hot wallet has enough to send
		$pending += $row['amount'];
		if ($row['amount'] > $available)
			continue;
		
		if (bcsub($row['amount'],$CFG->bitcoin_sending_fee,8) > 0) {
			$transactions[$row['send_address']] = bcadd($row['amount'],$transactions[$row['send_address']],8);
		}
		
		$users[$row['site_user']] = bcadd($row['amount'],$users[$row['site_user']],8);
		$requests[] = $row['id'];
		$available = bcsub($available,$row['amount'],8);
	}

	if ($pending > $available) {
		db_update('status',1,array('deficit_btc'=>($pending - $available)));
		echo 'Deficit: '.($pending - $available).PHP_EOL;
	}
}

if (count($transactions) > 1) {
	$bitcoin->walletpassphrase($CFG->bitcoin_passphrase,3);
	foreach ($transactions as $address => $amount) {
		$json_arr[$address] = ($amount - $CFG->bitcoin_sending_fee);
	}
	$response = $bitcoin->sendmany($CFG->bitcoin_accountname,json_decode(json_encode($json_arr)));
	echo $bitcoin->error.PHP_EOL;
}
elseif (count($transactions) == 1) {
	$bitcoin->walletpassphrase($CFG->bitcoin_passphrase,3);
	foreach ($transactions as $address => $amount) {
		$response = $bitcoin->sendfrom($CFG->bitcoin_accountname,$address,(float)bcsub($amount,$CFG->bitcoin_sending_fee,8));
		echo $bitcoin->error.PHP_EOL;
	}
}

if ($response && $users && !$bitcoin->error) {
	echo 'Transactions sent: '.$response.PHP_EOL;
	$total = 0;
	foreach ($users as $site_user => $amount) {
		$total += $amount;
		$balance = $user_balances[$site_user] - $amount;
		db_update('site_users',$site_user,array('btc'=>$balance));
		echo 'User '.$site_user.' from '.$user_balances[$site_user].' to '.$balance.PHP_EOL;
	}
	
	foreach ($requests as $request_id) {
		db_update('requests',$request_id,array('request_status'=>$CFG->request_completed_id));
	}
	
	if ($total > 0) {
		$hot_wallet = $status['hot_wallet_btc'] - $total;
		$total_btc = $status['total_btc'] - $total;
		db_update('status',1,array('hot_wallet_btc'=>$hot_wallet,'total_btc'=>$total_btc,'pending_withdrawals'=>($status['pending_withdrawals'] - $total)));
	}
}

if (!$pending) db_update('status',1,array('deficit_btc'=>'0'));

db_commit();
echo 'done'.PHP_EOL;
