<?php
include '../cfg/cfg.php';

$register = new Form('register',false,false,'form3','site_users');
$register->verify();
$register->get(User::$info['id']);
$register->info['first_name'] = ereg_replace("/[^\da-z]/i", "",$register->info['first_name']);
$register->info['last_name'] = ereg_replace("/[^\da-z]/i", "",$register->info['last_name']);
$register->info['country'] = ereg_replace("[^0-9]", "",$register->info['country']);
$register->info['email'] = ereg_replace("[^0-9a-zA-Z!@#$%&*?]", "",$register->info['email']);

if ($_REQUEST['register'] && !$register->info['terms'])
	$register->errors[] = Lang::string('settings-terms-error');

if (SiteUser::emailRegistered($register->info['email']))
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
	$new_id = SiteUser::getNewId();
	if ($new_id > 0) {
		$register->info['user'] = $new_id;
		$register->info['pass'] = SiteUser::randomPassword(12);
		$register->info['date'] = date('Y-m-d H:i:s');
		$register->info['confirm_withdrawal_email_btc'] = 'Y';
		$register->info['confirm_withdrawal_email_bank'] = 'Y';
		$register->info['notify_deposit_btc'] = 'Y';
		$register->info['notify_deposit_bank'] = 'Y';
		$register->info['notify_login'] = 'Y';
		$register->info['no_logins'] = 'Y';
		$register->info['fee_schedule'] = $CFG->default_fee_schedule_id;
		unset($register->info['terms']);
		$register->save();
		
		require_once('../lib/easybitcoin.php');
		$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
		$new_address = $bitcoin->getnewaddress($CFG->bitcoin_accountname);
		db_insert('bitcoin_addresses',array('address'=>$new_address,'site_user'=>$register->record_id,'date'=>date('Y-m-d H:i:s')));
		
		$email = SiteEmail::getRecord('register');
		Email::send($CFG->form_email,$register->info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$register->info);
		
		$email = SiteEmail::getRecord('register-notify');
		$register->info['pass'] = false;
		Email::send($CFG->form_email,$CFG->accounts_email,$email['title'],$CFG->form_email_from,false,$email['content'],$register->info);
		
		Link::redirect('login.php?message=registered');
		exit;
	}
}

$page_title = Lang::string('home-register');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="register.php"><?= Lang::string('register') ?></a></div>
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
                $register->selectInput('country',Lang::string('settings-country'),1,false,false,'iso_countries',array('name'));
                $register->textInput('email',Lang::string('settings-email'),'email');
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