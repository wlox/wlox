<?php
class Link {
	function redirect($url,$variables=false,$inside_iframe=false) {
		if ($variables) {
			$vars = http_build_query($variables);
			$vars = (!$vars) ? $vars : '?'.$vars;
		}

		if (!$inside_iframe) {
			header( 'Location: '.$url.$vars );
			exit;
		}
		else {
			echo '
			<script type="text/javascript">
				parent.location.href = "'.$url.$vars.'";
			</script>';
		}
	}
}
?>