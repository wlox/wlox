<?php
class SiteUser{
	public static $on_hold;
	
	function getOnHold() {
		global $CFG;
		
		$sql = " SELECT currencies.currency AS currency, requests.amount AS amount FROM requests LEFT JOIN currencies ON (currencies.id = requests.currency) WHERE requests.site_user = ".User::$info['id']." AND requests.request_type = {$CFG->request_widthdrawal_id} AND (requests.request_status = {$CFG->request_pending_id} OR requests.request_status = {$CFG->request_awaiting_id})";
		$result = db_query_array($sql);
		if ($result) {
			foreach ($result as $row) {
				$on_hold[$row['currency']]['withdrawal'] += floatval($row['amount']);
				$on_hold[$row['currency']]['total'] += floatval($row['amount']);
			}
		}
		
		$sql = " SELECT currencies.currency AS currency, orders.fiat AS amount, orders.btc AS btc_amount, orders.order_type AS type FROM orders LEFT JOIN currencies ON (currencies.id = orders.currency) WHERE orders.site_user = ".User::$info['id']."";
		$result = db_query_array($sql);
		if ($result) {
			foreach ($result as $row) {
				if ($row['type'] == $CFG->order_type_bid) {
					$on_hold[$row['currency']]['order'] += floatval($row['amount']);
					$on_hold[$row['currency']]['total'] += floatval($row['amount']);
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
	
	function getVolume() {
		global $CFG;
		
		$sql = "SELECT SUM(transactions.btc * currencies.usd) AS volume FROM transactions
				LEFT JOIN currencies ON (currencies.id = {$CFG->btc_currency_id})
				WHERE (site_user = ".User::$info['id']." OR site_user1 = ".User::$info['id'].") 
				AND transactions.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
				LIMIT 0,1";
		$result = db_query_array($sql);
		return $result[0]['volume'];
	}
	
	function emailRegistered($email) {
		if (!$email)
			return false;
		
		$sql = "SELECT id FROM site_users WHERE email = '$email' ";
		$result = db_query_array($sql);
		return $result[0]['id'];
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
	
	function sendSMS() {
		global $CFG;
		
		$response = shell_exec('curl https://api.authy.com/protected/json/sms/'.User::$info['authy_id'].'?api_key='.$CFG->authy_api_key);
		$response1 = json_decode($response,true);
		
		if (!$response || !is_array($response1))
			Errors::add(Lang::string('security-com-error'));
		elseif ($response1['success'] === false)
			Errors::merge($response1['errors']);
		else {
			return true;
		}
	}
	
	function confirmToken($token1) {
		global $CFG;
		
		if (!($token1 > 0))
			Errors::add(Lang::string('security-no-token'));
		
		if (!is_array(Errors::$errors)) {
			$authy_id = User::$info['authy_id'];
			$response = shell_exec('curl "https://api.authy.com/protected/json/verify/'.$token1.'/'.$authy_id.'?api_key='.$CFG->authy_api_key.'"');
			$response1 = json_decode($response,true);
				
			if (!$response || !is_array($response1))
				Errors::add(Lang::string('security-com-error'));
			if ($response1['success'] === false)
				Errors::merge($response1['errors']);
		
			if (!is_array(Errors::$errors)) {
				return true;
			}
		}
	}
}