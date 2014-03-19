<?php

include '../cfg/cfg.php';

if (User::isLoggedIn()) {
	if (User::$info['verified_authy'] == 'Y' && !($_SESSION['token_verified'] > 0))
		Link::redirect('verify-token.php');
}
else {
	Link::redirect('login.php');
	exit;
}

if (User::$info['no_logins'] != 'Y' && !$_REQUEST['settings']) {
	Link::redirect('account.php');
}

$personal = new Form('settings',false,false,'form1','site_users');
$personal->verify();
$personal->get(User::$info['id']);
$personal->info['pass'] = ereg_replace("[^0-9a-zA-Z!@#$%&*?\.\-\_]", "",$personal->info['pass']);

if ($_REQUEST['settings'] && is_array($personal->errors)) {
	$errors = array();
	foreach ($personal->errors as $key => $error) {
		if (stristr($error,'login-required-error')) {
			$errors[] = Lang::string('settings-'.str_replace('_','-',$key)).' '.Lang::string('login-required-error');
		}
		elseif (strstr($error,'-')) {
			$errors[] = Lang::string($error);
		}
		else {
			$errors[] = $error;
		}
	}
	Errors::$errors = $errors;
}
elseif (($_REQUEST['settings']) && !is_array($personal->errors)) {
	$personal->save();
	$personal->info['user'] = User::$info['user'];
	
	User::logOut(1);
	User::logIn($personal->info['user'],$personal->info['pass'],'site','openbtcexchange_user');

	$_SESSION['token_verified'] = 1;
	Link::redirect('account.php?message=settings-personal-message');
}
else {
	db_update('site_users',User::$info['id'],array('no_logins'=>'N'));
	$personal->info['pass'] = false;
}

$page_title = Lang::string('first-login');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="first_login.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<div class="testimonials-4">
			<? 
            Errors::display(); 
            Messages::display();
            ?>
            <div class="text"><p><?= Lang::string('settings-pass-explain') ?></p></div>
            <div class="content">
            	<h3 class="section_label">
                    <span class="left"><i class="fa fa-user fa-2x"></i></span>
                    <span class="right"><?= Lang::string('settings-personal-password') ?></span>
                </h3>
                <div class="clear"></div>
                <?
                $personal->passwordInput('pass',Lang::string('settings-pass'),true);
                $personal->passwordInput('pass2',Lang::string('settings-pass-confirm'),true,false,false,false,false,false,'pass');
                $personal->HTML('<div class="form_button"><input type="submit" name="submit" value="'.Lang::string('settings-save-password').'" class="but_user" /></div>');
                $personal->display();
                ?>
            	<div class="clear"></div>
            </div>       
            <div class="mar_top8"></div>
        </div>
	</div>
</div>
<? include 'includes/foot.php'; ?>