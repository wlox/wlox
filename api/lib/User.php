<?php 
class User {
	public static $info, $on_hold;
	
	public static function setInfo($info) {
		User::$info = $info;
		if (!empty($info['id'])) {
			$balances = self::getBalances($info['id']);
			if ($balances) {
				foreach ($balances as $abbr => $row) {
					User::$info[$abbr] = $row;
				}
			}
		}
	}
	
	public static function getInfo($session_id=false) {
		global $CFG;
		
		$session_id = preg_replace("/[^0-9]/", "",$session_id);
		
		if (!($session_id > 0) || !$CFG->session_active)
			return false;
	
		$result = db_query_array('SELECT site_users.first_name,site_users.last_name,site_users.country,site_users.email, site_users.default_currency FROM sessions LEFT JOIN site_users ON (sessions.user_id = site_users.id) WHERE sessions.session_id = '.$session_id);
		return $result[0];
	}
	
	// currency_id can be array of ids
	public static function getBalances($user_id,$currencies=false,$for_update=false) {
		global $CFG;
		
		if (!($user_id > 0))
			return false;
		
		$sorted = array();
		if ($CFG->memcached && !$currencies) {
			$cached = $CFG->m->get('balances_'.$user_id);
			if (is_array($cached)) {
				if (!empty($cached))
					return $cached;
				else
					return false;
			}
		}

		if (!is_array($currencies) && $currencies > 0)
			$currencies = array($currencies);
		
		$sql = 'SELECT balance, currency FROM site_users_balances WHERE site_user = '.$user_id.' ';
		if (is_array($currencies)) {
			$sub_sql = array();
			foreach ($currencies as $id) {
				$sub_sql[] = ' currency = '.$id;
			}
			$sql .= ' AND ('.implode(' OR ',$sub_sql).')';
		}
		
		if ($for_update)
			$sql .= ' FOR UPDATE';
		
		$result = db_query_array($sql);
		if ($result) {		
			foreach ($result as $row) {
				$key = strtolower($CFG->currencies[$row['currency']]['currency']);
				$sorted[$key] = $row['balance'];
			}
		}
		
		if ($CFG->memcached && !$currencies)
			$CFG->m->set('balances_'.$user_id,$sorted,60);
		
		return $sorted;
	}
	
	public static function updateBalances($user_id,$currencies_balances) {
		global $CFG;
		
		if (!($user_id > 0) || empty($currencies_balances) || !is_array($currencies_balances))
			return false;
		
		$currencies_str = '(CASE currency ';
		$currency_ids = array();
		$del_keys = array();
		
		foreach ($currencies_balances as $curr_abbr => $balance) {
			$curr_info = $CFG->currencies[strtoupper($curr_abbr)];
			$currencies_str .= ' WHEN '.$curr_info['id'].' THEN '.$balance.' ';
			$currency_ids[] = $curr_info['id'];
		}
		$currencies_str .= ' END)';
		
		$sql = 'UPDATE site_users_balances SET balance = '.$currencies_str.' WHERE currency IN ('.implode(',',$currency_ids).') AND site_user = '.$user_id;
		$result = db_query($sql);
		
		if (!$result || $result < count($currencies_balances)) {
			$sql = 'SELECT currency FROM site_users_balances WHERE site_user = '.$user_id;
			$result = db_query_array($sql);
			$existing = array();
			if ($result) {
				foreach ($result as $row) {
					$existing[] = $row['currency'];
				}
			}
			
			foreach ($currencies_balances as $curr_abbr => $balance) {
				$curr_info = $CFG->currencies[strtoupper($curr_abbr)];
				if (in_array($curr_info['id'],$existing))
					continue;
				
				$sql = 'INSERT INTO site_users_balances (balance,site_user,currency) VALUES ('.$balance.','.$user_id.','.$curr_info['id'].') ';
				$result = db_query($sql);
			}
		}
		
		if ($result) {
			$CFG->unset_cache['orders'][$user_id] = 1;
			$CFG->unset_cache['balances'][$user_id] = 1;
		}
		
		return $result;
	}
	
	public static function verifyLogin() {
		global $CFG;
		
		// IP throttling
		$login_attempts = 0;
		/*
		$ip_int = ip2long($CFG->client_ip);
		if ($ip_int) {
			$timeframe = (!empty($CFG->cloudflare_blacklist_timeframe)) ? $CFG->cloudflare_blacklist_timeframe : 15;
			$max_attempts = (!empty($CFG->cloudflare_blacklist_attempts)) ? $CFG->cloudflare_blacklist_attempts : 80;
			
			$sql = 'SELECT IFNULL(SUM(IF(login = "Y",1,0)),0) AS login_attempts, IFNULL(SUM(IF(login = "N",1,0)),0) AS hits, IFNULL(MIN(`timestamp`),NOW()) AS start FROM ip_access_log WHERE `timestamp` > DATE_SUB("'.date('Y-m-d H:i:s').'", INTERVAL '.$timeframe.' MINUTE) AND ip = '.$ip_int;
			$result = db_query_array($sql);
			if ($result) {
				$login_attempts = $result[0]['login_attempts'];
				if ($CFG->cloudflare_blacklist && $result[0]['hits'] > 0) {
					$time_elapsed = time() - strtotime($result[0]['start']);
					$hits_per_minute = $result[0]['hits'] / (($time_elapsed > 60 ? $time_elapsed : 60) / 60);
					
					if ($hits_per_minute >= $max_attempts && $time_elapsed >= 60)
						User::banIP($CFG->client_ip);
				}
			}
			
			db_insert('ip_access_log',array('ip'=>$ip_int,'timestamp'=>date('Y-m-d H:i:s')));
		}
		*/

		if (!($CFG->session_id > 0))
			return array('message'=>'not-logged-in','attempts'=>$login_attempts);
		
		if (!User::$info) {
			return array('error'=>'session-not-found','attempts'=>$login_attempts);
		}

		if (User::$info['ip'] != $CFG->client_ip) {
			return array('error'=>'session-not-found','attempts'=>$login_attempts);
		}
		
		if (User::$info['awaiting'] == 'Y') {
			return array('message'=>'awaiting-token','attempts'=>$login_attempts);
		}
		
		$return_values = array(
		'user',
		'first_name',
		'last_name',
		'fee_schedule',
		'tel',
		'country',
		'country_code',
		'verified_google',
		'verified_authy',
		'using_sms',
		'confirm_withdrawal_email_btc',
		'confirm_withdrawal_2fa_btc',
		'confirm_withdrawal_2fa_bank',
		'confirm_withdrawal_email_bank',
		'notify_deposit_btc',
		'notify_deposit_bank',
		'notify_withdraw_btc',
		'notify_withdraw_bank',
		'no_logins',
		'notify_login',
		'deactivated',
		'locked',
		'default_currency');
		
		$return = array();
		foreach (User::$info as $key => $value) {
			if (in_array($key,$return_values))
				$return[$key] = $value;
		}
		
		if ($return['country_code'] > 0) {
			$s = strlen($return['country_code']);
			$return['country_code'] = str_repeat('x',$s);
		}
		
		if ($return['tel'] > 0) {
			$s = strlen($return['tel']) - 2;
			$return['tel'] = str_repeat('x',$s).substr($return['tel'], -2);
		}
		
		if (User::$info['default_currency'] > 0) {
			$currency = $CFG->currencies[User::$info['default_currency']];
			$return['default_currency_abbr'] = $currency['currency'];
		}
		
		return array('message'=>'logged-in','info'=>$return);
	}
	
	public static function logOut($session_id=false) {
		if (!($session_id > 0))
			return false;
		
		$session_id = preg_replace("/[^0-9]/", "",$session_id);
		
		self::deleteCache();
		return db_delete('sessions',$session_id,'session_id');
	}
	
	public static function getOnHold($for_update=false,$user_id=false,$user_fee=false,$currencies=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$user_id = ($user_id > 0) ? $user_id : User::$info['id'];
		if ($CFG->memcached && !$currencies && empty($CFG->m_skip)) {
			$cached = $CFG->m->get('on_hold_'.$user_id);
			if (is_array($cached)) {
				self::$on_hold = $cached;
				if (!empty($cached))
					return $cached;
				else 
					return false;
			}
		}

		$user_fee = (is_array($user_fee)) ? $user_fee : FeeSchedule::getUserFees($user_id);
		$fee = $user_fee['fee1'] * 0.01;
		$lock = ($for_update) ? 'LOCK IN SHARE MODE' : '';
		$on_hold = array();
		
		if (!is_array($currencies) && $currencies > 0)
			$currencies = array($currencies);
		
		$currencies_str = '';
		$currencies_str1 = '';
		$amounts = array();
		$amounts1 = array();
		if (is_array($currencies)) {
			$currencies_str .= 'AND currency IN ('.implode(',',$currencies).')';
			$currencies1 = $currencies;
			if (in_array($CFG->btc_currency_id,$currencies1)) {
				unset($currencies1[$CFG->btc_currency_id]);
				$currencies_str1 .= 'AND (order_type = '.$CFG->order_type_ask.' OR currency IN ('.implode(',',$currencies).'))';
			}
			else
				$currencies_str1 .= 'AND currency IN ('.implode(',',$currencies1).')';
			
			foreach ($currencies as $currency_id) {
				$amounts[] = 'SUM(IF(currency = '.$currency_id.',amount,0)) AS '.$CFG->currencies[$currency_id]['currency'];
				
				if ($currency_id != $CFG->btc_currency_id)
					$amounts1[] = 'SUM(IF(order_type = '.$CFG->order_type_bid.' AND currency = '.$currency_id.',fiat + (fiat * '.$fee.'),0)) AS '.$CFG->currencies[$currency_id]['currency'];
				else
					$amounts1[] = 'SUM(IF(order_type = '.$CFG->order_type_ask.',btc,0)) AS '.$CFG->currencies[$currency_id]['currency'];
			}
		}
		else {
			foreach ($CFG->currencies as $currency_id => $currency1) {
				if (!is_numeric($currency_id))
					continue;
			
				$amounts[] = 'SUM(IF(currency = '.$currency_id.',amount,0)) AS '.$currency1['currency'];
				
				if ($currency_id != $CFG->btc_currency_id)
					$amounts1[] = 'SUM(IF(order_type = '.$CFG->order_type_bid.' AND currency = '.$currency_id.',fiat + (fiat * '.$fee.'),0)) AS '.$currency1['currency'];
				else
					$amounts1[] = 'SUM(IF(order_type = '.$CFG->order_type_ask.',btc,0)) AS '.$currency1['currency'];
			}
		}
		
		$sql = "
		SELECT ".implode(',',$amounts).", 'r' AS type FROM requests WHERE site_user = $user_id AND request_type = {$CFG->request_widthdrawal_id} AND request_status IN ({$CFG->request_pending_id},{$CFG->request_awaiting_id}) $currencies_str
		UNION
		SELECT ".implode(',',$amounts1).", 'o' AS type FROM orders WHERE site_user = $user_id $currencies_str1 $lock";
		$result = db_query_array($sql);
		if ($result) {
			foreach ($result as $row) {
				foreach ($row as $field => $value) {
					if ($field == 'type')
						continue;
					if (!($value > 0) && !($currencies && in_array($CFG->currencies[$field]['id'],$currencies)))
						continue;
					
					if ($field != 'BTC')
						$value = round($value,2,PHP_ROUND_HALF_UP);
					
					if ($row['type'] == 'r') {
						$on_hold[$field]['withdrawal'] = $value;
					}
					else {
						$on_hold[$field]['order'] = $value;
					}
					
					$on_hold[$field]['total'] = floatval($value) + (!empty($on_hold[$field]['total']) ? $on_hold[$field]['total'] : 0);
				}
			}
		}
		
		if (!$currencies && array_key_exists('BTC',$on_hold) && count($on_hold) > 1) {
			$btc_row = $on_hold['BTC'];
			unset($on_hold['BTC']);
			ksort($on_hold);
			$on_hold = array_merge(array('BTC'=>$btc_row),$on_hold);
		}
		
		if ($CFG->memcached && !$currencies) {
			$on_hold1 = ($on_hold) ? $on_hold : array();
			$CFG->m->set('on_hold_'.$user_id,$on_hold,60);
		}
		
		self::$on_hold = $on_hold;
		return $on_hold;
	}
	
	public static function getAvailable() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
	
		self::$on_hold = (is_array(self::$on_hold)) ? self::$on_hold : self::getOnHold();
		if ($CFG->currencies) {
			$on_hold = (!empty(self::$on_hold['BTC']['total'])) ? self::$on_hold['BTC']['total'] : 0;
			$available['BTC'] = User::$info['btc'] - $on_hold;
			$available['BTC'] = ($available['BTC'] < 0.00000001) ? 0 : $available['BTC'];
			foreach ($CFG->currencies as $currency) {
				if ($currency['currency'] == 'BTC')
					continue;
				
				if (empty(User::$info[strtolower($currency['currency'])]))
					continue;
					
				$on_hold = (!empty(self::$on_hold[$currency['currency']]['total'])) ? self::$on_hold[$currency['currency']]['total'] : 0;
				if (User::$info[strtolower($currency['currency'])] - $on_hold <= 0)
					continue;
	
				$available[$currency['currency']] = round(User::$info[strtolower($currency['currency'])] - $on_hold,2,PHP_ROUND_HALF_UP);
			}
		}
		return $available;
	}
	
	public static function hasCurrencies() {
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
	
	public static function getVolume() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		if ($CFG->memcached && empty($CFG->m_skip)) {
			$cached = $CFG->m->get('user_volume_'.User::$info['id']);
			if ($cached)
				return $cached;
		}	

		$sql = "SELECT ROUND(SUM(transactions.btc * transactions.btc_price * currencies.usd_ask),2) AS volume FROM transactions
				LEFT JOIN currencies ON (currencies.id = transactions.currency)
				WHERE (site_user = ".User::$info['id']." OR site_user1 = ".User::$info['id'].")
				AND transactions.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
				LIMIT 0,1";
		$result = db_query_array($sql);
		if ($CFG->memcached)
			$CFG->m->set('user_volume_'.User::$info['id'],300);
		
		return $result[0]['volume'];
	}
	
	public static function getNewId() {
		$sql = 'SELECT FLOOR(10000000 + RAND() * 89999999) AS random_num
				FROM site_users
				WHERE "random_num" NOT IN (SELECT user FROM site_users)
				LIMIT 1 ';
		$result = db_query_array($sql);
		
		if (!$result) {
			$sql = 'SELECT FLOOR(10000000 + RAND() * 89999999) AS random_num ';
			$result = db_query_array($sql);
		}
		
		return $result[0]['random_num'];
	}
	
	public static function randomPassword($length = 8) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$password = substr(str_shuffle($chars),0,$length);
		return $password;
	}
	
	public static function sendSMS($authy_id=false) {
		global $CFG;
		
		$authy_id = preg_replace("/[^0-9]/", "",$authy_id);
		
		if (!($CFG->session_active || $CFG->session_locked))
			return false;
		
		$authy_id = ($authy_id > 0) ? $authy_id : User::$info['authy_id'];
		$response = shell_exec('curl "https://api.authy.com/protected/json/sms/'.$authy_id.'?force=true&api_key='.$CFG->authy_api_key.'"');
		$response1 = json_decode($response,true);

		return $response1;
	}
	
	public static function confirmToken($token,$authy_id=false) {
		global $CFG;
		
		if (!($CFG->session_active || $CFG->session_locked))
			return false;
		
		$token1 = preg_replace("/[^0-9]/", "",$token);
		$authy_id1 = preg_replace("/[^0-9]/", "",$authy_id);
		$authy_id = ($authy_id > 0) ? $authy_id : User::$info['authy_id'];
		
		if (!($token1 > 0) || !($authy_id > 0))
			return false;
	
			$authy_id = ($authy_id > 0) ? $authy_id : User::$info['authy_id'];
			$response = shell_exec('curl "https://api.authy.com/protected/json/verify/'.$token.'/'.$authy_id.'?api_key='.$CFG->authy_api_key.'"');
			$response1 = json_decode($response,true);
			
			return $response1;
	}
	
	public static function disableNeverLoggedIn($pass) {
		global $CFG;
		
		$pass = preg_replace($CFG->pass_regex, "",$pass);
		if (!$CFG->session_active || mb_strlen($pass,'utf-8') < $CFG->pass_min_chars || User::$info['no_logins'] != 'Y')
			return false;
		
		self::deleteCache();
		$pass = Encryption::hash($pass);
		return db_update('site_users',User::$info['id'],array('no_logins'=>'N','pass'=>$pass));
	}
	
	public static function changePassword($pass) {
		global $CFG;
	
		$pass = preg_replace($CFG->pass_regex, "",$pass);
		if (!($CFG->session_active && ($CFG->token_verified || $CFG->email_2fa_verified)) || mb_strlen($pass,'utf-8') < $CFG->pass_min_chars)
			return false;
	
		self::deleteCache();
		$pass = Encryption::hash($pass);
		return db_update('site_users',User::$info['id'],array('pass'=>$pass));
	}
	
	public static function firstLoginPassChange($pass) {
		global $CFG;
		
		$pass = preg_replace($CFG->pass_regex, "",$pass);
		if (!$CFG->session_active || mb_strlen($pass,'utf-8') < $CFG->pass_min_chars || User::$info['no_logins'] != 'Y')
			return false;
		
		self::deleteCache();
		$pass = Encryption::hash($pass);
		return db_update('site_users',User::$info['id'],array('pass'=>$pass));
	}
	
	public static function userExists($email) {
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
	
	public static function resetUser($email) {
		global $CFG;
		
		$email = preg_replace("/[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/", "",$email);
		
		if (!$email)
			return false;
		
		$id = self::userExists($email);
		if (!($id > 0))
			return false;
		
		$sql = "DELETE FROM sessions WHERE user_id = $id";
		db_query($sql);
		
		self::deleteCache();
		$email_token = self::randomPassword(12);
		$request_id = db_insert('change_settings',array('email_token'=>$email_token,'date'=>date('Y-m-d H:i:s'),'site_user'=>$id,'request'=>1,'type'=>'r'));
		if ($request_id > 0) {
			$vars = User::$info;
			$vars['authcode'] = urlencode(Encryption::encrypt($email_token));
			$vars['baseurl'] = $CFG->frontend_baseurl;
		
			$email1 = SiteEmail::getRecord('forgot');
			Email::send($CFG->form_email,$email,$email1['title'],$CFG->form_email_from,false,$email1['content'],$vars);
		}
	}
	
	public static function registerNew($info) {
		global $CFG;
		
		if (!is_array($info))
			return false;
		
		$info['email'] = preg_replace("/[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/", "",$info['email']);
		$exist_id = self::userExists($info['email']);
		if ($exist_id > 0) {
			$user_info = DB::getRecord('site_users',$exist_id,0,1);
			$email = SiteEmail::getRecord('register-existing');
			Email::send($CFG->form_email,$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$user_info);
			return false;
		}

		$new_id = self::getNewId();
		if ($new_id > 0) {
			$sql = 'SELECT id FROM fee_schedule ORDER BY from_usd ASC LIMIT 0,1';
			$result = db_query_array($sql);
			
			$pass1 = self::randomPassword(12);
			//$info['first_name'] = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u", "",$info['first_name']);
			//$info['last_name'] = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u", "",$info['last_name']);
			//$info['country'] = preg_replace("/[^0-9]/", "",$info['country']);
			$info['user'] = $new_id;
			$info['pass'] = Encryption::hash($pass1);
			$info['date'] = date('Y-m-d H:i:s');
			$info['confirm_withdrawal_email_btc'] = 'Y';
			$info['confirm_withdrawal_email_bank'] = 'Y';
			$info['notify_deposit_btc'] = 'Y';
			$info['notify_deposit_bank'] = 'Y';
			$info['notify_withdraw_btc'] = 'Y';
			$info['notify_withdraw_bank'] = 'Y';
			$info['notify_login'] = 'Y';
			$info['no_logins'] = 'Y';
			$info['fee_schedule'] = $result[0]['id'];
			$info['default_currency'] = preg_replace("/[^0-9]/", "",$info['default_currency']);
			unset($info['terms']);
			
			$record_id = db_insert('site_users',$info);
		
			require_once('../lib/easybitcoin.php');
			$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
			$new_address = $bitcoin->getnewaddress($CFG->bitcoin_accountname);
			db_insert('bitcoin_addresses',array('address'=>$new_address,'site_user'=>$record_id,'date'=>date('Y-m-d H:i:s')));
		
			$info['pass'] = $pass1;
			$email = SiteEmail::getRecord('register');
			Email::send($CFG->form_email,$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$info);
		
			if ($CFG->email_notify_new_users) {
				$email = SiteEmail::getRecord('register-notify');
				$info['pass'] = false;
				Email::send($CFG->form_email,$CFG->support_email,$email['title'],$CFG->form_email_from,false,$email['content'],$info);
			}
			return true;
		}
	}
	
	public static function registerAuthy($cell,$country_code) {
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
	
	public static function enableAuthy($cell,$country_code,$authy_id,$using_sms) {
		global $CFG;
		
		$cell = preg_replace("/[^0-9]/", "",$cell);
		$country_code = preg_replace("/[^0-9]/", "",$country_code);
		$authy_id = preg_replace("/[^0-9]/", "",$authy_id);
		
		if (!$CFG->session_active || User::$info['verified_authy'] == 'Y' || User::$info['verified_google'] == 'Y')
			return false;
		
		self::deleteCache();
		
		return db_update('site_users',User::$info['id'],array('tel'=>$cell,'country_code'=>$country_code,'authy_requested'=>'Y','verified_authy'=>'N','authy_id'=>$authy_id,'using_sms'=>$using_sms,'google_2fa_code'=>'','confirm_withdrawal_2fa_btc'=>'Y','confirm_withdrawal_2fa_bank'=>'Y'));
	}
	
	public static function enableGoogle2fa($cell,$country_code) {
		global $CFG;
	
		$cell = preg_replace("/[^0-9]/", "",$cell);
		$country_code = preg_replace("/[^0-9]/", "",$country_code);
	
		if (!$CFG->session_active || User::$info['verified_authy'] == 'Y' || User::$info['verified_google'] == 'Y')
			return false;
	
		$key = Google2FA::generate_secret_key();
		if (!$key)
			return false;
		
		self::deleteCache();
		
		$result = db_update('site_users',User::$info['id'],array('tel'=>$cell,'country_code'=>$country_code,'google_2fa_code'=>$key,'verified_google'=>'N','using_sms'=>'N','authy_id'=>'','confirm_withdrawal_2fa_btc'=>'Y','confirm_withdrawal_2fa_bank'=>'Y'));
		if ($result)
			return $key;
	}
	
	public static function getGoogleSecret() {
		global $CFG;
		
		if (!($CFG->session_active) || User::$info['verified_google'] == 'Y')
			return false;
		
		return array('secret'=>User::$info['google_2fa_code'],'label'=>$CFG->exchange_name);
	}
	
	public static function verifiedAuthy() {
		global $CFG;
	
		if (!($CFG->session_active && $CFG->token_verified && $CFG->email_2fa_verified) || User::$info['verified_google'] == 'Y')
			return false;
	
		self::deleteCache();
		
		return db_update('site_users',User::$info['id'],array('verified_authy'=>'Y'));
	}
	
	public static function verifiedGoogle() {
		global $CFG;
	
		if (!($CFG->session_active && $CFG->token_verified && $CFG->email_2fa_verified) || User::$info['verified_authy'] == 'Y')
			return false;
			
		self::deleteCache();
		
		return db_update('site_users',User::$info['id'],array('verified_google'=>'Y'));
	}
	
	public static function disable2fa() {
		global $CFG;
		
		if (!($CFG->session_active && $CFG->token_verified))
			return false;

		self::deleteCache();
		
		return db_update('site_users',User::$info['id'],array('google_2fa_code'=>'','verified_google'=>'N','using_sms'=>'N','authy_id'=>'','verified_authy'=>'N'));
	}
	
	public static function updatePersonalInfo($info) {
		global $CFG;

		if (!($CFG->session_active && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;

		if (!is_array($info))
			return false;

		$update['pass'] = (!empty($info['pass'])) ? preg_replace($CFG->pass_regex, "",$info['pass']) : false;
		//$update['first_name'] = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u", "",$info['first_name']);
		//$update['last_name'] = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u", "",$info['last_name']);
		//$update['country'] = preg_replace("/[^0-9]/", "",$info['country']);
		$update['email'] = preg_replace("/[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/", "",$info['email']);
		$update['default_currency'] = preg_replace("/[^0-9]/", "",$info['default_currency']);
		
		if (!$update['pass'])
			unset($update['pass']);

		if ((!empty($update['pass']) && mb_strlen($update['pass'],'utf-8') < $CFG->pass_min_chars) /*|| !$update['first_name'] || !$update['last_name'] */|| !$update['email'])
			return false;
		
		self::deleteCache();
		
		if ($CFG->session_id) {
		    $sql = "DELETE FROM sessions WHERE user_id = ".User::$info['id']." AND session_id != {$CFG->session_id}";
		    db_query($sql);
		    
		    $sql = "DELETE FROM change_settings WHERE site_user = ".User::$info['id'];
		    db_query($sql);
		}

		if (!empty($update['pass']))
			$update['pass'] = Encryption::hash($update['pass']);
		
		return db_update('site_users',User::$info['id'],$update);
	}
	
	public static function updateSettings($confirm_withdrawal_2fa_btc1,$confirm_withdrawal_email_btc1,$confirm_withdrawal_2fa_bank1,$confirm_withdrawal_email_bank1,$notify_deposit_btc1,$notify_deposit_bank1,$notify_login1,$notify_withdraw_btc1,$notify_withdraw_bank1) {
		global $CFG;
		
		if (!($CFG->session_active && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;
			
		$confirm_withdrawal_2fa_btc2 = ($confirm_withdrawal_2fa_btc1) ? 'Y' : 'N';
		$confirm_withdrawal_email_btc2 = ($confirm_withdrawal_email_btc1) ? 'Y' : 'N';
		$confirm_withdrawal_2fa_bank2 = ($confirm_withdrawal_2fa_bank1) ? 'Y' : 'N';
		$confirm_withdrawal_email_bank2 = ($confirm_withdrawal_email_bank1) ? 'Y' : 'N';
		$notify_deposit_btc2 = ($notify_deposit_btc1) ? 'Y' : 'N';
		$notify_deposit_bank2 = ($notify_deposit_bank1) ? 'Y' : 'N';
		$notify_withdraw_btc2 = ($notify_withdraw_btc1) ? 'Y' : 'N';
		$notify_withdraw_bank2 = ($notify_withdraw_bank1) ? 'Y' : 'N';
		$notify_login2 = ($notify_login1) ? 'Y' : 'N';
			
		self::deleteCache();
		
		return db_update('site_users',User::$info['id'],array('confirm_withdrawal_2fa_btc'=>$confirm_withdrawal_2fa_btc2,'confirm_withdrawal_email_btc'=>$confirm_withdrawal_email_btc2,'confirm_withdrawal_2fa_bank'=>$confirm_withdrawal_2fa_bank2,'confirm_withdrawal_email_bank'=>$confirm_withdrawal_email_bank2,'notify_deposit_btc'=>$notify_deposit_btc2,'notify_deposit_bank'=>$notify_deposit_bank2,'notify_withdraw_btc'=>$notify_withdraw_btc2,'notify_withdraw_bank'=>$notify_withdraw_bank2,'notify_login'=>$notify_login2));
	}
	
	public static function deactivateAccount() {
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

		if (!$found) {
			self::deleteCache();
			return db_update('site_users',User::$info['id'],array('deactivated'=>'Y'));
		}
	}
	
	public static function reactivateAccount() {
		global $CFG;

		if (!(($CFG->session_locked || $CFG->session_active) && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;
	
		return db_update('site_users',User::$info['id'],array('deactivated'=>'N'));
	}
	
	public static function lockAccount() {
		global $CFG;
	
		if (!($CFG->session_active && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;
	
		self::deleteCache();
		return db_update('site_users',User::$info['id'],array('locked'=>'Y'));
	}
	
	public static function unlockAccount() {
		global $CFG;
	
		if (!(($CFG->session_locked || $CFG->session_active) && ($CFG->token_verified || $CFG->email_2fa_verified)))
			return false;
	
		self::deleteCache();
		return db_update('site_users',User::$info['id'],array('locked'=>'N'));
	}
	
	public static function settingsEmail2fa($request=false,$security_page=false) {
		global $CFG;

		if (!($CFG->session_locked || $CFG->session_active))
			return false;
		
		$sql = "DELETE FROM change_settings WHERE site_user = ".User::$info['id'];
		db_query($sql);
		
		$email_token = self::randomPassword(12);
		$request_id = db_insert('change_settings',array('email_token'=>$email_token,'date'=>date('Y-m-d H:i:s'),'request'=>base64_encode(serialize($request)),'site_user'=>User::$info['id'],'type'=>'s'));
		if ($request_id > 0) {
			$vars = User::$info;
			$vars['authcode'] = urlencode(Encryption::encrypt($email_token));
			$vars['baseurl'] = $CFG->frontend_baseurl;
		
			if (!$security_page)
				$email = SiteEmail::getRecord('settings-auth');
			else
				$email = SiteEmail::getRecord('security-auth');
				
			return Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$vars);
		}
	}
	
	public static function getSettingsChangeRequest($settings_change_id1) {
		global $CFG;

		if (!$settings_change_id1)
			return false;
		
		$request_id = Encryption::decrypt(urldecode($settings_change_id1));
		if (!$request_id)
			return false;
		
		$request_id = preg_replace("/[^0-9a-zA-Z]/", "",$request_id);
		if (!$request_id)
			return false;
		
		$sql = 'SELECT request FROM change_settings WHERE email_token = "'.$request_id.'"';
		$result = db_query_array($sql);
		
		if (!$result)
			return false;
		
		return $result[0]['request'];
	}
	
	public static function notifyLogin() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$ipaddress1 = $CFG->client_ip;
		
		db_insert('history',array('date'=>date('Y-m-d H:i:s'),'ip'=>$ipaddress1,'history_action'=>$CFG->history_login_id,'site_user'=>User::$info['id']));
		
		if (User::$info['notify_login'] != 'Y')
			return false;
		
		$email = SiteEmail::getRecord('login-notify');
		$info = User::$info;
		$info['ipaddress'] = $ipaddress1;

		Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$info);
	}
	
	public static function getCountries() {
		$sql = "SELECT * FROM iso_countries ORDER BY name ASC";
		return db_query_array($sql);
	}
	
	public static function setLang($lang) {
		if (!$lang)
			return false;
		
		$lang = preg_replace("/[^a-z]/", "",$lang);
		self::deleteCache();
		return db_update('site_users',User::$info['id'],array('last_lang'=>$lang));
	}
	
	public static function banIP($ip) {
		global $CFG;
		
		if (empty($ip) || empty($CFG->cloudflare_api_key) || empty($CFG->cloudflare_email) || empty($CFG->cloudflare_blacklist) || $CFG->cloudflare_blacklist != 'Y')
			return false;
		
		$ch = curl_init('https://www.cloudflare.com/api_json.html');
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,array('a'=>'ban','tkn'=>$CFG->cloudflare_api_key,'email'=>$CFG->cloudflare_email,'key'=>$ip));
		curl_setopt($ch,CURLOPT_FRESH_CONNECT,TRUE);
		
		$result1 = curl_exec($ch);
		$result = json_decode($result1,true);
		curl_close($ch);
	}

	public static function getBalancesAndInfo() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
	
		$CFG->m_skip = true;
		$available = self::getAvailable();
		$on_hold = ($CFG->memcached) ? $CFG->m->get('on_hold_'.User::$info['id']) : false;
		if (!$on_hold)
			$on_hold = self::getOnHold();
		
		$volume = ($CFG->memcached) ? $CFG->m->get('user_volume_'.User::$info['id']) : false;
		if ($volume)
			$volume = self::getVolume();
		
		$global_btc_vol = ($CFG->memcached) ? $CFG->m->get('btc_traded') : false;
		if (!$global_btc_vol)
			$global_btc_vol = Stats::getBTCTraded();
		
		$fees = FeeSchedule::getRecord(false,1);
		
		$return['on_hold'] = ($on_hold) ? $on_hold : array();
		$return['available'] = ($available) ? $available : array();
		$return['usd_volume'] = ($volume) ? $volume : 0;
		$return['fee_bracket']['maker'] = ($fees['fee1']) ? $fees['fee1'] : 0;
		$return['fee_bracket']['taker'] = ($fees['fee']) ? $fees['fee'] : 0;
		$return['global_btc_volume'] = ($global_btc_vol[0]['total_btc_traded'] > 0) ? $global_btc_vol[0]['total_btc_traded'] : 0;
		return $return;
	}

	public static function deleteCache($session_id=false) {
		global $CFG;
		
		$session_id = (!$session_id) ? $CFG->session_id : $session_id;
		if ($CFG->memcached && $CFG->session_id)
			$CFG->delete_cache = $CFG->m->delete('session_'.$CFG->session_id);
	}
	
	public static function deleteBalanceCache($user_id,$only_on_hold=false) {
		global $CFG;
		
		if (!$user_id || !$CFG->memcached)
			return false;
		
		if (!$only_on_hold) {
			$CFG->m->delete('balances_'.$user_id);
			$CFG->m->delete('user_volume_'.$user_id);
		}
		
		$CFG->m->delete('on_hold_'.$user_id);
	}
}

?>
