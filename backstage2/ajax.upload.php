<?php
include'lib/common.php';


$CFG->temp_file_location = 'tempfiles/';


$temp_files[] = Upload::saveTemp('Filedata');
if ($temp_files[0]['filename']) {
	echo 'file|'.$temp_files[0]['filename'];
}
else {
	echo 'error|'.$temp_files[0]['error'];
}
?>