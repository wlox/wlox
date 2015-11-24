<?php
class BankAccounts{
	public static function get($currency_id=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$currency_id = preg_replace("/[^0-9]/", "",$currency_id);
		
		$sql = "SELECT bank_accounts.*, currencies.currency AS currency FROM bank_accounts LEFT JOIN currencies ON (bank_accounts.currency = currencies.id) WHERE 1 AND site_user = ".User::$info['id'];

		if ($currency_id > 0)
			$sql .= " AND bank_accounts.currency = $currency_id ";

		$result = db_query_array($sql);
		
		if ($result) {
			foreach ($result as $row) {
				$return[$row['account_number']] = $row;
			}
			return $return;
		}
		return false;
	}
	
	public static function getRecord($id=false,$account_number=false) {
		global $CFG;
		
		$id = preg_replace("/[^0-9]/", "",$id);
		$account_number = preg_replace("/[^0-9]/", "",$account_number);
		
		if (!$CFG->session_active && !(($id > 0) || $account_number > 0))
			return false;
		
		$sql = 'SELECT * FROM bank_accounts WHERE '.(($id > 0) ? " id = $id " : " account_number = $account_number ").' AND site_user = '.User::$info['id'];
		$result = db_query_array($sql);
		
		if ($result)
			return $result[0];
		else
			return false;
	}
	
	public static function find($account_number) {
		global $CFG;
		
		if (!$CFG->session_active || !$account_number)
			return false;
		
		$account_number = preg_replace("/[^0-9]/", "",$account_number);
		
		$sql = "SELECT * FROM bank_accounts WHERE account_number = $account_number";
		$result = db_query_array($sql);
		
		if ($result)
			return $result[0];
	}
	
	public static function insert($account,$currency,$description=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$account = preg_replace("/[^0-9]/", "",$account);
		$currency = preg_replace("/[^0-9]/", "",$currency);
		$description = preg_replace("/[^0-9a-zA-Z!@#$%&*?\.\-\_ ]/",'',$description);
		
		db_insert('bank_accounts',array('account_number'=>$account,'currency'=>$currency,'description'=>$description,'site_user'=>User::$info['id']));
	}
	
	public static function delete($remove_id) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$sql = 'SELECT id FROM bank_accounts WHERE id = '.$remove_id.' AND site_user = '.User::$info['id'];
		$result = db_query_array($sql);
		if (!$result)
			return false;
		
		$remove_id = preg_replace("/[^0-9]/", "",$remove_id);
		
		db_delete('bank_accounts',$remove_id);
	}
}