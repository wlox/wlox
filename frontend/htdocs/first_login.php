<?php

include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

if (User::$info['no_logins'] != 'Y' && !$_REQUEST['settings']) {
	Link::redirect('account.php');
}

if (!empty($_REQUEST['settings'])) {
	$match = preg_match_all($CFG->pass_regex,$_REQUEST['settings']['pass'],$matches);
	$_REQUEST['settings']['pass'] = preg_replace($CFG->pass_regex, "",$_REQUEST['settings']['pass']);
	$too_few_chars = (mb_strlen($_REQUEST['settings']['pass'],'utf-8') < $CFG->pass_min_chars);
}

API::add('User','getInfo',array($_SESSION['session_id']));
$query = API::send();

$personal = new Form('settings',false,false,'form1','site_users');
$personal->verify();
$personal->get($query['User']['getInfo']['results'][0]);

if (!empty($_REQUEST['settings']) && $_SESSION['firstlogin_uniq'] != $_REQUEST['settings']['uniq'])
		$personal->errors[] = 'Page expired.';

if (!empty($match))
	$personal->errors[] = htmlentities(str_replace('[characters]',implode(',',array_unique($matches[0])),Lang::string('login-pass-chars-error')));
if (!empty($too_few_chars))
	$personal->errors[] = Lang::string('login-password-error');

if (!empty($_REQUEST['settings']) && !empty($personal->errors)) {
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
elseif (!empty($_REQUEST['settings']) && empty($personal->errors)) {
	API::add('User','disableNeverLoggedIn',array($personal->info['pass']));
	API::send();
	
	$_SESSION["firstlogin_uniq"] = md5(uniqid(mt_rand(),true));
	Link::redirect('account.php?message=settings-personal-message');
}
else {
	$personal->info['pass'] = false;
}

$_SESSION["firstlogin_uniq"] = md5(uniqid(mt_rand(),true));
$page_title = Lang::string('first-login');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="first_login.php"><?= $page_title ?></a></div>
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
                $personal->hiddenInput('uniq',1,$_SESSION["firstlogin_uniq"]);
                $personal->display();
                ?>
            	<div class="clear"></div>
            </div>       
            <div class="mar_top8"></div>
        </div>
	</div>
</div>
<? include 'includes/foot.php'; ?>