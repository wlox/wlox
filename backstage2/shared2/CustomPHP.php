<?php 
class CustomPHP{
	function __construct($url) {
		global $CFG;
		
		if (!$url)
			return false;
		
		include 'custom/'.$url;
	}
	
	function display() {
	}
}
?>