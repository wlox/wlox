<?php
include '../cfg/cfg.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$authcode1 = $_REQUEST['authcode'];
if ($authcode1) {
	API::add('User','getSettingsChangeRequest',array(urlencode($authcode1)));
	$query = API::send();

	if ($query['User']['getSettingsChangeRequest']['results'][0]) {
		$step1 = true;
	}
	else
		Errors::add(Lang::string('settings-request-expired'));
}

$cell1 = ($_REQUEST['cell']) ? ereg_replace("[^0-9]", "",$_REQUEST['cell']) : User::$info['tel'];
$country_code1 = ($_REQUEST['country_code']) ? ereg_replace("[^0-9]", "",$_REQUEST['country_code']) : User::$info['country_code'];
$token1 = ereg_replace("[^0-9]", "",$_REQUEST['token']);

if ($_REQUEST['step'] == 1) {
	if (!($cell1 > 0))
		Errors::add(Lang::string('security-no-cell'));
	if (!($country_code1 > 0))
		Errors::add(Lang::string('security-no-cc'));
	
	if (!is_array(Errors::$errors)) {
		API::add('User','registerAuthy',array($cell1,$country_code1));
		$query = API::send();
		$authy_id = $query['User']['registerAuthy']['results'][0]['user']['id'];
		$response = $query['User']['registerAuthy']['results'][0];
		
		if (!$response || !is_array($response))
			Errors::merge(Lang::string('security-com-error'));
		
		if ($response['success'] == 'false')
			Errors::merge($response['errors']);
		
		if (!is_array(Errors::$errors)) {
			if ($_REQUEST['send_sms']) {
				if (User::sendSMS($authy_id))
					$using_sms = 'Y';
			}
			else
				$using_sms = 'N';
			
			if (!is_array(Errors::$errors)) {
				API::add('User','enableAuthy',array($cell1,$country_code1,$authy_id,$using_sms));
				API::add('User','settingsEmail2fa',array(array(1=>1),1));
				$query = API::send();
				//$step1 = true;

				if ($query['User']['settingsEmail2fa']['results'][0])
					Link::redirect('security.php?notice=email');
			}
		}
	}
}
elseif ($_REQUEST['step'] == 2) {
	if (!($token1 > 0))
		Errors::add(Lang::string('security-no-token'));
	
	if (!is_array(Errors::$errors)) {
		API::settingsChangeId(urldecode($authcode1));
		API::token($token1);
		API::add('User','verifiedAuthy');
		$query = API::send();
	
		if ($query['error']['security-com-error'])
			Errors::merge(Lang::string('security-com-error'));
	
		if ($query['error']['authy-errors'])
			Errors::merge($query['authy_errors']);
		
		if ($query['error'] == 'request-expired')
			Errors::add(Lang::string('settings-request-expired'));
	
		if (!is_array(Errors::$errors)) {
			Messages::add(Lang::string('security-success-message'));
			
			$step2 = true;
		}
	}
}

if ($_REQUEST['notice'] == 'email')
	$notice = Lang::string('settings-change-notice');

if (User::$info['verified_authy'] == 'Y' || $step2)
	API::add('Content','getRecord',array('security-setup'));
elseif ($step1)
	API::add('Content','getRecord',array('security-token'));
else
	API::add('Content','getRecord',array('security-explain'));

$query = API::send();
$content = $query['Content']['getRecord']['results'][0];
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
		<? if ((User::$info['verified_authy'] == 'Y' || $step2) && !$step1) { ?>
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Messages::display(); ?>
			<ul class="list_empty">
				<li><div class="number">+<?= User::$info['country_code']?> <?= User::$info['tel']?></div></li>
				<li><a class="item_label" href="javascript:return false;"><?= Lang::string('security-verified') ?></a></li>
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
		<? } else { ?>
			<?= ($notice) ? '<div class="notice"><div class="message-box-wrap">'.$notice.'</div></div>' : '' ?>
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Errors::display(); ?>
			<form name="start_auth" id="enable_tfa" action="security.php" method="POST">
				<input type="hidden" name="step" value="1" />
				<input type="hidden" id="send_sms" name="send_sms" value="" />
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
								<label for="authy-countries"><?= Lang::string('security-country') ?></label>
								<select name="country_code" id="authy-countries">
								<? 
								if ($country_code1 > 0) {
									echo '<option value="'.$country_code1.'" selected="selected"></option>';
								}
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="authy-cellphone"><?= Lang::string('security-cell') ?></label>
								<input name="cell" id="authy-cellphone" type="text" value="<?= $cell1 ?>" />
								<div class="clear"></div>
							</div>
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('security-enable') ?>" class="but_user" /></li>
								<li><input type="submit" name="sms" value="<?= Lang::string('security-send-sms') ?>" class="but_user" /></li>
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