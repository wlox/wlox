<?php
class SiteEmail{
	function getRecord($key){
		global $CFG;
		
		if (empty($key))
			return false;		
		$sql="SELECT * FROM emails WHERE emails.key='$key' ";	
		$result = db_query_array($sql);
		
		$result[0]['title'] = ($CFG->language == 'en' && !empty($result[0]['title_en'])) ? $result[0]['title_en']: $result[0]['title_es'];
		$result[0]['content'] = ($CFG->language == 'en' && !empty($result[0]['content_en'])) ? $result[0]['content_en']: $result[0]['content_es'];
		return $result[0];
	}
	
	function emailExists($email) {
		$sql = "SELECT * FROM mailing_list WHERE email = '$email' ";
		$result = db_query_array($sql);
		return ($result);
	}
}
?>