<?php
class Email {	
	function verifyAddress($email) {
		$exp = "^[a-z\'0-9]+([._-][a-z\'0-9]+)*@([a-z0-9]+([._-][a-z0-9]+))+$";
   		if(eregi($exp,$email)){
			return checkdnsrr(array_pop(explode("@",$email)),"MX");
   		}
   		else
   			return false;
	}
	
}
?>