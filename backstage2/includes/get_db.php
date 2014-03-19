<?php
	if (!empty($_REQUEST['bs_db_name'])) {
		$CFG->dbhost = "localhost";
		$CFG->dbname = $_SESSION['bs_db_name'];
		$CFG->dbuser = "root";
		$CFG->dbpass = "";
		
		db_connect ( $CFG->dbhost, $CFG->dbname, $CFG->dbuser, $CFG->dbpass );
	}
?>