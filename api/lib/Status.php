<?php
class Status{
	public static function get($for_update=false) {
		$sql = "SELECT * FROM status WHERE id= 1";
		
		if ($for_update)
			$sql .= ' FOR UPDATE';
		
		$result = db_query_array($sql);
		return $result[0];
	}
}