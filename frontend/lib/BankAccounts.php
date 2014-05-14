<?php
class BankAccounts{
	function get($site_user=false,$currency_id=false) {
		$sql = "SELECT * FROM bank_accounts WHERE 1";
		
		if ($site_user > 0)
			$sql .= " AND site_user = $site_user ";
		if ($currency_id > 0)
			$sql .= " AND currency = $currency_id ";

		$result = db_query_array($sql);
		
		if ($result) {
			foreach ($result as $row) {
				$return[$row['account_number']] = $row;
			}
		}
		return $return;
	}
	
	function find($account_number) {
		$sql = "SELECT * FROM bank_accounts WHERE account_number = $account_number";
		$result = db_query_array($sql);
		
		if ($result)
			return $result[0];
	}
}