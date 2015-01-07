<?php
class Content{
	
	public static function getRecord($url){
		global $CFG;
		
		if(empty($url))
			return false;
			
		$sql = "SELECT * FROM content WHERE url = '$url' ";
		$result = db_query_array($sql);
		
		$result[0]['title'] = (empty($result[0]['title_'.$CFG->language])) ? $result[0]['title']: $result[0]['title_'.$CFG->language];
		$result[0]['content'] = (empty($result[0]['content_'.$CFG->language])) ? $result[0]['content']: $result[0]['content_'.$CFG->language];
		return $result[0];				
	}
	
}
?>