<?php


class String {
	public static $open_tags,$missing;
	
	public static function substring($string,$length=0,$start=0) {
		if ($length == 0) {
			return $string;
		}
		else {
			if (strlen($string) > $length) 
				$suffix = '...';
				
			$new_string = substr($string,$start,$length);
			if (($start + $length) < strlen($string))
				$new_string = $new_string.$suffix;
			return $new_string;
		}
	}
	
	public static function getSubstring($string,$delimiter1,$delimiter2) {
		$delimiter1 = preg_quote($delimiter1);
		$delimiter2 = preg_quote($delimiter2);

		preg_match_all("/({$delimiter1}){1}([^\[\]]+)({$delimiter2}){1}/",$string,$matches);
		return $matches[2];
	}
	
	public static function getUrlFromEmbed($string) {
		$parts = explode('"',$string);
		if (is_array($parts)) {
			foreach ($parts as $part) {
				if (stristr($part,'http')) {
					return $part;
				}
			}
		}
	}
	
	public static function showPath() {
		global $CFG;
		
		if ($_REQUEST['tab_bypass'])
			return false;
		
		if ($CFG->url) {
			$sql = "SELECT admin_tabs.name AS tab_name,admin_tabs.url AS tab_url".((!$CFG->is_tab) ? ",admin_pages.name AS page_name, admin_pages.url AS page_url" : '')
			." FROM admin_tabs".((!$CFG->is_tab) ? ",admin_pages" : '')
			." WHERE 1 ".((!$CFG->is_tab) ? "AND admin_pages.url = '$CFG->url' AND admin_pages.f_id = admin_tabs.id" : " AND admin_tabs.url = '$CFG->url'");
			$result = db_query_array($sql);
		}
		
		echo '<div class="path">';
		echo '<a href="'.$CFG->self.'">'.$CFG->home_label.'</a>';
		
		if ($result) {
			echo $CFG->path_separator;
			echo (!empty($result[0]['tab_url'])) ? Link::url($result[0]['tab_url'],$result[0]['tab_name'],false,array('is_tab'=>1)) : $result[0]['tab_name'];
			if (!$CFG->is_tab) {
				echo $CFG->path_separator;
				echo (!empty($result[0]['page_url'])) ? Link::url($result[0]['page_url'],$result[0]['page_name']) : $result[0]['page_name'];
			}
			if ($CFG->action) {
				if ($CFG->action == 'form')
					$action = $CFG->path_edit;
				elseif ($CFG->action == 'record')
					$action = $CFG->path_view;
				
				echo $CFG->path_separator.$action;
			}
		}
		else {
			if (!$CFG->url)
				echo $CFG->path_separator.$CFG->path_ctrl;
			else
				switch ($CFG->url) {
					case 'edit_tabs':
						echo $CFG->path_separator.$CFG->path_edit_tabs;
					break;
					case 'users':
						echo $CFG->path_separator.$CFG->path_users;
					break;
					case 'settings':
						echo $CFG->path_separator.$CFG->path_settings;
					break;
				}
		}
		echo '</div>';
	}
	
	public static function splitKeywords($string) {
		$keywords = explode(' ',$string);
		return '%'.implode('%',$keywords).'%';
	}
	
	public static function fauxArray($array,$not_root=false) {
		if (!is_array($array)) 
			return $array;

		foreach ($array as $k=>$v) {
			if (@strlen($v) > 0 || is_array($v)) {
				if (is_array($v)) {
					$ret[] = $k.'=>('.String::fauxArray($v,true).')';
				}
				else {
					$ret[] = $k.'=>'.$v;
				}
			}
		}
		
		if (is_array($ret)) 
			return ((!$not_root) ? 'array:' : '').implode(',',$ret);
		else
			return false;
	}
	
	public static function unFauxArray($array) {
		if (!is_array($array)) {
			if (stristr($array,'array:')) {
				$v = str_ireplace('array:','',$array);
				$v1 = DB::serializeCommas($v,true);
				return $v1;
			}
			else
				return $array;
		}
		else 
			return $array;
		
	}
	
	public static function unFaux($faux) {
		if (stristr($faux,'array:')) {
			$value = str_ireplace('array:','',$faux);
			if (stristr($value,'|')) {
				if (stristr($value,'|||')) {
					$array = explode('|||',$value);
					if (is_array($array)) {
						foreach ($array as $v) {
							$array1 = explode('|',$v);
							if (!empty($array1[0])) {
								$i = $array1[0];
								$new_array[$i] = $array1[1];
							}
						}
					}
				}
				else {
					$array1 = explode('|',$value);
					if (!empty($array1[0])) {
						$i = $array1[0];
						$new_array[$i] = $array1[1];
					}
				}
			}
		}
		return $new_array;
	}
	
	public static function checkSerialized($string) {
		$check = @unserialize($string);
		$s = ($check===false && $string != serialize(false)) ? false : true;
		if ($s) {
			return unserialize($string);
		}
		else {
			return $string;
		}
	}
	
	public static function sanitize($string,$detect_only=false,$delete_whitespace=false) {
		$ws = (!$delete_whitespace) ? '\s' : '';
		
		if ($detect_only) {
			$string = preg_match("/[^\p{Hebrew} \p{Cyrillic} 0-9a-zA-Z!@#$%&*?\.\-\_{$ws}]/u", $string);
		}
		else {
			$string = preg_replace("/[^\p{Hebrew} \p{Cyrillic} 0-9a-zA-Z!@#$%&*?\.\-\_{$ws}]/u", "", $string);
		}
		
		$string = trim($string);
		return $string;
	}
	
	public static function stripQuotes($v) {
		if (is_array($v))
	    	return array_map(array('String','stripQuotes'), $v);
	  	else
	    	return stripslashes($v);
	}
	
	public static function magicQuotesOff() {
		if (get_magic_quotes_gpc()) {
  			$_GET = String::stripQuotes($_GET);
  			$_POST = String::stripQuotes($_POST);
  			$_REQUEST = String::stripQuotes($_REQUEST);
  			$_COOKIES = String::stripQuotes($_COOKIES);
		}
	}
	
	public static function validateURL($url) {
		// returns true for OK
		// USER AND PASS (optional)
		$urlregex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?";
		
		// HOSTNAME OR IP
		$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*";  // http://x = allowed (ex. http://localhost, http://routerlogin)
		//$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)+";  // http://x.x = minimum
		//$urlregex .= "([a-z0-9+\$_-]+\.)*[a-z0-9+\$_-]{2,3}";  // http://x.xx(x) = minimum
		//use only one of the above
		
		// PORT (optional)
		$urlregex .= "(\:[0-9]{2,5})?";
		// PATH  (optional)
		$urlregex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?";
		// GET Query (optional)
		$urlregex .= "(\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?";
		// ANCHOR (optional)
		$urlregex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?\$"; 
		
		return eregi($urlregex, $url);
	}
	
	public static function truncate($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true) {
		String::$open_tags = array();
		if ($considerHtml) {
			// if the plain text is shorter than the maximum length, return the whole text
			if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
				return $text;
			}
			// splits all html-tags to scanable lines
			preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
			$total_length = strlen($ending);
			$open_tags = array();
			$truncate = '';
			foreach ($lines as $line_matchings) {
				// if there is any html-tag in this line, handle it and add it (uncounted) to the output
				if (!empty($line_matchings[1])) {
					// if it's an "empty element" with or without xhtml-conform closing slash
					if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
						// do nothing
					// if tag is a closing tag
					} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
						// delete tag from $open_tags list
						$pos = array_search($tag_matchings[1], $open_tags);
						if ($pos !== false) {
						unset($open_tags[$pos]);
						}
					// if tag is an opening tag
					} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
						// add tag to the beginning of $open_tags list
						array_unshift($open_tags, strtolower($tag_matchings[1]));
					}
					// add html-tag to $truncate'd text
					$truncate .= $line_matchings[1];
				}
				// calculate the length of the plain text part of the line; handle entities as one character
				$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
				if ($total_length+$content_length> $length) {
					// the number of characters which are left
					$left = $length - $total_length;
					$entities_length = 0;
					// search for html entities
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
						// calculate the real length of all entities in the legal range
						foreach ($entities[0] as $entity) {
							if ($entity[1]+1-$entities_length <= $left) {
								$left--;
								$entities_length += strlen($entity[0]);
							} else {
								// no more characters left
								break;
							}
						}
					}
					$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
					String::$missing = substr($line_matchings[2],$left+$entities_length);
					// maximum lenght is reached, so get off the loop
					break;
				} else {
					$truncate .= $line_matchings[2];
					$total_length += $content_length;
				}
				// if the maximum length is reached, get off the loop
				if($total_length>= $length) {
					break;
				}
			}
		} else {
			if (strlen($text) <= $length) {
				return $text;
			} else {
				$truncate = substr($text, 0, $length - strlen($ending));
			}
		}
		// if the words shouldn't be cut in the middle...
		if (!$exact) {
			// ...search the last occurance of a space...
			$spacepos = strrpos($truncate, ' ');
			if (isset($spacepos)) {
				// ...and cut the text in this position
				String::$missing = substr($truncate,$spacepos).String::$missing;
				$truncate = substr($truncate, 0, $spacepos);
			}
		}
		// add the defined ending to the text
		$truncate .= $ending;
		if($considerHtml) {
			// close all unclosed html-tags
			foreach ($open_tags as $tag) {
				$truncate .= '</' . $tag . '>';
			}
		}
		String::$open_tags = $open_tags;
		return $truncate;
	}
	
	public static function getMonthNames($language=false) {
		if ($language == 'es') {
			$values[1] = 'Enero';
			$values[2] = 'Febrero';
			$values[3] = 'Marzo';
			$values[4] = 'Abril';
			$values[5] = 'Mayo';
			$values[6] = 'Junio';
			$values[7] = 'Julio';
			$values[8] = 'Agosto';
			$values[9] = 'Septiembre';
			$values[10] = 'Octubre';
			$values[11] = 'Noviembre';
			$values[12] = 'Diciembre';
		}
		else {
			$values[1] = 'January';
			$values[2] = 'Febuary';
			$values[3] = 'March';
			$values[4] = 'April';
			$values[5] = 'May';
			$values[6] = 'June';
			$values[7] = 'July';
			$values[8] = 'August';
			$values[9] = 'September';
			$values[10] = 'October';
			$values[11] = 'November';
			$values[12] = 'December';
		}
		return $values;
	}
	
	public static function getDayNames($language=false) {
		if ($language == 'es') {
			$values[0] = 'Dom';
			$values[1] = 'Lun';
			$values[2] = 'Mar';
			$values[3] = 'Mie';
			$values[4] = 'Jue';
			$values[5] = 'Vie';
			$values[6] = 'Sab';
		}
		else {
			$values[0] = 'Sun';
			$values[1] = 'Mon';
			$values[2] = 'Tue';
			$values[3] = 'Wed';
			$values[4] = 'Thu';
			$values[5] = 'Fri';
			$values[6] = 'Sat';
		}
		return $values;
	}
	
	public static function hexDarker($hex,$factor = 30) {
		$new_hex = '';
        $hex = str_replace('#','',$hex);
        
		$base['R'] = hexdec($hex{0}.$hex{1});
        $base['G'] = hexdec($hex{2}.$hex{3});
        $base['B'] = hexdec($hex{4}.$hex{5});
        
		foreach ($base as $k => $v) {
			$amount = $v / 100;
			$amount = round($amount * $factor);
			$new_decimal = $v - $amount;
        
			$new_hex_component = dechex($new_decimal);
			if(strlen($new_hex_component) < 2){ 
				$new_hex_component = "0".$new_hex_component; 
			}
			$new_hex .= $new_hex_component;
		}
                
		return '#'.$new_hex;        
	}
	
	public static function hexLighter($hex,$factor = 30) {
        $new_hex = '';
        $hex = str_replace('#','',$hex);
        
        $base['R'] = hexdec($hex{0}.$hex{1});
        $base['G'] = hexdec($hex{2}.$hex{3});
        $base['B'] = hexdec($hex{4}.$hex{5});
        
        foreach ($base as $k => $v)
                {
                $amount = 255 - $v;
                $amount = $amount / 100;
                $amount = round($amount * $factor);
                $new_decimal = $v + $amount;
        
                $new_hex_component = dechex($new_decimal);
                if(strlen($new_hex_component) < 2)
                        { $new_hex_component = "0".$new_hex_component; }
                $new_hex .= $new_hex_component;
                }
                
        return '#'.$new_hex;        
	}
	

	public static function hexToRgb($hex) {
		$hex = ereg_replace("#", "", $hex);
		$color = array();
		
		if(strlen($hex) == 3) {
			$color['r'] = hexdec(substr($hex, 0, 1) . $r);
			$color['g'] = hexdec(substr($hex, 1, 1) . $g);
			$color['b'] = hexdec(substr($hex, 2, 1) . $b);
		}
		elseif(strlen($hex) == 6) {
			$color['r'] = hexdec(substr($hex, 0, 2));
			$color['g'] = hexdec(substr($hex, 2, 2));
			$color['b'] = hexdec(substr($hex, 4, 2));
		}
		return $color;	
	}
	
	function doFormulaReplacements($formula,$row=false,$directly_link_fields=false,$use_provided_row=false) {
		global $CFG;

		$matches = String::getSubstring($formula,'[',']');
		$formula_parts = explode('}',$formula);
		$dont_keep_squares = false;
		if ($matches) {
			foreach ($matches as $match) {
				$f_id = $row['id'];
				if (strstr($match,'count')) {
					$p = explode('(',$match);
					$p1 = str_replace(')','',$p[1]);
					$value = DB::countRows($p1);
					$dont_keep_squares = true;
				}
				elseif (strstr($formula,'count(['.$match.'])')) {
					$value = DB::countRows($match,$row);
					$match = 'count(['.$match.'])';
				}
				elseif (strstr($match,',')) {
					if (is_array($formula_parts)) {
						foreach ($formula_parts as $part) {
							$parts = explode('return',$part);
							$CFG->formula_before_return = (strstr($parts[0],$match));
							if ($CFG->formula_before_return)
								break;
						}
					}
					$first_row = ($use_provided_row) ? $row : false;
					$value = DB::getForeignValue($match,$f_id,$directly_link_fields,$first_row);
				}
				elseif(@array_key_exists($match,$row)) {
					$value = $row[$match];
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
				else {
					$value = self::replaceSystemVars($match);
				}
				$value = (!$value) ? 0 : $value;
				$match = (strstr($match,'(') && !$dont_keep_squares) ? $match : '['.$match.']';
				$formula = str_ireplace($match,$value,$formula);
			}
		}
		$CFG->formula_before_return = false;
		$CFG->formula_last_id = false;
		return $formula;
	}
	
	public static function replaceConditionals($formula,$row,$f_id_field=false) {
		global $CFG;
		
		$formula1 = preg_replace("/[\n\r]/","",$formula);
		preg_match_all('/(\(){1}((\()*(.)+(\))*)*(\)){1}/',$formula,$conditionals);
		preg_match_all('/(\{){1}([^\}]*)(\}){1}/',$formula1,$return_values);

		if (is_array($conditionals[2])) {
			$i = 0;
			foreach ($conditionals[2] as $conditional) {
				$matches = String::getSubstring($return_values[2][$i],'[',']');
				$matches = (count($matches) > 0) ? $matches : array();
				$matches1 = String::getSubstring($conditional,'[',']');
				$matches1 = (count($matches1) > 0) ? $matches1 : array();
				preg_match_all('/([a-zA-Z_]+(\.){1}[a-zA-Z_]+)/',$conditional,$matches2);
				$matches3 = (count($matches2[0]) > 0 && count($matches1) == 0) ? $matches2[0] : array();
				$matches = array_merge($matches,$matches3,$matches1);
				$table = false;
				$join_tables = false;
				if ($matches) {
					foreach ($matches as $match) {
						if (strstr($match,',')) {
							$match_parts = explode(',',$match);
							$c = count($match_parts) - 1;
							$j = 0;
							foreach ($match_parts as $join_field) {
								$join_field_parts = explode('.',$join_field);
								if ($j == 0) {
									$table = ($table) ? $table : $join_field_parts[0];
									$join_table = $join_field_parts[0];
									$join_tables[$join_table] = $join_field_parts[1];
								}
								else {
									$join_table = $join_field_parts[0];
									$j_field = $join_field_parts[1];
									$join_tables[$join_table] = $j_field;
								}
								$j++;
							}
						}
						elseif(strstr($match,'.')) {
							$match_parts1 = explode('.',$match);
							$table = ($table) ? $table : $match_parts1[0];
						}
					}
				}
				$matches = false;
				$matches1 = false;
				$matches2 = false;
				$matches3 = false;
				
				if (is_array($f_id_field)) {
					$sql1 = " AND {$f_id_field[$i]} = {$row['id']} ";
				}
				elseif ($f_id_field) {
					$sql1 = " AND $f_id_field = {$row['id']} "; 
				}
				
				$conditional1 = $conditional;
				$matches = String::getSubstring($conditional,'[',']');
				if ($matches) {
					foreach ($matches as $match) {
						if (strstr($match,',')) {
							$match_parts = explode(',',$match);
							$c = count($match_parts) - 1;
							$value = (!stristr($match,'cfg')) ? $match_parts[$c] : self::replaceSystemVars($match);
							$conditional1 = str_replace('['.$match.']',$match_parts[$c],$conditional1);
						}
					}
				}
				
				$sql = "SELECT $table.* FROM $table ";
				
				if (is_array($join_tables)) {
					$j = 0;
					foreach ($join_tables as $r_table => $r_field) {
						if ($r_table != $table && $j > 0) {
							$j_field = ($prev_field == 'id') ? $r_field : 'id';
							
							$sql .= " LEFT JOIN {$r_table} ON ({$prev_table}.{$prev_field} = {$r_table}.{$j_field}) ";
						}
						$prev_table = $r_table;
						$prev_field = $r_field;
						$j++;
					}
				}
				
				$sql .= " WHERE $conditional1 $sql1  LIMIT 0,1";
				//echo $sql.' | ';
				$result = db_query_array($sql);
				
				if ($result) {
					$formula = str_ireplace($conditional,'1==1',$formula);
					$matches = String::getSubstring($return_values[2][$i],'[',']');
					if ($matches) {
						foreach ($matches as $match) {
							$value = '';
							if (strstr($match,',')) {
								$value = DB::getForeignValue($match,$result[0]['id']);
							}
							elseif (strstr($match,'.')) {
								$match_parts = explode('.',$match);
								if ($match_parts[0] == $table) {
									$k = $match_parts[1];
									$value = $result[0][$k];
								}
							}
							else {
								$value = $row[$match];
							}
						}
						$replace_string = str_ireplace('['.$match.']',$value,$return_values[2][$i]);
						if (!strstr($replace_string,';')) 
							$replace_string .= ';';
						
						$replace_string = str_ireplace('['.$match.']',$value,$return_values[2][$i]);
						$formula = str_replace($return_values[2][$i],$replace_string,$formula);
					}
					$matches = false;
					$true = 1;
				}
				else {
					$formula = str_ireplace($conditional,'1!=1',$formula);
					$formula = str_ireplace($return_values[2][$i],'',$formula);
				}
				
				$i++;
			}
			
			if (!$true) {
				$i++;
				$matches = String::getSubstring($return_values[2][$i],'[',']');
				if ($matches) {
					foreach ($matches as $match) {
						if (strstr($match,',')) {
							$value = DB::getForeignValue($match,$row['id']);
						}
						else {
							$value = $row[$match];
						}
					}
					$replace_string = str_ireplace('['.$match.']',$value,$return_values[2][$i]);
					if (!strstr($replace_string,';')) 
						$replace_string .= ';';
					
					$formula = str_replace($return_values[2][$i],$replace_string,$formula);
				}
			}
		}
		//echo $formula;
		return $formula;
	}
	
	public static function replaceSystemVars($var) {
		global $CFG;
		
		if (!stristr($var,'cfg'))
			return $var;
			
		$var = str_ireplace('cfg','',str_replace('[','',str_replace(']','',str_replace('::','',$var))));
		eval('$var = $CFG->'.$var.';');
		return $var;
	}
	
	public static function parseVariables($variables,$row,$record_id=0,$url=false,$update_variable_values=false) {
		global $CFG;
		
		$reserved_keywords = array('current_url','action','bypass','is_tab');
		if (is_array($variables)) {
			foreach ($variables as $k => $v) {
				$is_formula = (strstr($v,'(') && strstr($v,')'));
				$k1 = ($url) ? "{$url}[{$k}]" : $k;
				$v1 = ($is_formula) ? $v : str_replace('[','',str_replace(']','',$v));
				
				if (strstr($v1,'(') && strstr($v1,')')) {
					$formula = String::doFormulaReplacements($v1,$row,1);
					$v1 = eval("return ($formula);");
				}
				
				if (in_array($k,$reserved_keywords))
					$variables1[$k] = $v;
				elseif ($k == 'record_id') {
					$variables1[$k1] = ($is_formula) ? $v1 : $record_id;
				}
				elseif ($k == 'id') {
					$variables1[$k] = ($is_formula) ? $v1 : $record_id;
					if ($update_variable_values)
						$variables1['record_id'] = ($is_formula) ? $v1 : $record_id;
				}
				elseif ($v1 == 'id') {
					$variables1[$k1] = ($is_formula) ? $v1 : $record_id;
				}
				elseif ($v1 == 'curdate') {
					$variables1[$k1] = date('Y-m-d 00:00:00');
				}
				elseif ($v1 == 'curtime') {
					$variables1[$k1] = date('Y-m-d H:i:s',time() + (Settings::mysqlTimeDiff() * 3600));
				}
				elseif ($k == 'user_id') {
					$variables1[$k1] = User::$info['id'];
				}
				elseif (strstr($v1,'count')) {
					$p = explode('(',$v1);
					$v1 = str_replace(')','',$p[1]);
					$variables1[$k1] = DB::countRows($v1);
				}
				elseif(strstr($v1,'.')) {
					$parts = explode('.',$v1);
					$sql = "SELECT {$parts[1]} FROM {$parts[0]} WHERE f_id = $record_id";
					$result = db_query_array($sql);
					if ($result) {
						$m1 = $parts[1];
						$variables1[$k1] = $result[0][$m1];
					}
				}
				elseif (strstr($v1,',')) {
					$variables1[$k1] = DB::getForeignValue(implode(',',$v1),$row);	
				}
				else {
					self::replaceSystemVars($v1);
					if (strstr($v1,'++'))
						$v1 = $row[(str_replace('++','',$v1))] + 1;
					elseif (strstr($v1,'--'))
						$v1 = $row[(str_replace('--','',$v1))] - 1;
					else
						$v1 = (array_key_exists($v1,$row)) ? $row[$v1] : $v;
					
					$variables1[$k1] = $v1;
				}
			}
		}
		if ($record_id > 0 && $update_variable_values)
			$variables1['record_id'] = $record_id;
			
		return $variables1;
	}
}
?>