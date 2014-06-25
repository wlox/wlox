<?php 
include '../cfg.php';

$headers = apache_request_headers();
$session_id1 = ereg_replace("[^0-9]","",$_POST['session_id']);
$signature1 = $_POST['signature'];
$nonce1 = ereg_replace("[^0-9]","",$_POST['nonce']);
$token1 = ereg_replace("[^0-9]","",$_POST['token']);
$dont_ask1 = $_POST['dont_ask'];

if (!$session_id1) {
	echo json_encode(array('error'=>'missing-session-id'));
	exit;
}

//db_start_transaction();
$result = db_query_array('SELECT sessions.session_key AS session_key, sessions.awaiting AS awaiting, site_users.first_name, site_users.last_name, site_users.fee_schedule,site_users.tel, site_users.country_code,site_users.verified_google, site_users.verified_authy,site_users.using_sms,site_users.confirm_withdrawal_email_btc,site_users.confirm_withdrawal_2fa_btc,site_users.confirm_withdrawal_2fa_bank,site_users.confirm_withdrawal_email_bank,site_users.notify_deposit_btc,site_users.notify_deposit_bank,site_users.no_logins,site_users.notify_login,site_users.deactivated, site_users.locked FROM sessions LEFT JOIN site_users ON (sessions.user_id = site_users.id) WHERE sessions.session_id = '.$session_id1.' AND sessions.nonce <= '.($nonce1 + 5).' AND sessions.nonce >= '.$nonce1);
if (!$result) {
	echo json_encode(array('error'=>'session-not-found'));
	//db_commit();
	exit;
}

if (!openssl_verify($_POST['commands'],$signature1,$result[0]['session_key'])) {
	echo json_encode(array('error'=>'invalid-signature'));
	//db_commit();
	exit;
}

if ($result[0]['awaiting'] == 'Y') {
	echo json_encode(array('message'=>'awaiting-token'));
	//db_commit();
	exit;
}

if ($result[0]['country_code'] > 0) {
	$s = strlen($result[0]['country_code']);
	$result[0]['country_code'] = str_repeat('x',$s);
}

if ($result[0]['tel'] > 0) {
	$s = strlen($result[0]['tel']) - 2;
	$result[0]['tel'] = str_repeat('x',$s).substr($result[0]['tel'], -2);
}

unset($result[0]['session_key']);
unset($result[0]['awaiting']);
unset($result[0]['id']);
unset($result[0]['pass']);
unset($result[0]['awaiting']);
echo json_encode(array('message'=>'logged-in','info'=>$result[0]));

db_update('sessions',$session_id1,array('nonce'=>($nonce1 + 1),'session_time'=>date('Y-m-d H:i:s')),'session_id');
//db_commit();