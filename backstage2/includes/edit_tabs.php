<?php

date_default_timezone_set($CFG->default_timezone);
String::magicQuotesOff();

if ($_REQUEST['tabs_form']) {
	$form = new Form('tabs_form',false,false,false,$_REQUEST['table']);
	$form->verify();
	$form->save();
	$form->get($_REQUEST['id']);
	$form->show_errors();
	$form->show_messages();
}

if ($CFG->action == 'form') {
	$table_fields = DB::getTableFields($_REQUEST['table'],1);
	if ($_REQUEST['table'] == 'admin_tabs') {
		if (!in_array('hidden',$table_fields)) {
			$sql = "ALTER TABLE admin_tabs ADD admin_tabs.hidden ENUM( 'Y', 'N' ) NOT NULL";
			db_query($sql);
		}
		
		if (!in_array('is_ctrl_panel',$table_fields)){
			$sql = "ALTER TABLE admin_tabs ADD admin_tabs.is_ctrl_panel ENUM( 'Y', 'N' ) NOT NULL";
			db_query($sql);
		}
		
		if (!in_array('for_group',$table_fields)){
			$sql = "ALTER TABLE admin_tabs ADD admin_tabs.for_group INT( 10 ) UNSIGNED NOT NULL";
			db_query($sql);
		}
	}
	
	if (!in_array('one_record',$table_fields)){
		$sql = "ALTER TABLE admin_tabs ADD {$_REQUEST['table']}.one_record ENUM( 'Y', 'N' ) NOT NULL";
		db_query($sql);
	}

	$form = new Form('tabs_form',false,false,false,$_REQUEST['table']);
	$form->verify();
	$form->save();
	$form->get($_REQUEST['id']);
	$form->show_errors();
	$form->show_messages();
	$form->textInput('name','Name',true);
	$form->textInput('url','Url');
	$form->textInput('order','Order',false,'0');
	$form->checkBox('one_record','Auto First Record?');
	if ($_REQUEST['table'] == 'admin_tabs') {
		$form->checkBox('hidden','Hidden?');
		$form->checkBox('is_ctrl_panel','Is Control Panel?');
		$form->selectInput('for_group','For Group'.false,false,false,false,'admin_groups',array('name'));
	}
	elseif ($_REQUEST['table'] == 'admin_pages') {
		if (in_array('icon',$table_fields))
			$form->textInput('icon','Icon');
	}
	if ($_REQUEST['from_editor']) {
		$next_page_id = $_REQUEST['pm_page_id'];
		$next_page_action = $_REQUEST['pm_action'];
		$form->button(false,$CFG->ok_button,false,false,false,false,'onclick="saveForm(this,\'pm_editor\',\'index.php?tab_bypass=1&current_url=edit_page&id='.$next_page_id.'&action='.$next_page_action.'&is_tab=1\')"');
	}
	else {
		$form->submitButton('save',$CFG->save_caption);
	}
	$form->button(false,$CFG->cancel_button,false,false,false,false,'onclick="closePopup(this);"');
	if ($CFG->action == 'form' && $_REQUEST['f_id'] > 0) {
		$form->hiddenInput('f_id',false,$_REQUEST['f_id']);
	}
	if ($CFG->action == 'form' && $_REQUEST['p_id'] > 0) {
		$form->hiddenInput('p_id',false,$_REQUEST['p_id']);
	}
	$form->display();

}
elseif ($CFG->action == 'record') {
	$table_fields = DB::getTableFields($_REQUEST['table'],1);
	if ($_REQUEST['table'] == 'admin_tabs') {
		if (!in_array('hidden',$table_fields)) {
			$sql = "ALTER TABLE admin_tabs ADD admin_tabs.hidden ENUM( 'Y', 'N' ) NOT NULL";
			db_query($sql);
		}
		
		if (!in_array('is_ctrl_panel',$table_fields)){
			$sql = "ALTER TABLE admin_tabs ADD admin_tabs.is_ctrl_panel ENUM( 'Y', 'N' ) NOT NULL";
			db_query($sql);
		}
		
		if (!in_array('for_group',$table_fields)){
			$sql = "ALTER TABLE admin_tabs ADD admin_tabs.for_group INT( 10 ) UNSIGNED NOT NULL";
			db_query($sql);
		}
	}
	
	$record = new Record($_REQUEST['table'],$_REQUEST['id']);
	$record->field('name','Name:');
	$record->field('url','Url');
	$record->field('order','Order');
	if ($_REQUEST['table'] == 'admin_tabs') {
		$record->field('hidden','Hidden?');
		$record->field('is_ctrl_panel','Is Control Panel?');
		$record->field('for_group','For Group','admin_groups',array('name'));
	}
	elseif ($_REQUEST['table'] == 'admin_pages') {
		if (in_array('icon',$table_fields))
			$record->field('icon','Icon');
	}
	$record->display();
	
	$form = new Form('dummy');
	$form->button(false,$CFG->ok_button,false,false,false,false,'onclick="closePopup(this);"');
	$form->display();
}
else {
	$list = new MultiList(false,true,'Backstage');
	$list->addTable('admin_tabs',array('name'),'edit_tabs',false,false,'edit_box');
	$list->addTable('admin_pages',array('name'),'edit_tabs','admin_tabs',false,'edit_box',true);
	$list->display();
}

?>