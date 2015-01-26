<?php
include "../cfg.php";
include "dblib.php";
include "stdlib.php";
include "Google2FA.php";
include "Settings.php";
include 'Encryption.php';
include 'SiteEmail.php';
include 'Email.php';

/* Connect to the database */
db_connect($CFG->dbhost,$CFG->dbname,$CFG->dbuser,$CFG->dbpass);

/* Load settings and timezone */
Settings::assign();
date_default_timezone_set($CFG->default_timezone);

?>