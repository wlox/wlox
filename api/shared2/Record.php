<?php
class Record {
	private $table,$record_id,$HTML,$t,$t_cols,$i,$db_fields,$db_subtables,$current_restricted,$has_areas,$tabs,$area_i,$current_area,$form_method_args;
	
	function __construct($table,$record_id) {
		global $CFG;
		
		$this->table = $table;
		$this->record_id = ($CFG->include_id > 0) ? $CFG->include_id : $record_id;
		$this->row = DB::getRecord($this->table,$this->record_id,0,1);
		$this->db_fields = DB::getTableFields($this->table);
		$this->db_subtables = DB::getSubtables($this->table);
		$this->db_subtables = (!$this->db_subtables) ? array() : $this->db_subtables;
		$this->area_i = 0;
		$this->current_area = 0;
		
		$page_id = Control::getPageId($CFG->url,$CFG->is_tab);
		$corresponding_form = Control::getControls($page_id,'form',$CFG->is_tab);
		if ($corresponding_form) {
			$k = key($corresponding_form);
			if ($corresponding_form[$k]['params'] = 'Form') {
				foreach ($corresponding_form[$k]['methods'] as $method) {
					$args = Control::parseArguments($method['arguments'],'Form',$method['method']);
					$name = ($args['name']) ? $args['name'] : $args['value'];
					$this->form_method_args[$name] = $args;
				}
			}
		}
	}
	
	function field($name=false,$caption=false,$subtable=false,$subtable_fields=false,$link_url=false,$concat_char=false,$in_form=false,$f_id_field=false,$order_by=false,$order_asc=false,$record_id=false,$link_is_tab=false,$limit_is_curdate=false,$override_value=false,$link_id_field=false) {
		global $CFG;
		
		$concat_char = ($concat_char) ? $concat_char : ' ';
		$caption = ($caption) ? $caption : $name;
		$record_row = ($this->in_grid) ? $this->grid_values : $this->row;
		$table = ($this->in_grid) ? $this->grid_table : $this->table;
		$db_fields = ($this->in_grid) ? DB::getTableFields($table) : $this->db_fields;

		if (!$link_id_field) {
			$param_name = ($this->in_grid) ? 'id' : $name;
			$param_name = ($CFG->url != $link_url) ? 'id' : $param_name;
			$id = $record_row[$name];
		}
		else {
			$param_name = $link_id_field;
			$id = $record_row[$link_id_field];
		}

		if (is_array($record_row) || strlen($override_value) > 0) {
			$is_field = (@array_key_exists($name,$record_row) || strlen($override_value) > 0);
			$has_subtable = in_array($subtable,$this->db_subtables);
			$has_table = DB::tableExists($subtable);
			$value = (strlen($override_value) > 0) ? $override_value : $record_row[$name];
			$is_time = ($this->form_method_args[$name]['only_time']);
			//echo $name.$is_field.'-'.$has_subtable.'-'.$has_table.'|';
		
			if (!$is_field && strstr($f_id_field,',')) {
				$id = DB::getForeignValue($f_id_field,$this->record_id,1);
			}
			
			if ($is_field && !$has_subtable && !$has_table) {
				$text = Grid::detectData($name,$value,$db_fields,false,$is_time);
				if ($link_url) {
					$text = Link::url($link_url,$text,"$param_name={$id}&is_tab={$link_is_tab}&action=record");
				}
			}
			elseif ($is_field && !$has_subtable && $has_table) {
				$result = DB::getFields($subtable,$value,$subtable_fields,$f_id_field);
				$text = @implode('<span class="record_component">'.$concat_char.'</span>',$result);
				if ($link_url) {
					$text = Link::url($link_url,$text,"$param_name={$id}&is_tab={$link_is_tab}&action=record");
				}
			}
			elseif (!$is_field && $has_subtable && $has_table) {
				$result = DB::getFieldsByLookup($table,$subtable,$this->row['id'],$subtable_fields);
				foreach ($result as $row) {
					$text_parts = false;
					if (is_array($subtable_fields)) {
						foreach ($subtable_fields as $field) {
							$text_parts[] = $row[$field];
						}
						$concat_char = ($concat_char) ? $concat_char : ' ';
						$text = implode('<span class="record_component">'.$concat_char.'</span>',$text_parts);
					}
					else {
						$text = $row['name'];
					}
					if ($link_url) {
						$results[] = Link::url($link_url,$text,"$param_name={$id}&is_tab={$link_is_tab}&action=record");
					}
					else {
						$results[] = $text;
					}
				}
				$text = implode('<span class="record_component">'.$concat_char.'</span>',$results);
			}
			elseif (!$is_field && $has_subtable && !$has_table) {
				$result = DB::getSubTable($subtable,$subtable_fields,$id,$concat_char);
				if ($link_url) {
					foreach ($result as $id => $row) {
						$text = Grid::detectData($name,$value,$db_fields);
						$result1[] = Link::url($link_url,$row,"$param_name={$id}&is_tab={$link_is_tab}&action=record");
					}
				}
				else {
					$result1 = $result;
				}
				$text = implode(', ',$result1);
			}
			elseif (!$is_field && !$has_subtable && $has_table) {
				$record_id = ($record_id > 0) ? $record_id : $this->record_id;
				$result = DB::getFields($subtable,$this->record_id,$subtable_fields,$f_id_field,$order_by,$order_asc,$record_id,$limit_is_curdate);
				$text = @implode('<span class="record_component">'.$concat_char.'</span>',$result);
				if ($link_url) {
					$text = Link::url($link_url,$text,"$param_name={$id}&is_tab={$link_is_tab}&action=record");
				}
			}
		}
		
		if ($CFG->pm_editor && !$this->in_grid) {
			$method_name = Form::peLabel($CFG->method_id,'field');
		}
		
		if ($this->t) {
			if ($this->i == 0) {
				$HTML = '<tr>';
			}
			
			$HTML = $HTML."|||<td><b class=\"record_label\">$caption</b>{$method_name}</td><td>$text</td>";
			$this->i++;
			
			if ($this->i == $this->t_cols) {
				$HTML = $HTML.'</tr>';
				$this->i = 0;
			}
		}
		else {
			if (!$this->in_grid) {
				$align_left_class = ($db_fields[$name]['Type'] == 'blob') ? 'files_caption' : '';
				$al1 = ($db_fields[$name]['Type'] == 'blob') ? '<div class="long_text">' : '';
				$al2 = ($db_fields[$name]['Type'] == 'blob') ? '</div>' : '';
				$HTML = "<div class=\"label_extend\"></div><b class=\"record_label $align_left_class\">$caption</b><span class=\"record_item\"> {$method_name} {$al1}{$text}{$al2}</span>";
			}
			else
				$HTML = $text;
		}
		
		if (!$in_form)
			$this->HTML[] = $HTML;
		else 
			return $HTML;
	}
	
	//must use braces
	function aggregate($name,$formula,$caption=false,$link_url=false,$run_in_sql=false,$in_form=false,$link_is_tab=false) {
		global $CFG;
		if (!$run_in_sql) {
			$formula = String::doFormulaReplacements($formula,$this->row);
			$text = eval("return $formula ;");
		}
		else {
			if ($this->record_id > 0)
				$text = DB::getAggregateRow($name,$formula,$this->table,$this->record_id);
		}
		
		if ($CFG->pm_editor && !$this->in_grid) {
			$method_name = Form::peLabel($CFG->method_id,'aggregate');
		}
		
		$text = Grid::detectData($name,$text,$this->db_fields);
		if ($link_url) {
			$text = Link::url($link_url,$text,"$name=$id&is_tab={$link_is_tab}&action=record");
		}
		
		$HTML = "<div class=\"label_extend\"></div><b class=\"record_label\">$caption</b><span class=\"record_item\">{$method_name} $text</span>";
		
		if (!$in_form)
			$this->HTML[] = $HTML;
		else 
			return $HTML;
	}
	
	// to use subtables, formula must return id or $f_id_field
	function indicator($name,$formula,$caption=false,$subtable=false,$subtable_fields=false,$concat_char=false,$f_id_field=false,$formula_f_id_field=false,$link_url=false,$link_is_tab=false,$run_in_sql=false,$in_form=false) {
		global $CFG;

		if (!$CFG->pm_editor) {
			if (!$run_in_sql) {
				$formula = String::doFormulaReplacements($formula,$this->row,1);
			}
			else {
				if ($this->record_id > 0)
					$formula = String::replaceConditionals($formula,$this->row,$formula_f_id_field);
			}
			$result = @eval("$formula");
			//echo $formula.' | '.$result .' ||| ';
			if ($subtable && $this->record_id > 0) {
				$f_id_field = ($f_id_field) ? $f_id_field : 'id';
				$db_output = DB::getSubTable($subtable,$subtable_fields,$result,$concat_char,$f_id_field);
				$key = key($db_output);
				$text = $db_output[$key];
			}
			else {
				$text = Grid::detectData($name,$result,$this->db_fields);
				if ($link_url) {
					$text = Link::url($link_url,$text,"$name=$id&is_tab={$link_is_tab}&action=record");
				}
			}
		}

		if ($CFG->pm_editor && !$this->in_grid) {
			$method_name = Form::peLabel($CFG->method_id,'indicator');
		}
		
		$HTML = "<div class=\"label_extend\"></div><div class=\"indicator\"><b class=\"record_label\">$caption</b><span class=\"record_item\">{$method_name} $text</span></div>";
		
		if (!$in_form)
			$this->HTML[] = $HTML;
		else 
			return $HTML;
	}
	
	function gallery($field_name=false,$size=false,$thumbnails=0,$class=false,$limit=0,$encrypted=false) {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'gallery');
		}
		$this->HTML[] = '<gallery>'.Gallery::multiple($this->table.'_files',$this->record_id,$field_name,$size,$thumbnails,$class,$limit,false,false,false,false,false,false,false,false,false,false,$encrypted).$method_name;
	}
	
	function image($image_url,$link_url=false) {
		
		$HTML = Gallery::imageUrl($url);
		$this->HTML[] = ($link_url) ? Link::url($link_url,$HTML,"id=$this->record_id") : $HTML;
	}
	
	function startTable($caption,$class=false,$cols=0) {
		$this->t = true;
		$this->t_cols = ($cols) ? $cols : 1;
		$colspan = $this->t_cols * 2;
		$this->HTML[] = "|||<li><table class=\"$class\">".(($caption) ? "<tr><th colspan=\"$colspan\">$caption</th></tr>" : '');
	}
	
	function endTable() {
		if (($this->i > 0) && ($this->i < $this->t_cols)) {
			$tr = '</tr>';
		}
		$this->HTML[] = $tr."</table></li>|||";
		$this->t = false;
	}
	
	function heading($caption) {
		global $CFG;
		
		preg_match_all("/\[([A-Za-z0-9-_]+)\]/",$caption,$variables);
		$variables[1] = array_combine($variables[1],$variables[1]);
		$variables1 = String::parseVariables($variables[1],$this->row,$this->record_id);
		if (is_array($variables[1])) {
			foreach ($variables[1] as $key) {
				$caption = str_ireplace("[{$key}]",$variables1[$key],$caption);
			}
		}
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'heading');
		}
		
		$this->HTML[] = "<h2>$caption</h2>".$method_name;
	}
	
	function link($url=false,$caption=false,$variables=false,$target_elem_id=false,$class=false,$disable_if_no_record_id=false,$disable_if_cant_edit=false) {
		global $CFG;
		
		$reserved_keywords = array('current_url','action','bypass','is_tab');
		if (is_array($variables)) {
			foreach ($variables as $k => $v) {
				$v1 = str_replace('[','',str_replace(']','',$v));
				if (in_array($k,$reserved_keywords))
					$variables1[$k] = $v;
				elseif ($k == 'id')
					$variables1[$k] = $this->record_id;
				elseif ($v1 == 'id')
					$variables1["{$url}[{$k}]"] = $this->record_id;
				else
					$variables1["{$url}[{$k}]"] = $this->row[$v1];
			}
		}
		$variables1['bypass_save'] = 1;
		
		$permission_level = ($variables1['action'] == 'form') ? 2 : 1;
		$disabled = (User::permission(0,0,$url,false,$variables1['is_tab']) < $permission_level);
		$disabled = (!($this->record_id > 0) && $disable_if_no_record_id) ? true : $disabled;
		$target_elem_id = (!$target_elem_id) ? 'content' : $target_elem_id;
		$class = ($class) ? $class : 'nav_link';
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'link');
		}

		$this->HTML[] = Link::url($url,'<div class="add_new"></div> '.$caption,false,$variables1,false,$target_elem_id,$class,false,false,false,$disabled).$method_name;
	}
	
	function cancelButton($value=false,$id=false,$class=false,$style=false) {
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'cancelButton';
		$form = new Form('cancelButton');
		$this->HTML[] = $form->cancelButton($value,$id,$class,$style,1);
	}
	
	function printButton($value=false,$class=false,$style=false) {
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'printButton';
		$form = new Form('printButton');
		$form->record_id = $this->record_id;
		$this->HTML[] = $form->printButton($value,$class,$style,1);
	}
	
	function button($url=false,$value=false,$variables=false,$target_elem_id=false,$id=false,$class = false, $jscript = false, $style = false,$disable_if_no_record_id=false,$disable_if_cant_edit=false) {
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'button';
		$form = new Form('button');
		$form->record_id = $this->record_id;
		$this->HTML[] = $form->button($url,$value,$variables,$target_elem_id,$id,$class,$jscript,$style,$disable_if_no_record_id,$disable_if_cant_edit,1);
	}
	
	function grid($name,$caption=false,$link_url=false,$link_is_tab=false,$concat_char=false) {
		global $CFG;
		
		$form = Control::getControls($CFG->editor_page_id,'form',$CFG->editor_is_tab);
		if (!$form) {
			Errors::add('No form action created yet.');
			return false;
		}
		foreach ($form as $id => $control) {
			if (is_array($control['methods'])) {
				foreach ($control['methods'] as $method) {
					$args = unserialize($method['arguments']);
					if ($method['method'] == 'grid' && $args['name'] == $name) {
						$grid_id = $id;
						$grid_method = $method;
						$grid_control = $control;
						break 2;
					}
				}
			}
		}
		if (!($grid_id > 0)) {
			Errors::add('No grid input called '.$name.' exists. Create it on the form action first.');
			return false;
		}
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'grid');
		}
		$this->in_grid = true;
		$this->grid_table = $this->table.'_grid_'.$name;
		$inputs_array = Control::getSubMethods($method['id'],$control['params']['class']);
		$HTML .= '<div class="record_grid"><div class="caption">'.$caption.$method_name.'</div><table>';
			
		if ($inputs_array) {
			$HTML .= '<tr>';
			foreach ($inputs_array as $args) {
				$HTML .= '<th>'.$args['caption'].'</th>';
			}
			$HTML .= '</tr>';
			
			$values = DB::getGridValues($this->table.'_grid_'.$name,$subtable_fields,$this->record_id);
			if ($values) {
				foreach ($values as $row) {
					$num_empty = 0;
					$c1 = count($inputs_array);
					
					if (is_array($inputs_array)) {
						foreach ($inputs_array as $k=>$v) {
							if (stristr($k,'checkBox'))
								$c1--;	
						}
					}
					
					foreach ($row as $key=>$row1) {
						if ($key != 'id' && empty($row1)) {
							$num_empty++;
						}
					}
					if ($num_empty >= $c1) {
						continue;
					}
					
					$this->grid_values = $row;
					$HTML .= '<tr>';
					foreach ($inputs_array as $args) {
						$HTML .= '<td>'.self::field($args['name'],$args['caption'],$args['subtable'],$args['subtable_fields'],$link_url,$concat_char,true,$args['f_id_field'],false,false,false,$link_is_tab).'</td>';
					}
					$HTML .= '</tr>';
				}
			}
		}
		$HTML .= '</table></div>';
		$this->in_grid = false;
		$this->grid_table = false;
		$this->HTML[] = $HTML;
	}
	
	function HTML($HTML) {
		$this->HTML[] = $HTML;
	}
	
	function files($name,$caption=false,$encrypted=false) {
		global $CFG;
		
		$caption = ($caption) ? $caption : $name;
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'files');
		}
		
		$HTML = "<b class=\"record_label files_caption\">$caption</b> {$method_name}<ul>";
		
		if ($this->record_id) {
			if ($files = DB::getFiles($this->table.'_files',$this->record_id,$name)) {
				foreach ($files as $file) {
					$HTML .= '<li>';
					$HTML .= File::showLinkForResult($file,false,$this->table,$encrypted);
					$HTML .= '</li>';
				}
			}
		}
		$HTML .= '</ul>';
		
		$this->HTML[] = $HTML;
	}
	
	function startArea($legend,$class=false,$height=false) {
		global $CFG;
		
		$this->has_areas = true;
		$this->current_area = $CFG->method_id;
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'startArea');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = "
			$legend [start_area] $method_name";
		}
		else {
			$style = ($height > 0) ? 'style="height:'.$height.'px;padding-bottom:47px;"' : '';
			$this->HTML[] = "<area>
			<div $style class=\"area $class\">  
				<h2>$legend</h2>
				<div class=\"box_bar\"></div>
				<div class=\"box_tl\"></div>
				<div class=\"box_tr\"></div>
				<div class=\"box_bl\"></div>
				<div class=\"box_br\"></div>
				<div class=\"t_shadow\"></div>
				<div class=\"r_shadow\"></div>
				<div class=\"b_shadow\"></div>
				<div class=\"l_shadow\"></div>
				<div class=\"box_b\"></div>
				<div class=\"contain\">
					<ul>";
		}
	}
	
	function endArea() {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'endArea');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = '[end_area]'.$method_name;
		}
		else {
			$HTML = '</area><div class="clear"></div></ul></div>';
			if ($this->tabs[$this->current_area]){
				$HTML .= '<div class="a_tabs">';
				$i = 0;
				foreach ($this->tabs[$this->current_area] as $m_id => $caption) {
					$visible = ($i == 0) ? 'visible' : '';
					$HTML .= '<a class="a_tab '.$visible.'" onclick="formSelectTab(\''.$m_id.'\',this)"><div class="contain1">'.$caption.'</div><div class="l"></div><div class="r"></div><div class="c"></div></a>';
					$i++;
				}
				$HTML .= '</div>';
			}
			$HTML .= '</div>';
			$this->HTML[] = $HTML;
		}
		$this->current_area = 0;
		$this->area_i = 0;
	}
	
	function startGroup($legend=false,$class=false) {
		global $CFG;
		
		$class = ($class) ? $class : 'group_default';
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'startGroup');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = $legend.' [start_group]'.$method_name;
		}
		else {
			$legend = ($legend) ? "<b class=\"record_label\">$legend</b><div class=\"label_extend\"></div>" : '';
			$this->HTML[] = "<group>$legend<div class=\"group $class\"><ul>";
		}
	}
	
	function endGroup() {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'endGroup');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = '[end_group]'.$method_name;
		}
		else {
			$this->HTML[] = '</group><div class="clear"></div></ul></div></li>';
		}
	}
	
	function startTab($caption,$url=false,$is_tab=false,$action=false,$inset_id_field=false) {
		global $CFG;

		$this->tabs[$this->current_area][$CFG->method_id] = $caption;
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'startTab';
		$form = new Form('startTab');
		$form->record_id = $this->record_id;
		$this->HTML[] = $form->startTab($caption,$url,$is_tab,$action,$inset_id_field,1,$this->area_i);
		$this->area_i++;
	}
	
	function endTab() {
		global $CFG;
		
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'endTab';
		$form = new Form('endTab');
		$form->record_id = $this->record_id;
		$this->HTML[] = $form->endTab(1);
	}
	
	function startRestricted($groups=false,$only_admin=false,$users=false,$user_id_equals_field=false,$group_id_equals_field=false,$condition=false,$exclude_groups=false,$exclude_admin=false,$exclude_users=false) {
		global $CFG;
	
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'startRestricted');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = "
			$legend [start_restricted] $method_name";
		}
		else {
			if ($only_admin) {
				if ($condition) {
					$condition = String::doFormulaReplacements($condition,$this->row);
					$restricted = ($restricted) ? $restricted : @eval("if ({$condition}) { return 0;} else { return 1;}");
				}
			}
			else {
				if (is_array($users))
					$restricted = (!array_key_exists(User::$info['id'],$users));
					
				if (is_array($groups))
					$restricted =  (!$restricted) ? (!array_key_exists(User::$info['f_id'],$groups)) : $restricted;
					
				if (is_array($exclude_users))
					$restricted = (!$restricted) ? array_key_exists(User::$info['id'],$exclude_users) : $restricted;
					
				if (is_array($exclude_groups))
					$restricted =  (!$restricted) ? (array_key_exists(User::$info['f_id'],$exclude_groups)) : $restricted;
					
				if ($exclude_admin)
					$restricted =  (!$restricted) ? (User::$info['is_admin'] == 'Y') : $restricted;
			
				if ($user_id_equals_field) {
					if (strstr($user_id_equals_field,',')) {
						$parts = explode(',',$user_id_equals_field);
						$parts1 = explode('.',$parts[0]);
						$first_table = $parts1[0];
						$first_field = $parts1[1];
						$c = count($parts) - 1;
						$parts2 = explode('.',$parts[$c]);
						$last_table = $parts2[0];
						$last_field = $parts2[1];
						$row = DB::getFields($last_table,$this->row[$first_field],array($last_field),$user_id_equals_field,false,false,false,false,1);
						$restricted = (!$restricted) ? $restricted : (User::$info['id'] != $row[$last_field]);
					}
					else {
						$restricted = (!$restricted) ? $restricted : (User::$info['id'] != $this->row[$user_id_equals_field]); 
					}
				}
				
				if ($group_id_equals_field) {
					if (strstr($group_id_equals_field,',')) {
						$parts = explode(',',$group_id_equals_field);
						$parts1 = explode('.',$parts[0]);
						$first_table = $parts1[0];
						$first_field = $parts1[1];
						$c = count($parts) - 1;
						$parts2 = explode('.',$parts[$c]);
						$last_table = $parts2[0];
						$last_field = $parts2[1];
						$row = DB::getFields($last_table,$this->row[$first_field],array($last_field),$group_id_equals_field,false,false,false,false,1);
						$restricted = (!$restricted) ? $restricted : (User::$info['f_id'] != $row[$last_field]); 
					}
					else {
						$restricted = (!$restricted) ? $restricted : (User::$info['f_id'] != $this->row[$group_id_equals_field]); 
					}
				}
				
				if ($condition) {
					$condition = String::doFormulaReplacements($condition,$this->row);
					$restricted = ($restricted) ? $restricted : eval("if ({$condition}) { return 0;} else { return 1;}");
				}
			}
			$restricted = (User::$info['is_admin'] == 'Y' && !$exclude_admin) ? false : $restricted;
			
			if ($restricted)
				$this->HTML[] = "<restricted>";
		}
	}
	
	function endRestricted() {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'endRestricted');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = '[end_restricted]'.$method_name;
		}
		else {
			$this->HTML[] = '</restricted>';
		}
	}
	
	function includePage($url,$is_tab=false) {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'includePage');
			$this->HTML[] = "[Include: {$url}] $method_name";
		}
		else {
			$was_tab = $CFG->is_tab;
			$CFG->is_tab = $is_tab;
			$CFG->in_include = true;
			$CFG->include_id = $this->row[str_replace('-','_',$url).'_include_id'];
			ob_start();
			$control = new Control($url,'record',$is_tab);
			$this->HTML[] = '<include>'.ob_get_contents();
			ob_end_clean();
			$CFG->in_include = false;
			$CFG->is_tab = $was_tab;
		}
	}
	
	function display() {
		global $CFG;
		$pm_method = ($CFG->pm_editor) ? ' class="pm_method"' : '';
		
		if ($this->HTML) {
			if (!$CFG->in_include) {
				$class = (!$this->has_areas) ? 'area full_box' : false;
				echo '<div class="record_container '.$class.'">';
				if (!$this->has_areas && !strstr($this->name,'form_filters') && !$CFG->in_popup) {
					echo '
					<h2>'.Ops::getPageTitle().'</h2>
					<div class="box_bar"></div>
					<div class="box_tl"></div>
					<div class="box_tr"></div>
					<div class="box_bl"></div>
					<div class="box_br"></div>
					<div class="t_shadow"></div>
					<div class="r_shadow"></div>
					<div class="b_shadow"></div>
					<div class="l_shadow"></div>
					<div class="box_b"></div>
					<div class="contain">';
				}
				echo '<ul class="record">';	
			}
			
			$alt = 'alt';
			foreach ($this->HTML as $row) {
				if (!$in_group)
					$alt = ($alt) ? false : 'alt';
				
				if (stristr($row,'<button>')) {
					$row = str_replace('<button>','',$row);
					$alt = 'button_alt';
				}
				
				if ($this->current_restricted > 0 && !stristr($row,'</restricted>')) {
					continue;
				}
				elseif (strstr($row,'|||')) {
					echo str_replace('|||','',$row);
				}
				elseif (strstr($row,'<area>')) {
					echo str_replace('<area>','',$row);
				}
				elseif (strstr($row,'</area>')) {
					echo str_replace('</area>','',$row);
				}
				elseif (stristr($row,'<group>')) {
					echo "<li class=\"$alt\">".str_replace('<group>','',$row);
					$in_group = true;
					continue;
				}
				elseif (stristr($row,'</group>')) {
					echo str_replace('</group>','',$row);
					$in_group = false;
					continue;
				}
				elseif (stristr($row,'<tab>')) {
					echo str_replace('<tab>','',$row);
					continue;
				}
				elseif (stristr($row,'</tab>')) {
					echo str_replace('</tab>','',$row);
					continue;
				}
				elseif (stristr($row,'<restricted>')) {
					$this->current_restricted = 1;
					continue;
				}
				elseif (stristr($row,'</restricted>')) {
					$this->current_restricted = 0;
					continue;
				}
				else {
					echo "<li class=\"$alt\" $pm_method>$row<div class=\"clear\"></div></li>";
				}
			}
			
			if (!$CFG->in_include) {
				echo '<div class="clear"></div></ul>';
				if (!$this->has_areas && !strstr($this->name,'form_filters') && !$CFG->in_popup)
					echo '</div>';
				echo '</div>';
			}
		}
	}
}
?>