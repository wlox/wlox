<?php

class Settings {
	public static function assign($all) {
		global $CFG;
		
		if (is_array($all) && is_object($CFG)) {
			foreach ($all as $name => $value) {
				$name = str_replace('frontend_','',$name);
				$CFG->$name = $value;
			}
		}
	}
}

?>