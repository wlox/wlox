<?php
class Email {	
	public static function send($from,$recipients,$subject,$from_name=false,$text_version=false,$html_version=false,$variables=false) {
		global $CFG;

		$reply_to = $from;
		//$from = ($from_name) ? '"'.$from_name.'" <'.$from.'>' : $from;
		/*
		if (is_array($recipients)) {
			foreach ($recipients as $name => $email) {
				if (!self::verifyAddress($email)) {
					$errors[$email] = $CFG->invalid_email_error;
					unset($recipients[$name]);
					continue;
				}
				if (!is_numeric($name)) 
					$recipients[$name] = "\"{$name}\" <{$email}>";
			}
			if (!empty($recipients))
				$to = implode(',',$recipients);
		}
		else {
			if (self::verifyAddress($recipients)) {
				$to = $recipients;
			}
			else {
				$errors[$recipients] = $recipients;
			}
		}
		*/

		if (is_array($variables)) {
			foreach ($variables as $name => $value) {
				$var_string .= '
					'.ucfirst(str_ireplace('_',' ',$name)).': '.$value.'<br/>';
			}
		}
		
		$html_version = str_ireplace('[variables]',$var_string,$html_version);
		$text_version = str_ireplace('[variables]',$var_string,$text_version);
		
		if (is_array($variables)) {
			if (!$CFG->backstage_mode) {
					foreach ($variables as $key => $val) {
						$html_version = str_ireplace('['.$key.']',$val,$html_version);
						$text_version = str_ireplace('['.$key.']',$val,$text_version);
						$subject = str_ireplace('['.$key.']',$val,$subject);
					}
			}
			else {
				$matches = String::getSubstring($html_version,'[',']');
				if (is_array($matches)) {
					foreach ($matches as $match) {
						$f_id = $variables['id'];
						if (strstr($match,',')) {
							$value = DB::getForeignValue($match,$f_id);
						}
						elseif(array_key_exists($match,$variables)) {
							$value = $variables[$match];
						}
						elseif(strstr($match,'.')) {
							$parts = explode('.',$match);
							$sql = "SELECT {$match[1]} FROM {$match[0]} WHERE f_id = $f_id";
							$result = db_query_array($sql);
							if ($result) {
								$m1 = $match[1];
								$value = $result[0][$m1];
							}
						}
						elseif (stristr($match,'curdate')) {
							$operation = str_ireplace('curdate','',$match);

							if (empty($operation)) {
								$value = date($CFG->default_date_format);
							}
							else {
								$value = date($CFG->default_date_format,strtotime($operation));
							}
						}

						$html_version = str_ireplace('['.$match.']',$value,$html_version);
						$text_version = str_ireplace('['.$match.']',$value,$text_version);
						$subject = str_ireplace('['.$match.']',$value,$subject);
					}
				}
			}
		}
		
		$html_version = str_ireplace('[curdate]',date($CFG->default_date_format),$html_version);
		$text_version = str_ireplace('[curdate]',date($CFG->default_date_format),$text_version);
		$subject = str_ireplace('[curdate]',date($CFG->default_date_format),$subject);

		if (!$text_version) {
			include_once 'html2text.php';
			
			$h2t =& new html2text($html); 
			$h2t->set_base_url($CFG->baseurl);
			$text_version = $h2t->get_text();
		}
		
		if (!$html_version) {
			$html_version = nl2br($text_version);
		}

		/*
		$message = '
		
------=_Part_40832071_1556867510.1259294982273
Content-Type: text/plain; charset=iso-8859-1
Content-Transfer-Encoding: 7bit

';
		$message .= $text_version;
		
		$message .= '
		
------=_Part_40832071_1556867510.1259294982273
Content-Type: text/html; charset=iso-8859-1
Content-Transfer-Encoding: quoted-printable

';
		$message .= $html_version;
		
		$message .= '
		
------=_Part_40832071_1556867510.1259294982273--
';

		if ($errors) {
			Errors::merge($errors);
			return false;
		}
		if(mail($to, $subject, $message, $headers)) {
			Messages::add($CFG->email_sent_message);
			return true;
		}
		else {
			Errors::add($CFG->email_send_error);
			return false;
		}
		*/

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
		$mail->setFrom($from,$from_name);
		$mail->addReplyTo($from);
		
		if (is_array($recipients)) {
			foreach ($recipients as $name => $email) {
				if (!self::verifyAddress($email)) {
					$errors[$email] = $CFG->invalid_email_error;
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
			else {
				$errors[$recipients] = $recipients;
			}
		}
		
		$mail->Subject = $subject;
		$mail->msgHTML($html_version);
		$mail->AltBody = $text_version;

		if($mail->send()) {
			Messages::add($CFG->email_sent_message);
			return true;
		}
		else {
			Errors::add($mail->ErrorInfo);
			return false;
		}
		
	}
	
	public static function verifyAddress($email) {
		$exp = "^[a-z\'0-9]+([._-][a-z\'0-9]+)*@([a-z0-9]+([._-][a-z0-9]+))+$";
   		if(eregi($exp,$email)){
			return checkdnsrr(array_pop(explode("@",$email)),"MX");
   		}
   		else
   			return false;
	}
	
}
?>