<?php
class String {
	function substring($string,$length=0,$start=0) {
		if ($length == 0) {
			return $string;
		}
		else {
			if (strlen($string) > $length) 
				$suffix = '...';
				
			$new_string = substr($string,$start,$length);
			if (($start + $length) < strlen($string))
				$new_string = $new_string.$suffix;
			return $new_string;
		}
	}
}
?>