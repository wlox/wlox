<?php 
register_shutdown_function('shutdown');
function shutdown() {
	global $lock_file;
	
	unlink($lock_file);
}

?>