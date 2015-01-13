<?php
include'lib/common.php';
String::magicQuotesOff();

if (!empty($_REQUEST['form_name'])) {
	$f_name = $_REQUEST['form_name'];
	$f_table = $_REQUEST['form_table'];
	
	$form = new Form($f_name,false,false,false,$f_table);

	if (($f_table == 'admin_controls_methods' || $f_table == 'admin_controls') && (is_numeric($form->info['order']) || $form->info['order'] === 0)) {
		if ($form->info['control_id'] > 0) {
			$l_field = 'control_id';
			$l_id = $form->info['control_id'];
		}
		else {
			if ($form->info['tab_id'] > 0) {
				$l_field = 'tab_id';
				$l_id = $form->info['tab_id'];	
			}
			else {
				$l_field = 'page_id';
				$l_id = $form->info['page_id'];	
			}
		}
		$sql = "SELECT $f_table.order FROM $f_table WHERE $l_field = $l_id ORDER BY $f_table.order ASC";
		$result = db_query_array($sql);
		if ($result) {
			$i = 0;
			foreach ($result as $row) {
				DB::update($f_table,array('order'=>$i),$row['id']);
				$i++;
			}
		}
		
		$form->info['order'] = ($form->info['order'] > 0) ? $form->info['order'] : '0';
		$sql = "UPDATE $f_table SET {$f_table}.order = ({$f_table}.order + 1) WHERE {$f_table}.order >= {$form->info['order']} AND $l_field = $l_id";
		db_query($sql);
	}
	else {
		unset($form->info['order']);
	}
	
	$form->verify();
	$form->save();
	$form->show_errors();
	$form->show_messages();
	
	if ($f_table == 'admin_controls' && !$form->errors) {
		$CFG->save_called = false;
		if ($form->info['class'] == 'Excel') {
			$form1 = new Form($f_name,false,false,false,$f_table);
			$form1->info['action'] = 'form';
			$form1->save();
			$form1->show_errors();
			$form1->show_messages();
		}
		elseif ($form->info['class'] == 'Form') {
			
		}
	}

	if ($f_table = 'admin_controls_methods' && ($form->info['method'] == 'emailNotify' || $form->info['method'] == 'createRecord' || $form->info['method'] == 'editRecord')) {
		if ($form->info['argument_day'] || $form->info['argument_month'] || $form->info['argument_year'] || $form->info['argument_run_in_cron']) {
			$sql = "SELECT id FROM admin_cron WHERE control_id = ".$form->info['control_id']." AND method_id = ".$form->record_id;
			$result = db_query_array($sql);
			
			if (!$result)
				DB::insert('admin_cron',array('control_id'=>$form->info['control_id'],'method_id'=>$form->record_id,'day'=>$form->info['argument_day'],'month'=>$form->info['argument_month'],'year'=>$form->info['argument_year'],'send_condition'=>$form->info['argument_send_condition']));
			else {
				DB::update('admin_cron',array('day'=>$form->info['argument_day'],'month'=>$form->info['argument_month'],'year'=>$form->info['argument_year'],'send_condition'=>$form->info['argument_send_condition']),$result[0]['id']);
			}
		}
	}

}

?>