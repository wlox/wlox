<?php
include '../cfg/cfg.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$account1 = ereg_replace("[^0-9]", "",$_REQUEST['account']);
$currency1 = ereg_replace("[^0-9]", "",$_REQUEST['currency']);
$description1 = preg_replace("/[^0-9a-zA-Z!@#$%&*?\.\-\_ ]/", "",$_REQUEST['description']);
$remove_id1 = ereg_replace("[^0-9]", "",$_REQUEST['remove_id']);


API::add('Content','getRecord',array((($_REQUEST['action'] == 'add') ? 'bank-accounts-add' : 'bank-accounts')));
API::add('BankAccounts','get');
API::add('BankAccounts','find',array($account1));
$query = API::send();

$bank_accounts = $query['BankAccounts']['get']['results'][0];
$content = $query['Content']['getRecord']['results'][0];
$page_title = Lang::string('bank-accounts');

if ($account1 > 0) {
	$exists = $query['BankAccounts']['find']['results'][0];
	if ($bank_accounts[$account1])
		Errors::add(Lang::string('bank-accounts-already-exists'));
	elseif ($exists)
		Errors::add(Lang::string('bank-accounts-already-associated'));
	if (strlen($account1) < 5)
		Errors::add(Lang::string('bank-accounts-invalid-number'));
	if (!($currency1 > 0))
		Errors::add(Lang::string('bank-accounts-no-currency'));
	
	if (!is_array(Errors::$errors)) {
		if (!$description1)
			$description1 = Lang::string('bank-accounts-crypto-label');
		
		$_REQUEST['action'] = false;
		API::add('BankAccounts','insert',array($account1,$currency1,$description1));
		API::add('BankAccounts','get',array(User::$info['id']));
		$query = API::send();
		
		Messages::add(Lang::string('bank-accounts-added-message'));
		
		$bank_accounts = $query['BankAccounts']['get']['results'][0];
	}
}

if ($remove_id1 > 0) {
	if ($bank_accounts[$account1])
		Errors::add(Lang::string('bank-accounts-remove-error'));
	
	if (!is_array(Errors::$errors)) {
		API::add('BankAccounts','delete',array($remove_id1));
		API::add('BankAccounts','get',array(User::$info['id']));
		$query = API::send();
		
		Messages::add(Lang::string('bank-accounts-removed-message'));
		
		$bank_accounts = $query['BankAccounts']['get']['results'][0];
	}
}

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="bank-accounts.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<? if (!$_REQUEST['action']) { ?>
    	<div class="text"><?= $content['content'] ?></div>
    	<div class="clearfix mar_top2"></div>
    	<div class="clear"></div>
    	<? Errors::display(); ?>
    	<? Messages::display(); ?>
    	<div class="clear"></div>
    	<ul class="list_empty">
			<li><a href="bank-accounts.php?action=add" class="but_user"><i class="fa fa-plus fa-lg"></i> <?= Lang::string('bank-accounts-add') ?></a></li>
		</ul>
    	<div class="table-style">
    		<table class="table-list trades">
				<tr>
					<th><?= Lang::string('bank-accounts-account') ?></th>
					<th><?= Lang::string('bank-accounts-currency') ?></th>
					<th><?= Lang::string('bank-accounts-description') ?></th>
					<th></th>
				</tr>
				<? 
				if ($bank_accounts) {
					foreach ($bank_accounts as $account) {
				?>
				<tr>
					<td><?= $account['account_number'] ?></td>
					<td><?= $account['currency'] ?></td>
					<td><?= $account['description'] ?></td>
					<td><a href="bank-accounts.php?remove_id=<?= $account['id'] ?>"><i class="fa fa-minus-circle"></i> <?= Lang::string('bank-accounts-remove') ?></a></td>
				</tr>
				<?
					}
				}
				else {
					echo '<tr><td colspan="4">'.Lang::string('bank-accounts-no').'</td></tr>';
				}
				?>
			</table>
		</div>
		<? } elseif ($_REQUEST['action'] == 'add') { ?>
		<div class="testimonials-4">
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Errors::display(); ?>
			<form id="add_bank_account" action="bank-accounts.php" method="POST">
				<input type="hidden" name="action" value="add" />
				<div class="buyform">
					<div class="content">
		            	<h3 class="section_label">
		                    <span class="left"><i class="fa fa-plus fa-2x"></i></span>
		                    <span class="right"><?= Lang::string('bank-accounts-add-label') ?></span>
		                </h3>
		                <div class="clear"></div>
		                <div class="one_half">
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="param">
								<label for="account"><?= Lang::string('bank-accounts-account-cc') ?></label>
								<input name="account" id="account" type="text" value="<?= $account1 ?>" />
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="description"><?= Lang::string('bank-accounts-description') ?></label>
								<input name="description" id="description" type="text" value="<?= $description1 ?>" />
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="currency"><?= Lang::string('bank-accounts-currency') ?></label>
								<select id="currency" name="currency">
								<? 
								if ($CFG->currencies) {
									foreach ($CFG->currencies as $currency) {
										echo '<option '.(($currency['id'] == $currency1 || (!$currency1 && $currency['currency'] == 'USD')) ? 'selected="selected"' : '').' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
									}
								}
								?>
								</select>
								<div class="clear"></div>
							</div>
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('bank-accounts-add-account') ?>" class="but_user" /></li>
							</ul>
		                </div>
		                <div class="clear"></div>
		            </div>
	            </div>
            </form>
		</div>
		<? } ?>
    </div>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>