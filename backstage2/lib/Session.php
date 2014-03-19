<?php

class Session {
	/* Open session */
	function _open($path, $name) {
		return TRUE;
	}
	
	/* Close session */
	function _close() {
		/* This is used for a manual call of the
           session gc function */
		$this->_gc ( 0 );
		return TRUE;
	}
	
	/* Read session data from database */
	function _read($ses_id) {
		$result = db_query_array("SELECT session_value FROM sessions WHERE session_id = '$ses_id'",'',true);
		if (!$result) {
			return '';
		} else {
			return $result['session_value'];
		}
	}
	
	/* Write new data to database */
	function _write($ses_id, $data) {
		$session_res = db_query ("UPDATE sessions SET session_time=NOW(), session_value='".addslashes($data)."' WHERE session_id='$ses_id'");
		
		if (!$session_res) {
			return FALSE;
		} 
		else if (db_affected_rows ()) {
			return TRUE;
		} 
		else {
			$session_sql = "INSERT INTO sessions (session_id, session_time, session_start, session_value, ip_address, user_agent) 
	        				VALUES ('$ses_id', NOW(), NOW(), '".addslashes($data)."',
	        				'".$_SERVER['REMOTE_ADDR']."','".addslashes($_SERVER['HTTP_USER_AGENT'])."')";
			$session_res = db_query ( $session_sql, false, false, true );
			
			if (!$session_res) {
				return FALSE;
			} 
			else {
				return TRUE;
			}
		}
	}
	
	/* Destroy session record in database */
	function _destroy($ses_id) {
		$session_res = db_query ( "DELETE FROM sessions WHERE session_id = '$ses_id'" );
		
		if (! $session_res) {
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	/* Garbage collection, deletes old sessions */
	function _gc($life) {
		$ses_life = strtotime ( "-5 minutes" ); // override life and delete things after x
		$session_res = db_query ( "DELETE FROM sessions WHERE session_time < $ses_life" );
		
		if (! $session_res) {
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	function deleteExpired() {
		$sql = "DELETE FROM sessions WHERE session_time < (NOW() - INTERVAL 15 MINUTE) ";
		db_query($sql);
	}
}

?>