<?php 
include '../lib/common.php';

$user1 = preg_replace("/[^0-9]/","",$_REQUEST['user']);
$pass1 = preg_replace($CFG->pass_regex,"",$_REQUEST['pass']);
$email_authcode = $_REQUEST['email_authcode'];
$email_authcode_request = !empty($_REQUEST['email_authcode_request']);
$ip1 = (!empty($_REQUEST['ip'])) ? preg_replace("/[^0-9\.]/","",$_REQUEST['ip']) : false;
$ip_int = ip2long($ip1);
$invalid_login = false;
$awaiting_token = false;
$attempts = 0;
$timeout = 0;
$timeout_next = 0;
$user_id = 0;

if ($email_authcode) {
	$authcode1 = Encryption::decrypt(urldecode($email_authcode));
	if ($authcode1 > 0) {
		if (!$email_authcode_request)
			$sql = 'SELECT site_user FROM change_settings WHERE id = '.$authcode1;
		else
			$sql = 'SELECT site_user FROM requests WHERE id = '.$authcode1;
		
		$request = db_query_array($sql);
		if ($request && $request[0]['site_user'])
			$user_id = $request[0]['site_user'];
	}
}

if ((!$user1 || !$pass1) && !$user_id)
	$invalid_login = 1;

$result = db_query_array("SELECT site_users.*, site_users_access.start AS `start`, site_users_access.last AS `last`, site_users_access.attempts AS attempts FROM site_users LEFT JOIN site_users_access ON (site_users_access.site_user = site_users.id) WHERE ".(($user_id > 0) ? "site_users.id = $user_id" :  "site_users.user = '$user1'"));

if (!$result) {
	if (strlen($user1) == 8) {
		if ($ip_int) {
			$timeframe = (!empty($CFG->cloudflare_blacklist_timeframe)) ? $CFG->cloudflare_blacklist_timeframe : 15;
			$sql = 'SELECT COUNT(1) AS login_attempts FROM ip_access_log WHERE login = "Y" AND `timestamp` > DATE_SUB("'.date('Y-m-d H:i:s').'", INTERVAL '.$timeframe.' MINUTE) AND ip = '.$ip_int;
			$result = db_query_array($sql);
			
			if ($result)
				$attempts = $result[0]['login_attempts'] + 1;
		}

		$result = db_query_array("SELECT attempts FROM site_users_catch WHERE site_user = $user1");
		if ($result) {
			$attempts = ($result[0]['attempts'] + 1 > $attempts) ? $result[0]['attempts'] + 1 : $attempts;
			$timeout = pow(2,$attempts);
			$timeout_next = pow(2,$attempts + 1);
			db_update('site_users_catch',$user1,array('attempts'=>($result[0]['attempts'] + 1)),'site_user');
		}
		else 
			db_insert('site_users_catch',array('attempts'=>'1','site_user'=>$user1));
	}
	
	$invalid_login = 1;
}
elseif ($result) {
	if (empty($result[0]['start']) || ($result[0]['start'] - time() >= 3600)) {
		$attempts = 1;
		if ($result[0]['start'])
			db_update('site_users_access',$result[0]['id'],array('attempts'=>'1','start'=>time(),'last'=>time()),'site_user');
		else
			db_insert('site_users_access',array('attempts'=>'1','start'=>time(),'last'=>time(),'site_user'=>$result[0]['id']));
	}
	else {
		$attempts = $result[0]['attempts'] + 1;
		$timeout = pow(2,$attempts);
		$timeout_next = pow(2,$attempts + 1);
		
		if ($attempts == 3) {
			$CFG->language = ($result[0]['last_lang']) ? $result[0]['last_lang'] : 'en';
			$email = SiteEmail::getRecord('bruteforce-notify');
			Email::send($CFG->support_email,$result[0]['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$result[0]);
		}
		
		db_update('site_users_access',$result[0]['id'],array('attempts'=>$attempts,'last'=>time()),'site_user');
		
		if ((time() - $result[0]['last']) <= $timeout)
			$invalid_login = 1;
		
	}
	
	if (!$invalid_login && !$user_id) {
		$invalid_login = (!Encryption::verify_hash($pass1,$result[0]['pass']));
		if (!$invalid_login) {
			$sql = "DELETE FROM change_settings WHERE type = 'r' AND site_user = ".$result[0]['id'];
			db_query($sql);
		}
	}
}


if ($invalid_login) {
	db_insert('ip_access_log',array('ip'=>$ip_int,'timestamp'=>date('Y-m-d H:i:s'),'login'=>'Y'));

	echo json_encode(array('error'=>'invalid-login','attempts'=>$attempts,'timeout'=>$timeout_next));
	exit;
}

if (($result[0]['verified_authy'] == 'Y' || $result[0]['verified_google'] == 'Y') && $result[0]['dont_ask_30_days'] != 'Y') {
	if ($result[0]['using_sms'] == 'Y')
		shell_exec('curl https://api.authy.com/protected/json/sms/'.$result[0]['authy_id'].'?force=true&api_key='.$CFG->authy_api_key);
	
	$awaiting_token = 1;
}

$res = openssl_pkey_new(array("digest_alg"=>"sha256","private_key_bits"=>512,"private_key_type"=>OPENSSL_KEYTYPE_RSA));
openssl_pkey_export($res,$private);
$public = openssl_pkey_get_details($res);
$public = $public["key"];
$nonce = rand(2,99999);

$session_id = db_insert('sessions',array('session_key'=>$public,'user_id'=>$result[0]['id'],'nonce'=>$nonce,'session_time'=>date('Y-m-d H:i:s'),'session_start'=>date('Y-m-d H:i:s'),'awaiting'=>(($awaiting_token) ? 'Y' : 'N'),'ip'=>$ip1));
$return['session_id'] = $session_id;
$return['session_key'] = $private;
$return['nonce'] = $nonce;
$return['no_logins'] = $result[0]['no_logins'];
$return['message'] = ($awaiting_token) ? 'awaiting-token' : 'logged-in'; 

if (!$awaiting_token)
	db_delete('site_users_access',$result[0]['id'],'site_user');
else
	$return['attempts'] = $attempts;

echo json_encode($return);


?>