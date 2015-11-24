<?php 

$show_form = true;
if ($CFG->action == 'enable') {
	$show_form = false;
	$CFG->form_legend = 'Please enter your token...';
	$enable = new Form('users_form_enable',false,false,false,false,true);
	
	if (empty($_REQUEST['users_form_enable'])) {
		$key = Google2FA::generate_secret_key();
		db_update('admin_users',User::$info['id'],array('authy_id'=>$key));
	}
	else {
		$key = User::$info['authy_id'];
		$response = Google2FA::verify_key($key,$enable->info['token']);
		if (!$response)
			$response->errors[] = 'Invalid token.';
		else {
			db_update('admin_users',User::$info['id'],array('verified_authy'=>'Y'));
			Messages::add('You have succesfully enabled 2FA.');
			$show_form = true;
		}
			
	}
		
	if (!$show_form) {
		$enable->verify();
		$enable->show_errors();
		$enable->HTML('<img class="qrcode" src="includes/qrcode.php?sec=1&code=otpauth://totp/Backstage2?secret='.$key.'" />');
		$enable->textInput('token','Enter token',true);
		$enable->submitButton('submit','Enable 2FA');
		$enable->display();
	}
}
else if ($CFG->action == 'disable') {
	$show_form = false;
	$CFG->form_legend = 'Please enter your token...';
	$disable = new Form('users_form_disable',false,false,false,false,true);
	
	if (!empty($_REQUEST['users_form_disable'])) {
		$key = User::$info['authy_id'];
		$response = Google2FA::verify_key($key,$disable->info['token']);
		if (!$response)
			$response->errors[] = 'Invalid token.';
		else {
			db_update('admin_users',User::$info['id'],array('verified_authy'=>'N'));
			Messages::add('You have succesfully disabled 2FA.');
			$show_form = true;
		}
			
	}
		
	if (!$show_form) {
		$disable->verify();
		$disable->show_errors();
		$disable->HTML('<img class="qrcode" src="includes/qrcode.php?sec=1&code=otpauth://totp/Backstage2?secret='.$key.'" />');
		$disable->textInput('token','Enter token',true);
		$disable->submitButton('submit','Disable 2FA');
		$disable->display();
	}
}

if ($show_form) {
	Messages::display();
	$CFG->form_legend = 'My User Info.';
	$edit = new Form('users_form',false,false,false,'admin_users',true);
	$edit->verify();
	$edit->show_errors();
	$edit->save();
	$edit->get(User::$info['id']);
	$edit->textInput('user',$CFG->user_username,true,false,false,false,false,false,false,false,1,$CFG->user_unique_error);
	$edit->passwordInput('pass',$CFG->user_password,true);
	$edit->passwordInput('pass1',$CFG->user_password,true,false,false,false,false,false,'pass');
	$edit->textInput('first_name',$CFG->user_first_name,true);
	$edit->textInput('last_name',$CFG->user_last_name,true);
	$edit->textInput('phone',$CFG->user_phone);
	$edit->textInput('email',$CFG->user_email);
	$edit->submitButton('submit',$CFG->save_caption);
	$edit->cancelButton($CFG->cancel_button);
	
	if ($edit->info['verified_authy'] == 'Y')
		$edit->button('my-account','Disable Google 2FA',array('action'=>'disable'));
	else
		$edit->button('my-account','Enable Google 2FA',array('action'=>'enable'));
	
	$edit->display();
}

?>