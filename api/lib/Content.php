<?php
class Content{
	public static function getRecord($url){
		global $CFG;
		
		$url = preg_replace("/[^0-9a-zA-Z!@#$%&*?\.\-_]/", "",$url);
		
		if(empty($url))
			return false;
		
		if ($CFG->memcached) {
			$cached = $CFG->m->get('content_'.$url.'_'.$CFG->language);
			if ($cached) {
				return $cached;
			}
		}
			
		$sql = "SELECT * FROM content WHERE url = '$url' ";
		$result = db_query_array($sql);
		
		if ($result) {
			$result[0]['title'] = (empty($result[0]['title_'.$CFG->language])) ? $result[0]['title']: $result[0]['title_'.$CFG->language];
			$result[0]['content'] = (empty($result[0]['content_'.$CFG->language])) ? $result[0]['content']: $result[0]['content_'.$CFG->language];
			$result[0]['title'] = str_replace('[exchange_name]',$CFG->exchange_name,str_replace('[baseurl]',$CFG->frontend_baseurl,$result[0]['title']));
			$result[0]['content'] = str_replace('[exchange_name]',$CFG->exchange_name,str_replace('[baseurl]',$CFG->frontend_baseurl,$result[0]['content']));
			
			if ($CFG->memcached)
				$CFG->m->set('content_'.$url.'_'.$CFG->language,$result[0],300);
			
			return $result[0];
		}
		return false;				
	}
	
}
?>