<?php

include '../cfg/cfg.php';

if (!stristr($_SERVER["SERVER_NAME"],'www.') && strlen($CFG->baseurl) > 0)
	Link::redirect($CFG->baseurl.'login.php',$_REQUEST);

$page_title = Lang::string('home-login');
$user1 = ereg_replace("[^0-9]", "",$_REQUEST['login']['user']);
$pass1 = ereg_replace("[^0-9a-zA-Z!@#$%&*?\.\-\_]", "",$_REQUEST['login']['pass']);

if ($_REQUEST['login'] && !is_array(Errors::$errors)) {
	if (User::$info['verified_authy'] == 'Y' && User::$info['dont_ask_30_days'] != 'Y') {
		if (User::$info['using_sms'] == 'Y')
			$response = shell_exec('curl https://api.authy.com/protected/json/sms/'.User::$info['authy_id'].'?api_key='.$CFG->authy_api_key);
		
		Link::redirect('verify-token.php');
	}
	elseif (User::$info['no_logins'] == 'Y') {
		Link::redirect('first_login.php');
	}
	else {
		$_SESSION['token_verified'] = 1;
		
		if (User::$info['notify_login'] == 'Y') {
			$info = User::$info;
			$info['ipaddress'] = $_SERVER['REMOTE_ADDR'];
			$email = SiteEmail::getRecord('login-notify');
			Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$info);
		}
		
		Link::redirect('account.php');
	}
}

if ($_REQUEST['message'] == 'registered')
	Messages::add(Lang::string('register-success'));

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= Lang::string('home-login') ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="login.php"><?= Lang::string('home-login') ?></a></div>
	</div>
</div>
<div class="fresh_projects login_bg">
	<div class="clearfix mar_top8"></div>
	<div class="container">
    	<h2><?= Lang::string('home-login') ?></h2>
    	<? 
    	if (count(Errors::$errors) > 0) {
			Lang::string(Errors::$errors[0]);
			echo '
		<div class="error" id="div4">
			<div class="message-box-wrap">
				'.Lang::string(Errors::$errors[0]).'
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
    	<form method="POST" action="login.php" name="login">
	    	<div class="loginform">
	    		<a href="forgot.php"><?= Lang::string('forgot-ask') ?></a>
	    		<div class="loginform_inputs">
		    		<div class="input_contain">
		    			<i class="fa fa-user"></i>
		    			<input type="text" class="login" name="login[user]" value="<?= $user1 ?>">
		    		</div>
		    		<div class="separate"></div>
		    		<div class="input_contain last">
		    			<i class="fa fa-lock"></i>
		    			<input type="password" class="login" name="login[pass]" value="<?= $pass1 ?>">
		    		</div>
	    		</div>
	    		<input type="submit" name="submit" value="<?= Lang::string('home-login') ?>" class="but_user" />
	    	</div>
    	</form>
    	<a class="forgot" href="how-to-register.php"><?= Lang::string('login-dont-have') ?></a>
    </div>
    <div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>