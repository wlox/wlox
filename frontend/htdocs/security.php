<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$step1 = false;
$step2 = false;
$step3 = false;
$step4 = false;

$authcode1 = (!empty($_REQUEST['authcode'])) ? urldecode($_REQUEST['authcode']) : false;
if ($authcode1 && empty($_REQUEST['step'])) {
	API::add('User','getSettingsChangeRequest',array(urlencode($authcode1)));
	$query = API::send();
	$response = unserialize(base64_decode($query['User']['getSettingsChangeRequest']['results'][0]));
	if ($response) {
		if (!empty($response['authy']))
			$step1 = true;
		elseif (!empty($response['google']))
			$step3 = true;
	}
	else
		Errors::add(Lang::string('settings-request-expired'));
}

$cell1 = (!empty($_REQUEST['cell'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['cell']) : false;
$country_code1 = (!empty($_REQUEST['country_code'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['country_code']) : false;
$token1 = (!empty($_REQUEST['token'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['token']) : false;
$remove = !empty($_REQUEST['remove']);

if ($remove) {
	if (empty($_REQUEST['submitted']) || (!empty($_REQUEST['method']) && $_REQUEST['method'] == 'sms')) {
		if (User::$info['using_sms'] == 'Y') {
			if (User::sendSMS()) {
				$sent_sms = true;
				Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
	}
	else {
		if (!($token1 > 0))
			Errors::add(Lang::string('security-no-token'));
		
		if (!is_array(Errors::$errors)) {
			API::token($token1);
			API::add('User','disable2fa');
			$query = API::send();
		
			if ($query['error'] == 'security-incorrect-token')
				Errors::add(Lang::string('security-incorrect-token'));
			
			if ($query['error'] == 'security-com-error')
				Errors::add(Lang::string('security-com-error'));
		
			if ($query['error'] == 'authy-errors')
				Errors::merge($query['authy_errors']);
		
			if ($query['error'] == 'request-expired')
				Errors::add(Lang::string('settings-request-expired'));
		
			if (!is_array(Errors::$errors)) {
				Link::redirect('security.php?message=security-disabled-message');
			}
		}
	}
}

if (!empty($_REQUEST['step']) && $_REQUEST['step'] == 1) {
	if (!($cell1 > 0) && $_REQUEST['method'] != 'google')
		Errors::add(Lang::string('security-no-cell'));
	if (!($country_code1 > 0) && $_REQUEST['method'] != 'google')
		Errors::add(Lang::string('security-no-cc'));
	
	if (!is_array(Errors::$errors)) {
		if ($_REQUEST['method'] != 'google') {
			API::add('User','registerAuthy',array($cell1,$country_code1));
			$query = API::send();
			$authy_id = $query['User']['registerAuthy']['results'][0]['user']['id'];
			$response = $query['User']['registerAuthy']['results'][0];
			
			if (!$response || !is_array($response))
				Errors::merge(Lang::string('security-com-error'));
			
			if ($response['success'] == 'false')
				Errors::merge($response['errors']);
		}
		
		if (!is_array(Errors::$errors)) {
			if ($_REQUEST['method'] != 'google') {
				if ($_REQUEST['method'] == 'sms') {
					if (User::sendSMS($authy_id))
						$using_sms = 'Y';
				}
				else
					$using_sms = 'N';
				
				if (!is_array(Errors::$errors)) {
					API::add('User','enableAuthy',array($cell1,$country_code1,$authy_id,$using_sms));
					API::add('User','settingsEmail2fa',array(array('authy'=>1),1));
					$query = API::send();
					//$step1 = true;
	
					if ($query['User']['settingsEmail2fa']['results'][0])
						Link::redirect('security.php?notice=email');
				}
			}
			else {
				if (!is_array(Errors::$errors)) {
					API::add('User','enableGoogle2fa',array($cell1,$country_code1));
					API::add('User','settingsEmail2fa',array(array('google'=>1),1));
					$query = API::send();
					//$step1 = true;
				
					if ($query['User']['settingsEmail2fa']['results'][0])
						Link::redirect('security.php?notice=email');
				}
			}
		}
	}
}
elseif (!empty($_REQUEST['step']) && $_REQUEST['step'] == 2) {
	if (!($token1 > 0))
		Errors::add(Lang::string('security-no-token'));
	
	if (!is_array(Errors::$errors)) {
		API::settingsChangeId($authcode1);
		API::token($token1);
		API::add('User','verifiedAuthy');
		$query = API::send();
	
		if (!empty($query['error'])) {
			if ($query['error'] == 'security-com-error')
				Errors::add(Lang::string('security-com-error'));
		
			if ($query['error'] == 'authy-errors')
				Errors::merge($query['authy_errors']);
			
			if ($query['error'] == 'request-expired')
				Errors::add(Lang::string('settings-request-expired'));
		}
		
		if (!is_array(Errors::$errors)) {
			Messages::add(Lang::string('security-success-message'));
			
			$step2 = true;
		}
		else
			$step1 = true;
	}
	else
		$step1 = true;
}
elseif (!empty($_REQUEST['step']) && $_REQUEST['step'] == 3) {
	if (!($token1 > 0))
		Errors::add(Lang::string('security-no-token'));

	if (!is_array(Errors::$errors)) {
		API::settingsChangeId($authcode1);
		API::token($token1);
		API::add('User','verifiedGoogle');
		$query = API::send();

		if ($query['error'] == 'security-incorrect-token')
			Errors::add(Lang::string('security-incorrect-token'));
		
		if ($query['error'] == 'request-expired')
			Errors::add(Lang::string('settings-request-expired'));

		if (!is_array(Errors::$errors)) {
			Messages::add(Lang::string('security-success-message'));
				
			$step4 = true;
		}
		else
			$step3 = true;
	}
	else
		$step3 = true;
}

if (!empty($_REQUEST['notice']) && $_REQUEST['notice'] == 'email')
	$notice = Lang::string('settings-change-notice');
elseif (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'security-disabled-message')
	Messages::add(Lang::string('security-disabled-message'));

if (User::$info['verified_authy'] == 'Y' || $step2)
	API::add('Content','getRecord',array('security-setup'));
elseif (User::$info['verified_google'] == 'Y' || $step4)
	API::add('Content','getRecord',array('security-setup-google'));
elseif ($step1)
	API::add('Content','getRecord',array('security-token'));
elseif ($step3) {
	API::add('Content','getRecord',array('security-google'));
	API::add('User','getGoogleSecret');
}
else
	API::add('Content','getRecord',array('security-explain'));

$query = API::send();
$content = $query['Content']['getRecord']['results'][0];
$secret = (!empty($query['User']['getGoogleSecret'])) ? $query['User']['getGoogleSecret']['results'][0] : false;
$page_title = Lang::string('security');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="security.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<div class="testimonials-4">
		<? if ($remove) { ?>
			<? Errors::display(); ?>
			<div class="buyform">
					<div class="content">
						<h3 class="section_label">
							<span class="left"><i class="fa fa-mobile fa-2x"></i></span>
							<span class="right"><?= Lang::string('security-enter-token') ?></span>
						</h3>
						<form id="enable_tfa" action="security.php" method="POST">
							<input type="hidden" name="remove" value="1" />
							<input type="hidden" name="submitted" value="1" />
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
				</div>
		<? } elseif (User::$info['verified_authy'] == 'Y' || $step2) { ?>
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Messages::display(); ?>
			<ul class="list_empty">
				<li><div class="number">+<?= User::$info['country_code']?> <?= User::$info['tel']?></div></li>
				<li><a class="item_label" href="javascript:return false;"><?= Lang::string('security-verified') ?></a></li>
			</ul>
			<ul class="list_empty">
				<li><a href="security.php?remove=1" class="but_user"><i class="fa fa-times fa-lg"></i> <?= Lang::string('security-disable') ?></a></li>
			</ul>
		<? } elseif (User::$info['verified_google'] == 'Y' || $step4) { ?>
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Messages::display(); ?>
			<ul class="list_empty">
				<li><a href="security.php?remove=1" class="but_user"><i class="fa fa-times fa-lg"></i> <?= Lang::string('security-disable') ?></a></li>
			</ul>
		<? } elseif ($step1) { ?>
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Errors::display(); ?>
			<form id="enable_tfa" action="security.php" method="POST">
				<input type="hidden" name="step" value="2" />
				<input type="hidden" name="authcode" value="<?= urlencode($authcode1) ?>" />
				<div class="buyform">
					<div class="content">
		            	<h3 class="section_label">
		                    <span class="left"><i class="fa fa-mobile fa-2x"></i></span>
		                    <span class="right"><?= Lang::string('security-enter-token') ?></span>
		                </h3>
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
							</ul>
		                </div>
		                <div class="clear"></div>
		            </div>
	            </div>
            </form>
		<? } elseif ($step3) { ?>
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Errors::display(); ?>
			<form id="enable_tfa" action="security.php" method="POST">
				<input type="hidden" name="step" value="3" />
				<input type="hidden" name="authcode" value="<?= urlencode($authcode1) ?>" />
				<div class="buyform">
					<div class="content">
		            	<h3 class="section_label">
		                    <span class="left"><i class="fa fa-mobile fa-2x"></i></span>
		                    <span class="right"><?= Lang::string('security-scan-qr') ?></span>
		                </h3>
		                <div class="clear"></div>
		                <div class="one_half">
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="param">
								<label for="secret"><?= Lang::string('security-secret-code') ?></label>
								<input type="text" id="secret" name="secret" value="<?= $secret['secret'] ?>" />
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="calc">
								<img class="qrcode" src="includes/qrcode.php?sec=1&code=otpauth://totp/<?= $secret['label'] ?>?secret=<?= $secret['secret'] ?>" />
							</div>
							<div class="spacer"></div>
							<div class="param">
								<label for="token"><?= Lang::string('security-token') ?></label>
								<input name="token" id="token" type="text" value="<?= $token1 ?>" />
								<div class="clear"></div>
							</div>
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('security-validate') ?>" class="but_user" /></li>
							</ul>
		                </div>
		                <div class="clear"></div>
		            </div>
	            </div>
            </form>
		<? } else { ?>
			<?= (!empty($notice)) ? '<div class="notice"><div class="message-box-wrap">'.$notice.'</div></div>' : '' ?>
			<? Errors::display(); ?>
			<? Messages::display(); ?>
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<form name="start_auth" id="enable_tfa" action="security.php" method="POST">
				<input type="hidden" name="step" value="1" />
				<input type="hidden" id="send_sms" name="send_sms" value="" />
				<input type="hidden" id="google_2fa" name="google_2fa" value="" />
				<div class="buyform">
					<div class="content">
		            	<h3 class="section_label">
		                    <span class="left"><i class="fa fa-mobile fa-2x"></i></span>
		                    <span class="right"><?= Lang::string('security-enable-two-factor') ?></span>
		                </h3>
		                <div class="one_half">
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="param">
								<label for="method"><?= Lang::string('security-method') ?></label>
								<select name="method" id="method">
									<option <?= ($_REQUEST['method'] == 'google') ? 'selected="selected"' : false ?> value="google">Google Authenticator</option>
									<option <?= ($_REQUEST['method'] == 'authy') ? 'selected="selected"' : false ?> value="authy">Authy</option>
									<option <?= ($_REQUEST['method'] == 'SMS') ? 'selected="selected"' : false ?> value="SMS">SMS</option>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param method_show" style="display:none;">
								<label for="authy-countries"><?= Lang::string('security-country') ?> (<?= Lang::string('security-optional-google') ?>)</label>
								<select name="country_code" id="authy-countries">
								<? 
								if ($country_code1 > 0) {
									echo '<option value="'.$country_code1.'" selected="selected"></option>';
								}
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param method_show" style="display:none;">
								<label for="authy-cellphone"><?= Lang::string('security-cell') ?> (<?= Lang::string('security-optional-google') ?>)</label>
								<input name="cell" id="authy-cellphone" type="text" value="<?= $cell1 ?>" />
								<div class="clear"></div>
							</div>
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('security-enable') ?>" class="but_user" /></li>
								<!-- li><input type="submit" name="google" value="<?= Lang::string('security-enable-google') ?>" class="but_user" /></li -->
								<!--  li><input type="submit" name="sms" value="<?= Lang::string('security-send-sms') ?>" class="but_user" /></li -->
							</ul>
		                </div>
		                <div class="clear"></div>
		            </div>
	            </div>
            </form>
		<? } ?>
		</div>
		<div class="mar_top8"></div>
	</div>
</div>
<? include 'includes/foot.php'; ?>