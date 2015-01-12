<?
function classLoader($class_name) {
	if (file_exists('../lib/'.$class_name.'.php')) {
		require_once ('../lib/'.$class_name.'.php');
	}
}
spl_autoload_register('classLoader');

?>