<?php
class News{
	public static function get($count=false,$page=false,$per_page=false) {
		$page = preg_replace("/[^0-9]/", "",$page);
		$per_page = preg_replace("/[^0-9]/", "",$per_page);
		
		$page = ($page > 0) ? $page - 1 : 0;
		$r1 = $page * $per_page;
		
		if (!$count)
			$sql = "SELECT * FROM news ";
		else
			$sql = "SELECT COUNT(id) AS total FROM news ";
		
		$sql .= " ORDER BY news.date DESC ";
		
		if ($per_page > 0 && !$count)
			$sql .= " LIMIT $r1,$per_page ";
		
		$result = db_query_array($sql);
		if (!$count)
			return $result;
		else
			return $result[0]['total'];
	}
}