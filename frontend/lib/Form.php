<?php
/*
 #### $required can specify the type of verification 
 #### types: default(no value),email, phone, file, password (will be checked against $CFG->pass_regex), zip, custom (custom regex)
 #### $img_save_sizes takes an array of height and width for each image size (ex: array(array('height'=>124,'width'=>245));)
 #### $required = 'user' for username
*/

class Form {
	public $name, $action, $method, $table, $class, $info, $enctype, $HTML, $required, $fieldset, $group, $errors, $db_fields, $messages, $record_id, $temp_files, $date_widgets,$errors_to_function,$compare,$unique,$output_started,$delete_whitespace,$color_fields,$override_methods,$select_subtables,$record_created,$save_called,$restricted,$current_restricted,$go_to_url,$go_to_action,$go_to_is_tab,$includes,$include_ids;
	static $old_info_prev;
	private $edit_record,$edit_record_field_id,$has_areas,$tabs,$current_area,$area_i;
	
	function __construct($name,$action=false,$method=false,$class=false,$table=false,$return_to_self=false,$errors_to_function=false,$start_on_construct=false,$go_to_url=false,$go_to_action=false,$go_to_is_tab=false,$target=false) {
		global $CFG;

		$this->name = ($go_to_url) ? 'form_filters' : $name;
		$this->action = ($action) ? $action : $CFG->self;
		$this->method = ($method) ? $method : 'POST';
		$this->table = ($table) ? $table : ((!empty($_REQUEST['form_table'])) ? $_REQUEST['form_table'] : false);
		$this->class = $class;
		$this->info = (!empty($_REQUEST[$name])) ? $_REQUEST[$name] : false;
		$this->verify_fields = ((!empty($_REQUEST['verify_fields'])) ? $_REQUEST['verify_fields'] : false);
		$this->temp_files = ((!empty($_REQUEST['temp_files'])) ? $_REQUEST['temp_files'] : false);
		$this->urls = ((!empty($_REQUEST['urls'])) ? $_REQUEST['urls'] : false);
		$this->attached_file_fields = ((!empty($_REQUEST['attached_file_fields'])) ? $_REQUEST['attached_file_fields'] : false);
		$this->record_id = ((!empty($_REQUEST['record_id'])) ? $_REQUEST['record_id'] : false);
		$this->errors_to_function = $errors_to_function;
		$this->return_to_self = $return_to_self;
		$this->unique_fields = ((!empty($_REQUEST['unique_fields'])) ? $_REQUEST['unique_fields'] : false);
		$this->compare_fields = ((!empty($_REQUEST['compare_fields'])) ? $_REQUEST['compare_fields'] : false);
		$this->delete_whitespace = ((!empty($_REQUEST['delete_whitespace'])) ? $_REQUEST['delete_whitespace'] : false);
		$this->go_to_url = $go_to_url;
		$this->go_to_action = $go_to_action;
		$this->go_to_is_tab = $go_to_is_tab;
		$this->target = ($target) ? 'target="'.$target.'"' : false;
		$this->includes = ((!empty($_REQUEST['includes'])) ? $_REQUEST['includes'] : false);
		$this->current_area = 0;
		$this->area_i = 0;
		$this->req_img = '<em>*</em>';
		$this->compare_error = 'login-password-compare';
		$this->capcha_error = 'login-capcha-error';
		$this->verify_date_error = false;
		$this->verify_email_error = 'login-email-error';
		$this->verify_password_error = 'login-password-error';
		$this->verify_default_error = 'login-required-error';
		$this->verify_custom_error = false;
		
		if ($this->info) {
			if (!empty($_REQUEST['remember_values'])) {
				$_SESSION[$this->name.'_info'] = $this->info;
			}
			
			if (!empty($_REQUEST['checkboxes'])) {
				foreach ($_REQUEST['checkboxes'] as $checkbox) {
					if (!array_key_exists($checkbox, $this->info)) {
						$this->info[$checkbox] = '';
					}
				}
			}
			if (is_array($this->urls)) {
				$this->info['urls'] = $this->urls;
			}
		}
		else {
			if (!empty($_SESSION[$this->name.'_info']) && $this->go_to_url) {
				$this->info = $_SESSION[$this->name.'_info'];
			}
		}
		
		if ($this->go_to_url)
			echo '<input type="hidden" id="dont_edit_table" value="1"/><input type="hidden" name="remember_values" value="1"/>';
	}
	
	function textInput($name, $caption = false, $required = false, $value = false, $id = false, $db_field_type = false, $class = false, $jscript = false, $style = false,$is_manual_array=false,$is_unique=false,$unique_error=false,$default_text=false,$delete_whitespace=false,$static=false,$j=false,$grid_input=false) {
		global $CFG;
		
		$class = ($class) ? 'class="' . $class . '"' : false;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$caption = ($caption) ? $caption : $name;
		$value = ($default_text) ? $default_text : $value;
		$value = (!empty($this->info[$name]) && !$static) ? $this->info[$name] : $value;
		$value = (strstr($value,'<')) ? htmlentities($value) : $value;
		$db_field_type = ($is_manual_array) ? 'text' : $db_field_type;
		$onblur = ($is_manual_array) ? "onblur=\"prepend(this,'array:')\"" : '';
		$onblur = ($default_text) ? "onblur=\"defaultText(this,'$default_text')\"" : '';
		$onclick = ($default_text) ? "onclick=\"defaultText(this,'$default_text')\"" : '';

		if (!$static) {
			if ($delete_whitespace)
				$this->delete_whitespace[] = $name;
			
			if ($required) {
				$this->required[$name] = $required;
				$req_img = $this->req_img;
			}
			else
				$req_img = false;
			
			if ($this->table) {
				$this->db_fields[$name] = ($db_field_type) ? $db_field_type : 'vchar';
			}
			
			if ($is_unique)
				$this->unique[$name] = ($unique_error) ? $unique_error : '';
		}

		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
		$id_j = ($grid_input) ? '_'.$j.'_'.$grid_input : '';
		
		$HTML = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}{$id_j}\">$caption </label><input type=\"text\" name=\"{$this->name}[$name]{$mult}\" value=\"$value\" id=\"{$this->name}_{$id}{$id_j}\" $class ".$jscript." $style $onblur $onclick />";
		if (!$static) {
			$this->HTML[] = $HTML;
		}
		else {
			return $HTML;
		}
		
	}
	
	function passwordInput($name, $caption = false, $required = false, $value = false, $id = false, $class = false, $jscript = false, $style = false,$compare_with=false) {
		global $CFG;
		
		$class = ($class) ? 'class="' . $class . '"' : false;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$caption = ($caption) ? $caption : $name;
		$value = (!empty($this->info[$name])) ? $this->info[$name] : $value;
		
		if ($required) {
			$this->required[$name] = 'password';
			$req_img = $this->req_img;
		}
		else
			$req_img = false;
		
		if ($this->table && !$compare_with) {
			$this->db_fields[$name] = 'password';
		}	
		
		$this->HTML[] = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}\">$caption </label><input type=\"password\" name=\"{$this->name}[$name]\" value=\"$value\" id=\"{$this->name}_{$id}\" $class ".$jscript." $style />";
		
		if ($compare_with)
			$this->compare[$name] = $compare_with;
	}
	
	function hiddenInput($name,$required=false,$value=false,$id=false,$db_field_type=false,$jscript=false,$static=false,$j=false,$grid_input=false,$is_current_timestamp=false,$on_every_update=false) {
		global $CFG;
		
		$id = ($id) ? $id : $name;
		$value = (!empty($this->info[$name]) && empty($value) && !$static) ? $this->info[$name] : $value;
		
		if ($is_current_timestamp) {
			$value = (empty($value) || $on_every_update) ? date('Y-m-d H:i:s') : $value;
		}
		
		if (!$static) {
			if ($this->table) {
				$this->db_fields[$name] = ($db_field_type) ? $db_field_type : 'vchar';
			}
		}

		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
		$id_j = ($grid_input) ? '_'.$j.'_'.$grid_input : '';
		$HTML = "<input type=\"hidden\" name=\"{$this->name}[$name]{$mult}\" value=\"$value\" class=\"hidden_input\" id=\"{$this->name}_{$id}{$id_j}\" $jscript />";


		if (!$static) {
			$this->HTML[] = $HTML;
		}
		else {
			return $HTML;
		}
	}
	
	function checkBox($name,$caption=false,$required=false,$id=false,$class=false,$jscript=false,$style=false,$checked=false,$label_class=false,$static=false,$j=false,$grid_input=false) {
		global $CFG;

		$label_class = ($label_class) ? 'class="' . $label_class . '"' : false;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$caption = ($caption) ? $caption : $name;
		$checked = (($this->info[$name] == 'Y' && !$static) || $checked == 'Y') ? 'checked="checked"' : '';
		
		if (!$static) {
			if ($required) {
				$this->required[$name] = 'checkbox';
				$req_img = $this->req_img;
			}
			else
				$req_img = false;
			
			if ($this->table) {
				$this->db_fields[$name] = 'checkbox';
			}
		}
		
		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
		
		$HTML = $req_img."<div class=\"label_extend\"></div><label $label_class for=\"{$this->name}_{$id}{$j}\">$caption </label><input type=\"checkbox\" class=\"checkbox\" name=\"{$this->name}[$name]{$mult}\" value=\"Y\" id=\"{$this->name}_{$id}{$j}\" ".$jscript." $style $checked />
						<input type=\"hidden\" name=\"checkboxes[]\" value=\"$name\" />";
		
		if (!$static) {
			$this->HTML[] = $HTML;
		}
		else {
			return $HTML;
		}
	}
	
	function radioInput($name, $caption = false, $required = false, $value = false, $id = false, $class = false, $jscript = false, $style = false, $checked = false) {
		global $CFG;
		
		$class = 'class="radio_input '.$class.'"';
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$caption = ($caption) ? $caption : $name;
		$checked = ($this->info[$name] == $value || (!$this->info && $checked)) ? 'checked="checked"' : '';
		
		if ($required  && !@array_key_exists($name,$this->required)) {
			$this->required[$name] = '';
			$req_img = $this->req_img;
		}
		else
			$req_img = false;
		
		if ($this->table) {
			$this->db_fields[$name] = 'enum';
		}
		
		$this->HTML[] = $req_img."<label for=\"{$this->name}_{$id}\">$caption </label><input type=\"radio\" name=\"{$this->name}[$name]\" value=\"$value\" id=\"{$this->name}_{$id}\" $class ".$jscript." $style $checked />
						<input type=\"hidden\" class=\"form_radio_inputs\" name=\"radioinputs[$name][]\" value=\"$value\" />";
	}
	
	function textArea($name, $caption = false, $required = false, $value = false, $id = false, $class = false, $jscript = false, $style = false,$is_manual_array=false,$static=false,$j=false,$grid_input=false) {
		global $CFG;
		
		$class = ($class) ? 'class="' . $class . '"' : false;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$caption = ($caption) ? $caption : $name;
		$value = ($this->info && !$static) ? $this->info[$name] : $value;
		$db_field_type = ($is_manual_array) ? 'text' : $db_field_type;
		
		if (!$static) {
			if ($required) {
				$this->required[$name] = $required;
				$req_img = $this->req_img;
			}
			else
				$req_img = false;
			
			if ($this->table) {
				$this->db_fields[$name] = 'text';
			}
		}
		
		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
		
		$HTML = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}{$j}\">$caption </label><textarea name=\"{$this->name}[$name]{$mult}\" id=\"{$this->name}_{$id}{$j}\" $class ".$jscript." $style $onblur wrap=\"virtual\">$value</textarea>";
		if (!$static) {
			$this->HTML[] = $HTML;
		}
		else {
			return $HTML;
		}
	}
	
	function textEditor($name, $caption = false, $required = false, $value = false, $id = false, $echo_on = false,$class=false,$is_basic=false,$allow_images=false,$height=false,$method_id=false) {
		global $CFG;
		
		$id = ($id) ? $id : $name;
		$caption = ($caption) ? $caption : $name;
		$value = (!empty($this->info[$name])) ? $this->info[$name] : $value;
		
		if ($required) {
			$this->required[$name] = $required;
			$req_img = $this->req_img;
		}
		else
			$req_img = false;
			
		if ($this->table) {
			$this->db_fields[$name] = 'blob';
		}
			
		$HTML = '';
		$HTML .= "<new_editor>";
		$HTML .= $req_img."<div class=\"label_extend\"></div><label class=\"editor_label\" for=\"{$this->name}_{$id}\">$caption </label>";
		
		$HTML .= '
		<textarea name="'.$this->name."[$name]".'">'.$value.'</textarea>
		<script>
		    CKEDITOR.replace(\''.$this->name."[$name]".'\',{ filebrowserImageUploadUrl:  \'ckeditor/upload.php\' '.(($is_basic) ? ',toolbarGroups : [{ name: \'basicstyles\', groups: [ \'basicstyles\', \'cleanup\' ] }]' : false).'});
		</script>		
		';

		$this->HTML[] = $HTML;
	}
	
	
	// you can use both $options_array and $subtable_fields to populate
	// $subtable_fields usage: table.field1, table.field2
	// $level can be 1 to n -> used for tables with p_id
	function selectInput($name,$caption=false,$required=false,$value=false,$options_array=false,$subtable=false,$subtable_fields=false,$subtable_f_id=false,$id=false,$class=false,$jscript=false,$style=false,$f_id_field=false,$default_text=false,$depends_on=false,$function_to_elements=false,$static=false,$j=false,$grid_input=false,$first_is_default=false,$level=false) {
		global $CFG;

		$class = ($class) ? 'class="' . $class . '"' : false;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$id = str_replace('.','_',$id);
		$caption = ($caption) ? $caption : $name;
		$value = (!empty($this->info[$name]) && !$static) ? $this->info[$name] : $value;

		if (!$static) {
			if ($required) {
				$this->required[$name] = $required;
				$req_img = $this->req_img;
			}
			else
				$req_img = false;
			
			if ($this->table) {
				$this->db_fields[$name] = (!$subtable) ? 'vchar' : 'int';
			}
		}
		
		if (is_array($options_array) && !$subtable) {
			$key1 = key($options_array);
			if (is_array($options_array[$key1])) {
				$options_array1 = $options_array;
				unset($options_array);
				foreach ($options_array1 as $i => $option_array) {
					if (is_array($subtable_fields)) {
						$arr = array();
						foreach ($subtable_fields as $field) {
							$arr[] = $option_array[$field];
						}
						$i = ($option_array['id'] > 0) ? $option_array['id'] : $i;
						$options_array[$i] = implode(' ',$arr);
					}
					else {
						$i = ($option_array['id'] > 0) ? $option_array['id'] : $i;
						$options_array[$i] = implode(' ',$option_array);
					}
				}
			}
		} 
		
		if ($subtable) {
			if (in_array('p_id',$subtable_fields))
				$db_output = DB::sortCats($db_output,0,1,$level);

			if ($db_output) {
				if ($options_array) {
					$options_array = array_merge($db_output,$options_array);
				}
				else {
					$options_array = $db_output;
				}
			}
		}

		if ($function_to_elements) {
			$options_array = array_map($function_to_elements,$options_array);
		}
		

		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
		$HTML = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}{$j}\">$caption </label><select name=\"{$this->name}[$name]{$mult}\" id=\"{$this->name}_{$id}{$j}\" $class $style>";
		if (!$first_is_default)
			$HTML .= "<option value=\"\" ".((empty($value)) ? 'selected = "selected"' : '').">$default_text</option>";
		if (is_array($options_array)) {
			if (@in_array('p_id',$subtable_fields) && !$level) {
				$HTML .= self::structureOptions($options_array,false,$value);
			}
			else {
				foreach ($options_array as $option => $option_name) {
					$HTML .= "<option value=\"$option\" ".(($option == $value) ? 'selected = "selected"' : '').">$option_name</option>";
				}
			}
		}
		$HTML .= '</select>';
		
		if ($depends_on) {
			$subtable_field = (!empty($f_id_field)) ? $f_id_field : 'f_id';
			$depends_on = str_replace('.','_',$depends_on);
			$HTML .= '
			<script type="text/javascript">
				$("#'.$this->name.'_'.$depends_on.'").change(function() {
					var value = $("#'.$this->name.'_'.$depends_on.'").attr("value");
					ajaxGetPage("ajax.get_options.php?value="+value+"&table='.$subtable.'&field='.$subtable_field.'&return_type=select","'.$this->name.'_'.$id.'");
				});
			</script>';
		}

		if (!$static) {
			$this->HTML[] = $HTML;
		}
		else {
			return $HTML;
		}
	}

	private function structureOptions(&$options_array,$concat_char=false,$value=false,$i=-1,$faux=false) {
		$HTML = '';
		$concat_char = ($concat_char) ? $concat_char : ' ';
		$i++;
		if ($options_array) {
			foreach ($options_array as $id => $row) {
				if ($faux) {
					if ($i > 0)
						$indent = 'f_indent'.$i;
						
					unset($row['row']['id']);
					unset($row['row']['p_id']);
					$option_name = implode($concat_char,$row['row']);
					$o = '<a class="f_elem '.$indent.'" onclick="fauxSelect(this,event,\''.$id.'\')">'.$option_name.'</a>';
					$options[] = $o;
					if ($id == $value)
						$HTML .= $o;
					
					if ($row['children'])
						$HTML .= self::structureOptions($row['children'],$concat_char,$value,$i,$faux);
				}
				else {
					if ($i > 0)
					$indent = str_repeat('--',$i).' ';
					
					unset($row['row']['id']);
					unset($row['row']['p_id']);
					$option_name = implode($concat_char,$row['row']);
					$HTML .= "<option value=\"$id\" ".(($id == $value) ? 'selected = "selected"' : '').">".$indent.$option_name."</option>";
					if ($row['children'])
						$HTML .= self::structureOptions($row['children'],$concat_char,$value,$i);
				}
			}
		}
		
		if ($faux) {
			$return['html'] = $HTML;
			$return['options'] = $options;
		}
		else
			return $HTML;
	}
	
	function passiveField($name=false,$caption=false,$subtable=false,$subtable_fields=false,$link_url=false,$concat_char=false,$f_id_field=false,$order_by=false,$order_asc=false,$link_is_tab=false,$limit_is_curdate=false,$create_db_field=false,$default_value=false,$dont_fill_automatically=false) {
		global $CFG;

		$HTML = '';

		$record = new Record($this->table,$this->record_id);
		$HTML .= '<div id="passive_'.$this->name.'_'.$name.'">'.$record->field($name,$caption,$subtable,$subtable_fields,$link_url,$concat_char,true,$f_id_field,$order_by,$order_asc,$this->record_id,$link_is_tab,$limit_is_curdate,$value).'</div>';
		
		if ($f_id_field) {
			if (stristr($f_id_field,',')) {
				$f_parts = explode(',',$f_id_field);
				$f_id_field1 = $f_parts[0];
			}
			else {
				$f_id_field1 = $f_id_field;
			}
			
			if (strstr($f_id_field1,'.')) {
				$f_parts1 = explode('.',$f_id_field1);
				$get_field = $f_parts1[1];
			}
			else {
				$get_field = $f_id_field1;
			}
			if ($get_field) {
				$HTML .= '
				<script type="text/javascript">
					getPassiveValue(\'ajax.passive.php?table='.$this->table.'&name='.$name.'&caption='.$caption.'&subtable='.$subtable.'&subtable_fields='.urlencode(serialize($subtable_fields)).'&link_url='.$link_url.'&concat_char='.$concat_char.'&f_id_field='.$f_id_field.'&order_by='.$order_by.'&order_asc='.$order_asc.'&link_is_tab='.$link_is_tab.'&limit_is_curdate='.$limit_is_curdate.'&record_id='.$this->record_id.'\',\'passive_'.$this->name.'_'.$name.'\',\''.$this->name.'_'.$get_field.'\');
				</script>
				';
			}
		}
		
		$this->HTML[] = $HTML;
	}
	
	
	
	function captcha($caption=false) {
		global $CFG;

		$this->HTML[] = '<captcha>';
		$this->c_caption = $caption;
	}
	
	function HTML($HTML) {
		global $CFG;
		
		$this->HTML[] = '<htmlfield>'.$HTML;
	}
	
	function show_errors() {
		// display errors
		if ($this->errors) {
			if (!$this->errors_to_function) {
				echo '<ul class="errors">';
				foreach ($this->errors as $name => $error) {
					echo '<li><div class="error_icon"></div>'.ucfirst(str_ireplace('[field]',$name,$error)).'</li>';
				}
				echo '</ul><div class="clear">&nbsp;</div>';
			}
			else {
				Errors::merge($this->errors);	
			}
		}
	}
	
	function show_messages() {
		// display messages
		if ($this->messages) {
			if (!$this->errors_to_function) {
				echo '<ul class="messages">';
				foreach ($this->messages as $name => $message) {
					echo '<li><div class="warning_icon"></div>'.ucfirst(str_ireplace('[field]',$name,$message)).'</li>';
				}
				echo '</ul><div class="clear"></div>';
			}
			else {
				Messages::merge($this->errors);	
			}
		}
	}
	
	function display() {
		global $CFG;		
	
		// display form
		if (!$this->output_started) {
			
			echo '<form name="'.$this->name.'" action="'.$this->action.'" class="form '.$this->class.'" method="'.$this->method.'"  '.$this->enctype.' '.$this->target.'>';
		}
		
		echo '<input type="hidden" id="form_name" name="form_name" value="'.htmlspecialchars($this->name).'" />';
		
		if ($this->go_to_url)
			echo '<input type="hidden" name="remember_values" value="1"/>';
			
		echo '<ul>';

		
		if (is_array($this->HTML)) {
			$alt = 'alt';
			foreach ($this->HTML as $method_id => $elem) {
				if (!stristr($elem,'<htmlfield>'))
					$alt = ($alt) ? false : 'alt';
					
				if ($this->current_restricted > 0 && !stristr($elem,'</restricted>')) {
					$alt = ($alt) ? false : 'alt';
					continue;
				}
				elseif (stristr($elem,'<button>')) {
					$elem = str_replace('<button>','',$elem);
					$alt = 'button_alt';
				}
				elseif (stristr($elem,'<date_filter_end>')) {
					$elem = str_replace('<date_filter_end>','',$elem);
					$alt = ($alt) ? false : 'alt';
				}
				elseif (stristr($elem,'<include>')) {
					echo str_replace('<include>','',$elem);
					continue;
				}
				elseif (stristr($elem,'<group>')) {
					$in_group = true;
					echo str_replace('<group>','','<li class="'.$alt.'">'.$elem);
					continue;
				}
				elseif (stristr($elem,'</group>')) {
					$in_group = false;
					echo str_replace('</group>','',$elem);
					continue;
				}
				elseif (stristr($elem,'<tab>')) {
					echo str_replace('<tab>','',$elem);
					continue;
				}
				elseif (stristr($elem,'</tab>')) {
					echo str_replace('</tab>','',$elem);
					continue;
				}
				elseif ($elem == '<captcha>') {
					if (!empty($CFG->google_recaptch_api_key) && !empty($CFG->google_recaptch_api_secret)) {
						echo '<li class="'.$alt.'"><div class="g-recaptcha" data-sitekey="'.$CFG->google_recaptch_api_key.'"></div></li>';
					}
					else {
						echo "
						<li class=\"$alt\">
							<div class=\"captcha\">
								<label for=\"captcha\">{$this->c_caption}</label>
								<img id=\"captcha\" src=\"securimage/securimage_show.php\" />
								<input type=\"text\" name=\"caco\" />
								<input type=\"hidden\" name=\"is_caco[{$this->name}]\" value=\"1\">
							</div>
						</li>";
					}
					continue;
				}
				elseif (stristr($elem,'<htmlfield>')) {
					echo str_replace('<htmlfield>','',$elem);
					$this->group = false;
					
					continue;
				} 
				elseif (stristr($elem,'<editor')) {
					$editor = explode('|||',$elem);
					$this->textEditor($editor[1],$editor[2],$editor[5],$editor[3],$editor[4],true,$editor[6],$editor[7],$editor[8],$editor[9],$method_id);
					continue;
				} 
				elseif (stristr($elem,'<new_editor>')) {
					echo "<li class=\"editor_li $alt\">".str_replace('<new_editor>','',$elem).'</li>';
					continue;
				}
				elseif (stristr($elem,'<pe_editor')) {
					$editor = explode('|||',$elem);
					$this->permissionEditor($editor[1],$editor[2],true);
					continue;
				}
				elseif (stristr($elem,'<area>')) {
					echo str_replace('<area>','',$elem);
					continue;
				}
				elseif (stristr($elem,'</area>')) {
					echo str_replace('</area>','',$elem);
					continue;
				}
				elseif (stristr($elem,'<restricted>')) {
					$this->current_restricted = 1;
					$alt = ($alt) ? false : 'alt';
					continue;
				}
				elseif (stristr($elem,'</restricted>')) {
					$this->current_restricted = 0;
					$alt = ($alt) ? false : 'alt';
					continue;
				}
				
				if (!$this->group && !stristr($elem,'<fieldset') && $elem != '</fieldset>' && !stristr($elem,'class="hidden_input"')) echo "<li class=\"$alt\">";
				echo $elem;
				if (!$this->group && !stristr($elem,'<fieldset') && $elem != '</fieldset>' && !stristr($elem,'class="hidden_input"')) echo '</li>';
				if ($this->group) echo '&nbsp;&nbsp;&nbsp;'; 
			}
		}
		
		echo '</ul>';
		
		// send required fields
		if (is_array($this->required)) {
			foreach ($this->required as $field => $type) {
				echo '<input type="hidden" name="verify_fields['.htmlspecialchars($field).']" value="'.htmlspecialchars($type).'" />';
			}
		}
		
		// send compare fields
		if (is_array($this->compare)) {
			foreach ($this->compare as $field => $value) {
				echo '<input type="hidden" class="compare_fields" name="compare_fields['.htmlspecialchars($field).']" value="'.htmlspecialchars($value).'" />';
			}
		}
		
		// send unique fields
		if (is_array($this->unique)) {
			foreach ($this->unique as $field => $value) {
				echo '<input type="hidden" class="unique_fields" name="unique_fields['.htmlspecialchars($field).']" value="'.htmlspecialchars($value).'" />';
			}
		}
		
		if (is_array($this->delete_whitespace)) {
			foreach ($this->delete_whitespace as $field) {
				echo '<input type="hidden" name="delete_whitespace[]" value="'.htmlspecialchars($field).'" />';
			}
		}
		
		if (is_array($this->color_fields)) {
			foreach ($this->color_fields as $field) {
				echo '<input type="hidden" name="color_fields[]" value="'.htmlspecialchars($field).'" />';
			}
		}
		
		if (!$this->output_started) {
			echo '</form>';
		}
	}
	
	function verify() {
		global $CFG;
		
		if ($this->info && $this->compare_fields) {
			foreach ($this->compare_fields as $name => $comp_name) {
				if (!empty($this->info[$comp_name])) {
					if ($this->info[$comp_name] != $this->info[$name])
						$this->errors[$name] = $this->compare_error;
				}
				unset($this->info[$name]);
			}
		}
		
		if (!empty($_REQUEST['is_caco'][$this->name])) {
			include_once 'securimage/securimage.php';
			$securimage = new Securimage();
			if ($securimage->check($_REQUEST['caco']) == false) {
				$this->errors[] = $this->capcha_error;
			}
		}
		
		if ($this->info && $this->verify_fields) {
			foreach ($this->info as $name => $value) {
				if (array_key_exists($name,$this->verify_fields)) {
					// date range verification
					if (strstr($this->verify_fields[$name],'date')) {
						$this->verify_fields[$name] = str_replace($this->verify_fields[$name],'date:','');
						$dates = explode('|',$this->verify_fields[$name]);
						
						if (!empty($dates[0])) 
							$e0 = (strtotime($value) > strtotime($dates[0]));
						if (!empty($dates[1])) 
							$e1 = (strtotime($value) > strtotime($dates[1]));
							
						if ($e0 || $e1)
							$this->errors[$name] = $this->verify_date_error;
						
						continue;
					}
					
					switch($this->verify_fields[$name]) {
						case 'email':
							if (!Email::verifyAddress($value)) 
								$this->errors[$name] = $this->verify_email_error;
						break;
						case 'checkbox':
							if ($value == 'N') 
								$this->errors[$name] = $this->verify_default_error;
						break;
						case '':
						case true:
							if (strlen(trim($value)) == 0) 
								$this->errors[$name] = $this->verify_default_error;
						break;
						default:
							if (!preg_match($this->verify_fields[$name],$value)) 
								$this->errors[$name] = $this->verify_custom_error;
						break;
					}
				}
			}
		
			return (is_array($this->errors));
		}
	}
	
	function save() {
		global $CFG;
		
		if ($_REQUEST['bypass_save'] || $CFG->save_called || strstr($_REQUEST['form_name'],'form_filters'))
			return false;
			
		$this->save_called = true;
		$CFG->save_called = true;
		if (!$this->get_called && ($this->record_id > 0)) {
			if (!is_array(self::$old_info_prev)) {
				$this->old_info = DB::getRecord($this->table,$this->record_id,0,1);
				self::$old_info_prev = $this->old_info;
			}
			else {
				$this->old_info = self::$old_info_prev;
			}
			
			$subtables = DB::getSubtables($this->name);
			if (is_array($subtables)) {
				foreach ($subtables as $subtable) {
					if (!DB::tableExists($this->table.'_'.$subtable))
						continue;
					
					if (strstr($subtable,'grid_')) {
						$name_parts = explode('grid_',$subtable);
						$name = $name_parts[1];
						$this->old_info[$name] = DB::getGridValues($this->table.'_grid_'.$name,$subtable_fields,$this->record_id);
					}
					elseif(strstr($subtable,'files')){
						//$files = DB::getFiles($this->table.'_files',$this->record_id);
					}
					else {
						if ($this->info['cat_selects']) {
							$cats = DB::getCats($this->table.'_'.$subtable,$this->record_id);
							if ($cats) {
								foreach ($cats as $cat) {
									$this->old_info['cat_selects'][$subtable][] = $cat['row']['c_id'];
								}
							}
						}
					}
				}
			}
		}

		if ($_FILES[$this->name]['name']) {
			foreach ($_FILES[$this->name]['name'] as $input_name => $file_name) {
				if ($file_name)
					$temp_files[] = Upload::saveTemp($this->name,$input_name);
			}
			if (is_array($temp_files)) {
				foreach ($temp_files as $file_info) {
					$field_name = $file_info['input_name'];
					if ($file_info['error']) {
						$this->errors[$field_name] = $file_info['error'];
					}
					else {
						$this->temp_files[$field_name] = $file_info['filename'];
						$CFG->temp_files[$field_name] = $file_info['filename'];
						$this->temp_descs[$field_name] = $file_info['file_desc'];
					}
				}
			}
		}
	}
	
	function get($info) {
		global $CFG;
		
		if (!is_array($this->info))
			$this->info = $info;
	}
	
	function reCaptchaCheck($force=false) {
		global $CFG;
		
		if (empty($CFG->google_recaptch_api_key) || empty($CFG->google_recaptch_api_secret) || (empty($this->info) && !$force))
			return false;
		
		if (empty($_REQUEST['g-recaptcha-response'])) {
			$this->errors['recaptcha'] = Lang::string('google-recaptcha-error');
			return false;
		}
		
		$ip = API::getUserIp();
		
		$ch = curl_init('https://www.google.com/recaptcha/api/siteverify?secret='.$CFG->google_recaptch_api_secret.'&response='.$_REQUEST['g-recaptcha-response'].'&remoteip='.$ip);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_FRESH_CONNECT,TRUE);
		
		$result1 = curl_exec($ch);
		$result = json_decode($result1,true);
		curl_close($ch);
		
		if (!is_array($result))
			$this->errors['recaptcha'] = Lang::string('google-recaptcha-connection');
		elseif ($result['success'] !== true)
			$this->errors['recaptcha'] = Lang::string('google-recaptcha-error');
		elseif ($result['success'] === true)
			return true;
	}
}
?>