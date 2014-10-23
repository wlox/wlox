#!/usr/bin/php
<?php
echo "Beginning Receive Bitcoin processing...".PHP_EOL;

include 'cfg.php';

$CFG->session_active = true;
$transactions_dir = $CFG->dirroot.'transactions/';

db_start_transaction();

$total_received = 0;
$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
$transactions = scandir($transactions_dir);
if (!$transactions) {
	db_commit();
	exit;
}

$sql = "SELECT transaction_id, id FROM requests WHERE request_status != {$CFG->request_completed_id} AND currency = {$CFG->btc_currency_id} AND request_type = {$CFG->request_deposit_id} ";
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		$requests[$row['transaction_id']] = $row['id'];
	}
}

$sql = "SELECT address, site_user FROM bitcoin_addresses WHERE system_address != 'Y' ";
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		$addresses[$row['address']] = $row['site_user'];
	}
}

$sql = "SELECT id, btc FROM site_users FOR UPDATE";
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		$user_balances[$row['id']] = $row['btc'];
	}
}

$sql = "SELECT address, site_user, hot_wallet FROM bitcoin_addresses WHERE system_address = 'Y' ";
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		$system[$row['address']] = ($row['hot_wallet'] == 'Y') ? 'Y' : 'N';
	}
}

$sql = "SELECT id, transaction_id FROM bitcoind_log ORDER BY `date` DESC LIMIT 0,100 ";
$result = db_query_array($sql);
if ($result) {
	foreach ($result as $row) {
		$transaction_log[$row['transaction_id']] = $row['id'];
	}
}

$status = DB::getRecord('status',1,0,1,false,false,false,1);

foreach ($transactions as $t_id) {
	if (!$t_id || $t_id == '.' || $t_id == '..')
		continue;
	
	$transaction = $bitcoin->gettransaction($t_id);
	if ($transaction_log[$t_id]) {
		unlink($transactions_dir.$t_id);
		continue;
	}
	if (!$transaction['details'])
		continue;
	
	$raw = $bitcoin->decoderawtransaction($bitcoin->getrawtransaction($t_id));
	$sender_address = $raw['vout'][1]['scriptPubKey']['addresses'][0];
	
	$send = false;
	$pending = false;
	$hot_wallet_in = 0;
	
	foreach ($transaction['details'] as $detail) {
		if ($detail['category'] == 'receive') {
			$user_id = $addresses[$detail['address']];
			$request_id = $requests[$transaction['txid']];
			
			if ($system[$detail['address']] == 'Y') {
				if ($transaction['confirmations'] > 0) {
					$hot_wallet_in = $detail['amount'];
				}
				continue;
			}
			elseif ($system[$detail['address']] == 'N') {
				unlink($transactions_dir.$t_id);
				break;
			}
			
			if ($transaction['confirmations'] < 3) {
				if (!($request_id > 0)) {
					$rid = db_insert('requests',array('date'=>date('Y-m-d H:i:s'),'site_user'=>$user_id,'currency'=>$CFG->btc_currency_id,'amount'=>$detail['amount'],'description'=>$CFG->deposit_bitcoin_desc,'request_status'=>$CFG->request_pending_id,'request_type'=>$CFG->request_deposit_id,'transaction_id'=>$transaction['txid'],'send_address'=>$sender_address));
					db_insert('history',array('date'=>date('Y-m-d H:i:s'),'history_action'=>$CFG->history_deposit_id,'site_user'=>$user_id,'request_id'=>$rid));
				}
				
				echo 'Transaction pending.'.PHP_EOL;
				$pending = true;
			}
			else {
				if (!($request_id > 0)) {
					$updated = db_insert('requests',array('date'=>date('Y-m-d H:i:s'),'site_user'=>$user_id,'currency'=>$CFG->btc_currency_id,'amount'=>$detail['amount'],'description'=>$CFG->deposit_bitcoin_desc,'request_status'=>$CFG->request_completed_id,'request_type'=>$CFG->request_deposit_id,'transaction_id'=>$transaction['txid'],'send_address'=>$sender_address));
					db_insert('history',array('date'=>date('Y-m-d H:i:s'),'history_action'=>$CFG->history_deposit_id,'site_user'=>$user_id,'request_id'=>$updated));
				}
				else
					$updated = db_update('requests',$request_id,array('request_status'=>$CFG->request_completed_id));
				
				if ($updated > 0) {
					$user_balances[$user_id] = $user_balances[$user_id] + $detail['amount'];
					
					db_update('site_users',$user_id,array('btc'=>($user_balances[$user_id]),'last_update'=>date('Y-m-d H:i:s')));
					db_insert('bitcoind_log',array('transaction_id'=>$transaction['txid'],'amount'=>$detail['amount'],'date'=>date('Y-m-d H:i:s')));
					$unlink = unlink($transactions_dir.$t_id);
					$total_received += $detail['amount'];
					
					if (!$unlink && file_exists($unlink)) {
						$unlink = unlink($transactions_dir.$t_id);
					}
					
					if (!$unlink)
						echo 'Error: Could not delete transaction file.'.PHP_EOL;
					else
						echo 'Transaction credited successfully.'.PHP_EOL;
				}
			}
		}
		elseif ($detail['category'] == 'send') {	
			if ($system[$detail['address']]) {
				unlink($transactions_dir.$t_id);
				break;
			} 
			else {
				$send = true;
			}
		}
	}
	
	if ($send && !$pending && !($hot_wallet_in > 0))
		unlink($transactions_dir.$t_id);
	elseif (!$send && ($hot_wallet_in > 0)) {
		$old_info = $status;
		$updated = db_update('status',1,array('hot_wallet_btc'=>($status['hot_wallet_btc'] + $hot_wallet_in),'warm_wallet_btc'=>($status['warm_wallet_btc'] - ($hot_wallet_in + $CFG->bitcoin_sending_fee)),'total_btc'=>($status['total_btc'] - $CFG->bitcoin_sending_fee)));
		echo 'Hot wallet received '.$hot_wallet_in.PHP_EOL;
		if ($updated) {
			unlink($transactions_dir.$t_id);
			if (!$unlink && file_exists($unlink)) {
				$unlink = unlink($transactions_dir.$t_id);
			}
			
			db_insert('bitcoind_log',array('transaction_id'=>$transaction['txid'],'amount'=>$hot_wallet_in,'date'=>date('Y-m-d H:i:s')));
			$status = DB::getRecord('status',1,0,1,false,false,false,1);
		}
	}
}

$hot_wallet = $status['hot_wallet_btc'] + $total_received;
$warm_wallet = $status['warm_wallet_btc'];
$total_btc = $status['total_btc'] + $total_received;
//$received_pending = $status['received_btc_pending'] + $total_received;
$pending_withdrawals = $status['pending_withdrawals'];
$reserve_balance = $total_btc * $CFG->bitcoin_reserve_ratio;
$reserve_surplus = $hot_wallet - $reserve_balance - $pending_withdrawals - $CFG->bitcoin_sending_fee;

echo 'Reserve surplus: '.sprintf("%.8f", $reserve_surplus).PHP_EOL;

if ($total_received > 0) {
	if (!$status) {
		echo 'Error: Could not get status.'.PHP_EOL;
		db_commit();
		exit;
	}
	
	echo 'Total received: '.$total_received.PHP_EOL;
	$updated = db_update('status',1,array('hot_wallet_btc'=>$hot_wallet,'total_btc'=>$total_btc));
	
	//$warm_wallet_a = BitcoinAddresses::getWarmWallet();
	$warm_wallet_a['address'] = $CFG->bitcoin_warm_wallet_address;
	$hot_wallet_a = BitcoinAddresses::getHotWallet();
	
	if ($reserve_surplus > $CFG->bitcoin_reserve_min) {
		$bitcoin->settxfee(0.00);
		$bitcoin->walletpassphrase($CFG->bitcoin_passphrase,3);
		$response = $bitcoin->sendfrom($CFG->bitcoin_accountname,$warm_wallet_a['address'],floatval($reserve_surplus));
		$transferred = 0;
		echo $bitcoin->error;
		if ($response && !$bitcoin->error) {
			$transferred = $reserve_surplus;
			$transfer_fees = 0;
			$transaction = $bitcoin->gettransaction($response);
			foreach ($transaction['details'] as $detail) {
				if ($detail['category'] == 'send') {
					$detail['fee'] = abs($detail['fee']);
					if ($detail['fee'] > 0) {
						$transfer_fees += $detail['fee'];
						db_insert('fees',array('fee'=>$detail['fee'],'date'=>date('Y-m-d H:i:s')));
					}
				}
			}
			
			db_update('status',1,array('hot_wallet_btc'=>($hot_wallet - $transferred - $transfer_fees),'warm_wallet_btc'=>($warm_wallet + $transferred),'total_btc'=>($total_btc - $transfer_fees)));
			echo 'Transferred '.$reserve_surplus.' to warm wallet. TX: '.$response.PHP_EOL;
		}
	}
}
elseif ($reserve_surplus > $CFG->bitcoin_reserve_min) {
	//$warm_wallet_a = BitcoinAddresses::getWarmWallet();
	$warm_wallet_a['address'] = $CFG->bitcoin_warm_wallet_address;
	$hot_wallet_a = BitcoinAddresses::getHotWallet();
	
	$bitcoin->settxfee(0.00);
	$bitcoin->walletpassphrase($CFG->bitcoin_passphrase,3);
	$response = $bitcoin->sendfrom($CFG->bitcoin_accountname,$warm_wallet_a['address'],floatval($reserve_surplus));
	$transferred = 0;
	echo $bitcoin->error;
	if ($response && !$bitcoin->error) {
		$transferred = $reserve_surplus;
		$transfer_fees = 0;
		$transaction = $bitcoin->gettransaction($response);
		
		foreach ($transaction['details'] as $detail) {
			if ($detail['category'] == 'send') {
				$detail['fee'] = abs($detail['fee']);
				if ($detail['fee'] > 0) {
					$transfer_fees += $detail['fee'];
					db_insert('fees',array('fee'=>$detail['fee'],'date'=>date('Y-m-d H:i:s')));
				}
			}
		}
		
		db_update('status',1,array('hot_wallet_btc'=>($hot_wallet - $transferred - $transfer_fees),'warm_wallet_btc'=>($warm_wallet + $transferred),'total_btc'=>($total_btc - $transfer_fees)));
		echo 'Transferred '.$reserve_surplus.' to warm wallet. TX: '.$response.PHP_EOL;
	}
}

db_commit();
