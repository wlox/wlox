<?php
include'lib/common.php';

$term = (is_array($_REQUEST['term'])) ? $_REQUEST['term']['term'] : $_REQUEST['term'];
if (strstr($term,',')) {
	$term_parts = explode(',',$term);
	$c = count($term_parts) - 1;
	$term = $term_parts[$c];	
}

if ($_REQUEST['get_table_fields']) {
	$table_fields = DB::getTableFields($_REQUEST['f_value'],true,false,$term);
	
	if ($table_fields) {
		foreach ($table_fields as $field) {
			echo "{$field}|{$field}||";
		}
	}
}
else {
	$db_output = DB::getSubTable($_REQUEST['subtable'],unserialize(urldecode($_REQUEST['subtable_fields'])),$_REQUEST['f_value'],false,$_REQUEST['f_id_field'],$term);
	if ($_REQUEST['options_array_is_subtable']) 
		$db_output = array_combine($db_output,$db_output);

	if (is_array($db_output)) {
		foreach ($db_output as $option => $option_name) {
			echo $option.'|'.$option_name."||";
		}
	}
}

?>