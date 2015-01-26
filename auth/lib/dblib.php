<?php

if (! isset ( $CFG->db_debug )) {
	$CFG->db_debug = false;
}

function db_connect($dbhost, $dbname, $dbuser, $dbpass) {
	global $CFG;
	
	if (! $dbh = mysql_connect ( $dbhost, $dbuser, $dbpass )) {
		if ($CFG->db_debug == 'Y') {
			$output = "Can't connect to $dbhost as $dbuser";
			$output .= "MySQL Error: ".mysql_error ();
			trigger_error($output,E_USER_ERROR);
		} else {
			$output = "Database error encountered";
			trigger_error($output,E_USER_WARNING);
		}
	}
	
	if (! mysql_select_db ( $dbname )) {
		if ($CFG->db_debug == 'Y') {
			$output = "Can't select database $dbname";
			$output .= "MySQL Error: ".mysql_error ();
			trigger_error($output,E_USER_ERROR);
		} else {
			$output = "Database error encountered";
			trigger_error($output,E_USER_WARNING);
		}
	}
	
	mysql_set_charset('utf8');
	return $dbh;
}

function db_select_db($dbname) {
	return mysql_select_db ( $dbname );
}

function db_disconnect() {
	/* disconnect from the database, we normally don't have to call this function
	* because PHP will handle it */ 
	
	mysql_close ();
}

function db_query($query, $debug = false, $die_on_debug = true, $silent = false, $unbuffered = false) {
	global $CFG;
	
	if ($unbuffered)
		$qid = mysql_unbuffered_query ( $query );
	else
		$qid = mysql_query ( $query );
	
	if (! $qid && ! $silent) {
		if ($CFG->db_debug == 'Y') {
			$output = "Can't execute query";
			$output .= "<pre>" . htmlspecialchars ( $query ) . "</pre>";
			$output .= "MySQL Error: ".mysql_error ();
			$output .= "Debug: ";
			$output .= print_r(debug_backtrace (),true);
			trigger_error($output,E_USER_ERROR);
		} else {
			$output = "Database error encountered";
			$output .= print_r(func_get_args(),true);
			trigger_error($output,E_USER_WARNING);
		}
	}
	return $qid;
}

function db_fetch_array($qid, $type = MYSQL_BOTH) {
	return mysql_fetch_array ( $qid, $type );
}

function db_num_rows($qid) {
	return mysql_num_rows ( $qid );
}

function db_affected_rows() {
	return mysql_affected_rows ();
}

function db_insert_id() {
	if (mysql_insert_id () === 0)
		return (! mysql_errno ());
	
	return mysql_insert_id ();
}

function db_free_result($qid) {
	mysql_free_result ( $qid );
}

function db_query_array($query, $key = '', $first_record = false, $unbuffered = false, $val_field = '') {
	global $CFG;
	
	$result = db_query ( $query, false, true, false, $unbuffered );
	
	if (!is_resource($result))
		return false;
	
	$amt = db_num_rows ( $result );
	
	if ($amt > 100000) {
		$params = func_get_args ();
		
		if ($CFG->db_debug == 'Y') {
			$output = "$_SERVER[HTTP_HOST] DB Overload $amt ROWS";
			$output .= "<pre>$query\r\nIn " . __FILE__ . ' on Line ' . __LINE__ . "\r\n_SERVER dump:\r\n" . print_r ( $_SERVER, true ) . "\r\n_POST dump:\r\n" . print_r ( $_POST, true ) . "\r\n_GET dump:\r\n" . print_r ( $_GET, true ) . "\r\nfunc_args dump:\r\n" . print_r ( $params, true ) . "\r\ndebug_backtrace dump:\r\n" . print_r ( debug_backtrace (), true ) . '</pre>';
			trigger_error($output,E_USER_ERROR);
		}
	}

	if ($key && ! $first_record)
		while ( $row = db_fetch_array ( $result, MYSQL_ASSOC ) )
			$return_arr [$row [$key]] = ($val_field) ? $row [$val_field] : $row;
	else
		while ( $row = db_fetch_array ( $result, MYSQL_ASSOC ) )
			$return_arr [] = ($val_field) ? $row [$val_field] : $row;
	db_free_result ( $result ); // clear memory
	

	if ($first_record && isset ( $return_arr [0] ))
		return $return_arr [0];
	else if (! $first_record)
		return @$return_arr;
	else
		return false;
}

function db_insert($table, $info, $date = '', $ignore = false, $silent = false, $echo_sql = false, $return_bool = false, $delayed = false) {
	
	$ignore = ($ignore) ? 'IGNORE' : '';
	$delayed = ($delayed) ? 'DELAYED' : '';
	
	$sql = "INSERT $delayed $ignore INTO $table (";
	$vals = ") VALUES (";
	
	foreach ( $info as $key => $val ) {
		$sql .= "`$key`,";
		$vals .= "'" . addslashes ( $val ) . "',";
	}
	
	if ($date) {
		$sql .= "`$date`,";
		$vals .= 'NOW(),';
	}
	
	// remove the trailing commas
	$sql = substr ( $sql, 0, - 1 );
	$vals = substr ( $vals, 0, - 1 );
	$sql .= "$vals)";
	if (! $silent) {
		$return_val = db_query ( $sql );
	} else {
		$return_val = db_query ( $sql, false, false, true );
		if (! $return_val)
			return $return_val;
	}
	
	if ($return_bool)
		return $return_val;
	
	return db_insert_id ();
}

function db_update($table, $id, $info, $pk = 'id', $date = '') {
	$sql = "UPDATE $table SET ";
	
	if (is_array ( $info )) {
		foreach ( $info as $key => $val ) {
			$sql .= "`$key`='" . addslashes ( $val ) . "',";
		}
	}
	
	if ($date) {
		$sql .= "`$date`=NOW(),";
	}
	
	if (! is_array ( $pk )) {
		$sql = substr ( $sql, 0, - 1 ) . " WHERE `$pk`='" . addslashes ( $id ) . "'";
	} else {
		$sql = substr ( $sql, 0, - 1 ) . " WHERE";
		
		foreach ( $pk as $key ) {
			list ( , $val ) = each ( $id );
			$sql .= " `$key`='" . addslashes ( $val ) . "' AND";
		}
		
		$sql = substr ( $sql, 0, - 3 );
	}
	db_query ( $sql );

	return db_affected_rows ();
}

function db_delete($table, $id, $pk = 'id') {
	if (! is_array ( $pk ))
		$where = "`$pk`='" . addslashes ( $id ) . "'";
	else {
		$where = '';
		
		foreach ( $pk as $key ) {
			list ( , $val ) = each ( $id );
			$where .= " `$key`='" . addslashes ( $val ) . "' AND";
		}
		
		$where = substr ( $where, 0, - 3 );
	}
	db_query ( "DELETE FROM $table WHERE $where" );
	
	return db_affected_rows ();
}

function db_split_keywords($keywords, $fields, $modifier = 'AND', $partial = false, $use_begin_only = false) {
	if ($keywords) {
		$quoted = explode ( "\\\"", $keywords );
		
		$ct = count ( $quoted );
		for($i = 0; $i < $ct; $i ++) {
			if ($i == 0 && ! $quoted [$i]) {
				//quote came at beginning of string
				$begin = true;
				$i ++;
			}
			if ($begin) {
				$words [] = $quoted [$i];
			} else {
				$phrase = explode ( " ", $quoted [$i] );
				$ct2 = count ( $phrase );
				for($n = 0; $n < $ct2; $n ++) {
					if ($phrase [$n]) {
						$words [] = $phrase [$n];
					}
				}
			}
			$begin = ! $begin;
		}
		
		$ct = count ( $words );
		for($i = 0; $i < $ct; $i ++) {
			if ($words [$i]) {
				$words [$i] = strtolower ( addslashes ( $words [$i] ) );
				
				if (($words [$i] == 'and' || $words [$i] == 'or' || $words [$i] == 'not') && $i < ($ct - 1)) {
					if ($words [$i] == 'not') {
						$i ++;
						if ($sql_out) {
							$sql_out .= " $modifier ";
						}
						
						$ct2 = count ( $fields );
						for($x = 0; $x < $ct2; $x ++) {
							if ($x == 0) {
								$sql_out .= "(";
							}
							if (! $partial) {
								$sql_out .= "REPLACE(CONCAT(' ',$fields[$x],' '),',',' ')" . " NOT LIKE '% " . $words [$i] . " %'";
							} else {
								$sql_out .= "$fields[$x] NOT LIKE '" . (! $use_begin_only ? '%' : '') . $words [$i] . "%'";
							}
							if ($x < $ct2 - 1) {
								$sql_out .= " $modifier ";
							} else {
								$sql_out .= ")";
							}
						}
					} else if ($i > 0) {
						$sql_out .= " " . strtoupper ( $words [$i] ) . " ";
						$boolean = true;
					}
				} else {
					if ($sql_out && ! $boolean) {
						$sql_out .= " $modifier ";
					}
					
					$ct2 = count ( $fields );
					for($x = 0; $x < $ct2; $x ++) {
						if ($x == 0) {
							$sql_out .= "(";
						}
						if (! $partial) {
							$sql_out .= "REPLACE(REPLACE(CONCAT(' ',$fields[$x],' '),'~',' '),',',' ')" . " LIKE '% " . $words [$i] . " %'";
						} else {
							$sql_out .= "$fields[$x] LIKE '" . (! $use_begin_only ? '%' : '') . $words [$i] . "%'";
						}
						if ($x < $ct2 - 1) {
							$sql_out .= " OR ";
						} else {
							$sql_out .= ")";
						}
					}
					$boolean = false;
				}
			}
		}
	}
	return $sql_out;
}

function db_date($date_str, $str_format = 'm/d/Y', $invalid = '-') {
	/* takes mysql date and/or time in the following format:
	yyyy-mm-dd hh:mm:ss
	and formats using the php date function
	*/
	if ($date_str == '' || $date_str == '0000-00-00' || $date_str == '0000-00-00 00:00:00')
		return $invalid;
	
	list ( $date, $time ) = explode ( ' ', $date_str );
	list ( $year, $month, $day ) = explode ( '-', $date );
	list ( $hour, $minute, $second ) = explode ( ':', $time );
	
	return date ( $str_format, db_mktime ( ( int ) $hour, ( int ) $minute, ( int ) $second, ( int ) $month, ( int ) $day, ( int ) $year ) );
}

function db_mktime() {
	$objArgs = func_get_args ();
	$nCount = count ( $objArgs );
	if ($nCount < 7) {
		$objDate = getdate ();
		if ($nCount < 1)
			$objArgs [] = $objDate ["hours"];
		if ($nCount < 2)
			$objArgs [] = $objDate ["minutes"];
		if ($nCount < 3)
			$objArgs [] = $objDate ["seconds"];
		if ($nCount < 4)
			$objArgs [] = $objDate ["mon"];
		if ($nCount < 5)
			$objArgs [] = $objDate ["mday"];
		if ($nCount < 6)
			$objArgs [] = $objDate ["year"];
		if ($nCount < 7)
			$objArgs [] = - 1;
	}
	$nYear = $objArgs [5];
	$nOffset = 0;
	if ($nYear < 1970) {
		if ($nYear < 1902)
			return 0;
		else if ($nYear < 1952) {
			$nOffset = - 2650838400;
			$objArgs [5] += 84;
			// Apparently dates before 1942 were never DST
			if ($nYear < 1942)
				$objArgs [6] = 0;
		} else {
			$nOffset = - 883612800;
			$objArgs [5] += 28;
		}
	}
	
	return call_user_func_array ( "mktime", $objArgs ) + $nOffset;
}

function db_start_transaction() {
	$sql = "START TRANSACTION";
	db_query($sql);
}

function db_commit() {
	$sql = "COMMIT";
	db_query($sql);
}
?>