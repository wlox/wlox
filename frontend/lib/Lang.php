<?php
class Lang {
	public static function string($key=false) {
		global $CFG;
		
		if (empty($key))
			return false;
			
		if (!empty($CFG->lang_table[$key][$CFG->language]))
			return $CFG->lang_table[$key][$CFG->language];
		else
			return false;
	}
}
?>