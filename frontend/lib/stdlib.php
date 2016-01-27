<?
function print_ar($arr, $html_entities = false, $no_interpolation = false, $in_recursion = false) {
	if ($in_recursion)
		$width = " width='100%'";
	else
		$width = " width='30'";
	echo "<table border=0 cellspacing=0 cellpadding=0 $width>";
	if (is_object ( $arr ))
		$arr = ( array ) $arr;
	if (! is_array ( $arr )) {
		if ($arr === false)
			$arr = '<I>false</I>';
		if ($arr === true)
			$arr = '<I>true</I>';
		if (! $in_recursion)
			echo '<tr bgcolor="#eeeeee"><td align=right valign=top><b>(scalar):</b>&nbsp;</td><td>';
		else
			echo '<tr><td>';
		if ($html_entities)
			$arr = 'stripped-HTML:' . htmlentities ( $arr );
		
		if (! $no_interpolation)
			echo "$arr";
		else
			echo $arr;
		echo '</td></tr>';
	} else {
		foreach ( $arr as $key => $val ) {
			$bgcolor = (@$bgcolor == 'eeeeee') ? 'ffffff' : 'eeeeee';
			echo "<tr bgcolor=\"#$bgcolor\"><td align=right valign=top><b>$key:</b>&nbsp;</td><td align=left>";
			print_ar ( $val, $html_entities, $no_interpolation, true );
		}
	}
	echo ("</table>");
	if (! $in_recursion) {
		//		echo("</table>");
	}
}

function session_regenerate() {
	$session_data = $_SESSION;
	session_destroy();
	session_start();
	session_regenerate_id();
	$_SESSION = $session_data;
}

function session_readonly() {
	$session_path = session_save_path();
	$session_name = session_name();
	$session_key = 'KEY_'.$session_name;
	
	if (empty($session_name) || empty($session_key) || empty($_COOKIE[$session_name]) || empty($_COOKIE[$session_key]))
		return false;
	
	$session_id = preg_replace('/[^\da-z]/i','',$_COOKIE[$session_name]);
	
	$key = false;
	$auth = false;
	
	if (!file_exists($session_path.'/'.$session_name.'_'.$session_id))
		return false;
	
	$encoded_data = file_get_contents($session_path.'/'.$session_name.'_'.$session_id);
	if (empty($encoded_data))
		return false;
	
	list($key,$auth) = explode (':',$_COOKIE[$session_key]);
	$key = base64_decode($key);
	$auth = base64_decode($auth);
	
	list($hmac,$iv,$encrypted) = explode(':',$encoded_data);
	$iv = base64_decode($iv);
	$encrypted = base64_decode($encrypted);
	$newHmac = hash_hmac('sha256',$iv.MCRYPT_RIJNDAEL_128.$encrypted,$auth);
	
	if ($hmac !== $newHmac)
		return false;
	
	$decrypt = mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$key,$encrypted,MCRYPT_MODE_CBC,$iv);
	$raw_data = rtrim($decrypt, "\0");
	$method = ini_get("session.serialize_handler");
	
	if (empty($raw_data) || empty($method))
		return false;
	
	if ($method == 'php')
		$_SESSION = unserialize_php($raw_data);
	elseif ($method == 'php_binary')
		$_SESSION = unserialize_phpbinary($raw_data);
	else
		return false;
}

function unserialize_php($session_data) {
	$return_data = array();
	$offset = 0;
	while ($offset < strlen($session_data)) {
		if (!strstr(substr($session_data, $offset), "|")) {
			trigger_error('Invalid session data.',E_USER_NOTICE);
			return false;
		}
			
		$pos = strpos($session_data, "|", $offset);
		$num = $pos - $offset;
		$varname = substr($session_data, $offset, $num);
		$offset += $num + 1;
		$data = unserialize(substr($session_data, $offset));
		$return_data[$varname] = $data;
		$offset += strlen(serialize($data));
	}
	return $return_data;
}

function unserialize_phpbinary($session_data) {
	$return_data = array();
	$offset = 0;
	while ($offset < strlen($session_data)) {
		$num = ord($session_data[$offset]);
		$offset += 1;
		$varname = substr($session_data, $offset, $num);
		$offset += $num;
		$data = unserialize(substr($session_data, $offset));
		$return_data[$varname] = $data;
		$offset += strlen(serialize($data));
	}
	return $return_data;
}

if (!function_exists('mb_strlen')) {
	function mb_strlen($utf8string=false) {
		if (empty($utf8string))
			return false;

		return preg_match_all("/.{1}/us",$utf8string,$dummy);
	}
}

?>