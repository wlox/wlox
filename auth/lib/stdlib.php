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


?>