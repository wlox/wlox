<?php 
class API{
	private static $commands,$nonce,$token,$settings_change_id,$request_id;
	
	function add($classname,$method,$arguments=false) {
		API::$commands[$classname][][$method] = $arguments;
	}
	
	function token($token) {
		API::$token = $token;
	}
	
	function settingsChangeId($settings_change_id) {
		API::$settings_change_id = $settings_change_id;
	}
	
	function requestId($request_id) {
		API::$request_id = $request_id;
	}
	
	function send() {
		global $CFG;

		$commands['session_id'] = $_SESSION['session_id'];
		$commands['nonce'] = $_SESSION['nonce'];
		$commands['lang'] = $CFG->language;
		$commands['commands'] = json_encode(API::$commands);
		$commands['token'] = API::$token;
		$commands['settings_change_id'] = urlencode(API::$settings_change_id);
		$commands['request_id'] = API::$request_id;
		$commands['ip'] = ($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

		if (User::isLoggedIn()) openssl_sign($commands['commands'],$signature,$_SESSION['session_key']);
		$commands['signature'] = urlencode($signature);
		
		$ch = curl_init($CFG->api_url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$commands);
		curl_setopt($ch,CURLOPT_FRESH_CONNECT,TRUE);
		
		$result1 = curl_exec($ch);
		$result = json_decode($result1,true);
		curl_close($ch);
		
		if ($result['nonce_updated'])
			User::updateNonce();
		
		API::$commands = array();
		return $result;
	}
}

?>