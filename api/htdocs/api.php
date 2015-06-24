<?php 
// get built-in php classes
$system_classes = get_declared_classes();
$system_classes[] = 'DB';

include '../lib/common.php';

$session_id1 = (!empty($_POST['session_id'])) ? preg_replace("/[^0-9]/","",$_POST['session_id']) : false;
$signature1 = (!empty($_POST['signature'])) ? hex2bin($_POST['signature']) : false;
$nonce1 = (!empty($_POST['nonce'])) ? preg_replace("/[^0-9]/","",$_POST['nonce']) : false;
$token1 = (!empty($_POST['token'])) ? preg_replace("/[^0-9]/","",$_POST['token']) : false;
$settings_change_id1 = (!empty($_POST['settings_change_id'])) ? $_REQUEST['settings_change_id'] : false;
$request_id1 = (!empty($_POST['request_id'])) ? $_REQUEST['request_id'] : false;
$api_key1 = (!empty($_POST['api_key'])) ? preg_replace("/[^0-9a-zA-Z]/","",$_POST['api_key']) : false;
$api_signature1 = (!empty($_POST['api_signature'])) ? preg_replace("/[^0-9a-zA-Z]/","",$_POST['api_signature']) : false;
$raw_params_json = (!empty($_POST['raw_params_json'])) ? $_POST['raw_params_json'] : false;
$update_nonce = false;
$awaiting_token = false;

$CFG->language = (!empty($_POST['lang']) && in_array(strtolower($_POST['lang']),array('en','es','ru','zh'))) ? strtolower($_POST['lang']) : false;
$CFG->client_ip = (!empty($_POST['ip'])) ? preg_replace("/[^0-9\.]/","",$_POST['ip']) : false;
$CFG->session_id = $session_id1;
$CFG->session_locked = false;
$CFG->session_active = false;
$CFG->session_api = false;
$CFG->token_verified = false;
$CFG->email_2fa_verified = false;

// commands is of form array('Class1'=>array('method1'=>array('arg1'=>blah,'arg2'=>bob)));
$commands = (!empty($_POST['commands'])) ? json_decode($_POST['commands'],true) : false;

// authenticate session
if ($session_id1) {
	$cached = false;
	$nonce_invalid = false;
	if ($CFG->memcached) {
		$cached = $CFG->m->get('session_'.$session_id1);
		if ($cached && ($nonce1 >= ($cached['nonce'] + 5) || $nonce1 <= ($cached['nonce'] - 5))) {
			$nonce_invalid = true;
		}
	}
	
	if ($cached)
		$result = array($cached);
	else
		$result = db_query_array('SELECT sessions.nonce AS nonce ,sessions.session_key AS session_key, sessions.ip AS ip, sessions.awaiting AS awaiting, site_users.* FROM sessions LEFT JOIN site_users ON (sessions.user_id = site_users.id) WHERE sessions.session_id = '.$session_id1);
	
	$return['session'] = $session_id1;
	if ($nonce_invalid) {
		$return['error'] = 'invalid-nonce';
	}
	elseif (!empty($result)) {
		$awaiting_token = ($result[0]['awaiting'] == 'Y');
		if (!empty($_POST['commands']) && openssl_verify($_POST['commands'],$signature1,$result[0]['session_key'])) {
			User::setInfo($result[0]);
			$update_nonce = $_POST['update_nonce'];
			
			if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y') {
				$CFG->session_locked = true;
			}
			else {
				$CFG->session_active = true;
			}
			
			if (empty($CFG->language))
				$CFG->language = $result[0]['last_lang'];
		}
		else
			$return['error'] = 'invalid-signature';
	}
	else 
		$return['error'] = 'session-not-found';
}

// verify api key
if ($api_key1 && $api_signature1) {
	$cached = false;
	$nonce_invalid = false;
	if ($CFG->memcached) {
		$cached = $CFG->m->get('api_'.$api_key1);
		if ($cached && floatval(substr(strval($nonce1),0,10)) <= (floatval(substr(strval($cached['nonce']),0,10)) - 5)) {
			$nonce_invalid = true;
		}
	}
	
	if ($cached)
		$result = array($cached);
	else
		$result = db_query_array('SELECT api_keys.id AS key_id, api_keys.nonce AS nonce, api_keys.key AS api_key, api_keys.secret AS secret, api_keys.view AS p_view, api_keys.orders AS p_orders, api_keys.withdraw AS p_withdraw, site_users.* FROM api_keys LEFT JOIN site_users ON (api_keys.site_user = site_users.id) WHERE api_keys.key = "'.$api_key1.'"');
	
	if ($nonce_invalid) {
		$return['error'] = 'AUTH_INVALID_NONCE';
	}
	elseif ($result && !($result[0]['id'] > 0)) {
		$return['error'] = 'AUTH_USER_NOT_FOUND';
	}
	elseif (!empty($result)) {
		if ($raw_params_json) {
			$decoded = json_decode($raw_params_json,1);
			$decoded['api_key'] = $result[0]['api_key'];
			$decoded['nonce'] = intval($decoded['nonce']);
			unset($decoded['signature']);
		}
		
		$hash = hash_hmac('sha256',json_encode($decoded,JSON_NUMERIC_CHECK),$result[0]['secret']);
		if ($api_signature1 == $hash) {
			User::setInfo($result[0]);
			
			if (!empty($_REQUEST['api_update_nonce'])) {
				if ($CFG->memcached) {
					$result[0]['nonce'] = $nonce1;
					$CFG->m->set('api_'.$api_key1,$result[0],300);
				}
				else
					db_update('api_keys',$result[0]['key_id'],array('nonce'=>$nonce1));	
			}
			
			if (empty($CFG->language))
				$CFG->language = $result[0]['last_lang'];
				
			if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y') {
				$return['error'] = 'account-locked-or-deactivated';
				$CFG->session_locked = true;
			}
			else {
				$CFG->session_active = true;
				$CFG->session_api = true;
			}
		}
		else
			$return['error'] = 'AUTH_INVALID_SIGNATURE';
	}
	else
		$return['error'] = 'AUTH_INVALID_KEY';
}

// verify token
if ($token1 > 0) {
	$token_cache = array();
	if ($CFG->memcached) {
		$token_cache = $CFG->m->get('tokens');
		if (!$token_cache)
			$token_cache = array();
	}
	
	if (in_array($token1,$token_cache)) {
		$return['error'] = 'security-incorrect-token';
	}
	else {
		if ($token1 > 0 && !empty($result[0]['authy_id']) && $result[0]['authy_id'] > 0) {
			$response = shell_exec('curl "https://api.authy.com/protected/json/verify/'.$token1.'/'.$result[0]['authy_id'].'?api_key='.$CFG->authy_api_key.'"');
			$response1 = (!empty($response)) ? json_decode($response,true) : false;
		
			if (empty($response) || (empty($response1) || !is_array($response1))) {
				$return['error'] = 'security-com-error';
			}
			elseif (!empty($response1['errors']) || $response1['success'] === false || $response1['success'] === 'false') {
				$return['error'] = 'authy-errors';
				$return['authy_errors'] = $response1['errors'];
			}
			elseif (!empty($response1['success']) && ($response1['success'] == true || $response1['success'] == 'true')) {
				$CFG->token_verified = true;
			}
		}
		elseif ($token1 > 0 && $result[0]['google_2fa_code']) {
			$response = Google2FA::verify_key($result[0]['google_2fa_code'],$token1);
			if ($response)
				$CFG->token_verified = true;
			else
				$return['error'] = 'security-incorrect-token';
		}
		
		if ($CFG->memcached && !empty($CFG->token_verified)) {
			if (count($token_cache) > 1000)
				array_shift($token_cache);
			
			$token_cache[] = $token1;
			$CFG->m->set('tokens',$token_cache,0);
		}
	}
}

// email 2fa for settings changes
if ($settings_change_id1 && ($CFG->session_active || $CFG->session_locked)) {
	$request_id = Encryption::decrypt(hex2bin($settings_change_id1));
	if ($request_id > 0) {
		$change_request = DB::getRecord('change_settings',$request_id,0,1);
		if ($change_request) {
			db_delete('change_settings',$request_id);
			$CFG->email_2fa_verified = true;
		}
		else
			$return['error'] = 'request-expired';
	}
	else
		$return['error'] = 'request-expired';
}

/* Lang Key Selector */
$CFG->lang_table_key = $CFG->language;
if ($CFG->language == 'en')
	$CFG->lang_table_key = 'eng';
elseif ($CFG->language == 'es')
	$CFG->lang_table_key = 'esp';

if (is_array($commands) && empty($return['error'])) {
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

if ($update_nonce) {
	if ($CFG->memcached && empty($CFG->delete_cache) && !$awaiting_token) {
		$result[0]['nonce'] = $nonce1 + 1;
		$return['nonce_updated'] = $CFG->m->set('session_'.$session_id1,$result[0],300);
	}
	else
		$return['nonce_updated'] = db_update('sessions',$session_id1,array('nonce'=>($nonce1 + 1),'session_time'=>date('Y-m-d H:i:s')),'session_id');
}

if (is_array($return))
	echo json_encode($return);

