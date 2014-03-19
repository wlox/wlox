<?php
class Arr {
	private static $cmp_subfield,$reverse;
	
	function sortBySubValue($array, $subfield_name,$reverse=false) {
		self::$cmp_subfield = $subfield_name;
		self::$reverse = $reverse;
		uasort($array, array('self','cmp')); 
		self::$cmp_subfield = false;
		self::$reverse = false;
		return $array;
	}
	
	private static function cmp($a,$b) {
		$retval = strnatcmp($a[self::$cmp_subfield], $b[self::$cmp_subfield]);
        return $retval;
	}
}
?>