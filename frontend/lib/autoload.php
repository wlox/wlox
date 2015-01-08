<?php 
function classLoader($class_name) {
	require_once ('../lib/'.$class_name.'.php');
}
spl_autoload_register('classLoader');
