<?php

class FormDB {
	static $random_ids;

	function insert($table, $fields_array,$date_fields=false) {
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

		$fields_array = FormDB::serializeMultiples($fields_array);
		
		if (!$CFG->backstage_mode)
			$fields_array = array_map('mysql_real_escape_string',$fields_array);
		
		$insert_id = db_insert ( $table, $fields_array );
		
		if ($cats_array) {
			foreach ($cats_array as $subtable => $cat) {
				db_delete($table.'_'.$subtable,$insert_id,'f_id');
				foreach ($cat as $c_id) {
					db_insert($table.'_'.$subtable,array('f_id' => $insert_id, 'c_id' => $c_id));
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
	
	function update($table, $fields_array,$id) {
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
		
		$fields_array = FormDB::serializeMultiples($fields_array);
		if (!$CFG->backstage_mode)
			$fields_array = array_map('mysql_real_escape_string',$fields_array);
			
		$insert_id = db_update($table,$id,$fields_array);
		if ($cats_array) {
			foreach ($cats_array as $subtable => $cat) {
				db_delete($table.'_'.$subtable,$id,'f_id');
				foreach ($cat as $c_id) {
					db_insert($table.'_'.$subtable,array('f_id' => $id, 'c_id' => $c_id));
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
		return $insert_id;
	}
	
	function createTable($table,$fields_array,$enum_fields=false) {
		if (empty($table) || !is_array($fields_array))
			return false;
		
		$sql = " CREATE TABLE {$table} (
				 id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,";
		
		foreach ($fields_array as $field => $type) {
			if (stristr($type,'||')) {
				$type1 = explode('||',$type);
				$type = $type[0];
				$subfields = unserialize($type[1]);
				if ($is_array($subfields)) {
					$sql4 = "CREATE TABLE `{$table}_grid_{$field}` (
							id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
							`f_id` INT( 10 ) UNSIGNED NOT NULL ,";
	
					foreach ($subfields as $method => $args) {
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
			if (!FormDB::tableExists("{$table}_{$field}")) {
				$cats = db_query($sql2);
			}
		}
		if ($sql3) {
			if (!FormDB::tableExists("{$table}_files")) {
				$files = db_query($sql3);
			}
		}
		if ($sql4) {
			if (!FormDB::tableExists("{$table}_grid_{$field}")) {
				$files = db_query($sql4);
			}
		}
		return db_query($sql);
	}
	
	function editTable($table,$fields_array,$enum_fields=false) {
		if (empty($table))
			return false;
			
		$table_fields = GridDB::getTableFields($table,true);
		
		if (!is_array($table_fields) || !is_array($fields_array))
			return false;
			
		foreach ($fields_array as $field => $type) {
			if (stristr($type,'||')) {
				$type1 = explode('||',$type);
				$type = $type1[0];
				$subfields = unserialize($type1[1]);
				if (is_array($subfields)) {
					if (!FormDB::tableExists("{$table}_grid_{$field}")) {
						$sql4 = "CREATE TABLE `{$table}_grid_{$field}` (
							id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
							`f_id` INT( 10 ) UNSIGNED NOT NULL ,";
					}
					else {
						$add = ' ADD ';
						$table_fields1 = GridDB::getTableFields("{$table}_grid_{$field}",true);
					}
					foreach ($subfields as $method => $args) {
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
					if (!FormDB::tableExists("{$table}_grid_{$field}")) {	
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
				if (!FormDB::tableExists("{$table}_files"))
					db_query($sql3);
			}
			elseif ($sql4) {
				if (!FormDB::tableExists("{$table}_grid_{$field}")) {
					$cats = db_query($sql4);
				}
				else {
					db_query("ALTER TABLE {$table}_grid_{$field} ADD $sql5");
				}
			}
			elseif ($sql2) {
				if (!FormDB::tableExists("{$table}_{$field}")) {
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

		foreach ($table_fields as $field)  {
			if (($field != 'id') && (!array_key_exists($field,$fields_array))) {
				db_query("ALTER TABLE $table DROP {$table}.$field");
			}
		}
	}
	
	function tableExists($table) {
		$sql = "SELECT 1 FROM {$table} LIMIT 0";
		if (mysql_query($sql)) {
			return true;
		}
		else {
			return false;
		}
	}
	
	function delete($id) {
		$sql = "DELETE FROM notes WHERE f_id = $id AND f_type = 'C'";
		db_query ( $sql );
		
		$sql = "DELETE FROM client_username WHERE client_id = $id";
		db_query ( $sql );
		
		return db_delete ( 'client', $id );
	}
	
	function getForm($table,$id = 0,$f_id=0,$id_required=false) {
		if ($id_required && !($id > 0))
			return false;
		
		$sql = "SELECT {$table}.* FROM {$table} WHERE 1 ";
		if ($id > 0) {
			$sql .= " AND  {$table}.id = $id ";
		}
		if ($f_id > 0) {
			$sql .= " AND  {$table}.f_id = $f_id ";
		}
		$result = db_query_array ( $sql );
		return $result[0];
	}
	
	function getMaxId($table,$f_id=false) {
		$sql = "SELECT MAX(id) AS id FROM {$table} WHERE 1 ";

		if ($f_id > 0) {
			$sql .= " AND f_id = $f_id ";
		}
		$result = db_query_array ( $sql );
		return $result[0]['id'];
	}
	
	function getSubTable($table, $table_fields=false,$f_id=0,$concat_char=false,$f_id_field=false) {
		$concat_char = ($concat_char) ? $concat_char : ' ';
		
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
		elseif (is_array($table_fields)) {
			if (!empty($table_fields[0]))
				$sql .= " ORDER BY {$table}.{$table_fields[0]} ASC ";
		}
		
		$result = db_query_array ( $sql );
		$fields_array = array();
		if ($result) {
			$table_info = GridDB::getTableFields($table);
			foreach ($result as $row) {
				$r_id = $row['id'];
				if (!in_array('id',$table_fields))
					unset($row['id']);
				
				if (is_array($row)) {
					foreach ($row as $name=>$field) {
						$row[$name] = Grid::detectData($name,$field,$table_info);
					}
				}
				$fields_array[$r_id] = implode($concat_char,$row);
			}
		}
		return $fields_array;
	}
	
	function getGridValues($table, $table_fields=false,$f_id=0) {
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
		elseif (is_array($table_fields)) {
			if (!empty($table_fields[0]))
				$sql .= " ORDER BY {$table}.id ASC ";
		}
		
		return db_query_array($sql);
	}
	
	function getUniqueValues($table,$field_name) {
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
	
	function getCats($table,$f_id=0) {
		$sql = "SELECT {$table}.* FROM {$table} WHERE 1 ";
		
		if ($f_id > 0) {
			$sql .= " AND {$table}.f_id = $f_id ";
		}
		
		$table_fields = GridDB::getTableFields($table,true);
		if (in_array('order',$table_fields)) {
			$sql .= " ORDER BY {$table}.order ASC";
		}
		
		$result = db_query_array ( $sql );
		return FormDB::sortCats($result,0);
	}
	
	function sortCats($result,$p_id) {
		$structured = array();
		if (is_array($result)) {
			foreach ($result as $row) {
				if ($row['p_id'] == $p_id) {
					$id = $row['id'];
					$structured[$id]['row'] = $row;
					$structured[$id]['children'] = FormDB::sortCats($result,$id);
				}	
			}
		}
		return $structured;
	}
	
	function getCatSelection($table,$subtable,$id) {
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
	
	function deleteCats($table,$id) {
		$subtables = GridDB::getSubtables($table);
		if (is_array($subtables)) {
			foreach ($subtables as $s_table) {
				$fields = GridDB::getTableFields($table.'_'.$s_table,true);
				
				if ($table == 'files' || !in_array('f_id',$fields));
					continue;
					
				$f_field = ($table == 'admin_groups') ? 'group_id' : 'f_id';
					
				db_delete($table.'_'.$s_table,$id,$f_field);
			}
		}
	}
	
	function getFiles($table,$id,$field_name=false,$limit=0,$start_record=0,$randomize=false) {
		if (!FormDB::tableExists($table))
			return false;
		
		$sql = "SELECT *, CONCAT('".str_ireplace('_files','',$table)."','_',f_id,'_',id) AS name FROM {$table} WHERE 1 ";
		
		if ($id > 0) {
			$sql .= " AND f_id = $id ";
		}
		if ($field_name) {
			$sql .= " AND field_name = '$field_name' ";
		}
		if ($randomize) {
			/*
			if (is_array(FormDB::$random_ids[$field_name])) {
				foreach (FormDB::$random_ids[$field_name] as $id) {
					$sql .= " AND id != $id ";
				}
			}
			*/
			$sql .= " ORDER BY RAND() ";
		}
		if ($limit > 0) {
			$start_record = ($start_record > 0) ? $start_record : '0';
			$sql .= " LIMIT $start_record,$limit ";
		}
		$result = db_query_array($sql);
		if ($randomize) {
			if ($result) {
				foreach ($result as $row) {
					FormDB::$random_ids[$field_name] = $row['id'];
				}
			}
		}
		return $result;
	}
	
	function deleteFiles($table,$id) {
		global $CFG;
		
		$files = FormDB::getFiles($table,$id);
		if (is_array($files)) {
			foreach ($files as $row) {
				File::deleteLike($row['name'],$row['dir']);
			}
			return db_delete($table,$id,'f_id');
		}
	}
	
	function serializeMultiples($fields_array) {
		global $CFG;

		if (is_array($fields_array)) {
			$new_array = array();
			foreach ($fields_array as $key => $value) {
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
						$c_array = FormDB::serializeCommas($value);
						$new_array = array_merge($new_array,$c_array);
					}
					$fields_array[$key] = serialize($new_array);
				}
				elseif (stristr($key,'date')) {
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
	
	function serializeCommas($value,$serialize=false) {
		if (stristr($value,',')) {
			preg_match_all('#\((.*?)\)#',$value,$m);
			$parenthesis = $m[1];
			$value = preg_replace('#\((.*?)\)#','$$$',$value);
			$k = 0;
			$array = explode(',',$value);
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
							$new_array[$i] = FormDB::serializeCommas($parenthesis[$k]);
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
	
	function saveImageSizes($field_name,$size_info) {
		if (!$field_name || !$size_info) 
			return false;
		
		if ($result = db_query_array("SELECT id FROM admin_image_sizes WHERE field_name = '$field_name'")) {
			db_update('admin_image_sizes',$result[0]['id'],array('value'=>urldecode($size_info)));
		}
		else {
			db_insert('admin_image_sizes',array('field_name'=>$field_name,'value'=>urldecode($size_info)));
		}
	}
	
	function getImageSizes($field_name) {
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
	
	function isUniqueValue($table,$field_name,$value) {
		if (empty($table) || empty($field_name) || empty($value))
			return true;
			
		$sql = "SELECT * FROM {$table} WHERE {$field_name} = '{$value}'";
		$result = db_query_array($sql);
		return (!is_array($result));
	}
}

?>