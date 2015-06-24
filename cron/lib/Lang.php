<?php
class Lang {
	public static function getTable() {
		global $CFG;
		$sql = "SELECT * FROM lang";
		$result = db_query_array($sql);
		
		if ($result) {
			foreach ($result as $row) {
				$key = $row['key'];
				$lang_table[$key]['es'] = str_replace('[exchange_name]',$CFG->exchange_name,$row['esp']);
				$lang_table[$key]['en'] = str_replace('[exchange_name]',$CFG->exchange_name,$row['eng']);
				$lang_table[$key]['ru'] = str_replace('[exchange_name]',$CFG->exchange_name,$row['ru']);
				$lang_table[$key]['zh'] = str_replace('[exchange_name]',$CFG->exchange_name,$row['zh']);
				$lang_table[$key]['es'] = str_replace('[baseurl]',$CFG->frontend_baseurl,$row['esp']);
				$lang_table[$key]['en'] = str_replace('[baseurl]',$CFG->frontend_baseurl,$row['eng']);
				$lang_table[$key]['ru'] = str_replace('[baseurl]',$CFG->frontend_baseurl,$row['ru']);
				$lang_table[$key]['zh'] = str_replace('[baseurl]',$CFG->frontend_baseurl,$row['zh']);
			}
		}
		return $lang_table;
	}
	
	public static function string($key=false) {
		global $CFG;
	
		if (empty($key))
			return false;
		
		$lang = (empty($CFG->language)) ? 'eng' : $CFG->language;
		if ($lang == 'en')
			$lang = 'eng';
		else if ($lang == 'es')
			$lang = 'esp';
			
		$sql = 'SELECT '.$lang.' AS line FROM lang WHERE `key` = "'.$key.'" LIMIT 0,1';
		$result = db_query_array($sql);
		if ($result)
			return $result[0]['line'];
		else
			return false;
	}
}
?>
