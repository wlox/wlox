<?php 
class API{
	private static $commands,$nonce,$token,$settings_change_id,$request_id,$api_signature,$api_key,$api_update_nonce;
	
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
	
	function apiKey($api_key) {
		API::$api_key = $api_key;
	}
	
	function apiSignature($api_signature) {
		API::$api_signature = $api_signature;
	}
	
	function apiUpdateNonce() {
		API::$api_update_nonce = 1;
	}
	
	function send($nonce=false) {
		global $CFG;
		
		if (!is_array(API::$commands))
			return false;

		$commands['session_id'] = $_SESSION['session_id'];
		$commands['nonce'] = ($nonce > 0) ? $nonce : $_SESSION['nonce'];
		$commands['lang'] = $CFG->language;
		$commands['commands'] = json_encode(API::$commands);
		$commands['token'] = API::$token;
		$commands['settings_change_id'] = bin2hex(API::$settings_change_id);
		$commands['request_id'] = API::$request_id;
		$commands['ip'] = self::getUserIp();

		if (User::isLoggedIn()) openssl_sign($commands['commands'],$signature,$_SESSION['session_key']);
		$commands['signature'] = bin2hex($signature);
		
		if (API::$api_key) {
			$commands['api_key'] = API::$api_key;
			$commands['api_signature'] = API::$api_signature;
			$commands['api_update_nonce'] = API::$api_update_nonce;
		}
		
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
	
	function getUserIp($force_string=null) {
		$ip_addresses = array();
		$ip_elements = array(
				'HTTP_X_FORWARDED_FOR', 'HTTP_FORWARDED_FOR',
				'HTTP_X_FORWARDED', 'HTTP_FORWARDED',
				'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_CLUSTER_CLIENT_IP',
				'HTTP_X_CLIENT_IP', 'HTTP_CLIENT_IP',
				'REMOTE_ADDR'
		);
		
		foreach ( $ip_elements as $element ) {
			if(isset($_SERVER[$element])) {
				if (!is_string($_SERVER[$element]) )
					continue;
				
				$address_list = explode(',',$_SERVER[$element]);
				$address_list = array_map('trim',$address_list);

				foreach ($address_list as $x)
					$ip_addresses[] = $x;
			}
		}
		
		if (count($ip_addresses) == 0)
			return false;
		else
			return $ip_addresses[0];
	}
}

?>