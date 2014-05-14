<?php
class Currencies {
	function get() {
		$sql = "SELECT * FROM currencies WHERE currency != 'BTC' AND is_active = 'Y' ORDER BY currency ASC";
		$result = db_query_array($sql);
		
		if ($result) {
			foreach ($result as $row) {
				$currencies[$row['currency']] = $row;
			}
		}
		return $currencies;
	}
}