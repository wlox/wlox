<?php

class Settings {
	function assign($all) {
		global $CFG;
		
		if (is_array ( $all )) {
			if (is_object ( $CFG )) {
				foreach ( $all as $row ) {
					$normalized_name = Settings::_normalizeName ( $row['name'] );
					if (!is_array($row['value']))
						$CFG->$normalized_name = html_entity_decode($row['value']);
					else
						$CFG->$normalized_name = $row['value'];
				}
			} else {
				foreach ( $all as $row ) {
					$normalized_name = Settings::_normalizeName ( $row ['name'] );
					if (!is_array($row['value']))
						$CFG->$normalized_name = html_entity_decode($row['value']);
					else
						$CFG->$normalized_name = $row['value'];
				}
			}
		}
	}
	
	function _normalizeName($str) {
		$str = strtolower ( $str );
		return $str;
		//return preg_replace ( '/[^a-z0-9_]/', '', $str );
	}
}

?>