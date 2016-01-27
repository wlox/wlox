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
		$this->table = ($table) ? $table : $_REQUEST['form_table'];
		//$this->class = ($class) ? $class : 'form';
		$this->class = $class;
		$this->enctype = $enctype;
		$this->info = $_REQUEST[$name];
		$this->verify_fields = $_REQUEST['verify_fields'];
		$this->temp_files = $_REQUEST['temp_files'];
		$this->urls = $_REQUEST['urls'];
		$this->attached_file_fields = $_REQUEST['attached_file_fields'];
		$this->record_id = $_REQUEST['record_id'];
		$this->errors_to_function = $errors_to_function;
		$this->return_to_self = $return_to_self;
		$this->unique_fields = $_REQUEST['unique_fields'];
		$this->compare_fields = $_REQUEST['compare_fields'];
		$this->delete_whitespace = $_REQUEST['delete_whitespace'];
		$this->go_to_url = $go_to_url;
		$this->go_to_action = $go_to_action;
		$this->go_to_is_tab = $go_to_is_tab;
		$this->target = ($target) ? 'target="'.$target.'"' : false;
		$this->includes = $_REQUEST['includes'];
		$this->current_area = 0;
		$this->area_i = 0;
		
		if ($start_on_construct) {
			echo "<form name=\"$this->name\" action=\"$this->action\" class=\"form $this->class\" method=\"$this->method\"  $this->enctype $this->target>";
			$this->output_started = true;
		}
		elseif($CFG->form_output_started || $CFG->in_include) {
			$this->output_started = true;
		}
	
		if (is_array($this->includes)) {
			foreach ($this->includes as $i_table => $i_info) {
				$this->info = array_merge($this->info,$_REQUEST[$i_info['name']]);
				$c1 = count($_REQUEST[$i_info['name']]);
				$c2 = count($i_info['fields']);
				$this->ignore_fields = (is_array($this->ignore_fields)) ? $this->ignore_fields : array();
				$this->ignore_fields = array_merge($this->ignore_fields,array_combine(array_keys($_REQUEST[$i_info['name']]),array_fill(0,$c1,$i_table)),array_combine(array_keys($i_info['fields']),array_fill(0,$c2,$i_table)));
			}
		}
		
		if ($this->info) {
			if ($_REQUEST['remember_values'] && !$CFG->in_include) {
				$_SESSION[$this->name.'_info'] = $this->info;
			}
			if ($_REQUEST['datefields']) {
				foreach ($this->info as $key => $value) {
					if (array_key_exists($key, $_REQUEST['datefields'])) {
						if ($this->info[$key.'_h']) {
							$this->info[$key] = $this->info[$key].' '.$this->info[$key.'_h'].':'.$this->info[$key.'_m'].''.$this->info[$key.'_ampm'];
							unset($this->info[$key.'_h']);
							unset($this->info[$key.'_m']);
							unset($this->info[$key.'_ampm']);
						}
						if (!empty($value))
							$this->info[$key] = date('Y-m-d H:i:00', strtotime($this->info[$key]));
						else
							$this->info[$key] = false;
							
							if (@array_key_exists($key, $_REQUEST['timefields'])) {
								$key1 = $_REQUEST['timefields'];
								if (!empty($this->info[$key1])) {
									$v1 = date('Y-m-d', strtotime($this->info[$key1]));
									$v2 = date('H:i:00', strtotime($this->info[$key]));
									$this->info[$key] = date('Y-m-d H:i:00', strtotime($v1.' '.$v2));
								}
							}
					}
				}
			}
			
			if ($_REQUEST['checkboxes']) {
				foreach ($_REQUEST['checkboxes'] as $checkbox) {
					if (!array_key_exists($checkbox, $this->info)) {
						$this->info[$checkbox] = '';
					}
				}
			}
			if ($_REQUEST['color_fields']) {
				$CFG->bypass_unserialize = true;
				foreach ($_REQUEST['color_fields'] as $name) {
					if (array_key_exists($name, $this->info)) {
						if (array_key_exists('color',$this->info[$name])) {
							if (empty($this->info[$name]['color'])) {
								unset($this->info[$name]);
								continue;
							}
						}
						else {
							if (empty($this->info[$name])) {
								unset($this->info[$name]);
								continue;
							}
						}
						$this->info[$name] = serialize($this->info[$name]);
					}
				}
			}
			if (is_array($this->urls)) {
				$this->info['urls'] = $this->urls;
			}
		}
		else {
			if ($_SESSION[$this->name.'_info'] && $this->go_to_url) {
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
		$value = ($this->info && !$static) ? $this->info[$name] : $value;
		$value = (strstr($value,'<')) ? htmlentities($value) : $value;
		$db_field_type = ($is_manual_array) ? 'text' : $db_field_type;
		$onblur = ($is_manual_array) ? "onblur=\"prepend(this,'array:')\"" : '';
		$onblur = ($default_text) ? "onblur=\"defaultText(this,'$default_text')\"" : '';
		$onclick = ($default_text) ? "onclick=\"defaultText(this,'$default_text')\"" : '';
	
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;

		if (!$static) {
			if ($delete_whitespace)
				$this->delete_whitespace[] = $name;
			
			if ($required) {
				$this->required[$name] = $required;
				$req_img = $CFG->req_img;
			}
			
			if ($this->table) {
				$this->db_fields[$name] = ($db_field_type) ? $db_field_type : 'vchar';
			}
			
			if ($is_unique)
				$this->unique[$name] = ($unique_error) ? $unique_error : '';
		}

		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
		$id_j = ($grid_input) ? '_'.$j.'_'.$grid_input : '';
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'textInput');
			$jscript = false;
			$outside_jscript = false;
		}
		
		$HTML = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}{$id_j}\">$caption $method_name</label><input type=\"text\" name=\"{$this->name}[$name]{$mult}\" value=\"$value\" id=\"{$this->name}_{$id}{$id_j}\" $class ".$jscript." $style $onblur $onclick />$outside_jscript";
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
		$value = ($this->info) ? $this->info[$name] : $value;
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;
		
		if ($required) {
			$this->required[$name] = 'password';
			$req_img = $CFG->req_img;
		}
		
		if ($this->table && !$compare_with) {
			$this->db_fields[$name] = 'password';
		}	
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'passwordInput');
			$jscript = false;
			$outside_jscript = false;
		}
		
		$this->HTML[] = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}\">$caption $method_name</label><input type=\"password\" name=\"{$this->name}[$name]\" value=\"$value\" id=\"{$this->name}_{$id}\" $class ".$jscript." $style />$outside_jscript";
		
		if ($compare_with)
			$this->compare[$name] = $compare_with;
	}
	
	function hiddenInput($name,$required=false,$value=false,$id=false,$db_field_type=false,$jscript=false,$static=false,$j=false,$grid_input=false,$is_current_timestamp=false,$on_every_update=false) {
		global $CFG;
		
		if ($CFG->o_method_suppress && $CFG->pm_editor)
			return false;

		$id = ($id) ? $id : $name;
		$value = ($this->info && empty($value) && !$static) ? $this->info[$name] : $value;
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;
		
		if ($is_current_timestamp) {
			$value = (empty($value) || $on_every_update) ? date('Y-m-d H:i:s') : $value;
		}
		
		if (!$static) {
			if ($this->table) {
				$this->db_fields[$name] = ($db_field_type) ? $db_field_type : 'vchar';
			}
		}
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'hiddenInput');
			$jscript = false;
			$outside_jscript = false;
		}

		if (!$CFG->pm_editor) {
			$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
			$id_j = ($grid_input) ? '_'.$j.'_'.$grid_input : '';
			$HTML = "<input type=\"hidden\" name=\"{$this->name}[$name]{$mult}\" value=\"$value\" class=\"hidden_input\" id=\"{$this->name}_{$id}{$id_j}\" $jscript />$outside_jscript";
		}
		else {
			$HTML = "[{$name}] $method_name";
		}
		
		/*
		if ($this->db_fields[$name] == 'blob')
			return false;
		*/

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
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;
		
		if (!$static) {
			if ($required) {
				$this->required[$name] = 'checkbox';
				$req_img = $CFG->req_img;
			}
			
			if ($this->table) {
				$this->db_fields[$name] = 'checkbox';
			}
		}
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'checkBox');
			$jscript = false;
			$outside_jscript = false;
		}
		
		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
		
		$HTML = $req_img."<div class=\"label_extend\"></div><label $label_class for=\"{$this->name}_{$id}{$j}\">$caption $method_name</label><input type=\"checkbox\" class=\"checkbox\" name=\"{$this->name}[$name]{$mult}\" value=\"Y\" id=\"{$this->name}_{$id}{$j}\" ".$jscript." $style $checked />$outside_jscript
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
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;
		
		if ($required  && !@array_key_exists($name,$this->required)) {
			$this->required[$name] = '';
			$req_img = $CFG->req_img;
		}
		
		if ($this->table) {
			$this->db_fields[$name] = 'enum';
		}

		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'radioInput');
			$jscript = false;
			$outside_jscript = false;
		}
		
		$this->HTML[] = $req_img."<label for=\"{$this->name}_{$id}\">$caption $method_name</label><input type=\"radio\" name=\"{$this->name}[$name]\" value=\"$value\" id=\"{$this->name}_{$id}\" $class ".$jscript." $style $checked />$outside_jscript
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
		$onblur = ($is_manual_array) ? "onblur=\"prepend(this,'array:',true)\"" : '';
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;
		
		if (!$static) {
			if ($required) {
				$this->required[$name] = $required;
				$req_img = $CFG->req_img;
			}
			
			if ($this->table) {
				$this->db_fields[$name] = 'text';
			}
		}
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'textArea');
			$jscript = false;
			$outside_jscript = false;
		}
		
		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
		
		$HTML = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}{$j}\">$caption $method_name</label><textarea name=\"{$this->name}[$name]{$mult}\" id=\"{$this->name}_{$id}{$j}\" $class ".$jscript." $style $onblur wrap=\"virtual\">$value</textarea>$outside_jscript";
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
		$value = ($this->info) ? $this->info[$name] : $value;
		$method_id = ($CFG->o_method_id) ? $CFG->o_method_id : $CFG->method_id;
		
		if ($required) {
			$this->required[$name] = $required;
			$req_img = $CFG->req_img;
		}
			
		if ($this->table) {
			$this->db_fields[$name] = 'blob';
		}
			
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($method_id,'textEditor');
		}
			
		$HTML = '';
		
		if ($CFG->pm_editor) {
			$HTML = "
			$name [text_editor] $method_name";
		}
		else {
			$HTML .= "<new_editor>";
			$HTML .= $req_img."<div class=\"label_extend\"></div><label class=\"editor_label\" for=\"{$this->name}_{$id}\">$caption $method_name</label>";
			
			$HTML .= '
			<textarea name="'.$this->name."[$name]".'">'.$value.'</textarea>
			<script>
			    CKEDITOR.replace(\''.$this->name."[$name]".'\',{ filebrowserImageUploadUrl:  \'ckeditor/upload.php\' '.(($is_basic) ? ',toolbarGroups : [{ name: \'basicstyles\', groups: [ \'basicstyles\', \'cleanup\' ] }]' : false).'});
			</script>		
			';
			
		}

		if ($CFG->pm_editor) {
				$this->HTML[$method_id] = $HTML;
		}
		else
			$this->HTML[] = $HTML;
		
		/*
		if ($echo_on) {
			if ($required) {
				$this->required[$name] = $required;
				$req_img = $CFG->req_img;
			}
			
			if ($this->table) {
				$this->db_fields[$name] = 'blob';
			}
			
			if ($CFG->pm_editor) {
				$method_name = self::peLabel($method_id,'textEditor');
			}
			
			echo "<li class=\"editor_li\">";
			
			if ($CFG->pm_editor) {
				echo "
				$name [text_editor] $method_name";
			}
			else {
				if ($allow_images) {
					setcookie("fckdir", $CFG->dirroot.'uploads/',time()+2592000);
					setcookie("fckdirabs", $CFG->baseurl.'uploads/',time()+2592000);
				}
				
				echo $req_img."<label class=\"editor_label\" for=\"{$this->name}_{$id}\">$caption $method_name</label>";
				$oFCKeditor = new FCKeditor("{$this->name}[$name]");
				$oFCKeditor->BasePath = 'shared/fckeditor/';
				$oFCKeditor->Config['BaseHref'] = $CFG->baseurl.'uploads/';
				$oFCKeditor->Config['UserFilesPath'] = $CFG->baseurl.'uploads/';
				$oFCKeditor->Config['UserFilesAbsolutePath'] = $CFG->dirroot.'uploads/';
				$oFCKeditor->Config['ImageUpload'] = true;
				$oFCKeditor->Value = $value;
				$oFCKeditor->Height = ($height) ? $height.'px' : '350px';
				$_COOKIE['fckdir'] = false;
				$_COOKIE['fckdirabs'] = false;
				if ($CFG->fck_css_file) {
					$oFCKeditor->Config['EditorAreaCSS'] = $CFG->baseurl.'css/'.$CFG->fck_css_file;
				}
				if ($is_basic) {
					$oFCKeditor->ToolbarSet = 'Basic';
				}
				else {
					if ($allow_images)
						$oFCKeditor->ToolbarSet = 'YesImages';
					else
						$oFCKeditor->ToolbarSet = 'NoImages';
				}
				
				$oFCKeditor->Create();
			}
			echo '<div class="height"></div></li>';
		}
		else {
			$id = ($id) ? $id : $name;
			$caption = ($caption) ? $caption : $name;
			$value = ($this->info) ? $this->info[$name] : $value;
			
			$HTML = "<editor|||$name|||$caption|||$value|||$id|||$required|||$class|||$is_basic|||$allow_images|||$height|||$method_id||| >";
			
			if ($CFG->pm_editor) {
				$method_id = ($CFG->o_method_id) ? $CFG->o_method_id : $CFG->method_id;
				$this->HTML[$method_id] = $HTML;
			}
			else
				$this->HTML[] = $HTML;
		}
		*/
	}
	
	// permitted: array of permitted types (ex: jpg, gif, doc)
	// image_sizes: real array, not faux
	function fileInput($name,$caption=false,$required=false,$permitted_ext=false,$max_size=false,$dir=false,$image_sizes=false,$amount=0,$id=false,$class=false,$jscript=false,$style=false,$crop_images=false,$no_url=false,$encrypt=false,$allow_descriptions=false) {
		global $CFG;
		
		if ($required) {
			$this->required[$name] = 'file';
			$req_img = $CFG->req_img;
		}
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'fileInput');
		}
		
		$caption = ($caption) ? $caption : $name;
		$HTML = $req_img."<div class=\"label_extend\"></div><label class=\"file_input_label\">$caption $method_name</label><ul class=\"file_input_list\"><input type=\"hidden\" class=\"file_list_name\" id=\"file_list_".$name."\" value=\"".$name."\" />";
		$dir = ($dir) ? $dir : $CFG->default_upload_location;
		$permitted_ext = ($permitted_ext) ? $permitted_ext : $CFG->accepted_file_formats;
		$amount = ($amount > 0) ? $amount : 12;
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;
		
		$i = 0;
		if ($this->record_id) {
			$files = DB::getFiles($this->table.'_files',$this->record_id,$name);
			if ($files) {
				$c = count($files);
				foreach ($files as $file) {
					$HTML .= '<li id="'.$i.'">';
					$HTML .= File::showLinkForResult($file,false,$this->table,$encrypt);
					
					if ($allow_descriptions)
						$HTML .= '<div class="file_desc" onclick="fieldZoomIn(\'file_desc__'.$this->table.'_'.$file['id'].'\',\'textarea\',\''.urlencode($CFG->gallery_desc_label).'\')"><img class="'.(($file['file_desc']) ? 'opaq' : '').'" src="'.$CFG->gallery_desc_icon.'" title="'.(($file['file_desc']) ? $file['file_desc'] : $CFG->gallery_desc_tooltip).'"/><input type="hidden" id="file_desc__'.$this->table.'_'.$file['id'].'" name="file_descs['.$file['id'].']" value="'.$file['file_desc'].'" /></div>';
						
					$HTML .= ($c > 1) ? '<a href="#" title="'.$CFG->move_hover_caption.'" class="file_move"></a>' : false;
					$HTML .= "<input type=\"hidden\" name=\"attached_file_fields[]\" value=\"$name\" />";
					$HTML .= '<input type="hidden" name="file_order[]" value="'.$file['id'].'" />';
					if (!empty($file['url'])) {
						$HTML .= "<a title=\"{$CFG->delete_hover_caption}\" onclick=\"formDeleteUrl(this,'{$this->table}_files','{$file['id']}')\" class=\"delete\"></a>";
					}
					else {
						$HTML .= "<a title=\"{$CFG->delete_hover_caption}\" onclick=\"formDeleteFile(this,'{$this->table}_files','{$file['id']}','{$file['name']}.{$file['ext']}','{$file['dir']}')\" class=\"delete\"></a>";
					}
					$HTML .= '</li>';
					$i++;
				}
			}
		}
		
		if (!is_array($this->temp_files) && is_array($CFG->temp_files))
			$this->temp_files = $CFG->temp_files;

		if (is_array($this->temp_files)) {
			foreach ($this->temp_files as $k => $v) {
				if (stristr($k,$name)) {
					$i++;
					$HTML .= '<li>';
					$HTML .= File::showLink($v,$CFG->temp_file_location,false,$encrypt);
					
					if ($allow_descriptions)
						$HTML .= '<div class="file_desc" onclick="fieldZoomIn(\'file_desc__'.$this->table.'_'.$k.'\',\'textarea\',\''.urlencode($CFG->gallery_desc_label).'\')"><img src="'.$CFG->gallery_desc_icon.'" title="'.$CFG->gallery_desc_tooltip.'"/><input type="hidden" id="file_desc__'.$this->table.'_'.$k.'" name="file_descs_temp['.$k.']" value="'.$this->temp_descs[$k].'" /></div>';
					
					$HTML .= "<input type=\"hidden\" name=\"temp_files[$k]\" value=\"$v\" />
					<a title=\"{$CFG->delete_hover_caption}\" onclick=\"formDeleteTemp(this,'$v')\"  class=\"delete\"></a>";
					$HTML .= '</li>';
				}
			}
		}
		
		if (!$this->record_id) {
			if (is_array($this->urls)) {
				foreach ($this->urls as $k => $v) {
					if (is_array($v)) {
						foreach ($v as $j => $v1) {
							if (stristr($k,$name) && !empty($v1)) {
								$i++;
								$HTML .= '<li>';
								$HTML .= File::showUrl($v1);
								$HTML .= "<input type=\"hidden\" name=\"urls[$k][$j]\" value=\"".htmlentities($v1)."\" /> <a title=\"{$CFG->delete_hover_caption}\" onclick=\"formDeleteTemp(this)\" class=\"delete\"></a>";
								$HTML .= '</li>';
							}
						}
					}
				}
			}
		}
		
		$name_n = $name.'__'.$i;
		$class = 'class="file_input"';
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id.'__'.$i : $name_n;
		$value = ($this->info) ? $this->info[$name] : $value;
		$custom_image_sizes = ($image_sizes) ? urlencode(serialize($image_sizes)) : false;
		
		if ($this->table) {
			$this->db_fields[$name] = 'file';
		}
		$reproduction_script = 'onchange="fileInputReproduce(this,'.$amount.',0,\''.$CFG->gallery_desc_tooltip.'\')"';
		$reproduction_script1 = 'onblur="fileInputReproduce(this,'.$amount.',1,\''.$CFG->gallery_desc_tooltip.'\')"';
		
		if ($amount > 0) {
			$c = count($this->urls) + count($this->temp_files) + count($files);
			$hidden = ($c >= $amount) ? 'hidden' : false;
			
			$HTML .= '
			<li class="'.$hidden.'">
				<div class="file_input_container">
					<input type="hidden" class="amount" value="'.$amount.'" />
					<input type="text" name="cover" class="input_cover" value="" />
					<input type="button" name="cover_button" class="input_cover_button" value="'.$CFG->file_input_button.'" />
					<input type="file" name="'.$this->name.'['.$name_n.']" value="'.$value.'" id="'.$this->name.'_'.$id.'" '.$reproduction_script.' '.$class.' '.$jscript.' '.$style.' />
				</div>';
			
			if (!$no_url)
				$HTML .= "<label class=\"file_label\">$CFG->alt_url_label</label><input type=\"text\" class=\"file_url\" name=\"urls[$name][$i]\" value=\"$value\" id=\"{$this->name}_{$id}\" $reproduction_script1  />";
			
			if (is_array($permitted_ext)) {
				foreach ($permitted_ext as $ext) {
					$HTML .= "<input type=\"hidden\" name=\"files[$name][exts][]\" value=\"$ext\" />";
				}
			}
			elseif ($permitted_ext) {
				$HTML .= "<input type=\"hidden\" name=\"files[$name][exts][]\" value=\"$permitted_ext\" />";
			}
			if ($max_size)
				$HTML .= "<input type=\"hidden\" name=\"files[$name][max_size]\" value=\"$max_size\" />";
			
			if ($dir)
				$HTML .= "<input type=\"hidden\" name=\"files[$name][dir]\" value=\"$dir\" />";
			
			if ($custom_image_sizes)
				$HTML .= "<input type=\"hidden\" name=\"image_sizes[$name]\" value=\"$custom_image_sizes\" />";
			
			if ($crop_images)
				$HTML .= "<input type=\"hidden\" name=\"crop_images[$name]\" value=\"$crop_images\" />";
			
			if ($encrypt)
				$HTML .= "<input type=\"hidden\" name=\"encrypt_files[$name]\" value=\"1\" />";
			
			if ($allow_descriptions)
				$HTML .= '<div class="file_desc" onclick="fieldZoomIn(\'file_desc__'.$this->table.'_'.$i.'\',\'textarea\',\''.urlencode($CFG->gallery_desc_label).'\')"><img class="hack fdesk_img" src="'.$CFG->gallery_desc_icon.'" title="'.$CFG->gallery_desc_tooltip.'"/><input type="hidden" id="file_desc__'.$this->table.'_'.$i.'" class="fdesk" name="file_descs_new['.$name_n.']" value="" /></div>';

			$HTML .= '<a href="#" title="'.$CFG->delete_hover_caption.'" onclick="multipleRemoveInput(this);" class="delete"></a><div class="clear"></div></li>';
		}
		$HTML .= '<div class="clear"></div></ul>'.$outside_jscript;
		$this->HTML[] = $HTML;
		
		$this->enctype = 'enctype="multipart/form-data"';
	}
	
	function fileMultiple($name, $caption = false, $required = false, $permitted_ext = false, $max_size = false, $dir = false, $image_sizes = false, $amount = 0,$crop_images=false,$encrypt=false) {
		global $CFG;
		
		if ($required) {
			$this->required[$name] = 'file';
			$req_img = $CFG->req_img;
		}
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'fileMultiple');
		}
		
		$caption = ($caption) ? $caption : $name;
		$HTML = $req_img."<div class=\"label_extend\"></div><label class=\"file_input_label\">$caption $method_name</label><ul class=\"file_input_list\">";
		$dir = ($dir) ? $dir : $CFG->default_upload_location;
		$permitted_ext = ($permitted_ext) ? $permitted_ext : $CFG->accepted_file_formats;
		$amount = ($amount > 0) ? $amount : '0';
		$max_size = ($max_size > 0) ? $max_size : '0';
		
		$i = 1;
		if ($this->record_id) {
			if ($files = DB::getFiles($this->table.'_files',$this->record_id,$name)) {
				foreach ($files as $file) {
					$i++;
					$amount--;
					$HTML .= '<li>';
					$HTML .= File::showLinkForResult($file,false,$this->table,$encrypt);
					$HTML .= "<input type=\"hidden\" name=\"attached_file_fields[]\" value=\"$name\" />";
					if (!empty($file['url'])) {
						$HTML .= "<a title=\"{$CFG->delete_hover_caption}\" onclick=\"formDeleteUrl(this,'{$this->table}_files','{$file['id']}')\" class=\"delete\"></a>";
					}
					else {
						$HTML .= "<a title=\"{$CFG->delete_hover_caption}\" onclick=\"formDeleteFile(this,'{$this->table}_files','{$file['id']}','{$file['name']}.{$file['ext']}','{$file['dir']}')\" class=\"delete\"></a>";
					}
					$HTML .= '</li>';
				}
			}
		}
		
		if (!is_array($this->temp_files) && is_array($CFG->temp_files))
			$this->temp_files = $CFG->temp_files;
		
		if (is_array($this->temp_files)) {
			foreach ($this->temp_files as $k => $v) {
				if (stristr($k,$name)) {
					$i++;
					$amount--;
					$HTML .= '<li>';
					$HTML .= File::showLink($v,$CFG->temp_file_location,false,$encrypt);
					$HTML .= "<input type=\"hidden\" name=\"temp_files[$k]\" value=\"$v\" /> <a title=\"{$CFG->delete_hover_caption}\" onclick=\"formDeleteTemp(this,'$v')\" class=\"delete\"></a>";
					$HTML .= '</li>';
				}
			}
		}
		
		$name_n = $name.'__'.$i;
		$class = 'class="file_input"';
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id.'__'.$i : $name_n;
		$value = ($this->info) ? $this->info[$name] : $value;
		$custom_image_sizes = ($image_sizes) ? urlencode(serialize($image_sizes)) : false;
		
		if ($this->table) {
			$this->db_fields[$name] = 'file';
		}
		
		if (!$CFG->pm_editor) {
			$HTML .= '
			<li class="upload_template">
				<span class="file_name"></span><span class="file_size"></span><a title="'.$CFG->delete_hover_caption.'" class="delete"></a>
				<div class="progress_bar"><div class="progress"></div></div>
				<input type="hidden" name="" value="" /> 
			</li>';
		}
		
		$HTML .= '
		<li>
			<span class="file_input_container">
				<input type="text" name="cover" class="input_cover" value=""/>
				<input type="button" name="cover_button" class="input_cover_button" value="'.$CFG->file_input_button.'"/>
				<div class="flash_upload" id="'.$this->name.'_'.$name.'">
					<input type="button" id="button" />
				</div>
			</span>
			<div class="clear"></div>
		</li>';
		
		if (!$CFG->pm_editor) {
		$HTML .= '
			<script type="text/javascript">
			var bar_size = new Array();
			var i = '.$i.';
			$(function(){
				$("#'.$this->name.'_'.$name.'").swfupload({
					upload_url: "ajax.upload.php",
					file_types : "'.(($permitted_ext) ? $permitted_ext : '*.*').'",
					file_types_description : "All Files",
					file_upload_limit : "'.$amount.'",
					file_size_limit : "'.$max_size.'",
					flash_url : "../shared/js/swfupload.swf",
					button_width : 251,
					button_height : 21,
					button_placeholder : $("#'.$this->name.'_'.$name.' #button")[0],
					button_window_mode : SWFUpload.WINDOW_MODE.TRANSPARENT
				})
				.bind("fileQueued", function(event, file){
					var clone = $(".upload_template").clone();
					var file_name = normalizeName(file.name);
					
					$(clone).attr("class","");
					$(clone).attr("id","file_"+file_name);
					$(clone).find(".file_name").html(file.name);
					$(clone).find(".file_size").html(file.size+"b");
					$(clone).find(".button").hide();
					$(clone).css("display","none");
					$(".upload_template").before(clone);
					$(clone).show(200);
					// start the upload since it"s queued
					$(this).swfupload("startUpload");
				})
				.bind("fileQueueError", function(event, file, errorCode, message){
					
				})
				.bind("uploadStart", function(event, file){
					var file_name = normalizeName(file.name);
					$("#file_"+file_name).find(".progress_bar").show(100);
				})
				.bind("uploadProgress", function(event, file, bytesLoaded){
					var file_name = normalizeName(file.name);
					var bar_size = parseInt($(".upload_template").find(".progress_bar").css("width"));
					var percent = (parseInt(bytesLoaded) / parseInt(file.size)) * bar_size;
					$("#file_"+file_name).find(".progress").css("width",percent+"px");
				})
				.bind("uploadSuccess", function(event, file, serverData){
					var file_name = normalizeName(file.name);
					$("#file_"+file_name).find(".progress_bar").hide(100);
					$("#file_"+file_name).find(".file_size").hide(100);
					var response = serverData.split("|");
					if (response[0] == "error") {
						alert("Error: '.$CFG->verify_file_misc_error.'");
						$("#file_"+file_name).remove();
					}
					else {
						i++;
						$("#file_"+file_name).append("<input type=\"hidden\" value=\""+file.name+"\" name=\"temp_files['.$name.'__"+i+"]\">");
						$("#file_"+file_name).find(".button").show(100);
						$("#file_"+file_name).find(".button").click(function() { 
							formDeleteTemp(this,file.name);
						});
					}
					$(this).swfupload("startUpload");
				})
				.bind("uploadError", function(event, file, errorCode, message){
					alert("Error: "+message);
					var file_name = normalizeName(file.name);
					$("#file_"+file_name).remove();
				});
			
			});	
			</script>
			';
		}
		
		$HTML .= '<div class="clear"></div></ul>';
		if ($encrypt) {
			$HTML .= "<input type=\"hidden\" name=\"encrypt_files[$name]\" value=\"1\" />";
		}
		if ($crop_images) {
			$HTML .= "<input type=\"hidden\" name=\"crop_images[$name]\" value=\"$crop_images\" />";
		}
		$this->HTML[] = $HTML;
		$this->enctype = 'enctype="multipart/form-data"';
	}
	
	// you can use both $options_array and $subtable_fields to populate
	// $subtable_fields usage: table.field1, table.field2
	// $level can be 1 to n -> used for tables with p_id
	function selectInput($name,$caption=false,$required=false,$value=false,$options_array=false,$subtable=false,$subtable_fields=false,$subtable_f_id=false,$id=false,$class=false,$jscript=false,$style=false,$f_id_field=false,$default_text=false,$depends_on=false,$function_to_elements=false,$static=false,$j=false,$grid_input=false,$first_is_default=false,$level=false,$use_enum_values=false) {
		global $CFG;

		$class = ($class) ? 'class="' . $class . '"' : false;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$id = str_replace('.','_',$id);
		$caption = ($caption) ? $caption : $name;
		$value = (!empty($this->info[$name]) && !$static) ? $this->info[$name] : $value;
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;

		if (!$static) {
			if ($required) {
				$this->required[$name] = $required;
				$req_img = $CFG->req_img;
			}
			
			if ($this->table) {
				$this->db_fields[$name] = (!$subtable) ? 'vchar' : 'int';
			}
		}
		
		if ($use_enum_values) {
			$enum_table = ($subtable) ? $subtable : $this->table;
			$fields = DB::getTableFields($enum_table);
			$options_raw = $fields[$name]['Type'];
			if (stristr($options_raw,'enum')) {
				$options_split = explode(',',str_ireplace('enum(','',str_replace(')','',str_replace("'",'',str_replace('"','',$options_raw)))));
				if (is_array($options_split)) {
					foreach ($options_split as $option) {
						$options_array[$option] = $option;
					}
				}
			}
		}
		else if ($subtable) {
			$db_output = DB::getSubTable($subtable,$subtable_fields,$subtable_f_id,false,$f_id_field);
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
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'selectInput');
			$jscript = false;
			$outside_jscript = false;
		}
		
		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
		$HTML = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}{$j}\">$caption $method_name</label><select name=\"{$this->name}[$name]{$mult}\" id=\"{$this->name}_{$id}{$j}\" $class ".$jscript." $style>";
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
		$HTML .= '</select>'.$outside_jscript;
		
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
	
	function fauxSelect($name,$caption=false,$required=false,$value=false,$options_array=false,$subtable=false,$subtable_fields=false,$subtable_f_id=false,$id=false,$class=false,$jscript=false,$style=false,$f_id_field=false,$default_text=false,$depends_on=false,$function_to_elements=false,$static=false,$j=false,$grid_input=false,$first_is_default=false,$level=false,$concat_char=false,$is_catsel=false) {
		global $CFG;

		$class = ($class) ? 'class="' . $class . '"' : false;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$id = str_replace('.','_',$id);
		$caption = ($caption) ? $caption : $name;
		$value = (!empty($this->info[$name]) && !$static) ? $this->info[$name] : $value;
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;

		if (!$static) {
			if ($required) {
				$this->required[$name] = $required;
				$req_img = $CFG->req_img;
			}
			
			if ($this->table) {
				$this->db_fields[$name] = (!$subtable) ? 'vchar' : 'int';
			}
		}
		
		if ($subtable) {
			$db_output = DB::getSubTable($subtable,$subtable_fields,$subtable_f_id,$concat_char,$f_id_field);
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
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'selectInput');
			$jscript = false;
			$outside_jscript = false;
		}
		
		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';
		$HTML = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}{$j}\">$caption $method_name</label>";
		$HTML .= '
		<div class="faux_select" onclick="openFauxSelect(this,event)" id="'.$this->name.'_'.$id.$j.'_select" '.$class.' '.$style.'>';
			$HTML .= (!$is_catsel) ? '<input type="hidden" class="faux_value" value="'.$value.'" name="'.$this->name.'['.$name.']'.$mult.'" id="'.$this->name.'_'.$id.$j.'" '.$jscript.' />' : '';
			$HTML .= '
			<div class="paste '.($is_catsel ? 'paste_margin' : '').'">';
		
		if (!$first_is_default && !$is_catsel) {
			$o = '<a class="f_elem" onclick="fauxSelect(this,event,\'\')"> '.$default_text.'</a>';
			$options[] = $o;
			if (empty($value))
				$HTML .= $o;
		}
		if (is_array($options_array)) {
			if ($is_catsel) {
				$CFG->in_faux_select = true;
				$options = self::catSelect($subtable,false,($required ? 1 : 0),false,false,false,false,$subtable_fields,$concat_char);
				$CFG->in_faux_select = false;
			}
			elseif (@in_array('p_id',$subtable_fields) && !$level) {
				$op_html = self::structureOptions($options_array,false,$value,false,1);
				$HTML .= $op_html['html'];
				$options = $op_html['options'];
			}
			else {
				foreach ($options_array as $option => $option_name) {
					$o = '<a class="f_elem" onclick="fauxSelect(this,event,\''.$option.'\')">'.$option_name.'</a>';
					$options[] = $o;
					if ($option == $value)
						$HTML .= $o;
				}
			}
		}
		$HTML .= '
			</div>
			<div class="f_down">'.$CFG->down.'</div>
			<div class="faux_dropdown" id="'.$this->name.'_'.$id.$j.'_dropdown">
				'.(($is_catsel) ? $options : implode('',$options)).'
				<div class="clear"></div>
			</div>
		</div>';
		
		if ($depends_on) {
			$subtable_field = (!empty($f_id_field)) ? $f_id_field : 'f_id';
			$depends_on = str_replace('.','_',$depends_on);
			$HTML .= '
			<script type="text/javascript">
				$("#'.$this->name.'_'.$depends_on.'").change(function() {
					var value = $("#'.$this->name.'_'.$depends_on.'").attr("value");
					ajaxGetPage("ajax.get_options.php?value="+value+"&table='.$subtable.'&field='.$subtable_field.'&return_type=select&mode=faux","'.$this->name.'_'.$id.$j.'_dropdown");
					$("#'.$this->name.'_'.$id.$j.'_select").children(".paste").html(\'<a class="f_elem">'.$default_text.'</a>\');
					$("#'.$this->name.'_'.$id.$j.'_select").children(".faux_value").attr("value","");
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
		if ($this->table && $create_db_field) {
			$type = ($create_db_field == 'datetime') ? 'date' : $create_db_field;
			$this->db_fields[$name] = $type;		
			$value = (empty($this->info[$name]) && ($default_value !== false)) ? $default_value : $this->info[$name];
			$value1 = $value;
			if ($create_db_field == 'date' || $create_db_field == 'datetime') {
				$time = ($create_db_field == 'datetime') ? ' '.$CFG->default_time_format : '';
				if (!$dont_fill_automatically) {
					$value = (!empty($value)) ? $value : date($CFG->default_date_format.$time);
					$value1 = date('Y-m-d H:i:s',strtotime($value));
				}
			}
			$HTML .= "<input type=\"hidden\" name=\"{$this->name}[$name]\" value=\"$value1\" id=\"{$this->name}_{$name}\"/>";
		}

		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'passiveField';
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
			if ($get_field && !$CFG->pm_editor) {
				$HTML .= '
				<script type="text/javascript">
					getPassiveValue(\'ajax.passive.php?table='.$this->table.'&name='.$name.'&caption='.$caption.'&subtable='.$subtable.'&subtable_fields='.urlencode(serialize($subtable_fields)).'&link_url='.$link_url.'&concat_char='.$concat_char.'&f_id_field='.$f_id_field.'&order_by='.$order_by.'&order_asc='.$order_asc.'&link_is_tab='.$link_is_tab.'&limit_is_curdate='.$limit_is_curdate.'&record_id='.$this->record_id.'\',\'passive_'.$this->name.'_'.$name.'\',\''.$this->name.'_'.$get_field.'\');
				</script>
				';
			}
		}
		
		$this->HTML[] = $HTML;
	}
	
	function aggregate($name,$formula,$caption=false,$link_url=false,$run_in_sql=false) {
		global $CFG;

		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'aggregate';
		$record = new Record($this->table,$this->record_id);
		$HTML = '<div id="passive_'.$this->name.'_'.$name.'">'.$record->aggregate($name,$formula,$caption,$link_url,$run_in_sql,1).'</div>';
		$this->HTML[] = $HTML;
	}
	
	function indicator($name,$formula,$caption=false,$subtable=false,$subtable_fields=false,$concat_char=false,$f_id_field=false,$formula_id_field=false,$link_url=false,$link_is_tab=false,$run_in_sql=false) {
		global $CFG;
		
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'indicator';
		$record = new Record($this->table,$this->record_id);
		$HTML = '<div id="passive_'.$this->name.'_'.$name.'">'.$record->indicator($name,$formula,$caption,$subtable,$subtable_fields,$concat_char,$f_id_field,$formula_id_field,$link_url,$link_is_tab,$run_in_sql,1).'</div>';
		
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
			if ($get_field && !$CFG->pm_editor) {
				$HTML .= '
				<script type="text/javascript">
					getPassiveValue(\'ajax.passive.php?action=indicator&table='.$this->table.'&record_id='.$this->record_id.'&name='.$name.'&formula='.urlencode($formula).'&caption='.$caption.'&subtable='.$subtable.'&subtable_fields='.urlencode(serialize($subtable_fields)).'&link_url='.$link_url.'&concat_char='.$concat_char.'&f_id_field='.$f_id_field.'&formula_id_field='.$formula_id_field.'&order_by='.$order_by.'&order_asc='.$order_asc.'&link_url'.$link_url.'&link_is_tab='.$link_is_tab.'&run_in_sql='.$run_in_sql.'&limit_is_curdate='.$limit_is_curdate.'\',\'passive_'.$this->name.'_'.$name.'\',\''.$this->name.'_'.$get_field.'\');
				</script>
				';
			}
		}
		
		$this->HTML[] = $HTML;
	}
	
	// in editor, put subtable in $options_array for auto-suggest textbox
	function autoComplete($name,$caption=false,$required=false,$value=false,$multiple=false,$options_array=false,$subtable=false,$subtable_fields=false,$subtable_f_id=false,$id=false,$class=false,$jscript=false,$style=false,$depends_on=false,$depend_url=false,$f_id_field=false,$list_field_values=false,$default_text=false,$delete_whitespace=false,$static=false,$j=false,$grid_input=false,$is_tokenizer=false,$get_table_fields=false,$first_is_default=false) {
		global $CFG;

		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$id = str_replace('.','_',$id);
		
		if (strstr($id,',')) {
			$id_parts = explode(',',$id);
			$id = $id_parts[0];
		}
		
		$depends_on = str_replace('.','_',$depends_on);
		$caption = ($caption) ? $caption : $name;
		$value = (!empty($this->info[$name]) && !$static) ? $this->info[$name] : $value;
		$onclick = ($default_text) ? "onclick=\"defaultText(this,'$default_text')\"" : '';
		$onblur = ($default_text) ? "onblur=\"defaultText(this,'$default_text')\"" : '';
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;
		$multiple = ($is_tokenizer) ? 1 : $multiple;
		$options_array_is_subtable = DB::tableExists($options_array);
		$subtable = ($options_array_is_subtable) ? $options_array : $subtable;

		if ($delete_whitespace)
			$this->delete_whitespace[] = $name;
		
		if (!$static) {
			if ($required) {
				$this->required[$name] = $required;
				$req_img = $CFG->req_img;
			}
			
			if ($this->table) {
				if (!$multiple || $list_field_values)
					$this->db_fields[$name] = (!$subtable || $list_field_values) ? 'vchar' : 'int';
				elseif ($options_array_is_subtable)
					$this->db_fields[$name] = 'vchar';
				else
					$this->db_fields[$name] = 'text';
			}
		}
		
		if ($subtable || $options_array_is_subtable || $list_field_values) {
			if (!$list_field_values) {
				$db_output = DB::getSubTable($subtable,$subtable_fields,$subtable_f_id,$f_id_field);
				if ($options_array_is_subtable) 
					$db_output = array_combine($db_output,$db_output);
			}
			else {
				$table1 = ($subtable) ? $subtable : $this->table;
				$fields1 = ($subtable) ? $subtable_fields : $name;
				$db_output = DB::getUniqueValues($table1,$fields1);
			}
			
			if ($db_output) {
				if ($options_array && !$options_array_is_subtable) {
					$options_array = array_merge($db_output,$options_array);
				}
				else {
					$options_array = $db_output;
				}
			}
		}
		
		if (!$multiple) {
			$selected_index = (strlen($value) != 0) ? $options_array[$value] : false;
			$selected_index = (!is_numeric($value) && empty($selected_index)) ? $value : $selected_index;
			$selected_index = ($options_array_is_subtable) ? $value : $selected_index;
		}
		else {
			$value1 = (is_array(@unserialize($value))) ? @unserialize($value) : $value;
			if (is_array($value1)) {
				$selected_index = implode(', ',$value1);
				$tokenizer_values = $value1;
				unset($value);
				
				$value2 = array();
				foreach ($value1 as $k => $v) {
					$value2[] = $k.'|'.$v;
				}
				$value = 'array:'.implode('|||',$value2);
			}
			elseif (strlen($value1) > 0) {
				$tokenizer_values = String::unFaux($value1);
			}
		}
		
		$selected_index = (empty($selected_index)) ? $default_text : $selected_index;
		$selected_index = ($multiple && !empty($selected_index)) ? $selected_index.',' : $selected_index;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'autoComplete');
			$jscript = false;
			$outside_jscript = false;
		}
		
		if (!$list_field_values) {
			$mult = ($static) ? '['.$j.']'.(($grid_input) ? '['.$grid_input.']' : '') : '';

			if ($is_tokenizer) {
				$HTML = $req_img."
				<div class=\"label_extend\"></div>
				<label for=\"{$this->name}_{$id}{$j}_dummy\">$caption $method_name</label>
				<div class=\"tokenizer\" id=\"tokenizer\">";

				if ($tokenizer_values) {
					foreach ($tokenizer_values as $t_key => $t_value) {
						if ($t_key)
							$HTML .= '<div class="token"><span>'.$t_value.'</span><input type="hidden" id="d0" value="'.$t_key.'"/><div class="x" onclick="removeThis(this)">x</div></div>';
					}
				}
				
				$HTML .= "
					<input type=\"text\" name=\"$name\" id=\"{$this->name}_{$id}{$j}_dummy\" $class ".$jscript." onkeypress=\"detectBackspace(event,this)\" $onclick $onblur $style/>
				</div>$outside_jscript";
			}
			else {
				$depend = ($depends_on) ? 'depends_on_'.$depends_on : '';
				$first = ($first_is_default) ? 'first_is_default' : '';
				$HTML = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}{$j}_dummy\">$caption $method_name</label><input type=\"text\" name=\"$name\" value=\"$selected_index\" class=\"$class $depend $first\" id=\"{$this->name}_{$id}{$j}_dummy\" $class ".$jscript." $onclick $onblur $style onchange=\"checkEmpty(this,'$multiple')\" />$outside_jscript";
			}
			
			$HTML .= "<input class=\"autocomplete_hidden\" type=\"hidden\" name=\"{$this->name}[$name]{$mult}\" value=\"$value\" id=\"{$this->name}_{$id}{$j}\" $jscript />";
			
			if ($is_tokenizer)
				$HTML .= '<input type="hidden" name="tokenizers[]" value="'.$name.'" />';
		}
		else {
			$HTML = $req_img."<label for=\"{$this->name}_{$id}\">$caption $method_name</label><input type=\"text\" name=\"{$this->name}[$name]\" value=\"$value\" id=\"{$this->name}_{$id}\" $class $jscript $style />";
		}
		
		if ($depends_on) {
			$HTML .= '<span style="display:none;" class="auto_search_params">'.$get_table_fields.'|'.$subtable.'|'.urlencode(serialize($subtable_fields)).'|'.$f_id_field.'|'.$options_array_is_subtable.'</span>';
		}
		
		if (!$CFG->pm_editor) {
			$a_field = (!$list_field_values) ? '#'.$this->name.'_'.$id.$j.'_dummy' : '#'.$this->name.'_'.$id;
			$HTML .= '
			<script type="text/javascript">';
			if (!$depends_on) {
				$HTML .= '
				var '.$id.'_data = [';
				if (is_array($options_array)) {
					foreach ($options_array as $option => $option_name) {
						$HTML .= '{value:"'.$option.'",label:"'.$option_name.'"},';
					}
				}
				$HTML = substr($HTML,0,-1);
				$HTML .= '];';
			}
			
			$HTML .= '
			$().ready(function() {
				$("'.$a_field.'").autocomplete({
					minChars: 1,
					autoFocus: true,
					search:function (event,ui) {
						//console.log(this);
					},
					';
					if (!$depends_on && !$multiple) {
						$HTML .= '
						source:'.$id.'_data,
						';
					}
					else {
						if ($depends_on) {
							$HTML .= '
							source: function(term,response) {
								$.get("ajax.get_fields.php", {f_value: $("#'.$this->name.'_'.$depends_on.'").attr("value"),get_table_fields:"'.$get_table_fields.'",subtable:"'.$subtable.'",subtable_fields:"'.urlencode(serialize($subtable_fields)).'",f_id_field:"'.$f_id_field.'",options_array_is_subtable:"'.$options_array_is_subtable.'",term: term },function(data){
									var values = autoCompleteData(data);
									response(values);
								});
							},
							';
						}
						else {
							$HTML .= '
							source: function(term,response) {
								response($.ui.autocomplete.filter('.$id.'_data,extractLast(term.term)));
							},
							';
						}
					}
					$HTML .= '
					select: function(event, ui) {';
					if (!$multiple) {
						$HTML .= '
						$("#'.$this->name.'_'.$id.'").attr("value",ui.item.value);
						$("#'.$this->name.'_'.$id.'_dummy").attr("value",ui.item.label);
						if ($(".depends_on_'.$id.'").length > 0) {
							$(".depends_on_'.$id.'").each(function (i) {
								var elem = this;
								if ($(elem).hasClass("first_is_default")) {
									var params = $(elem).siblings(".auto_search_params").html().split("|");
									$.get("ajax.get_fields.php", {f_value: $("#'.$this->name.'_'.$id.'").attr("value"),get_table_fields:params[0],subtable:params[1],subtable_fields:params[2],f_id_field:params[3],options_array_is_subtable:params[4]},function(data){
										var values = autoCompleteData(data);
										var depend_id = $(elem).attr("id");
										depend_id = depend_id.replace("_dummy","");
										$("#"+depend_id).attr("value",values[0]["value"]);
										$("#"+depend_id+"_dummy").attr("value",values[0]["label"]);
									});
								}
							});
						}
						return false;
						';
					}
					else {
						$HTML .= '
						var value1 = $("#'.$this->name.'_'.$id.$j.'").attr("value").replace("array:","");
						var value1 = (value1.length > 0) ? "array:" + value1 + "|||" + ui.item.value + "|" + ui.item.label : "array:" + ui.item.value + "|" + ui.item.label;
						var label1 = $("#'.$this->name.'_'.$id.$j.'_dummy").attr("value");
						label2 = label1.split(",");
						label2.pop();
						label1 = label2.join(",");
						label1 = ((label1.length > 0) ? label1 + "," : "") + ui.item.label + ",";
						'.(($is_tokenizer) ? '
							$("'.$a_field.'").before(\'<div class="token"><span>\'+ui.item.label+\'</span><input type="hidden" id="d0" value="\'+value1+\'"/><div class="x"  onclick="removeThis(this)">x</div></div>\');
							$("'.$a_field.'").attr("value","");
							$("#'.$this->name.'_'.$id.$j.'").attr("value",value1);
						' : '
							$("#'.$this->name.'_'.$id.$j.'").attr("value",value1);
							$("#'.$this->name.'_'.$id.$j.'_dummy").attr("value",label1);
						' ).'
						return false;';
					}
					$HTML .= '
					}';
					if ($multiple) {
						$HTML .= '
						,focus: function(event,ui) {
							return false;
						}
						';
					}
				$HTML .= '
				});
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
	
	// input type can be checkbox or text for now
	// if input type, cats must exist already
	function catSelect($subtable,$caption=false,$minimum_required_selections=0,$id=false,$class=false,$jscript=false,$style=false,$subtable_fields=false,$concat_char=false,$input_type=false,$aux_table_name=false,$as_popup=false) {
		global $CFG;
		
		$class = ($class) ? 'class="' . $class . '"' : false;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $subtable;
		$caption = ($caption) ? $caption : $subtable;
		$jscript = self::parseJscript($jscript,$id,false,false);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;
		
		if ($minimum_required_selections > 0) {
			$this->required[$subtable] = 'cat_select|'.$minimum_required_selections;
			$req_img = $CFG->req_img;
		}
		
		if ($this->table && !$input_type) {
			$this->db_fields[$subtable] = 'cat_select';
		}
		elseif ($input_type && $aux_table_name) {
			$this->db_fields[$aux_table_name] = 'cat_input';
		}

		$cats = DB::getCats($subtable);
		if ($this->record_id && !$input_type)
			$cat_selection = DB::getCatSelection($this->table,$subtable,$this->record_id);
		elseif ($this->record_id && $input_type)
			$cat_selection = DB::getCatValues($this->table,$aux_table_name,$this->record_id);
		elseif ($this->info)
			$cat_selection = $this->info['cat_selects'][$subtable];
		else
			$cat_selection = array();
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'catSelect');
			$jscript = false;
			$outside_jscript = false;
		}
		
		$HTML = (!$CFG->in_faux_select) ? $req_img."<div class=\"label_extend\"></div><label class=\"cat_label\">$caption $method_name</label>" : '';
		
		if ($as_popup) {
			$HTML .= '
			<div class="cats_selected" onclick="formCatselPop(this,event)">'.((count($cat_selection) > 0) ? count($cat_selection) : false).' '.((count($cat_selection) > 0) ? $CFG->grid_n_selected : $CFG->grid_click_to_select).'</div>
			<div class="cats_contain">
				<div class="t_shadow"></div>
				<div class="r_shadow"></div>
				<div class="b_shadow"></div>
				<div class="l_shadow"></div>
				<div class="tl1_shadow"></div>
				<div class="tl2_shadow"></div>
				<div class="tr1_shadow"></div>
				<div class="tr2_shadow"></div>
				<div class="bl1_shadow"></div>
				<div class="bl2_shadow"></div>
				<div class="br1_shadow"></div>
				<div class="br2_shadow"></div>
				<div class="contain1">';
		}
		$HTML .= Form::displayCats($cats,$subtable,$class,$id,$jscript,$style,$cat_selection,$subtable_fields,$concat_char,$input_type,$aux_table_name,$as_popup);
		if ($as_popup)
			$HTML .= '</div></div>';
		
		$HTML .= "<input type=\"hidden\" name=\"cat_selects[".(($aux_table_name) ? $aux_table_name : $subtable)."]\" value=\"".($input_type)."\" />$outside_jscript";
		
		if ($CFG->in_faux_select)
			return $HTML;
		else
			$this->HTML[] = $HTML;
		
	}
	
	private function displayCats($cats,&$subtable,$class=false,$id=false,$jscript=false,$style=false,&$cat_selection=false,&$subtable_fields=false,&$concat_char=false,&$input_type=false,&$aux_table_name=false,&$as_popup=false) {
		global $CFG;
		
		if ($cats) {
			$class = ($class) ? $class : 'class="cats_ul"';
			$HTML = "<ul $class>";
			foreach ($cats as $cat) {
				if (!is_array($subtable_fields)) {
					$cat_name = $cat['row']['name'];
				}
				else {
					$cat_name = '';
					$concat_char = (empty($concat_char)) ? ' ' : $concat_char;
					$c = strlen($concat_char);
					foreach ($subtable_fields as $field) {;
						$cat_name .= $cat['row'][$field].$concat_char;
					}
					$cat_name = substr($cat_name,0,-($c));
				}
					
				$cat_id = $cat['row']['id'];
				$checked = (@in_array($cat_id,$cat_selection)) ? 'checked="checked"' : '';
				$jscript = ($CFG->in_faux_select) ? 'onclick="fauxMultiSelect(this,event)"' : '';
				$class1 = ($CFG->in_faux_select) ? 'faux_check' : '';
				
				if (!$input_type)
					$HTML .= "<li onclick=\"formCatSelect(this,event,".(($as_popup) ? '1' : '0').",'".$CFG->grid_n_selected."','".$CFG->grid_click_to_select."')\"><label for=\"{$this->name}_{$id}_{$cat_id}\">$cat_name</label><input type=\"checkbox\" class=\"checkbox_input $class1\" name=\"{$this->name}[cat_selects][$subtable][$cat_id]\" value=\"$cat_id\" id=\"{$this->name}_{$id}_{$cat_id}\" ".$jscript." $style $checked /></li>";
				else
					$HTML .= "<li><label class=\"cat_text_label\" for=\"{$this->name}_{$id}_{$cat_id}\">$cat_name</label><input type=\"text\" class=\"cat_text narrow $class1\" name=\"{$this->name}[cat_selects][$aux_table_name][$cat_id]\" value=\"".$cat_selection[$cat_id]."\" id=\"{$this->name}_{$id}_{$cat_id}\" ".$jscript." $style /></li>";
					
				if (is_array($cat['children'])) {
					$HTML .= Form::displayCats($cat['children'],$subtable,$class,$id,$jscript,$style,$cat_selection,$subtable_fields,$concat_char,$input_type,$aux_table_name,$as_popup);
				}
			}
			$HTML .= '</ul>';
			return $HTML;
		}
	}
	
	// if $only_time, use $link_to to get a date for the time from another field
	// regular interval is to be used with Calendar class
	function dateWidget($name,$caption=false,$required=false,$time=false,$ampm=true,$req_start=false,$req_end=false,$value=false,$link_to=false,$id=false,$class=false,$format=false,$jscript=false,$style=false,$is_filter_range_end=false,$document_ready=false,$only_time=false,$regular_interval=false,$only_interval=false) {
		global $CFG;
		
		$name = (@in_array($name,$this->date_widgets)) ? $name.'bbb' : $name;
		$this->date_widgets[] = $name;
		$format = (!$format) ?  $CFG->default_date_format : $format;
		$ampm_class = ($ampm || $regular_interval || $time) ? 'ampm' : false;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$id = str_replace('.','_',$id);
		$link_to = str_replace('.','_',$link_to);
		$caption = ($caption) ? $caption : $name;
		$value = ($this->info) ? $this->info[$name] : $value;
		$interval_value = ($this->info) ? $this->info[$name.'_interval'] : (($only_interval) ? $value : false);
		$hour = date((($ampm) ? 'h' : 'H'),strtotime($value));
		$minute = date('i',strtotime($value));
		$a = date('a',strtotime($value));
		$value_in = ($value && $value != '0000-00-00 00:00:00' && !strstr($value,'1969-12-31')) ? date('Y,m -1,d',strtotime($value)) : '';
		$value = ($value && $value != '0000-00-00 00:00:00' && !strstr($value,'1969-12-31')) ? date($format,strtotime($value)) : '';
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;
		
		$req_start = ($req_start) ? date('Y,m -1,d', strtotime($req_start)) : false;
		$req_end = ($req_end) ? date('Y,m -1,d', strtotime($req_end)) : false;
		$hours = ($ampm) ? range(1,12) : range (00,23);
		$minutes = range(00,59);
		
		if ($required) {
			$this->required[$name] = $required;
			$req_img = $CFG->req_img;
		}
		
		if ($this->table && !$only_interval) {
			$this->db_fields[$name] = 'date';
		}
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'dateWidget');
			$jscript = false;
			$outside_jscript = false;
		}
		
		$identify_end = ($is_filter_range_end) ? '<date_filter_end>' : false;
		$date_format = str_replace('Y','yy',str_replace('n','m',str_replace('m','mm',str_replace('j','d',str_replace('d','dd',$format)))));
		
		$HTML = $req_img.$identify_end."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}\">$caption $method_name</label>";
		if (!$only_time && !$only_interval) {
			$HTML .= "<input type=\"text\" name=\"{$this->name}[$name]\" value=\"$value\" id=\"{$this->name}_{$id}\" class=\"$class $ampm_class\" ".$jscript." $style />$outside_jscript";
			if (!$CFG->pm_editor) {
				$HTML .= '
				<script type="text/javascript">';
				
			if($document_ready)	{
			$HTML .='$(document).ready(
					function(){';
					}
					
			$HTML.='$("#'.$this->name.'_'.$id.'").datepicker({ 
						showAnim: "fadeIn",
					    showOn: "both", 
					    buttonImage: "'.$CFG->date_picker_icon.'",
					    showButtonPanel: true,
					    defaultDate: new Date('.$value_in.'),
					    '.(($req_start) ? "minDate: new Date($req_start)," : '').'
					    '.(($req_end) ? "maxDate: new Date($req_end)," : '').'
					    '.(($link_to) ? "beforeShow: function() { 
												var min = $('#{$this->name}_{$link_to}').datepicker('getDate');
		    									return {minDate : min};
		  								}," : '').' 
					    prevText: "<",
					    nextText: ">",
					    closeText: "x",
					    buttonImageOnly: true,
					    dateFormat: "'.$date_format.'"
					});';
	
			 if($document_ready){		
				$HTML.='});';
					}
				$HTML.='</script>
				';
			}
		}
		else {
			if (!$only_interval) {
				$value1 = ($link_to) ? $this->info[$link_to] : time();
				$HTML .= "<input type=\"hidden\" name=\"{$this->name}[$name]\" value=\"".date($format,$value1)."\" id=\"{$this->name}_{$id}\" />";
			}
		}
		
		if ($time || $only_time) {
			$only_class = ($only_time) ? 'only' : '';
			$HTML .= " <select name=\"{$this->name}[{$name}_h]\" id=\"{$this->name}_{$id}_h\" class=\"time_hour $only_class\" $jscript $style>";
				foreach ($hours as $h) {
					$h = sprintf("%02d",$h);
					$HTML .= "<option value=\"$h\" ".(($h == $hour) ? 'selected = "selected"' : '').">$h</option>";
				}
			$HTML .= "</select> : ";
			$HTML .= "<select name=\"{$this->name}[{$name}_m]\" id=\"{$this->name}_{$id}_m\" class=\"time_minute\" $jscript $style>";
				foreach ($minutes as $m) {
					$m = sprintf("%02d",$m);
					$HTML .= "<option value=\"$m\" ".(($m == $minute) ? 'selected = "selected"' : '').">$m</option>";
				}
			$HTML .= "</select>";
			if ($ampm) {
				$HTML .= "
						<select name=\"{$this->name}[{$name}_ampm]\" id=\"{$this->name}_{$id}_ampm\" class=\"time_ampm\" $jscript $style>
							<option value=\"am\" ".(($a == 'am') ? 'selected = "selected"' : '').">AM</option>
							<option value=\"pm\" ".(($a == 'pm') ? 'selected = "selected"' : '').">PM</option>
						</select>";
			}
		}
		
		if ($regular_interval) {
			$only_class = ($only_interval) ? 'only' : '';
			$HTML .= " 
			<select name=\"{$this->name}[{$name}_interval]\" id=\"{$this->name}_{$id}_interval\" class=\"interval $only_class\">
				<option value=\"\" ".((!$interval_value) ? 'selected = "selected"' : '').">{$CFG->cal_dont_repeat}</option>
				<option value=\"day\" ".(($interval_value == 'day') ? 'selected = "selected"' : '').">{$CFG->cal_every} {$CFG->cal_day}</option>
				<option value=\"sun\" ".(($interval_value == 'sun') ? 'selected = "selected"' : '').">{$CFG->cal_every} {$CFG->cal_sun}</option>
				<option value=\"mon\" ".(($interval_value == 'mon') ? 'selected = "selected"' : '').">{$CFG->cal_every} {$CFG->cal_mon}</option>
				<option value=\"tue\" ".(($interval_value == 'tue') ? 'selected = "selected"' : '').">{$CFG->cal_every} {$CFG->cal_tue}</option>
				<option value=\"wed\" ".(($interval_value == 'wed') ? 'selected = "selected"' : '').">{$CFG->cal_every} {$CFG->cal_wed}</option>
				<option value=\"thu\" ".(($interval_value == 'thu') ? 'selected = "selected"' : '').">{$CFG->cal_every} {$CFG->cal_thur}</option>
				<option value=\"fri\" ".(($interval_value == 'fri') ? 'selected = "selected"' : '').">{$CFG->cal_every} {$CFG->cal_fri}</option>
				<option value=\"sat\" ".(($interval_value == 'sat') ? 'selected = "selected"' : '').">{$CFG->cal_every} {$CFG->cal_sat}</option>
			</select>
			<input type=\"hidden\" name=\"intervalfields[$name]\" value=\"$link_to\" />";
			
			$this->db_fields[$name.'_interval'] = 'vchar';
		}
		
		$HTML .= "<input type=\"hidden\" name=\"datefields[$name]\" value=\"$is_filter_range_end\" />";
		
		if ($only_time && $link_to)
			$HTML .= "<input type=\"hidden\" name=\"timefields[$name]\" value=\"$link_to\" />";
		
		$this->HTML[] = $HTML;
	}
	
	function gallery($field_name=false,$size=false,$thumbnails=0,$class=false,$limit=0,$encrypted=false,$alt_field=false) {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'gallery');
		}
		
		$this->HTML[] = $method_name.Gallery::multiple($this->table.'_files',$this->record_id,$field_name,$size,$thumbnails,$class,$limit,false,false,false,false,false,false,false,false,false,false,$encrypted,$alt_field);
	}
	
	function submitButton($name, $value = false, $id = false, $class = false, $jscript = false, $style = false) {
		global $CFG;
		
		$style = ($style) ? 'style="' . $style . '"' : false;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'submitButton');
		}
		
		
		$this->HTML[] = "
		<button>
		<div onmouseover=\"Ops.buttonOver(this)\" onmouseout=\"Ops.buttonOut(this)\" class=\"button submit primary $class\" $style>
			<input type=\"submit\" name=\"$name\" value=\"$value\" $jscript />
			<div class=\"l\"></div>
			<div class=\"c\"></div>
			<div class=\"r\"></div>
		</div>$method_name";
	}
	
	function resetButton($value = false, $id = false, $class = false, $jscript = false, $style = false) {
		global $CFG;
		
		$style = ($style) ? 'style="' . $style . '"' : false;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'resetButton');
		}
		
		$this->HTML[] = "
		<button>
		<div onmouseover=\"Ops.buttonOver(this)\" onclick=\"formReset(this)\" onmouseout=\"Ops.buttonOut(this)\" class=\"button $class\" $style>
			<input type=\"button\" value=\"$value\" $jscript />
			<div class=\"l\"></div>
			<div class=\"c\"></div>
			<div class=\"r\"></div>
		</div>$method_name";
	}
	
	function cancelButton($value=false,$id=false,$class=false,$style=false,$static=false) {
		global $CFG;
		
		$style = ($style) ? 'style="' . $style . '"' : false;
		$onclick = ($CFG->pm_cancel_back) ? "onclick=\"ajaxGetPage('".$_SESSION['last_query']."','main')\";return false;" : "onclick=\"formCancel('{$CFG->url}&is_tab={$CFG->is_tab}',this)\"";
		$class = ($static) ? 'primary' : '';
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'cancelButton');
		}
		
		$HTML = "
		<button>
		<div onmouseover=\"Ops.buttonOver(this)\" onclick=\"formCancel('{$CFG->url}&is_tab={$CFG->is_tab}',this)\" onmouseout=\"Ops.buttonOut(this)\" class=\"button $class\" $style>
			<input type=\"button\" value=\"$value\" />
			<div class=\"l\"></div>
			<div class=\"c\"></div>
			<div class=\"r\"></div>
		</div>".$method_name;
		if (!$static) {
			$this->HTML[] = $HTML;
		}
		else {
			return $HTML;
		}
	}
	
	function printButton($value=false,$static=false) {
		global $CFG;
		
		$style = ($style) ? 'style="' . $style . '"' : false;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'printButton');
		}
		
		$HTML = '
		<button>
		<a onmouseover="Ops.buttonOver(this)" onmouseout="Ops.buttonOut(this)" class="button '.$class.'" '.$style.' target="_blank" href="index.php?current_url='.$CFG->url.'&bypass=1&action=record&print=1&is_tab='.$CFG->is_tab.'&id='.$this->record_id.'">
			'.$value.'
			<div class="l"></div>
			<div class="c"></div>
			<div class="r"></div>
		</div></a>'.$method_name;
		if (!$static) {
			$this->HTML[] = $HTML;
		}
		else {
			return $HTML;
		}
	}
	
	function button($url=false,$value=false,$variables=false,$target_elem_id=false,$id=false,$class = false, $jscript = false, $style = false,$disable_if_no_record_id=false,$disable_if_cant_edit=false,$static=false,$update_variable_values=false,$bypass_create_record=false) {
		global $CFG;
		
		$variables1 = String::parseVariables($variables,$this->info,$this->record_id,$url,$update_variable_values);
		$variables1['current_url'] = $url;
		
		$permission_level = ($variables1['action'] == 'form') ? 2 : 1;
		$disabled = (User::permission(0,0,$url,false,$variables1['is_tab']) < $permission_level);
		$disabled = (!($this->record_id > 0) && $disable_if_no_record_id) ? true : $disabled;
		$disabled_text = ($disabled) ? 'disabled="disabled"' : '';
		$target_elem_id = (!$target_elem_id) ? 'content' : $target_elem_id;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = strtolower(str_replace('<','',str_replace('>','',str_replace('.','',str_replace(' ','_',$value)))));
		$jscript = self::parseJscript($jscript,$id,$j,$grid_input);
		$outside_jscript = (strstr($jscript,'outside|')) ? '<script type="text/javascript">'.str_replace('outside|','',$jscript).'</script>' : false;
		$jscript = strstr($jscript,'outside|') ? false : $jscript;
		
		if ($update_variable_values) {
			//$variables1['form_name'] = $url;
			//$variables1['form_table'] = $url;
		}
		else {
			$variables1['bypass_save'] = 1;
		}
		
		if ($bypass_create_record) {
			$variables1['bypass_create_record'] = 1;
		}
		
		if ($url) {
			$onclick = 'onclick="ajaxGetPage(\'index.php'.((is_array($variables1)) ? '?'.http_build_query($variables1) : '').'\',\''.$target_elem_id.'\');return false;"';
		}

		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'button');
		}
		
		$HTML = "
		<button>
		<div onmouseover=\"Ops.buttonOver(this)\" onmouseout=\"Ops.buttonOut(this)\" class=\"button $class\" $style>
			<input type=\"button\" value=\"$value\" id=\"{$this->name}_$id\" $onclick $jscript $disabled_text />".$outside_jscript."
			<div class=\"l\"></div>
			<div class=\"c\"></div>
			<div class=\"r\"></div>
		</div>".$method_name;
		if (!$static) {
			$this->HTML[] = $HTML;
		}
		else {
			return $HTML;
		}
	}
	
	function link($url=false,$caption=false,$variables=false,$target_elem_id=false,$class=false,$disable_if_no_record_id=false,$disable_if_cant_edit=false) {
		global $CFG;
		
		$variables1 = String::parseVariables($variables,$this->info,$this->record_id,$url);
		$variables1['bypass_save'] = 1;
		$permission_level = ($variables1['action'] == 'form') ? 2 : 1;
		$disabled = (User::permission(0,0,$url,false,$variables1['is_tab']) <= $permission_level);
		$disabled = (!($this->record_id > 0) && $disable_if_no_record_id) ? true : $disabled;
		$target_elem_id = (!$target_elem_id) ? 'content' : $target_elem_id;
		$class = ($class) ? $class : 'nav_link';
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'link');
		}
		
		
		$this->HTML[] = Link::url($url,$caption,false,$variables1,false,$target_elem_id,$class,false,false,false,$disabled).$method_name;
	}
	
	function heading($caption) {
		global $CFG;
		
		preg_match_all("/\[([A-Za-z0-9-_]+)\]/",$caption,$variables);
		$variables[1] = array_combine($variables[1],$variables[1]);
		$variables1 = String::parseVariables($variables[1],$this->info,$this->record_id);
		if (is_array($variables[1])) {
			foreach ($variables[1] as $key) {
				$caption = str_ireplace("[{$key}]",$variables1[$key],$caption);
			}
		}
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'heading');
		}
		
		$this->HTML[] = "<h2>$caption</h2>".$method_name;
	}
	
	function permissionEditor($table,$group_id,$echo_on = false) {
		global $CFG;
		
		if (!$echo_on) {
			$HTML = "<pe_editor|||$table|||$group_id";
			
			if ($CFG->pm_editor) {
				$method_id = ($CFG->o_method_id) ? $CFG->o_method_id : $CFG->method_id;
				$this->HTML[$method_id] = $HTML;
				$this->override_methods[$method_id] = $CFG->o_method_name;
			}
			else
				$this->HTML[] = $HTML;
		}
		else {
			$pe = new PermissionEditor($table,$group_id);
		}
	}
	
	function colorPicker($name, $caption = false, $required = false, $value = false, $id = false, $class = false, $jscript = false,$color_name=false,$static=false,$j=false,$document_ready=false) {
		global $CFG;
		
		$class = ($class) ? 'class="' . $class . '"' : 'class="color_text"';
		$id = ($id) ? $id : $name;
		$caption = ($caption) ? $caption : $name;
		$value = ($this->info && !$static) ? $this->info[$name] : $value;

		if ($required) {
			$this->required[$name] = $required;
			$req_img = $CFG->req_img;
		}
		
		if (!$static) {
			if ($this->table) {
				$this->db_fields[$name] = 'vchar';
			}
		}
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'colorPicker');
		}
		
		$mult = ($static) ? '['.$j.']'.(($grid_input) ? '[]' : '') : '';
		
		if (!$color_name) {
			$HTML = $req_img."<div class=\"label_extend\"></div><label for=\"{$this->name}_{$id}{$j}\">$caption $method_name</label><input type=\"text\" name=\"{$this->name}[$name]{$mult}\" value=\"$value\" id=\"{$this->name}_{$id}{$j}\" $class $jscript /><div class=\"color_box\" style=\"background-color:{$value};\" id=\"{$this->name}_{$id}{$j}_color\"></div>";
		}
		else { 
			$value1 = (!$static) ? unserialize($value) : $value;
			$onblur = ($CFG->pmt_form_colorpicker_nametext) ? "onblur=\"defaultText(this,'$CFG->pmt_form_colorpicker_nametext')\"" : '';
			$onclick = ($CFG->pmt_form_colorpicker_nametext) ? "onclick=\"defaultText(this,'$CFG->pmt_form_colorpicker_nametext')\"" : '';
			$HTML = $req_img."<label for=\"{$this->name}_{$id}{$j}\">$caption</label><input type=\"text\" name=\"{$this->name}[$name]{$mult}[color]\" value=\"{$value1['color']}\" id=\"{$this->name}_{$id}{$j}\" $class $jscript /><div class=\"color_box\" style=\"background-color:{$value1['color']};\" id=\"{$this->name}_{$id}{$j}_color\"></div><input class=\"color_name\" name=\"{$this->name}[$name]{$mult}[color_name]\" value=\"".((empty($value1['color_name'])) ? $CFG->pmt_form_colorpicker_nametext : $value1['color_name'])."\" $onblur $onclick />";	
			
			if (!$static)
				$this->color_fields[] = $name;	
		}
		
		if (!$CFG->pm_editor) {
			$HTML .= '
			<script type="text/javascript">';
			if($document_ready)	{
				$HTML .= '
				$(document).ready(
					function(){';
			}

			$HTML .= '
					$("#'.$this->name.'_'.$id.$j.'").ColorPicker({
						onChange: function (hsb, hex, rgb) {
							$(\'#'.$this->name.'_'.$id.$j.'_color\').css(\'backgroundColor\', \'#\' + hex);
							$(\'#'.$this->name.'_'.$id.$j.'\').attr("value",\'#\' + hex);
						}
					});
			';
			if($document_ready)	{
				$HTML .= '
				
					}
				);';
			}
			$HTML .= '
			</script>
			';
		}
		
		if (!$static)
			$this->HTML[] = $HTML;
		else
			return $HTML;
	}
	
	function startArea($legend,$class=false,$height=false) {
		global $CFG;
		
		$this->has_areas = true;
		$this->current_area = $CFG->method_id;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'startArea');
		}
		
		$matches = String::getSubstring($legend,'[',']');
		if (is_array($matches)) {
			foreach ($matches as $match) {
				if(@array_key_exists($match,$this->info)) {
					$legend = str_ireplace('['.$match.']',$this->info[$match],$legend);
				}
			}
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
			$method_name = self::peLabel($CFG->method_id,'endArea');
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
	
	function startRestricted($groups=false,$only_admin=false,$users=false,$user_id_equals_field=false,$group_id_equals_field=false,$condition=false,$exclude_groups=false,$exclude_admin=false,$exclude_users=false) {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'startRestricted');
		}
		
		$is_admin = (User::$info['is_admin'] == 'Y');
		
		if ($CFG->pm_editor) {
			$this->HTML[] = "
			$legend [start_restricted] $method_name";
		}
		else {
			if ($only_admin) {
				if ($condition) {
					$condition = String::doFormulaReplacements($condition,$this->info,1,1);
					$restricted = ($restricted) ? $restricted : @eval("if ({$condition}) { return 0;} else { return 1;}");
				}
			}
			else {
				if (is_array($users))
					$restricted = (!array_key_exists(User::$info['id'],$users) && !$is_admin);
					
				if (is_array($groups))
					$restricted =  (!$restricted) ? (!array_key_exists(User::$info['f_id'],$groups) && !$is_admin) : $restricted;
					
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
						$row = DB::getFields($last_table,$this->info[$first_field],array($last_field),$user_id_equals_field,false,false,false,false,1);
						$restricted = (!$restricted) ? $restricted : (User::$info['id'] != $row[$last_field]);
					}
					else {
						$restricted = (!$restricted) ? $restricted : (User::$info['id'] != $this->info[$user_id_equals_field]); 
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
						$row = DB::getFields($last_table,$this->info[$first_field],array($last_field),$group_id_equals_field,false,false,false,false,1);
						$restricted = (!$restricted) ? $restricted : (User::$info['f_id'] != $row[$last_field]); 
					}
					else {
						$restricted = (!$restricted) ? $restricted : (User::$info['f_id'] != $this->info[$group_id_equals_field]); 
					}
				}
				
				if ($condition) {
					$condition = String::doFormulaReplacements($condition,$this->info,1,1);
					$restricted = ($restricted) ? $restricted : eval("if ({$condition}) { return 0;} else { return 1;}");
				}
			}
			
			if ($restricted)
				$this->HTML[] = "<restricted>";
		}
	}
	
	function endRestricted() {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'endRestricted');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = '[end_restricted]'.$method_name;
		}
		else {
			$this->HTML[] = '</restricted>';
		}
	}
	
	function startFieldset($legend) {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'startFieldset');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = "
			$legend [start_fieldset] $method_name";
		}
		else {
			$this->HTML[] = "
			<fieldset class=\"fieldset\">  
			<legend>$legend</legend>";
			$this->fieldset = true;
		}
	}
	
	function endFieldset() {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'endFieldset');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = '[end_fieldset]'.$method_name;
		}
		else {
			$this->HTML[] = '</fieldset>';
			$this->fieldset = false;
		}
	}
	
	function captcha($caption) {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_id = ($CFG->o_method_id) ? $CFG->o_method_id : $CFG->method_id;
			$this->HTML[$method_id] = '<captcha>';
		}
		else
			$this->HTML[] = '<captcha>';
			
		$this->c_caption = $caption;
	}
	
	function startGroup($legend=false,$class=false) {
		global $CFG;
		
		$class = ($class) ? $class : 'group_default';
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'startGroup');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = $legend.' [start_group]'.$method_name;
		}
		else {
			$legend = ($legend) ? "<label>$legend</label><div class=\"label_extend\"></div>" : '';
			$this->HTML[] = "<group>$legend<div class=\"group $class\"><ul>";
		}
		/*
		if ($CFG->pm_editor) {
			if (!$CFG->o_method_suppress) {
				$method_id = ($CFG->o_method_id) ? $CFG->o_method_id : $CFG->method_id;
				$this->HTML[$method_id] = '<group>';
				$this->override_methods[$method_id] = $CFG->o_method_name;
			}
			else {
				$CFG->o_method_suppress = false;
			}
		}
		else
			$this->HTML[] = '<group>';
		*/
	}
	
	function endGroup() {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'endGroup');
		}
		
		if ($CFG->pm_editor) {
			$this->HTML[] = '[end_group]'.$method_name;
		}
		else {
			$this->HTML[] = '</group><div class="clear"></div></ul></div><div class="clear"></div></li>';
		}
		/*
		if ($CFG->pm_editor) {
			if (!$CFG->o_method_suppress) {
				$method_id = ($CFG->o_method_id) ? $CFG->o_method_id : $CFG->method_id;
				$this->HTML[$method_id] = '</group>';
				$this->override_methods[$method_id] = $CFG->o_method_name;
			}
			else {
				$CFG->o_method_suppress = false;
			}
		}
		else
			$this->HTML[] = '</group>';
*/
	}
	
	function startTab($caption,$url=false,$is_tab=false,$action=false,$inset_id_field=false,$static=false,$area_i=false) {
		global $CFG;
		
		$area_i = ($area_i !== false) ? $area_i : $this->area_i;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'startTab');
		}

		if ($CFG->pm_editor) {
			$HTML = "
			$caption [start_tab] $method_name";
		}
		else {
			$this->tabs[$this->current_area][$CFG->method_id] = $caption;
			$visible = ($this->area_i == 0) ? 'visible' : '';
			$HTML  = '<tab><div class="tab_area_container '.$visible.'" id="tab_area_'.$CFG->method_id.'">';
			if ($url) {
				$tab = $CFG->is_tab;
				$CFG->inset_id = $this->record_id;
				$CFG->inset_id_field = $inset_id_field;
				$CFG->inset_is_tab = $is_tab;
				$CFG->inset_url = $url;
				$CFG->is_form_inset = 1;
				ob_start();
				$control = new Control($url,false,$is_tab);
				$control_html = ob_get_contents();
				ob_end_clean();
				$HTML .= $control_html;
				$HTML .= '</div>';
				$CFG->is_tab = $tab;
			}
		}
		$area_i++;
		$this->area_i = $area_i;
		
		if (!$static) {
			$this->HTML[] = $HTML;
		}
		else {
			return $HTML;
		}
	}
	
	function endTab($static=false) {
		global $CFG;

		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'endTab');
		}
		
		if ($CFG->pm_editor) {
			$HTML = "
			[end_tab] $method_name";
		}
		else {
			$HTML  = '</tab></div>';
		}
		
		if (!$static) {
			$this->HTML[] = $HTML;
		}
		else {
			return $HTML;
		}
	}
	
	function HTML($HTML) {
		global $CFG;
		
		if ($CFG->pm_editor && !strstr($this->name,'form_filters')) {
			if (!$CFG->o_method_suppress) {
				$method_id = ($CFG->o_method_id) ? $CFG->o_method_id : $CFG->method_id;
				$this->HTML[$method_id] = '<htmlfield>'.$HTML;
				$this->override_methods[$method_id] = $CFG->o_method_name;
			}
			else {
				$CFG->o_method_suppress = false;
			}
		}
		else
			$this->HTML[] = '<htmlfield>'.$HTML;
	}
	
	function multiple($name,$input_type=false, $caption = false, $minimum_required_selections = 0, $id = false, $class = false,$color_names=false,$is_manual_array=false,$default_text=false) {
		global $CFG;
		
		$class = ($class) ? 'class="' . $class . '"' : false;
		$style = ($style) ? 'style="' . $style . '"' : false;
		$id = ($id) ? $id : $name;
		$caption = ($caption) ? $caption : $name;
		$value = ($this->info) ? unserialize($this->info[$name]) : unserialize($value);
		$c_val = count($value) - 1;
		
		if (is_array($value)) {
			foreach ($value as $i => $val) {
				if (empty($val) && !array_key_exists('color',$val)) {
					unset($value[$i]);	
				}
				elseif (array_key_exists('color',$val)) {
					if (empty($val['color'])) {
						unset($value[$i]);	
					}
				}
			}
		}
		$value[] = '';
			
		$this->color_fields[] = $name;
		
		if ($this->table) {
			$this->db_fields[$name] = 'text';
		}
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'multiple');
		}
		
		$HTML = '<div class="multiple_input"><div class="caption">'.$caption.$method_name.'</div><ul>';
		if (is_array($value)) {
			$c = count($value);
			$i = 1;
			foreach ($value as $row) {
				$visible = ($i == $c) ? 'style="display:none;"' : '';
				
				if ($input_type == 'textInput' || !$input_type) {
					$HTML .= '<li '.$visible.'>'.call_user_func_array(array('Form', 'textInput'),array($name,'&nbsp;',false,$row,false,false,false,false,false,$is_manual_array,false,false,$default_text,false,true,$i)).' <a title="'.$CFG->delete_hover_caption.'" onclick="multipleRemoveInput(this)" class="delete"></a></li>';
				}
				elseif ($input_type == 'colorPicker') {
					$HTML .= '<li '.$visible.'>'.call_user_func_array(array('Form', 'colorPicker'),array($name,'&nbsp;',false,$row,false,false,false,$color_names,true,$i,1)).' <a title="'.$CFG->delete_hover_caption.'" onclick="multipleRemoveInput(this)" class="delete"></a></li>';
				}
				$i++;
			}
		}
		
		if ($input_type == 'colorPicker') {
			$args = ',1,\''.$this->name.'_'.$name.'\'';
		}
		
		$HTML .= '<li class="multiple_add"><label></label><a href="#" onclick="multipleNewInput(this'.$args.');return false;"><div class="add_new"></div>'.$CFG->add_new_caption.'</a></li>';
		$HTML .= '</ul></div>';
		$this->HTML[] = $HTML;
	}
	
	//inputs is array('method'=>array(args))
	function grid($name,$inputs_array=false, $caption = false, $minimum_required_selections = 0,$show_as_grid=false) {
		global $CFG;

		$caption = ($caption) ? $caption : $name;
		
		if ($this->table) {
			$this->db_fields[$name] = 'grid||'.urlencode(serialize($inputs_array));
		}
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'grid');
		}

		$ref = new ReflectionClass('Form');
		if (is_array($inputs_array)) {
			foreach ($inputs_array as $method=>$args) {
				$method_parts = explode('|',$method);
				$method1 = $method_parts[0];
				
				$subtable_fields[] = $args['name'];
				$params = $ref->getMethod($method1)->getParameters();
				
				if ($args['show_total'])
					$total = true;
				
				if (is_array($params)) {
					$i = 0;
					foreach ($params as $param) {
						$param_name = $param->getName();
						if ($param_name == 'value')
							$i_methods[$method]['value'] = $i;
						elseif ($param_name == 'static')
							$i_methods[$method]['static'] = $i;
						elseif ($param_name == 'j')
							$i_methods[$method]['j'] = $i;
						elseif ($param_name == 'grid_input')
							$i_methods[$method]['grid_input'] = $i;
						elseif ($param_name == 'checked')
							$i_methods[$method]['checked'] = $i;
						elseif ($param_name == 'jscript')
							$i_methods[$method]['jscript'] = $i;
							
						$i++;
					}
				}
			}
		}
		$value = (!$this->record_id) ? $this->info[$name] : DB::getGridValues($this->table.'_grid_'.$name,$subtable_fields,$this->record_id);
		if (!is_array($value)) {
			$value[] = '';
			$value[] = '';
		}
		else {
			foreach ($value as $id => $row) {
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
					unset($value[$id]);
				}
			}
		}
		
		if (!$show_as_grid && is_array($value))
			$value[] = '';

		$HTML = '
		<input type="hidden" name="grid_inputs[]" value="'.$name.'" id="grid_table" />
		<div class="'.(($show_as_grid) ? 'record_grid' : 'multiple_input').'"><div class="caption">'.$caption.$method_name.'</div>';
		
		if ($show_as_grid) {
			$HTML .= '<table>';
			$HTML .= '<tr>';
			if ($inputs_array) {
				foreach ($inputs_array as $args) {
					$HTML .= '<th>'.$args['caption'].'</th>';
				}
			}
			$HTML .= '<th></th></tr>';
		}
		else {
			$HTML .= '<ul>';
		}
		
		if (is_array($value)) {
			$c = count($value);
			$i = 1;
			foreach ($value as $row) {
				$visible = ($i == $c) ? 'style="display:none;"' : '';
				
				if ($show_as_grid) {
					$HTML .= '<tr '.$visible.'>';
				}
				else {
					$HTML .= '<li '.$visible.' class="m_items">';	
				}

				if (is_array($inputs_array)) {
					foreach ($inputs_array as $method=>$args) {
						$method_parts = explode('|',$method);
						$method1 = $method_parts[0];
						
						$CFG->method_id = $args['pm_method_id'];
						unset($args['pm_method_id']);
						unset($args['show_total']);
						$args1 = $args;
						$args = array_values($args);

						$i_value = $i_methods[$method]['value'];
						$i_checked = $i_methods[$method]['checked'];
						$i_static = $i_methods[$method]['static'];
						$i_j = $i_methods[$method]['j'];
						$i_grid_input = $i_methods[$method]['grid_input'];
						$i_jscript = $i_methods[$method]['jscript'];

						$input_name = $args[0];
						$args[$i_static] = 1;
						$args[$i_j] = $i;
						$args[$i_grid_input] = $args[0];
						$args[0] = $name;

						if ($method1 == 'textInput') {
							$args[13] = '';
							ksort($args);
						}

						if ($args1['jscript']) 
							$args[$i_jscript] = $args1['jscript'];
						
						if ($args1['show_total'])
							$totals[$input_name][] = $row[$input_name];
						
						if ($method1 == 'checkBox')
							$args[$i_checked] = $row[$input_name];
						else
							$args[$i_value] = $row[$input_name];

						$HTML .= (($show_as_grid) ? '<td>' : '<div class="col">').call_user_func_array(array('Form', $method1),$args).(($show_as_grid) ? '</td>' : '</div>');
					}
				}
				$HTML .= (($show_as_grid) ? '<td>' : '').'<a title="'.$CFG->delete_hover_caption.'" onclick="multipleRemoveInput(this'.(($show_as_grid) ? ',1' : '').')" class="delete"></a>'.(($show_as_grid) ? '</td>' : '<div class="clear"></div>');
				
				if ($show_as_grid) {
					$HTML .= '</tr>';
				}
				else {
					$HTML .= '</li>';	
				}
				
				$i++;
			}
		}
		
		if ($show_as_grid) {
			if ($total) {
				$HTML .= '<tr>';
				if ($inputs_array) {
					$table_fields = DB::getTableFields($this->table.'_grid_'.$name);
					foreach ($inputs_array as $args) {
						$input_name = $args['name'];
						if ($args['show_total']) {
							$total = (is_array($totals[$input_name])) ? array_sum($totals[$input_name]) : 0;
							$total = (strstr($table_fields[$input_name]['Type'],'double')) ? number_format($total,2) : $total;
							$HTML .= '
							<td class="subtotal">
								<div class="show_total" id="total_'.$input_name.'">'.$total.'</div>
								<script type="text/javascript">
									formGridTotal(\''.$input_name.'\');
								</script>
							</td>';
						}
						else
							$HTML .= '<td class="subtotal"></td>';
					}
				}
				$HTML .= '<td class="subtotal"></td></tr>';
			}
			$HTML .= '</table>';
		}

		$HTML .= (($show_as_grid) ? '<div class="multiple_add">' : '<li class="multiple_add">').'<label></label><a href="#" onclick="multipleNewInput(this'.(($show_as_grid) ? ',false,false,1' : '').');return false;"><div class="add_new"></div>'.$CFG->add_new_caption.'</a>'.(($show_as_grid) ? '</div>' : '</li>');
		
		if (!$show_as_grid) {
			$HTML .= '</ul>';
		}
		
		$HTML .= '</div>';
		$this->HTML[] = $HTML;
	}
	
	function includePage($url,$is_tab=false) {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = self::peLabel($CFG->method_id,'includePage');
			$this->HTML[] = "[Include: {$url}] $method_name";
		}
		else {
			$was_tab = $CFG->is_tab;
			$CFG->is_tab = $is_tab;
			$CFG->in_include = true;
			$CFG->include_id = $this->info[str_replace('-','_',$url).'_include_id'];
			$CFG->include_id = (is_array($this->include_ids)) ? $this->include_ids[str_replace('-','_',$url)] : $CFG->include_id;
			ob_start();
			$control = new Control($url,'form',$is_tab);
			$this->HTML[] = '<include>'.ob_get_contents();
			ob_end_clean();
			$CFG->in_include = false;
			$CFG->is_tab = $was_tab;
		}
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
	
	private function executeUpdates() {
		if (is_array($this->create_record)) {
			foreach ($this->create_record as $record) {
				if ($record['edit'] > 0)
					DB::update($record['table'],$record['insert_values'],$record['id']);
				else
					DB::insert($record['table'],$record['insert_values']);
			}
		}
	}
	
	function display() {
		global $CFG;		
		
		self::executeUpdates();
	
		// display form
		if (!$this->output_started) {
			if (!$this->has_areas && !strstr($this->name,'form_filters') && !$CFG->in_popup && $CFG->backstage_mode) {
				echo '
				<div class="area full_box">
					<h2>'.(($CFG->form_legend) ? $CFG->form_legend : Ops::getPageTitle()).'</h2>
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
			if (!$CFG->pm_editor) {
				echo '<form name="'.$this->name.'" action="'.$this->action.'" class="form '.$this->class.'" method="'.$this->method.'"  '.$this->enctype.' '.$this->target.'>';
			}
			else {
				echo '<div class="form '.$this->class.'">';
			}
		}
		
		if (!$CFG->in_include) {
			$is_tab = ($this->go_to_is_tab) ? $this->go_to_is_tab : $CFG->is_tab;
			echo '<input type="hidden" id="form_table" name="form_table" value="'.$this->table.'" />';
			echo '<input type="hidden" id="form_name" name="form_name" value="'.$this->name.'" />';
			echo '<input type="hidden" name="is_tab" value="'.$is_tab.'" />';
			
			if ($CFG->url) {
				$url = ($this->go_to_url) ? $this->go_to_url : $CFG->url;
				$action = ($this->go_to_action) ? $this->go_to_action : $CFG->action;
				echo '<input type="hidden" name="current_url" value="'.$url.'" />';
				if ($this->return_to_self) {
					echo '<input type="hidden" name="id" value="'.$this->record_id.'" />';
					echo '<input type="hidden" name="action" value="'.$action.'" />';
					echo '<input type="hidden" name="return_to_self" value="1" />';
				}
			}
			if ($this->go_to_url)
				echo '<input type="hidden" name="remember_values" value="1"/>';
				
			echo '<ul>';
		}
		else {
			echo '
			<input type="hidden" name="includes['.$this->table.'][name]" value="'.$this->name.'" />
			<input type="hidden" name="includes['.$this->table.'][id]" value="'.$this->record_id.'" />';
			if (is_array($this->db_fields)) {
				foreach ($this->db_fields as $f_name => $f_type) {
					echo '<input type="hidden" name="includes['.$this->table.'][fields]['.$f_name.']" value="'.$f_type.'" />';
				}
			}
		}
		
		if (is_array($this->HTML)) {
			$alt = 'alt';
			foreach ($this->HTML as $method_id => $elem) {
				if (!$in_group && !stristr($elem,'<htmlfield>'))
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
					if ($CFG->pm_editor) {
						$method_name = self::peLabel($method_id,'captcha');
					}
					echo "
					<li class=\"$alt\">
						<div class=\"captcha\">
							<label for=\"captcha\">{$this->c_caption}{$method_name}</label>
							<img id=\"captcha\" src=\"securimage/securimage_show.php\" />
							<input type=\"text\" name=\"caco\" />
							<input type=\"hidden\" name=\"is_caco[{$this->name}]\" value=\"1\">
						</div>
					</li>";
				
					continue;
				}
				elseif (stristr($elem,'<htmlfield>')) {
					if ($CFG->pm_editor && !strstr($this->name,'form_filters')) {
						$method_name = self::peLabel($method_id,'HTML');
					}
					echo str_replace('<htmlfield>','',$elem).$method_name;
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
		
		if (!$CFG->in_include)
			echo '</ul>';
		
		// send required fields
		if (is_array($this->required)) {
			foreach ($this->required as $field => $type) {
				echo '<input type="hidden" name="verify_fields['.$field.']" value="'.$type.'" />';
			}
		}
		
		// send db types for fields
		if (is_array($this->db_fields)) {
			foreach ($this->db_fields as $field => $type) {
				echo '<input type="hidden" class="form_db_field" name="db_fields['.$field.']" value="'.$type.'" />';
			}
		}
		
		// send compare fields
		if (is_array($this->compare)) {
			foreach ($this->compare as $field => $value) {
				echo '<input type="hidden" class="compare_fields" name="compare_fields['.$field.']" value="'.$value.'" />';
			}
		}
		
		// send unique fields
		if (is_array($this->unique)) {
			foreach ($this->unique as $field => $value) {
				echo '<input type="hidden" class="unique_fields" name="unique_fields['.$field.']" value="'.$value.'" />';
			}
		}
		
		if (is_array($this->delete_whitespace)) {
			foreach ($this->delete_whitespace as $field) {
				echo '<input type="hidden" name="delete_whitespace[]" value="'.$field.'" />';
			}
		}
		
		if (is_array($this->color_fields)) {
			foreach ($this->color_fields as $field) {
				echo '<input type="hidden" name="color_fields[]" value="'.$field.'" />';
			}
		}
		
		if ($this->record_id) {
			echo '<input type="hidden" name="record_id" value="'.$this->record_id.'" />';
		}
		echo '
		<div style="clear:both;"></div>
		<script type="text/javascript">
			startFileSortable();
		</script>';
		
		if (!$this->output_started) {
			if (!$CFG->pm_editor) {
				echo '</form>';
			}
			else {
				echo '</div>';
			}
			
			if (!$this->has_areas && !strstr($this->name,'form_filters') && !$CFG->in_popup && $CFG->backstage_mode)
				echo '</div></div>';
		}
	}
	
	function verify() {
		global $CFG;
		
		if ($this->info && $this->compare_fields) {
			foreach ($this->compare_fields as $name => $comp_name) {
				if ($this->info[$comp_name]) {
					if ($this->info[$comp_name] != $this->info[$name])
						$this->errors[$name] = $CFG->compare_error;
				}
				unset($this->info[$name]);
			}
		}
		
		if ($_REQUEST['is_caco'][$this->name]) {
			include_once 'securimage/securimage.php';
			$securimage = new Securimage();
			if ($securimage->check($_REQUEST['caco']) == false) {
				$this->errors[] = $CFG->capcha_error;
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
							$this->errors[$name] = $CFG->verify_date_error;
						
						continue;
					}
					
					switch($this->verify_fields[$name]) {
						case 'email':
							if (!Email::verifyAddress($value)) 
								$this->errors[$name] = $CFG->verify_email_error;
						break;
						case 'phone':
							if (!ValidateData::validatePhone($value)) 
								$this->errors[$name] = $CFG->verify_phone_error;
						break;
						case 'user':
							if (!ValidateData::validatePhone($value)) 
								$this->errors[$name] = $CFG->verify_phone_error;
						break;
						case 'password':
							if (!preg_match($CFG->pass_regex,$value)) 
								$this->errors[$name] = $CFG->verify_password_error;
						break;
						case 'zip':
							if (!ValidateData::validateZip($value)) 
								$this->errors[$name] = $CFG->verify_zip_error;
						break;
						case 'checkbox':
							if ($value == 'N') 
								$this->errors[$name] = $CFG->verify_default_error;
						break;
						case '':
						case true:
							if (strlen(trim($value)) == 0) 
								$this->errors[$name] = $CFG->verify_default_error;
							elseif (!$CFG->backstage_mode) {
								if ($_REQUEST['db_fields'][$name] == 'vchar' || $_REQUEST['db_fields'][$name] == 'int') {
									$delete_whitespace = @in_array($name,$this->delete_whitespace);
									if (String::sanitize($value,true,$delete_whitespace)) {
										$this->errors[$name] = $CFG->verify_invalid_char_error;
									}
								}	
							}
						break;
						default:
							if (!preg_match($this->verify_fields[$name],$value)) 
								$this->errors[$name] = $CFG->verify_custom_error;
						break;
					}
				}
			}
			
			if ($this->info && $this->unique_fields && !($this->record_id > 0)) {
				foreach ($this->unique_fields as $name => $err_message) {
					if (@array_key_exists($name,$this->errors))
						continue;
					
					if (!DB::isUniqueValue($this->table,$name,$this->info[$name]))
						$this->errors[$name] = (!empty($err_message)) ? $err_message : $CFG->value_exists_error;
				}
			}
			
			if ($_REQUEST['cat_selects']) {
				foreach ($_REQUEST['cat_selects'] as $name => $v) {
					if (!$this->info['cat_selects'][$name])
						$this->info['cat_selects'][$name] = array();
				}
				
				foreach ($this->info['cat_selects'] as $name => $values) {
					if ($this->verify_fields[$name]) {
						$name_parts = explode('|', $this->verify_fields[$name]);
						$cat_select_min = $name_parts[1];
						
						if (!is_array($values) || count($values) < $cat_select_min)
							$this->errors[$name] = str_ireplace('[n]',$cat_select_min,$CFG->verify_cat_select_error);
					}
				}
			}
			if ($_REQUEST['files']) {
				foreach ($_REQUEST['files'] as $name => $reqs) {
					if (!@in_array($name,$this->attached_file_fields)) {
						$found = false;
						if (array_key_exists($name,$this->verify_fields)) {
							if (is_array($this->temp_files)) {
								foreach ($this->temp_files as $k => $v) {
									if (stristr($k,$name)) {
										$found = true;
									}
								}
							}
							if (is_array($_FILES[$this->name]['name'])) {
								foreach ($_FILES[$this->name]['name'] as $k => $v) {
									if (stristr($k,$name)) {
										if (!empty($v)) {
											$found = true;
										}
									}
								}
							}
							if (!$found) 
								$this->errors[$name] = $CFG->verify_file_required_error;
						}
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
						if ($this->info['cat_selects'] && $this->info['cat_selects'][$subtable]) {
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

		/*
		if ($CFG->backstage_mode && !empty($_REQUEST['trigger_field'])) {
			if (is_array($_REQUEST['trigger_field'])) {
				foreach ($_REQUEST['trigger_field'] as $k => $tfield) {
					self::emailNotify($tfield,$_REQUEST['trigger_value'][$k],$_REQUEST['email_field'][$k],$_REQUEST['email_table'][$k],$_REQUEST['email_record'][$k]);
				}
				$this->bypass_email = true;
			}
		}
*/
/*
		if ($CFG->backstage_mode && !empty($_REQUEST['trigger_field1'])) {
			if (is_array($_REQUEST['trigger_field1'])) {
				foreach ($_REQUEST['trigger_field1'] as $k => $tfield) {
					if (!empty($tfield)) {
						self::createRecord($_REQUEST['create_record_table'][$k],$tfield,$_REQUEST['trigger_value'][$k]);
					}
				}
			}
		}
*/	
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

		if ($this->info && !$this->errors) {
			if ($CFG->auto_create_table) {
				if (!DB::tableExists($this->table)) {
					if (DB::createTable($this->table,$_REQUEST['db_fields'],$_REQUEST['radioinputs'],$this->ignore_fields)) {
						$this->messages[$this->table] = $CFG->table_created;
					}
					else {
						$this->errors[] = $CFG->table_creation_error;
					}
				}
			}
			if (!$this->errors) {
				$insert_values = $this->info;
				if (is_array($this->ignore_fields)) {
					foreach ($this->ignore_fields as $i_name => $i_table) {
						unset($insert_values[$i_name]);
					}		
				}

				if (is_array($this->includes)) {
					foreach ($this->includes as $i_table => $i_info) {
						if (is_array($this->ignore_fields)) {
							foreach ($this->info as $key => $value) {
								if (array_key_exists($key,$this->ignore_fields) && $this->ignore_fields[$key] == $i_table)
									$i_values[$key] = $value;
							}
						}

						if (!$this->record_id) {
							$include_ids[$i_table] = DB::insert($i_table,$i_values);
							$this->include_ids = $include_ids;
						}
						else {
							DB::update($i_table,$i_values,$i_info['id']);
						}
					}
				}

				if (!$this->record_id) {
					if ($include_ids) {
						$t_fields = DB::getTableFields($this->table,1);
						if (is_array($t_fields)) {
							foreach ($include_ids as $i_table => $i_id) {
								if (!in_array($i_table.'_include_id',$t_fields)) 
									db_query('ALTER TABLE '.$this->table.' ADD '.$i_table.'_include_id INT( 10 ) UNSIGNED NOT NULL ');
								
								$insert_values[$i_table.'_include_id'] = $i_id;
							}
						}
					}

					if ($this->record_id = DB::insert($this->table,$insert_values,false,$this->ignore_fields)) {
						$this->record_created = true;
						$CFG->id = $this->record_id;
						$this->info['id'] = $this->record_id;
						$this->messages[] = $CFG->form_save_message;
					}
					else {
						$this->errors[] = $CFG->form_save_error;
					}
				}
				else {
					DB::saveImageOrder($_REQUEST['file_order'],$this->table);
					if (DB::update($this->table,$insert_values,$this->record_id,$this->ignore_fields) != -1) {
						$this->record_created = false;
						$this->messages[$this->record_id] = $CFG->form_update_message;
						
						if ($this->table == 'admin_users' && $CFG->url != 'users') {
							User::logOut(1);
							User::logIn($this->info['user'],$this->info['pass']);
						}
					}
					else {
						$this->errors[$this->record_id] = $CFG->form_update_error;
					}
				}
			}
			if (!$this->errors && is_array($this->temp_files)) {
				foreach ($this->temp_files as $field_name => $file_name) {
					$field_name_parts = explode('__',$field_name);
					$field_name_n = $field_name_parts[0];
					$file_reqs = $_REQUEST['files'][$field_name_n];
					$image_sizes = ($file_reqs['image_sizes']) ? $file_reqs['image_sizes'] : $CFG->image_sizes;
					if (Upload::save($file_name,$field_name_n,$this->table,$this->record_id,$file_reqs['dir'],$image_sizes,$field_name)) {
						$this->messages[$file_name] = $CFG->file_save_message;
						unset($this->temp_files[$field_name]);
						unset($CFG->temp_files[$field_name]);
					}
					else {
						$this->errors[$file_name] = $CFG->file_save_error;
					}
				}
			}

			if ($_REQUEST['file_descs']) {
				foreach ($_REQUEST['file_descs'] as $i => $desc) {
					Upload::saveDescriptions($this->table,false,$i);
				}
			}
		}
	}
	
	function get($record_id) {
		global $CFG;
		
		if (($this->record_id && !$CFG->save_called) || !($record_id > 0) || !DB::tableExists($this->table))
			return false;

		$this->get_called = true;
		
		if (!$info = DB::getRecord($this->table,$record_id)) {
			$this->errors = $CFG->form_get_record_error;
		}
		else {
			$this->record_id = $record_id;
		}
		
		if (!$this->save_called)
			$this->old_info = $info;
		
		if ($info && $this->info) {
			$this->info = array_merge($this->info,$info);
		}
		elseif ($info)
			$this->info = $info;
	}
	
	function send_email($from,$recipients_field,$subject=false,$from_name=false,$html_version=false,$text_version=false) {
		if (!$this->errors) {
			if (!$recipients_field)
				return false;
				
			$recipients = $this->info[$recipients_field];
			Email::send($from,$recipients,$subject,$from_name,$html_version,$text_version,$this->info);
		}
	}
	
	function peLabel($method_id,$method_name) {
		global $CFG;
		
		if ($CFG->o_method_suppress) {
			$CFG->o_method_suppress = false;
			return false;
		}
	
		$method_id = ($CFG->o_method_id > 0) ? $CFG->o_method_id : $method_id;
		$method_name = ($CFG->o_method_name) ? $CFG->o_method_name : $method_name;
		$method_name = ($this->override_methods[$method_id]) ? $this->override_methods[$method_id] : $method_name;

		$HTML = '
		<input type="hidden" id="method" value="'.$method_name.'" />
		<input type="hidden" id="id" class="method_id" value="'.$method_id.'" />
		<a href="#" title="'.$CFG->move_hover_caption.'" class="move_handle dont_disable"></a>
		<a href="#" title="'.$CFG->edit_hover_caption.'" class="edit dont_disable" onclick="pmMethodEdit(this,event);return false;"></a>
		<a href="#" title="'.$CFG->delete_hover_caption.'" class="delete dont_disable" onclick="pmMethodDelete(this,'.$method_id.',event);return false;"></a>';
		
		$CFG->o_method_id = 0;
		$CFG->o_method_name = false;
		return $HTML;
	}
	
	function emailNotify($trigger_field=false,$trigger_value=false,$email_field=false,$email_table=false,$email_record=false,$day=false,$month=false,$year=false,$send_condition=false) {		
		global $CFG;

		if ($CFG->backstage_mode) {
			$HTML = '';
			
			if ($CFG->pm_editor)
				$this->HTML[] = "[email_notify]".self::peLabel($CFG->method_id,'emailNotify');
			
			/*
			$HTML .= '
			<input type="hidden" name="trigger_field[]" value="'.$trigger_field.'" />
			<input type="hidden" name="trigger_value[]" value="'.$trigger_value.'" />
			<input type="hidden" name="email_field[]" value="'.$email_field.'" />
			<input type="hidden" name="email_table[]" value="'.$email_table.'" />
			<input type="hidden" name="email_record[]" value="'.$email_record.'" />
			';
			$this->HTML[] = $HTML;
*/
		}

		if ((is_array($this->errors) || !($this->save_called || $CFG->save_called) || !$_REQUEST[$this->name] || $_REQUEST['bypass_create_record']) && !$CFG->in_cron)
			return false;

		if ($this->bypass_email)
			return true;

		if ($send_condition) {
			$send_condition = String::doFormulaReplacements($send_condition,$this->info,1);
			if (!@eval("if ($send_condition) { return 1;} else { return 0;}"))
				return false;
		}
		
		$new_trigger_field = (strstr($trigger_field,',')) ? DB::getForeignValue($trigger_field,$this->record_id,1,$this->info) : $this->info[$trigger_field];
		$old_trigger_field = (strstr($trigger_field,',')) ? DB::getForeignValue($trigger_field,$this->record_id,1,$this->old_info) : $this->old_info[$trigger_field];

		if ($new_trigger_field != $old_trigger_field) {
			if ($new_trigger_field == $trigger_value || (!$trigger_value && $trigger_field)) {
				$message = DB::getRecord($email_table,$email_record,0,1);
				$email_field = (strstr($email_field,',')) ? DB::getForeignValue($email_field,$this->record_id,1,$this->info) : $this->info[$email_field];
				if (Email::send($CFG->form_email,$email_field,$message['title'],$CFG->form_email_from,false,$message['content'],$this->info)) {
					$this->messages[] = $CFG->email_sent_message;
				}
				else {
					$this->errors[] = $CFG->email_send_error;
				}
			}
		}
	}
	
	// $insert_array is array('new_field'=>'old_field','new_field'=>'old_field');
	// $register_changes requires the insert to be into 'comments' table
	function createRecord($table,$insert_array,$trigger_field=false,$trigger_value=false,$day=false,$month=false,$year=false,$send_condition=false,$any_modification=false,$register_changes=false,$on_new_record_only=false,$store_row=false,$if_not_exists=false,$run_in_cron=false) {
		global $CFG;

		if ($CFG->backstage_mode) {
			$HTML = '';
			
			if ($CFG->pm_editor) {
				if (!$this->edit_record)
					$this->HTML[] = "[create_record]".self::peLabel($CFG->method_id,'createRecord');
				else
					$this->HTML[] = "[edit_record]".self::peLabel($CFG->method_id,'editRecord');
			}

			/*
			$HTML .= '
			<input type="hidden" name="trigger_field1[]" value="'.$trigger_field.'" />
			<input type="hidden" name="trigger_value1[]" value="'.$trigger_value.'" />
			<input type="hidden" name="create_record_table[]" value="'.$table.'" />
			';
			$this->HTML[] = $HTML;
*/
		}

		if ($run_in_cron && !$CFG->in_cron) {
			return false;
		}
		elseif ($run_in_cron && $CFG->in_cron) {
			$modified = true;
		}
		
		//used to have this ($CFG->ignore_request == $table) return false. Don't remember why.
		if ((is_array($this->errors) || !($this->save_called || $CFG->save_called) || !$_REQUEST[$this->name] || $_REQUEST['bypass_create_record']) && !$CFG->in_cron)
			return false;
			
		if ((!$on_new_record_only && $this->record_created && !$trigger_field) || ($on_new_record_only && !$this->record_created)) {
			return false;
		}

		if ($send_condition) {
			$send_condition = String::doFormulaReplacements($send_condition,$this->info,1);
			if (!eval("if ($send_condition) { return 1;} else { return 0;}"))
				return false;
		}

		if ($register_changes) {
			$changes = '<div class="show_details"><a onclick="showDetails(this);return false;" href="#">'.$CFG->comments_show_details.'</a><a onclick="hideDetails(this);return false;" style="display:none;" href="#">'.$CFG->comments_hide_details.'</a></div><div class="details" style="display:none;">';
		}	

		if ($this->info && $register_changes) {
			foreach ($this->info as $name => $value) {
				$grid_input_modified = false;
				if (@in_array($name,$_REQUEST['grid_inputs'])) {
					if (is_array($this->info[$name])) {
						if (is_array($this->old_info[$name])) {
							foreach ($this->old_info[$name] as $id => $row) {
								foreach ($row as $k => $v) {
									$key = $row['id'];
									if (!empty($v) && $v != 'N' && $k != 'id' && $k != 'f_id')
										$compare[$key][$k] = $v;
								}
							}
							if ($compare) {
								ksort($compare);
								$compare = array_values($compare);
							}
						}
						$i = 0;
						foreach ($this->info[$name] as $id => $row) {
							foreach ($row as $k => $v) {
								if (!empty($v))
									$filtered[$i] = $row;
							}
							$i++;
						}	
						if ($filtered && $compare) {
							$i = 0;
							foreach ($filtered as $array) {
								if (is_array($array)) {
									foreach ($array as $k => $v) { 
										if ($v != $compare[$i][$k] && (!empty($v) && !empty($compare[$i][$k]))) {
											$grid_input_modified = true;	
										}
									}
								}
								$i++;
							}

							if (!$grid_input_modified) {
								if (count($filtered) != count($compare))
									$grid_input_modified = true;
							}
						}
						elseif (($compare && !$filtered) || ($filtered && !$compare))
							$grid_input_modified = true;
					}
				}
				
				if ($name == 'cat_selects') {
					if (is_array($this->info[$name])) {
						@asort($this->info[$name]);
						@asort($this->old_info[$name]);
						$this->info[$name] = @array_values($this->info[$name]);
						$this->old_info[$name] = @array_values($this->old_info[$name]);
						
						foreach ($this->info[$name] as $key => $arr) {
								@asort($arr);
								$this->info[$name][$key] = @array_values($arr);	
							}
						
						if (is_array($this->old_info[$name])) {
							foreach ($this->old_info[$name] as $key => $arr) {
								@asort($arr);
								$this->old_info[$name][$key] = @array_values($arr);	
							}
						}
						
						if ($this->info[$name] != $this->old_info[$name]) {
							$modified = true;
							$changes.= '<b>'.$name.'</b> '.$CFG->comments_set_to.' '.(is_array($value) ? print_r($value,true) : $value).'<br/>';
						}
					}
				}
				elseif (((strip_tags($this->info[$name]) != strip_tags($this->old_info[$name])) && !@in_array($name,$_REQUEST['grid_inputs'])) || $grid_input_modified) {
					$modified = true;
					$changes.= '<b>'.$name.'</b> '.$CFG->comments_set_to.' '.(is_array($value) ? print_r($value,true) : $value).'<br/>';
				}
				$bypass = false;
				$compare = false;
				$filtered = false;
			}
		}

		if ($register_changes) {
			$changes .= '</div>';
		}
		
		if ($on_new_record_only && $this->record_created)
			$modified = true;
		
		if (($this->info[$trigger_field] != $this->old_info[$trigger_field]) || $modified) {
			if (($this->info[$trigger_field] == $trigger_value || (!$trigger_value && $trigger_field)) || $modified) {
				if (!is_array($insert_array) && stristr($insert_array,'array:')) {
					$insert_array = str_ireplace('array:','',$insert_array);
					$ia1 = explode(',',$insert_array);
					if (is_array($ia1)) {
						foreach ($ia1 as $v) {
							if (strstr($v,'=>')) {
								$ia2 = explode('=>',$v);
								$ia3[$ia2[0]] = $ia2[1];
								$last_key = $ia2[0];
							}
							else {
								$ia3[$last_key] .= ','.$v;
							}
						}
					}
					unset($insert_array);
					$insert_array = $ia3;
				}

				if (is_array($insert_array)) {
					foreach ($insert_array as $new_field =>$old_field) {
						if ($old_field == 'curdate') {
							$insert_values[$new_field] = date('Y-m-d 00:00:00');
						}
						elseif ($old_field == 'curtime') {
							$insert_values[$new_field] = date('Y-m-d H:i:s',time() + (Settings::mysqlTimeDiff() * 3600));
						}
						elseif ($old_field == 'user_id') {
							$insert_values[$new_field] = User::$info['id'];
						}
						elseif ($old_field == 'record_id') {
							$insert_values[$new_field] = $this->record_id;
						}
						elseif (strstr($old_field,'(') && strstr($old_field,')')) {
							if ($this->record_created)
								$this->old_info['id'] = $this->record_id;
							
							$formula = String::doFormulaReplacements($old_field,$this->old_info,1);
							$insert_values[$new_field] = eval("return ($formula);");
						}
						elseif ($old_field == 'current_url') {
							$insert_values[$new_field] = $CFG->url;
						}
						elseif (!is_array($old_field)) {
							if (array_key_exists($old_field,$this->info)) {
								$insert_values[$new_field] = $this->info[$old_field];
							}
							else {
								$insert_values[$new_field] = $old_field;
							}
						}
						else {
							$insert_values[$new_field] = DB::getForeignValue(implode(',',$old_field),$this->info['id']);
						}
					}
					if ($register_changes) {
						$insert_values['comments'] = $changes;
					}
					if ($store_row && $table == 'comments') {
						if ($this->edit_record && $table != $this->table) {
							$row = DB::getRecord($table,$this->edit_record_field_id,0,1);
							$insert_values['f_table_row'] = serialize($row);
						}
						else 
							$insert_values['f_table_row'] = serialize($this->info);
					}

					$CFG->ignore_request = $table;
					$CFG->bypass_unserialize = true;
					$this->edit_record_id_field = ($this->edit_record_id_field) ? $this->edit_record_id_field : $this->record_id;
					
					if (!$this->edit_record) {
						if ($if_not_exists) {
							$insert_values1 = $insert_values;
							if ($k = array_search($this->record_id,$insert_values1))
								unset($insert_values1[$k]);
								
							if (DB::recordExists($table,$insert_values1))
								return false;
						}
						$this->create_record[] = array('table'=>$table,'insert_values'=>$insert_values);
						//echo 'Insert:';
						//print_ar($insert_values);
					}
					else {
						$this->create_record[] = array('edit'=>1,'table'=>$table,'insert_values'=>$insert_values,'id'=>$this->edit_record_field_id);
						//echo 'Update:';
						//print_ar($insert_values);
					}
				}
			}
		}
	}
	
	function editRecord($table,$insert_array,$trigger_field=false,$trigger_value=false,$day=false,$month=false,$year=false,$send_condition=false,$any_modification=false,$register_changes=false,$on_new_record_only=false,$store_row=false,$edit_record_field_id=false,$run_in_cron=false) {
		$this->edit_record = true;
		$this->edit_record_field_id = (strstr($edit_record_field_id,',')) ? DB::getForeignValue($edit_record_field_id,$this->record_id,1,$this->row) : $this->info[$edit_record_field_id];
		$this->createRecord($table,$insert_array,$trigger_field,$trigger_value,$day,$month,$year,$send_condition,$any_modification,$register_changes,$on_new_record_only,$store_row,false,$run_in_cron);
		$this->edit_record = false;
		$this->edit_record_field_id = false;
	}
	
	private function parseJscript($jscript,$id,$j=false,$grid_input=false) {
		global $CFG;
		
		if ($CFG->pm_editor)
			return false;
			
		$primary_id = ($j > 0) ? $this->name.'_'.$id.'_'.$j.'_'.$grid_input : $this->name.'_'.$id;
		$j_parts = explode('(',$jscript);
		$j_parts1 = explode(')',$j_parts[1]);
		$operation = $j_parts1[0];
		$matches = String::getSubstring($jscript,'[',']');
		if (is_array($matches)) {
			$variables = 'var variables = new Array();';
	
			foreach ($matches as $match) {
				$variables .= 'variables["'.$match.'"] = "'.$match.'";';
				$operation = str_ireplace('['.$match.']','variables["'.$match.'"]',$operation);
			}
		}

		if (stristr($jscript,'operation(')) {
			$SCRIPT = '';
			if (strlen($variables) > 0) {
				$SCRIPT .= $variables.' ';
				$SCRIPT .= 'operation(\''.$operation.'\',variables,\''.$this->name.'\',\''.$id.'\','.$j.',\''.$grid_input.'\');';
			}
			$SCRIPT = 'outside|'.$SCRIPT;
		}
		elseif (stristr($jscript,'getValue(')) {
			$SCRIPT = '';
			if (strlen($variables) > 0) {
				$SCRIPT .= $variables.' ';
				$SCRIPT .= 'getValue(variables,\''.$primary_id.'\');';
				$SCRIPT = 'outside| '.$SCRIPT;
			}
		}
		elseif (stristr($jscript,'createRecord(')) {
			$SCRIPT = '';
			$op_parts = explode(',',$operation);
			$SCRIPT .= 'createRecord(\''.$op_parts[0].'\',\''.$op_parts[1].'\',\''.$op_parts[2].'\',\''.$op_parts[3].'\',\''.$op_parts[4].'\','.$this->record_id.');';
			$SCRIPT = ' onclick="'.$SCRIPT.'" ';

		}
		elseif (stristr($jscript,'displayIf(')) {
			$SCRIPT = '';
			$op_parts = explode(',',$operation);
			if (strlen($variables) > 0) {
				$SCRIPT .= $variables.' ';
				$SCRIPT .= 'displayIf(variables,\''.$primary_id.'\',\''.str_replace("'","",$op_parts[1]).'\',\''.$this->name.'\');';
				$SCRIPT = 'outside| '.$SCRIPT;
			}
		}	
		else {
			$SCRIPT = $jscript;
		}
		//echo $SCRIPT;
		return $SCRIPT;
	}
	
	function filterPerPage($caption=false,$options_array=false,$class=false) {
		global $CFG;
		
		$properties = array('type'=>'per_page','field_name'=>'per_page','caption'=>$caption,'options_array'=>$options_array,'class'=> $class);
		$options_array = (is_array($options_array)) ? $options_array : array(10=>10,30=>30,50=>50);
		$caption = (!empty($caption)) ? $caption : $CFG->results_per_page_text;
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterPerPage';
		self::selectInput('per_page',$caption,false,false,$options_array,false,false,false,false,$class);
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="filter_properties[per_page]" value="'.urlencode(serialize($properties)).'" />');
	}
	
	// $fields_array = array('field_name'=>'table_name');
	function filterSearch($fields_array,$caption=false,$class=false) {
		global $CFG;
		
		$properties = array('type'=>'search','caption'=>$caption,'subtable_fields' => $fields_array,'class'=> $class);
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterSearch';
		self::textInput('search',$caption,false,false,false,false,$class);
		foreach ($fields_array as $s_field => $s_subtable) {
			$s_subtable = (($s_subtable) && ($s_subtable != $s_field)) ? $s_subtable : $this->table;
			$CFG->o_method_suppress = true;
			self::HTML('<input type="hidden" name="search_fields['.$s_field.']" value="'.$s_subtable.'" />');
			$CFG->o_method_suppress = true;
			self::HTML('<input type="hidden" name="filter_properties[search]" value="'.urlencode(serialize($properties)).'" />');
		}
	}
	
	function filterAutocomplete($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false) {
		global $CFG;
		
		$properties = array('type'=>'autocomplete','field_name'=>$field_name,'caption'=>$caption,'options_array'=>$options_array,'subtable' => $subtable,'subtable_fields' => $subtable_fields,'class'=> $class);
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterAutocomplete';
		self::autoComplete($field_name,$caption,false,false,false,$options_array,$subtable,$subtable_fields,false,false,$class);
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="subtables['.$field_name.'][subtable]" value="'.$subtable.'" />');
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="subtables['.$field_name.'][subtable_fields]" value="'.implode('|',$subtable_fields).'" />');
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="subtables['.$field_name.'][f_id_field]" value="'.$f_id_field.'" />');	
	}
	
	function filterCats($subtable,$caption=false,$class=false,$subtable_fields=false,$concat_char=false) {
		global $CFG;
		
		$properties = array('type'=>'cats','caption'=>$caption,'subtable' => $subtable,'class'=> $class,'subtable_fields'=>$subtable_fields,'concat_char'=>$concat_char);
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterCats';
		self::catSelect($subtable,$caption,0,$class,false,false,false,$subtable_fields,$concat_char);
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="filter_properties[cat_selects]" value="'.urlencode(serialize($properties)).'" />');		
	}

	function filterFirstLetter($field_name,$subtable=false) {
		global $CFG;
		
		$properties = array('field_name'=>$field_name,'type'=>'first_letter','subtable' => $subtable);
		$range = range('A','Z');
		$HTML = '';
		foreach ($range as $l) {
			$HTML .= Link::url($this->go_to_url,$l,'fl='.$l.'&fl_field='.$field_name.'&fl_subtable='.$subtable.'&is_tab='.$this->go_to_is_tab,false,false,'content');
		}
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterFirstLetter';
		self::HTML($HTML);
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="filter_properties['.$field_name.']" value="'.urlencode(serialize($properties)).'" />');
	}
	
	function filterSelect($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false,$f_id_field=false,$depends_on=false) {
		global $CFG;
		
		$properties = array('type'=>'select','field_name'=>$field_name,'caption'=>$caption,'options_array'=>$options_array,'subtable' => $subtable,'subtable_fields' => $subtable_fields,'class'=> $class,'f_id_field'=>$f_id_field,'depends_on'=>$depends_on);
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterSelect';
		self::selectInput($field_name,$caption,false,false,$options_array,$subtable,$subtable_fields,false,false,$class,false,false,$f_id_field,false,$depends_on);
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="subtables['.$field_name.'][subtable]" value="'.$subtable.'" />');
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="subtables['.$field_name.'][subtable_fields]" value="'.implode('|',$subtable_fields).'" />');
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="subtables['.$field_name.'][f_id_field]" value="'.$f_id_field.'" />');
	}
	
	function filterTokenizer($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false,$f_id_field=false,$depends_on=false) {
		global $CFG;
		
		$properties = array('type'=>'tokenizer','field_name'=>$field_name,'caption'=>$caption,'options_array'=>$options_array,'subtable' => $subtable,'subtable_fields' => $subtable_fields,'class'=> $class);
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterTokenizer';
		self::autoComplete($field_name,$caption,false,$value,false,$options_array,$subtable,$subtable_fields,false,false,$class,false,false,false,false,false,false,false,false,false,false,false,1);
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="filter_properties['.$field_name.']" value="'.urlencode(serialize($properties)).'" />');
	}
	
	function filterCheckbox($field_name,$caption=false,$checked=false,$class=false) {
		global $CFG;
		
		$properties = array('type'=>'checkbox','field_name'=>$field_name,'caption'=>$caption,'checked' => $checked,'class'=> $class,'method_id'=>$CFG->method_id);
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterCheckbox';
		self::checkBox($field_name,$caption,false,false,$class,false,false,$checked);
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="filter_properties['.$field_name.']" value="'.urlencode(serialize($properties)).'" />');
	}
	
	function filterRadio($field_name,$caption=false,$value=false,$checked=false,$class=false) {
		global $CFG;
		
		$properties = array('type'=>'radio','field_name'=>$field_name,'value'=>$value,'caption'=>$caption,'checked' => $checked,'class'=> $class);
		
		if (!$group) {
			$CFG->o_method_suppress = true;
			self::startGroup();
		}
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterRadio';
		self::radioInput($field_name,$caption,false,$value,false,$class,false,false,$checked);
		if (!$group) {
			$group = true;
		}
		else {
			$CFG->o_method_suppress = true;
			self::endGroup();
			$group = false;
		}
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="filter_properties['.$field_name.']" value="'.urlencode(serialize($properties)).'" />');
	}
						
	function filterDateStart($field_name,$caption=false,$value=false,$time=false,$ampm=false,$req_start=false,$req_end=false,$link_to=false,$format=false) {
		global $CFG;
		
		$properties = array('type'=>'start_date','field_name'=>$field_name,'caption'=>$caption,'value'=>$value,'time'=>$time,'ampm'=>$ampm,'req_start'=>$req_start,'req_end'=>$req_end,'link_to'=>$link_to,'format'=>$format);
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterDateStart';
		self::dateWidget($field_name,$caption,false,$time,$ampm,$req_start,$req_end,$value,false,false,$class,$format);
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="filter_properties['.$field_name.']" value="'.urlencode(serialize($properties)).'" />');
	}
	
	function filterDateEnd($field_name,$caption=false,$value=false,$time=false,$ampm=false,$req_start=false,$req_end=false,$link_to=false,$format=false) {
		global $CFG;
		
		$properties = array('type'=>'end_date','field_name'=>$field_name,'caption'=>$caption,'value'=>$value,'time'=>$time,'ampm'=>$ampm,'req_start'=>$req_start,'req_end'=>$req_end,'link_to'=>$link_to,'format'=>$format);
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterDateEnd';
		self::dateWidget($field_name,$caption,false,$time,$ampm,$req_start,$req_end,$value,$link_to,false,$class,$format,false,false,true);
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="filter_properties['.$field_name.']" value="'.urlencode(serialize($properties)).'" />');
	}
	
	function filterMonth($field_name,$caption=false,$language=false) {
		global $CFG;
		
		$this->filters[] = array('type'=>'month','field_name'=>$field_name,'caption'=>$caption,'language'=>$language);
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterMonth';
		self::selectInput($field_name.'_month',$caption,false,false,String::getMonthNames($language));
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="month_fields[]" value="'.$field_name.'_month" />');
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="filter_properties['.$field_name.']" value="'.urlencode(serialize($properties)).'" />');
	}
	
	function filterYear($field_name,$caption=false,$back_to=false) {
		global $CFG;
		
		$this->filters[] = array('type'=>'year','field_name'=>$field_name,'caption'=>$caption,'back_to'=>$back_to);
		$CFG->o_method_id = $CFG->method_id;
		$CFG->o_method_name = 'filterYear';
		$back_to = ($back_to) ? $back_to : 1975;
		$years = range(date('Y'),$back_to);
		$years = array_combine($years,$years);
		self::selectInput($field_name.'_year',$caption,false,false,$years);
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="year_fields[]" value="'.$field_name.'_year" />');
		$CFG->o_method_suppress = true;
		self::HTML('<input type="hidden" name="filter_properties['.$field_name.']" value="'.urlencode(serialize($properties)).'" />');
	}
}
?>