<?php
include '../cfg/cfg.php';

if ($_REQUEST['register']) {
	$_REQUEST['register']['first_name'] = preg_replace("/[^\p{Hebrew} \p{Cyrillic} a-zA-Z0-9@\._-\s]/u", "",$_REQUEST['register']['first_name']);
	$_REQUEST['register']['last_name'] = preg_replace("/[^\p{Hebrew} \p{Cyrillic} a-zA-Z0-9@\._-\s]/u", "",$_REQUEST['register']['last_name']);
	$_REQUEST['register']['country'] = preg_replace("/[^0-9]/", "",$_REQUEST['register']['country']);
	$_REQUEST['register']['email'] = preg_replace("/[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/", "",$_REQUEST['register']['email']);
	$_REQUEST['register']['default_currency'] = preg_replace("/[^0-9]/", "",$_REQUEST['register']['default_currency']);
	$_REQUEST['is_caco'] = (!$_REQUEST['is_caco']) ? array('register'=>1) : $_REQUEST['is_caco'];
}

$register = new Form('register',false,false,'form3');
unset($register->info['uniq']);
$register->verify();

if ($_REQUEST['register'] && $_SESSION["register_uniq"] != $_REQUEST['register']['uniq'])
	$register->errors[] = 'Page expired.';

if ($_REQUEST['register'] && !$register->info['terms'])
	$register->errors[] = Lang::string('settings-terms-error');

if ($_REQUEST['register'] && (is_array($register->errors))) {
	$errors = array();
	
	if ($register->errors) {
		foreach ($register->errors as $key => $error) {
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
	}
		
	Errors::$errors = $errors;
}
elseif ($_REQUEST['register'] && !is_array($register->errors)) {
	API::add('User','registerNew',array($register->info));
	$query = API::send();
	
	$_SESSION["register_uniq"] = md5(uniqid(mt_rand(),true));
	Link::redirect('login.php?message=registered');
}

API::add('User','getCountries');
$query = API::send();
$countries = $query['User']['getCountries']['results'][0];

$page_title = Lang::string('home-register');

$_SESSION["register_uniq"] = md5(uniqid(mt_rand(),true));
include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="register.php"><?= Lang::string('register') ?></a></div>
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
            <div class="content">
            	<h3 class="section_label">
                    <span class="left"><i class="fa fa-user fa-2x"></i></span>
                    <span class="right"><?= Lang::string('settings-registration-info') ?></span>
                </h3>
                <div class="clear"></div>
                <?
                $register->textInput('first_name',Lang::string('settings-first-name'),1);
                $register->textInput('last_name',Lang::string('settings-last-name'),1);
                $register->selectInput('country',Lang::string('settings-country'),1,false,$countries,false,array('name'));
                $register->textInput('email',Lang::string('settings-email'),'email');
                $register->selectInput('default_currency',Lang::string('default-currency'),1,false,$CFG->currencies,false,array('currency'));
                $register->checkBox('terms',Lang::string('settings-terms-accept'),false,false,false,false,false,false,'checkbox_label');
                $register->captcha(Lang::string('settings-capcha'));
                $register->HTML('<div class="form_button"><input type="submit" name="submit" value="'.Lang::string('home-register').'" class="but_user" /></div>');
                $register->hiddenInput('uniq',1,$_SESSION["register_uniq"]);
                $register->display();
                ?>
            	<div class="clear"></div>
            </div>
            <div class="mar_top8"></div>
        </div>
	</div>
</div>
<? include 'includes/foot.php'; ?>