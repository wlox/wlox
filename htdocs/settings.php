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

$account_deactivated = (User::$info['deactivated'] == 'Y');
$account_locked = (User::$info['locked'] == 'Y');
	
if ($_REQUEST['request_2fa']) {
	$token1 = ereg_replace("[^0-9]", "",$_REQUEST['token']);

	if ($_REQUEST['send_sms']) {
		$sent_sms = SiteUser::sendSMS();
		if ($sent_sms)
			Messages::add(Lang::string('withdraw-sms-sent'));

		$request_2fa = true;
	}
	else {
		$did_2fa = SiteUser::confirmToken($token1);
		if (!$did_2fa)
			$request_2fa = true;
			
		$_REQUEST = unserialize(urldecode($_REQUEST['ex_request']));
	}
}
elseif ($_REQUEST['email_auth']) {
	$request_id = Encryption::decrypt(urldecode($_REQUEST['authcode']));
	if ($request_id > 0) {
		$change_request = DB::getRecord('change_settings',$request_id,0,1);
		db_delete('change_settings',$request_id);
		$_REQUEST = unserialize(base64_decode($change_request['request']));
		$did_2fa = true;
	}
	else
		Errors::add(Lang::string('settings-request-expired'));
}

if (!$request_2fa) {
	$personal = new Form('settings',false,false,'form1','site_users');
	$personal->verify();
	$personal->get(User::$info['id']);
	$personal->info['pass'] = ereg_replace("[^0-9a-zA-Z!@#$%&*?\.\-\_]", "",$personal->info['pass']);
	$personal->info['first_name'] = ereg_replace("/[^\da-z]/i", "",$personal->info['first_name']);
	$personal->info['last_name'] = ereg_replace("/[^\da-z]/i", "",$personal->info['last_name']);
	$personal->info['country'] = ereg_replace("[^0-9]", "",$personal->info['country']);
	$personal->info['email'] = ereg_replace("[^a-zA-Z@.!#$%&'*+-/=?^_`{|}~]", "",$personal->info['email']);

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
		if (User::$info['verified_authy'] == 'Y' && !$did_2fa) {
			$request_2fa = true;
			if (User::$info['using_sms'] == 'Y' && !$sent_sms) {
				$sent_sms = SiteUser::sendSMS();
				if ($sent_sms)
					Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
		elseif (!$did_2fa) {
			$request_id = db_insert('change_settings',array('date'=>date('Y-m-d H:i:s'),'request'=>base64_encode(serialize($_REQUEST))));
			if ($request_id > 0) {
				$vars = User::$info;
				$vars['authcode'] = urlencode(Encryption::encrypt($request_id));
					
				$email = SiteEmail::getRecord('settings-auth');
				Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$vars);
				Link::redirect('settings.php?notice=email');
			}
		}
		else {
			$personal->save();
		
			User::logOut(1);
			User::logIn($personal->info['user'],$personal->info['pass'],'site','openbtcexchange_user');
			$_SESSION['token_verified'] = 1;
			Link::redirect('settings.php?message=settings-personal-message');
		}
	}
	
	$confirm_withdrawal_2fa_btc1 = (User::$info['confirm_withdrawal_2fa_btc'] == 'Y');
	$confirm_withdrawal_email_btc1 = (User::$info['confirm_withdrawal_email_btc'] == 'Y');
	$confirm_withdrawal_2fa_bank1 = (User::$info['confirm_withdrawal_2fa_bank'] == 'Y');
	$confirm_withdrawal_email_bank1 = (User::$info['confirm_withdrawal_email_bank'] == 'Y');
	$notify_deposit_btc1 = (User::$info['notify_deposit_btc'] == 'Y');
	$notify_deposit_bank1 = (User::$info['notify_deposit_bank'] == 'Y');
	$notify_login1 = (User::$info['notify_login'] == 'Y');
	
	if ($_REQUEST['prefs']) {
		if (User::$info['verified_authy'] == 'Y' && !$did_2fa) {
			$request_2fa = true;
		
			if (User::$info['using_sms'] == 'Y' && !$sent_sms) {
				$sent_sms = SiteUser::sendSMS();
				if ($sent_sms)
					Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
		elseif (!$did_2fa) {
			$request_id = db_insert('change_settings',array('date'=>date('Y-m-d H:i:s'),'request'=>base64_encode(serialize($_REQUEST))));
			if ($request_id > 0) {
				$vars = User::$info;
				$vars['authcode'] = urlencode(Encryption::encrypt($request_id));
					
				$email = SiteEmail::getRecord('settings-auth');
				Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$vars);
				Link::redirect('settings.php?notice=email');
			}
		}
		else {
			$confirm_withdrawal_2fa_btc1 = $_REQUEST['confirm_withdrawal_2fa_btc'];
			$confirm_withdrawal_email_btc1 = $_REQUEST['confirm_withdrawal_email_btc'];
			$confirm_withdrawal_2fa_bank1 = $_REQUEST['confirm_withdrawal_2fa_bank'];
			$confirm_withdrawal_email_bank1 = $_REQUEST['confirm_withdrawal_email_bank'];
			$notify_deposit_btc1 = $_REQUEST['notify_deposit_btc'];
			$notify_deposit_bank1 = $_REQUEST['notify_deposit_bank'];
			$notify_login1 = $_REQUEST['notify_login'];
			
			$confirm_withdrawal_2fa_btc2 = ($_REQUEST['confirm_withdrawal_2fa_btc']) ? 'Y' : 'N';
			$confirm_withdrawal_email_btc2 = ($_REQUEST['confirm_withdrawal_email_btc']) ? 'Y' : 'N';
			$confirm_withdrawal_2fa_bank2 = ($_REQUEST['confirm_withdrawal_2fa_bank']) ? 'Y' : 'N';
			$confirm_withdrawal_email_bank2 = ($_REQUEST['confirm_withdrawal_email_bank']) ? 'Y' : 'N';
			$notify_deposit_btc2 = ($_REQUEST['notify_deposit_btc']) ? 'Y' : 'N';
			$notify_deposit_bank2 = ($_REQUEST['notify_deposit_bank']) ? 'Y' : 'N';
			$notify_login2 = ($_REQUEST['notify_deposit_bank']) ? 'Y' : 'N';
			
			db_update('site_users',User::$info['id'],array('confirm_withdrawal_2fa_btc'=>$confirm_withdrawal_2fa_btc2,'confirm_withdrawal_email_btc'=>$confirm_withdrawal_email_btc2,'confirm_withdrawal_2fa_bank'=>$confirm_withdrawal_2fa_bank2,'confirm_withdrawal_email_bank'=>$confirm_withdrawal_email_bank2,'notify_deposit_btc'=>$notify_deposit_btc2,'notify_deposit_bank'=>$notify_deposit_bank2,'notify_login'=>$notify_login2));
			Link::redirect('settings.php?message=settings-settings-message');
		}
	}
	
	if ($_REQUEST['deactivate_account']) {
		if ($CFG->currencies) {
			$found = false;
			if (!(User::$info['btc'] > 0)) {
				foreach ($CFG->currencies as $currency => $info) {
					if (User::$info[strtolower($currency)] > 0) {
						$found = true;
						break;
					}
				}
			}
			else
				$found = true;
			
			if ($found) {
				Errors::add(Lang::string('settings-deactivate-error'));
			}
			else {
				if (User::$info['verified_authy'] == 'Y' && !$did_2fa) {
					$request_2fa = true;
					if (User::$info['using_sms'] == 'Y' && !$sent_sms) {
						$sent_sms = SiteUser::sendSMS();
						if ($sent_sms)
							Messages::add(Lang::string('withdraw-sms-sent'));
					}
				}
				elseif (!$did_2fa) {
					$request_id = db_insert('change_settings',array('date'=>date('Y-m-d H:i:s'),'request'=>base64_encode(serialize($_REQUEST))));
					if ($request_id > 0) {
						$vars = User::$info;
						$vars['authcode'] = urlencode(Encryption::encrypt($request_id));
							
						$email = SiteEmail::getRecord('settings-auth');
						Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$vars);
						Link::redirect('settings.php?notice=email');
					}
				}
				else {
					db_update('site_users',User::$info['id'],array('deactivated'=>'Y','deactivated_date'=>date('Y-m-d')));
					Link::redirect('settings.php?message=settings-account-deactivated');
					$account_deactivated = true;
				}
			}
		}
	}
	
	if ($_REQUEST['reactivate_account']) {
		if (User::$info['verified_authy'] == 'Y' && !$did_2fa) {
			$request_2fa = true;
			if (User::$info['using_sms'] == 'Y' && !$sent_sms) {
				$sent_sms = SiteUser::sendSMS();
				if ($sent_sms)
					Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
		elseif (!$did_2fa) {
			$request_id = db_insert('change_settings',array('date'=>date('Y-m-d H:i:s'),'request'=>base64_encode(serialize($_REQUEST))));
			if ($request_id > 0) {
				$vars = User::$info;
				$vars['authcode'] = urlencode(Encryption::encrypt($request_id));
					
				$email = SiteEmail::getRecord('settings-auth');
				Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$vars);
				Link::redirect('settings.php?notice=email');
			}
		}
		else {
			db_update('site_users',User::$info['id'],array('deactivated'=>'N'));
			Link::redirect('settings.php?message=settings-account-reactivated');
			$account_deactivated = false;
		}
	}
	
	if ($_REQUEST['lock_account']) {
		if (User::$info['verified_authy'] == 'Y' && !$did_2fa) {
			$request_2fa = true;
			if (User::$info['using_sms'] == 'Y' && !$sent_sms) {
				$sent_sms = SiteUser::sendSMS();
				if ($sent_sms)
					Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
		elseif (!$did_2fa) {
			$request_id = db_insert('change_settings',array('date'=>date('Y-m-d H:i:s'),'request'=>base64_encode(serialize($_REQUEST))));
			if ($request_id > 0) {
				$vars = User::$info;
				$vars['authcode'] = urlencode(Encryption::encrypt($request_id));
					
				$email = SiteEmail::getRecord('settings-auth');
				Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$vars);
				Link::redirect('settings.php?notice=email');
			}
		}
		else {
			db_update('site_users',User::$info['id'],array('locked'=>'Y'));
			Link::redirect('settings.php?message=settings-account-locked');
			$account_locked = false;
		}
	}
	
	if ($_REQUEST['unlock_account']) {
		if (User::$info['verified_authy'] == 'Y' && !$did_2fa) {
			$request_2fa = true;
			if (User::$info['using_sms'] == 'Y' && !$sent_sms) {
				$sent_sms = SiteUser::sendSMS();
				if ($sent_sms)
					Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
		elseif (!$did_2fa) {
			$request_id = db_insert('change_settings',array('date'=>date('Y-m-d H:i:s'),'request'=>base64_encode(serialize($_REQUEST))));
			if ($request_id > 0) {
				$vars = User::$info;
				$vars['authcode'] = urlencode(Encryption::encrypt($request_id));
					
				$email = SiteEmail::getRecord('settings-auth');
				Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$vars);
				Link::redirect('settings.php?notice=email');
			}
		}
		else {
			db_update('site_users',User::$info['id'],array('locked'=>'N'));
			Link::redirect('settings.php?message=settings-account-unlocked');
			$account_locked = false;
		}
	}
}

if ($_REQUEST['message'] == 'settings-personal-message')
	Messages::add(Lang::string('settings-personal-message'));
elseif ($_REQUEST['message'] == 'settings-settings-message')
	Messages::add(Lang::string('settings-settings-message'));
elseif ($_REQUEST['notice'] == 'email')
	$notice = Lang::string('settings-change-notice');
elseif ($_REQUEST['message'] == 'settings-account-deactivated')
	Messages::add(Lang::string('settings-account-deactivated'));
elseif ($_REQUEST['message'] == 'settings-account-reactivated')
	Messages::add(Lang::string('settings-account-reactivated'));
elseif ($_REQUEST['message'] == 'settings-account-locked')
	Messages::add(Lang::string('settings-account-locked'));
elseif ($_REQUEST['message'] == 'settings-account-unlocked')
	Messages::add(Lang::string('settings-account-unlocked'));

$page_title = Lang::string('settings');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="settings.php"><?= $page_title ?></a></div>
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
            <?= ($notice) ? '<div class="notice"><div class="message-box-wrap">'.$notice.'</div></div>' : '' ?>
            <? if (!$request_2fa && !$account_deactivated && !$account_locked) { ?>
            <div class="content">
            	<h3 class="section_label">
                    <span class="left"><i class="fa fa-user fa-2x"></i></span>
                    <span class="right"><?= Lang::string('settings-personal-info') ?></span>
                </h3>
                <div class="clear"></div>
                <?
                $personal->hiddenInput('user',false,User::$info['user']);
                $personal->passwordInput('pass',Lang::string('settings-pass'),true);
                $personal->passwordInput('pass2',Lang::string('settings-pass-confirm'),true,false,false,false,false,false,'pass');
                $personal->textInput('first_name',Lang::string('settings-first-name'));
                $personal->textInput('last_name',Lang::string('settings-last-name'));
                $personal->selectInput('country',Lang::string('settings-country'),1,false,false,'iso_countries',array('name'));
                $personal->textInput('email',Lang::string('settings-email'),'email');
                $personal->HTML('<div class="form_button"><input type="submit" name="submit" value="'.Lang::string('settings-save-info').'" class="but_user" /></div>');
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
					<div class="buyform">
						<div class="spacer"></div>
						<? if (User::$info['verified_authy'] == 'Y') { ?>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="confirm_withdrawal_2fa_btc" id="confirm_withdrawal_2fa_btc" type="checkbox" value="1" <?= ($confirm_withdrawal_2fa_btc1) ? 'checked="checked"' : '' ?> />
							<label for="confirm_withdrawal_2fa_btc"><?= Lang::string('settings-withdrawal-2fa-btc') ?></label>
							<div class="clear"></div>
						</div>
						<? } ?>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="confirm_withdrawal_email_btc" id="confirm_withdrawal_email_btc" type="checkbox" value="1" <?= ($confirm_withdrawal_email_btc1) ? 'checked="checked"' : '' ?> />
							<label for="confirm_withdrawal_email_btc"><?= Lang::string('settings-withdrawal-email-btc') ?></label>
							<div class="clear"></div>
						</div>
						<? if (User::$info['verified_authy'] == 'Y') { ?>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="confirm_withdrawal_2fa_bank" id="confirm_withdrawal_2fa_bank" type="checkbox" value="1" <?= ($confirm_withdrawal_2fa_bank1) ? 'checked="checked"' : '' ?> />
							<label for="confirm_withdrawal_2fa_bank"><?= Lang::string('settings-withdrawal-2fa-bank') ?></label>
							<div class="clear"></div>
						</div>
						<? } ?>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="confirm_withdrawal_email_bank" id="confirm_withdrawal_email_bank" type="checkbox" value="1" <?= ($confirm_withdrawal_email_bank1) ? 'checked="checked"' : '' ?> />
							<label for="confirm_withdrawal_email_bank"><?= Lang::string('settings-withdrawal-email-bank') ?></label>
							<div class="clear"></div>
						</div>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="notify_deposit_btc" id="notify_deposit_btc" type="checkbox" value="1" <?= ($notify_deposit_btc1) ? 'checked="checked"' : '' ?> />
							<label for="notify_deposit_btc"><?= Lang::string('settings-notify-deposit-btc') ?></label>
							<div class="clear"></div>
						</div>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="notify_deposit_bank" id="notify_deposit_bank" type="checkbox" value="1" <?= ($notify_deposit_bank1) ? 'checked="checked"' : '' ?> />
							<label for="notify_deposit_bank"><?= Lang::string('settings-notify-deposit-bank') ?></label>
							<div class="clear"></div>
						</div>
						<div class="param lessbottom marginleft">
							<input class="checkbox" name="notify_login" id="notify_login" type="checkbox" value="1" <?= ($notify_login1) ? 'checked="checked"' : '' ?> />
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
					<li><a class="but_user" href="settings.php?lock_account=1"><?= Lang::string('settings-lock-account') ?></a></li>
				</ul>
				<div class="clear"></div>
			</div>
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
					<li><a class="but_user" href="settings.php?deactivate_account=1"><?= Lang::string('settings-delete-account') ?></a></li>
				</ul>
				<div class="clear"></div>
			</div>
			<? } elseif ($account_locked) { ?>
			<div class="content1">
	            <h3 class="section_label">
					<span class="left"><i class="fa fa-unlock fa-2x"></i></span>
					<span class="right"><?= Lang::string('settings-unlock-account') ?></span>
				</h3>
				<div class="clear"></div>
				<div class="mar_top1"></div>
				<div class="text"><?= Lang::string('settings-unlock-account-explain') ?></div>
				<div class="mar_top3"></div>
           		<div class="clear"></div>
				<ul class="list_empty">
					<li><a class="but_user" href="settings.php?unlock_account=1"><?= Lang::string('settings-unlock-account') ?></a></li>
				</ul>
				<div class="clear"></div>
			</div>
			<? } elseif ($account_deactivated) { ?>
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
					<li><a class="but_user" href="settings.php?reactivate_account=1"><?= Lang::string('settings-reactivate-account') ?></a></li>
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
					<div class="buyform">
						<div class="one_half">
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="param">
								<label for="authy-token"><?= Lang::string('security-token') ?></label>
								<input name="token" id="authy-token" type="text" value="<?= $token1 ?>" />
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