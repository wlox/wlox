<?php 
class User {
	public static $info, $on_hold;
	
	public static function getOnHold($for_update=false,$user_id=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$user_info = ($user_id > 0) ? DB::getRecord('site_users',$user_id,0,1,false,false,false,$for_update) : User::$info;
		$user_fee = DB::getRecord('fee_schedule',$user_info['fee_schedule'],0,1);
		$lock = ($for_update) ? 'FOR UPDATE' : '';
		$on_hold = array();
	
		$sql = " SELECT currencies.currency AS currency, requests.amount AS amount FROM requests LEFT JOIN currencies ON (currencies.id = requests.currency) WHERE requests.site_user = ".$user_info['id']." AND requests.request_type = {$CFG->request_widthdrawal_id} AND (requests.request_status = {$CFG->request_pending_id} OR requests.request_status = {$CFG->request_awaiting_id}) ".$lock;
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
	
		$sql = " SELECT currencies.currency AS currency, orders.fiat AS amount, orders.btc AS btc_amount, orders.order_type AS type FROM orders LEFT JOIN currencies ON (currencies.id = orders.currency) WHERE orders.site_user = ".$user_info['id']." ".$lock;
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
		self::$on_hold = $on_hold;
		return $on_hold;
	}
}