<?php
include'lib/common.php';

if (!empty($_REQUEST['table']) && !empty($_REQUEST['field']) && !empty($_REQUEST['value'])) {
	$sql = "SELECT * FROM {$_REQUEST['table']} WHERE {$_REQUEST['field']} = {$_REQUEST['value']}";
	$result = db_query_array($sql);
}


if ($result) {
	if ($_REQUEST['return_type'] == 'select') {
		if ($_REQUEST['mode'] == 'faux') {
			echo '<a class="f_elem" onclick="fauxSelect(this,event,\'\')"> '.$default_text.'</a>';
			foreach ($result as $row) {
				echo '<a class="f_elem" onclick="fauxSelect(this,event,\''.$row['id'].'\')">'.$row['name'].'</a>';
			}
		}
		else {
			echo '<option value="" selected="selected""></option>';
			foreach ($result as $row) {
				echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
		}
	}
}
?>