<?php
class FeeSchedule {
	public static function get($currency=false) {
		global $CFG;
		
		$currency = preg_replace("/[^a-zA-Z]/", "",$currency);
		$currency_info = ($currency) ? $CFG->currencies[strtoupper($currency)] : $CFG->currencies['USD'];
		
		$sql = "SELECT fee_schedule.*, (fee_schedule.from_usd/currencies.usd_ask) AS from_usd, (fee_schedule.to_usd/currencies.usd_ask) AS to_usd, currencies.fa_symbol AS fa_symbol FROM fee_schedule LEFT JOIN currencies ON (currencies.id = {$currency_info['id']}) ORDER BY fee_schedule.order ASC, fee_schedule.id ASC";
		return db_query_array($sql);
	}
	
	public static function getRecord($braket_id=false,$user=false) {
		global $CFG;
		
		$braket_id = preg_replace("/[^0-9]/", "",$braket_id);
		
		if ($user && !$CFG->session_active)
			return false;
		
		if (!($braket_id > 0) && !$user)
			return false;
		
		if ($user)
			$braket_id = User::$info['fee_schedule'];
		
		return DB::getRecord('fee_schedule',$braket_id,0,1);
	}
	
	public static function getUserFees($user_id=false) {
		if (!$user_id)
			return false;
		
		$sql = 'SELECT fee_schedule.* FROM fee_schedule LEFT JOIN site_users ON (site_users.fee_schedule = fee_schedule.id) WHERE site_users.id = '.$user_id.' LIMIT 0,1';
		$result = db_query_array($sql);
		return ($result) ? $result[0] : false;
	}
}