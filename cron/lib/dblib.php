<?php

if (! isset ( $CFG->db_debug )) {
	$CFG->db_debug = false;
}

$db_retries = 5;
$db_timeout = 400; //milliseconds
$db_transaction = array();

function db_connect($dbhost, $dbname, $dbuser, $dbpass) {
	global $CFG,$dbh;
	
	if (class_exists('PDO')) {
		try {
			$dbh = new PDO('mysql:host='.$dbhost.';dbname='.$dbname.';charset=utf8', $dbuser, $dbpass,array(PDO::ATTR_EMULATE_PREPARES => false,PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
		} 
		catch (PDOException $e) {
			if ($CFG->db_debug == 'Y') {
				$output = "Can't connect to $dbhost as $dbuser \n";
				$output .= "MySQL Error: ".$e->getMessage();
				trigger_error($output,E_USER_ERROR);
			}
			else {
				$output = "Database error encountered";
				trigger_error($output,E_USER_WARNING);
			}
			return false;
		}
	}
	else {
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
}

function db_select_db($dbname) {
	return mysql_select_db ( $dbname );
}

function db_disconnect() {
	mysql_close ();
}

function db_query($query, $debug = false, $die_on_debug = true, $silent = false, $unbuffered = false,$return_resource=false) {
	global $CFG,$dbh,$db_transaction,$db_retries,$db_timeout;
	
	if (class_exists('PDO')) {
		try {
			$qid = $dbh->query($query);
			if ($dbh->inTransaction() && !$return_resource)
				$db_transaction[] = $query;
		}
		catch (PDOException $e) {
			if ($dbh->inTransaction()) {
				for ($i = 1; $i <= $db_retries; $i++) {
					$dbh->rollBack();
					if (db_is_syntax_error()) {
						db_log_error($e,$query);
						return false;
					}
					
					usleep($db_timeout);
					trigger_error('Transaction failed. Retrying...',E_USER_WARNING);
					$dbh->beginTransaction();
					
					try {
						foreach ($db_transaction as $i_query) {
							$dbh->query($i_query);
						}
						
						$qid = $dbh->query($query);
						break;
					}
					catch (PDOException $e) {
						if ($i < $db_retries)
							continue;
						
						db_log_error($e,$query);
						return false;
					}
				}
			}
			else {
				if (db_is_syntax_error()) {
					db_log_error($e,$query);
					return false;
				}
				
				for ($i = 1; $i <= $db_retries; $i++) {
					usleep($db_timeout);
					trigger_error('Single query failed. Retrying...',E_USER_WARNING);
					
					try {
						$qid = $dbh->query($query);
						break;
					}
					catch (PDOException $e) {
						if ($i < $db_retries)
							continue;
					
						db_log_error($e,$query);
						return false;
					}
				}
			}
		}
		
		if ($return_resource)
			return $qid;
		else {
			if ($dbh->lastInsertId() > 0)
				return $dbh->lastInsertId();
			else
				return $qid->rowCount();
		}
			
	}
	else {
		if ($unbuffered)
			$qid = mysql_unbuffered_query ( $query );
		else
			$qid = mysql_query ( $query );
		
		if (! $qid && ! $silent) {
			if ($CFG->db_debug == 'Y') {
				$output = "Can't execute query";
				$output .= "<pre>".$query."</pre>";
				$output .= "MySQL Error: ".mysql_error ();
				$output .= "Debug: ";
				$output .= print_r(debug_backtrace (),true);
				trigger_error($output,E_USER_ERROR);
			} else {
				$output = "Database error: ";
				$output .= mysql_error();
				$output .= ' '.$query;
				trigger_error($output,E_USER_WARNING);
			}
		}
		return $qid;
	}
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
	
	if (class_exists('PDO')) {
		$result = db_query($query,false,true,false,$unbuffered,true);
		if (!$result)
			return false;
		
		$amt = $result->rowCount();
		if (!($amt > 0))
			return false;
		
		$return_arr = $result->fetchAll(PDO::FETCH_ASSOC);
		$result->closeCursor();
		
		if (!empty($return_arr))
			return $return_arr;
		else
			return false;
	}
	else {
		$result = db_query($query,false,true,false,$unbuffered);
		if (!is_resource($result))
			return false;
		
		$amt = db_num_rows($result);
		
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
	
	$sql = substr ( $sql, 0, - 1 );
	$vals = substr ( $vals, 0, - 1 );
	$sql .= "$vals)";
	
	if (class_exists('PDO')) {
		$result = db_query($sql);
		return $result;
	}
	else {
		$return_val = db_query($sql);
		if ($return_bool)
			return $return_val;
		
		return db_insert_id ();
	}
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
	
	if (class_exists('PDO')) {
		$result = db_query($sql);
		return $result;
	}
	else {
		db_query($sql);
		return db_affected_rows ();
	}
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
	
	$sql = "DELETE FROM $table WHERE $where";
	if (class_exists('PDO')) {
		$result = db_query($sql);
		return $result;
	}
	else {
		db_query($sql);
		return db_affected_rows ();
	}
}

function db_start_transaction() {
	global $dbh;
	
	if (class_exists('PDO')) {
		$dbh->query("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
		//$dbh->query("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ");
		$dbh->beginTransaction();
		$db_transaction = array();
	}
	else {
		$sql = "SET TRANSACTION ISOLATION LEVEL READ COMMITTED";
		db_query($sql);
		$sql = "START TRANSACTION";
		db_query($sql);
	}
}

function db_commit() {
	global $dbh,$db_transaction;
	
	if (class_exists('PDO')) {
		$dbh->commit();
		$db_transaction = array();
	}
	else {
		$sql = "COMMIT";
		db_query($sql);
	}
}

function db_log_error($e,$query) {
	global $CFG;
	
	if ($CFG->db_debug == 'Y') {
		$output = "Can't execute query";
		$output .= "<pre>".$query."</pre>";
		$output .= "MySQL Error: ".$e->getMessage();
		$output .= "Debug: ";
		$output .= print_r(debug_backtrace (),true);
		trigger_error($output,E_USER_ERROR);
	}
	else {
		$output = "Database error: ";
		$output .= $e->getMessage();
		$output .= ' '.$query;
		trigger_error($output,E_USER_WARNING);
	}
}

function db_is_syntax_error() {
	global $dbh;
	
	$errors = array('42S02','42000','42S22');
	$e = $dbh->errorCode();
	if (!$e)
		return false;
	
	return in_array($e,$errors);
}
?>