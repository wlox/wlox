<?php
class DB {
	public static $errors, $random_ids;
	
	public static function getRecord($table,$id = 0,$f_id=0,$id_required=false,$f_id_field=false,$order_by=false,$order_asc=false,$for_update=false) {
		if ($id_required && !($id > 0))
			return false;
			
		if (!$table)
			return false;
		
		$f_id_field = ($f_id_field) ? $f_id_field : 'f_id';
			
		$sql = "SELECT {$table}.* FROM {$table} WHERE 1 ";
		if ($id > 0) {
			$sql .= " AND  {$table}.id = $id ";
		}
		if ($f_id) {
			$sql .= " AND  {$table}.{$f_id_field} = '$f_id' ";
		}
		if ($order_by) {
			$order_asc = ($order_asc) ? 'ASC' : 'DESC';
			$sql .= " ORDER BY $order_by $order_asc ";
		}
		$sql .= " LIMIT 0,1 ";
		
		if ($for_update)
			$sql .= ' FOR UPDATE';
		
		$result = db_query_array($sql);
		return $result[0];
	}
}
?>