<?php
class GridDB {
	function getTableFields($table,$names_only=false,$for_autocomplete=false) {
		$result = db_query_array("DESCRIBE $table");
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

	function getSubtables($table) {
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
	
	function getTables() {
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
	
	function get($table,$fields,$join_tables=false,$join_subtables=false,$double_join_tables=false,$foreign_tables=false,$remote_tables=false,$foreign_order=false,$start_row=0,$per_page=0,$filter_results=false,$order_by=false,$order_asc=false,$count=false,$f_id=0,$f_id_field=false,$f_where=false,$get_total=false,$get_average=false) {
		$per_page = (!empty($filter_results['per_page'])) ? $filter_results['per_page'] : $per_page;
		$order_asc = ($order_asc) ? 'ASC' : 'DESC';
		
		if ($count) {
			$sql = "SELECT COUNT(DISTINCT {$table}.id) AS total FROM {$table} ";
		}
		elseif ($get_total) {
			$sql = "SELECT SUM(".implode(',',$fields).") AS grand_total FROM {$table} ";
		}
		elseif ($get_average) {
			$sql = "SELECT AVG(".implode(',',$fields).") AS grand_total FROM {$table} ";
		}
		else {
			$sql = "SELECT ".implode(',',$fields)." FROM {$table} ";
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
				$sql .= " LEFT JOIN {$j_table} {$j_as} ON ({$table}.{$j_field} = {$j_alias}.id) ";
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
			$i = 1;
			$c = count($remote_tables);
			foreach ($remote_tables as $r_table => $r_field) {
				if (!@array_key_exists($r_table,$foreign_tables) && !@array_key_exists($r_table,$join_tables) && !@array_key_exists($r_table,$join_subtables) && !@array_key_exists($r_table,$double_join_tables)) {
					if ($r_table != $table) {
						$j_field = ($prev_field == 'id') ? $r_field : 'id';
						$sql .= " LEFT JOIN {$r_table} ON ({$prev_table}.{$prev_field} = {$r_table}.{$j_field}) ";
					}
				}
				$prev_table = $r_table;
				$prev_field = $r_field;
				$i++;
			}
		}
		
		$sql .= " WHERE 1 ";
		
		if (is_array($foreign_order)) {
			foreach ($foreign_order as $f_table => $properties) {
				$sql .= " AND {$f_table}xx.{$properties['order_by']} IS NULL";
			}
		}
		
		if (is_array($filter_results)) {
			if (!empty($filter_results['first_letter'])) {
				$r_subtable = (!empty($filter_results['first_letter_subtable'])) ? $filter_results['first_letter_subtable'] : $table;
				$sql .= " AND {$r_subtable}.{$filter_results['first_letter_field']} LIKE '{$filter_results['first_letter']}%' ";
			}
			
			foreach ($filter_results as $r_name => $r_value) {
				if (strlen($r_value) == 0)
					continue;
				
				$w_table = (@array_key_exists($r_name,$f_where)) ? $f_where[$r_name] : $table;
					
				if ($r_name == 'cat_selects') {
					$sql .= " AND ( ";
					foreach ($r_value as $c_table => $cats) {
						if (is_array($cats)) {
							foreach ($cats as $cat) {
								$sql .= " {$c_table}.id = $cat OR";
							}
						}
					}
					$sql = substr($sql,0,-2);
					$sql .= " ) ";
				}
				elseif ($r_name == 'first_letter' || $r_name == 'first_letter_field' || $r_name == 'first_letter_subtable' || $r_name == 'per_page') {
					continue;
				}
				elseif ($r_name == 'search') {
					if (is_array($_REQUEST['search_fields'])) {
						$sql .= " AND ( ";
						foreach ($_REQUEST['search_fields'] as $s_field => $s_subtable) {
							$s_subtable = (!empty($s_subtable)) ? $s_subtable : $table;
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
				else {
					$sql .= " AND {$r_name} = '$r_value' ";
				}
			}
		}
		
		if ($f_id > 0) {
			$sql .= " AND {$table}.{$f_id_field} = $f_id ";
		}
		
		if (!$count && !$get_average && !$get_total) {
			$sql .= " GROUP BY {$table}.id ";
			
			if ($order_by) {
				$sql .= " ORDER BY $order_by $order_asc ";
			}
			else {
				$sql .= " ORDER BY {$table}.id $order_string ";
			}
			
			if ($start_row > 0 || $per_page > 0) {
				$sql .= " LIMIT {$start_row},{$per_page} ";
			}
		}
		//echo $sql;
		$result = db_query_array($sql);
		
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
	
	function getOrder($control_id,$user_id) {
		if (!($control_id > 0) || !($user_id > 0))
			return false;
			
		$sql = "SELECT * FROM admin_order WHERE control_id = $control_id AND user_id = $user_id";
		$result = db_query_array($sql);
		return $result[0];
	}
	
	function setOrder($id,$order_by,$order_asc,$control_id,$user_id) {
		if (empty($order_by) || !($control_id > 0) || !($user_id > 0))
			return false;
		
		if ($id > 0) {
			return FormDB::update('admin_order',array('order_by'=>$order_by,'order_asc'=>$order_asc),$id);
		}
		else {
			return FormDB::insert('admin_order',array('control_id'=>$control_id,'user_id'=>$user_id,'order_by'=>$order_by,'order_asc'=>$order_asc));
		}
	}
	
	function getTotal($field,$table) {
		if (!$field || !$table)
			return false;
		
		if (!empty($field['subtable']) && empty($field['subtable_fields']))
			return false;	
			
		$name = (!is_array($field['subtable_fields'])) ? $field['name'] : implode('+',$field['subtable_fields']);
		$table = (empty($field['subtable'])) ? $table : $field['subtable'];
		
		$sql = "SELECT SUM({$name}) AS grand_total FROM {$table}";
		echo $sql;
		$result = db_query_array($sql);
		
		return $result[0]['grand_total'];
	}
	
	function getAverage($field,$table) {
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
}
?>