<?php
class Email {	
	public static function verifyAddress($email) {
   		if(preg_match("/[0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/",$email)){
   			$email_parts = explode("@",$email);
			return checkdnsrr(array_pop($email_parts),"MX");
   		}
   		else
   			return false;
	}
	
}
?>