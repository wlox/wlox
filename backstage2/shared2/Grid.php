<?php

class Grid {
	public $table,$fields,$mode,$rows_per_page,$class,$operations,$op_fields,$filters,$filter_results,$order_by,$order_asc,$errors,$messages,$filter_i,$max_pages,$pagination_label,$target_elem_id,$show_buttons,$show_list_captions,$inset_id,$inset_id_field,$inset_i,$modes,$cumulative,$links_out,$grid_label,$sql_filter,$alert_condition1,$alert_condition2,$i,$group_by,$no_group_by;
	
	function __construct($table,$mode=false,$rows_per_page=0,$class=false,$show_buttons=true,$target_elem_id=false,$max_pages=false,$pagination_label=true,$show_list_captions=false,$order_by=false,$order_asc=false,$save_order=false,$enable_graph=false,$enable_table=false,$enable_list=false,$enable_graph_line=false,$enable_graph_pie=false,$button_link_url=false,$button_link_is_tab=false,$sql_filter=false,$alert_condition1=false,$alert_condition2=false,$group_by=false,$no_group_by=false) {
		global $CFG;
		
		$this->table = $table;
		$this->i = $CFG->control_pass_id;

		if ($save_order) {
			$saved_order = DB::getOrder($this->i,User::$info['id']);
			if ($saved_order) {
				$order_by = $saved_order['order_by'];
				$order_asc = $saved_order['order_asc'];
			}
			DB::setOrder($saved_order['id'],$_REQUEST['filter'.$this->i],$_REQUEST['order_asc'.$this->i],$this->i,User::$info['id']);
		}
		
		$this->mode = ($mode) ? $mode : $_SESSION['mode'.$this->i];
		$this->mode = ($this->mode) ? $this->mode : 'table';
		$this->mode = ($_REQUEST['mode'.$this->i]) ? $_REQUEST['mode'.$this->i] : $this->mode;
		$_SESSION['mode'.$this->i] = $this->mode;

		$this->class = ($class) ? $class : 'grid';
		$this->target_elem_id = ($target_elem_id) ? $target_elem_id : 'content';
		$this->target_elem_id = ($this->inset_id > 0) ? $CFG->inset_target_elem : $this->target_elem_id;
		$this->max_pages = $max_pages;
		$this->pagination_label = ($pagination_label) ? $pagination_label : $CFG->pagination_label;
		$this->show_buttons = $show_buttons;
		$this->filter_i = 0;
		$this->group_by = $group_by;
		$this->no_group_by = $no_group_by;
		
		$form = new Form('form_filters'.$this->i);
		if ($_REQUEST['form_filters'.$this->i]) {
			$this->graph_name_column = $form->info['graph_name_column'];
			$this->graph_value_column = $form->info['graph_value_column'];
			$this->graph_x_axis = $form->info['graph_x_axis'];
			$this->graph_combine = $form->info['graph_combine'];
			unset($form->info['graph_name_column']);
			unset($form->info['graph_value_column']);
			unset($form->info['graph_x_axis']);
			unset($form->info['graph_combine']);
			$this->filter_results = $form->info;
			$this->filter_results['first_letter'] = $_REQUEST['fl'.$this->i];
			$this->filter_results['first_letter_field'] = $_REQUEST['fl_field'.$this->i];
			$this->filter_results['first_letter_subtable'] = $_REQUEST['fl_subtable'.$this->i];
			$this->rows_per_page = ($this->filter_results['per_page'] > 0) ? $this->filter_results['per_page'] : $rows_per_page;
			$this->rows_per_page = (!($this->rows_per_page > 0)) ? $CFG->rows_per_page : $this->rows_per_page;
			$_SESSION['filter_results'.$this->i] = $this->filter_results;
			$_SESSION['rows_per_page'.$this->i] = $this->rows_per_page;
			$_SESSION['search_fields'.$this->i] = $_REQUEST['search_fields'.$this->i];
		}
		else {
			$this->filter_results = $_SESSION['filter_results'.$this->i];
			$this->rows_per_page = ($_SESSION['rows_per_page'.$this->i] > 0) ? $_SESSION['rows_per_page'.$this->i] : $CFG->rows_per_page;
			$_REQUEST['search_fields'.$this->i] = $_SESSION['search_fields'.$this->i];
			unset($this->filter_results['search']);
		}
		unset($_SESSION['filter_results'.$this->i]);

		$this->order_by = ($_REQUEST['filter'.$this->i]) ? $_REQUEST['filter'.$this->i] : $order_by;
		$this->order_by = ($this->order_by) ? $this->order_by : $_SESSION['order_by'.$this->i];
		$this->order_by_changed = ($this->order_by != $_SESSION['order_by'.$this->i]);
		$_SESSION['order_by'.$this->i] = $this->order_by;
		$this->order_asc = ($_REQUEST['order_asc'.$this->i]) ? $_REQUEST['order_asc'.$this->i] : $order_asc;
		$this->order_asc = ($_REQUEST['filter'.$this->i]) ? $this->order_asc : $_SESSION['order_asc'.$this->i];
		$this->order_asc_changed = ($this->order_asc != $_SESSION['order_asc'.$this->i]);
		$_SESSION['order_asc'.$this->i] = $this->order_asc;
		
		$this->show_list_captions = $show_list_captions;
		$this->inset_id = ($_REQUEST['inset_id']) ? $_REQUEST['inset_id'] : $CFG->inset_id;
		$this->inset_id_field = ($_REQUEST['inset_id_field']) ? $_REQUEST['inset_id_field'] : $CFG->inset_id_field;
		$this->inset_id_field = ($this->inset_id_field) ? $this->inset_id_field : 'f_id';
		$this->inset_i = ($CFG->inset_i) ? $CFG->inset_i : $_REQUEST['inset_i'];
		$CFG->inset_url = ($this->inset_id > 0) ? (($CFG->inset_url) ? $CFG->inset_url : $_REQUEST['inset_url']) : false;
		$this->link_url = ($this->inset_id > 0) ? $CFG->inset_url : $CFG->url;
		$this->link_url = ($button_link_url) ? $button_link_url : $this->link_url;
		$this->is_tab = $CFG->is_tab;
		$this->is_tab = ($button_link_is_tab) ? 1 : $this->is_tab;
		$this->is_tab = ($this->inset_id > 0) ? $CFG->inset_is_tab : $this->is_tab;
		$CFG->is_tab = $this->is_tab;
		$this->links_out = $button_link_url;
		$this->enable_graph = $enable_graph;
		$this->enable_table = $enable_table;
		$this->enable_list = $enable_list;
		$this->sql_filter[] = $sql_filter;
		$this->alert_condition1 = $alert_condition1;
		$this->alert_condition2 = $alert_condition2;
		
		if ($enable_table)
			$this->modes['table'] = 1;
		if ($enable_list)
			$this->modes['list'] = 1;
		if ($enable_graph)
			$this->modes['graph'] = 1;
		if ($enable_graph_line)
			$this->modes['graph_line'] = 1;
		if ($enable_graph_pie)
			$this->modes['graph_pie'] = 1;
			
		$iform = new Form('grid_form_'.$this->table.$this->i);
		$iform_action = $_REQUEST['iform_action'.$this->i];
		$iform_table = $_REQUEST['iform_table'.$this->i];
		$iform_id = $_REQUEST['iform_id'.$this->i];
		
		if (is_array($iform->info)) {
			foreach ($iform->info as $key => $row) {
				if ($iform_action[$key] == 'edit') {
					if (count($row) > 0 && !empty($row)) {
						$num_affected = DB::update($iform_table[$key],$row,$iform_id[$key]);
						if ($num_affected > 0)
							$this->messages[] = $CFG->form_save_message;
						else
							$this->errors[] = $CFG->form_save_error;
					}
				}
				else {
					if (count($row) > 0 && !empty($row)) {
						$insert_id = DB::insert($iform_table[$key],$row);
						if ($insert_id > 0)
							$this->messages[] = $CFG->form_save_message;
						else
							$this->errors[] = $CFG->form_save_error;
					}
				}
			}
			if (is_array($this->messages)) {
				$this->errors = false;
			}
		}
	}	
	
	function field($name,$subtable=false,$header_caption=false,$filter=false,$link_url=false,$subtable_fields=false,$subtable_fields_concat=false,$is_media=false,$class=false,$aggregate_function=false,$thumb_amount=0,$media_amount=0,$media_size=false,$f_id_field=false,$order_by=false,$order_asc=false,$link_is_tab=false,$limit_is_curdate=false) {
		global $CFG;
		
		$rand = (@array_key_exists($name,$this->fields)) ? 'lll'.rand(1,99) : false;
		
		$this->fields[$name.$rand] = array(
			'name'=>$name,
			'subtable'=>$subtable,
			'header_caption'=>($header_caption) ? $header_caption : $name,
			'filter' => $filter,
			'link_url'=>$link_url,
			'is_media'=>$is_media,
			'subtable_fields'=>$subtable_fields,
			'subtable_fields_concat'=>$subtable_fields_concat,
			'class'=>$class,
			'aggregate_function'=>$aggregate_function,
			'thumb_amount'=>$thumb_amount,
			'media_amount'=>$media_amount,
			'media_size'=>$media_size,
			'method_id'=>$CFG->method_id,
			'f_id_field'=>$f_id_field,
			'order_by'=>$order_by,
			'order_asc'=>$order_asc,
			'link_is_tab'=>$link_is_tab,
			'limit_is_curdate'=>$limit_is_curdate);
	}
	
	// you can do operations with field names, ex: (name1 - name2) / name3
	// you can also use any math function such as min,max,etc...
	function aggregate($name,$formula,$header_caption=false,$cumulative_function=false,$run_in_sql=false,$filter=false,$join_path=false,$join_condition=false) {
		global $CFG;

		$this->fields[$name] = array(
			'name'=>$name,
			'formula'=>$formula,
			'header_caption'=>($header_caption) ? $header_caption : String::substring($formula,15),
			'method_id'=>$CFG->method_id,
			'cumulative_function'=>$cumulative_function,
			'run_in_sql'=>$run_in_sql,
			'filter' => $filter,
			'join_path' => $join_path,
			'join_condition'=>$join_condition,
			'is_op'=>1);
	}
	
	//$insert_new_record_when = sql conditional that returns row or no row
	function inlineForm($name,$table,$inputs_array=false,$header_caption=false,$f_id_field=false,$f_id=0,$insert_new_record_when=false,$order_by=false,$order_asc=false,$save_button_caption=false) {
		global $CFG;

		$this->fields[$name] = array(
			'name'=>$name,
			'table'=>$table,
			'inputs_array'=>$inputs_array,
			'header_caption'=>$header_caption,
			'method_id'=>$CFG->method_id,
			'f_id_field'=>$f_id_field,
			'f_id'=>$f_id,
			'insert_new_record_when'=>$insert_new_record_when,
			'order_by'=>$order_by,
			'order_asc'=>$order_asc,
			'save_button_caption'=>$save_button_caption,
			'is_form'=>1);
	}
	
	function filterPerPage($caption=false,$options_array=false,$class=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'per_page',
			'field_name'=>'per_page',
			'caption'=>$caption,
			'options_array'=>$options_array,
			'class'=> $class,
			'method_id'=>$CFG->method_id);	
	}
	
	// $fields_array = array('field_name'=>'table_name');
	function filterSearch($fields_array,$caption=false,$class=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'search',
			'caption'=>$caption,
			'subtable_fields' => $fields_array,
			'class'=> $class,
			'method_id'=>$CFG->method_id);
	}
	
	function filterAutocomplete($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'type'=>'autocomplete',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'options_array'=>$options_array,
			'subtable' => $subtable,
			'subtable_fields' => $subtable_fields,
			'class'=> $class,
			'method_id'=>$CFG->method_id);
	}
	
	function filterCats($subtable,$caption=false,$class=false,$subtable_fields=false,$concat_char=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'cats',
			'caption'=>$caption,
			'subtable' => $subtable,
			'class'=> $class,
			'method_id'=>$CFG->method_id,
			'subtable_fields'=>$subtable_fields,
			'concat_char'=>$concat_char);
	}

	function filterFirstLetter($field_name,$subtable=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'field_name'=>$field_name,
			'type'=>'first_letter',
			'subtable' => $subtable,
			'method_id'=>$CFG->method_id);
	}
	
	function filterSelect($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false,$f_id_field=false,$depends_on=false,$level=false,$f_id=false,$use_enum_values=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'type'=>'select',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'options_array'=>$options_array,
			'subtable' => $subtable,
			'subtable_fields' => $subtable_fields,
			'class'=> $class,
			'method_id'=>$CFG->method_id,
			'f_id_field'=>$f_id_field,
			'level'=>$level,
			'depends_on'=>$depends_on,
			'f_id'=>$f_id,
			'use_enum_values'=>$use_enum_values
		);
	}
	
	function filterTokenizer($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false,$f_id_field=false,$depends_on=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'type'=>'tokenizer',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'options_array'=>$options_array,
			'subtable' => $subtable,
			'subtable_fields' => $subtable_fields,
			'class'=> $class,
			'method_id'=>$CFG->method_id);
	}
	
	function filterCheckbox($field_name,$caption=false,$checked=false,$class=false,$value=false,$not_equals=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'type'=>'checkbox',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'checked' => $checked,
			'class'=> $class,
			'value'=>$value,
			'not_equals'=>$not_equals,
			'method_id'=>$CFG->method_id);
	}
	
	function filterRadio($field_name,$caption=false,$value=false,$checked=false,$class=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'radio',
			'field_name'=>$field_name,
			'value'=>$value,
			'caption'=>$caption,
			'checked' => $checked,
			'class'=> $class,
			'method_id'=>$CFG->method_id);
	}
						
	function filterDateStart($field_name,$caption=false,$value=false,$time=false,$ampm=false,$req_start=false,$req_end=false,$link_to=false,$format=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'start_date',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'value'=>$value,
			'time'=>$time,
			'ampm'=>$ampm,
			'req_start'=>$req_start,
			'req_end'=>$req_end,
			'link_to'=>$link_to,
			'format'=>$format,
			'method_id'=>$CFG->method_id);
	}
	
	function filterDateEnd($field_name,$caption=false,$value=false,$time=false,$ampm=false,$req_start=false,$req_end=false,$link_to=false,$format=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'end_date',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'value'=>$value,
			'time'=>$time,
			'ampm'=>$ampm,
			'req_start'=>$req_start,
			'req_end'=>$req_end,
			'link_to'=>$link_to,
			'format'=>$format,
			'method_id'=>$CFG->method_id);
	}
	
	function filterMonth($field_name,$caption=false,$language=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'month',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'language'=>$language,
			'method_id'=>$CFG->method_id);
	}
	
	function filterYear($field_name,$caption=false,$back_to=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'year',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'back_to'=>$back_to,
			'method_id'=>$CFG->method_id);
	}
	
	function gridLabel($text) {
		global $CFG;
		$this->grid_label = array('text'=>$text,'method_id'=>$CFG->method_id);
	}
	
	function show_errors() {
		if ($this->errors) {
			echo '<ul class="errors">';
			foreach ($this->errors as $name => $error) {
				echo '<li><div class="error_icon"></div>'.ucfirst(str_ireplace('[field]',$name,$error)).'</li>';
			}
			echo '</ul>';
		}
	}
	
	function show_messages() {
		if ($this->messages) {
			echo '<ul class="messages">';
			foreach ($this->messages as $name => $message) {
				echo '<li><div class="warning_icon"></div>'.ucfirst(str_ireplace('[field]',$name,$message)).'</li>';
			}
			echo '</ul>';
		}
	}
	
	function display($page=0) {
		global $CFG;
		
		$filters = self::getFilterResults();
		$page = ($page > 0) ? $page : $_SESSION['page'.$this->i];
		$_SESSION['page'.$this->i] = $page;
		$page = (!($page > 0) || $_REQUEST['submit'] || $this->order_asc_changed || $this->order_by_changed) ? 1 : $page;
		$fields = DB::getTableFields($this->table);
		$total_rows = DB::get($this->table,$this->fields,$page,$this->rows_per_page,$this->order_by,$this->order_asc,1,$filters,$this->inset_id,$this->inset_id_field,false,false,false,false,false,false,false,$this->sql_filter,$this->group_by,$this->no_group_by);
		$data = DB::get($this->table,$this->fields,$page,$this->rows_per_page,$this->order_by,$this->order_asc,0,$filters,$this->inset_id,$this->inset_id_field,false,false,false,false,false,false,false,$this->sql_filter,$this->group_by,$this->no_group_by);
		$HTML = "";
		
		if ($CFG->backstage_mode && (User::permission(0,0,$this->link_url,false,$this->is_tab) > 1) && !($this->inset_id > 0)) {
			$HTML .= '
			<form id="grid_form_'.$this->table.$this->i.'" name="grid_form_'.$this->table.$this->i.'" action="'.$CFG->self.'" method="POST">
				<input type="hidden" name="current_url" value="'.$CFG->url.'" />
				<input type="hidden" name="action" value="" />
				<input type="hidden" name="return_to_self" value="1" />';
			
			if (is_array($this->fields)) {
				foreach ($this->fields as $properties) {
					if ($properties['aggregate_function']) {
						$i_name = $properties['name'];
						switch ($properties['aggregate_function']) {
							case 'grand_total':
								$grand_total[$i_name] = 0;
							break;
							case 'page_total':
								$page_total[$i_name] = 0;
							break;
							case 'grand_avg':
								$grand_avg[$i_name] = array();
							break;
							case 'page_avg':
								$page_avg[$i_name] = array();
							break;
							case 'both_total':
								$page_total[$i_name] = 0;
								$grand_total[$i_name] = 0;
							break;
							case 'both_avg':
								$page_avg[$i_name] = array();
								$grand_avg[$i_name] = array();
							break;
						}
					}
				}
			}
			if (is_array($this->fields)) {
				foreach ($this->fields as $properties) {
					if ($properties['cumulative_function']) {
						$i_name = $properties['name'];
						if ($properties['cumulative_function'] == 'sum') {
							$page_total[$i_name] = 0;
							if ($grand_total)
								$grand_total[$i_name] = 0;
						}
						elseif ($properties['cumulative_function'] == 'avg') {
							$page_avg[$i_name] = array();
							if ($grand_avg)
								$grand_avg[$i_name] = array();
						}
					}
				}
			}
		}
		
		if ($this->mode == 'list') {
			$HTML .= "<ul class=\"grid_list\">";
			if (is_array($data)) {
				$j = 0;
				foreach ($data as $row) {
					$HTML .= "<li><ul>";
					if (is_array($this->fields)) {
						foreach ($this->fields as $name => $properties) {
							$key = $name;
							if (strstr($name,'lll')) {
								$name_parts = explode('lll',$name);
								$name = $name_parts[0];
							}
					
							if (($this->inset_id > 0) && ($name == $this->inset_id_field)) 
								continue;
							
							$value = $row[$key];
							$link_id = ($row[$name.'_id']) ? $row[$name.'_id'] : $value;
							$class = ($properties['class']) ? "class=\"{$properties['class']}\"" : '';
							if ($CFG->pm_editor)
								$method_name = Form::peLabel($properties['method_id'],'field');
								
							$HTML .= "<li $class>".$method_name."";
							
							if (!empty($properties['is_media'])) {
								reset($CFG->image_sizes);
								$m_values = explode('|||',$value);
								$m_size = (!empty($properties['media_size'])) ? $properties['media_size'] : key($CFG->image_sizes);
								$m_limit = (!empty($properties['media_amount'])) ? $properties['media_amount'] : 1;

								$HTML .= Gallery::multiple($properties['subtable'],$row['id'],$properties['name'],$properties['media_size'],0,false,$properties['media_amount'],false,false,true);
								$HTML .= '<div class="clear"></div>';
							}
							else {
								if ($fields[$name]['Type'] == 'datetime' || @in_array($name,$foreign_dates)) {
									$value = date($CFG->default_date_format,strtotime($value));
								}
								elseif ($fields[$name]['Type'] == "enum('Y','N')") {
									$value = ($value == 'Y') ? '<div class="y_icon"></div>' : '<div class="n_icon"></div>';
								}
								
								if ($value['filter']) {
									$order_asc = ($this->order_asc) ? false : true;
									
									if ($this->order_by == $name) 
										$dir_img = ($this->order_asc) ? $CFG->up : $CFG->down;
									else
										$dir_img = false;
									
									$HTML .= '<b>'.Link::url($this->link_url,$properties['header_caption'].$dir_img,"filter{$this->i}=$name&order_by{$this->i}={$this->order_by}&order_asc{$this->i}={$order_asc}&is_tab={$this->is_tab}",$this->filter_results,false,'content').':</b> ';
								}
								else {
									$HTML .= ($this->show_list_captions) ? '<b>'.$properties['header_caption'].':</b> ' : '';
								}
								
								if (empty($properties['link_url'])) {
									$HTML .= str_ireplace('|||',' ',$value);
								}
								else {
									$action = ($CFG->backstage_mode) ? '&action=record' : '';
									$value = str_replace('|||',' ',$value);
									
									if (!empty($value))
										$HTML .= Link::url($properties['link_url'],$value,"id=$link_id&is_tab={$properties['link_is_tab']}{$action}",false,false,$this->target_elem_id);
								}
								
								if (is_array($page_total)) {
									if (array_key_exists($name,$page_total))
										$page_total[$name] += $value;
								}
								if (is_array($page_avg)) {
									if (array_key_exists($name,$page_avg))
										$page_avg[$name][] = $value;
								}
							}
							$HTML .= "</li>";
						}
					}
					if ($this->show_buttons) {
						$HTML .= '<li><nobr>'.(($CFG->backstage_mode) ? "<span><label for=\"{$row['id']}\">Select:</label><input id=\"{$row['id']}\" type=\"checkbox\" value=\"{$row['id']}\" class=\"grid_select checkbox_input\"/></span>" : '');
						if (User::permission(0,0,$this->link_url,false,$this->is_tab) > 0)
							$HTML .= Link::url($this->link_url,false,'id='.$row['id'].'&action=record&is_tab='.$this->is_tab,false,false,$this->target_elem_id,'view',false,false,false,false,$CFG->view_hover_caption).' ';
						if (User::permission(0,0,$this->link_url,false,$this->is_tab) > 1)
							$HTML .= Link::url($this->link_url,false,'id='.$row['id'].'&action=form&is_tab='.$this->is_tab,false,false,$this->target_elem_id,'edit',false,false,false,false,$CFG->edit_hover_caption).' ';
						if (User::permission(0,0,$this->link_url,false,$this->is_tab) > 1)
							$HTML .= '<a href="#" class="delete" title="'.$CFG->delete_hover_caption.'" onclick="gridDelete('.$row['id'].',\''.$this->table.'\',this)"></a></li>';
					}
					$HTML .= '</nobr></li></ul>';
					$j++;
				}
			}
			else {
				$HTML .= '<li>'.$CFG->grid_no_results.'</li>';
			}
			$HTML .= "</ul>";
		}
		elseif ($this->mode == 'graph' || $this->mode == 'graph_line' || $this->mode == 'graph_pie') {
			$name_column = $this->graph_name_column;
			$y_axis = $this->graph_value_column;
			$x_axis = $this->graph_x_axis;
			
			if (is_array($this->fields)) {
				foreach ($this->fields as $name => $properties) {
					if ((strstr($fields[$name]['Type'],'varchar') || (!$properties['is_op'] && !empty($properties['subtable'])))) {
						if (!$name_column)
							$name_column = $name;
							
						$this->name_columns[$name] = $properties['header_caption']; 
					}
					elseif (strstr($fields[$name]['Type'],'date')) {
						if (!$x_axis)
							$x_axis = $name;
							
						$this->x_columns[$name] = $properties['header_caption'];
					}	
					elseif (($properties['is_op'] || strstr($fields[$name]['Type'],'int') || strstr($fields[$name]['Type'],'double')) && ($name != 'id') && empty($properties['subtable'])) {
						if (!$y_axis)
							$y_axis = $name;
							
						$this->value_columns[$name] = $properties['header_caption'];
					}
				}
			}

			if ($data) {
				foreach ($data as $row) {
					$x_values[] = strtotime($row[$x_axis]);
					$y_values[] = $row[$y_axis];
				}
				
				$days = (max($x_values) - min($x_values)) / 86400;
				$max_x = max($x_values);
				$min_x = min($x_values);
				$timestamp = $min_x;
				
				if ($days <= 30) {
					$time_unit = 'days';
					while ($timestamp <= $max_x) {
						$time_units[] = $timestamp;
						$timestamp += 86400;
					}
				}
				elseif ($days > 30 && $days <= 183) {
					$time_unit = 'weeks';
					while ($timestamp <= $max_x) {
						$time_units[] = $timestamp;
						$timestamp += (86400 * 7);
					}
				}
				elseif ($days > 183 && $days <= 910) {
					$time_unit = 'months';
					$timestamp = strtotime(date('n/1/Y',$min_x));
					while ($timestamp <= $max_x) {
						$time_units[] = $timestamp;
						$timestamp = strtotime(date('n/1/Y',strtotime($p_name.' + 1 month')));
					}
				}
				elseif ($days > 910 && $days <= 1820) {
					$time_unit = 'months';
					$timestamp = strtotime(date('n/1/Y',$min_x));
					while ($timestamp <= $max_x) {
						$time_units[] = $timestamp;
						$timestamp = strtotime(date('n/1/Y',strtotime($p_name.' + 2 months')));
					}
				}
				elseif ($days > 1820 && $days <= 3640) {
					$time_unit = 'months';
					$timestamp = strtotime(date('n/1/Y',$min_x));
					while ($timestamp <= $max_x) {
						$time_units[] = $timestamp;
						$timestamp = strtotime(date('n/1/Y',strtotime($p_name.' + 4 months')));
					}
				}
				elseif ($days > 3640 && $days <= 7280) {
					$time_unit = 'months';
					$timestamp = strtotime(date('n/1/Y',$min_x));
					while ($timestamp <= $max_x) {
						$time_units[] = $timestamp;
						$timestamp = strtotime(date('n/1/Y',strtotime($p_name.' + 6 months')));
					}
				}
				elseif ($days > 7280 && $days <= 14560) {
					$time_unit = 'months';
					$timestamp = strtotime(date('n/1/Y',$min_x));
					while ($timestamp <= $max_x) {
						$time_units[] = $timestamp;
						$timestamp = strtotime(date('n/1/Y',strtotime($p_name.' + 8 months')));
					}
				}
				elseif ($days > 14560 && $days <= 29120) {
					$time_unit = 'months';
					$timestamp = strtotime(date('n/1/Y',$min_x));
					while ($timestamp <= $max_x) {
						$time_units[] = $timestamp;
						$timestamp = strtotime(date('n/1/Y',strtotime($p_name.' + 10 months')));
					}
				}
				elseif ($days > 29120 && $days <= 58240) {
					$time_unit = 'years';
					$timestamp = strtotime(date('1/1/Y',$min_x));
					while ($timestamp <= $max_x) {
						$time_units[] = $timestamp;
						$timestamp = strtotime(date('1/1/Y',strtotime($p_name.' + 1 year')));
					}
				}
				
				$reps = 0;
				foreach ($data as $row) {
					if (is_array($this->fields)) {
						$name_value = ($this->graph_combine != 'Y') ? $row[$name_column] : 'All';
						$c_units = count($time_units);
						$x_val = strtotime($row[$x_axis]);
			
						for ($i=0;$i<$c_units;$i++) {
							if ($x_val >= $time_units[$i] && ($x_val < $time_units[($i + 1)] || !$time_units[($i + 1)])) {
								$x_current = $time_units[$i];
								break;
							}
						}
						$key = $x_current;
						
						if ($x_prev != $x_current) {
							$y_current = false;
							$reps = 0;
						}
						
						if ($this->fields[$y_axis]['is_op'] && !$this->fields[$y_axis]['run_in_sql']) {
							$y_current = self::doOperation($y_axis,$this->fields[$y_axis],$row,$name_value);
						}
						else {
							$y_current = $y_current + $row[$y_axis];
						}
						
						$x_prev = $x_current;
						
						if ($this->mode != 'graph_pie') {
							if (!$graph_data[$name_value][$key]) {
								$graph_data[$name_value][$key] = $y_current;
							}
							else {
								if ($this->fields[$y_axis]['cumulative_function'] == 'avg') {
									$graph_data[$name_value][$key] = ($graph_data[$name_value][$key] + $y_current)/$reps;
								}
								else {
									$graph_data[$name_value][$key] += $y_current;
								}
							}
						}
						else {
							if (!$graph_data[1][$name_value]) {
								$graph_data[1][$name_value] = $y_current;
							}
							else {
								if ($this->fields[$y_axis]['cumulative_function'] == 'avg') {
									$graph_data[1][$name_value] = ($graph_data[1][$name_value] + $y_current)/$reps;
								}
								else {
									$graph_data[1][$name_value] += $y_current;
								}
							}
						}
							
						$titles[$name_value] = $name_value;
						$reps++;
					}
				}

				if ($this->mode != 'graph_pie') {
					foreach ($graph_data as $name_value => $val) {
						$last_value = 0;
						foreach ($time_units as $unit) {
							$key = $unit;
							if (!array_key_exists($key,$graph_data[$name_value])) {
								if (!empty($this->fields[$y_axis]['cumulative_function'])) {
									$graph_data[$name_value][$key] = $last_value;
								}
								else {
									$graph_data[$name_value][$key] = 0;
								}
							} 
							else {
								$last_value = $graph_data[$name_value][$key];
							}
						}
					}
					foreach ($graph_data as $name_value => $val) {
						ksort($graph_data[$name_value]);
						$last_value = 0;
						
						foreach ($graph_data[$name_value] as $key => $val) {
							if ($time_unit == 'days' || $time_unit == 'weeks') {
								$key1 = date('M j',$key);
							}
							elseif ($time_unit == 'months') {
								$key1 = date('M',$key);
							}
							elseif ($time_unit == 'years') {
								$key1 = date('Y',$key);
							}
							$graph_data1[$name_value][$key1] = $val;
						}
					}
				}
				else {
					$graph_data1 = $graph_data;
				}
			}

			$HTML .= '<img class="graph" src="includes/graph.php?graph_data='.urlencode(serialize($graph_data1)).'&titles='.urlencode(serialize($titles)).'&mode='.$this->mode.'">';
		}
		else {
			$HTML .= "<table><tr class=\"grid_header\">";

			if ($CFG->backstage_mode && !$this->links_out && $this->show_buttons && $CFG->is_ctrl_panel != 'Y')
				$HTML .= "<th><label for=\"grid_select{$this->i}\"/><input id=\"grid_select{$this->i}\" type=\"checkbox\" class=\"grid_select checkbox_input\" onclick=\"gridSelectAll(this)\"/></th>";
				
			if (is_array($this->fields)) {
				foreach ($this->fields as $name => $value) {
					$key = $name;
					if (strstr($name,'lll')) {
						$name_parts = explode('lll',$name);
						$name = $name_parts[0];
					}
					
					if ($this->inset_id > 0) {
						if ($value['name'] == $this->inset_id_field)
							continue;
						
						if (strstr($this->inset_id_field,'.')) {
							$inset_field_parts = explode('.',$this->inset_id_field);
							if ($value['subtable'] == $inset_field_parts[0] && (in_array($inset_field_parts[1],$value['subtable_fields']) || $inset_field_parts[1] == 'id')) {
								continue;
							}
						}
					}
						
					if ($value['is_op'] && !$value['run_in_sql']) {
						if ($CFG->pm_editor)
							$method_name = Form::peLabel($value['method_id'],'aggregate');
							
						$HTML .= "<th>".$value['header_caption'].$method_name.'</th>';
						continue;
					}
					elseif($value['is_form']) {
						if ($CFG->pm_editor)
							$method_name = Form::peLabel($value['method_id'],'inlineForm');
							
						$HTML .= "<th class=\"multiple_input\">".$value['header_caption'].$method_name.'</th>';
						continue;
					}

					if ($CFG->pm_editor)
						$method_name = (!$value['run_in_sql']) ? Form::peLabel($value['method_id'],'field') : Form::peLabel($value['method_id'],'aggregate');
						
					if ($value['filter']) {
						$order_asc = ($this->order_asc) ? false : true;
						
						if ($this->order_by == $name) 
							$dir_img = ($this->order_asc) ? $CFG->up : $CFG->down;
						else
							$dir_img = false;
						
						$filter_results = $_REQUEST['form_filters'.$this->i];
						$HTML .= "<th>".Link::url($CFG->url,$value['header_caption'].$dir_img,false,array('filter'.$this->i=>$name,'order_by'.$this->i=>$this->order_by,'order_asc'.$this->i=>$order_asc,'is_tab'=>$this->is_tab,'inset_id'=>$this->inset_id,'inset_id_field'=>$this->inset_id_field,'inset_i'=>$this->inset_i,'form_filters'.$this->i=>$filter_results,'search_fields'.$this->i=>$_REQUEST['search_fields'.$this->i]),false,false,(($this->inset_i > 0) ? 'inset_area_'.$this->inset_i : 'content')).$method_name."</th>";		
					}
					else {
						$HTML .= "<th>".$value['header_caption'].$method_name.'</th>';
					}
				}
			}
			
			$HTML .= ($this->show_buttons) ? "<th>&nbsp;</th>" : '';
			$HTML .= '</tr>';
			
			if (is_array($data)) {
				$alt = false;
				foreach ($data as $row) {
					$alt = ($alt) ? false : 'alt';
					if ($this->alert_condition1) {
						$condition = String::doFormulaReplacements($this->alert_condition1,$row,1);
						$alert_class1 = (eval("if ($condition) { return 1;} else { return 0;}")) ? 'alert1' : '';
					}
					if ($this->alert_condition2) {
						$condition = String::doFormulaReplacements($this->alert_condition2,$row,1);
						$alert_class2 = (eval("if ($condition) { return 1;} else { return 0;}")) ? 'alert2' : '';
					}
					$HTML .= '<tr class="'.$alt.' '.$alert_class1.' '.$alert_class2.'">';
					
					if (!is_array($this->fields))
						continue;
						
					if ($CFG->backstage_mode && !$this->links_out && $this->show_buttons && $CFG->is_ctrl_panel != 'Y')
						$HTML .= "<td><label for=\"checkbox{$row['id']}\"/><input id=\"checkbox{$row['id']}\" type=\"checkbox\" value=\"{$row['id']}\" class=\"grid_select checkbox_input\"/></td>";

					foreach ($this->fields as $name => $properties) {
						$key = $name;
						if (strstr($name,'lll')) {
							$name_parts = explode('lll',$name);
							$name = $name_parts[0];
						}

						if ($this->inset_id > 0) {
							if ($properties['name'] == $this->inset_id_field)
								continue;
							
							if (strstr($this->inset_id_field,'.')) {
								$inset_field_parts = explode('.',$this->inset_id_field);
								if ($properties['subtable'] == $inset_field_parts[0] && (in_array($inset_field_parts[1],$properties['subtable_fields']) || $inset_field_parts[1] == 'id')) {
									continue;
								}
							}
						}

						$value = $row[$key];
						$link_id = ($row[$name.'_id']) ? $row[$name.'_id'] : $value;
						$class = ($properties['class']) ? "class=\"{$properties['class']}\"" : '';
						$HTML .= "<td $class>";
						
						if (!empty($properties['is_media'])) {
							reset($CFG->image_sizes);
							$m_values = explode('|||',$value);
							$m_size = (!empty($properties['media_size'])) ? $properties['media_size'] : key($CFG->image_sizes);
							$m_limit = (!empty($properties['media_amount'])) ? $properties['media_amount'] : 1;
							
							$HTML .= Gallery::multiple($properties['subtable'],$row['id'],$properties['name'],$properties['media_size'],0,false,$properties['media_amount'],false,false,true);
						}
						elseif ($properties['is_op'] && !$properties['run_in_sql']) {
							$value1 = number_format(self::doOperation($key,$properties,$row),2);
							if (is_array($page_total)) {
								if (array_key_exists($name,$page_total))
									$page_total[$key] += $value1;
							}
							if (is_array($page_avg)) {
								if (array_key_exists($name,$page_avg))
									$page_avg[$key][] = $value1;
							}
							$HTML .= $value1;
						}
						elseif ($properties['is_form']) {
							$HTML .= '<div>';
							
							if (!$ref) {
								$ref = new ReflectionClass('Form');
								if (is_array($properties['inputs_array'])) {
									foreach ($properties['inputs_array'] as $method=>$args) {
										$method_parts = explode('|',$method);
										$method1 = $method_parts[0];
										$params = $ref->getMethod($method1)->getParameters();
										
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
												elseif ($param_name == 'checked')
													$i_methods[$method]['checked'] = $i;
												elseif ($param_name == 'grid_input')
													$i_methods[$method]['grid_input'] = $i;
												elseif ($param_name == 'is_current_timestamp')
													$i_methods[$method]['is_current_timestamp'] = $i;
													
												$i++;
											}
										}
									}
								}
							}
							if (!empty($properties['insert_new_record_when'])) {
								$properties['insert_new_record_when'] = String::replaceConditionals('('.$properties['insert_new_record_when'].')',$row,$properties['f_id_field']);
								$result = eval("if ({$properties['insert_new_record_when']}) { return 0;} else { return 1;}");
							}

							$i_table = (!empty($properties['table'])) ? $properties['table'] : $this->table;
							$i_f_id = ($properties['f_id']) ? $row[str_replace('[','',str_replace(']','',$properties['f_id']))] : $row['id'];
							if (!$result) {
								$i_row = DB::getRecord($i_table,0,$row['id'],1,$properties['f_id_field'],$properties['order_by'],$properties['order_asc']);
							}
							else {
								$i_row = false;
							}
							
							$HTML .= '
							<input type="hidden" name="iform_table'.$this->i.'['.$row['id'].']" value="'.$i_table.'" />
							<input type="hidden" name="iform_id'.$this->i.'['.$row['id'].']" value="'.$i_row['id'].'" />';
							if ($i_row) {
								$HTML .=  '<input type="hidden" name="iform_action'.$this->i.'['.$row['id'].']" value="edit" />';
							}
							else {
								$HTML .=  '<input type="hidden" name="iform_action'.$this->i.'['.$row['id'].']" value="new" />';	
							}

							if (is_array($properties['inputs_array'])) {
								foreach ($properties['inputs_array'] as $method=>$args) {
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
									$i_is_current_timestamp = $i_methods[$method]['is_current_timestamp'];
			
									$input_name = $args[0];
									$args[$i_static] = 1;
									$args[$i_j] = $input_name;
									$args[0] = $row['id'];
			
									if ($method1 == 'textInput') {
										$args[13] = '';
										ksort($args);
									}
									if ($method1 == 'hiddenInput') {
										$args[8] = '';
										$args[$i_is_current_timestamp] = $args1['is_current_timestamp'];
										ksort($args);
									}
									
									if ($args1['show_total'])
										$totals[$input_name][] = $row[$input_name];
									
									if ($method1 == 'checkBox')
										$args[$i_checked] = $i_row[$input_name];
									else
										$args[$i_value] = $i_row[$input_name];
									
									if (!$class_instance) {
										$CFG->form_output_started = true;
										$class_instance = $ref->newInstanceArgs(array('grid_form_'.$this->table.$this->i));
									}
									
									$method_instance = $ref->getMethod($method1);
									$HTML .= '<div class="col" id="'.$properties['method_id'].'">'.$method_instance->invokeArgs($class_instance,$args).'</div>';
								}
							}
							if ($class_instance) {
								$CFG->o_method_suppress = true;
								$method_instance = $ref->getMethod('hiddenInput');
								$HTML .= $method_instance->invokeArgs($class_instance,array($row['id'],0,$i_f_id,false,false,false,1,$properties['f_id_field']));
								$CFG->o_method_suppress = false;
							}
							$HTML .= '<div class="clear"></div>';
							$HTML .= '</div>';
						}
						else {
							$value = self::detectData($key,$value,$fields,$foreign_dates);
							
							if (!empty($properties['link_url'])) {
								$action = ($CFG->backstage_mode) ? '&action=record' : '';
								$value = str_replace('|||',' ',$value);
								
								if (!empty($value))
									$HTML .= Link::url($properties['link_url'],$value,"id=$link_id&is_tab={$properties['link_is_tab']}{$action}",false,false,'content');
							}
							else {
								$HTML .= str_ireplace('|||',' ',$value);	
							}

							if (is_array($page_total)) {
								if (array_key_exists($name,$page_total)) {
									$page_total[$key] += $value;
								}
							}
							if (is_array($page_avg)) {
								if (array_key_exists($name,$page_avg)) {
									$page_avg[$key][] = $value;
								}
							}
						}
						$HTML .= "</td>";
					}
					
					if ($this->show_buttons) {
						$HTML .= '<td><nobr>';
						if (User::permission(0,0,$this->link_url,false,$this->is_tab) > 0)
							$HTML .= Link::url($this->link_url,false,'id='.$row['id'].'&action=record&is_tab='.$this->is_tab,false,false,$this->target_elem_id,'view',false,false,false,false,$CFG->view_hover_caption).' ';
						if (User::permission(0,0,$this->link_url,false,$this->is_tab) > 1)
							$HTML .= Link::url($this->link_url,false,'id='.$row['id'].'&action=form&is_tab='.$this->is_tab,false,false,$this->target_elem_id,'edit',false,false,false,false,$CFG->edit_hover_caption).' ';
						if (User::permission(0,0,$this->link_url,false,$this->is_tab) > 1 && !$this->links_out)	
							$HTML .= '<a href="#" title="'.$CFG->delete_hover_caption.'" onclick="gridDelete('.$row['id'].',\''.$this->table.'\',this)" class="delete"></a></nobr></td>';
					}
					$HTML .= '</tr>';
				}
			}
			else {
				$HTML .= '<tr><td colspan="'.(count($this->fields) + 2 ).'">'.$CFG->grid_no_results.'</td></tr>';
			}

			if ($page_total || $page_avg) {
				$HTML .= '<tr>';
				
				if ($this->show_buttons)
					$HTML .= '<td></td>';

				foreach ($this->fields as $name => $properties) {
					if ($page_total[$name]) {
						$subtotal = $page_total[$name];
						$subtotal = (stristr($fields[$name]['Type'],'double')) ? number_format($subtotal,2) : $subtotal;
						$subtotals[$name] = $subtotal;
					}
					else
						$subtotal = false;
					
					if ($page_avg[$name]) {
						$subavg = array_sum($page_avg[$name])/count($page_avg[$name]);
						$subavg = (stristr($fields[$name]['Type'],'double')) ? number_format($subavg,2) : $subavg;
						$subavgs[$name] = $subavg;
					}
					else
						$subavg = false;
				}
				
				$subtotals = $subtotals ? $subtotals : array();
				$subavgs = ($subavgs) ? $subavgs : array();
				$subs = array_merge($subtotals,$subavgs);

				foreach ($this->fields as $name => $properties) {
					$sub = ($subs[$name]) ? $subs[$name] : false;
					$HTML .= '<td class="subtotal">'.((is_numeric($sub)) ? number_format($sub,2) : $sub).'</td>';
				}
				
				if ($this->show_buttons)
					$HTML .= '<td class="subtotal"><em>'.(($page_total) ? $CFG->subtotal_label : '').(($page_total && $page_avg) ? '/' : '').(($page_avg) ? $CFG->subavg_label : '').'</em></td>';
				
				$HTML .= '</tr>';
			}
			
			if ($grand_total || $grand_avg) {
				$HTML .= '<tr>';
				
				if ($this->show_buttons)
					$HTML .= '<td class="total"></td>';

				foreach ($this->fields as $name => $properties) {
					if ($properties['is_op'] && !$properties['run_in_sql'])
						continue;
						
					if (is_array($grand_total)) {
						if (array_key_exists($name,$grand_total)) {
							$total = (array_key_exists($name,$grand_total)) ? number_format(DB::getTotal($properties,$this->table),2) : false;
							$totals[$name] = $total;
						}
					}
					if (is_array($grand_avg)) {
						if (array_key_exists($name,$grand_avg)) {
							//$avg = (array_key_exists($name,$grand_avg)) ? number_format(DB::get($this->table,array($this->table.'.'.$name),$page,$this->rows_per_page,$this->order_by,$this->order_asc,0,$this->filter_results,$this->inset_id,$this->inset_id_field,0,1),2) : false;
							$avg = (array_key_exists($name,$grand_avg)) ? number_format(DB::getAverage($properties,$this->table),2) : false;
							$totals[$name] = $avg;
						}
					}
				}
				
				foreach ($this->fields as $name => $properties) {
					if ($properties['is_op'] && !$properties['run_in_sql']) {
						/*
						$formula = $properties['formula'];
						foreach ($totals as $o_name => $o_value) {
							$formula = str_replace($o_name,str_replace(',','',$o_value),$formula);
						}
						$total = eval("return $formula ;");
						*/
						$total = false;
					}
					else {
						if ($totals[$name])
							$total = $totals[$name];
						else
							$total = false;
					}
					$HTML .= '<td class="total">'.((is_numeric($total)) ? number_format($total,2) : $total).'</td>';
				}
				
				if ($this->show_buttons)
					$HTML .= '<td class="total"><em>'.(($grand_total) ? $CFG->total_label : '').(($grand_total && $grand_avg) ? '/' : '').(($grand_avg) ? $CFG->avg_label : '').'</em></td>';
				
				$HTML .= '</tr>';
			}
			
			$HTML .= '</table>';
		}
		
		$pagination = Grid::pagination($page,$total_rows);
		if ($this->grid_label) {
			if ($CFG->pm_editor)
				$method_name = Form::peLabel($this->grid_label['method_id'],'gridLabel');
		
			$grid_label = $this->grid_label['text'].' '.$method_name;
		}
		else
			$grid_label = Ops::getPageTitle();
		
		
		Grid::show_filters();
		Grid::show_errors();
			
		$amount = ($total_rows > 0) ? '('.$total_rows.')' : false; 
		
		if (!($this->inset_id > 0 || $CFG->is_form_inset)) {
			echo '
			<div class="area full_box" id="grid_'.$this->i.'">
				<h2>'.$grid_label.' '.$amount.'</h2>
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
				<div class="grid_buttons">
				'.$pagination.'';
			
			if (is_array($this->modes)) {
				if (count($this->modes) > 1) {
					echo '<div class="modes">';
					foreach ($this->modes as $mode => $enabled) {
						$class1 = ($mode == $this->mode) ? 'active_view' : false;
						if ($mode == 'table') {
							$class = 'switch_table';
							$caption = $CFG->switch_to_table;
						}
						elseif ($mode == 'list') {
							$class = 'switch_list';
							$label = $CFG->switch_to_list;
						}
						elseif ($mode == 'graph') {
							$class = 'switch_graph';
							$label = $CFG->switch_to_graph;
						}
						elseif ($mode == 'graph_line') {
							$class = 'switch_graph_line';
							$label = $CFG->switch_to_graph_line;
						}
						elseif ($mode == 'graph_pie') {
							$class = 'switch_graph_pie';
							$label = $CFG->switch_to_graph_pie;
						}
						echo Link::url($CFG->url,false,false,array('page'.$this->i=>$page,'p_bypass'.$this->i=>1,'mode'.$this->i=>$mode),true,'content',$class.' '.$class1,false,false,false,false,$label).' ';
					}
					echo '</div>';
				}
			}
			
			if (!$this->links_out && $this->show_buttons && $CFG->is_ctrl_panel != 'Y') {
				echo '<div class="button before"></div>';
				
				if (is_array($this->fields)) {
					foreach ($this->fields as $properties) {
						if ($properties['is_form']) {
							$i_name = (!$properties['button_submit_all']) ? $properties['name'] : 'all';
							echo '<a href="#" onclick="gridSubmitForm(\''.$this->table.$this->i.'\')" class="button"><div class="save"></div>'.$properties['save_button_caption'].'</a>';
							
							if ($properties['button_submit_all'])
								break;
						}
					}
				}
				
				if (array_key_exists('is_active',$fields)) {
					echo '<a class="button" href="#" onclick="gridSetActive(\''.$this->table.'\',1)">'.$CFG->grid_activate_button.'</a>';
					echo '<a class="button" href="#" onclick="gridSetActive(\''.$this->table.'\')">'.$CFG->grid_deactivate_button.'</a>';
				}
				echo Link::url($this->link_url,'<div class="add_new"></div>'.$CFG->add_new_caption,'&action=form&is_tab='.$this->is_tab,false,false,$this->target_elem_id,'button')
				.'<a class="button last" href="#" onclick="gridDeleteSelection(\''.$this->table.'\')"><div class="delete"></div> '.$CFG->delete_button_label.'</a>';
				echo '<div class="button after"></div>';
			}
			
			echo '
				</div>
				<div class="contain">';
		}
		
		$HTML .= '
		<script type="text/javascript">
			$(document).ready(function() {
				$("#grid_'.$this->i.'").find("th").mouseover(function() {
					gridHighlightTH(this);
				});
				$("#grid_'.$this->i.'").find("th").mouseout(function() {
					gridUnHighlightTH(this);
				});
				$("#grid_'.$this->i.'").find("td").mouseover(function() {
					gridHighlightTD(this);
				});
				$("#grid_'.$this->i.'").find("td").mouseout(function() {
					gridUnHighlightTD(this);
				});
			';
		if (User::permission(0,0,$this->link_url,false,$this->is_tab) < 1) {
			$HTML .= '
				$("input").attr("disabled","disabled");
				$("select").attr("disabled","disabled");
			';
		}
		$HTML .= '
			});
		</script>';
		echo $HTML;
		
		if ($this->rows_per_page > 30) echo $pagination;
		
		if ($CFG->backstage_mode && (User::permission(0,0,$this->link_url,false,$this->is_tab) > 1) && !($this->inset_id > 0)) {
			echo "</form>";
			
			if (!($this->inset_id > 0 || $CFG->is_form_inset))
				echo '</div></div>';
		}
	}
	
	function show_filters() {
		global $CFG;
		
		if ($this->inset_id > 0 || $CFG->is_form_inset)
			return false;
		
		if (is_array($this->filters) || $this->mode == 'graph' || $this->mode == 'graph_line' || $this->mode == 'graph_pie') {
			$form_filters = new Form('form_filters'.$this->i,false,'GET','form_filters',false);
			$form_filters->show_errors();
			$filter_results = ($this->filter_results) ? $this->filter_results : array();
			$form_filters_info = ($form_filters->info) ? $form_filters->info : array();
			$form_filters->info = array_merge($filter_results,$form_filters_info);

			if (is_array($this->filters)) {
				foreach ($this->filters as $filter) {
					$name = $filter['field_name'];
					$caption = (!empty($filter['caption'])) ? $caption : $name;
					$value = ($this->filter_results[$name]) ? $this->filter_results[$name] : $filter['value'];
					
					if (($filter['type'] != 'radio' && $filter['type'] != 'start_date' && $filter['type'] != 'end_date') && $group) {
						$form_filters->endGroup();
						$group = false;
					}
					switch ($filter['type']) {
						case 'per_page':
							$options_array = (is_array($filter['options_array'])) ? $filter['options_array'] : array(10=>10,30=>30,50=>50);
							$caption = (!empty($filter['caption'])) ? $filter['caption'] : $CFG->results_per_page_text;
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterPerPage';
							$form_filters->selectInput('per_page',$caption,false,$this->rows_per_page,$options_array,false,false,false,false,$filter['class']);
							break;
						case 'search':
							$search_i = ($search_i > 0) ? $search_i + 1 : 1;
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterSearch';
							$form_filters->textInput('search'.'|'.$search_i,$filter['caption'],false,$value,false,false,$filter['class']);
							foreach ($filter['subtable_fields'] as $s_field => $s_subtable) {
								$s_subtable = (($s_subtable) && ($s_subtable != $s_field)) ? $s_subtable : $this->table;
								$CFG->o_method_suppress = true;
								$form_filters->HTML('<input type="hidden" name="search_fields'.$this->i.'['.$s_field.'|'.$search_i.']" value="'.$s_subtable.'" />');
								$CFG->o_method_suppress = false;
							}
							break;
						case 'autocomplete':
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterAutocomplete';
							$form_filters->autoComplete($name,$filter['caption'],false,$value,false,$filter['options_array'],$filter['subtable'],$filter['subtable_fields'],false,false,$filter['class']);
							break;
						case 'tokenizer':
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterTokenizer';
							$form_filters->autoComplete($name,$filter['caption'],false,$value,false,$filter['options_array'],$filter['subtable'],$filter['subtable_fields'],false,false,$filter['class'],false,false,false,false,false,false,false,false,false,false,false,1);
							break;
						case 'cats':
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterCats';
							//$form_filters->fauxSelect('catsel',$filter['caption'],0,false,false,false,$filter['subtable'],$filter['subtable_fields'],false,$filter['class'],false,false,false,false,false,false,false,false,false,false,false,$filter['concat_char'],1);
							$form_filters->catSelect($filter['subtable'],$filter['caption'],0,$filter['class'],false,false,false,$filter['subtable_fields'],$filter['concat_char'],false,false,1);
							break;
						case 'first_letter':
							$range = range('A','Z');
							$HTML = '';
							foreach ($range as $l) {
								$HTML .= Link::url($this->link_url,$l,'fl'.$this->i.'='.$l.'&fl_field'.$this->i.'='.$name.'&fl_subtable'.$this->i.'='.$filter['subtable'].'&is_tab='.$this->is_tab,false,false,'content');
							}
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterFirstLetter';
							$form_filters->HTML($HTML);
							break;
						case 'select':
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterSelect';
							$form_filters->selectInput($name,$filter['caption'],false,$value,$filter['options_array'],(($filter['use_enum_values'] && !$filter['subtable']) ? $this->table : $filter['subtable']),$filter['subtable_fields'],$filter['f_id'],false,$filter['class'],false,false,$filter['f_id_field'],false,$filter['depends_on'],false,false,false,false,false,$filter['level'],$filter['use_enum_values']);
							break;
						case 'checkbox':
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterCheckbox';
							$form_filters->checkBox($name,$filter['caption'],false,false,$filter['class'],false,false,$value);
							break;
						case 'radio':
							if (!$group) {
								$CFG->o_method_suppress = true;
								$form_filters->startGroup();
							}
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterRadio';
							$form_filters->radioInput($name,$filter['caption'],false,$value,false,$filter['class'],false,false,$filter['checked']);
							if (!$group) {
								$group = true;
							}
							else {
								$CFG->o_method_suppress = true;
								$form_filters->endGroup();
								$group = false;
							}
							break;
						case 'start_date':
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterDateStart';
							$form_filters->dateWidget($name,$filter['caption'],false,$filter['time'],$filter['ampm'],$filter['req_start'],$filter['req_end'],$value,false,false,$filter['class'],$filter['format']);
							$form_filters->dateWidget($name,$CFG->grid_until_label,false,$filter['time'],$filter['ampm'],$filter['req_start'],$filter['req_end'],$value,$filter['link_to'],false,$filter['class'],$filter['format'],false,false,true);
							break;
						case 'month':
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterMonth';
							$form_filters->selectInput($name.'_month',$filter['caption'],false,$value,String::getMonthNames($filter['language']));
							$CFG->o_method_suppress = true;
							$form_filters->HTML('<input type="hidden" name="month_fields[]" value="'.$name.'_month" />');
							break;
						case 'year':
							$CFG->o_method_id = $filter['method_id'];
							$CFG->o_method_name = 'filterYear';
							$back_to = ($filter['back_to']) ? $filter['back_to'] : 1975;
							$years = range(date('Y'),$back_to);
							$years = array_combine($years,$years);
							$form_filters->selectInput($name.'_year',$filter['caption'],false,$value,$years);
							$CFG->o_method_suppress = true;
							$form_filters->HTML('<input type="hidden" name="year_fields[]" value="'.$name.'_year" />');
							break;
					}
				}
			}

			if ($this->mode == 'graph' || $this->mode == 'graph_line' || $this->mode == 'graph_pie') {
				$CFG->o_method_suppress = true;
				$form_filters->selectInput('graph_value_column',$CFG->value_column_label,false,false,$this->value_columns);
				
				$CFG->o_method_suppress = true;
				$form_filters->selectInput('graph_name_column',$CFG->name_column_label,false,false,$this->name_columns);
				
				if ($this->mode != 'graph_pie') {
					$CFG->o_method_suppress = true;
					$form_filters->selectInput('graph_x_axis',$CFG->x_axis,false,false,$this->x_columns);
					
					$CFG->o_method_suppress = true;
					$form_filters->checkBox('graph_combine',$CFG->combine_label,false);
				}
			}
			
			if ($group) 
				$form_filters->endGroup();
			
			$CFG->o_method_suppress = true;
			$form_filters->HTML('<input type="hidden" name="mode" value="'.$this->mode.'" />');
			$CFG->o_method_suppress = true;
			$form_filters->HTML('<div class="clear"></div>');
			$CFG->o_method_suppress = true;
			$form_filters->submitButton('submit',$CFG->filter_submit_text,false,'not_method');
			$CFG->o_method_suppress = true;
			$form_filters->resetButton($CFG->grid_default_reset,false,'not_method');
			
			echo '
			<div class="grid_filters area">
				<div class="box_tl"></div>
				<div class="box_tr"></div>
				<div class="box_bl"></div>
				<div class="box_br"></div>
				<div class="t_shadow"></div>
				<div class="r_shadow"></div>
				<div class="b_shadow"></div>
				<div class="l_shadow"></div>
				<div class="box_b"></div>
				<div class="box_t"></div>
				<div class="contain">';
			
			$form_filters->display();
			
			echo '</div></div>';
		}
	}
	
	function pagination($page,$total_rows,$rows_per_page=0,$max_pages=0,$pagination_label=false,$target_elem=false) {
		global $CFG;
		
		$page = ($page > 0) ? $page : 1;
		$target_elem = ($target_elem) ? $target_elem : 'content';
		if (is_object($this)) {
			if ($this->max_pages == 1)
				return false;
			
			$rows_per_page = $this->rows_per_page;
			$max_pages = ($this->max_pages)? $this->max_pages : 10;
			$pagination_label = $this->pagination_label;
			$link_url = $CFG->url;
			$mode = $this->mode;
			$inset_id = $this->inset_id;
			$inset_id_field = $this->inset_id_field;
			$inset_i = $this->inset_i;
			$target_elem = ($this->inset_i > 0) ? 'inset_area_'.$this->inset_i : $target_elem;
			$grid_i = $this->i;
			$filter_results = $_REQUEST['form_filters'.$this->i];
		}
		else {
			$link_url = $CFG->url;
		}

		if (!($rows_per_page > 0))
			return false;
		
		if ($total_rows > $rows_per_page) {
			$num_pages = ceil($total_rows / $rows_per_page);
			$page_array = range(1,$num_pages);
			
			if ($max_pages > 0) {
				$p_deviation = ($max_pages - 1) / 2;
				$alpha = $page - 1;
				$alpha = ($alpha < $p_deviation) ? $alpha : $p_deviation;
				$beta = $num_pages - $page;
				$beta = ($beta < $p_deviation) ? $beta : $p_deviation;
				if ($alpha < $p_deviation) $beta = $beta + ($p_deviation - $alpha);
				if ($beta < $p_deviation) $alpha = $alpha + ($p_deviation - $beta);
			}
			
			if ($page != 1) 
				$first_page = Link::url($link_url,$CFG->first_page_text,false,array('page'.$grid_i=>1,'p_bypass'.$grid_i=>1,'mode'.$grid_i=>$mode,'inset_id'=>$inset_id,'inset_id_field'=>$inset_id_field,'inset_i'=>$inset_i,'form_filters'.$grid_i=>$filter_results,'search_fields'.$grid_i=>$_REQUEST['search_fields'.$grid_i]),true,$target_elem,'first');
			if ($page != $num_pages)
				$last_page = Link::url($link_url,$CFG->last_page_text,false,array('page'.$grid_i=>$num_pages,'p_bypass'.$grid_i=>1,'mode'.$grid_i=>$mode,'inset_id'=>$inset_id,'inset_id_field'=>$inset_id_field,'inset_i'=>$inset_i,'form_filters'.$grid_i=>$filter_results,'search_fields'.$grid_i=>$_REQUEST['search_fields'.$grid_i]),true,$target_elem,'last');
				
			$pagination = '<div class="pagination"><div style="float:left;">'.$first_page;
			foreach ($page_array as $p) {
				if (($p >= ($page - $alpha) && $p <= ($page + $beta)) || $max_pages == 0) {
					if ($p == $page) {
						$pagination .= ' <span>'.$p.'</span> ';
					}
					else {
						$pagination .= Link::url($link_url,$p,false,array('page'.$grid_i=>$p,'p_bypass'.$grid_i=>1,'mode'.$grid_i=>$mode,'inset_id'=>$inset_id,'inset_id_field'=>$inset_id_field,'inset_i'=>$inset_i,'form_filters'.$grid_i=>$filter_results,'search_fields'.$grid_i=>$_REQUEST['search_fields'.$grid_i]),true,$target_elem);
					}
				}
			}
			$pagination .= '</div>';
			
			if ($pagination_label) {
				$label = str_ireplace('[results]','<b>'.$total_rows.'</b>',$CFG->pagination_label);
				$label = str_ireplace('[num_pages]','<b>'.$num_pages.'</b>',$label);
				$pagination .= '<div style="float:right" class="pagination_label">'.$label.'</div>'; 
			}
			$pagination .= $last_page.'<div style="clear:both;height:0;">&nbsp;</div></div>';
			return $pagination;
		}
	}
	
	private function doOperation($name,$properties,$row,$data_set=false) {
		$formula = $properties['formula'];
		$caption = $properties['header_caption'];
		$braces = (strstr($formula,'['));
		
		foreach ($row as $o_name => $o_value) {
			$o_name = ($braces) ? '['.$o_name.']' : $o_name;
			$formula = str_replace($o_name,str_replace(',','',$o_value),$formula);
		}
		
		$result = eval("return $formula ;");
		
		if (!$data_set) {
			$this->cumulative[$name][] = $result;
			if ($properties['cumulative_function'] == 'sum') {
				$result = array_sum($this->cumulative[$name]);
			}
			elseif ($properties['cumulative_function'] == 'avg') {
				$result = array_sum($this->cumulative[$name])/count($this->cumulative[$name]);
			}
		}
		else {
			$this->cumulative[$name][$data_set][] = $result;
			if ($properties['cumulative_function'] == 'sum') {
				$result = array_sum($this->cumulative[$name][$data_set]);
			}
			elseif ($properties['cumulative_function'] == 'avg') {
				$result = array_sum($this->cumulative[$name][$data_set])/count($this->cumulative[$name][$data_set]);
			}
		}
		
		return $result;
	}
	
	function detectData(&$name,&$value,&$fields,$foreign_dates=false,$is_time=false) {
		global $CFG;
		
		if ($fields[$name]['Type'] == 'datetime' || @in_array($name,$foreign_dates) ) {
			if (date('H',strtotime($value)) > 0 || date('i',strtotime($value)) > 0)
				$time = " ".$CFG->default_time_format;
		
			if (strtotime($value) > 0) {
				if (!$is_time)
					$value = date($CFG->default_date_format.$time,strtotime($value));
				else
					$value = date($CFG->default_time_format,strtotime($value));
			}
			else
				$value = 'N/A';
		}
		elseif ($fields[$name]['Type'] == 'text') {
			$value = '<div class="clear"></div>'.nl2br($value);
		}
		elseif ($fields[$name]['Type'] == 'blob') {
			$value = '<div class="clear"></div>'.$value;
		}
		elseif ((($fields[$name]['Type'] == "enum('Y','N')" && $value == 'Y') || $value == 'Y') && $value !== 0) {
			$value = '<div class="y_icon"></div>';
		}
		elseif ((($fields[$name]['Type'] == "enum('Y','N')" && $value != 'Y') || $value == 'N') && $value !== 0) {
			$value = '<div class="n_icon"></div>';
		}
		elseif (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',$value)) {
			$value = '<span class="color_wrap"></span><div class="color_box1" style="background-color:'.$value.'"></div>';
		}
		elseif (preg_match('/([0-9]{1,3}){1}(\.[0-9]{1,2})?(%){1}/',$value) && (!$CFG->action)) {
			$value1 = intval($value);
			$value = '<img src="images/progressbar.gif" class="percent" style="background-position:'.(100 - $value1).'% 50%" /><div class="p_label">'.$value1.'%</div>';
		}
		elseif (stristr($name,'_interval')) {
			if ($value == 'day')
				$value = "{$CFG->cal_every} {$CFG->cal_day}";
			elseif ($value == 'sun')
				$value = "{$CFG->cal_every} {$CFG->cal_sun}";
			elseif ($value == 'mon')
				$value = "{$CFG->cal_every} {$CFG->cal_mon}";
			elseif ($value == 'tue')
				$value = "{$CFG->cal_every} {$CFG->cal_tue}";
			elseif ($value == 'wed')
				$value = "{$CFG->cal_every} {$CFG->cal_wed}";
			elseif ($value == 'thu')
				$value = "{$CFG->cal_every} {$CFG->cal_thur}";
			elseif ($value == 'fri')
				$value = "{$CFG->cal_every} {$CFG->cal_fri}";
			elseif ($value == 'sat')
				$value = "{$CFG->cal_every} {$CFG->cal_sat}";
			else
				$value = $value;
		}
		
		return $value;
	}
	
	private function getFilterResults() {
		if (empty($this->filters) && empty($this->filter_results))
			return false;
		
		$results = array();
		foreach ($this->filter_results as $f_name => $f_value) {
			if (!empty($this->filters[$f_name]))
				$results[$f_name] = $this->filters[$f_name];
			
			$results[$f_name]['results'] = $f_value;
		}
		
		return $results;
	}
}



?>