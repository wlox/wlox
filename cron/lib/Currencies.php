<?php
class Currencies {
	public static function get() {
		$sql = "SELECT * FROM currencies WHERE is_active = 'Y' ORDER BY currency ASC";
		$result = db_query_array($sql);
		
		if ($result) {
			foreach ($result as $row) {
				$currencies[$row['currency']] = $row;
				$currencies[$row['id']] = $row;
			}
		}
		return $currencies;
	}
	
	public static function getRecord($currency_abbr=false,$currency_id=false) {
		if (!$currency_abbr && !$currency_id)
			return false;

		if ($currency_abbr)
			return DB::getRecord('currencies',false,$currency_abbr,0,'currency');
		elseif ($currency_id > 0)
			return DB::getRecord('currencies',$currency_id,false,1);
	}
}