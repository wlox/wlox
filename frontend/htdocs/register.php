<?php
include '../cfg/cfg.php';

$register = new Form('register',false,false,'form3');
//$register->get(User::$info['id']);
$register->info['first_name'] = preg_replace("/[^\p{Hebrew} \p{Cyrillic} a-zA-Z0-9@\._-\s]/u", "",$register->info['first_name']);
$register->info['last_name'] = preg_replace("/[^\p{Hebrew} \p{Cyrillic} a-zA-Z0-9@\._-\s]/u", "",$register->info['last_name']);
$register->info['country'] = ereg_replace("[^0-9]", "",$register->info['country']);
$register->info['email'] = ereg_replace("[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]", "",$register->info['email']);
$register->verify();


if ($_REQUEST['register'] && !$register->info['terms'])
	$register->errors[] = Lang::string('settings-terms-error');

if ($_REQUEST['register']) {
	API::add('User','userExists',array($register->info['email']));
	$query = API::send();
}
	
if ($query['User']['userExists']['results'][0])
	$email_exists = Lang::string('settings-unique-error');

if ($_REQUEST['register'] && (is_array($register->errors) || $email_exists)) {
	$errors = array();
	
	if ($email_exists)
		$errors[] = $email_exists;
	
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
	
	Link::redirect('login.php?message=registered');
}

API::add('User','getCountries');
$query = API::send();
$countries = $query['User']['getCountries']['results'][0];

$page_title = Lang::string('home-register');

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
                $register->display();
                ?>
            	<div class="clear"></div>
            </div>
            <div class="mar_top8"></div>
        </div>
	</div>
</div>
<? include 'includes/foot.php'; ?>