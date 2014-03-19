<?

include 'dblib.php';
include 'stdlib.php';
//include("fckeditor/fckeditor.php");

function classLoader($class_name) {
	global $CFG;

	if (file_exists($CFG->libdir . '/' . $class_name . '.php')) {
		require_once ($CFG->libdir . '/' . $class_name . '.php');
	}
	elseif (file_exists(((!$CFG->shared_dir) ? '../shared2/' : $CFG->shared_dir). $class_name . '.php')) {
		require_once (((!$CFG->shared_dir) ? '../shared2/' : $CFG->shared_dir). $class_name . '.php');
	}
}

spl_autoload_register('classLoader');

?>