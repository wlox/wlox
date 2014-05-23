#!/usr/bin/php
<?php

include 'cfg.php';

db_start_transaction();

$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
$status = DB::getRecord('status',1,0,1,false,false,false,1);
$available = $status['hot_wallet_btc'];
$deficit = $status['deficit_btc'];

$sql = "SELECT site_user,amount,send_address,id FROM requests WHERE requests.request_status = {$CFG->request_pending_id} AND currency = {$CFG->btc_currency_id} AND request_type = {$CFG->request_withdrawal_id} FOR UPDATE";
$result = db_query_array($sql);

if ($result) {
	$pending = 0;
	
	foreach ($result as $row) {
		$pending += $row['amount'];
		
		if ($row['amount'] > $available)
			continue;

		if (bcsub($row['amount'],$CFG->bitcoin_sending_fee,8) > 0) {
			$transactions[$row['send_address']] = bcadd($row['amount'],$transactions[$row['send_address']],8);
		}
		
		$users[$row['site_user']] = bcadd($row['amount'],$users[$row['site_user']],8);
		$requests[] = $row['id'];
		$available = bcsub($row['amount'],$available,8);
	}
	
	$sql = "SELECT id, btc FROM site_users ";
	$result = db_query_array($sql);
	if ($result) {
		foreach ($result as $row) {
			$user_balances[$row['id']] = $row['btc'];
		}
	}

	if ($pending > $available) {
		db_update('status',1,array('deficit_btc'=>($pending - $available)));
		echo 'Deficit: '.($pending - $available).'<br>';
	}
}

if (count($transactions) > 1) {
	$bitcoin->walletpassphrase($CFG->bitcoin_passphrase,3);
	foreach ($transactions as $address => $amount) {
		$json_arr[$address] = ($amount - $CFG->bitcoin_sending_fee);
	}
	$response = $bitcoin->sendmany($CFG->bitcoin_accountname,json_decode(json_encode($json_arr)));
	echo $bitcoin->error;
}
elseif (count($transactions) == 1) {
	$bitcoin->walletpassphrase($CFG->bitcoin_passphrase,3);
	foreach ($transactions as $address => $amount) {
		$response = $bitcoin->sendfrom($CFG->bitcoin_accountname,$address,bcsub($amount,$CFG->bitcoin_sending_fee,8));
		echo $bitcoin->error;
	}
}

if ($response || $users) {
	echo 'Transactions sent: '.$response.'<br>';
	$total = 0;
	foreach ($users as $site_user => $amount) {
		$total += $amount;
		$balance = $user_balances[$site_user] - $amount;
		db_update('site_users',$site_user,array('btc'=>$balance));
		echo 'User '.$site_user.' from '.$user_balances[$site_user].' to '.$balance.'<br>';
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
echo 'done';