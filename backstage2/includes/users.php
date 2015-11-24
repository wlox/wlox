<?php
date_default_timezone_set($CFG->default_timezone);
String::magicQuotesOff();

if ($_REQUEST['users_form']) {
	$form = new Form('users_form',false,false,false,$_REQUEST['table']);
	$form->verify();
	$form->save();
	$form->get($_REQUEST['id']);
	$form->show_errors();
	$form->show_messages();

	PermissionEditor::save();
}

if ($CFG->action == 'record') {
	$view = new Record($_REQUEST['table'],$_REQUEST['id']);
	
	if ($_REQUEST['table'] == 'admin_groups') {
		$view->field('name',$CFG->user_group_name);
	}
	else {
		$view->field('id','ID');
		$view->field('first_name',$CFG->user_first_name);
		$view->field('last_name',$CFG->user_last_name);
		$view->field('phone',$CFG->user_phone);
		$view->field('email',$CFG->user_email);
		$view->field('f_id',$CFG->user_group,'admin_groups',array('name'));
		$view->field('is_admin',$CFG->user_is_admin);
	}
	
	$view->display();
	
	if ($_REQUEST['table'] == 'admin_groups')
		$pe = new PermissionEditor('admin',$_REQUEST['id']);
	
	$form = new Form('dummy');
	//$form->button(false,$CFG->ok_button,false,false,false,false,'onclick="closePopup(this);"');
	$form->cancelButton($CFG->ok_button);
	$form->display();
}
elseif ($CFG->action == 'form') {
	
	$edit = new Form('users_form',false,false,false,$_REQUEST['table']);
	$edit->get($_REQUEST['id']);
	
	if ($_REQUEST['table'] == 'admin_groups') {
		$edit->textInput('name','Name');
		if ($_REQUEST['id']) $edit->permissionEditor('admin',$_REQUEST['id']);
	}
	else {
		$edit->passiveField('id','ID');
		$edit->textInput('user',$CFG->user_username,true,false,false,false,false,false,false,false,1,$CFG->user_unique_error);
		$edit->passwordInput('pass',$CFG->user_password,true);
		$edit->passwordInput('pass1',$CFG->user_password,true,false,false,false,false,false,'pass');
		$edit->textInput('first_name',$CFG->user_first_name,true);
		$edit->textInput('last_name',$CFG->user_last_name,true);
		$edit->textInput('phone',$CFG->user_phone);
		$edit->textInput('country_code','Country Code');
		$edit->textInput('email',$CFG->user_email);
		$edit->selectInput('f_id',$CFG->user_group,false,$_REQUEST['f_id'],false,'admin_groups',array('name'));
		$edit->checkBox('is_admin',$CFG->user_is_admin);
		$edit->checkBox('verified_authy','Use Authy?');
	}
	$edit->submitButton('submit',$CFG->save_caption);
	//$edit->button(false,$CFG->cancel_button,false,false,false,false,'onclick="$(\'#edit_box\').fadeOut(\'slow\');"');
	$edit->cancelButton($CFG->cancel_button);
	$edit->display();
	
}
else {
	$users = new MultiList(false,true,$CFG->path_users);
	$users->addTable('admin_groups',array('name'),$CFG->url,false,false,'edit_box');
	$users->addTable('admin_users',array('id','first_name','last_name','company'),$CFG->url,'admin_groups',false,'edit_box');
	$users->display();
}
?>