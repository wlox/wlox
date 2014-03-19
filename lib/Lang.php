<?php
class Lang {
	function getTable() {
		$sql = "SELECT * FROM lang";
		$result = db_query_array($sql);
		
		if ($result) {
			foreach ($result as $row) {
				$key = $row['key'];
				$lang_table[$key]['es'] = str_replace('[exchange_name]',$CFG->exchange_name,$row['esp']);
				$lang_table[$key]['en'] = str_replace('[exchange_name]',$CFG->exchange_name,$row['eng']);
			}
		}
		return $lang_table;
	}
	
	function string($key=false) {
		global $CFG;
		
		if (empty($key))
			return false;
			
		return $CFG->lang_table[$key][$CFG->language];
	}
	
	function getLanguage($country_code) {
		global $CFG;
	
		if (strlen($country_code) < 2)
			return false;
	
		$country_code = ereg_replace("[^A-Z]", "",$country_code);
		$sql = "SELECT * FROM iso_countries WHERE code = '$country_code' ";
		$result = db_query_array($sql);
	
		$CFG->currency = $result[0]['currency'];
	
		if (strlen($result[0]['locale']) == 2) {
			if ($result[0]['locale'] == 'es')
				return 'es';
			else
				return 'en';
		}
		else
			return 'es';
	}
	
	function getCountry($country_code) {
		if (strlen($country_code) < 2)
			return false;
	
		$country_code = ereg_replace("[^A-Z]", "",$country_code);
		$sql = "SELECT * FROM iso_countries WHERE code = '$country_code' ";
		$result = db_query_array($sql);
		return $result[0];
	}
	
	function getCountries() {
		$sql = "SELECT * FROM iso_countries ORDER BY name ASC ";
		return db_query_array($sql);
	}
}
?>