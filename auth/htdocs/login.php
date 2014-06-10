<?php 
include '../cfg.php';

$user1 = ereg_replace("[^0-9]","",$_REQUEST['user']);
$pass1 = ereg_replace("[^0-9a-zA-Z!@#$%&*?\.\-\_]","",$_REQUEST['pass']);

if (!$user1 || !$pass1)
	$invalid_login = 1;

$result = db_query_array("SELECT * FROM site_users WHERE user = '$user1' AND pass = '$pass1'");
if (!$result)
	$invalid_login = 1;

if ($invalid_login) {
	echo json_encode(array('error'=>'invalid-login'));
	exit;
}

if (($result[0]['verified_authy'] == 'Y' || $result[0]['verified_google'] == 'Y') && $result[0]['dont_ask_30_days'] != 'Y') {
	if ($result[0]['using_sms'] == 'Y')
		shell_exec('curl https://api.authy.com/protected/json/sms/'.$result[0]['authy_id'].'?api_key='.$CFG->authy_api_key);
	
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

echo json_encode($return);


?>