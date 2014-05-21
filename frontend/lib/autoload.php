<?php 
include 'dblib.php';
include 'stdlib.php';
include 'session.php';

function classLoader($class_name) {
	global $CFG;

	require_once ($CFG->libdir.$class_name.'.php');
}

spl_autoload_register('classLoader');
