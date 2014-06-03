<?php 


// get built-in php classes
$system_classes = get_declared_classes();
$system_classes[] = 'DB';

include '../cfg/cfg.php';

$session_id1 = preg_replace("/[^0-9]/","",$_POST['session_id']);
$signature1 = $_POST['signature'];
$nonce1 = preg_replace("/[^0-9]/","",$_POST['nonce']);
$token1 = preg_replace("/[^0-9]/","",$_POST['token']);
$settings_change_id1 = $_REQUEST['settings_change_id'];
$request_id1 = $_REQUEST['request_id'];
$CFG->language = preg_replace("/[^a-z]/","",$_POST['lang']);

// commands is of form array('Class1'=>array('method1'=>array('arg1'=>blah,'arg2'=>bob)));
$commands = json_decode($_POST['commands'],true);


// authenticate session
if ($session_id1) {
	$result = db_query_array('SELECT sessions.session_key AS session_key, site_users.* FROM sessions LEFT JOIN site_users ON (sessions.user_id = site_users.id) WHERE sessions.session_id = '.$session_id1.' AND sessions.nonce = '.$nonce1);
	//$result = db_query_array('SELECT sessions.session_key AS session_key, site_users.* FROM sessions LEFT JOIN site_users ON (sessions.user_id = site_users.id) WHERE sessions.session_id = '.$session_id1.' ');
	if ($result) {
		if (openssl_verify($_POST['commands'],$signature1,$result[0]['session_key'])) {
			User::setInfo($result[0]);
			db_update('sessions',$session_id1,array('nonce'=>($nonce1 + 1)),'session_id');
			
			if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y') {
				$return['error'] = 'account-locked-or-deactivated';
				$CFG->session_locked = true;
			}
			else {
				$CFG->session_active = true;
			}
		}
		else
			$return['error'] = 'invalid-signature';
	}
	else 
		$return['error'] = 'session-not-found';
}

// verify token
if ($token1 > 0 && $result[0]['authy_id'] > 0) {
	$response = shell_exec('curl "https://api.authy.com/protected/json/verify/'.$token1.'/'.$result[0]['authy_id'].'?api_key='.$CFG->authy_api_key.'"');
	$response1 = json_decode($response,true);
	
	if (!$response || !is_array($response1)) {
		$return['error'] = 'security-com-error';
	}
	elseif ($response1['success'] === false) {
		$return['error'] = 'authy-errors';
		$return['authy_errors'] = $response1['errors'];
	}
	else {
		$CFG->token_verified = true;
	}
}

// email 2fa for settings changes
if ($settings_change_id1 && $CFG->session_active) {
	$request_id = Encryption::decrypt(urldecode($settings_change_id1));
	if ($request_id > 0) {
		$change_request = DB::getRecord('change_settings',$request_id,0,1);
		db_delete('change_settings',$request_id);
		$CFG->email_2fa_verified = true;
	}
	else
		$return['error'] = 'request-expired';
}

if (is_array($commands)) {
	foreach ($commands as $classname => $methods_arr) {
		if (in_array($classname,$system_classes))
			continue;
		
		if (is_array($methods_arr)) {
			foreach ($methods_arr as $methods) {
				if (is_array($methods)) {
					foreach ($methods as $method => $args) {
						$classname = preg_replace("/[^0-9a-zA-Z_]/","",$classname);
						$method = preg_replace("/[^0-9a-zA-Z_]/","",$method);

						if (is_array($args)) {
							foreach ($args as $i => $arg) {
								if (!is_array($args) && $method != 'getSettingsChangeRequest')
									$args[$i] = preg_replace("/[^0-9a-zA-Z!@#$%&*?\.\-_]/", "",$arg);
							}
						}
						else {
							$args = array();
						}
						
						$response = call_user_func_array(array($classname,$method),$args);
						$return[$classname][$method]['results'][] = $response;
					}
				}
			}
		}
	}
}

if (is_array($return))
	echo json_encode($return);

