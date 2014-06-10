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

$result = db_query_array('SELECT sessions.session_key AS session_key, site_users.authy_id AS authy_id, site_users.id AS user_id, site_users.google_2fa_code AS google_2fa_code  FROM sessions LEFT JOIN site_users ON (sessions.user_id = site_users.id) WHERE sessions.session_id = '.$session_id1.' AND sessions.nonce = '.$nonce1);
if (!$result) {
	echo json_encode(array('error'=>'session-not-found'));
	exit;
}

if (!($result[0]['authy_id'] > 0) && !$result[0]['google_2fa_code']) {
	echo json_encode(array('error'=>'session-not-found'));
	exit;
}

if (!openssl_verify($_POST['commands'],$signature1,$result[0]['session_key'])) {
	echo json_encode(array('error'=>'invalid-signature'));
	exit;
}

if ($result[0]['authy_id'] > 0) {
	$response = shell_exec('curl "https://api.authy.com/protected/json/verify/'.$token1.'/'.$result[0]['authy_id'].'?api_key='.$CFG->authy_api_key.'"');
	$response1 = json_decode($response,true);
	
	if (!$response || !is_array($response1)) {
		echo json_encode(array('error'=>'security-com-error'));
		exit;
	}
	
	if ($response1['success'] === false) {
		echo json_encode(array('error'=>'authy-errors','authy-errors'=>$response1['errors']));
		exit;
	}
}
elseif ($result[0]['google_2fa_code']) {
	$result = Google2FA::verify_key($result[0]['google_2fa_code'],$token1);
	if (!$result) {
		echo json_encode(array('error'=>'security-incorrect-token'));
		exit;
	}
}

if ($dont_ask1 > 0)
	db_update('site_users',$result[0]['user_id'],array('dont_ask_30_days'=>'Y','dont_ask_date'=>date('Y-m-d H:i:s')));
		
db_update('sessions',$session_id1,array('nonce'=>($nonce1 + 1),'awaiting'=>'N'),'session_id');

echo json_encode(array('message'=>'OK'));


