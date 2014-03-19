<?php
class Requests{
	function get($count=false,$page=false,$per_page=false,$user=false,$type=false) {
		global $CFG;
		
		$page = ($page > 0) ? $page - 1 : 0;
		$r1 = $page * $per_page;
		
		if (!$count)
			$sql = "SELECT requests.*, request_descriptions.name_{$CFG->language} AS description, request_status.name_{$CFG->language} AS status, currencies.fa_symbol AS fa_symbol FROM requests LEFT JOIN request_descriptions ON (request_descriptions.id = requests.description) LEFT JOIN request_status ON (request_status.id = requests.request_status) LEFT JOIN currencies ON (requests.currency = currencies.id) WHERE 1 ";
		else
			$sql = "SELECT COUNT(requests.id) AS total FROM requests WHERE 1 ";
		
		if ($user > 0)
			$sql .= " AND requests.site_user = $user ";
		
		if ($type > 0)
			$sql .= " AND requests.request_type = $type ";
		
		if ($per_page > 0 && !$count)
			$sql .= " ORDER BY requests.date DESC LIMIT $r1,$per_page ";
		
		$result = db_query_array($sql);
		if (!$count)
			return $result;
		else
			return $result[0]['total'];
	}
}