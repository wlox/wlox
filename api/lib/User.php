<?php 
class User {
	public static $info, $on_hold;
	
	function setInfo($info) {
		User::$info = $info;
	}
	
	function getInfo($session_id=false) {
		global $CFG;
		
		$session_id = preg_replace("/[^0-9]/", "",$session_id);
		
		if (!($session_id > 0) || !$CFG->session_active)
			return false;
	
		$result = db_query_array('SELECT site_users.first_name,site_users.last_name,site_users.pass,site_users.country,site_users.email FROM sessions LEFT JOIN site_users ON (sessions.user_id = site_users.id) WHERE sessions.session_id = '.$session_id);
		return $result[0];
	}
	
	function logOut($session_id=false) {
		if (!($session_id > 0))
			return false;
		
		$session_id = preg_replace("/[^0-9]/", "",$session_id);
		
		return db_delete('sessions',$session_id,'session_id');
	}
	
	function getOnHold($for_update=false,$user_id=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$user_info = ($user_id > 0) ? DB::getRecord('site_users',$user_id,0,1,false,false,false,$for_update) : User::$info;
		$user_fee = FeeSchedule::getRecord($user_info['fee_schedule']);
		$lock = ($for_update) ? 'FOR UPDATE' : '';
	
		$sql = " SELECT currencies.currency AS currency, requests.amount AS amount FROM requests LEFT JOIN currencies ON (currencies.id = requests.currency) WHERE requests.site_user = ".$user_info['id']." AND requests.request_type = {$CFG->request_widthdrawal_id} AND (requests.request_status = {$CFG->request_pending_id} OR requests.request_status = {$CFG->request_awaiting_id}) ".$lock;
		$result = db_query_array($sql);
		if ($result) {
			foreach ($result as $row) {
				$on_hold[$row['currency']]['withdrawal'] += floatval($row['amount']);
				$on_hold[$row['currency']]['total'] += floatval($row['amount']);
			}
		}
	
		$sql = " SELECT currencies.currency AS currency, orders.fiat AS amount, orders.btc AS btc_amount, orders.order_type AS type FROM orders LEFT JOIN currencies ON (currencies.id = orders.currency) WHERE orders.site_user = ".$user_info['id']." ".$lock;
		$result = db_query_array($sql);
		if ($result) {
			foreach ($result as $row) {
				if ($row['type'] == $CFG->order_type_bid) {
					$on_hold[$row['currency']]['order'] += floatval($row['amount']) + (floatval($row['amount']) * ($user_fee['fee'] * 0.01));
					$on_hold[$row['currency']]['total'] += floatval($row['amount']) + (floatval($row['amount']) * ($user_fee['fee'] * 0.01));
				}
				else {
					$on_hold['BTC']['order'] += floatval($row['btc_amount']);
					$on_hold['BTC']['total'] += floatval($row['btc_amount']);
				}
			}
		}
		self::$on_hold = $on_hold;
		return $on_hold;
	}
	
	function getAvailable() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
	
		self::$on_hold = (is_array(self::$on_hold)) ? self::$on_hold : self::getOnHold();
		if ($CFG->currencies) {
			$available['BTC'] = User::$info['btc'] - self::$on_hold['BTC']['total'];
			foreach ($CFG->currencies as $currency) {
				if (User::$info[strtolower($currency['currency'])] - self::$on_hold[$currency['currency']]['total'] == 0)
					continue;
	
				$available[$currency['currency']] = User::$info[strtolower($currency['currency'])] - self::$on_hold[$currency['currency']]['total'];
			}
		}
		return $available;
	}
	
	function hasCurrencies() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$found = false;
		if (!(User::$info['btc'] > 0)) {
			foreach ($CFG->currencies as $currency => $info) {
				if (User::$info[strtolower($currency)] > 0) {
					$found = true;
					break;
				}
			}
		}
		else
			$found = true;
		
		return $found;
	}
	
	function getVolume() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
	
		$sql = "SELECT SUM(transactions.btc * currencies.usd) AS volume FROM transactions
		LEFT JOIN currencies ON (currencies.id = {$CFG->btc_currency_id})
		WHERE (site_user = ".User::$info['id']." OR site_user1 = ".User::$info['id'].")
				AND transactions.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
				LIMIT 0,1";
		$result = db_query_array($sql);
			return $result[0]['volume'];
	}
	
	function getNewId() {
		$sql = 'SELECT FLOOR(10000000 + RAND() * 89999999) AS random_num
				FROM site_users
				WHERE "random_num" NOT IN (SELECT user FROM site_users)
				LIMIT 1 ';
		$result = db_query_array($sql);
		return $result[0]['random_num'];
	}
	
	function randomPassword($length = 8) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*?";
		$password = substr(str_shuffle($chars),0,$length);
		return $password;
	}
	
	function sendSMS($authy_id=false) {
		global $CFG;
		
		$authy_id = preg_replace("/[^0-9]/", "",$authy_id);
		
		if (!($CFG->session_active || $CFG->session_locked))
			return false;
		
		$authy_id = ($authy_id > 0) ? $authy_id : User::$info['authy_id'];
		$response = shell_exec('curl https://api.authy.com/protected/json/sms/'.$authy_id.'?api_key='.$CFG->authy_api_key);
		$response1 = json_decode($response,true);
		
		return $response1;
	
		/*
		if (!$response || !is_array($response1))
			Errors::add(Lang::string('security-com-error'));
		elseif ($response1['success'] === false)
			Errors::merge($response1['errors']);
		else {
			return true;
		}
		*/
	}
	
	function confirmToken($token,$authy_id=false) {
		global $CFG;
		
		if (!($CFG->session_active || $CFG->session_locked))
			return false;
		
		$token1 = preg_replace("/[^0-9]/", "",$token);
		$authy_id1 = preg_replace("/[^0-9]/", "",$authy_id);
		$authy_id = ($authy_id > 0) ? $authy_id : User::$info['authy_id'];
		
		if (!($token1 > 0) || !($authy_id > 0))
			return false;
		
		
		/*
		if (!($token1 > 0))
			Errors::add(Lang::string('security-no-token'));
		*/
	
		//if (!is_array(Errors::$errors)) {
			$authy_id = ($authy_id > 0) ? $authy_id : User::$info['authy_id'];
			$response = shell_exec('curl "https://api.authy.com/protected/json/verify/'.$token.'/'.$authy_id.'?api_key='.$CFG->authy_api_key.'"');
			$response1 = json_decode($response,true);
			
			return $response1;
	
			/*
			if (!$response || !is_array($response1))
				Errors::add(Lang::string('security-com-error'));
			if ($response1['success'] === false)
				Errors::merge($response1['errors']);
	
			if (!is_array(Errors::$errors)) {
				return true;
			}
		}
		*/
	}
	
	function disableNeverLoggedIn($pass) {
		$pass = preg_replace("/[^0-9a-zA-Z!@#$%&*?\.\-\_]/", "",$pass);
		if (strlen($pass) < 8)
			return false;
		
		return db_update('site_users',User::$info['id'],array('no_logins'=>'N','pass'=>$pass));
	}
	
	function firstLoginPassChange($pass) {
		global $CFG;
		
		$pass = preg_replace("/[^0-9a-zA-Z!@#$%&*?\.\-\_]/", "",$pass);
		
		if (!$CFG->session_active || strlen($pass) < 8 || User::$info['no_logins'] != 'Y')
			return false;
		
		return db_update('site_users',User::$info['id'],array('pass'=>$pass));
	}
	
	function userExists($email) {
		$email = preg_replace("/[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/", "",$email);
		
		if (!$email)
			return false;
		
		$sql = "SELECT id FROM site_users WHERE email = '$email'";
		$result = db_query_array($sql);
		
		if ($result)
			return $result[0]['id'];
		else
			return false;
	}
	
	function resetUser($id) {
		global $CFG;
		
		$id = preg_replace("/[^0-9]/", "",$id);
		
		if (!($id > 0))
			return false;
		
		$user = DB::getRecord('site_users',$id,0,1);
		if (!$user)
			return false;
		
		$new_id = self::getNewId();
		$user['new_user'] = $new_id;
		$user['new_password'] = self::randomPassword(12);
		db_update('site_users',$id,array('user'=>$user['new_user'],'pass'=>$user['new_password'],'no_logins'=>'Y'));
		
		$email = SiteEmail::getRecord('forgot');
		Email::send($CFG->form_email,$user['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$user);
	}
	
	function registerNew($info) {
		global $CFG;
		
		if (!is_array($info))
			return false;

		$new_id = self::getNewId();
		if ($new_id > 0) {
			$info['first_name'] = preg_replace("/[^\da-z ]/i", "",$info['first_name']);
			$info['last_name'] = preg_replace("/[^\da-z ]/i", "",$info['last_name']);
			$info['country'] = preg_replace("/[^0-9]/", "",$info['country']);
			$info['email'] = preg_replace("/[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/", "",$info['email']);
			$info['user'] = $new_id;
			$info['pass'] = self::randomPassword(12);
			$info['date'] = date('Y-m-d H:i:s');
			$info['confirm_withdrawal_email_btc'] = 'Y';
			$info['confirm_withdrawal_email_bank'] = 'Y';
			$info['notify_deposit_btc'] = 'Y';
			$info['notify_deposit_bank'] = 'Y';
			$info['notify_login'] = 'Y';
			$info['no_logins'] = 'Y';
			$info['fee_schedule'] = $CFG->default_fee_schedule_id;
			unset($info['terms']);
			
			$record_id = db_insert('site_users',$info);
		
			require_once('../lib/easybitcoin.php');
			$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
			$new_address = $bitcoin->getnewaddress($CFG->bitcoin_accountname);
			db_insert('bitcoin_addresses',array('address'=>$new_address,'site_user'=>$record_id,'date'=>date('Y-m-d H:i:s')));
		
			$email = SiteEmail::getRecord('register');
			Email::send($CFG->form_email,$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$info);
		
			$email = SiteEmail::getRecord('register-notify');
			$info['pass'] = false;
			Email::send($CFG->form_email,$CFG->accounts_email,$email['title'],$CFG->form_email_from,false,$email['content'],$info);
			return true;
		}
	}
	
	function registerAuthy($cell,$country_code) {
		global $CFG;
		
		$cell = preg_replace("/[^0-9]/", "",$cell);
		$country_code = preg_replace("/[^0-9]/", "",$country_code);
		
		if (!$CFG->session_active || User::$info['verified_authy'] == 'Y')
			return false;
		
		$response = shell_exec("
				curl https://api.authy.com/protected/json/users/new?api_key=$CFG->authy_api_key \
				-d user[email]='".User::$info['email']."' \
				-d user[cellphone]='$cell' \
				-d user[country_code]='$country_code'");
		$response1 = json_decode($response,true);
		return $response1;
	}
	
	function enableAuthy($cell,$country_code,$authy_id,$using_sms) {
		global $CFG;
		
		$cell = preg_replace("/[^0-9]/", "",$cell);
		$country_code = preg_replace("/[^0-9]/", "",$country_code);
		$authy_id = preg_replace("/[^0-9]/", "",$authy_id);
		
		if (!$CFG->session_active || User::$info['verified_authy'] == 'Y' || User::$info['verified_google'] == 'Y')
			return false;
		
		db_update('site_users',User::$info['id'],array('tel'=>$cell,'country_code'=>$country_code,'authy_requested'=>'Y','verified_authy'=>'N','authy_id'=>$authy_id,'using_sms'=>$using_sms,'google_2fa_code'=>'','confirm_withdrawal_2fa_btc'=>'Y','confirm_withdrawal_2fa_bank'=>'Y'));
	}
	
	function enableGoogle2fa($cell,$country_code) {
		global $CFG;
	
		$cell = preg_replace("/[^0-9]/", "",$cell);
		$country_code = preg_replace("/[^0-9]/", "",$country_code);
	
		if (!$CFG->session_active || User::$info['verified_authy'] == 'Y' || User::$info['verified_google'] == 'Y')
			return false;
	
		$key = Google2FA::generate_secret_key();
		if (!$key)
			return false;
		
		$result = db_update('site_users',User::$info['id'],array('tel'=>$cell,'country_code'=>$country_code,'google_2fa_code'=>$key,'verified_google'=>'N','using_sms'=>'N','authy_id'=>'','confirm_withdrawal_2fa_btc'=>'Y','confirm_withdrawal_2fa_bank'=>'Y'));
		if ($result)
			return $key;
	}
	
	function getGoogleSecret() {
		global $CFG;
		
		if (!($CFG->session_active) || User::$info['verified_google'] == 'Y')
			return false;
		
		return array('secret'=>User::$info['google_2fa_code'],'label'=>$CFG->exchange_name);
	}
	
	function verifiedAuthy() {
		global $CFG;
	
		if (!($CFG->session_active && $CFG->token_verified && $CFG->email_2fa_verified) || User::$info['verified_google'] == 'Y')
			return false;
	
		return db_update('site_users',User::$info['id'],array('verified_authy'=>'Y'));
	}
	
	function verifiedGoogle() {
		global $CFG;
	
		if (!($CFG->session_active && $CFG->email_2fa_verified) || User::$info['verified_authy'] == 'Y')
			return false;
			
		return db_update('site_users',User::$info['id'],array('verified_google'=>'Y'));
	}
	
	function disable2fa() {
		global $CFG;
		
		if (!($CFG->session_active && $CFG->token_verified))
			return false;

		return db_update('site_users',User::$info['id'],array('google_2fa_code'=>'','verified_google'=>'N','using_sms'=>'N','authy_id'=>'','verified_authy'=>'N'));
	}
	
	function updatePersonalInfo($info) {
		global $CFG;

		if (!($CFG->session_active && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;

		if (!is_array($info))
			return false;

		$update['pass'] = preg_replace("/[^0-9a-zA-Z!@#$%&*?\.\-\_]/", "",$info['pass']);
		$update['first_name'] = preg_replace("/[^\da-z ]/i", "",$info['first_name']);
		$update['last_name'] = preg_replace("/[^\da-z ]/i", "",$info['last_name']);
		$update['country'] = preg_replace("/[^0-9]/", "",$info['country']);
		$update['email'] = preg_replace("/[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/", "",$info['email']);

		if (strlen($update['pass']) < 8 || !$update['first_name'] || !$update['last_name'] || !$update['email'])
			return false;

		return db_update('site_users',User::$info['id'],$update);
	}
	
	function updateSettings($confirm_withdrawal_2fa_btc1,$confirm_withdrawal_email_btc1,$confirm_withdrawal_2fa_bank1,$confirm_withdrawal_email_bank1,$notify_deposit_btc1,$notify_deposit_bank1,$notify_login1) {
		global $CFG;
		
		if (!($CFG->session_active && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;
			
		$confirm_withdrawal_2fa_btc2 = ($confirm_withdrawal_2fa_btc1) ? 'Y' : 'N';
		$confirm_withdrawal_email_btc2 = ($confirm_withdrawal_email_btc1) ? 'Y' : 'N';
		$confirm_withdrawal_2fa_bank2 = ($confirm_withdrawal_2fa_bank1) ? 'Y' : 'N';
		$confirm_withdrawal_email_bank2 = ($confirm_withdrawal_email_bank1) ? 'Y' : 'N';
		$notify_deposit_btc2 = ($notify_deposit_btc1) ? 'Y' : 'N';
		$notify_deposit_bank2 = ($notify_deposit_bank1) ? 'Y' : 'N';
		$notify_login2 = ($notify_login1) ? 'Y' : 'N';
			
		return db_update('site_users',User::$info['id'],array('confirm_withdrawal_2fa_btc'=>$confirm_withdrawal_2fa_btc2,'confirm_withdrawal_email_btc'=>$confirm_withdrawal_email_btc2,'confirm_withdrawal_2fa_bank'=>$confirm_withdrawal_2fa_bank2,'confirm_withdrawal_email_bank'=>$confirm_withdrawal_email_bank2,'notify_deposit_btc'=>$notify_deposit_btc2,'notify_deposit_bank'=>$notify_deposit_bank2,'notify_login'=>$notify_login2));
	}
	
	function deactivateAccount() {
		global $CFG;
		
		if (!($CFG->session_active && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;

		$found = false;
		if (!(User::$info['btc'] > 0)) {
			foreach ($CFG->currencies as $currency => $info) {
				if (User::$info[strtolower($currency)] > 0) {
					$found = true;
					break;
				}
			}
		}
		else
			$found = true;

		if (!$found)
			return db_update('site_users',User::$info['id'],array('deactivated'=>'Y'));
	}
	
	function reactivateAccount() {
		global $CFG;

		if (!(($CFG->session_locked || $CFG->session_active) && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;
	
		return db_update('site_users',User::$info['id'],array('deactivated'=>'N'));
	}
	
	function lockAccount() {
		global $CFG;
	
		if (!($CFG->session_active && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;
	
		return db_update('site_users',User::$info['id'],array('locked'=>'Y'));
	}
	
	function unlockAccount() {
		global $CFG;
	
		if (!(($CFG->session_locked || $CFG->session_active) && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;
	
		return db_update('site_users',User::$info['id'],array('locked'=>'N'));
	}
	
	function settingsEmail2fa($request=false,$security_page=false) {
		global $CFG;

		if (!($CFG->session_locked || $CFG->session_active))
			return false;
		
		$request_id = db_insert('change_settings',array('date'=>date('Y-m-d H:i:s'),'request'=>base64_encode(serialize($request))));
		if ($request_id > 0) {
			$vars = User::$info;
			$vars['authcode'] = urlencode(Encryption::encrypt($request_id));
		
			if (!$security_page)
				$email = SiteEmail::getRecord('settings-auth');
			else
				$email = SiteEmail::getRecord('security-auth');
				
			return Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$vars);
		}
	}
	
	function getSettingsChangeRequest($settings_change_id1) {
		global $CFG;

		if (!$settings_change_id1)
			return false;
		
		$request_id = Encryption::decrypt(urldecode($settings_change_id1));
		if (!($request_id > 0))
			return false;
		
		$change_request = DB::getRecord('change_settings',$request_id,0,1);
		return $change_request['request'];

	}
	
	function notifyLogin($ipaddress) {
		global $CFG;
		
		if (!$CFG->session_active || User::$info['notify_login'] != 'Y')
			return false;
		
		$ipaddress1 = preg_replace("/[^0-9\.]/", "",$ipaddress);
		
		db_insert('history',array('date'=>date('Y-m-d H:i:s'),'ip'=>$ipaddress1,'history_action'=>$CFG->history_login_id,'site_user'=>User::$info['id']));
		
		$email = SiteEmail::getRecord('login-notify');
		$info = User::$info;
		$info['ipaddress'] = $ipaddress1;

		Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$info);
	}
	
	function getCountries() {
		$sql = "SELECT * FROM iso_countries ORDER BY name ASC";
		return db_query_array($sql);
	}
}

?>