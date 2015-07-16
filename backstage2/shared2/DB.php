<?php
class DB {
	public static $errors, $random_ids;
	
	public static function get($table,$fields,$page=0,$per_page=0,$order_by=false,$order_asc=false,$count=false,$filter_results=false,$f_id=false,$f_id_field=false,$get_total=false,$get_average=false,$s_date=false,$e_date=false,$s_date_field=false,$e_date_field=false,$calendar_mode=false,$filters=false,$group_by=false,$no_group_by=false) {
		global $CFG;
		
		if (!self::tableExists($table)) {
			$this->errors[$table] = $CFG->grid_no_table_error;
			self::show_errors();
			return false;
		}

		$page = (!$page || $page < 1) ? 1 : (int) $page;
		$table_fields = self::getTableFields($table);
		$subtables = self::getSubtables($table);
		$subtables = (!$subtables) ? array() : $subtables;
		$start_row = $per_page * ($page-1);
		$remote_i = 0;
		
		if (!$get_total || !$get_average) {
			$db_fields[] = "{$table}.id AS id";
		}

		if (!$table_fields) {
			$this->errors[$table] = $CFG->grid_no_table_error;
			self::show_errors();
			return false;
		}

		if (is_array($filter_results)) {
			foreach ($filter_results as $f_result => $f_value) {
				if ($_REQUEST['subtables'][$f_result]['subtable']) {
					$fields[$f_result] = array(
						'subtable'=>$_REQUEST['subtables'][$f_result]['subtable'],
						'subtable_fields'=>$_REQUEST['subtables'][$f_result]['subtable_fieldls'],
						'f_id_field'=>$_REQUEST['subtables'][$f_result]['f_id_field'],
						'filter_result'=>1,
						'f_value'=>$f_value['results']
					);
					unset($filter_results[$f_result]);
				}
			}
		}
		
		if (is_array($fields)) {
			foreach ($fields as $name => $field) {
				if ($field['is_op'] && !$field['run_in_sql'])
					continue;
				
				$key = $name;
				if (strstr($name,'lll')) {
					$name_parts = explode('lll',$name);
					$name = $name_parts[0];
				}

				$is_field = array_key_exists($name,$table_fields) || (is_array($field['subtable_fields']) && $CFG->in_file_manager);
				$has_subtable = in_array($field['subtable'],$subtables);
				$has_table = self::tableExists($field['subtable']);
				$subtable = $field['subtable'];
				$s_fields = array();
				
				if ($field['aggregate_function'] == 'page_total' || $field['aggregate_function'] == 'both_total')
					$page_total[$key] = 0; 
				if ($field['aggregate_function'] == 'page_avg' || $field['aggregate_function'] == 'both_avg')
					$page_avg[$key] = array();
				if ($field['aggregate_function'] == 'grand_total' || $field['aggregate_function'] == 'both_total')
					$grand_total[$key] = true;
				if ($field['aggregate_function'] == 'grand_avg' || $field['aggregate_function'] == 'both_avg')
					$grand_avg[$key] = true;
					
				if ($field['run_in_sql']) {
					$formula1 = "{$field['formula']} AS `$name`";
					/*
					$db_fields[] = "{$field['formula']} AS $name";
					$formulas[] = $field['formula'];
					*/
					if ($field['join_path']) {
						if (!strstr($field['join_path'],',')) {
							$foreign_tables[$subtable] = $field['join_path'];
							if ($field['filter_result']) {
								$filter_results[$subtable] = $field['f_value'];	
							}
						}
						else {
							$join_path = explode(',',$field['join_path']);
							if (is_array($join_path)) {
								$join_table = false;
								if (strstr($field['order_by'],'.')) {
									$otparts = explode('.',$field['order_by']);
									$foreign_order_table = $otparts[0];
								}
								else {
									$foreign_order_table = false;
									$otparts = false;
								}
								$i1 = 1;
								$c1 = count($join_path);
								foreach ($join_path as $join_field) {
									$join_field_parts = explode('.',$join_field);
									$join_table = $join_field_parts[0];
									$j_field = $join_field_parts[1];
									$remote_tables[$remote_i][$join_table][] = $j_field;
									
									if ($i1 == $c1)
										$remote_tables[$remote_i][$join_table]['join_condition'] = $field['join_condition'];
									
									$i1++;
								}
								if ($field['filter_result']) {
									$join_table1 = $join_table.$remote_i.'.'.$j_field;
									$filter_results[$join_table1] = $field['f_value'];
								}
								$formula1 = str_replace($join_table,$join_table.$remote_i,$formula1);
							}
						}
					}
					else {
						$formulas[] = $formula1;
					}
					
					$db_fields[] = $formula1;
					continue;
				}
				
				if (!$is_field && !$has_subtable && !$has_subtable && empty($field['is_media']) && empty($field['f_id_field'])) {
					//$this->errors[$key] = $CFG->grid_no_field_error;
					unset($fields[$k]);
					continue;
				}
				elseif ($is_field && !$has_subtable && !$has_table) {
					if (is_array($field['subtable_fields'])) {
						foreach ($field['subtable_fields'] as $s_field) {
							$f_subtable = ($field['subtable']) ? '_'.$field['subtable'] : '';
							$s_fields[] = "{$table}{$f_subtable}.{$s_field}";
						}
						$db_fields[] = "CONCAT_WS('$concat_char',".implode(',',$s_fields).") AS `$name`";
					}	
					else {
						$db_fields[] = "{$table}.{$name} AS `$name`";
					}
				}
				elseif ($has_subtable || $has_table) {
					$subtable_fields = (!is_array($field['subtable_fields']) && !empty($field['subtable_fields'])) ? array($field['subtable_fields']) : $field['subtable_fields'];
					$concat_char = (!empty($field['subtable_fields_concat'])) ? $field['subtable_fields_concat'] : ' ';
					$concat_char = (!$subtable_fields) ? '|||' : $concat_char;
					
					if ($is_field && !$has_subtable && $has_table) {
						$subtable_fields = (!$subtable_fields) ? self::getTableFields($field['subtable'],true) : $subtable_fields;
						$fields[$key]['subtable_fields'] = $subtable_fields;
						if (@array_key_exists($subtable,$join_tables)) {
							//if something gets messed up, it's probably this
							$alias = 'a'.rand(1,99);
							$j_table = $subtable.$alias;
						}
						else {
							$j_table = $subtable;
						}
						
						$int_fields = self::getFieldsLikeType($field['subtable'],'int');
						$int_exists = array();
						$dou_fields = self::getFieldsLikeType($field['subtable'],'double');
						$dou_exists = array();
						foreach ($subtable_fields as $s_field) {
							$s_fields[] = "{$j_table}.{$s_field}";
							$int_exists[] = @array_key_exists($s_field,$int_fields);
							$dou_exists[] = @array_key_exists($s_field,$dou_fields);
						}
						if (count($subtable_fields) == array_sum($int_exists)) {
							$cast1 = "CAST(";
							$cast2 = " AS DECIMAL)";
						}
						elseif (count($subtable_fields) == array_sum($dou_exists)) {
							$cast1 = "CAST(";
							$cast2 = " AS DECIMAL(10,2))";
						}
						else{
							$cast1 = false;
							$cast2 = false;
						}
						
						$db_fields[] = "{$cast1}CONCAT_WS('$concat_char',".implode(',',$s_fields)."){$cast2} AS `$key`";
						$db_fields[] = "{$j_table}.id AS {$key}_id";
						$j_field = ($field['f_id_field']) ? array($name,$field['f_id_field']) : $name;
						$f_field = ($field['f_id_field']) ? $field['f_id_field'] : 'id';
						
						if (@array_key_exists($subtable,$join_tables)) {
							$join_tables[$subtable.'|'.$alias] = $j_field;
							if ($field['filter_result'])
								$filter_results[$subtable.'|'.$alias.'.'.$f_field] = $field['f_value'];
						}
						else {
							$join_tables[$subtable] = $j_field;
							if ($field['filter_result'])
								$filter_results[$subtable.'.'.$f_field] = $field['f_value'];
						}
					}
					elseif (!$is_field && $has_subtable && $has_table) {
						$subtable_fields = (!$subtable_fields) ? self::getTableFields($field['subtable'],true) : $subtable_fields;
						$fields[$key]['subtable_fields'] = $subtable_fields;
						
						$int_fields = self::getFieldsLikeType($field['subtable'],'int');
						$int_exists = array();
						$dou_fields = self::getFieldsLikeType($field['subtable'],'double');
						$dou_exists = array();
						foreach ($subtable_fields as $s_field) {
							$s_fields[] = "{$field['subtable']}.{$s_field}";
							$int_exists[] = @array_key_exists($s_field,$int_fields);
							$dou_exists[] = @array_key_exists($s_field,$dou_fields);
						}
						if (count($subtable_fields) == array_sum($int_exists)) {
							$cast1 = "CAST(";
							$cast2 = " AS DECIMAL)";
						}
						elseif (count($subtable_fields) == array_sum($dou_exists)) {
							$cast1 = "CAST(";
							$cast2 = " AS DECIMAL(10,2))";
						}
						else{
							$cast1 = false;
							$cast2 = false;
						}
						
						$db_fields[] = "{$cast1}GROUP_CONCAT(DISTINCT CONCAT_WS('$concat_char',".implode(',',$s_fields).") SEPARATOR ', '){$cast2} AS `$name`";
						$db_fields[] = "{$field['subtable']}.id AS {$name}_id";
						$double_join_tables[$subtable] = $name;
					}
					elseif (!$is_field && $has_subtable && !$has_table) {
						if (empty($field['is_media'])) {
							$subtable_fields = (!$subtable_fields) ? self::getTableFields("{$table}_{$field['subtable']}",true) : $subtable_fields;
							$fields[$key]['subtable_fields'] = $subtable_fields;
							
							$int_fields = self::getFieldsLikeType($field['subtable'],'int');
							$int_exists = array();
							$dou_fields = self::getFieldsLikeType($field['subtable'],'double');
							$dou_exists = array();
							foreach ($subtable_fields as $s_field) {
								$s_fields[] = "{$table}_{$field['subtable']}.{$s_field}";
								$int_exists[] = array_key_exists($s_field,$int_fields);
								$dou_exists[] = array_key_exists($s_field,$dou_fields);
							}
							if (count($subtable_fields) == array_sum($int_exists)) {
								$cast1 = "CAST(";
								$cast2 = " AS DECIMAL)";
							}
							elseif (count($subtable_fields) == array_sum($dou_exists)) {
								$cast1 = "CAST(";
								$cast2 = " AS DECIMAL(10,2))";
							}
							else{
								$cast1 = false;
								$cast2 = false;
							}
							$db_fields[] = "{$cast1}GROUP_CONCAT(DISTINCT CONCAT_WS('$concat_char',".implode(',',$s_fields).")){$cast2} AS `$name`";
							$db_fields[] = "{$table}_{$field['subtable']}.id AS {$name}_id";
							$join_subtables[$subtable] = 'id';
						}
						else {
							$db_fields[] = "(CONCAT_WS('|||','{$table}','$subtable','$name','{$field['thumb_amount']}','{$field['media_amount']}','{$field['media_size']}')) AS `$name`";
						}
					}
					elseif (!$is_field && !$has_subtable && $has_table) {
						if (empty($field['is_media'])) {
							$subtable_fields = (!$subtable_fields) ? self::getTableFields($field['subtable'],true) : $subtable_fields;
							$fields[$key]['subtable_fields'] = $subtable_fields;
							$f_where[$name] = $field['subtable'];
							$remote_i++;
							
							$int_fields = self::getFieldsLikeType($field['subtable'],'int');
							$int_exists = array();
							$dou_fields = self::getFieldsLikeType($field['subtable'],'double');
							$dou_exists = array();
							foreach ($subtable_fields as $s_field) {
								$s_fields[] = "{$field['subtable']}{$remote_i}.{$s_field}";
								$int_exists[] = @array_key_exists($s_field,$int_fields);
								$dou_exists[] = @array_key_exists($s_field,$dou_fields);
							}
							if (count($subtable_fields) == array_sum($int_exists)) {
								$cast1 = "CAST(";
								$cast2 = " AS DECIMAL)";
							}
							elseif (count($subtable_fields) == array_sum($dou_exists)) {
								$cast1 = "CAST(";
								$cast2 = " AS DECIMAL(10,2))";
							}
							else{
								$cast1 = false;
								$cast2 = false;
							}
							if (!$field['filter_result']) {
								$db_fields[] = "{$cast1}CONCAT_WS('$concat_char',".implode(',',$s_fields)."){$cast2} AS `$name`";
								$db_fields[] = "{$field['subtable']}{$remote_i}.id AS {$name}_id";
							}
							if (!strstr($field['f_id_field'],',')) {
								$foreign_tables[$subtable] = $field['f_id_field'];
								$foreign_order[$subtable]['order_by'] = $field['order_by'];
								$foreign_order[$subtable]['order_asc'] = $field['order_asc'];
								$foreign_order[$subtable]['limit_is_curdate'] = $field['limit_is_curdate'];
								if ($field['filter_result']) {
									$filter_results[$subtable] = $field['f_value'];	
								}
							}
							else {
								$join_path = explode(',',$field['f_id_field']);
								if (is_array($join_path)) {
									$join_table = false;
									if (strstr($field['order_by'],'.')) {
										$otparts = explode('.',$field['order_by']);
										$foreign_order_table = $otparts[0];
									}
									else {
										$foreign_order_table = false;
										$otparts = false;
									}
									
									foreach ($join_path as $join_field) {
										$join_field_parts = explode('.',$join_field);
										$join_table = $join_field_parts[0];
										$j_field = $join_field_parts[1];
										$remote_tables[$remote_i][$join_table][] = $j_field;
									}
									if ($field['filter_result']) {
										$join_table1 = $join_table.$remote_i.'.'.$j_field;
										$filter_results[$join_table1] = $field['f_value'];	
									}
									if ($join_table && $field['order_by']) {
										$join_table = ($foreign_order_table) ? $foreign_order_table : $join_table;
										$foreign_order[$join_table]['order_by'] = (is_array($otparts)) ? $otparts[1] : $field['order_by'];
										$foreign_order[$join_table]['order_asc'] = $field['order_asc'];
										$foreign_order[$join_table]['limit_is_curdate'] = $field['limit_is_curdate'];
										$foreign_order[$join_table]['remote_i'] = $remote_i;
									}
								}
							}
							$field_properties = self::getTableFields($field['subtable']);
							foreach ($field_properties as $k => $v) {
								if ($v['Type'] == 'datetime') {
									$foreign_dates[$k] = $k;
								}
							}
						}
					}
				}
			}
		}

		if (strstr($f_id_field,','))  {
			$remote_i++;
			$join_path = explode(',',$f_id_field);
			if (is_array($join_path)) {
				$c = count($join_path) - 1;
				$i = 0;
				foreach ($join_path as $join_field) {
					if ($i == $c)
						break;
					
					$join_field_parts = explode('.',$join_field);
					$join_table = $join_field_parts[0];
					$j_field = $join_field_parts[1];
					$remote_tables[$remote_i][$join_table][] = $j_field;
					$f_id_table_i = $remote_i;
					$i++;
				}
			}
		}

		if (is_array($filter_results['cat_selects'])) {
			foreach ($filter_results['cat_selects'] as $c_table => $ids) {
				if (!@array_key_exists($c_table,$double_join_tables))
					$double_join_tables[$c_table] = '';
			}
		}

		if (!$db_fields) return false;

		$order_asc = ($order_asc) ? 'ASC' : 'DESC';
		
		if ($count) {
			$sql = "SELECT COUNT(DISTINCT {$table}.id) AS total FROM {$table} ";
		}
		elseif ($get_total) {
			$sql = "SELECT SUM(".implode(',',$db_fields).") AS grand_total FROM {$table} ";
		}
		elseif ($get_average) {
			$sql = "SELECT AVG(".implode(',',$db_fields).") AS grand_total FROM {$table} ";
		}
		else {
			$sql = "SELECT ".implode(',',$db_fields)." FROM {$table} ";
		}
		
		if (is_array($join_tables)) {
			foreach ($join_tables as $j_table => $j_field) {
				if (strstr($j_table,'|')) {
					$j_parts = explode('|',$j_table);
					$j_table = $j_parts[0];
					$j_alias = $j_parts[0].$j_parts[1];
					$j_as = $j_alias;
				}
				else {
					$j_alias = $j_table;
					$j_as = false;
				}
				if (is_array($j_field)) {
					$j_field1 = $j_field[0];
					$j_field2 = $j_field[1];
					
					if (strstr($j_field2,',')) {
						$j_parts = explode(',',$j_field2);
						if (strstr($j_parts[1],'.')) {
							$j_parts1 = explode('.',$j_parts[1]);
							$j_field2 = $j_parts1[1];
						}
						else {
							$j_field2 = $j_parts[1];
						}
					}
				}
				else {
					$j_field1 = $j_field;
					$j_field2 = 'id';
				}

				$sql .= " LEFT JOIN {$j_table} {$j_as} ON ({$table}.{$j_field1} = {$j_alias}.{$j_field2}) ";
				
				if ($j_table != $j_alias)
					$joined_tables[$j_table] = $j_alias;
			}
		}
		if (is_array($join_subtables)) {
			foreach ($join_subtables as $j_table => $j_field) {
				$sql .= " LEFT JOIN {$table}_{$j_table} ON ({$table}.id = {$table}_{$j_table}.f_id) ";
			}
		}
		if (is_array($double_join_tables)) {
			foreach ($double_join_tables as $d_table => $d_field) {
				$sql .= " LEFT JOIN {$table}_{$d_table} ON ({$table}.id = {$table}_{$d_table}.f_id) ";
				$sql .= " LEFT JOIN {$d_table} ON ({$table}_{$d_table}.c_id = {$d_table}.id) ";
			}
		}
		
		if (is_array($foreign_tables)) {
			foreach ($foreign_tables as $j_table => $j_field) {
				$o_field = $foreign_order[$j_table]['order_by'];
				$o_asc = (!empty($foreign_order[$j_table]['order_asc'])) ? '>' : '<';
				$sql .= " LEFT JOIN {$j_table} ON ({$table}.id = {$j_table}.{$j_field}) ";
				$sql .= " LEFT JOIN {$j_table} {$j_table}xx ON ({$j_table}.{$j_field} = {$j_table}xx.{$j_field} AND {$j_table}.{$o_field} {$o_asc} {$j_table}xx.{$o_field}) ";
			}
		}

		if (is_array($remote_tables)) {
			$c = count($remote_tables);
			foreach ($remote_tables as $remote_i => $join_sequence) { 
				$i = 0;
				foreach ($join_sequence as $r_table => $r_field) {
					$r_table = str_replace('||','',$r_table);
					// removed [[ && !@array_key_exists($r_table,$join_tables) ]]
					if (!@array_key_exists($r_table,$foreign_tables) && !@array_key_exists($r_table,$join_subtables) && !@array_key_exists($r_table,$double_join_tables)) {
						if ($r_table != $table) {
							$o_field = $foreign_order[$r_table]['order_by'];
							$j_field = ($prev_field == 'id') ? $r_field[0] : 'id';
							$j_field = ($r_table == $prev_table) ? $prev_field : $r_field[0];
							$remote_i1 = ($i > 0) ? $remote_i : '';
							$join_condition = (!empty($r_field['join_condition'])) ? 'AND '.str_replace($r_table,$r_table.$remote_i,$r_field['join_condition']) : '';
							
							if (empty($o_field)) {
								$sql .= " LEFT JOIN {$r_table} {$r_table}{$remote_i} ON ({$prev_table}{$remote_i1}.{$prev_field} = {$r_table}{$remote_i}.{$j_field} $join_condition) ";
							}
							else {
								$o_asc = (!empty($foreign_order[$j_table]['order_asc'])) ? '>' : '<';
								$sql .= " LEFT JOIN {$r_table} {$r_table}{$remote_i} ON ({$prev_table}{$remote_i1}.{$prev_field} = {$r_table}{$remote_i}.{$j_field}) ";
								$sql .= " LEFT JOIN {$r_table} {$r_table}{$remote_i}xx ON ({$prev_table}{$remote_i1}.{$prev_field} = {$r_table}{$remote_i}xx.{$j_field} AND {$r_table}{$remote_i}.{$o_field} {$o_asc} {$r_table}{$remote_i}xx.{$o_field}) ";
							}
							$joined_tables[$r_table] = $r_table.$remote_i;
							
							if ($formulas) {
								foreach ($formulas as $formula) {
									if (strstr($formula,$r_table)) {
										$formula1 = str_replace($r_table,$r_table.$remote_i,$formula);
										$sql = str_replace($formula,$formula1,$sql);
									}
								}
							}
							$i++;
						}
					}
					$prev_table = $r_table;
					$prev_field = (count($r_field) > 1) ? $r_field[1] : $r_field[0];
				}
			}
		}
		
		$sql .= " WHERE 1 ";
		
		if (is_array($foreign_order)) {
			foreach ($foreign_order as $f_table => $properties) {
				$remote_i = ($properties['remote_i'] > 0) ? $properties['remote_i'] : '';
				$sql .= " AND {$f_table}{$remote_i}xx.{$properties['order_by']} IS NULL";
			}
		}
		
		if (is_array($filter_results)) {
			if (!empty($filter_results['first_letter']['results'])) {
				$r_subtable = (!empty($filter_results['first_letter_subtable'])) ? $filter_results['first_letter_subtable'] : $table;
				$sql .= " AND {$r_subtable}.{$filter_results['first_letter_field']} LIKE '{$filter_results['first_letter']}%' ";
			}

			foreach ($filter_results as $r_name => $r_properties) {
				$r_value = $r_properties['results'];
				if (!is_array($r_value) && strlen($r_value) == 0)
					continue;

				$w_table = (@array_key_exists($r_name,$f_where)) ? $f_where[$r_name] : $table;
				$r_name_orig = $r_name;
				$r_name = self::replaceTables($r_name,$joined_tables);

				if ($r_name == 'cat_selects') {
					$having = '';
					foreach ($r_value as $c_table => $cats) {
						if (is_array($cats)) {
							$having .= " AND SUM(IF(";
							foreach ($cats as $cat) {
								$having .= " {$c_table}.id = $cat OR";
							}
							$having = substr($having,0,-2);
							$having .= ',1,0)) > 0 ';
						}
					}
				}
				elseif ($r_name == 'first_letter' || $r_name == 'first_letter_field' || $r_name == 'first_letter_subtable' || $r_name == 'per_page') {
					continue;
				}
				elseif (stristr($r_name,'search')) {
					$r_name_parts = explode('|',$r_name);
					$r_i = $r_name_parts[1];
					if (is_array($_REQUEST['search_fields'.$CFG->control_pass_id])) {
						$sql .= " AND ( ";
						foreach ($_REQUEST['search_fields'.$CFG->control_pass_id] as $s_field => $s_subtable) {
							$s_field_parts = explode('|',$s_field);
							if ($s_field_parts[1] != $r_i)
								continue;

							$s_field = $s_field_parts[0];
							$s_subtable = (!empty($s_subtable)) ? $s_subtable : $table;
							if (!is_numeric($s_field))
								$sql .= " {$s_subtable}.{$s_field} LIKE '%$r_value%' OR";
						}
						$sql = substr($sql,0,-2);
						$sql .= " ) ";
					}
					else {
						$sql .= " AND {$w_table}.{$r_name} = '$r_value' ";
					}
				}
				elseif(@array_key_exists($r_name, $_REQUEST['datefields'])) {
					$is_b = (stristr($r_name,'bbb'));
					$r_name = str_replace('bbb','',$r_name);
					
					if ($is_b) {
						$sql .= " AND {$w_table}.{$r_name} <= '".date('Y-m-d',strtotime($r_value))."' ";
					}
					else {
						$sql .= " AND {$w_table}.{$r_name} >= '".date('Y-m-d',strtotime($r_value))."' ";
					}
				}
				elseif(@in_array($r_name, $_REQUEST['month_fields'])) {
					$r_name = str_replace('_month','',$r_name);
					$sql .= " AND MONTH({$w_table}.{$r_name}) = '".$r_value."' ";
				}
				elseif(@in_array($r_name, $_REQUEST['year_fields'])) {
					$r_name = str_replace('_year','',$r_name);
					$sql .= " AND YEAR({$w_table}.{$r_name}) = '".$r_value."' ";
				}
				elseif(@array_key_exists($r_name,$_REQUEST['tokenizers'])) {
					$value1 = (is_array(unserialize($r_value))) ? unserialize($r_value) : $r_value;
					if (is_array($value1))
						$tokenizer_values = $value1;
					elseif (strlen($value1) > 0)
						$tokenizer_values = String::unFaux($value1);
					
					if (is_array($tokenizer_values)) {
						$sql .= " AND (";
						foreach ($tokenizer_values as $r_name1 => $r_value1) {
							$sql .= " {$w_table}.{$r_name} = $r_value1 OR";
						}
						$sql = substr($sql,0,-2);
						$sql .= ") ";
					}
				}
				else {
					$r_name_parts = explode(',',$r_name);
					$r_name_orig_parts = explode(',',$r_name_orig);
					$r_name_sql = false;
					$equals = ($r_properties['not_equals']) ? '!=' : '=';

					$sql .= ' AND (';
					foreach ($r_name_parts as $k1 => $r_name_part) {
						if (strstr($r_name_part,'.')) {
							$r_orig_name = explode('.',$r_name_orig_parts[$k1]);
							if (!empty($join_tables[$r_orig_name[0]]))
								$r_name_sql[] = " {$r_name_orig_parts[$k1]} $equals '$r_value' ";
							
							$r_name_sql[] = " {$r_name_part} $equals '$r_value' ";
						}
						else
							$r_name_sql[] = " {$table}.{$r_name_part} $equals '$r_value' ";
					}
					$sql .= implode('OR',$r_name_sql);
					$sql .= ' ) ';
				}
			}
		}

		if (($f_id > 0 || $f_id === 0 || $f_id === '0') && $f_id_field) {
			if (strstr($f_id_field,',')) {
				$parts = explode(',',$f_id_field);
				$c = count($parts) - 1;
				$r_parts = explode('.',$parts[$c]);
				$j_table = ($joined_tables[$r_parts[0]]) ? $joined_tables[$r_parts[0]] : $r_parts[0];
				$r_table = $j_table.'.'.$r_parts[1];
				$sql .= " AND {$r_table} = $f_id ";
			}
			elseif (strstr($f_id_field,'.')) {
				$r_parts = explode('.',$f_id_field);
				$j_table = ($joined_tables[$r_parts[0]]) ? $joined_tables[$r_parts[0]] : $r_parts[0];
				$sql .= " AND {$j_table}.{$r_parts[1]} = $f_id ";
			}
			else
				$sql .= " AND {$table}.{$f_id_field} = $f_id ";
		}
		
		if (!$calendar_mode) {
			if ($s_date && $s_date_field) {
				$sql .= " AND {$table}.{$s_date_field} >= '".date('Y-m-d',$s_date)."' ";
			}
			
			if ($e_date && $e_date_field) {
				$sql .= " AND {$table}.{$e_date_field} <= '".date('Y-m-d',$e_date)."' ";
			}
		}
		else {
			if (($s_date && $s_date_field) && ($e_date && $e_date_field)) {
				$sql .= " AND (DATE({$table}.{$s_date_field}) <= '".date('Y-m-d',$e_date)."' AND (DATE({$table}.{$e_date_field}) >= '".date('Y-m-d',$s_date)."' OR DATE({$table}.{$e_date_field}) < '1980-01-01')) ";
			}
			else {
				$sql .= " AND (DATE({$table}.{$s_date_field}) >= '".date('Y-m-d',$s_date)."' AND DATE({$table}.{$s_date_field}) <= '".date('Y-m-d',$e_date)."') ";
			}
		}

		if ($filters) { 
			foreach ($filters as $filter) {
				if ($filter) {
					$filter = String::doFormulaReplacements($filter);
					$filter = self::replaceTables($filter,$joined_tables);
					$sql .= " AND $filter ";
				}
			}
		}
		
		if ((!$count && !$get_average && !$get_total) || $having) {
			if (!$no_group_by) {
				$group_field = ($group_by) ? $group_by : "{$table}.id";
				$sql .= " GROUP BY $group_field ";
			}
			
		}
		
		if ($having)
			$sql .= " HAVING 1 ".$having; 
		
		if (!$count && !$get_average && !$get_total) {
			if ($order_by) {
				$order_by = (!strstr('.',$order_by) && !($fields[$order_by]['is_op'])) ? $table.'.'.$order_by : '`'.$order_by.'`';
				
				$sql .= " ORDER BY $order_by $order_asc ";
			}
			else {
				$sql .= " ORDER BY {$table}.id $order_string ";
			}
			
			if ($start_row > 0 || $per_page > 0) {
				$sql .= " LIMIT {$start_row},{$per_page} ";
			}
		}
		//echo $sql.'<br><br>';
		$result = db_query_array($sql);
		
		if ($count && $having) {
			$result[0]['total'] = count($result);
		}
		
		if ($count) {
			return $result[0]['total'];
		}
		elseif ($get_total || $get_average) {
			return $result[0]['grand_total'];
		}
		else {
			return $result;
		}
	}
	
	public static function getRecord($table,$id = 0,$f_id=0,$id_required=false,$f_id_field=false,$order_by=false,$order_asc=false,$for_update=false) {
		if ($id_required && !($id > 0))
			return false;
			
		if (!$table)
			return false;
		
		$f_id_field = ($f_id_field) ? $f_id_field : 'f_id';
			
		$sql = "SELECT {$table}.* FROM {$table} WHERE 1 ";
		if ($id > 0) {
			$sql .= " AND  {$table}.id = $id ";
		}
		if ($f_id) {
			$sql .= " AND  {$table}.{$f_id_field} = '$f_id' ";
		}
		if ($order_by) {
			$order_asc = ($order_asc) ? 'ASC' : 'DESC';
			$sql .= " ORDER BY $order_by $order_asc ";
		}
		$sql .= " LIMIT 0,1 ";
		
		if ($for_update)
			$sql .= ' FOR UPDATE';
		
		$result = db_query_array($sql);
		return $result[0];
	}
	
	public static function replaceTables($string,$joined_tables) {
		if ($joined_tables) {
			foreach ($joined_tables as $j_table => $j_alias) {
				if (stristr($string,$j_alias))
					continue;
					
				$string = str_ireplace($j_table,$j_alias,$string);
			}
		}
		return $string;
	}
	
	// must put vars in braces
	public static function getAggregateRow($name,$formula,$table,$record_id) {
		$matches = String::getSubstring($formula,'[',']');
		foreach ($matches as $match) {
			if (strstr($match,','))  {
				$join_path = explode(',',$f_id_field);
				if (is_array($join_path)) {
					$c = count($join_path) - 1;
					$i = 0;
					foreach ($join_path as $join_field) {
						if ($i == $c)
							break;
							
						$join_field_parts = explode('.',$join_field);
						$j_table = $join_field_parts[0];
						$j_field = $join_field_parts[1];
						$tables[$join_table][] = $j_field;
						$i++;
					}
				}
			}
		}
		
		$formula = str_replace('[','',str_replace(']','',$formula));
		
		$sql = "SELECT $formula AS $name FROM $table ";
		
		if ($tables) {
			foreach ($tables as $r_table => $r_field) {
				if ($r_table != $table) {
					$o_field = $foreign_order[$r_table]['order_by'];
					$j_field = ($prev_field == 'id') ? $r_field[0] : 'id';
					$remote_i1 = ($i > 0) ? $remote_i : '';
					
					$sql .= " LEFT JOIN {$r_table} ON ({$prev_table}.{$prev_field} = {$r_table}.{$j_field}) ";
	
					$i++;
				}
				
				$prev_table = $r_table;
				$prev_field = (count($r_field) > 1) ? $r_field[1] : $r_field[0];
			}
		}
		
		$sql .= " WHERE {$table}.id = {$record_id} LIMIT 0,1";
		$result = db_query_array($sql);
		return $result[0][$name];
	}
	
	public static function insert($table,$fields_array,$date_fields=false,$ignore_fields=false) {
		global $CFG;
		
		if ($CFG->control_one_record)
			$fields_array['id'] = 1;

		if ($fields_array['cat_selects']) {
			$cats_array = $fields_array['cat_selects'];
			unset($fields_array['cat_selects']);
		}
		if ($fields_array['urls']) {
			$urls = $fields_array['urls'];
			unset($fields_array['urls']);
		}
		if (is_array($_REQUEST['grid_inputs']) && !$CFG->ignore_request) {
			foreach ($_REQUEST['grid_inputs'] as $name) {
				$grid_inputs[$name] = $fields_array[$name];
				unset($fields_array[$name]);
			}
		}

		$fields_array = self::serializeMultiples($fields_array,$table);
		if (is_array($_REQUEST['tokenizers'])) {
			foreach ($_REQUEST['tokenizers'] as $name) {
				$fields_array[$name] = serialize($fields_array[$name]);
			}
		}
		
		if (!$CFG->backstage_mode)
			$fields_array = array_map('mysql_real_escape_string',$fields_array);
		
		$insert_id = db_insert($table,$fields_array);
		
		if ($cats_array) {
			foreach ($cats_array as $subtable => $cat) {
				if ($_REQUEST['cat_selects'][$subtable]) {
					db_delete($table.'_'.$subtable,$insert_id,'f_id');
					foreach ($cat as $c_id => $value) {
						db_insert($table.'_'.$subtable,array('f_id'=>$insert_id,'c_id'=>$c_id,'value'=>$value));
					}
				}
				else {
					db_delete($table.'_'.$subtable,$insert_id,'f_id');
					foreach ($cat as $c_id) {
						db_insert($table.'_'.$subtable,array('f_id'=>$insert_id,'c_id'=>$c_id));
					}
				}
			}
		}
		if ($urls) {
			foreach ($urls as $f_name => $field) {
				foreach ($field as $url) {
					if (!empty($url)) {
						db_insert($table.'_files',array('f_id' => $insert_id, 'field_name' => $f_name,'url' => $url));
					}
				}
			}
		}
		if ($grid_inputs) {
			foreach ($grid_inputs as $name => $row) {
				db_delete($table.'_grid_'.$name,$insert_id,'f_id');
				foreach ($row as $values) {
					$values['f_id'] = $insert_id;
					db_insert($table.'_grid_'.$name,$values);
				}
			}
		}
		return $insert_id;
	}
	
	public static function update($table,$fields_array,$id,$ignore_fields=false) {
		global $CFG;

		if ($fields_array['cat_selects']) {
			$cats_array = $fields_array['cat_selects'];
			unset($fields_array['cat_selects']);
		}
		if ($fields_array['urls']) {
			$urls = $fields_array['urls'];
			unset($fields_array['urls']);
		}
		if (is_array($_REQUEST['grid_inputs']) && !$CFG->ignore_request) {
			foreach ($_REQUEST['grid_inputs'] as $name) {
				$grid_inputs[$name] = $fields_array[$name];
				unset($fields_array[$name]);
			}
		}
		$fields_array = self::serializeMultiples($fields_array);
		
		if (is_array($_REQUEST['tokenizers'])) {
			foreach ($_REQUEST['tokenizers'] as $name) {
				$fields_array[$name] = serialize($fields_array[$name]);
			}
		}

		if (!$CFG->backstage_mode)
			$fields_array = array_map('mysql_real_escape_string',$fields_array);

			
		$num_affected = db_update($table,$id,$fields_array);

		if ($cats_array) {
			foreach ($cats_array as $subtable => $cat) {
				if ($_REQUEST['cat_selects'][$subtable]) {
					db_delete($table.'_'.$subtable,$id,'f_id');
					foreach ($cat as $c_id => $value) {
						db_insert($table.'_'.$subtable,array('f_id'=>$id,'c_id'=>$c_id,'value'=>$value));
					}
				}
				else {
					db_delete($table.'_'.$subtable,$id,'f_id');
					foreach ($cat as $c_id) {
						db_insert($table.'_'.$subtable,array('f_id'=>$id,'c_id'=>$c_id));
					}
				}
			}
		}
		if ($urls) {
			foreach ($urls as $f_name => $field) {
				foreach ($field as $url) {
					if (!empty($url)) {
						db_insert($table.'_files',array('f_id' => $id, 'field_name' => $f_name,'url' => $url));
					}
				}
			}
		}
		if ($grid_inputs) {
			foreach ($grid_inputs as $name => $row) {
				db_delete($table.'_grid_'.$name,$id,'f_id');
				foreach ($row as $values) {
					$values['f_id'] = $id;
					db_insert($table.'_grid_'.$name,$values);
				}
			}
		}
		return $num_affected;
	}
	
	public static function delete($id) {
		$sql = "DELETE FROM notes WHERE f_id = $id AND f_type = 'C'";
		db_query ( $sql );
		
		$sql = "DELETE FROM client_username WHERE client_id = $id";
		db_query ( $sql );
		
		return db_delete ( 'client', $id );
	}
	
	public static function emptyTable($table) {
		if (!$table)
			return false;
			
			$sql = "TRUNCATE TABLE $table";
			return db_query($sql);
	}
	
	public static function getTotal($field,$table) {
		if (!$field || !$table)
			return false;
		
		if (!empty($field['subtable']) && empty($field['subtable_fields']))
			return false;	

		if (is_array($field['subtable_fields'])) {
			$name = implode('+',$field['subtable_fields']);
		}
		elseif ($field['formula']) {
			$name = $field['formula'];
		}
		else {
			$name = $field['name'];
		}
		
		$table = (empty($field['subtable'])) ? $table : $field['subtable'];	
		$sql = "SELECT SUM({$name}) AS grand_total FROM {$table}";
		$result = db_query_array($sql);
		
		return $result[0]['grand_total'];
	}
	
	public static function getAverage($field,$table) {
		if (!$field || !$table)
			return false;
		
		if (!empty($field['subtable']) && empty($field['subtable_fields']))
			return false;	
			
		$name = (!is_array($field['subtable_fields'])) ? $field['name'] : implode('+',$field['subtable_fields']);
		$table = (empty($field['subtable'])) ? $table : $field['subtable'];
		
		$sql = "SELECT AVG({$name}) AS grand_total FROM {$table}";
		$result = db_query_array($sql);
		
		return $result[0]['grand_total'];
	}
	
	public static function tableExists($table) {
		if (!$table)
			return false;
		
		$sql = "SELECT 1 FROM {$table} LIMIT 0";
		if (mysql_query($sql)) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public static function getTableFields($table,$names_only=false,$for_autocomplete=false,$field_like=false) {
		$field_like = ($field_like) ? '"%'.$field_like.'%"' : '';
		$result = db_query_array("DESCRIBE {$table} {$field_like}");
		if ($result) {
			if (!$names_only && !$for_autocomplete) {
				foreach ($result as $row) {
					$key = $row['Field'];
					$fields[$key] = $row;
				}
			}
			elseif ($for_autocomplete) {
				foreach ($result as $row) {
					$key = $row['Field'];
					$fields[$key] = $key;
				}
			}
			else {
				foreach ($result as $row) {
					$fields[] = $row['Field'];
				}
			}
		}
		else {
			return false;
		}
		return $fields;
	}
	
	public static function getTables($table=false) {
		$result = db_query_array("SHOW TABLES LIKE '{$table}%' ");
		if ($result) {
			foreach ($result as $row) {
				$row = array_values($row);
				if (!stristr($row[0],'admin')) {
					$name = $row[0];
					$tables[$name] = $name;
				}
			}
			return $tables;
		}
	}
	
	public static function getSubtables($table) {
		$result = db_query_array("SHOW TABLES LIKE '{$table}%' ");
		if ($result) {
			foreach ($result as $row) {
				$row = array_values($row);
				if ($row[0] != $table) {
					$subtable = str_ireplace("{$table}_",'',$row[0]);
					$subtables[] = $subtable;
				}
			}
		}
		else {
			return false;
		}
		return $subtables;
	}
	
	public static function createTable($table,$fields_array,$enum_fields=false,$ignore_fields=false) {
		if (empty($table) || !is_array($fields_array))
			return false;
		
		$sql = " CREATE TABLE {$table} (
				 id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,";
		
		foreach ($fields_array as $field => $type) {
			if (array_key_exists($field,$ignore_fields));
				continue;
				
			if (stristr($type,'||')) {
				$type1 = explode('||',$type);
				$type = $type[0];
				$subfields = unserialize($type[1]);
				if ($is_array($subfields)) {
					$sql4 = "CREATE TABLE `{$table}_grid_{$field}` (
							id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
							`f_id` INT( 10 ) UNSIGNED NOT NULL ,";
	
					foreach ($subfields as $method => $args) {
						$method_parts = explode('|',$method);
						$method = $method_parts[0];
						switch($method) {
							case 'textInput':
								if ($args['db_field_type'] == 'vchar' || $args['db_field_type'] == 'varchar')
									$sql4 .= "`{$args['name']}` VARCHAR( 255 ) NOT NULL ,";
								elseif ($args['db_field_type'] == 'int')
									$sql4 .= "`{$args['name']}` INT( 10 ) NOT NULL,";
								elseif ($args['db_field_type'] == 'decimal')
									$sql4 .= "`{$args['name']}` DOUBLE( 10, 2 ) NOT NULL,";
								break;
							case 'selectInput':
							case 'autoComplete':
								$sql4 .= "`{$args['name']}` INT( 10 ) NOT NULL,";
								break;
							case 'checkBox':
								$sql4 .= "`{$args['name']}` ENUM('Y','N') NOT NULL DEFAULT 'N',";
								break;
							case 'textArea':
								$sql4 .= "`{$args['name']}` TEXT NOT NULL,";
								break;
						}
					}
							
					$sql4 .= "INDEX ( `f_id`)
							) ENGINE = MYISAM ";
				}
			}
			else {
				switch ($type) {
					case 'varchar':
					case 'vchar':
					case 'password':
					default:
						$sql .= "`{$field}` VARCHAR( 255 ) NOT NULL ,";
						break;
					case 'checkbox':
						$sql .= "`{$field}` ENUM('Y','N') NOT NULL DEFAULT 'N',";
						break;
					case 'enum':
						$sql .= "`{$field}` ENUM(";
						foreach ($enum_fields[$field] as $value) {
							$sql .= "'$value',";
						}
						$sql = substr($sql,0,-1);
						$sql .= ") NOT NULL DEFAULT '{$enum_fields[$field][0]}',";
						break;
					case 'text':
						$sql .= "`{$field}` TEXT NOT NULL,";
						break;
					case 'int':
						$sql .= "`{$field}` INT( 10 ) NOT NULL,";
						break;
					case 'decimal':
						$sql .= "`{$field}` DOUBLE( 10, 2 ) NOT NULL,";
						break;
					case 'blob':
						$sql .= "`{$field}` BLOB NOT NULL,";
						break;
					case 'date':
						$sql .= "`{$field}` DATETIME NOT NULL,";
						break;
					case 'cat_select':
						$sql2 = "CREATE TABLE `{$table}_{$field}` (
								`f_id` INT( 10 ) UNSIGNED NOT NULL ,
								`c_id` INT( 10 ) UNSIGNED NOT NULL ,
								INDEX ( `f_id` , `c_id` )
								) ENGINE = MYISAM ";
						break;
					case 'cat_input':
						$sql2 = "CREATE TABLE `{$table}_{$field}` (
								`f_id` INT( 10 ) UNSIGNED NOT NULL ,
								`c_id` INT( 10 ) UNSIGNED NOT NULL ,
								`value` VARCHAR( 255 ) NOT NULL ,
								INDEX ( `f_id` , `c_id` )
								) ENGINE = MYISAM ";
						break;
					case 'file':
						$sql3 = "CREATE TABLE `{$table}_files` (
								id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
								`f_id` INT( 10 ) UNSIGNED NOT NULL ,
								`ext` CHAR( 4 ) NOT NULL ,
								`dir` VARCHAR( 255 ) NOT NULL ,
								`url` TEXT NOT NULL ,
								`old_name` VARCHAR( 255 ) NOT NULL ,
								`field_name` VARCHAR( 50 ) NOT NULL ,
								INDEX ( `f_id`)
								) ENGINE = MYISAM ";
						break;
				}
			}
		}
		$sql = substr($sql,0,-1);
		$sql .=	") ENGINE = MYISAM ";
			
		if ($sql2) {
			if (!self::tableExists("{$table}_{$field}")) {
				$cats = db_query($sql2);
			}
		}
		if ($sql3) {
			if (!self::tableExists("{$table}_files")) {
				$files = db_query($sql3);
			}
		}
		if ($sql4) {
			if (!self::tableExists("{$table}_grid_{$field}")) {
				$files = db_query($sql4);
			}
		}
		return db_query($sql);
	}
	
	public static function editTable($table,$fields_array,$enum_fields=false) {
		if (empty($table))
			return false;
			
		$table_fields = self::getTableFields($table,true);
		
		if (!is_array($table_fields) || !is_array($fields_array))
			return false;
			
		foreach ($fields_array as $field => $type) {
			if (stristr($type,'||')) {
				$type1 = explode('||',$type);
				$type = $type1[0];
				$subfields = unserialize($type1[1]);
				if (is_array($subfields)) {
					if (!self::tableExists("{$table}_grid_{$field}")) {
						$sql4 = "CREATE TABLE `{$table}_grid_{$field}` (
							id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
							`f_id` INT( 10 ) UNSIGNED NOT NULL ,";
					}
					else {
						$add = ' ADD ';
						$table_fields1 = self::getTableFields("{$table}_grid_{$field}",true);
					}
					foreach ($subfields as $method => $args) {
						$method_parts = explode('|',$method);
						$method = $method_parts[0];
						$subfields1[] = $args['name'];
						if (is_array($table_fields1)) {
							if (in_array($args['name'],$table_fields1))
								continue;
						}
						switch($method) {
							case 'textInput':
								if ($args['db_field_type'] == 'vchar' || $args['db_field_type'] == 'varchar')
									$sql4 .= $add."`{$args['name']}` VARCHAR( 255 ) NOT NULL ,";
								elseif ($args['db_field_type'] == 'int')
									$sql4 .= $add."`{$args['name']}` INT( 10 ) NOT NULL,";
								elseif ($args['db_field_type'] == 'decimal')
									$sql4 .= $add."`{$args['name']}` DOUBLE( 10, 2 ) NOT NULL,";
								break;
							case 'selectInput':
							case 'autoComplete':
								$sql4 .= $add."`{$args['name']}` INT( 10 ) NOT NULL,";
								break;
							case 'checkBox':
								$sql4 .= $add."`{$args['name']}` ENUM('Y','N') NOT NULL DEFAULT 'N',";
								break;
							case 'textArea':
								$sql4 .= $add."`{$args['name']}` TEXT NOT NULL,";
								break;
						}
					}
					if (!self::tableExists("{$table}_grid_{$field}")) {	
						$sql4 .= "INDEX ( `f_id`)
								) ENGINE = MYISAM ";
					}
					else {
						$sql4 = substr($sql4,0,-1);
					}
					if (is_array($table_fields1)) {
						foreach ($table_fields1 as $field1)  {
							if (($field1 != 'id' && $field1 != 'f_id') && (!in_array($field1,$subfields1))) {
								db_query("ALTER TABLE {$table}_grid_{$field} DROP {$table}_grid_{$field}.$field1");
							}
						}
					}
				}
			}
			else {
				if (!in_array($field,$table_fields)) {
					switch ($type) {
						case 'varchar':
						case 'vchar':
						case 'password':
						default:
							$sql = "`{$field}` VARCHAR( 255 ) NOT NULL ";
							break;
						case 'checkbox':
							$sql = "`{$field}` ENUM('Y','N') NOT NULL DEFAULT 'N'";
							break;
						case 'enum':
							$sql = "`{$field}` ENUM(";
							foreach ($enum_fields[$field] as $value) {
								$sql .= "'$value',";
							}
							$sql = substr($sql,0,-1);
							$sql .= ") NOT NULL DEFAULT '{$enum_fields[$field][0]}'";
							break;
						case 'text':
							$sql = "`{$field}` TEXT NOT NULL";
							break;
						case 'blob':
							$sql .= "`{$field}` BLOB NOT NULL";
							break;
						case 'int':
							$sql = "`{$field}` INT( 10 ) NOT NULL";
							break;
						case 'decimal':
							$sql .= "`{$field}` DOUBLE( 10, 2 ) NOT NULL ";
							break;
						case 'date':
							$sql = "`{$field}` DATETIME NOT NULL";
							break;
						case 'cat_select':
							$sql2 = "CREATE TABLE `{$table}_{$field}` (
									`f_id` INT( 10 ) UNSIGNED NOT NULL ,
									`c_id` INT( 10 ) UNSIGNED NOT NULL ,
									INDEX ( `f_id` , `c_id` )
									) ENGINE = MYISAM ";
							break;
						case 'file':
							$sql3 = "CREATE TABLE `{$table}_files` (
									id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
									`f_id` INT( 10 ) UNSIGNED NOT NULL ,
									`ext` CHAR( 4 ) NOT NULL ,
									`dir` VARCHAR( 255 ) NOT NULL ,
									`url` TEXT NOT NULL, 
									`old_name` VARCHAR( 255 ) NOT NULL ,
									`field_name` VARCHAR( 50 ) NOT NULL ,
									INDEX ( `f_id`)
									) ENGINE = MYISAM ";
							break;
					}
				}
			}
			
			if ($sql3) {
				if (!self::tableExists("{$table}_files"))
					db_query($sql3);
			}
			elseif ($sql4) {
				if (!self::tableExists("{$table}_grid_{$field}")) {
					$cats = db_query($sql4);
				}
				else {
					db_query("ALTER TABLE {$table}_grid_{$field} ADD $sql5");
				}
			}
			elseif ($sql2) {
				if (!self::tableExists("{$table}_{$field}")) {
					$cats = db_query($sql2);
				}
			}
			elseif ($sql) {
				db_query("ALTER TABLE {$table} ADD $sql");
			}
		
			unset($sql);
			unset($sql2);
			unset($sql3);
			unset($sql4);
			unset($add);
		}

		foreach ($table_fields as $field){
			if (($field != 'id' && !stristr($field,'include_id')) && (!array_key_exists($field,$fields_array))) {
				db_query("ALTER TABLE $table DROP {$table}.$field");
			}
		}
	}
	
	public static function getMaxId($table,$f_id=false) {
		$sql = "SELECT MAX(id) AS id FROM {$table} WHERE 1 ";

		if ($f_id > 0) {
			$sql .= " AND f_id = $f_id ";
		}
		$result = db_query_array ( $sql );
		return $result[0]['id'];
	}
	
	public static function getSubTable($table, $table_fields=false,$f_id=0,$concat_char=false,$f_id_field=false,$search_term=false) {
		$concat_char = ($concat_char) ? $concat_char : ' ';
		$search_term = mysql_escape_string($search_term);
		$sql = "SELECT ";
		if (is_array($table_fields)) {
			$sql .= "{$table}.id,";
			foreach ($table_fields as $field) {
				if ($field != 'order')
					$sql .= "{$table}.{$field},";
			}
			$sql = substr($sql,0,-1);
		}
		else {
			$sql .= "{$table}.*";
		}
		$sql .= " FROM {$table} WHERE 1 ";
		
		if (strlen($f_id) > 0) {
			$f_id = (!is_numeric($f_id)) ? '"'.$f_id.'"' : $f_id;
			$f_id_field = ($f_id_field) ? $f_id_field : 'f_id';
			$sql .= " AND {$table}.{$f_id_field} = $f_id ";
		}
		
		if (is_array($table_fields) && $search_term) {
			$sql .= " AND (";
			foreach ($table_fields as $field) {
				$sql .= " {$table}.{$field} LIKE '%{$search_term}%' OR";
			}
			$sql = substr($sql,0,-2);
			$sql .= ") ";
		}

		if (@in_array('order',$table_fields)) {
			$k = key($table_fields);
			$sql .= " ORDER BY {$table}.order, {$table}.{$table_fields[$k]} ASC ";
		}
		elseif (@is_array($table_fields)) {
			$k = key($table_fields);
			if (!empty($table_fields[$k]))
				$sql .= " ORDER BY {$table}.{$table_fields[$k]} ASC ";
		}
		//echo $sql;
		$result = db_query_array ($sql);
		$fields_array = array();
		if ($result) {
			$table_info = self::getTableFields($table);
			foreach ($result as $row) {
				$r_id = $row['id'];
				if (!@in_array('id',$table_fields))
					unset($row['id']);
				
				if (is_array($row)) {
					foreach ($row as $name=>$field) {
						$row[$name] = Grid::detectData($name,$field,$table_info);
					}
				}
				
				if (in_array('p_id',$table_fields))
					$fields_array[$r_id] = $row;
				else
					$fields_array[$r_id] = implode($concat_char,$row);
			}
		}
		return $fields_array;
	}
	
	public static function getGridValues($table, $table_fields=false,$f_id=0) {
		if (!($f_id > 0))
			return false;
			
		$sql = "SELECT ";
		if (is_array($table_fields)) {
			$sql .= "{$table}.id,";
			foreach ($table_fields as $field) {
				$sql .= "{$table}.{$field},";
			}
			$sql = substr($sql,0,-1);
		}
		else {
			$sql .= "{$table}.*";
		}
		$sql .= " FROM {$table} WHERE 1 ";
		
		if (is_numeric($f_id)) {
			$f_id_field = ($f_id_field) ? $f_id_field : 'f_id';
			$sql .= " AND {$table}.{$f_id_field} = $f_id ";
		}
		
		if (@in_array('order',$table_fields)) {
			$sql .= " ORDER BY {$table}.order, {$table}.{$table_fields[0]} ASC ";
		}
		/*
		elseif (is_array($table_fields)) {
			if (!empty($table_fields[0]))
				$sql .= " ORDER BY {$table}.id ASC ";
		}
*/
		else {
			$sql .= " ORDER BY {$table}.id ASC ";
		}
		
		return db_query_array($sql);
	}
	
	public static function isUniqueValue($table,$field_name,$value) {
		if (empty($table) || empty($field_name) || empty($value))
			return true;
			
		$sql = "SELECT * FROM {$table} WHERE {$field_name} = '{$value}'";
		$result = db_query_array($sql);
		return (!is_array($result));
	}
	
	public static function getUniqueValues($table,$field_name) {
		if (is_array($field_name)) {
			$k = key($field_name);
			$field = $field_name[$k];
		}
		else {
			$field = $field_name;
		}
		
		$sql = "SELECT DISTINCT({$field}) FROM {$table}";
		$result = db_query_array($sql);
		$fields_array = array();
		if ($result) {
			foreach ($result as $row) {
				$v = $row[$field];
				$fields_array[$v] = $v;
			}
		}
		return $fields_array;
	}
	
	public static function getCats($table,$f_id=0) {
		$sql = "SELECT {$table}.* FROM {$table} WHERE 1 ";
		
		if ($f_id > 0) {
			$sql .= " AND {$table}.f_id = $f_id ";
		}
		
		$table_fields = self::getTableFields($table,true);
		if ($table_fields) {
			if (count($table_fields) > 1)
				$default_order_field =  $table_fields[next($table_fields)];
		}
		if (in_array('order',$table_fields)) {
			$order_present = 1;
			$order_fields[] = " {$table}.order ";
		}
		
		if ($default_order_field) {
			$order_fields[] = " {$table}.{$default_order_field} ";
		}
		
		if ($order_fields)
			$sql .= ' ORDER BY '.implode(',',$order_fields).' ASC';
		
		$result = db_query_array ( $sql );
		return self::sortCats($result,0);
	}
	
	
	public static function sortCats($result,$p_id,$use_key_as_id=false,$level=false,$i=0,$structured=false) {
		$i = ($i > 0) ? $i+1 : 1;
		$structured = (is_array($structured)) ? $structured : array();
		if (is_array($result)) {
			foreach ($result as $id => $row) {
				if ($row['p_id'] == $p_id) {
					$id = ($use_key_as_id || !array_key_exists('id',$row)) ? $id : $row['id'];
					$structured[$id]['row'] = $row;
					if ($id > 0) { 
						if ($level > 0) {
							if ($level == $i) {
								unset($row['p_id']);
								$structured[$id] = implode(' ',$row);
							}
							else {
								$structured = self::sortCats($result,$id,$use_key_as_id,$level,$i,$structured);
							}
						}
						else 
							$structured[$id]['children'] = self::sortCats($result,$id,$use_key_as_id,$level,$i);
					}
				}	
			}
		}
		
		return $structured;
	}
	
	public static function getCatSelection($table,$subtable,$id) {
		if (!self::tableExists($table.'_'.$subtable)) {
			db_query("CREATE TABLE `{$table}_{$subtable}` (
			`f_id` INT( 10 ) UNSIGNED NOT NULL ,
			`c_id` INT( 10 ) UNSIGNED NOT NULL ,
			INDEX ( `f_id` , `c_id` )
			) ENGINE = MYISAM ");
		}
		
		$sql = "SELECT {$table}_{$subtable}.* FROM {$table}_{$subtable} WHERE 1 ";
		
		if ($id > 0) {
			$sql .= " AND {$table}_{$subtable}.f_id = $id ";
		}
		$result = db_query_array ( $sql );
		if ($result) {
			$selection = array();
			foreach ($result as $row) {
				$selection[] = $row['c_id'];
			}
		}
		return $selection;
	}
	
	public static function getCatValues($table,$values_table,$id) {	
		if (!self::tableExists($table.'_'.$values_table)) {
			db_query("CREATE TABLE `{$table}_{$values_table}` (
			`f_id` INT( 10 ) UNSIGNED NOT NULL ,
			`c_id` INT( 10 ) UNSIGNED NOT NULL ,
			`value` VARCHAR( 255 ) NOT NULL ,
			INDEX ( `f_id` , `c_id` )
			) ENGINE = MYISAM ");
		}
		
		if (!($id > 0))
			return false;
			
		$sql = "SELECT {$table}_{$values_table}.* FROM {$table}_{$values_table} WHERE {$table}_{$values_table}.f_id = $id";
		$result = db_query_array($sql);
		if ($result) {
			$selection = array();
			foreach ($result as $row) {
				$selection[$row['c_id']] = $row['value'];
			}
		}
		return $selection;
	}
	
	public static function deleteCats($table,$id) {
		$subtables = self::getSubtables($table);
		if (is_array($subtables)) {
			foreach ($subtables as $s_table) {
				$fields = self::getTableFields($table.'_'.$s_table,true);
				
				if ($table == 'files' || !in_array('f_id',$fields));
					continue;
					
				$f_field = ($table == 'admin_groups') ? 'group_id' : 'f_id';
					
				return db_delete($table.'_'.$s_table,$id,$f_field);
			}
		}
	}
	
	public static function getFiles($table,$id,$field_name=false,$limit=0,$start_record=0,$randomize=false) {
		if (!self::tableExists($table))
			return false;
		
		$sql = "SELECT *, CONCAT('".str_ireplace('_files','',$table)."','_',f_id,'_',id) AS name FROM {$table} WHERE 1 ";
		
		if ($id > 0) {
			$sql .= " AND f_id = $id ";
		}
		if ($field_name) {
			$sql .= " AND field_name = '$field_name' ";
		}
		if ($randomize) {
			$sql .= " ORDER BY RAND() ";
		}
		else {
			$db_fields = DB::getTableFields($table,1);
			if (in_array('file_order',$db_fields)) {
				$sql .= " ORDER BY file_order ASC ";
			}
		}
		if ($limit > 0) {
			$start_record = ($start_record > 0) ? $start_record : '0';
			$sql .= " LIMIT $start_record,$limit ";
		}
		$result = db_query_array($sql);
		if ($randomize) {
			if ($result) {
				foreach ($result as $row) {
					self::$random_ids[$field_name] = $row['id'];
				}
			}
		}
		return $result;
	}
	
	public static function deleteFiles($table,$id) {
		global $CFG;
		
		$files = self::getFiles($table,$id);
		if (is_array($files)) {
			foreach ($files as $row) {
				File::deleteLike($row['name'],$row['dir']);
			}
			return db_delete($table,$id,'f_id');
		}
	}
	
	public static function getOrder($control_id,$user_id) {
		if (!($control_id > 0) || !($user_id > 0))
			return false;
			
		$sql = "SELECT * FROM admin_order WHERE control_id = $control_id AND user_id = $user_id";
		$result = db_query_array($sql);
		return $result[0];
	}
	
	public static function setOrder($id,$order_by,$order_asc,$control_id,$user_id) {
		if (empty($order_by) || !($control_id > 0) || !($user_id > 0))
			return false;
		
		if ($id > 0) {
			return self::update('admin_order',array('order_by'=>$order_by,'order_asc'=>$order_asc),$id);
		}
		else {
			return self::insert('admin_order',array('control_id'=>$control_id,'user_id'=>$user_id,'order_by'=>$order_by,'order_asc'=>$order_asc));
		}
	}
	
	public static function getImageSizes($field_name) {
		global $CFG;

		if (!$field_name)
			return $CFG->image_sizes;
		
		$sql = "SELECT * FROM admin_image_sizes WHERE field_name = '$field_name'";
		$result = db_query_array($sql);
		
		if ($result) {
			$sizes = unserialize($result[0]['value']);
			$k = key($sizes);
			if (!empty($sizes[$k])) {
				return $sizes;
			}
			else {
				return $CFG->image_sizes;
			}
		}
		else
			return $CFG->image_sizes;
	}
	
	public static function saveImageSizes($field_name,$size_info) {
		if (!$field_name || !$size_info) 
			return false;
		
		if ($result = db_query_array("SELECT id FROM admin_image_sizes WHERE field_name = '$field_name'")) {
			db_update('admin_image_sizes',$result[0]['id'],array('value'=>urldecode($size_info)));
		}
		else {
			db_insert('admin_image_sizes',array('field_name'=>$field_name,'value'=>urldecode($size_info)));
		}
	}
	
	public static function getFields($table,$f_id,$subtable_fields=false,$f_id_field=false,$order_by=false,$order_asc=false,$record_id=false,$limit_is_curdate=false,$dont_format_results=0) {
		global $CFG;

		$f_id = (strlen($f_id) > 0) ? $f_id : $record_id;
		
		if (!(strlen($f_id) > 0))
			return false;
			
		if (!is_array($subtable_fields)) {
			$subtable_fields = array('*');
		}

		$field_info = self::getTableFields($table);
		$f_id_field = ($f_id_field) ? $f_id_field : 'id';
		if (!strstr($f_id_field,',')) {
			$sql = "SELECT ".implode(',',$subtable_fields)." FROM $table WHERE {$f_id_field} = $f_id";
			if ($order_by) {
				if ($field_info[$order_by]['Type'] == 'datetime' && $limit_is_curdate) {
					$sql .= " AND $order_by >= '".date('Y-m-d 00:00:00')."' ";
				}

				$order_asc = ($order_asc) ? 'ASC' : 'DESC';
				$sql .= " ORDER BY $order_by $order_asc";
			}
			
			$sql .= " LIMIT 0,1 ";
			$result = db_query_array($sql);
			$row = $result[0];
		}
		else {
			$join_path = explode(',',$f_id_field);
			if (is_array($join_path)) {
				$i = 0;
				foreach ($join_path as $join_field) {
					if ($CFG->passive_override_id > 0) {
						$f_id = $CFG->passive_override_id;
						$CFG->passive_override_id = 0;
						$i++;
						continue;
					}
					
					if ($dont_format_results && $i == 0) {
						$i++;
						continue;
					}
						
					$join_field_parts = explode('.',$join_field);
					$where_name = ($select_name == 'id') ? $join_field_parts[1] : 'id';
					if ($i > 0)
						$where_name = ($join_field_parts[0] == $join_table) ? $select_name : $join_field_parts[1];
					$select_name = $join_field_parts[1];
					$join_table = $join_field_parts[0];
					
					$sql = "SELECT {$select_name} FROM {$join_table} WHERE {$where_name} = $f_id";
					//echo $sql.'<br>';
					$result = db_query_array($sql);
					$row = $result[0];
					$f_id = $row[$select_name];
					$i++;
				}
			}
			if (!$dont_format_results && $result) {
				$sql = "SELECT ".implode(',',$subtable_fields)." FROM $table WHERE id = $f_id";
				$result = db_query_array($sql);
			}
			$row = $result[0];
		}
		
		if (is_array($field_info) && is_array($row) && !$dont_format_results) {
			foreach ($row as $name => $value) {
				$row[$name] = '<span class="record_component">'.Grid::detectData($name,$value,$field_info).'</span>';
			}
		}
		return $row;
	}
	
	public static function getFieldsByLookup($table,$subtable,$f_id,$field_names=false) {
		$cats = self::getCatSelection($table,$subtable,$f_id);
		foreach ($cats as $c_id) {
			$field_name = (is_array($field_names)) ? implode(',',$field_names) : 'name';
			$result = db_query_array("SELECT id, {$field_name} FROM {$subtable} WHERE id = $c_id");
			$results[] = $result[0];
		}
		return $results;
	}
	
	public static function getForeignValue($field,$f_id,$directly_link_fields=false,$first_row=false) {
		global $CFG;
		
		$first_row = ($CFG->passive_override_id > 0) ? $CFG->passive_override_id : $first_row;
		if (empty($field) || (!($f_id > 0) && !$first_row))
			return '0';	
		
		$join_path = explode(',',$field);
		if (is_array($join_path)) {
			$i = 0;
			$c = count($join_path) - 1;
			foreach ($join_path as $join_field) {
				$join_field_parts = explode('.',$join_field);
				$where_name = ($select_name == 'id') ? $join_field_parts[1] : 'id';
				
				if ($directly_link_fields && $i > 0)
					$where_name = ($join_field_parts[0] == $join_table) ? $select_name : $join_field_parts[1];
				
				$select_name = $join_field_parts[1];
				$join_table = $join_field_parts[0];
				if ($first_row && $i == 0) {
					$row = $first_row;
				}
				elseif (strlen($f_id) > 0) {
					if (strstr($where_name,'|')) {
						$parts = explode('|',$where_name);
						$where_name = $parts[0];
					}
					if (strstr($select_name,'|')) {
						$select_name_parts = explode('|',$select_name);
						$select_name = $select_name_parts[0];
						array_shift($select_name_parts);

						foreach ($select_name_parts as $modifier) {
							if ($modifier == 'closest_date') {
								$sort_col = ",ABS(DATEDIFF(CURDATE(), {$select_name})) AS sort_col ";
								$order_by = "ORDER BY sort_col ASC";
							}
							elseif ($modifier == 'last_id' && $CFG->formula_last_id > 0) {
								$where_id = "AND id = ".$CFG->formula_last_id;
							}
						}
					}
					
					$sql = "SELECT id, {$select_name} {$sort_col} FROM {$join_table} WHERE {$where_name} = $f_id {$where_id} {$order_by}";
					//echo $sql.' | ';
					$result = db_query_array($sql);
					$row = $result[0];
					$sort_col = false;
					$order_by = false;
					$where_id = false;
				}
				
				$f_id = $row[$select_name];
				if ($CFG->formula_before_return) {
					$CFG->formula_last_id = $row['id'];
				}
				
				$i++;
			}
		}
		else {
			return false;
		}
		return $f_id;
	}
	
	public static function show_errors() {
		// display errors
		if ($this->errors) {
			echo '<ul class="errors">';
			foreach ($this->errors as $name => $error) {
				echo '<li>'.ucfirst(str_ireplace('[field]',$name,$error)).'</li>';
			}
			echo '</ul>';
		}
	}
	
	public static function serializeMultiples($fields_array,$table=false) {
		global $CFG;

		if ($table)
			$table_fields = self::getTableFields($table);
		
		if (is_array($fields_array)) {
			foreach ($fields_array as $key => $value) {
				if ($key == 'argument_insert_array')
					continue;
					
				$new_array = array();
				if (stristr($value,'array:')) {
					$value = str_ireplace('array:','',$value);
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
					else {
						$c_array = self::serializeCommas($value);
						$new_array = array_merge($new_array,$c_array);
					}
					$fields_array[$key] = serialize($new_array);
				}
				elseif ($table_fields[$key]['Type'] == 'datetime') {
					$fields_array[$key] = date('Y-m-d H:i:s',strtotime($value));
				}
			}
		}
		
		if (!$CFG->bypass_unserialize) {
			if (is_array($fields_array)) {
				$fields_array = Control::unSerializeAll($fields_array);
				foreach ($fields_array as $name => $value) {
					if (stristr($name,'argument')) {
						$arg_name = str_replace('argument_','',$name);
						$arguments[$arg_name] = $value;
						unset($fields_array[$name]);
					}
				}
				if ($arguments)
					$fields_array['arguments'] = serialize($arguments);
			}
		}
		
		return $fields_array;
	}
	
	public static function serializeCommas($value,$serialize=false) {
		if (stristr($value,',') || stristr($value,'=>')) {
			preg_match_all('#\((.*?)\)#',$value,$m);
			$parenthesis = $m[1];
			$value = preg_replace('#\((.*?)\)#','$$$',$value);
			$k = 0;
			$array = (stristr($value,',')) ? explode(',',$value) : array($value);
			if (is_array($array)) {
				foreach ($array as $v) {
					if (stristr($v,'=>')) {
						$array1 = explode('=>',$v);
						
						if (!stristr($array1[1],'$$$')) {
							$i = $array1[0];
							$new_array[$i] = $array1[1];
						}
						else {
							$i = $array1[0];
							if (strstr($parenthesis[$k],']') || strstr($parenthesis[$k],'[') || strstr($parenthesis[$k],'+') || strstr($parenthesis[$k],'-'))
								$new_array[$i] = '('.$parenthesis[$k].')';
							else
								$new_array[$i] = self::serializeCommas($parenthesis[$k]);
								
							$k++;
						}
					}
					else {
						if (stristr($v,'=>')) {
							$array = explode('=>',$v1);
							$i = $array[0];
							$new_array[$i] = $array[1];
						}
						else {
							$new_array[] = $v;
						}
					}
				}
			}
		}
		else {
			$new_array[] = $value;
		}
		
		if ($serialize)
			$fields_array = serialize($new_array);
		else
			$fields_array = $new_array;
			
		return $fields_array;
	}
	
	public static function adequateFilterResults($filter_results,$filter_properties,$table,$is_folder=false) {
		if (!is_array($filter_results) || !is_array($filter_properties) || !$table)
			return false;

		$table_fields = DB::getTableFields($table,1);
		$filter_results1 = $filter_results;
		if (is_array($filter_results['cat_selects'])) {
			foreach ($filter_results['cat_selects'] as $cat_table => $values) {
				if (!self::tableExists($table.'_'.$cat_table))
					unset($filter_results1['cat_selects'][$cat_table]);
			}
		}
		if (empty($filter_results1['cat_selects'])) {
			unset($filter_results1['cat_selects']);
		}
		
		foreach ($filter_properties as $k => $properties) {
			if ($properties['type'] == 'search') {
				foreach ($properties['subtable_fields'] as $field_name) {
					if (!in_array($field_name,$table_fields))
						unset($filter_results1['search']);
				}
			}
			else {
				if ($properties['subtable_fields'] && $properties['subtable'] && !$is_folder) {
					if (self::tableExists($properties['subtable'])) {
						$subtable_fields = DB::getTableFields($properties['subtable'],1);
						if (is_array($properties['subtable_fields'])) {
							foreach ($properties['subtable_fields'] as $s_field) {
								if (!in_array($s_field,$subtable_fields))
									unset($filter_results1[($properties['field_name'])]);
							}
						}
					}
				}
				elseif (!in_array($properties['field_name'],$table_fields) ) {
					unset($filter_results1[($properties['field_name'])]);
					unset($filter_results1[($properties['field_name']).'bbb']);
				}
			}
		}
		return $filter_results1;
	}
	
	public static function deleteRecursive($table,$id) {
		global $CFG;
		
		if (!$table || !($id > 0))
			return false;
			
		if (!db_delete($table,$id)) {
			Errors::add($CFG->ajax_delete_error);
			return false;
		}
		
		$sql = "SELECT id FROM $table WHERE p_id = $id";
		$result = db_query_array($sql);
		if ($result) {
			foreach ($result as $row1) {
				$id1 = $row1['id'];
				 self::deleteRecursive($table,$id1);
			}
		}
	}
	
	public static function countRows($table,$row=false) {
		if (!$table)
			return false;
		
		if (strstr($table,','))  {
			$join_path = explode(',',$table);
			if (is_array($join_path)) {
				foreach ($join_path as $join_field) {						
					$join_field_parts = explode('.',$join_field);
					$j_table = $join_field_parts[0];
					$j_field = $join_field_parts[1];
					$tables[$j_table] = $j_field;
				}
			}
			
			$sql = "SELECT COUNT({$j_table}.{$j_field}) AS c FROM {$j_table} ";
			$i = 0;
			$tables = array_reverse($tables);
			foreach ($tables as $j_table => $j_field) {
				if ($i > 0)
					$sql .= " LEFT JOIN {$j_table} ON ({$j_table}.{$j_field} = $prev_field) ";
				
				$prev_field = "{$j_table}.{$j_field}";
				$i++;
			}
			if ($row[$j_field])
				$sql .= " WHERE {$prev_field} = '{$row[$j_field]}' ";
		}
		else
			$sql = "SELECT COUNT({$table}.id) AS c FROM {$table} ";

		$result = db_query_array($sql);
		return $result[0]['c'];
	}
	
	public static function saveImageOrder($file_order,$table) {
		if (!is_array($file_order) || !$table)
			return false;
			
		$db_fields = DB::getTableFields($table.'_files',1);
		if (!in_array('file_order',$db_fields)) {
			$sql = "ALTER TABLE {$table}_files ADD file_order INT( 10 ) UNSIGNED NOT NULL";
			db_query($sql);
		}
		
		foreach ($file_order as $i => $id) {
			self::update($table.'_files',array('file_order'=>$i),$id);
		}
	}
	
	public static function recordExists($table,$fields_array) {
		if (!$table || !is_array($fields_array))
			return false;
			
		$sql = "SELECT id FROM {$table} WHERE 1 ";
		foreach ($fields_array as $field => $value) {
			$sql .= " AND {$table}.{$field} = '{$value}' ";
		}
		$sql .= " LIMIT 0,1";
		return (is_array(db_query_array($sql)));
	}
	
	public static function getFieldsLikeType($table,$type,$names_only=false){
		if (!$table || !$type)
			return false;
			
		$sql = "SHOW COLUMNS FROM {$table} WHERE TYPE LIKE '%{$type}%'";
		$result = db_query_array($sql);
		if ($result) {
			if (!$names_only) {
				foreach ($result as $row) {
					$key = $row['Field'];
					$fields[$key] = $row;
				}
			}
			elseif ($for_autocomplete) {
				foreach ($result as $row) {
					$key = $row['Field'];
					$fields[$key] = $key;
				}
			}
			else {
				foreach ($result as $row) {
					$fields[] = $row['Field'];
				}
			}
		}
		else {
			return false;
		}
		return $fields;
	}
}
?>