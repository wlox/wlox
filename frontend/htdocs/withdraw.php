<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$page1 = (!empty($_REQUEST['page'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['page']) : false;
$btc_address1 = (!empty($_REQUEST['btc_address'])) ?  preg_replace("/[^\da-z]/i", "",$_REQUEST['btc_address']) : false;
$btc_amount1 = (!empty($_REQUEST['btc_amount']) && $_REQUEST['btc_amount'] > 0) ? preg_replace("/[^0-9.]/", "",$_REQUEST['btc_amount']) : 0;
$btc_total1 = (!empty($_REQUEST['btc_amount']) && $_REQUEST['btc_amount'] > 0) ? $btc_amount1 - $CFG->bitcoin_sending_fee : 0;
$account1 = (!empty($_REQUEST['account'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['account']) : false;
$fiat_amount1 = (!empty($_REQUEST['fiat_amount']) && $_REQUEST['fiat_amount'] > 0) ? preg_replace("/[^0-9.]/", "",$_REQUEST['fiat_amount']) : 0;
$fiat_total1 = (!empty($_REQUEST['btc_amount']) && $_REQUEST['btc_amount'] > 0) ? $fiat_amount1 - $CFG->fiat_withdraw_fee : 0;
$token1 = (!empty($_REQUEST['token'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['token']) : false;
$authcode1 = (!empty($_REQUEST['authcode'])) ? $_REQUEST['authcode'] : false;
$request_2fa = false;
$no_token = false;

if ((!empty($_REQUEST['bitcoins']) || !empty($_REQUEST['fiat'])) && !$token1) {
	if (!empty($_REQUEST['request_2fa'])) {
		if (!($token1 > 0)) {
			$no_token = true;
			$request_2fa = true;
			Errors::add(Lang::string('security-no-token'));
		}
	}

	if ((User::$info['verified_authy'] == 'Y'|| User::$info['verified_google'] == 'Y') && ((User::$info['confirm_withdrawal_2fa_btc'] == 'Y' && $_REQUEST['bitcoins']) || (User::$info['confirm_withdrawal_2fa_bank'] == 'Y' && $_REQUEST['fiat']))) {
		if (!empty($_REQUEST['send_sms']) || User::$info['using_sms'] == 'Y') {
			if (User::sendSMS()) {
				$sent_sms = true;
				Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
		$request_2fa = true;
	}
}

if ($authcode1) {
	API::add('Requests','emailValidate',array(urlencode($authcode1)));
	$query = API::send();

	if ($query['Requests']['emailValidate']['results'][0]) {
		Link::redirect('withdraw.php?message=withdraw-2fa-success');
	}
	else {
		Errors::add(Lang::string('settings-request-expired'));
	}
}

API::add('Status','get');
API::add('Requests','get',array(1,false,false,1));
API::add('Requests','get',array(false,$page1,15,1));
API::add('BankAccounts','get');
if ($account1 > 0) {
	API::add('BankAccounts','getRecord',array($account1));
}
$query = API::send();

$bank_accounts = $query['BankAccounts']['get']['results'][0];
if ($account1 > 0) {
	$bank_account = $query['BankAccounts']['getRecord']['results'][0];
}
elseif ($bank_accounts) {
	$key = key($bank_accounts);
	$bank_account = $bank_accounts[$key];	
}

$total = $query['Requests']['get']['results'][0];
$requests = $query['Requests']['get']['results'][1];
$status = $query['Status']['get']['results'][0];

API::add('User','getAvailable');
if ($bank_account) {
	if (is_numeric($bank_account['currency'])) {
		API::add('Currencies','getRecord',array(false,$bank_account['currency']));
		API::add('Currencies','getRecord',array(false,$bank_account['currency']));
	}
	else {
		API::add('Currencies','getRecord',array($bank_account['currency']));
		API::add('Currencies','getRecord',array($bank_account['currency']));
	}
	$query = API::send();
	
	$currency_info = $query['Currencies']['getRecord']['results'][0];
	$currency1 = $currency_info['currency'];
	$bank_account_currency = $query['Currencies']['getRecord']['results'][1];
}
else {
	API::add('Content','getRecord',array('deposit-no-bank'));
	$query = API::send();
	$bank_instructions = $query['Content']['getRecord']['results'][0];
}
$user_available = $query['User']['getAvailable']['results'][0];
$pagination = Content::pagination('withdraw.php',$page1,$total,15,5,false);

if ($CFG->withdrawals_status == 'suspended')
	Errors::add(Lang::string('withdrawal-suspended'));

if (!empty($_REQUEST['bitcoins'])) {
	if (!($btc_amount1 > 0))
		Errors::add(Lang::string('withdraw-amount-zero'));
	if ($btc_amount1 > $user_available['BTC'])
		Errors::add(Lang::string('withdraw-too-much'));
	
	API::add('BitcoinAddresses','validateAddress',array($btc_address1));
	$query = API::send();

	if (!$query['BitcoinAddresses']['validateAddress']['results'][0])
		Errors::add(Lang::string('withdraw-address-invalid'));
	
	if (!is_array(Errors::$errors)) {
		if (User::$info['confirm_withdrawal_email_btc'] == 'Y' && !$request_2fa && !$token1) {
			API::add('Requests','insert',array(1,false,$btc_amount1,$btc_address1));
			$query = API::send();
			Link::redirect('withdraw.php?notice=email');
		}
		elseif (!$request_2fa) {
			API::token($token1);
			API::add('Requests','insert',array(1,false,$btc_amount1,$btc_address1));
			$query = API::send();
			
			if ($query['error'] == 'security-com-error')
				Errors::add(Lang::string('security-com-error'));
			
			if ($query['error'] == 'authy-errors')
				Errors::merge($query['authy_errors']);
			
			if ($query['error'] == 'security-incorrect-token')
				Errors::add(Lang::string('security-incorrect-token'));
			
			if (!is_array(Errors::$errors)) {
				if ($query['Requests']['insert']['results'][0]) {
					if ($token1 > 0)
						Link::redirect('withdraw.php?message=withdraw-2fa-success');
					else
						Link::redirect('withdraw.php?message=withdraw-success');
				}	
			}
			elseif (!$no_token) {
				$request_2fa = true;
			}
		}
	}
	elseif (!$no_token) {
		$request_2fa = false;
	}
}
elseif (!empty($_REQUEST['fiat'])) {
	if (!($account1 > 0))
		Errors::add(Lang::string('withdraw-no-account'));
	if (!is_array($bank_account))
		Errors::add(Lang::string('withdraw-account-not-found'));
	if (!($fiat_amount1 > 0))
		Errors::add(Lang::string('withdraw-amount-zero'));
	if ($fiat_amount1 > 0 && $fiat_amount1 < 1)
		Errors::add(Lang::string('withdraw-amount-one'));
	if (!$bank_accounts[$bank_account['account_number']])
		Errors::add(Lang::string('withdraw-account-not-found'));
	if ($fiat_amount1 > $user_available[strtoupper($currency1)])
		Errors::add(Lang::string('withdraw-too-much'));
		
	if (!is_array(Errors::$errors)) {
		if (User::$info['confirm_withdrawal_email_bank'] == 'Y' && !$request_2fa && !$token1) {
			API::add('Requests','insert',array(false,$bank_account['currency'],$fiat_amount1,false,$bank_account['account_number']));
			$query = API::send();
			Link::redirect('withdraw.php?notice=email');
		}
		elseif (!$request_2fa) {
			API::token($token1);
			API::add('Requests','insert',array(false,$bank_account['currency'],$fiat_amount1,false,$bank_account['account_number']));
			$query = API::send();
			
			if ($query['error'] == 'security-com-error')
				Errors::add(Lang::string('security-com-error'));
				
			if ($query['error'] == 'authy-errors')
				Errors::merge($query['authy_errors']);
			
			if ($query['error'] == 'security-incorrect-token')
				Errors::add(Lang::string('security-incorrect-token'));
				
			if (!is_array(Errors::$errors)) {
				if ($query['Requests']['insert']['results'][0]) {
					if ($token1 > 0)
						Link::redirect('withdraw.php?message=withdraw-2fa-success');
					else
						Link::redirect('withdraw.php?message=withdraw-success');
				}
			}
			elseif (!$no_token) {
				$request_2fa = true;
			}
		}
	}
	elseif (!$no_token) {
		$request_2fa = false;
	}
}

if (!empty($_REQUEST['message'])) {
	if ($_REQUEST['message'] == 'withdraw-2fa-success')
		Messages::add(Lang::string('withdraw-2fa-success'));
	elseif ($_REQUEST['message'] == 'withdraw-success')
		Messages::add(Lang::string('withdraw-success'));
}

if (!empty($_REQUEST['notice']) && $_REQUEST['notice'] == 'email')
	$notice = Lang::string('withdraw-email-notice');

$page_title = Lang::string('withdraw');

if (empty($_REQUEST['bypass'])) {
	include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="withdraw.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<? Errors::display(); ?>
		<? Messages::display(); ?>
		<?= (!empty($notice)) ? '<div class="notice"><div class="message-box-wrap">'.$notice.'</div></div>' : '' ?>
		<div class="testimonials-4">
			<? if (!$request_2fa) { ?>
			<div class="one_half">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-btc fa-2x"></i></span>
						<span class="right"><?= Lang::string('withdraw-bitcoins') ?></span>
					</h3>
					<div class="clear"></div>
					<form id="buy_form" action="withdraw.php" method="POST">
						<div class="buyform">
							<div class="spacer"></div>
							<div class="calc dotted">
								<div class="label"><?= Lang::string('sell-btc-available') ?></div>
								<div class="value"><?= number_format($user_available['BTC'],8) ?> BTC</div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="param">
								<label for="btc_address"><?= Lang::string('withdraw-send-to-address') ?></label>
								<input type="text" id="btc_address" name="btc_address" value="<?= $btc_address1 ?>" />
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="btc_amount"><?= Lang::string('withdraw-send-amount') ?></label>
								<input type="text" id="btc_amount" name="btc_amount" value="<?= number_format($btc_amount1,8) ?>" />
								<div class="qualify">BTC</div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="calc">
								<div class="label"><?= Lang::string('withdraw-network-fee') ?> <a title="<?= Lang::string('withdraw-network-fee-explain') ?>" href="javascript:return false;"><i class="fa fa-question-circle"></i></a></div>
								<div class="value"><span id="withdraw_btc_network_fee"><?= $CFG->bitcoin_sending_fee ?></span> BTC</div>
								<div class="clear"></div>
							</div>
							<div class="calc bigger">
								<div class="label">
									<span id="withdraw_btc_total_label"><?= Lang::string('withdraw-btc-total') ?></span>
								</div>
								<div class="value"><span id="withdraw_btc_total"><?= number_format($btc_total1,8) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<input type="hidden" name="bitcoins" value="1" />
							<input type="submit" name="submit" value="<?= Lang::string('withdraw-send-bitcoins') ?>" class="but_user" />
						</div>
					</form>
					<div class="clear"></div>
				</div>
			</div>
			<div class="one_half last">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-money fa-2x"></i></span>
						<span class="right"><?= Lang::string('withdraw-fiat') ?></span>
					</h3>
					<div class="clear"></div>
					<form id="buy_form" action="withdraw.php" method="POST">
						<div class="buyform">
							<div class="spacer"></div>
							<? if ($bank_accounts) { ?>
							<div class="calc dotted">
								<div class="label"><?= str_replace('[currency]','<span class="currency_label">'.$currency_info['currency'].'</span>',Lang::string('buy-fiat-available')) ?></div>
								<div class="value"><span class="currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="user_available"><?= number_format($user_available[strtoupper($currency1)],2) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="param">
							<label for="withdraw_account"><?= Lang::string('withdraw-fiat-account') ?></label>
								<select id="withdraw_account" name="account">
								<?
								if ($bank_accounts) {
									foreach ($bank_accounts as $account) {
										echo '<option '.(($bank_account['id'] == $account['id']) ? 'selected="selected"' : '').' value="'.$account['id'].'">'.$account['account_number'].' - ('.$account['currency'].')</option>';
									}
								}	
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="fiat_amount"><?= Lang::string('withdraw-amount') ?></label>
								<input type="text" id="fiat_amount" name="fiat_amount" value="<?= number_format($fiat_amount1,2) ?>" />
								<div class="qualify"><span class="currency_label"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="calc">
								<div class="label"><?= str_replace('[currency]','<span class="currency_label">'.$currency_info['currency'].'</span>',Lang::string('buy-fee')) ?> <a title="<?= Lang::string('account-view-fee-schedule') ?>" href="fee-schedule.php"><i class="fa fa-question-circle"></i></a></div>
								<div class="value"><span class="currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="withdraw_fiat_fee"><?= number_format($CFG->fiat_withdraw_fee,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="calc bigger">
								<div class="label"><?= str_replace('[currency]','<span class="currency_label">'.$currency_info['currency'].'</span>',Lang::string('withdraw-total')) ?></div>
								<div class="value"><span class="currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="withdraw_fiat_total"><?= number_format($fiat_total1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<input type="hidden" name="fiat" value="1" />
							<input type="submit" name="submit" value="<?= Lang::string('withdraw-withdraw') ?>" class="but_user" />
							<? } else { ?>
							<div class="calc">
								<div class="text"><?= $bank_instructions['content'] ?></div>
								<div class="mar_top2"></div>
								<a class="item_label" href="bank-accounts.php"><i class="fa fa-cog"></i> <?= Lang::string('deposit-manage-bank-accounts') ?></a>
								<div class="clear"></div>
							</div>
							<? } ?>
						</div>
					</form>
				</div>
			</div>
			<? } else { ?>
			<div class="content">
				<h3 class="section_label">
					<span class="left"><i class="fa fa-mobile fa-2x"></i></span>
					<span class="right"><?= Lang::string('security-enter-token') ?></span>
				</h3>
				<form id="enable_tfa" action="withdraw.php" method="POST">
					<input type="hidden" name="request_2fa" value="1" />
					<input type="hidden" name="account" value="<?= $account1 ?>" />
					<input type="hidden" name="fiat_amount" value="<?= $fiat_amount1 ?>" />
					<input type="hidden" name="btc_address" value="<?= $btc_address1 ?>" />
					<input type="hidden" name="btc_amount" value="<?= $btc_amount1 ?>" />
					<input type="hidden" name="bitcoins" value="<?= ($_REQUEST['bitcoins']) ? '1' : '' ?>" />
					<input type="hidden" name="fiat" value="<?= ($_REQUEST['fiat']) ? '1' : '' ?>" />
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
		</div>
		<div class="mar_top3"></div>
		<div class="clear"></div>
		<h3><?= Lang::string('withdrawal-recent') ?></h3>
		<div id="filters_area">
<? } ?>
        	<div class="table-style">
        		<table class="table-list trades" id="bids_list">
        			<tr>
        				<th>ID</th>
        				<th><?= Lang::string('deposit-date') ?></th>
        				<th><?= Lang::string('deposit-description') ?></th>
        				<th><?= Lang::string('deposit-amount') ?></th>
        				<th><?= Lang::string('withdraw-net-amount') ?></th>
        				<th><?= Lang::string('deposit-status') ?></th>
        			</tr>
        			<? 
        			if ($requests) {
						foreach ($requests as $request) {
							echo '
					<tr>
						<td>'.$request['id'].'</td>
						<td><input type="hidden" class="localdate" value="'.(strtotime($request['date'])/* + $CFG->timezone_offset*/).'" /></td>
						<td>'.$request['description'].'</td>
						<td>'.(($request['fa_symbol'] == 'BTC') ? number_format($request['amount'],8).' '.$request['fa_symbol'] : $request['fa_symbol'].number_format($request['amount'],2)).'</td>
    					<td>'.(($request['fa_symbol'] == 'BTC') ? number_format((($request['net_amount'] > 0) ? $request['net_amount'] : ($request['amount'] - $request['fee'])),8).' '.$request['fa_symbol'] : $request['fa_symbol'].number_format((($request['net_amount'] > 0) ? $request['net_amount'] : ($request['amount'] - $request['fee'])),2)).'</td>
						<td>'.$request['status'].'</td>
					</tr>';
						}
					}
					else {
						echo '<tr><td colspan="5">'.Lang::string('withdraw-no').'</td></tr>';
					}
        			?>
        		</table>
			</div>
			<?= $pagination ?>
<? if (empty($_REQUEST['bypass'])) { ?>
		</div>
		<div class="mar_top5"></div>
	</div>
</div>
<? include 'includes/foot.php'; ?>
<? } ?>