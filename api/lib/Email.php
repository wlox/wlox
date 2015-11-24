<?php
class Email {	
	public static function send($from,$recipients,$subject,$from_name=false,$text_version=false,$html_version=false,$variables=false) {
		global $CFG;

		$reply_to = $from;
		$var_string = '';
		if (is_array($variables)) {
			foreach ($variables as $name => $value) {
				$var_string .= '
					'.ucfirst(str_ireplace('_',' ',$name)).': '.$value.'<br/>';
			}
		}
		
		$html_version = str_ireplace('[variables]',$var_string,$html_version);
		$text_version = str_ireplace('[variables]',$var_string,$text_version);
		$html_version = str_ireplace('&amp;','&',$html_version);
		$text_version = str_ireplace('&amp;','&',$text_version);
		
		if (is_array($variables)) {
			foreach ($variables as $key => $val) {
				$html_version = str_ireplace('['.$key.']',$val,$html_version);
				$text_version = str_ireplace('['.$key.']',$val,$text_version);
				$subject = str_ireplace('['.$key.']',$val,$subject);
			}
		}

		if (!$text_version) {
			include_once 'html2text.php';
			
			$h2t = new html2text($html_version); 
			$h2t->set_base_url($CFG->frontend_baseurl);
			$text_version = $h2t->get_text();
		}
		
		if (!$html_version) {
			$html_version = nl2br($text_version);
		}

		include_once 'phpmailer/PHPMailerAutoload.php';
		
		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->CharSet = 'UTF-8';
		$mail->SMTPDebug = 0;
		$mail->Debugoutput = 'html';
		$mail->Host = $CFG->email_smtp_host;
		$mail->Port = $CFG->email_smtp_port;
		$mail->SMTPSecure = $CFG->email_smtp_security;
		$mail->SMTPAuth = true;
		$mail->Username = $CFG->email_smtp_username;
		$mail->Password = $CFG->email_smtp_password;
		$mail->setFrom($CFG->email_smtp_send_from,$from_name);
		$mail->addReplyTo($from);
		
		if (is_array($recipients)) {
			foreach ($recipients as $name => $email) {
				if (!self::verifyAddress($email)) {
					unset($recipients[$name]);
					continue;
				}
				$mail->addAddress($email,$name);
			}
		}
		else {
			if (self::verifyAddress($recipients)) {
				$mail->addAddress($recipients);
			}
		}
		
		$mail->Subject = $subject;
		$mail->msgHTML($html_version);
		$mail->AltBody = $text_version;

		if($mail->send()) {
			return true;
		}
		else {
			trigger_error('Email could not be sent: '.print_r($mail->ErrorInfo,true),E_USER_WARNING);
			return false;
		}
		
	}
	
	public static function verifyAddress($email) {
		$email_parts = explode("@",$email);
		return checkdnsrr(array_pop($email_parts),"MX");

	}
	
}
?>