<?php
class FeeSchedule {
	function get() {
		$sql = "SELECT * FROM fee_schedule ORDER BY fee_schedule.order ASC, id ASC";
		return db_query_array($sql);
	}
	
	function getRecord($braket_id=false) {
		$braket_id = preg_replace("/[^0-9]/", "",$braket_id);
		
		if (!($braket_id > 0))
			return false;
		
		return DB::getRecord('fee_schedule',$braket_id,0,1);
	}
}