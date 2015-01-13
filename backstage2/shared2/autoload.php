<?

include 'dblib.php';
include 'stdlib.php';
include('lib/session.php');

function classLoader($class_name) {
	global $CFG;

	if (file_exists('lib/'.$class_name.'.php')) {
		require_once ('lib/'.$class_name.'.php');
	}
	elseif (file_exists('shared2/'.$class_name.'.php')) {
		require_once ('shared2/'.$CFG->shared_dir.$class_name.'.php');
	}
}

spl_autoload_register('classLoader');

?>