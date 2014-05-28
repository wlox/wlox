<?php
class Content{
	
	function getRecord($url){
		global $CFG;
		
		$url = preg_replace("/[^0-9a-zA-Z!@#$%&*?\.\-_]/", "",$url);
		
		if(empty($url))
			return false;
			
		$sql = "SELECT * FROM content WHERE url = '$url' ";
		$result = db_query_array($sql);
		
		$result[0]['title'] = (empty($result[0]['title_'.$CFG->language])) ? $result[0]['title']: $result[0]['title_'.$CFG->language];
		$result[0]['content'] = (empty($result[0]['content_'.$CFG->language])) ? $result[0]['content']: $result[0]['content_'.$CFG->language];
		$result[0]['title'] = str_replace('[exchange_name]',$CFG->exchange_name,$result[0]['title']);
		$result[0]['content'] = str_replace('[exchange_name]',$CFG->exchange_name,$result[0]['content']);
		return $result[0];				
	}
	
}
?>