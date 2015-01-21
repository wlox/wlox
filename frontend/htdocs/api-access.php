<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$request_2fa = false;
$no_2fa = false;

if (!(User::$info['verified_authy'] == 'Y' || User::$info['verified_google'] == 'Y')) {
	$no_2fa = true;
}

if (!$request_2fa && !$no_2fa) {
	API::add('APIKeys','get');
	$query = API::send();
	$api_keys = $query['APIKeys']['get']['results'][0];
}

$token1 = (!empty($_REQUEST['token'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['token']) : false;
$remove_id1 = (!empty($_REQUEST['remove_id'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['remove_id']) : false;
if (!empty($_REQUEST['permissions']))
	$permissions = (is_array($_REQUEST['permissions'])) ? $_REQUEST['permissions'] : unserialize(urldecode($_REQUEST['permissions']));
else
	$permissions = false;

if (!empty($_REQUEST['action']) && ($_REQUEST['action'] == 'edit' || $_REQUEST['action'] == 'add' || $_REQUEST['action'] == 'delete')) {
	if (!$token1) {
		if (!empty($_REQUEST['request_2fa'])) {
			if (!($token1 > 0)) {
				$no_token = true;
				$request_2fa = true;
				Errors::add(Lang::string('security-no-token'));
			}
		}
	
		if (User::$info['verified_authy'] == 'Y' || User::$info['verified_google'] == 'Y') {
			if (!empty($_REQUEST['send_sms']) || User::$info['using_sms'] == 'Y') {
				if (User::sendSMS()) {
					$sent_sms = true;
					Messages::add(Lang::string('withdraw-sms-sent'));
				}
			}
			$request_2fa = true;
		}
	}
	else {
		API::token($token1);
		if ($_REQUEST['action'] == 'edit')
			API::add('APIKeys','edit',array($permissions));
		elseif ($_REQUEST['action'] == 'add')
			API::add('APIKeys','add');
		elseif ($_REQUEST['action'] == 'delete')
			API::add('APIKeys','delete',array($remove_id1));
		$query = API::send();
		
		if (!empty($query['error'])) {
			if ($query['error'] == 'security-com-error')
				Errors::add(Lang::string('security-com-error'));
			
			if ($query['error'] == 'authy-errors')
				Errors::merge($query['authy_errors']);
			
			if ($query['error'] == 'security-incorrect-token')
				Errors::add(Lang::string('security-incorrect-token'));
		}
		
		if ($_REQUEST['action'] == 'delete' && !$query['APIKeys']['delete']['results'][0])
			Link::redirect('api-access.php?error=delete');
		
		if (!is_array(Errors::$errors)) {
			if ($_REQUEST['action'] == 'edit')
				Link::redirect('api-access.php?message=edit');
			elseif ($_REQUEST['action'] == 'add') {
				$secret = $query['APIKeys']['add']['results'][0];
				Messages::add(Lang::string('api-add-message'));
				$info_message = str_replace('[secret]',$secret,Lang::string('api-add-show-secret'));
				
				API::add('APIKeys','get');
				$query = API::send();
				$api_keys = $query['APIKeys']['get']['results'][0];
			}
			elseif ($_REQUEST['action'] == 'delete')
				Link::redirect('api-access.php?message=delete');
		}
		else
			$request_2fa = true;
	}
}

if (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'edit')
	Messages::add(Lang::string('api-edit-message'));
elseif (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'delete')
	Messages::add(Lang::string('api-delete-message'));
elseif (!empty($_REQUEST['error']) && $_REQUEST['error'] == 'delete')
	Errors::add(Lang::string('api-delete-error'));

$page_title = Lang::string('api-access-setup');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="api-access.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<div class="testimonials-4">
			<? 
            Errors::display(); 
            Messages::display();
            if (!empty($info_message)) {
				echo '<div class="text dotted"><p>'.$info_message.'</p></div><div class="clear"></div><div class="mar_top1"></div>';
			} 
			else {
			?>
			<div class="info"><div class="message-box-wrap"><?= Lang::string('api-go-to-docs') ?></div></div>
			<?
			}
            ?>
            <? if ($no_2fa) { ?>
			<div class="content1">
	            <h3 class="section_label">
					<span class="left"><i class="fa fa-ban fa-2x"></i></span>
					<span class="right"><?= Lang::string('api-disabled') ?></span>
				</h3>
				<div class="clear"></div>
				<div class="mar_top1"></div>
				<div class="text"><?= Lang::string('api-disabled-explain') ?></div>
				<div class="mar_top3"></div>
           		<div class="clear"></div>
				<ul class="list_empty">
					<li><a class="but_user" href="security.php"><?= Lang::string('api-setup-security') ?></a></li>
				</ul>
				<div class="clear"></div>
			</div>
			<? } elseif ($request_2fa) { ?>
			<div class="content">
				<h3 class="section_label">
					<span class="left"><i class="fa fa-mobile fa-2x"></i></span>
					<span class="right"><?= Lang::string('security-enter-token') ?></span>
				</h3>
				<form id="enable_tfa" action="api-access.php" method="POST">
					<input type="hidden" name="request_2fa" value="1" />
					<input type="hidden" name="permissions" value="<?= urlencode(serialize($permissions)) ?>" />
					<input type="hidden" name="remove_id" value="<?= $remove_id1 ?>" />
					<input type="hidden" name="action" value="<?= preg_replace("/[^a-z]/", "",$_REQUEST['action']) ?>" />
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
			<? } else { ?>
			<div class="clear"></div>
			<form id="add_bank_account" action="api-access.php" method="POST">
				<input type="hidden" name="action" value="edit" />
	           	<ul class="list_empty">
					<li><a style="display:block;" href="api-access.php?action=add" class="but_user"><i class="fa fa-plus fa-lg"></i> <?= Lang::string('api-add-new') ?></a></li>
					<? if ($api_keys) { ?><li><input style="display:block;" type="submit" class="but_user" value="<?= Lang::string('api-add-save') ?>" /></li><? } ?>
				</ul>
		    	<div class="table-style">
		    		<table class="table-list trades">
						<tr>
							<th colspan="5"><?= Lang::string('api-keys') ?></th>
						</tr>
						<? 
						if ($api_keys) {
							foreach ($api_keys as $api_key) {
						?>
						<tr>
							<td class="api-label first"><?= Lang::string('api-key') ?>:</td>
							<td class="api-key" colspan="3"><?= $api_key['key'] ?></td>
							<td><a href="api-access.php?remove_id=<?= $api_key['id'] ?>&action=delete"><i class="fa fa-minus-circle"></i> <?= Lang::string('bank-accounts-remove') ?></a></td>
						</tr>
						<tr>
							<td class="api-label"><?= Lang::string('api-permissions') ?>:</td>
							<td class="inactive">
								<input type="checkbox" id="permission_<?= $api_key['id'] ?>_view" name="permissions[<?= $api_key['id'] ?>][view]" value="Y" <?= ($api_key['view'] == 'Y') ? 'checked="checked"' : '' ?> />
								<label for="permission_<?= $api_key['id'] ?>_view"><?= Lang::string('api-permission_view') ?></label>
							</td>
							<td class="inactive">
								<input type="checkbox" id="permission_<?= $api_key['id'] ?>_orders" name="permissions[<?= $api_key['id'] ?>][orders]" value="Y"<?= ($api_key['orders'] == 'Y') ? 'checked="checked"' : '' ?> />
								<label for="permission_<?= $api_key['id'] ?>_orders"><?= Lang::string('api-permission_orders') ?></label>
							</td>
							<td class="inactive">
								<input type="checkbox" id="permission_<?= $api_key['id'] ?>_view" name="permissions[<?= $api_key['id'] ?>][withdraw]" value="Y" <?= ($api_key['withdraw'] == 'Y') ? 'checked="checked"' : '' ?> />
								<label for="permission_<?= $api_key['id'] ?>_withdraw"><?= Lang::string('api-permission_withdraw') ?></label>
							</td>
							<td class="inactive"></td>
						</tr>
						<?
							}
						}
						else {
							echo '<tr><td colspan="5">'.Lang::string('api-keys-no').'</td></tr>';
						}
						?>
					</table>
				</div>
			</form>
           	<? } ?>
            <div class="mar_top8"></div>
        </div>
	</div>
</div>
<? include 'includes/foot.php'; ?>
