<?

function validate_email($address) {
	return (ereg ( '^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+' . '@' . '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.' . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $address ));
}

function is_po_box($address) {
	return preg_match ( "/\s*P\.?O\.?\s+b/i", $address );
}

function xmlentities($string, $quote_style = ENT_COMPAT) {
	$trans = get_html_translation_table ( HTML_ENTITIES, $quote_style );
	
	foreach ( $trans as $key => $value )
		$trans [$key] = '&#' . ord ( $key ) . ';';
	
	return strtr ( $string, $trans );
}

function print_ar($arr, $html_entities = false, $no_interpolation = false, $in_recursion = false) {
	/**Rewritten 12/21/05 by Gershom to handle objects, scalars, bools***/
	
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

function setImageSize($image_file, $maxSize = 100) {
	$image_size = GetImageSize ( $image_file, $image_info );
	$width = $image_size [0];
	$height = $image_size [1];
	
	if ($width > $maxSize) {
		$z = $width;
		$i = 0;
		while ( $z > $maxSize ) {
			-- $z;
			++ $i;
		}
		
		$imgStr = "width=" . ( int ) $z . " height=" . ( int ) ($height - ($height * ($i / $width)));
	
	} else {
		$imgStr = "width=$width height=$height";
	}
	
	return $imgStr;
}

?>