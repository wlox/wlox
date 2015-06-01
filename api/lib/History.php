<?php 
class History {
	public static function get($count=false,$page=false,$per_page=false,$dont_paginate=false) {
		global $CFG;
		
		$page = preg_replace("/[^0-9]/", "",$page);
		$per_page = preg_replace("/[^0-9]/", "",$per_page);
		$page = preg_replace("/[^0-9]/", "",$page);
		$page = ($page > 0) ? $page - 1 : 0;
		$r1 = $page * $per_page;
		
		if (!$count)
			$sql = "SELECT history.date AS `date`, history.ip AS ip, history_actions.name_{$CFG->language} AS type, currencies.currency AS request_currency FROM history ";
		else
			$sql = "SELECT COUNT(history.id) AS total FROM history ";
		
		$sql .= "
		LEFT JOIN history_actions ON (history_actions.id = history.history_action)
		LEFT JOIN requests ON (requests.id = history.request_id)
		LEFT JOIN currencies ON (requests.currency = currencies.id)
		WHERE history.site_user = ".User::$info['id']." ";
		
		if ($per_page > 0 && !$count && !$dont_paginate)
			$sql .= " ORDER BY history.id DESC LIMIT $r1,$per_page ";

		$result = db_query_array($sql);
		if (!$count)
			return $result;
		else
			return $result[0]['total'];
	}
}

?>