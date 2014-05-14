<?php
	class RecordDB {
		function getFieldsByLookup($table,$subtable,$f_id) {
			$cats = FormDB::getCatSelection($table,$subtable,$f_id);
			foreach ($cats as $c_id) {
				$result = db_query_array("SELECT id, name FROM {$subtable} WHERE id = $c_id");
				$results[] = $result[0];
			}
			return $results;
		}
		
		function getFields($table,$f_id,$subtable_fields=false,$f_id_field=false,$order_by=false,$order_asc=false,$record_id=false,$limit_is_curdate=false) {
			global $CFG;
			
			$f_id = ($f_id > 0) ? $f_id : $record_id;
			
			if (!($f_id > 0))
				return false;
				
			if (!is_array($subtable_fields)) {
				$subtable_fields = array('*');
			}
			
			$field_info = GridDB::getTableFields($table);
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
					foreach ($join_path as $join_field) {
						$join_field_parts = explode('.',$join_field);
						$where_name = ($select_name == 'id') ? $join_field_parts[1] : 'id'; 
						$select_name = $join_field_parts[1];
						$join_table = $join_field_parts[0];
						
						$sql = "SELECT {$select_name} FROM {$join_table} WHERE {$where_name} = $f_id";
						$result = db_query_array($sql);
						$row = $result[0];
						$f_id = $row[$select_name];
					}
				}
				$sql = "SELECT ".implode(',',$subtable_fields)." FROM $table WHERE id = $f_id";
				$result = db_query_array($sql);
				$row = $result[0];
			}
			
			if (is_array($field_info) && is_array($row)) {
				foreach ($row as $name => $value) {
					$row[$name] = '<span class="record_component">'.Grid::detectData($name,$value,$field_info).'</span>';
				}
			}
			return $row;
		}
		
		function getForeignValue($field,$f_id) {
			if (empty($field) || !($f_id > 0))
				return false;

			$join_path = explode(',',$field);
			if (is_array($join_path)) {
				foreach ($join_path as $join_field) {
					$join_field_parts = explode('.',$join_field);
					$where_name = ($select_name == 'id') ? $join_field_parts[1] : 'id'; 
					$select_name = $join_field_parts[1];
					$join_table = $join_field_parts[0];
					$sql = "SELECT {$select_name} FROM {$join_table} WHERE {$where_name} = $f_id";
					$result = db_query_array($sql);
					$row = $result[0];
					$f_id = $row[$select_name];
				}
			}
			else {
				return false;
			}
			return $f_id;
		}
	}
?>