<?php
include'lib/common.php';
$input_type = ($_REQUEST['input_type']) ? $_REQUEST['input_type'] : 'textarea';
$elem_id = $_REQUEST['elem_id'];
$caption = urldecode($_REQUEST['label_caption']);
$current_val = urldecode($_REQUEST['current_val']);

if (stristr($elem_id,'file_desc')) {
	$elem_parts = explode('__',$elem_id);
	$elem_parts1 = explode('_',$elem_parts[1]);
	$table = $elem_parts1[0];
	$db_fields = DB::getTableFields($table.'_files',1);
	if (!in_array('file_desc',$db_fields)) {
		$sql = "ALTER TABLE {$table}_files ADD file_desc TEXT NOT NULL ";
		db_query($sql);
	}
}

$form = new Form('zoom');
switch ($input_type) {
	case 'textarea':
		$form->textArea($_REQUEST['elem_id'],$caption,false,$current_val);
		break;
}
$form->button(false,$CFG->ok_button,false,false,false,false,'onclick="fieldZoomOut(\''.$_REQUEST['elem_id'].'\')"');
$form->display();

?>