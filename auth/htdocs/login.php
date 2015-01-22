<?php 
include '../lib/common.php';

$user1 = preg_replace("/[^0-9]/","",$_REQUEST['user']);
$pass1 = preg_replace($CFG->pass_regex,"",$_REQUEST['pass']);
$invalid_login = false;
$awaiting_token = false;

if (!$user1 || !$pass1)
	$invalid_login = 1;

$result = db_query_array("SELECT site_users.*, site_users_access.start AS `start`, site_users_access.last AS `last`, site_users_access.attempts AS attempts FROM site_users LEFT JOIN site_users_access ON (site_users_access.site_user = site_users.id) WHERE site_users.user = '$user1'");
if (!$result)
	$invalid_login = 1;
elseif ($result) {
	if (empty($result[0]['start']) || ($result[0]['start'] - time() >= 3600)) {
		if ($result[0]['start'])
			db_update('site_users_access',$result[0]['id'],array('attempts'=>'1','start'=>time(),'last'=>time()),'site_user');
		else
			db_insert('site_users_access',array('attempts'=>'1','start'=>time(),'last'=>time(),'site_user'=>$result[0]['id']));
	}
	else {
		$attempts = $result[0]['attempts'] + 1;
		$timeout = pow(2,$attempts);
		
		if ($attempts == 3) {
			$CFG->language = ($result[0]['last_lang']) ? $result[0]['last_lang'] : 'en';
			$email = SiteEmail::getRecord('bruteforce-notify');
			Email::send($CFG->support_email,$result[0]['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$result[0]);
		}
		
		trigger_error(print_r(array($timeout,(time() - $result[0]['last'])),1),E_USER_WARNING);
		db_update('site_users_access',$result[0]['id'],array('attempts'=>$attempts,'last'=>time()),'site_user');
		
		if (time() - $result[0]['last'] <= $timeout)
			$invalid_login = 1;
		
	}
	
	if (!$invalid_login)
		$invalid_login = (!Encryption::verify_hash($pass1,$result[0]['pass']));
}


if ($invalid_login) {
	echo json_encode(array('error'=>'invalid-login'));
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

$session_id = db_insert('sessions',array('session_key'=>$public,'user_id'=>$result[0]['id'],'nonce'=>$nonce,'session_time'=>date('Y-m-d H:i:s'),'session_start'=>date('Y-m-d H:i:s'),'awaiting'=>(($awaiting_token) ? 'Y' : 'N')));
$return['session_id'] = $session_id;
$return['session_key'] = $private;
$return['nonce'] = $nonce;
$return['no_logins'] = $result[0]['no_logins'];
$return['message'] = ($awaiting_token) ? 'awaiting-token' : 'logged-in'; 

db_delete('site_users_access',$result[0]['id'],'site_user');

echo json_encode($return);


?>