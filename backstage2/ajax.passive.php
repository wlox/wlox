<?php
include'lib/common.php';

$CFG->passive_override_id = $_REQUEST['get_id'];
$record = new Record($_REQUEST['table'],$_REQUEST['record_id']);

if ($_REQUEST['action'] == 'indicator') {
	echo $record->indicator($_REQUEST['name'],$_REQUEST['formula'],$_REQUEST['caption'],$_REQUEST['subtable'],@unserialize($_REQUEST['subtable_fields']),$_REQUEST['concat_char'],$_REQUEST['formula_id_field'],$_REQUEST['f_id_field'],$_REQUEST['link_url'],$_REQUEST['link_is_tab'],$_REQUEST['run_in_sql'],1);
}
else {
	echo $record->field($_REQUEST['name'],$_REQUEST['caption'],$_REQUEST['subtable'],@unserialize($_REQUEST['subtable_fields']),$_REQUEST['link_url'],$_REQUEST['concat_char'],true,$_REQUEST['f_id_field'],$_REQUEST['order_by'],$_REQUEST['order_asc'],1,$_REQUEST['link_is_tab'],$_REQUEST['limit_is_curdate'],$_REQUEST['get_id']);
}

unset($record);
?>