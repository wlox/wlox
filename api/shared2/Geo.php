<?php
class Geo {
	public static $info;
	
	function getRemote($ip=false) {
		$ip = ($ip) ? $ip : $_SERVER['REMOTE_ADDR'];
		$info = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_SERVER['REMOTE_ADDR']));
		self::$info = $info;
		
		return $info;
	}
	
	function setTimezone($zone_name=false) {
		global $CFG;
		
		if ($zone_name) {
			date_default_timezone_set($zone_name);
		}
		elseif (self::$info) {
			$continents = array('AF'=>'Africa','AS'=>'Asia','EU'=>'Europe','NA'=>'America','OC'=>'Oceania','SA'=>'America');
			$zones = timezone_identifiers_list();
			
		}
		elseif(!empty($CFG->default_timezone)) {
			date_default_timezone_set($CFG->default_timezone);
		}

		return date_default_timezone_get();
	}
}
?>