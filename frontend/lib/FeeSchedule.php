<?php
class FeeSchedule {
	function get() {
		$sql = "SELECT * FROM fee_schedule ORDER BY fee_schedule.order ASC, id ASC";
		return db_query_array($sql);
	}
}