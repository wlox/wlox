<?php
class Ops {
	function getTabs() {
		$sql = "SELECT * FROM admin_tabs WHERE hidden != 'Y' AND is_ctrl_panel != 'Y' ORDER BY admin_tabs.order ASC, admin_tabs.name ASC ";
		return db_query_array($sql);
	}
	
	function getPages($tab_id) {
		if (!($tab_id > 0))
			return false;
			
		$sql = "SELECT * FROM admin_pages WHERE f_id = ".$tab_id." ORDER BY admin_pages.order ASC, admin_pages.name ASC ";
		return db_query_array($sql);
	}
	
	function getPageTitle() {
		global $CFG;
		
		if (!$CFG->pm_editor)
			$page_info = DB::getRecord((($CFG->is_tab) ? 'admin_tabs' : 'admin_pages'),false,$CFG->url,0,'url');
		else
			$page_info = DB::getRecord((($CFG->is_tab) ? 'admin_tabs' : 'admin_pages'),$CFG->id,0,1);
			
		$string = $page_info['name'];
		if (($CFG->action == 'form' || $CFG->action == 'record') && $CFG->id > 0) {
			if (substr($string,-1) == 's') {
				$string = substr($string,0,-1).' #'.$CFG->id;
			}
		}
		return $string;
	}
}
?>