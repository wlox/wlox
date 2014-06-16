<?php
class User {
	private static $logged_in;
	public static $awaiting_token, $info;
	
	function logIn($user,$pass) {
		global $CFG;
		
		$ch = curl_init($CFG->auth_login_url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,array('user'=>$user,'pass'=>$pass));
		
		$result1 = curl_exec($ch);
		$result = json_decode($result1,true);
		curl_close($ch);
		
		if (!$result || $result['error']) {
			Errors::add($CFG->login_invalid);
			return false;
		}
		elseif ($result['message']) {
			$_SESSION['session_id'] = $result['session_id'];
			$_SESSION['session_key'] = $result['session_key'];
			$_SESSION['nonce'] = $result['nonce'];
			return $result;
		}
	}
	
	function verifyLogin() {
		global $CFG;
		
		if (!($_SESSION['session_id']) > 0)
			return false;
		
		$commands['session_id'] = $_SESSION['session_id'];
		$commands['nonce'] = $_SESSION['nonce'];
		$commands['commands'] = json_encode($commands);
		
		openssl_sign($commands['commands'],$signature,$_SESSION['session_key']);
		$commands['signature'] = $signature;
		
		$ch = curl_init($CFG->auth_verify_login_url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$commands);
		
		$result1 = curl_exec($ch);
		$result = json_decode($result1,true);
		curl_close($ch);
		//echo $commands['nonce'].',';
		//print_r($result);

		if (!$result || $result['error']) {
			Errors::add($CFG->login_invalid);
			$_SESSION = array();
			return false;
		}
		
		if ($result['message'] == 'awaiting-token') {
			self::$awaiting_token = true;
			return true;
		}
		else {
			self::$info = $result['info'];
			self::$logged_in = true;
			self::updateNonce();
			return true;
		}
	}
	
	function verifyToken($token,$dont_ask=false) {
		global $CFG;
		
		if (!self::$awaiting_token)
			return false;
	
		$commands['session_id'] = $_SESSION['session_id'];
		$commands['nonce'] = $_SESSION['nonce'];
		$commands['token'] = $token;
		$commands['dont_ask'] = $dont_ask;
		$commands['commands'] = json_encode($commands);
		
		openssl_sign($commands['commands'],$signature,$_SESSION['session_key']);
		$commands['signature'] = $signature;
	
		$ch = curl_init($CFG->auth_verify_token_url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$commands);
	
		$result1 = curl_exec($ch);
		$result = json_decode($result1,true);
		curl_close($ch);
		
		if ($result['authy-errors'] == 'false') {
			Errors::merge($result['authy-errors']);
			return false;
		}
		elseif ($result['error'] == 'security-incorrect-token') {
			Errors::add(Lang::string('security-incorrect-token'));
			return false;
		}
		elseif (!$result || $result['error']) {
			Errors::add(Lang::string('security-com-error'));
			return false;
		}
		
		if ($result['message'] == 'OK') {
			self::$info = $result['info'];
			self::$logged_in = true;
			self::updateNonce();
			return true;
		}
	}
	
	function isLoggedIn() {
		return self::$logged_in;
	}
	
	function logOut($logout) {
		if ($logout) {
			API::add('User','logOut',array($_SESSION['session_id']));
			API::send();
			
			unset($_SESSION);
			
			self::$logged_in = false;
			self::$info = false;
		}
	}
	
	function updateNonce() {
		if (!self::$logged_in)
			return false;
		
		$_SESSION['nonce']++;
		echo 'Session nonce: '.$_SESSION['nonce'].'<br>';
		return true;
	}
	
	function sendSMS($authy_id=false) {
		global $CFG;
		
		API::add('User','sendSMS',array($authy_id));
		$query = API::send();
		$response = $query['User']['sendSMS']['results'][0];
		
		if (!$response || !is_array($response))
			Errors::add(Lang::string('security-com-error'));
		elseif ($response['success'] === false)
			Errors::merge($response['errors']);
		else {
			return true;
		}
	}
}
?>