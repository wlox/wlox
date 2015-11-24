<?php 
class API{
	private static $commands,$nonce,$token,$settings_change_id,$request_id,$api_signature,$api_key,$api_update_nonce,$raw_params_json;
	
	static public function add($classname,$method,$arguments=false) {
		API::$commands[$classname][][$method] = $arguments;
	}
	
	static public function token($token) {
		API::$token = $token;
	}
	
	static public function settingsChangeId($settings_change_id) {
		API::$settings_change_id = $settings_change_id;
	}
	
	static public function requestId($request_id) {
		API::$request_id = $request_id;
	}
	
	static public function apiKey($api_key) {
		API::$api_key = $api_key;
	}
	
	static public function apiSignature($api_signature,$raw_params_json) {
		API::$api_signature = $api_signature;
		API::$raw_params_json = $raw_params_json;
	}
	
	static public function apiUpdateNonce() {
		API::$api_update_nonce = 1;
	}
	
	static public function send($nonce=false) {
		global $CFG;
		
		if (!is_array(API::$commands))
			return false;

		$commands['lang'] = (isset($CFG->language)) ? $CFG->language : false;
		$commands['commands'] = json_encode(API::$commands);
		$commands['token'] = API::$token;
		$commands['settings_change_id'] = bin2hex(API::$settings_change_id);
		$commands['request_id'] = API::$request_id;
		$commands['ip'] = self::getUserIp();

		if (isset($_SESSION['session_key']) && empty($CFG->public_api)) {
			$commands['session_id'] = $_SESSION['session_id'];
			$commands['nonce'] = ($nonce > 0) ? $nonce : $_SESSION['nonce'];
			openssl_sign($commands['commands'],$signature,$_SESSION['session_key']);
			$commands['signature'] = bin2hex($signature);
			$commands['update_nonce'] = API::$api_update_nonce;
		}
		
		if (API::$api_key) {
			$commands['nonce'] = $nonce;
			$commands['api_key'] = API::$api_key;
			$commands['api_signature'] = API::$api_signature;
			$commands['raw_params_json'] = API::$raw_params_json;
			$commands['api_update_nonce'] = API::$api_update_nonce;
		}
		
		$ch = curl_init($CFG->api_url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$commands);
		curl_setopt($ch,CURLOPT_FRESH_CONNECT,TRUE);
		
		$result1 = curl_exec($ch);
		$result = json_decode($result1,true);
		curl_close($ch);
		
		if (!empty($result['nonce_updated']))
			User::updateNonce();
		
		API::$commands = array();
		return $result;
	}
	
	static public function getUserIp() {
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