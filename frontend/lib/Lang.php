<?php
class Lang {
	function string($key=false) {
		global $CFG;
		
		if (empty($key))
			return false;
			
		return $CFG->lang_table[$key][$CFG->language];
	}
}
?>