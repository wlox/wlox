<?php

include '../lib/common.php';

if (User::$awaiting_token)
	Link::redirect('verify-token.php?email_auth=1&authcode='.urlencode($_REQUEST['authcode']));
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$authcode1 = (!empty($_REQUEST['authcode'])) ? urldecode($_REQUEST['authcode']) : false;
$authcode_valid = false;
$uniq1 = (!empty($_REQUEST['settings'])) ? $_REQUEST['settings']['uniq'] : $_REQUEST['uniq'];
$token1 = (!empty($_REQUEST['token'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['token']) : false;
$request_2fa = false;

/*
if (!empty($_REQUEST['ex_request'])) {
	$_REQUEST = unserialize(urldecode($_REQUEST['ex_request']));
}
*/

// check for authcode or redirect if invalid
if ($authcode1) {
	API::add('User','getSettingsChangeRequest',array(urlencode($authcode1)));
	$query = API::send();
	$authcode_valid = $query['User']['getSettingsChangeRequest']['results'][0];
}

if (!$authcode1 || !$authcode_valid) {
	User::logOut(true);
	Link::redirect('login.php');
	exit;
}

// check if form submitted and process
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

if (!empty($_REQUEST['settings']) && $_SESSION['cp_uniq'] != $uniq1)
		$personal->errors[] = 'Page expired.';

if (!empty($match))
	$personal->errors[] = htmlentities(str_replace('[characters]',implode(',',array_unique($matches[0])),Lang::string('login-pass-chars-error')));
if (!empty($too_few_chars))
	$personal->errors[] = Lang::string('login-password-error');

// check if we should request 2fa
/*
if (!empty($_REQUEST['settings']) && !$token1 && !is_array($personal->errors) && !is_array(Errors::$errors)) {
	if (!empty($_REQUEST['request_2fa'])) {
		if (!($token1 > 0)) {
			$no_token = true;
			$request_2fa = true;
			Errors::add(Lang::string('security-no-token'));
		}
	}

	if (User::$info['verified_authy'] == 'Y' || User::$info['verified_google'] == 'Y') {
		if ($_REQUEST['send_sms'] || User::$info['using_sms'] == 'Y') {
			if (User::sendSMS()) {
				$sent_sms = true;
				Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
		$request_2fa = true;
	}
}
*/

// display errors or send pass change request
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
	if (empty($no_token) && !$request_2fa) {
		//$authcode2 = (User::$info['verified_authy'] == 'Y' || User::$info['verified_google'] == 'Y') ? false : $authcode1;
		//API::settingsChangeId($authcode2);
		//API::token($token1);
		API::settingsChangeId($authcode1);
		API::add('User','changePassword',array($personal->info['pass']));
		$query = API::send();
		
		if (!empty($query['error'])) {
			if ($query['error'] == 'security-com-error')
				Errors::add(Lang::string('security-com-error'));
				
			if ($query['error'] == 'authy-errors')
				Errors::merge($query['authy_errors']);
				
			if ($query['error'] == 'request-expired')
				Errors::add(Lang::string('settings-request-expired'));
				
			if ($query['error'] == 'security-incorrect-token')
				Errors::add(Lang::string('security-incorrect-token'));
		}
		
		if (!is_array(Errors::$errors)) {
			$_SESSION["cp_uniq"] = md5(uniqid(mt_rand(),true));
			Link::redirect('account.php?message=settings-personal-message');
		}
		else
			$request_2fa = true;
	}
}
else {
	$personal->info['pass'] = false;
}

$_SESSION["cp_uniq"] = md5(uniqid(mt_rand(),true));
$page_title = Lang::string('change-password');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="<?= Lang::url('index.php') ?>"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="change-password.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<div class="content_right">
		<div class="testimonials-4">
			<? 
            Errors::display(); 
            Messages::display();
            ?>
            <? if (!$request_2fa) { ?>
            <div class="text"><p><?= Lang::string('change-password-explain') ?></p></div>
            <div class="content">
            	<h3 class="section_label">
                    <span class="left"><i class="fa fa-user fa-2x"></i></span>
                    <span class="right"><?= Lang::string('change-password') ?></span>
                </h3>
                <div class="clear"></div>
                <?
                $personal->passwordInput('pass',Lang::string('settings-pass'),true);
                $personal->passwordInput('pass2',Lang::string('settings-pass-confirm'),true,false,false,false,false,false,'pass');
                $personal->HTML('<div class="form_button"><input type="submit" name="submit" value="'.Lang::string('settings-save-password').'" class="but_user" /></div>');
                $personal->hiddenInput('uniq',1,$_SESSION["cp_uniq"]);
                $personal->HTML('<input type="hidden" name="authcode" value="'.urlencode($authcode1).'" />');
                $personal->display();
                ?>
            	<div class="clear"></div>
            </div>
            <? } else { ?>
            <div class="content">
				<h3 class="section_label">
					<span class="left"><i class="fa fa-mobile fa-2x"></i></span>
					<span class="right"><?= Lang::string('security-enter-token') ?></span>
				</h3>
				<form id="enable_tfa" action="change-password.php" method="POST">
					<input type="hidden" name="request_2fa" value="1" />
					<input type="hidden" name="authcode" value="<?= urlencode($authcode1) ?>" />
					<input type="hidden" name="uniq" value="<?= $_SESSION["cp_uniq"] ?>" />
					<input type="hidden" name="ex_request" value="<?= urlencode(serialize($_REQUEST)) ?>" />
					<div class="buyform">
						<div class="one_half">
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="param">
								<label for="token"><?= Lang::string('security-token') ?></label>
								<input name="token" id="token" type="text" value="<?= $token1 ?>" />
								<div class="clear"></div>
							</div>
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('security-validate') ?>" class="but_user" /></li>
								<? if (User::$info['using_sms'] == 'Y') { ?>
								<li><input type="submit" name="sms" value="<?= Lang::string('security-resend-sms') ?>" class="but_user" /></li>
								<? } ?>
							</ul>
						</div>
					</div>
				</form>
                <div class="clear"></div>
			</div>
            <? } ?> 
            <div class="mar_top8"></div>
        </div>
	</div>
	<? include 'includes/sidebar_account.php'; ?>
</div>
<? include 'includes/foot.php'; ?>