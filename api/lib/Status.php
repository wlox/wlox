<?php
class Status{
	public static function get($for_update=false) {
		$sql = "SELECT * FROM status WHERE id= 1";
		
		if ($for_update)
			$sql .= ' FOR UPDATE';
		
		$result = db_query_array($sql);
		return $result[0];
	}
	
	public static function updateEscrows($escrows=false) {
		global $CFG;
		
		if (!is_array($escrows) || empty($escrows))
			return false;
		
		$currencies_str = '(CASE currency ';
		$currency_ids = array();
		foreach ($escrows as $curr_abbr => $balance) {
			$curr_info = $CFG->currencies[strtoupper($curr_abbr)];
			$currencies_str .= ' WHEN '.$curr_info['id'].' THEN balance + '.$balance.' ';
			$currency_ids[] = $curr_info['id'];
		}
		$currencies_str .= ' END)';
		
		$sql = 'UPDATE status_escrows SET balance = '.$currencies_str.' WHERE currency IN ('.implode(',',$currency_ids).') AND status_id = 1';
		$result = db_query($sql);
		
		if (!$result || $result < count($escrows)) {
			$sql = 'SELECT currency FROM status_escrows WHERE status_id = 1';
			$result = db_query_array($sql);
			$existing = array();
			if ($result) {
				foreach ($result as $row) {
					$existing[] = $row['currency'];
				}
			}
			
			foreach ($escrows as $curr_abbr => $balance) {
				$curr_info = $CFG->currencies[strtoupper($curr_abbr)];
				if (in_array($curr_info['id'],$existing))
					continue;
				
				$sql = 'INSERT INTO status_escrows (balance,status_id,currency) VALUES ('.$balance.',1,'.$curr_info['id'].') ';
				$result = db_query($sql);
			}
		}
		return $result;
	}
	
	public static function sumFields($fields) {
		global $CFG;
	
		if (!is_array($fields) || empty($fields))
			return false;
	
		$set = array();
		foreach ($fields as $field => $sum_amount) {
			if (!is_numeric($sum_amount))
				continue;
	
			$set[] = $field.' = '.$field.' + ('.$sum_amount.')';
		}
	
		$sql = 'UPDATE status SET '.implode(',',$set).' WHERE id = 1';
		return db_query($sql);
	}
}