<?php

include '../cfg/cfg.php';

$page_title = Lang::string('login-forgot');
$email1 = ereg_replace("[^0-9a-zA-Z@.!#$%&'*+-/=?^_`{|}~]", "",$_REQUEST['forgot']['email']);

if ($_REQUEST['forgot']) {
	$sql = "SELECT * FROM site_users WHERE email = '$email1'";
	$result = db_query_array($sql);
	
	if ($result) {
		$new_id = SiteUser::getNewId();
		$result[0]['new_user'] = $new_id;
		$result[0]['new_password'] = SiteUser::randomPassword(12);
		db_update('site_users',$result[0]['id'],array('user'=>$result[0]['new_user'],'pass'=>$result[0]['new_password'],'no_logins'=>'Y'));
		
		$email = SiteEmail::getRecord('forgot');
		Email::send($CFG->form_email,$email1,$email['title'],$CFG->form_email_from,false,$email['content'],$result[0]);
		Messages::$messages = array();
		Messages::add(Lang::string('login-password-sent-message'));
	}
	else {
		Errors::add(Lang::string('login-account-not-found'));
	}
}

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= Lang::string('login-forgot') ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="forgot.php"><?= Lang::string('login-forgot') ?></a></div>
	</div>
</div>
<div class="fresh_projects login_bg">
	<div class="clearfix mar_top8"></div>
	<div class="container">
    	<h2><?= Lang::string('login-forgot') ?></h2>
    	<? 
    	if (count(Errors::$errors) > 0) {
			echo '
		<div class="error" id="div4">
			<div class="message-box-wrap">
				<button id="colosebut4" class="close-but">close</button>
				'.Errors::$errors[0].'
			</div>
		</div>';
		}
		
		if (count(Messages::$messages) > 0) {
			Lang::string(Messages::$messages[0]);
			echo '
		<div class="messages" id="div4">
			<div class="message-box-wrap">
				'.Messages::$messages[0].'
			</div>
		</div>';
		}
    	?>
    	<form method="POST" action="forgot.php" name="forgot">
	    	<div class="loginform">
	    		<span><?= Lang::string('forgot-explain') ?></span>
	    		<div class="loginform_inputs">
		    		<div class="input_contain">
		    			<i class="fa fa-user"></i>
		    			<input type="text" class="login" name="forgot[email]" value="<?= $email1 ?>">
		    		</div>
	    		</div>
	    		<input type="submit" name="submit" value="<?= Lang::string('login-forgot-send-new') ?>" class="but_user" />
	    	</div>
    	</form>
    	<a class="forgot" href="login.php"><?= Lang::string('login-remembered') ?></a>
    </div>
    <div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>