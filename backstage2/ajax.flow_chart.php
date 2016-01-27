<?php
include'lib/common.php';
$action = $_REQUEST['action'];

if ($action == 'save_order') {
	if ($_REQUEST['rows']) {
		foreach ($_REQUEST['rows'] as $i => $id) {
			DB::update($_REQUEST['table'],array('step_order'=>$i),$id);
		}
		Messages::add($CFG->ajax_save_message);
		Messages::display();
	}
}
?>