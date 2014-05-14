<?php

include '../cfg/cfg.php';

$page_title = Lang::string('home-login');
$user1 = ereg_replace("[^0-9]", "",$_REQUEST['login']['user']);
$pass1 = ereg_replace("[^0-9a-zA-Z!@#$%&*?\.\-\_]", "",$_REQUEST['login']['pass']);

if ($_REQUEST['submitted']) {
	if (empty($user1)) {
		Errors::add($CFG->login_empty_user);
	}

	if (empty($pass1)) {
		Errors::add($CFG->login_empty_pass);
	}
	
	if (!is_array(Errors::$errors)) {
		$login = User::logIn($user1,$pass1);
		if ($login && !$login['error']) {
			if ($login['message'] == 'awaiting-token') {
				Link::redirect('verify-token.php');
			}
			elseif ($login['message'] == 'logged-in' && $login['no_logins'] == 'Y') {
				Link::redirect('first_login.php');
			}
			elseif ($login['message'] == 'logged-in') {
				Link::redirect('account.php');
			}
		}
		elseif (!$login || $login['error']) {
			Errors::add($CFG->login_invalid);
		}
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
	    		<input type="hidden" name="submitted" value="1" />
	    		<input type="submit" name="submit" value="<?= Lang::string('home-login') ?>" class="but_user" />
	    	</div>
    	</form>
    	<a class="forgot" href="how-to-register.php"><?= Lang::string('login-dont-have') ?></a>
    </div>
    <div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>