<?php
require_once ("cfg.php");
require_once ("dblib.php");
require_once ("stdlib.php");
require_once ("Google2FA.php");
require_once ("Settings.php");
require_once ('Encryption.php');

/* Connect to the database */
db_connect($CFG->dbhost,$CFG->dbname,$CFG->dbuser,$CFG->dbpass);

/* Load settings and timezone */
Settings::assign();
date_default_timezone_set($CFG->default_timezone);

?>