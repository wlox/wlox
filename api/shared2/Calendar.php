<?php
class Calendar {
	private $date,$y,$m,$d,$tables,$mode,$class,$fields,$target_elem_id,$data,$days_in_month,$filters,$filter_results,$tokenizers,$add_buttons,$start_time,$end_time,$mode_switching;
	private static $i;
	public $errors,$record_id;
	
	//mode can be month,week,day
	//start and end time NOT IMPLEMENTED
	function __construct($mode=false,$y=0,$m=0,$d=0,$class=false,$target_elem_id=false,$record_id=false,$print=false,$start_time=false,$end_time=false,$mode_switching_enabled=false) {
		global $CFG;
		
		$this->i = (Calendar::$i > 0) ? Calendar::$i : 1;
		$_SESSION['cal_'.$this->i.'_mode'] = ($_REQUEST['cal_'.$this->i.'_mode']) ? $_REQUEST['cal_'.$this->i.'_mode'] : $_SESSION['cal_'.$this->i.'_mode'];
		$this->mode = ($_REQUEST['cal_'.$this->i.'_mode']) ? $_REQUEST['cal_'.$this->i.'_mode'] : $_SESSION['cal_'.$this->i.'_mode'];
		$this->mode = ($this->mode) ? $this->mode : $mode;
		$this->y = ($y > 0) ? $y : $_REQUEST['cal_'.$this->i.'_y'];
		$this->m = ($m > 0) ? $m : $_REQUEST['cal_'.$this->i.'_m'];
		$this->d = ($d > 0) ? $d : $_REQUEST['cal_'.$this->i.'_d'];
		$this->print = ($print || $CFG->print);
		$this->target_elem_id = ($target_elem_id) ? $target_elem_id : 'content';
		$this->record_id = ($CFG->backstage_mode) ? $CFG->id : $record_id;
		$this->click_height = 50;
		$this->click_width = 153;
		$this->start_time = $start_time;
		$this->end_time = $end_time;
		$this->mode_switching = $mode_switching_enabled;
		
		if (!$this->d && !$this->m && !$this->y) {
			$this->y = date('Y');
			$this->m = date('m');
			$this->d = date('j');
			
			$this->date = time();
		} 
		else {
			if (!($this->y > 0)) $this->y = date('Y');
			if (!($this->m > 0)) $this->m = date('m');
			if (!($this->d > 0)) $this->d = date('j');	
		
			$this->date = mktime(0,0,0,$this->m, $this->d, $this->y);
		}
		
		if ($CFG->pm_editor) {
			echo '<ul>';
		}
		
		$form = new Form('form_filters');
		$this->filter_results = $form->info;
		if ($_REQUEST['tokenizers']) {
			foreach ($_REQUEST['tokenizers'] as $tokenizer) {
				$value1 = (is_array(unserialize($this->filter_results[$tokenizer]))) ? unserialize($this->filter_results[$tokenizer]) : $this->filter_results[$tokenizer];
				if (is_array($value1))
					$this->tokenizers[$tokenizer] = $value1;
				elseif (strlen($value1) > 0)
					$this->tokenizers[$tokenizer] = String::unFaux($value1);
					
				$this->filter_results[$tokenizer] = false;
			}
		}
	}
	
	function addTable($table,$sdate_field=false,$edate_field=false,$concat_char_for_title=false,$concat_char_for_details=false,$f_id_field=false,$color=false,$color_field=false,$filters=false,$stime_field=false,$etime_field=false,$int_field=false) {
		global $CFG;

		if ($int_field)
			$int_field = (stristr($int_field,'_interval')) ? $int_field : $int_field.'_interval';
			
		$this->tables[$table] = array(
			'sdate_field'=>$sdate_field,
			'edate_field'=>$edate_field,
			'stime_field'=>$stime_field,
			'etime_field'=>$etime_field,
			'int_field'=>$int_field,
			'concat_char_for_title'=>$concat_char_for_title,
			'concat_char_for_details'=>$concat_char_for_details,
			'table_fields'=>DB::getTableFields($table),
			'f_id_field'=>$f_id_field,
			'color'=>$color,
			'color_field'=>$color_field,
			'filters'=>$filters);
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'addTable');
			echo '<li>'.$table.' [addTable] '.$method_name.'</li>';
		}
	}
	
	function field($table,$name,$subtable=false,$in_title=false,$link_url=false,$subtable_fields=false,$subtable_fields_concat=false,$is_media=false,$class=false,$media_size=false,$f_id_field=false,$order_by=false,$order_asc=false,$link_is_tab=false,$target_elem_id=false,$link_id_field=false,$limit_is_curdate=false,$print_only=false,$print_caption=false) {
		global $CFG;
/*
		if (!array_key_exists($table,$this->tables)) {
			$this->errors[$table] = $CFG->grid_no_table_error;
			return false;
		}
*/
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'field');
			echo '<li>'.$table.' [field] '.$method_name.'</li>';
		}
		
		$rand = (@array_key_exists($name,$this->fields[$table])) ? 'lll'.rand(1,99) : false;
		$this->fields[$table][$name.$rand] = array(
			'name'=>$name,
			'subtable'=>$subtable,
			'in_title'=>$in_title,
			'link_url'=>$link_url,
			'is_media'=>$is_media,
			'subtable_fields'=>$subtable_fields,
			'subtable_fields_concat'=>$subtable_fields_concat,
			'class'=>$class,
			'thumb_amount'=>0,
			'media_amount'=>1,
			'media_size'=>$media_size,
			'method_id'=>$CFG->method_id,
			'f_id_field'=>$f_id_field,
			'order_by'=>$order_by,
			'order_asc'=>$order_asc,
			'link_is_tab'=>$link_is_tab,
			'target_elem_id'=>$target_elem_id,
			'link_id_field'=>$link_id_field,
			'limit_is_curdate'=>$limit_is_curdate,
			'print_only'=>$print_only,
			'print_caption'=>$print_caption);
	}
	
	function placeholder($table,$value,$in_title=false,$link_url=false,$class=false,$link_is_tab=false,$target_elem_id=false,$link_id_field=false,$print_only=false,$print_caption=false) {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'placeholder');
			echo '<li>'.$table.' [placeholder] '.$method_name.'</li>';
		}
		
		$this->fields[$table][] = array(
			'is_placeholder'=>true,
			'value'=>$value,
			'in_title'=>$in_title,
			'link_url'=>$link_url,
			'class'=>$class,
			'link_is_tab'=>$link_is_tab,
			'target_elem_id'=>$target_elem_id,
			'link_id_field'=>$link_id_field,
			'print_only'=>$print_only,
			'print_caption'=>$print_caption);
	}
	
	function addNewButton($table=false,$target_elem_id=false,$is_tab=false) {
		global $CFG;
		
		$table = (!$table) ? key($this->tables) : $table;
		$target_elem_id = ($target_elem_id) ? $target_elem_id : 'content';
		$this->add_buttons[] = array(
			'table'=>$table,
			'target_elem_id'=>$target_elem_id,
			'is_tab'=>$is_tab);
	}
	
	private function get($start,$end) {
		global $CFG;
		
		if (is_array($this->tables)) {
			foreach ($this->tables as $table => $fields) {
				$s_date = false;
				$e_date = false;
				
				if (!DB::tableExists($table)) {
					$this->errors[$table] = $CFG->grid_no_table_error;
				}
				
				$table_fields = $fields['table_fields'];
				if (is_array($table_fields)) {
					if (!empty($fields['sdate_field'])) {
						if (array_key_exists($fields['sdate_field'],$table_fields))
							$s_date = $fields['sdate_field'];
						else
							$this->errors[$fields['sdate_field']] = $CFG->grid_no_field_error;
					}
					if (!empty($fields['edate_field'])) {
						if (array_key_exists($fields['edate_field'],$table_fields))
							$e_date = $fields['edate_field'];
						else
							$this->errors[$fields['edate_field']] = $CFG->grid_no_field_error;
					}
					if (!empty($fields['stime_field'])) {
						if (array_key_exists($fields['stime_field'],$table_fields))
							$s_time = $fields['stime_field'];
						else
							$this->errors[$fields['stime_field']] = $CFG->grid_no_field_error;
					}
					if (!empty($fields['etime_field'])) {
						if (array_key_exists($fields['etime_field'],$table_fields))
							$e_time = $fields['etime_field'];
						else
							$this->errors[$fields['etime_field']] = $CFG->grid_no_field_error;
					}
					if (!empty($fields['int_field'])) {
						if (array_key_exists($fields['int_field'],$table_fields))
							$int = $fields['int_field'];
						elseif (array_key_exists($fields['int_field'],$table_fields))
							$int = $fields['int_field'];
						else
							$this->errors[$fields['int_field']] = $CFG->grid_no_field_error;
					}
					
					if (!$s_date || !$e_date) {
						foreach ($table_fields as $name=>$field) {
							if ($s_date)
								break;
								
							if ($field['Type'] == 'datetime') {
								$s_date = $name;
							}
						}
					}
				}
				
				if (is_array($this->fields[$table])) {
					$db_fields = array();
					foreach ($this->fields[$table] as $k => $info) {
						if (!$info['is_placeholder'])
							$db_fields[$k] = $info;
					}
					
					if ($s_date)
						$db_fields[$s_date] = array('name'=>$s_date);
					if ($e_date)
						$db_fields[$e_date] = array('name'=>$e_date);
					if ($s_time)
						$db_fields[$s_time] = array('name'=>$s_time);
					if ($e_time)
						$db_fields[$e_time] = array('name'=>$e_time);
					if ($int)
						$db_fields[$int] = array('name'=>$int);
					
					$fields['filters'] = (is_array($fields['filters'])) ? $fields['filters'] : array();
					$this->filter_results = (is_array($this->filter_results)) ? $this->filter_results : array();
					$fields['filters'] = array_merge($fields['filters'],$this->filter_results);
					$record_id = ($fields['f_id_field']) ? $this->record_id : 0;
					
					$datasets = array();
					if ($this->tokenizers) {
						foreach ($this->tokenizers as $t_name => $tokenizer) {
							if (is_array($tokenizer)) {
								foreach ($tokenizer as $t_id => $t_value) {
									$datasets[] = $t_id;
								}
							}
						}
					}
					else {
						$datasets = array(0=>$record_id);
					}

					foreach ($datasets as $set) {
						if (strstr($fields['f_id_field'],',')) {
							$f_id_parts = explode(',',$fields['f_id_field']);
							$f_parts = explode('.',$f_id_parts[(count($f_id_parts)-1)]);
							$db_fields['f_table_id'] = array(
							'name'=>'f_table_id',
							'formula'=>$f_parts[0].'.id',
							'run_in_sql'=>1);
						}
						$result = DB::get($table,$db_fields,0,0,false,false,false,$fields['filters'],$set,$fields['f_id_field'],false,false,$start,$end,$s_date,$e_date,1);
						if ($result) {
							foreach ($result as $row) {
								$row['table'] = $table;
								$data[$set][strtotime($row[$s_date])][] = $row;
							}
							@ksort($data[$set]);
						}
						else {
							$data[$set] = '';
						}
					}
				}
			}
		}
		return $data;
	}
	
	private function getEvents($s_time=false,$e_time=false,$day=0,$is_first=false,$is_last=false) {
		$is_first = (!$s_time && $day == 1) ? true : $is_first;
		$is_last = (!$s_time && $day == $this->days_in_month) ? true : $is_last;
		if (is_array($this->data)) {
			foreach ($this->data as $i => $dataset) {
				if (is_array($dataset)) {
					foreach ($dataset as $timestamp => $rows) {
						if (is_array($rows)) {
							foreach ($rows as $row) {
								$table = $row['table'];
								$edate_field = $this->tables[$table]['edate_field'];
								$stime_field = $this->tables[$table]['stime_field'];
								$int_field = $this->tables[$table]['int_field'];
								$tk = ($stime_field) ? strtotime(date('Y-m-d',$timestamp).' '.date('H:i:00', strtotime($row[$stime_field]))) : $timestamp;
								
								if ($s_time > 0 && $e_time > 0) {
									if ($timestamp < $e_time && (strtotime($row[$edate_field]) > $s_time || strtotime($row[$edate_field]) < mktime(0,0,0,1,1,'1980'))) {
										$data[$i][$tk][] = $row;
									}
								}
								elseif ($int_field) {
									if (($row[$int_field] == 'day' || $row[$int_field] == strtolower(date('D',mktime(0,0,0,$this->m,$day,$this->y)))) && ($timestamp <= mktime(23,59,59,$this->m,$day,$this->y) && (strtotime($row[$edate_field]) >= mktime(0,0,0,$this->m,$day,$this->y) || strtotime($row[$edate_field]) < mktime(0,0,0,1,1,'1980')))) {
										$tk = strtotime(date('Y-m-d',mktime(0,0,0,$this->m,$day,$this->y)).' '.date('H:i:00', strtotime($row[$stime_field])));
										$data[$i][$tk][] = $row;
									}
								}
								else {
									if ($timestamp <= mktime(23,59,59,$this->m,$day,$this->y) && (strtotime($row[$edate_field]) >= mktime(0,0,0,$this->m,$day,$this->y) || strtotime($row[$edate_field]) < mktime(0,0,0,1,1,'1980'))) {
										$data[$i][$tk][] = $row;
									}
								}
							}
						}
					}
				}
				else {
					$data[$i] = '';
				}
			}
			return $data;
		}
	}
	
	private function showEvents($events,$show_details=false,$s_time=false,$e_time=false,$day=0,$floating=false,$f_id=0) {
		global $CFG;
		
		$margin_top_cumulative = 0;
		if (is_array($events)) {
			$HTML = '';
			foreach ($events as $timestamp => $events) {
				if (is_array($events)) {
					foreach ($events as $event) {
						$table = $event['table'];
						$sdate_field = $this->tables[$table]['sdate_field'];
						$edate_field = $this->tables[$table]['edate_field'];
						$stime_field = $this->tables[$table]['stime_field'];
						$etime_field = $this->tables[$table]['etime_field'];
						$s_val = ($stime_field) ? date('Y-m-d',$timestamp).' '.date('H:i:00', strtotime($event[$stime_field])) : $event[$sdate_field];
						$e_val = ($etime_field) ? date('Y-m-d',$timestamp).' '.date('H:i:00', strtotime($event[$etime_field])) : $event[$edate_field];
						$color_field = $this->tables[$table]['color_field'];
						$color1 = (!empty($event[$color_field])) ? $event[$color_field] : $this->tables[$table]['color'];
						$color2 = String::hexDarker($color1,30);
						$color3 = String::hexDarker($color1,80);
		
						$top_open = (($s_time && $timestamp < $s_time) || ($day > 0 && date('Y-m-d',$timestamp) < date('Y-m-d',mktime(0,0,0,$this->m,$day,$this->y)))) ? 'top_open' : false;
						$bottom_open = (($s_time && $e_val > $e_time) || ($day > 0 && date('Y-m-d',strtotime($e_val)) > date('Y-m-d',mktime(23,59,59,$this->m,$day,$this->y)))) ? 'bottom_open' : false;
						$no_bg = (!$edate_field) ? 'no_bg' : false;
						$float_class = ($floating) ? 'floating' : '';
						
						if ($floating) {
							$height = (strtotime($e_val) - (strtotime($s_val))) / 1800;
							$overflow_top = ((strtotime($s_val)) - mktime(0,0,0,$this->m,$day,$this->y)) / 1800;
							$overflow_bottom = ((strtotime($e_val)) - mktime(0,0,0,$this->m,$day+1,$this->y)) / 1800;
							$overflow_top_normalized = ($overflow_top < 0) ? $overflow_top : 0;
							$overflow_bottom_normalized = ($overflow_bottom > 0) ? $overflow_bottom : 0;
							
							if (($overflow_top < 0) && ($overflow_bottom > 0))
								$visible_area = 48;
							elseif (($overflow_top >= 0) && ($overflow_bottom <= 0))
								$visible_area = $height;
							elseif ($overflow_top < 0) 
								$visible_area = abs($height + $overflow_top);
							elseif ($overflow_bottom > 0) 
								$visible_area = abs($height - $overflow_bottom);
								
							$clicks_style = 'height:'.(($height * $this->click_height) - 3).'px; top:'.(($overflow_top - $margin_top_cumulative) * $this->click_height).'px;';
							$title_style = 'style="margin-top:'.(((($visible_area/2) - $overflow_top_normalized) * $this->click_height) - 10).'px;"';
						}
						
						
						if (is_array($this->fields[$table])) {
							foreach ($this->fields[$table] as $field) {
								$name = $field['name'];
								if (($name == $color_field && !empty($color_field)) || $field['print_only'])
									continue;
		
								$value = ($field['is_placeholder']) ? $field['value'] : Grid::detectData($name,$event[$name],$this->tables[$table]['table_fields']);
								$value = ($field['class']) ? '<span class="'.$field['class'].'">'.$value.'</span>' : '<span>'.$value.'</span>';
		
								if ($field['link_url'] && !empty($value)) {
									$link_id = ($field['link_id_field']) ? $field['link_id_field'] : $event['id'];
									$action = ($CFG->backstage_mode) ? '&action=record' : '';
									$target_elem_id = ($field['target_elem_id']) ? $field['target_elem_id'] : $this->target_elem_id;
									$value = Link::url($field['link_url'],$value,"id={$event['id']}&is_tab={$field['link_is_tab']}{$action}",false,false,$target_elem_id);
								}
								
								if ($field['in_title']) {
									$title_fields[] = $value;
								}
								else {
									$details_fields[] = $value;
								}
							}
						}

						$HTML .= '
						<div class="event '.$float_class.' '.$top_open.' '.$bottom_open.' '.$no_bg.'" style="'.$clicks_style.' '.(($no_bg) ? '' : "background-color:{$color1};border:{$color2} 1px solid;color:{$color3};").'">
							<div class="title" '.$title_style.'>'.@implode($this->tables[$table]['concat_char_for_title'],$title_fields).'</div>
							'.(($show_details) ? '<div class="details">'.@implode($this->tables[$table]['concat_char_for_details'],$details_fields).'</div>':'').'
							<input type="hidden" id="f_id_field" value="'.$this->tables[$table]['f_id_field'].'" />
							<input type="hidden" id="table" value="'.$table.'" />
							<input type="hidden" id="f_id" value="'.$f_id.'" />
							<input type="hidden" id="id" value="'.$event['id'].'" />
							<input type="hidden" id="f_table_id" value="'.$event['f_table_id'].'" />
							<input type="hidden" class="total_y" id="total_y" value="0" />
							<input type="hidden" id="sdate_field" value="'.(($stime_field) ? $stime_field : $sdate_field).'" />
							<input type="hidden" id="edate_field" value="'.(($etime_field) ? $etime_field : $edate_field).'" />
						</div>';
						
						unset($title_fields);
						unset($details_fields);
					}
				}
			}
		}
		
		return $HTML;
	}
	
	private function printEvents($events,$s_time=false,$e_time=false,$day=0,$f_id=0) {
		global $CFG;
		
		if (is_array($events)) {
			$HTML = '';

			foreach ($events as $timestamp => $events) {
				if (is_array($events)) {
					foreach ($events as $event) {
						$table = $event['table'];
						$sdate_field = $this->tables[$table]['sdate_field'];
						$edate_field = $this->tables[$table]['edate_field'];
						$color_field = $this->tables[$table]['color_field'];
						
						if (is_array($this->fields[$table])) {
							foreach ($this->fields[$table] as $field) {
								$name = $field['name'];
								if ($name == $color_field)
									continue;

								$value = ($field['is_placeholder']) ? $field['value'] : Grid::detectData($name,$event[$name],$this->tables[$table]['table_fields']);
								$value = ($field['print_caption']) ? '<div class="print_line"><span class="print_label">'.$field['print_caption'].'</span>:<span>'.$value.'</span></div>' : '<div class="print_line">'.$value.'</div>';
								if ($field['in_title']) {
									$title_fields[] = $value;
								}
								else {
									$details_fields[] = $value;
								}
							}
						}
		
						$HTML .= '
						<div class="cal_print_item">
							<div class="title">'.@implode('',$title_fields).'<div class="clear"></div></div>
							<div class="details">'.@implode('',$details_fields).'<div class="clear"></div></div>
						</div>';
						
						unset($title_fields);
						unset($details_fields);
					}
				}
			}
		}
		
		return $HTML;
	}
	
	private function show_errors() {
		if ($this->errors) {
			echo '<ul class="errors">';
			foreach ($this->errors as $name => $error) {
				echo '<li>'.ucfirst(str_ireplace('[field]',$name,$error)).'</li>';
			}
			echo '</ul>';
		}
	}
	
	function display() {
		global $CFG;
		
		if (!is_array($this->tables) || !is_array($this->fields))
			return false;
		
		if ($CFG->pm_editor) {
			echo '</ul>';
		}	
		
		$mode = $this->mode;
		$date = $this->date;
		$y = $this->y;
		$m = $this->m;
		$d = $this->d;

		$this->days_in_month = cal_days_in_month(0, $m, $y);
		
		if (!$mode || $mode == 'month') {
			$first_day = mktime(0,0,0,$m,1,$y);
			$title = date('F', $first_day).' '.$y;
			$day_of_week = date('w',$first_day);
			$days_array = range(1,$this->days_in_month);
			$start = $first_day;
			$end = mktime(23,59,59,$m,$this->days_in_month,$y);
			
			$next = strtotime('+1 month',$first_day);
			$next_5 = strtotime('+5 month',$first_day);
			$prev = strtotime('-1 month',$first_day);
			$prev_5 = strtotime('-5 month',$first_day);
		}
		elseif ($mode == 'week') {
			$day_of_week = date('w',$date);
			$first_day = date('j',strtotime('-'.$day_of_week.' day',$date));
			$start = strtotime('-'.$day_of_week,$date);
			$end = strtotime('+'.(6 - $day_of_week),$date);
			$title = $CFG->cal_week_from.' '.date($CFG->default_date_format,strtotime('-'.$day_of_week.' day',$date)).' '.$CFG->cal_week_until.' '.date($CFG->default_date_format,strtotime('+'.(6-$day_of_week).' day',$date));
			
			$next = strtotime('+1 week',$first_day);
			$next_5 = strtotime('+5 week',$first_day);
			$prev = strtotime('-1 week',$first_day);
			$prev_5 = strtotime('-5 week',$first_day);
			
			for ($i=0;$i<=6;$i++) {
				$days_array[] = date('j',strtotime("+$i day",$start));
			}
		}
		elseif ($mode == 'day') {
			$refer_mode = $_REQUEST['cal_'.$this->i.'_refer_mode'];
			$s_hour = ($this->start_time) ? $this->start_time : 0;
			$e_hour = ($this->end_time) ? $this->end_time : 23;
			$start = mktime(0,0,0,$m,$d,$y);
			$end = mktime(23,59,59,$m,$d,$y);
			$title = date('D F j, Y',$start);
			
			$next = strtotime('+1 day',$start);
			$next_5 = strtotime('+5 day',$start);
			$prev = strtotime('-1 day',$start);
			$prev_5 = strtotime('-5 day',$start);
			$days_array[] = $this->date;
		}
		$this->data = self::get($start,$end);
		
		if (!$this->print) {
			$day_class = ($mode == 'day') ? 'calendar_day' : '';
			$HTML = "
			<div class=\"area full_box calendar $class $day_class\" id=\"cal_{$this->i}\">
				<h2>$title</h2>
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
				<div class=\"grid_buttons\"><div class=\"button before\"></div>";
			
				if (is_array($this->add_buttons)) {
					foreach ($this->add_buttons as $button) {
						$string = $button['table'];
						if (substr($string,-1) == 's') {
							$string = substr($string,0,-1);
						}
						
						$HTML .= Link::url($button['table'],'<div class="add_new"></div>'.$CFG->add_new_caption.' '.ucfirst($string),'&action=form&is_tab='.$button['is_tab'],false,false,$button['target_elem_id'],'button');
					}
				}
			
				$HTML .= ((User::permission(0,0,$CFG->url) > 1) ? "<a href=\"#\" onclick=\"saveCalOrder()\" class=\"button\"><div class=\"save\"></div> $CFG->save_caption</a>" : '')
				.'<a href="#" onclick="printCal('.$this->i.')" class="button"><div class="print"></div>'.$CFG->print_caption.'</a>';
				
				if ($this->mode_switching && ($mode == 'day' || $mode == 'week'))
					$HTML .= Link::url($CFG->url,'<div class="cal_icon"></div>'.$CFG->cal_view_month,false,array('cal_'.$this->i.'_y'=>date('Y',$start),'cal_'.$this->i.'_m'=>date('n',$start),'cal_bypass'=>1,'bypass'=>1,'cal_'.$this->i.'_mode'=>'month'),true,'cal_'.$this->i,'button');
				
				$HTML .= '
				<div class="button after"></div></div><div class="contain">'
				.Link::url($CFG->url,'hidden',false,array('cal_'.$this->i.'_y'=>$this->y,'cal_'.$this->i.'_m'=>$this->m,'cal_'.$this->i.'_d'=>$this->d,'cal_bypass'=>1,'bypass'=>1,'mode'=>$mode,'print'=>1),true,false,'cal_print_link',false,'_blank')."
				<table cellspacing=\"0\">
				<tr>
					<th colspan=\"".((!$mode || $mode == 'month') ? 7 : (($mode == 'week') ? 8 : 2))."\">
						<div>
							<div onclick=\"synchCalDates(".date('Y',$prev_5).",".date('n',$prev_5).",".date('j',$prev_5).")\" class=\"n1 n\">".Link::url($CFG->url,'<<',false,array('cal_'.$this->i.'_y'=>date('Y',$prev_5),'cal_'.$this->i.'_m'=>date('n',$prev_5),'cal_'.$this->i.'_d'=>date('j',$prev_5),'cal_bypass'=>1,'bypass'=>1,'mode'=>$mode),true,'cal_'.$this->i)."</div>
							<div onclick=\"synchCalDates(".date('Y',$prev).",".date('n',$prev).",".date('j',$prev).")\" class=\"n2 n\">".Link::url($CFG->url,'<',false,array('cal_'.$this->i.'_y'=>date('Y',$prev),'cal_'.$this->i.'_m'=>date('n',$prev),'cal_'.$this->i.'_d'=>date('j',$prev),'cal_bypass'=>1,'bypass'=>1,'mode'=>$mode),true,'cal_'.$this->i)."</div>
							<div onclick=\"synchCalDates(".date('Y',time()).",".date('n',time()).",".date('j',time()).")\" class=\"n_today n\">".Link::url($CFG->url,$CFG->cal_today,false,array('cal_'.$this->i.'_y'=>date('Y',time()),'cal_'.$this->i.'_m'=>date('n',time()),'cal_'.$this->i.'_d'=>date('j',time()),'cal_bypass'=>1,'bypass'=>1,'mode'=>$mode),true,'cal_'.$this->i)."</div>
							<div onclick=\"synchCalDates(".date('Y',$next).",".date('n',$next).",".date('j',$next).")\" class=\"n3 n\">".Link::url($CFG->url,'>',false,array('cal_'.$this->i.'_y'=>date('Y',$next),'cal_'.$this->i.'_m'=>date('n',$next),'cal_'.$this->i.'_d'=>date('j',$next),'cal_bypass'=>1,'bypass'=>1,'mode'=>$mode),true,'cal_'.$this->i)."</div>
							<div onclick=\"synchCalDates(".date('Y',$next_5).",".date('n',$next_5).",".date('j',$next_5).")\" class=\"n4 n\">".Link::url($CFG->url,'>>',false,array('cal_'.$this->i.'_y'=>date('Y',$next_5),'cal_'.$this->i.'_m'=>date('n',$next_5),'cal_'.$this->i.'_d'=>date('j',$next_5),'cal_bypass'=>1,'bypass'=>1,'mode'=>$mode),true,'cal_'.$this->i)."</div>
						</div>
					</th>
				</tr>
			";
		
			if ($this->tokenizers && $this->mode == 'day') {
				foreach ($this->tokenizers as $t_name => $t_values) {
					if (is_array($t_values)) {
						$HTML .= '<tr><td class="datasets" colspan="'.((!$mode || $mode == 'month') ? 7 : (($mode == 'week') ? 8 : 2)).'">';
						foreach ($t_values as $t_value) {
							$HTML .= '<div>'.$t_value.'</div>';
						}
						$HTML .= "</td></tr>";
					}
				}
			}
			
			if (!$mode || $mode == 'month') {
				$alt = ($alt) ? '' : 'class="alt"';
				$HTML .= "
					<tr $alt>
						<td class=\"c_day\">$CFG->cal_sun</td>
						<td class=\"c_day\">$CFG->cal_mon</td>
						<td class=\"c_day\">$CFG->cal_tue</td>
						<td class=\"c_day\">$CFG->cal_wed</td>
						<td class=\"c_day\">$CFG->cal_thur</td>
						<td class=\"c_day\">$CFG->cal_fri</td>
						<td class=\"c_day\">$CFG->cal_sat</td>
					</tr>";
				
				$day_count = 1;
				$HTML .= "<tr>";
				$i = $day_of_week;
				while ($i > 0){
					$HTML .= '<td class="inactive"></td>';
					$i--;
					$day_count++;
				} 
			
				foreach($days_array as $day_num) {
					$events = self::getEvents(false,false,$day_num);
					$current = ($day_num == date('j') && $m == date('n') && $y == date('Y')) ? 'current':'';
					$HTML .= "
					<td class=\"$current\" onmouseover=\"calCellHover(this)\" onmouseout=\"calCellOut(this)\" onclick=\"calZoomDay({$this->i},'{$CFG->url}',{$CFG->is_tab},{$y},{$m},{$day_num})\">
						<div class=\"wrap\">
							<div class=\"day_num\">$day_num</div>";
					if (is_array($events)) {
						foreach ($events as $k => $dataset) {
							@reset($this->tokenizers);
							$tk = @key($this->tokenizers);
							if (count($this->tokenizers[$tk]) > 1)
								$HTML .= '<div class="token_label">'.$this->tokenizers[$tk][$k].'</div>';
							
							$HTML .= self::showEvents($dataset,false,false,false,$day_num,false,$k);
						}
					}
					$HTML .= "
						</div>
					 </td>";
					$day_count++;
		
					if ($day_count > 7) {
						$HTML .= "</tr><tr>";
						$day_count = 1;
					}
				}
				
				while ($day_count >1 && $day_count <=7){
					$HTML .= '<td class="inactive"></td>';
					$day_count++;
				}
				$HTML .= '</tr>';
			}
			elseif ($mode == 'week') {
				$alt = ($alt) ? '' : 'class="alt"';
				$HTML .= "
				<tr $alt>
					<td class=\"c_day\"></td>
					<td class=\"c_day\">'.$CFG->cal_sun.'</td>
					<td class=\"c_day\">'.$CFG->cal_mon.'</td>
					<td class=\"c_day\">'.$CFG->cal_tue.'</td>
					<td class=\"c_day\">'.$CFG->cal_web.'</td>
					<td class=\"c_day\">'.$CFG->cal_thur.'</td>
					<td class=\"c_day\">'.$CFG->cal_fri.'</td>
					<td class=\"c_day\">'.$CFG->cal_sat.'</td>
				</tr>
				<tr>";
	
				foreach($days_array as $day_num) {
					$events = self::getEvents(false,false,$day_num);
					reset($events);
					$first_key = key($events);
					$current = ($day_num == date('j') && $m == date('n') && $y == date('Y')) ? 'current':'';
					
					$HTML .= "
					<td class=\"$current\" onmouseover=\"calCellHover(this)\" onmouseout=\"calCellOut(this)\" onclick=\"calZoomDay({$this->i},'{$CFG->url}',{$CFG->is_tab},{$y},{$m},{$day_num})\">
						<div class=\"wrap\">
							<div class=\"day_num\">$day_num</div> 
							".self::showEvents($events[$first_key],false,false,false,$day_num)."
						</div>
					 </td>";
				}
	
				$HTML .= '</tr>';
			}
			elseif ($mode == 'day') {
				$hour = 0;
				while ($hour <= 23) {
					$alt = ($alt) ? '' : 'class="alt"';
					$HTML .= '
					<tr '.$alt.'>
						<td class="time" rowspan="2">
							'.date($CFG->default_time_format,strtotime("{$y}-{$m}-{$d} {$hour}:00:00")).'
						</td>';
					
					$HTML .= '<td class="row"></td></tr><tr '.$alt.'><td class="row"></td></tr>';
					$hour++;
				}
			}
			$HTML .= "</table>";
		}
		
		if ($mode == 'day') {
			$events = self::getEvents(false,false,$d);
			if (is_array($events)) {
				if (!$this->print) {
					$container_width = count($events) * $this->click_width + 2; 
					$HTML .= '<div class="all_floats '.((!$this->tokenizers) ? 'one_float' : '').'" style="width:'.$container_width.'px">';
					$j = 0;
					foreach ($events as $k => $dataset) {
						$margin_left = $j * $this->click_width;
						$HTML .= '
						<div class="float_contain '.(($this->tokenizers) ? 'float_topspace' : '').'" id="float_contain_'.$k.'" style="margin-left:'.$margin_left.'px">
							<input type="hidden" id="record_id" value="'.$k.'"/>
							'.self::showEvents($dataset,1,false,false,$d,1,$k).'
						</div>';
						$j++;
					}
					$HTML .= '
					<script type="text/javascript">
						initializeCalFloats();
					</script>';
					$HTML .= '</div>';
				}
				else {
					$i = 0;
					if ($this->tokenizers) {
						$tokenizers = $this->tokenizers;
						$first_key = key($tokenizers);
						$labels = $tokenizers[$first_key];
					}
					
					foreach ($events as $k => $dataset) {
						if (is_array($dataset)) { 
							if ($labels)
								$HTML .= '<div class="print_caption">'.current($labels).'</div>';
						}
						$HTML .= $this->printEvents($dataset,false,false,$d,$k);
						$HTML .= '<div class="page-break"></div>';
						@next($labels);
						$i++;
					}
					unset($labels);
				}
			}
		}
		
		$HTML .= '<div class="clear"></div></div></div>';
		
		if (!$_REQUEST['cal_bypass']) { 
			self::show_filters();		
			
			/*
			if ($this->mode == 'day' && !$this->print) {
				echo '
				<div class="grid_operations">
					<span class="button" onclick="saveCalOrder()">'.$CFG->save_icon.' '.$CFG->save_button.'</span>
					<span class="button" onclick="printCal('.$this->i.')">'.$CFG->print_icon.' '.$CFG->print_button.'</span>
					<div class="clear"></div>
				</div>';
			}
			*/
		}
		
		self::show_errors();
		echo $HTML;
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
	
	function filterTokenizer($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false) {
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
	
	function filterCheckbox($field_name,$caption=false,$checked=false,$class=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'type'=>'checkbox',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'checked' => $checked,
			'class'=> $class,
			'method_id'=>$CFG->method_id);
	}
	
	function filterSelect($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false,$f_id_field=false,$depends_on=false) {
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
			'depends_on'=>$depends_on);
	}
	
	function show_filters() {
		global $CFG;
		
		if ($this->inset_id > 0)
			return false;
		
		if (is_array($this->filters)) {
			$form_filters = new Form('form_filters',false,'GET','form_filters cal',false);
			$form_filters->show_errors();
			
			foreach ($this->filters as $filter) {
				$name = $filter['field_name'];
				$caption = (!empty($filter['caption'])) ? $caption : $name;
				
				if (($filter['type'] != 'radio' && $filter['type'] != 'start_date' && $filter['type'] != 'end_date') && $group) {
					$form_filters->endGroup();
					$group = false;
				}
				switch ($filter['type']) {
					case 'autocomplete':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterAutocomplete';
						$form_filters->autoComplete($name,$filter['caption'],false,$filter['value'],false,$filter['options_array'],$filter['subtable'],$filter['subtable_fields'],false,false,$filter['class']);
						break;
					case 'tokenizer':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterTokenizer';
						$row = DB::getRecord($filter['subtable'],$this->record_id,0,1);
						if (is_array($filter['subtable_fields'])) {
							foreach ($filter['subtable_fields'] as $field) {
								$row1[] = $row[$field];
							}
							$show = implode(' ',$row1);
						}
						else {
							$show = implode(' ',$row);
						}
						
						$filter['value'] = (!empty($filter['value']) && $this->mode == 'day') ? $filter['value'] : array($this->record_id=>$show);
						$form_filters->autoComplete($name,$filter['caption'],false,$filter['value'],false,$filter['options_array'],$filter['subtable'],$filter['subtable_fields'],false,false,$filter['class'],false,false,false,false,false,false,false,false,false,false,false,1);
						break;
					case 'select':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterSelect';
						$form_filters->selectInput($name,$filter['caption'],false,false,$filter['options_array'],$filter['subtable'],$filter['subtable_fields'],false,false,$filter['class'],false,false,$filter['f_id_field'],false,$filter['depends_on']);
						break;
					case 'checkbox':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterCheckbox';
						$form_filters->checkBox($name,$filter['caption'],false,false,$filter['class'],false,false,$filter['checked']);
						break;
				}
			}
			
			if ($group) 
				$form_filters->endGroup();
				
			$CFG->o_method_suppress = true;
			$form_filters->HTML('
			<input type="hidden" id="cal_mode" name="mode" value="'.$this->mode.'" />
			<input type="hidden" id="cal_y" name="'.'cal_'.$this->i.'_y'.'" value="'.$this->y.'" />
			<input type="hidden" id="cal_m" name="'.'cal_'.$this->i.'_m'.'" value="'.$this->m.'" />
			<input type="hidden" id="cal_d" name="'.'cal_'.$this->i.'_d'.'" value="'.$this->d.'" />
			');
			$CFG->o_method_suppress = true;
			$form_filters->submitButton('submit',$CFG->filter_submit_text,false,'not_method');
			$CFG->o_method_suppress = true;
			$form_filters->resetButton('Reset',false,'not_method');
			$form_filters->display();
		}
	}
}
?>