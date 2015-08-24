<?php
class Link {
	function url($url=false,$caption=false,$query_string=false,$variables=false,$get_query_persists=false,$target_elem_id=false,$class=false,$custom_animation=false,$window_target=false,$anchor=false,$disabled=false,$title=false) {
		global $CFG;
		
		$url = ($url) ? $url : $CFG->self;
		$query_char = (!strstr($url,'?')) ? '?' : '';
		$variables = (is_array($variables)) ? $variables : array();
		$ajax = ($target_elem_id) ? $CFG->ajax : false;
		$dis_class = ($disabled) ? ' disabled' : '';
		$class = ($class) ? "class=\"{$class}{$dis_class}\"" : '';
		$window_target = ($window_target == true) ? 'target=”_blank”' : $window_target;
		$anchor = ($anchor) ? '#'.$anchor : false;
		$title = ($title) ? 'title="'.$title.'"' : false;
			
		if (!$disabled) {
			if ($get_query_persists) {
				$p1 = explode('&',$_SERVER['QUERY_STRING']);
				if (is_array($p1)) {
					foreach ($p1 as $value) {
						$p2 = explode('=',$value);
						$key = $p2[0];
						if (!empty($key))
							$p_variables[$key] = $p2[1];
					}
				}
				elseif (strstr($_SERVER['QUERY_STRING'],'=')) {
					$p2 = explode('=',$_SERVER['QUERY_STRING']);
					$key = $p2[0];
					if (!empty($key))
						$p_variables[$key] = $p2[1];
				}
			}
			$q1 = explode('&',$query_string);
			if (is_array($q1)) {
				foreach ($q1 as $value) {
					$q2 = explode('=',$value);
					$key = $q2[0];
					if (!empty($key))
						$q_variables[$key] = $q2[1];
				}
			}
			elseif (strstr($query_string,'=')) {
				echo $query_string;
				$q2 = explode('=',$query_string);
				$key = $q2[0];
				if (!empty($key))
					$q_variables[$key] = $q2[1];
			}
				
			$p_variables = (is_array($p_variables)) ? $p_variables : array();
			$q_variables = (is_array($q_variables)) ? $q_variables : array();
			$all_variables = array_merge($p_variables,$q_variables,$variables);
			
			if ($all_variables)
				unset($all_variables['submit']);
			
			if ($CFG->backstage_mode) {
				$all_variables['current_url'] = $url;
				$url = 'index.php';
			}
			
			$query_char = (!empty($all_variables)) ? $query_char : '';
			$str = $url.$query_char.http_build_query($all_variables);
			if ($ajax) {
				$url = " <a $window_target $class href=\"$str\" $title  onclick=\"ajaxGetPage('$str','$target_elem_id',false,'$custom_animation'); return false;\">$caption</a> ";
			}
			else {
				$url = ' <a '.$window_target.' '.$class.' '.$title.' href="'.$str.$anchor.'">'.$caption.'</a> ';
			}
		}
		else {
			$url = ' <a '.$class.' href="#" onclick="return false;">'.$caption.'</a> ';
		}
		
		return $url;
	}
	
	function parseVariables($variables) {
		$ignore = array('session_id','SESSION_ID','submit');
		if (is_array($variables)) {
			$str = '';
			foreach ($variables as $name => $value) {
				if (!is_array($value)) {
					if (!empty($value) && !in_array($name,$ignore)) {
						$str .= "$name=$value&";
					}
				}
				else {
					$str .= Link::parseVariables($value);
				}
			}
		}
		$str = substr($str,0,-1);
		return $str;
	}
	
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
	
	function executeScript($url,$variables=false,$method=false,$is_local=false) {
		$method = (!empty($method)) ? $method : 'POST'; 
		$postdata = http_build_query($variables);
		
		if (!$is_local) {
			$header = array('http' =>
			    array(
			        'method'  => $method,
			        'header'  => 'Content-type: application/x-www-form-urlencoded',
			        'content' => $postdata
			    )
			);
			
			$context = stream_context_create($header);
			return file_get_contents($url, false, $context);
		}
		else {
			exec($url." 2>&1",$output);
			return implode(' ',$output);
		}
		
	}
	
	function parsePaypalInfo($info) {
		if (!empty($info)) {
			$p_variables = array();
			$p1 = explode(' ',$info);
			foreach ($p1 as $value) {
				$p2 = explode('=',$value);
				$key = $p2[0];
				if (!empty($key))
					$p_variables[$key] = $p2[1];
			}
		}
		return $p_variables;
	}
	
	function showIcon($url) {
		global $CFG;
		
		$info = pathinfo($url);
		$ext = $info['extension'];
		
		//if (!File::fileExists($url))
			//return $CFG->icon_broken;
		
		if (stristr($url,'video.google.com') || stristr($url,'www.youtube.com')) {
			$img = $CFG->icon_vid;
		}
		else {
			switch (strtolower($ext)) {
				case 'jpg':
				case 'jpeg':
					$img = $CFG->icon_jpg;
				break;
				case 'gif':
					$img = $CFG->icon_gif;
				break;
				case 'zip':
					$img = $CFG->icon_zip;
				break;
				case 'psd':
					$img = $CFG->icon_psd;
				break;
				case 'png':
					$img = $CFG->icon_png;
				break;
				case 'pdf':
					$img = $CFG->icon_pdf;
				break;
				case 'psd':
					$img = $CFG->icon_psd;
				break;
				case 'mp3':
				case 'wav':
					$img = $CFG->icon_play;
				break;
				default:
					$img = $CFG->icon_html;
				break;
			}
		}
		return '<img src="'.$img.'" />';
	}
}
?>