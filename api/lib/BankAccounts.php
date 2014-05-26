<?php
class BankAccounts{
	function get($currency_id=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$sql = "SELECT bank_accounts.*, currencies.currency AS currency FROM bank_accounts LEFT JOIN currencies ON (bank_accounts.currency = currencies.id) WHERE 1 AND site_user = ".User::$info['id'];

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
	
	function getRecord($id) {
		global $CFG;
		
		if (!$CFG->session_active || !($id > 0))
			return false;
		
		$id1 = ereg_replace("[^0-9]", "",$id);
		
		return DB::getRecord('bank_accounts',$id1,0,1);
	}
	
	function find($account_number) {
		global $CFG;
		
		if (!$CFG->session_active || !$account_number)
			return false;
		
		$sql = "SELECT * FROM bank_accounts WHERE account_number = $account_number";
		$result = db_query_array($sql);
		
		if ($result)
			return $result[0];
	}
	
	function insert($account,$currency,$description=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		db_insert('bank_accounts',array('account_number'=>$account,'currency'=>$currency,'description'=>$description,'site_user'=>User::$info['id']));
	}
	
	function delete($remove_id) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		db_delete('bank_accounts',$remove_id);
	}
}