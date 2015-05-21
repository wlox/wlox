<?php 
class User {
	public static function getOnHold($for_update=false,$user_id=false,$user_fee=false) {
		global $CFG;
	
		if (!($user_id > 0))
			return false;
	
		$user_fee = (is_array($user_fee)) ? $user_fee : FeeSchedule::getUserFees($user_id);
		$lock = ($for_update) ? 'LOCK IN SHARE MODE' : '';
		$on_hold = array();
	
		$sql = " SELECT currencies.currency AS currency, requests.amount AS amount FROM requests LEFT JOIN currencies ON (currencies.id = requests.currency) WHERE requests.site_user = ".$user_id." AND requests.request_type = {$CFG->request_widthdrawal_id} AND (requests.request_status = {$CFG->request_pending_id} OR requests.request_status = {$CFG->request_awaiting_id}) ".$lock;
		$result = db_query_array($sql);
		if ($result) {
			foreach ($result as $row) {
				if (!empty($on_hold[$row['currency']]['withdrawal']))
					$on_hold[$row['currency']]['withdrawal'] += floatval($row['amount']);
				else
					$on_hold[$row['currency']]['withdrawal'] = floatval($row['amount']);
					
				if (!empty($on_hold[$row['currency']]['total']))
					$on_hold[$row['currency']]['total'] += floatval($row['amount']);
				else
					$on_hold[$row['currency']]['total'] = floatval($row['amount']);
			}
		}
	
		$sql = " SELECT currencies.currency AS currency, orders.fiat AS amount, orders.btc AS btc_amount, orders.order_type AS type FROM orders LEFT JOIN currencies ON (currencies.id = orders.currency) WHERE orders.site_user = ".$user_id." ".$lock;
		$result = db_query_array($sql);
		if ($result) {
			foreach ($result as $row) {
				if ($row['type'] == $CFG->order_type_bid) {
					if (!empty($on_hold[$row['currency']]['order']))
						$on_hold[$row['currency']]['order'] += round(floatval($row['amount']) + (floatval($row['amount']) * ($user_fee['fee'] * 0.01)),2,PHP_ROUND_HALF_UP);
					else
						$on_hold[$row['currency']]['order'] = round(floatval($row['amount']) + (floatval($row['amount']) * ($user_fee['fee'] * 0.01)),2,PHP_ROUND_HALF_UP);
						
					if (!empty($on_hold[$row['currency']]['total']))
						$on_hold[$row['currency']]['total'] += round(floatval($row['amount']) + (floatval($row['amount']) * ($user_fee['fee'] * 0.01)),2,PHP_ROUND_HALF_UP);
					else
						$on_hold[$row['currency']]['total'] = round(floatval($row['amount']) + (floatval($row['amount']) * ($user_fee['fee'] * 0.01)),2,PHP_ROUND_HALF_UP);
				}
				else {
					if (!empty($on_hold['BTC']['order']))
						$on_hold['BTC']['order'] += floatval($row['btc_amount']);
					else
						$on_hold['BTC']['order'] = floatval($row['btc_amount']);
	
					if (!empty($on_hold['BTC']['total']))
						$on_hold['BTC']['total'] += floatval($row['btc_amount']);
					else
						$on_hold['BTC']['total'] = floatval($row['btc_amount']);
				}
			}
		}
		return $on_hold;
	}
	
	public static function updateBalances($user_id,$currencies_balances,$sum=false) {
		global $CFG;
	
		if (!($user_id > 0) || empty($currencies_balances) || !is_array($currencies_balances))
			return false;
	
		$currencies_str = '(CASE currency ';
		$currency_ids = array();
		foreach ($currencies_balances as $curr_abbr => $balance) {
			$curr_info = $CFG->currencies[strtoupper($curr_abbr)];
			$currencies_str .= ' WHEN '.$curr_info['id'].' THEN '.(($sum) ? 'balance + ' : '').' ('.$balance.') ';
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
		return $result;
	}
	
	// currency_id can be array of ids
	public static function getBalances($user_id,$currencies=false,$for_update=false) {
		global $CFG;
	
		if (!($user_id > 0))
			return false;
	
		$sql = 'SELECT site_users_balances.*, currencies.currency AS currency_abbr FROM site_users_balances LEFT JOIN currencies ON (site_users_balances.currency = currencies.id) WHERE site_users_balances.site_user = '.$user_id.' ';
		if (!is_array($currencies) && $currencies > 0)
			$sql .= ' AND site_users_balances.currency = '.$currencies;
		else if (is_array($currencies)) {
			$sub_sql = array();
			foreach ($currencies as $id) {
				$sub_sql[] = ' site_users_balances.currency = '.$id;
			}
			$sql .= ' AND ('.implode(' OR ',$sub_sql).')';
		}
		if ($for_update)
			$sql .= ' FOR UPDATE';
	
		$result = db_query_array($sql);
		if (!$result)
			return false;
	
		if (!empty($currencies)) {
			$sorted = array();
			foreach ($result as $row) {
				$sorted[strtolower($row['currency_abbr'])] = $row['balance'];
			}
			return $sorted;
		}
		else
			return $result;
	}
	
	public static function getBalance($user_id,$currency_id,$for_update=false) {
		global $CFG;
	
		if (!($user_id > 0) || !($currency_id > 0))
			return false;
		
		
		$sql = 'SELECT site_users_balances.* FROM site_users_balances WHERE site_users_balances.currency = '.$currency_id.' AND site_users_balances.site_user = '.$user_id.' ';
		if ($for_update)
			$sql .= ' FOR UPDATE';
		
		$result = db_query_array($sql);
		if (!$result)
			return false;
		
		return $result[0];
	}
}