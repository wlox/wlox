<?php
include '../lib/common.php';

if (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$account_deactivated = (User::$info['deactivated'] == 'Y');
$account_locked = (User::$info['locked'] == 'Y');
$token1 = (!empty($_REQUEST['token'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['token']) : false;
$authcode1 = (!empty($_REQUEST['authcode'])) ? $_REQUEST['authcode'] : false;
$email_auth = false;
$match = false;
$request_2fa = false;
$too_few_chars = false;
$expired = false;
$no_token = false;

API::add('User','getInfo',array($_SESSION['session_id']));
//API::add('User','getCountries');
$query = API::send();
//$countries = $query['User']['getCountries']['results'][0];

if (!empty($_REQUEST['ex_request'])) {
	$uniq = $_REQUEST['uniq'];
	$_REQUEST = unserialize(urldecode($_REQUEST['ex_request']));
	if ($_REQUEST['settings'])
		$_REQUEST['settings']['uniq'] = $uniq;
	else
		$_REQUEST['uniq'] = $uniq;
}

if ($authcode1) {
	API::add('User','getSettingsChangeRequest',array(urlencode($authcode1)));
	$query = API::send();

	if ($query['User']['getSettingsChangeRequest']['results'][0]) {
		$_REQUEST = unserialize(base64_decode($query['User']['getSettingsChangeRequest']['results'][0]));
		unset($_REQUEST['submitted']);
		$email_auth = true;
	}
	else
		Errors::add(Lang::string('settings-request-expired'));
}

if (empty($_REQUEST['settings']['pass'])) {
	unset($_REQUEST['settings']['pass']);
	unset($_REQUEST['settings']['pass2']);
	unset($_REQUEST['verify_fields']['pass']);
	unset($_REQUEST['verify_fields']['pass2']);
}
else {
	$_REQUEST['verify_fields']['pass'] = 'password';
	$_REQUEST['verify_fields']['pass2'] = 'password';
}

if (!empty($_REQUEST['settings'])) {
	if (!$email_auth && (empty($_SESSION["settings_uniq"]) || $_SESSION["settings_uniq"] != $_REQUEST['settings']['uniq']))
		$expired = true;
	
	if (!empty($_REQUEST['settings']['pass'])) {
		$match = preg_match_all($CFG->pass_regex,$_REQUEST['settings']['pass'],$matches);
		$too_few_chars = (mb_strlen($_REQUEST['settings']['pass'],'utf-8') < $CFG->pass_min_chars);
	}
	
	if (!empty($_REQUEST['settings']['pass'])) {
		$_REQUEST['settings']['pass'] = preg_replace($CFG->pass_regex, "",$_REQUEST['settings']['pass']);
		$_REQUEST['settings']['pass2'] = preg_replace($CFG->pass_regex, "",$_REQUEST['settings']['pass2']);
	}
	
	//$_REQUEST['settings']['first_name'] = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u", "",$_REQUEST['settings']['first_name']);
	//$_REQUEST['settings']['last_name'] = preg_replace("/[^\pL a-zA-Z0-9@\._-\s]/u", "",$_REQUEST['settings']['last_name']);
	//$_REQUEST['settings']['country'] = preg_replace("/[^0-9]/", "",$_REQUEST['settings']['country']);
	$_REQUEST['settings']['email'] = preg_replace("/[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/", "",$_REQUEST['settings']['email']);
}

$personal = new Form('settings',false,false,'form1','site_users');
if (!empty($query['User']['getInfo']['results'][0]))
	$personal->get($query['User']['getInfo']['results'][0]);

if (!$personal->info['email'])
	unset($personal->info['email']);

$personal->verify();

if ($expired)
	$personal->errors[] = 'Page expired.';
if ($match)
	$personal->errors[] = htmlentities(str_replace('[characters]',implode(',',array_unique($matches[0])),Lang::string('login-pass-chars-error')));
if ($too_few_chars)	
	$personal->errors[] = Lang::string('login-password-error');


if (!empty($_REQUEST['submitted']) && empty($_REQUEST['settings'])) {
	if (!$email_auth && (empty($_SESSION["settings_uniq"]) || $_SESSION["settings_uniq"] != $_REQUEST['uniq']))
		Errors::add('Page expired.');
}

if (!empty($_REQUEST['submitted']) && !$token1 && !is_array($personal->errors) && !is_array(Errors::$errors)) {
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
	else {
		API::add('User','settingsEmail2fa',array($_REQUEST));
		$query = API::send();
		
		$_SESSION["settings_uniq"] = md5(uniqid(mt_rand(),true));
		Link::redirect('settings.php?notice=email');
	}
}

if (!empty($_REQUEST['settings']) && is_array($personal->errors)) {
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
	$request_2fa = false;
}
elseif (!empty($_REQUEST['settings']) && !is_array($personal->errors)) {
	if (empty($no_token) && !$request_2fa) {
		API::settingsChangeId($authcode1);
		API::token($token1);
		API::add('User','updatePersonalInfo',array($personal->info));
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
			$_SESSION["settings_uniq"] = md5(uniqid(mt_rand(),true));
			Link::redirect('settings.php?message=settings-personal-message');
		}
		else
			$request_2fa = true;
	}
}

if (empty($_REQUEST['prefs'])) {
	$confirm_withdrawal_2fa_btc1 = User::$info['confirm_withdrawal_2fa_btc'];
	$confirm_withdrawal_email_btc1 = User::$info['confirm_withdrawal_email_btc'];
	$confirm_withdrawal_2fa_bank1 = User::$info['confirm_withdrawal_2fa_bank'];
	$confirm_withdrawal_email_bank1 = User::$info['confirm_withdrawal_email_bank'];
	$notify_deposit_btc1 = User::$info['notify_deposit_btc'];
	$notify_deposit_bank1 = User::$info['notify_deposit_bank'];
	$notify_withdraw_btc1 = User::$info['notify_withdraw_btc'];
	$notify_withdraw_bank1 = User::$info['notify_withdraw_bank'];
	$notify_login1 = User::$info['notify_login'];
}
else {
	$confirm_withdrawal_2fa_btc1 = $_REQUEST['confirm_withdrawal_2fa_btc'];
	$confirm_withdrawal_email_btc1 = $_REQUEST['confirm_withdrawal_email_btc'];
	$confirm_withdrawal_2fa_bank1 = $_REQUEST['confirm_withdrawal_2fa_bank'];
	$confirm_withdrawal_email_bank1 = $_REQUEST['confirm_withdrawal_email_bank'];
	$notify_deposit_btc1 = $_REQUEST['notify_deposit_btc'];
	$notify_deposit_bank1 = $_REQUEST['notify_deposit_bank'];
	$notify_withdraw_btc1 = $_REQUEST['notify_withdraw_btc'];
	$notify_withdraw_bank1 = $_REQUEST['notify_withdraw_bank'];
	$notify_login1 = $_REQUEST['notify_login'];
}

if (!empty($_REQUEST['prefs'])) {
	if (!$email_auth && (empty($_SESSION["settings_uniq"]) || $_SESSION["settings_uniq"] != $_REQUEST['uniq']))
		Errors::add('Page expired.');
	elseif (!$no_token && !$request_2fa) {
		API::settingsChangeId($authcode1);
		API::token($token1);
		API::add('User','updateSettings',array($confirm_withdrawal_2fa_btc1,$confirm_withdrawal_email_btc1,$confirm_withdrawal_2fa_bank1,$confirm_withdrawal_email_bank1,$notify_deposit_btc1,$notify_deposit_bank1,$notify_login1,$notify_withdraw_btc1,$notify_withdraw_bank1));
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
			$_SESSION["settings_uniq"] = md5(uniqid(mt_rand(),true));
			Link::redirect('settings.php?message=settings-settings-message');
		}
		else
			$request_2fa = true;
	}
}

if (!empty($_REQUEST['deactivate_account'])) {
	if (!$email_auth && (empty($_SESSION["settings_uniq"]) || $_SESSION["settings_uniq"] != $_REQUEST['uniq']))
		Errors::add('Page expired.');
	else {
		API::add('User','hasCurrencies');
		$query = API::send();
		$found = $query['User']['hasCurrencies']['results'][0];
	}
	
	if ($found) {
		Errors::add(Lang::string('settings-deactivate-error'));
		$request_2fa = false;
	}
	else {
		if (!$no_token && !$request_2fa) {
			API::settingsChangeId($authcode1);
			API::token($token1);
			API::add('User','deactivateAccount');
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
				$_SESSION["settings_uniq"] = md5(uniqid(mt_rand(),true));
				Link::redirect('settings.php?message=settings-account-deactivated');
			}
			else
				$request_2fa = true;
		}
	}
}

if (!empty($_REQUEST['reactivate_account'])) {
	if (!$email_auth && (empty($_SESSION["settings_uniq"]) || $_SESSION["settings_uniq"] != $_REQUEST['uniq']))
		Errors::add('Page expired.');
	elseif (!$no_token && !$request_2fa) {
		API::settingsChangeId($authcode1);
		API::token($token1);
		API::add('User','reactivateAccount');
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
			$_SESSION["settings_uniq"] = md5(uniqid(mt_rand(),true));
			Link::redirect('settings.php?message=settings-account-reactivated');
		}
		else
			$request_2fa = true;
	}
}
/*
if (!empty($_REQUEST['lock_account'])) {
	if (!$email_auth && (empty($_SESSION["settings_uniq"]) || $_SESSION["settings_uniq"] != $_REQUEST['uniq']))
		Errors::add('Page expired.');
	elseif (!$no_token && !$request_2fa) {
		API::settingsChangeId($authcode1);
		API::token($token1);
		API::add('User','lockAccount');
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
			$_SESSION["settings_uniq"] = md5(uniqid(mt_rand(),true));
			Link::redirect('settings.php?message=settings-account-locked');
		}
		else
			$request_2fa = true;
	}
}

if (!empty($_REQUEST['unlock_account'])) {
	if (!$email_auth && (empty($_SESSION["settings_uniq"]) || $_SESSION["settings_uniq"] != $_REQUEST['uniq']))
		Errors::add('Page expired.');
	elseif (!$no_token && !$request_2fa) {
		API::settingsChangeId($authcode1);
		API::token($token1);
		API::add('User','unlockAccount');
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
			$_SESSION["settings_uniq"] = md5(uniqid(mt_rand(),true));
			Link::redirect('settings.php?message=settings-account-unlocked');
		}
		else
			$request_2fa = true;
	}
}
*/
if (!empty($_REQUEST['message'])) {
	if ($_REQUEST['message'] == 'settings-personal-message')
		Messages::add(Lang::string('settings-personal-message'));
	elseif ($_REQUEST['message'] == 'settings-settings-message')
		Messages::add(Lang::string('settings-settings-message'));
	elseif ($_REQUEST['message'] == 'settings-account-deactivated')
		Messages::add(Lang::string('settings-account-deactivated'));
	elseif ($_REQUEST['message'] == 'settings-account-reactivated')
		Messages::add(Lang::string('settings-account-reactivated'));
	elseif ($_REQUEST['message'] == 'settings-account-locked')
		Messages::add(Lang::string('settings-account-locked'));
	elseif ($_REQUEST['message'] == 'settings-account-unlocked')
		Messages::add(Lang::string('settings-account-unlocked'));
}

if (!empty($_REQUEST['notice']) && $_REQUEST['notice'] == 'email')
	$notice = Lang::string('settings-change-notice');

$cur_sel = array();
if ($CFG->currencies) {
	foreach ($CFG->currencies as $key => $currency) {
		if (is_numeric($key) || $currency['currency'] == 'BTC')
			continue;
		
		$cur_sel[$key] = $currency;
	}
}

$page_title = Lang::string('settings');
$_SESSION["settings_uniq"] = md5(uniqid(mt_rand(),true));

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="settings.php"><?= $page_title ?></a></div>
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
            <?= (!empty($notice)) ? '<div class="notice"><div class="message-box-wrap">'.$notice.'</div></div>' : '' ?>
            <? if (!$request_2fa && !$account_deactivated && !$account_locked) { ?>
            <div class="content">
            	<h3 class="section_label">
                    <span class="left"><i class="fa fa-user fa-2x"></i></span>
                    <span class="right"><?= Lang::string('settings-personal-info') ?></span>
                </h3>
                <div class="clear"></div>
                <?
                $personal->passwordInput('pass',Lang::string('settings-pass'));
                $personal->passwordInput('pass2',Lang::string('settings-pass-confirm'),false,false,false,false,false,false,'pass');
                //$personal->textInput('first_name',Lang::string('settings-first-name'));
                //$personal->textInput('last_name',Lang::string('settings-last-name'));
                //$personal->selectInput('country',Lang::string('settings-country'),false,false,$countries,false,array('name'));
                $personal->textInput('email',Lang::string('settings-email'),'email');
                $personal->selectInput('default_currency',Lang::string('default-currency'),0,$CFG->currencies['USD']['id'],$cur_sel,false,array('currency'));
                $personal->HTML('<div class="form_button"><input type="submit" name="submit" value="'.Lang::string('settings-save-info').'" class="but_user" /></div><input type="hidden" name="submitted" value="1" />');
                $personal->hiddenInput('uniq',1,$_SESSION["settings_uniq"]);
                $personal->display();
                ?>
            	<div class="clear"></div>
            </div>
            <div class="mar_top3"></div>
            <div class="clear"></div>
            <div class="content">
	            <h3 class="section_label">
					<span class="left"><i class="fa fa-check fa-2x"></i></span>
					<span class="right"><?= Lang::string('settings-conf') ?></span>
				</h3>
				<div class="clear"></div>
				<form id="buy_form" action="settings.php" method="POST">
					<input type="hidden" name="prefs" value="1" />
					<input type="hidden" name="submitted" value="1" />
					<input type="hidden" name="uniq" value="<?= $_SESSION["settings_uniq"] ?>" />
					<div class="buyform">
						<div class="spacer"></div>
						<? if (User::$info['verified_authy'] == 'Y' || User::$info['verified_google'] == 'Y') { ?>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="confirm_withdrawal_2fa_btc" id="confirm_withdrawal_2fa_btc" type="checkbox" value="Y" <?= ($confirm_withdrawal_2fa_btc1 == 'Y') ? 'checked="checked"' : '' ?> />
							<label for="confirm_withdrawal_2fa_btc"><?= Lang::string('settings-withdrawal-2fa-btc') ?></label>
							<div class="clear"></div>
						</div>
						<? } ?>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="confirm_withdrawal_email_btc" id="confirm_withdrawal_email_btc" type="checkbox" value="Y" <?= ($confirm_withdrawal_email_btc1 == 'Y') ? 'checked="checked"' : '' ?> />
							<label for="confirm_withdrawal_email_btc"><?= Lang::string('settings-withdrawal-email-btc') ?></label>
							<div class="clear"></div>
						</div>
						<? if (User::$info['verified_authy'] == 'Y' || User::$info['verified_google'] == 'Y') { ?>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="confirm_withdrawal_2fa_bank" id="confirm_withdrawal_2fa_bank" type="checkbox" value="Y" <?= ($confirm_withdrawal_2fa_bank1 == 'Y') ? 'checked="checked"' : '' ?> />
							<label for="confirm_withdrawal_2fa_bank"><?= Lang::string('settings-withdrawal-2fa-bank') ?></label>
							<div class="clear"></div>
						</div>
						<? } ?>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="confirm_withdrawal_email_bank" id="confirm_withdrawal_email_bank" type="checkbox" value="Y" <?= ($confirm_withdrawal_email_bank1 == 'Y') ? 'checked="checked"' : '' ?> />
							<label for="confirm_withdrawal_email_bank"><?= Lang::string('settings-withdrawal-email-bank') ?></label>
							<div class="clear"></div>
						</div>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="notify_deposit_btc" id="notify_deposit_btc" type="checkbox" value="Y" <?= ($notify_deposit_btc1 == 'Y') ? 'checked="checked"' : '' ?> />
							<label for="notify_deposit_btc"><?= Lang::string('settings-notify-deposit-btc') ?></label>
							<div class="clear"></div>
						</div>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="notify_deposit_bank" id="notify_deposit_bank" type="checkbox" value="Y" <?= ($notify_deposit_bank1 == 'Y') ? 'checked="checked"' : '' ?> />
							<label for="notify_deposit_bank"><?= Lang::string('settings-notify-deposit-bank') ?></label>
							<div class="clear"></div>
						</div>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="notify_withdraw_btc" id="notify_withdraw_btc" type="checkbox" value="Y" <?= ($notify_withdraw_btc1 == 'Y') ? 'checked="checked"' : '' ?> />
							<label for="notify_withdraw_btc"><?= Lang::string('settings-notify-withdraw-btc') ?></label>
							<div class="clear"></div>
						</div>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="notify_withdraw_bank" id="notify_withdraw_bank" type="checkbox" value="Y" <?= ($notify_withdraw_bank1 == 'Y') ? 'checked="checked"' : '' ?> />
							<label for="notify_withdraw_bank"><?= Lang::string('settings-notify-withdraw-bank') ?></label>
							<div class="clear"></div>
						</div>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="notify_login" id="notify_login" type="checkbox" value="Y" <?= ($notify_login1 == 'Y') ? 'checked="checked"' : '' ?> />
							<label for="notify_login"><?= Lang::string('settings-notify-login') ?></label>
							<div class="clear"></div>
						</div>
						<div class="mar_top2"></div>
						<ul class="list_empty">
							<li><input type="submit" name="submit" value="<?= Lang::string('settings-save-settings') ?>" class="but_user" /></li>
						</ul>
					</div>
				</form>
	            <div class="clear"></div>
            </div>
            <div class="mar_top3"></div>
            <? /*
            <div class="clear"></div>
            <div class="content1">
	            <h3 class="section_label">
					<span class="left"><i class="fa fa-lock fa-2x"></i></span>
					<span class="right"><?= Lang::string('settings-lock-account') ?></span>
				</h3>
				<div class="clear"></div>
				<div class="mar_top1"></div>
				<div class="text"><?= Lang::string('settings-lock-account-explain') ?></div>
				<div class="mar_top3"></div>
           		<div class="clear"></div>
				<ul class="list_empty">
					<li><a class="but_user" href="settings.php?lock_account=1&submitted=1&uniq=<?= $_SESSION["settings_uniq"] ?>"><?= Lang::string('settings-lock-account') ?></a></li>
				</ul>
				<div class="clear"></div>
			</div>
			*/ ?>
            <div class="clear"></div>
            <div class="content1">
	            <h3 class="section_label">
					<span class="left"><i class="fa fa-ban fa-2x"></i></span>
					<span class="right"><?= Lang::string('settings-delete-account') ?></span>
				</h3>
				<div class="clear"></div>
				<div class="mar_top1"></div>
				<div class="text"><?= Lang::string('settings-delete-account-explain') ?></div>
				<div class="mar_top3"></div>
           		<div class="clear"></div>
				<ul class="list_empty">
					<li><a class="but_user" href="settings.php?deactivate_account=1&submitted=1&uniq=<?= $_SESSION["settings_uniq"] ?>"><?= Lang::string('settings-delete-account') ?></a></li>
				</ul>
				<div class="clear"></div>
			</div>
			<? } elseif ($account_locked && !$request_2fa) { ?>
			<div class="content1">
	            <h3 class="section_label">
					<span class="left"><i class="fa fa-lock fa-2x"></i></span>
					<span class="right"><?= Lang::string('settings-account-locked') ?></span>
				</h3>
				<div class="clear"></div>
				<div class="mar_top3"></div>
				<div class="clear"></div>
				<div class="notice"><div class="message-box-wrap"><?= Lang::string('settings-account-locked-explain') ?></div></div>
				<div class="mar_top3"></div>
           		<div class="clear"></div>
           		<? /*
				<ul class="list_empty">
					<li><a class="but_user" href="settings.php?unlock_account=1&submitted=1&uniq=<?= $_SESSION["settings_uniq"] ?>"><?= Lang::string('settings-unlock-account') ?></a></li>
				</ul>
				<div class="clear"></div>
				*/ ?>
			</div>
			<? } elseif ($account_deactivated && !$request_2fa) { ?>
			<div class="content1">
	            <h3 class="section_label">
					<span class="left"><i class="fa fa-power-off fa-2x"></i></span>
					<span class="right"><?= Lang::string('settings-reactivate-account') ?></span>
				</h3>
				<div class="clear"></div>
				<div class="mar_top1"></div>
				<div class="text"><?= Lang::string('settings-reactivate-account-explain') ?></div>
				<div class="mar_top3"></div>
           		<div class="clear"></div>
				<ul class="list_empty">
					<li><a class="but_user" href="settings.php?reactivate_account=1&submitted=1&uniq=<?= $_SESSION["settings_uniq"] ?>"><?= Lang::string('settings-reactivate-account') ?></a></li>
				</ul>
				<div class="clear"></div>
			</div>
            <? } else { ?>
            <div class="content">
				<h3 class="section_label">
					<span class="left"><i class="fa fa-mobile fa-2x"></i></span>
					<span class="right"><?= Lang::string('security-enter-token') ?></span>
				</h3>
				<form id="enable_tfa" action="settings.php" method="POST">
					<input type="hidden" name="request_2fa" value="1" />
					<input type="hidden" name="ex_request" value="<?= urlencode(serialize($_REQUEST)) ?>" />
					<input type="hidden" name="uniq" value="<?= $_SESSION["settings_uniq"] ?>" />
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
</div>
<? include 'includes/foot.php'; ?>