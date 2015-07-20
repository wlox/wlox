<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$order_id1 = preg_replace("/[^0-9]/", "",$_REQUEST['order_id']);
$bypass = (!empty($_REQUEST['bypass']));
$buy = (!empty($_REQUEST['buy']));
$sell = (!empty($_REQUEST['sell']));
$buy = (!empty($_REQUEST['buy']));
$sell = (!empty($_REQUEST['sell']));

if ($buy || $sell) {
	if (empty($_SESSION["editorder_uniq"]) || empty($_REQUEST['uniq']) || !in_array($_REQUEST['uniq'],$_SESSION["editorder_uniq"]))
		Errors::add('Page expired.');
}

API::add('Orders','getRecord',array($order_id1));
$query = API::send();
$order_info = $query['Orders']['getRecord']['results'][0];
$currency_info = $CFG->currencies[$order_info['currency']];
$currency1 = strtolower($currency_info['currency']);

if (empty($order_info['id']) || $order_info['site_user'] != $order_info['user_id']) {
	Link::redirect('open-orders.php?message=order-doesnt-exist');
	exit;
}

API::add('Orders','getBidAsk',array($currency1));
API::add('Orders','get',array(false,false,10,$currency1,false,false,1));
API::add('Orders','get',array(false,false,10,$currency1,false,false,false,false,1));
API::add('FeeSchedule','getRecord',array(User::$info['fee_schedule']));
API::add('User','getAvailable');
$query = API::send();

$user_fee_both = $query['FeeSchedule']['getRecord']['results'][0];
$user_available = $query['User']['getAvailable']['results'][0];
$current_bid = $query['Orders']['getBidAsk']['results'][0]['bid'];
$current_ask = $query['Orders']['getBidAsk']['results'][0]['ask'];
$bids = $query['Orders']['get']['results'][0];
$asks = $query['Orders']['get']['results'][1];
$buy_market_price1 = 0;
$sell_market_price1 = 0;
$buy_limit = 1;
$sell_limit = 1;
$pre_btc_available = false;
$pre_fiat_available = false;
$buy_amount1 = false;
$sell_amount1 = false;
$buy_price1 = false;
$sell_price1 = false;
$buy_stop_price1 = false;
$sell_stop_price1 = false;
$buy_stop = false;
$sell_stop = false;
$buy_subtotal1 = false;
$sell_subtotal1 = false;
$buy_total1 = false;
$sell_total1 = false;
$user_fee_bid = false;
$user_fee_ask = false;

if ($order_info['is_bid']) {
	$buy_amount1 = (!empty($_REQUEST['buy_amount'])) ? preg_replace("/[^0-9.]/", "",$_REQUEST['buy_amount']) : $order_info['btc'];
	$buy_market_price1 = (!empty($_REQUEST['buy_market_price'])) ? $_REQUEST['buy_market_price'] : ($order_info['market_price'] == 'Y');
	$buy_price1 = (!empty($_REQUEST['buy_price'])) ? preg_replace("/[^0-9.]/", "",$_REQUEST['buy_price']) : (($order_info['btc_price'] > 0) ? $order_info['btc_price'] : ($buy_market_price1 ? $current_ask : 0));
	$buy_stop_price1 = (!empty($_REQUEST['buy_stop_price'])) ? preg_replace("/[^0-9.]/", "",$_REQUEST['buy_stop_price']) : $order_info['stop_price'];
	$user_fee_bid = ($buy_price1 >= $asks[0]['btc_price'] || $buy_market_price1) ? $user_fee_both['fee'] : $user_fee_both['fee1'];
	$buy_subtotal1 = $buy_amount1 * (($buy_price1 > 0) ? $buy_price1 : $buy_stop_price1);
	$buy_fee_amount1 = ($user_fee_bid * 0.01) * $buy_subtotal1;
	$buy_total1 = round($buy_subtotal1 + $buy_fee_amount1,2,PHP_ROUND_HALF_UP);
	$pre_fiat_available = (!empty($user_available[strtoupper($currency1)])) ? $user_available[strtoupper($currency1)] : false;
	$user_available[strtoupper($currency1)] += ($order_info['btc'] * $order_info['btc_price']) + (($user_fee_bid * 0.01) * ($order_info['btc'] * $order_info['btc_price']));
	$buy_stop = ($buy_stop_price1 > 0);
	$buy_limit = ($buy_price1 > 0 && !$buy_market_price1) ? 1 : !empty($_REQUEST['buy_limit']);
	$old_fiat = ($order_info['btc'] * $order_info['btc_price']) + (($order_info['btc'] * $order_info['btc_price']) * ($user_fee_bid * 0.01));
}
else {
	$sell_amount1 = (!empty($_REQUEST['sell_amount'])) ? preg_replace("/[^0-9.]/", "",$_REQUEST['sell_amount']) : $order_info['btc'];
	$sell_market_price1 = (!empty($_REQUEST['sell_market_price'])) ? $_REQUEST['sell_market_price'] : ($order_info['market_price'] == 'Y');
	$sell_price1 = (!empty($_REQUEST['sell_price'])) ? preg_replace("/[^0-9.]/", "",$_REQUEST['sell_price']) : (($order_info['btc_price'] > 0) ? $order_info['btc_price'] : ($buy_market_price1 ? $current_bid : 0));
	$sell_stop_price1 = (!empty($_REQUEST['sell_stop_price'])) ? preg_replace("/[^0-9.]/", "",$_REQUEST['sell_stop_price']) : $order_info['stop_price'];
	$user_fee_ask = (($sell_price1 <= $bids[0]['btc_price']) || $sell_market_price1) ? $user_fee_both['fee'] : $user_fee_both['fee1'];
	$sell_subtotal1 = $sell_amount1 * (($sell_price1 > 0) ? $sell_price1 : $sell_stop_price1);
	$sell_fee_amount1 = ($user_fee_ask * 0.01) * $sell_subtotal1;
	$sell_total1 = round($sell_subtotal1 - $sell_fee_amount1,2,PHP_ROUND_HALF_UP);
	$pre_btc_available = $user_available['BTC'];
	$user_available['BTC'] += $order_info['btc'];
	$sell_stop = ($sell_stop_price1 > 0);
	$sell_limit = ($sell_price1 > 0 && !$sell_market_price1) ? 1 : !empty($_REQUEST['sell_limit']);
	$old_btc = $order_info['btc'];
}

if ($CFG->trading_status == 'suspended')
	Errors::add(Lang::string('buy-trading-disabled'));

if ($buy && !is_array(Errors::$errors)) {
	$buy_market_price1 = (!empty($_REQUEST['buy_market_price']));
	$buy_stop = (!empty($_REQUEST['buy_stop']));
	$buy_stop_price1 = ($buy_stop) ? preg_replace("/[^0-9.]/", "",$_REQUEST['buy_stop_price']) : false;
	$buy_limit = (!empty($_REQUEST['buy_limit']));
	$buy_limit = (!$buy_stop && !$buy_market_price1) ? 1 : $buy_limit;
	$buy_price1 = ($buy_market_price1) ? $current_ask : $buy_price1;

	API::add('Orders','executeOrder',array(1,(($buy_stop && !$buy_limit) ? $buy_stop_price1 : $buy_price1),$buy_amount1,$currency1,$user_fee_bid,$buy_market_price1,$order_info['id'],false,false,$buy_stop_price1));
	$query = API::send();
	$operations = $query['Orders']['executeOrder']['results'][0];
	
	if (!empty($operations['error'])) {
		Errors::add($operations['error']['message']);
	}
	else if ($operations['edit_order'] > 0) {
	    $uniq_time = time();
	    $_SESSION["editorder_uniq"][$uniq_time] = md5(uniqid(mt_rand(),true));
	    if (count($_SESSION["editorder_uniq"]) > 3) {
	    	unset($_SESSION["editorder_uniq"][min(array_keys($_SESSION["editorder_uniq"]))]);
	    }
	    
		Link::redirect('open-orders.php',array('transactions'=>$operations['transactions'],'edit_order'=>1));
		exit;
	}
	else {
	    $uniq_time = time();
	    $_SESSION["editorder_uniq"][$uniq_time] = md5(uniqid(mt_rand(),true));
	    if (count($_SESSION["editorder_uniq"]) > 3) {
	    	unset($_SESSION["editorder_uniq"][min(array_keys($_SESSION["editorder_uniq"]))]);
	    }
	    
		Link::redirect('transactions.php',array('transactions'=>$operations['transactions']));
		exit;
	}
}

if ($sell && !is_array(Errors::$errors)) {
	$sell_market_price1 = (!empty($_REQUEST['sell_market_price']));
	$sell_stop = (!empty($_REQUEST['sell_stop']));
	$sell_stop_price1 = ($sell_stop) ? preg_replace("/[^0-9.]/", "",$_REQUEST['sell_stop_price']) : false;
	$sell_limit = (!empty($_REQUEST['sell_limit']));
	$sell_limit = (!$sell_stop && !$sell_market_price1) ? 1 : $sell_limit;
	$sell_price1 = ($sell_market_price1) ? $current_bid : $sell_price1;
	
	API::add('Orders','executeOrder',array(0,(($sell_stop && !$sell_limit) ? $sell_stop_price1 : $sell_price1),$sell_amount1,$currency1,$user_fee_ask,$sell_market_price1,$order_info['id'],false,false,$sell_stop_price1));
	$query = API::send();
	$operations = $query['Orders']['executeOrder']['results'][0];
	
	if (!empty($operations['error'])) {
		Errors::add($operations['error']['message']);
	}
	else if ($operations['edit_order'] > 0) {
	    $uniq_time = time();
	    $_SESSION["editorder_uniq"][$uniq_time] = md5(uniqid(mt_rand(),true));
	    if (count($_SESSION["editorder_uniq"]) > 3) {
	    	unset($_SESSION["editorder_uniq"][min(array_keys($_SESSION["editorder_uniq"]))]);
	    }
	    
		Link::redirect('open-orders.php',array('transactions'=>$operations['transactions'],'edit_order'=>1));
		exit;
	}
	else {
	    $uniq_time = time();
	    $_SESSION["editorder_uniq"][$uniq_time] = md5(uniqid(mt_rand(),true));
	    if (count($_SESSION["editorder_uniq"]) > 3) {
	    	unset($_SESSION["editorder_uniq"][min(array_keys($_SESSION["editorder_uniq"]))]);
	    }
	    
		Link::redirect('transactions.php',array('transactions'=>$operations['transactions']));
		exit;
	}
}

$user_available[strtoupper($currency1)] = $pre_fiat_available;
$user_available['BTC'] = $pre_btc_available;

$page_title = Lang::string('edit-order');
if (!$bypass) {
    $uniq_time = time();
    $_SESSION["editorder_uniq"][$uniq_time] = md5(uniqid(mt_rand(),true));
    if (count($_SESSION["editorder_uniq"]) > 3) {
    	unset($_SESSION["editorder_uniq"][min(array_keys($_SESSION["editorder_uniq"]))]);
    }
    
	include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="buy-sell.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<? Errors::display(); ?>
		<div class="testimonials-4">
			<input type="hidden" id="user_fee" value="<?= $user_fee_both['fee'] ?>" />
			<input type="hidden" id="user_fee1" value="<?= $user_fee_both['fee1'] ?>" />
			<input type="hidden" id="is_bid" value="<?= $order_info['is_bid'] ?>" />
			<input type="hidden" id="edit_order" value="1" />
			<input type="hidden" id="pre_btc_available" value="<?= $pre_btc_available ?>" />
			<input type="hidden" id="pre_fiat_available" value="<?= $pre_fiat_available ?>" />
			<div class="one_half last" <?= ($order_info['is_bid']) ? '' : 'style="display:none;"' ?>>
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-btc fa-2x"></i></span>
						<span class="right"><?= $page_title ?></span>
					</h3>
					<div class="clear"></div>
					<form id="buy_form" action="edit-order.php" method="POST">
						<input type="hidden" name="order_id" value="<?= $order_info['id'] ?>" />
						<div class="buyform">
							<div class="spacer"></div>
							<div class="calc dotted">
								<div class="label"><?= str_replace('[currency]','<span class="sell_currency_label">'.$currency_info['currency'].'</span>',Lang::string('buy-fiat-available')) ?></div>
								<div class="value"><span class="buy_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="buy_user_available"><?= number_format($user_available[strtoupper($currency1)],2) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="param">
								<label for="buy_amount"><?= Lang::string('buy-amount') ?></label>
								<input name="buy_amount" id="buy_amount" type="text" value="<?= $buy_amount1 ?>" />
								<div class="qualify">BTC</div>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="buy_currency"><?= Lang::string('buy-with-currency') ?></label>
								<select id="buy_currency" name="currency" disabled="disabled">
								<?
								if ($CFG->currencies) {
									foreach ($CFG->currencies as $key => $currency) {
										if (is_numeric($key) || $currency['currency'] == 'BTC')
											continue;
										
										echo '<option '.(($currency['id'] == $order_info['currency']) ? 'selected="selected"' : '').' value="'.strtolower($currency['currency']).'">'.$currency['currency'].'</option>';
									}
								}	
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="buy_market_price" id="buy_market_price" type="checkbox" value="1" <?= ($buy_market_price1 && !$buy_stop) ? 'checked="checked"' : '' ?> <?= (!$asks) ? 'readonly="readonly"' : '' ?> />
								<label for="buy_market_price"><?= Lang::string('buy-market-price') ?> <a title="<?= Lang::string('buy-market-rates-info') ?>" href=""><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="buy_limit" id="buy_limit" type="checkbox" value="1" <?= ($buy_limit && !$buy_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="buy_limit"><?= Lang::string('buy-limit') ?> <a title="<?= Lang::string('buy-market-rates-info') ?>" href=""><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="buy_stop" id="buy_stop" type="checkbox" value="1" <?= ($buy_stop && !$buy_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="buy_stop"><?= Lang::string('buy-stop') ?> <a title="<?= Lang::string('buy-market-rates-info') ?>" href=""><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div id="buy_price_container" class="param" <?= (!$buy_limit && !$buy_market_price1) ? 'style="display:none;"' : '' ?>>
								<label for="buy_price"><span id="buy_price_limit_label" <?= (!$buy_limit) ? 'style="display:none;"' : '' ?>><?= Lang::string('buy-limit-price') ?></span><span id="buy_price_market_label" <?= ($buy_limit) ? 'style="display:none;"' : '' ?>><?= Lang::string('buy-price') ?></span></label>
								<input name="buy_price" id="buy_price" type="text" value="<?= number_format($buy_price1,2) ?>" <?= ($buy_market_price1) ? 'readonly="readonly"' : '' ?> />
								<div class="qualify"><span class="buy_currency_label"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div id="buy_stop_container" class="param" <?= (!$buy_stop) ? 'style="display:none;"' : '' ?>>
								<label for="buy_stop_price"><?= Lang::string('buy-stop-price') ?></label>
								<input name="buy_stop_price" id="buy_stop_price" type="text" value="<?= number_format($buy_stop_price1,2) ?>" />
								<div class="qualify"><span class="buy_currency_label"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-subtotal') ?></div>
								<div class="value"><span class="buy_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="buy_subtotal"><?= number_format($buy_subtotal1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-fee') ?> <a title="<?= Lang::string('account-view-fee-schedule') ?>" href="fee-schedule.php"><i class="fa fa-question-circle"></i></a></div>
								<div class="value"><span id="buy_user_fee"><?= $user_fee_bid ?></span>%</div>
								<div class="clear"></div>
							</div>
							<div class="calc bigger">
								<div class="label">
									<span id="buy_total_approx_label"><?= str_replace('[currency]','<span class="buy_currency_label">'.$currency_info['currency'].'</span>',Lang::string('buy-total-approx')) ?></span>
									<span id="buy_total_label" style="display:none;"><?= Lang::string('buy-total') ?></span>
								</div>
								<div class="value"><span class="buy_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="buy_total"><?= number_format($buy_total1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="buy" value="1" />
							<input type="hidden" name="uniq" value="<?= $_SESSION["editorder_uniq"][$uniq_time] ?>" />
							<input type="submit" name="submit" value="<?= $page_title ?>" class="but_user" />
						</div>
					</form>
				</div>
			</div>
			<div class="one_half last" <?= (!$order_info['is_bid']) ? '' : 'style="display:none;"' ?>>
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-usd fa-2x"></i></span>
						<span class="right"><?= $page_title ?></span>
					</h3>
					<div class="clear"></div>
					<form id="sell_form" action="edit-order.php" method="POST">
						<input type="hidden" name="order_id" value="<?= $order_info['id'] ?>" />
						<div class="buyform">
							<div class="spacer"></div>
							<div class="calc dotted">
								<div class="label"><?= Lang::string('sell-btc-available') ?></div>
								<div class="value"><span id="sell_user_available"><?= number_format($user_available['BTC'],8) ?></span> BTC</div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="param">
								<label for="sell_amount"><?= Lang::string('sell-amount') ?></label>
								<input name="sell_amount" id="sell_amount" type="text" value="<?= $sell_amount1 ?>" />
								<div class="qualify">BTC</div>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="sell_currency"><?= Lang::string('buy-with-currency') ?></label>
								<select id="sell_currency" name="currency" disabled="disabled">
								<?
								if ($CFG->currencies) {
									foreach ($CFG->currencies as $key => $currency) {
										if (is_numeric($key) || $currency['currency'] == 'BTC')
											continue;
										
										echo '<option '.(($currency['id'] == $order_info['currency']) ? 'selected="selected"' : '').' value="'.strtolower($currency['currency']).'">'.$currency['currency'].'</option>';
									}
								}	
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="sell_market_price" id="sell_market_price" type="checkbox" value="1" <?= ($sell_market_price1 && !$sell_stop) ? 'checked="checked"' : '' ?> <?= (!$bids) ? 'readonly="readonly"' : '' ?> />
								<label for="sell_market_price"><?= Lang::string('sell-market-price') ?> <a title="<?= Lang::string('buy-market-rates-info') ?>" href=""><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="sell_limit" id="sell_limit" type="checkbox" value="1" <?= ($sell_limit && !$sell_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="sell_stop"><?= Lang::string('buy-limit') ?> <a title="<?= Lang::string('buy-market-rates-info') ?>" href=""><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="sell_stop" id="sell_stop" type="checkbox" value="1" <?= ($sell_stop && !$sell_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="sell_stop"><?= Lang::string('buy-stop') ?> <a title="<?= Lang::string('buy-market-rates-info') ?>" href=""><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div id="sell_price_container" class="param" <?= (!$sell_limit && !$sell_market_price1) ? 'style="display:none;"' : '' ?>>
								<label for="sell_price"><span id="sell_price_limit_label" <?= (!$sell_limit) ? 'style="display:none;"' : '' ?>><?= Lang::string('buy-limit-price') ?></span><span id="sell_price_market_label" <?= ($sell_limit) ? 'style="display:none;"' : '' ?>><?= Lang::string('buy-price') ?></span></label>
								<input name="sell_price" id="sell_price" type="text" value="<?= number_format($sell_price1,2) ?>" <?= ($sell_market_price1) ? 'readonly="readonly"' : '' ?> />
								<div class="qualify"><span class="sell_currency_label"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div id="sell_stop_container" class="param" <?= (!$sell_stop) ? 'style="display:none;"' : '' ?>>
								<label for="sell_stop_price"><?= Lang::string('buy-stop-price') ?></label>
								<input name="sell_stop_price" id="sell_stop_price" type="text" value="<?= number_format($sell_stop_price1,2) ?>" />
								<div class="qualify"><span class="sell_currency_label"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-subtotal') ?></div>
								<div class="value"><span class="sell_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="sell_subtotal"><?= number_format($sell_subtotal1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-fee') ?> <a title="<?= Lang::string('account-view-fee-schedule') ?>" href="fee-schedule.php"><i class="fa fa-question-circle"></i></a></div>
								<div class="value"><span id="sell_user_fee"><?= $user_fee_ask ?></span>%</div>
								<div class="clear"></div>
							</div>
							<div class="calc bigger">
								<div class="label">
									<span id="sell_total_approx_label"><?= str_replace('[currency]','<span class="sell_currency_label">'.$currency_info['currency'].'</span>',Lang::string('sell-total-approx')) ?></span>
									<span id="sell_total_label" style="display:none;"><?= str_replace('[currency]','<span class="sell_currency_label">'.$currency_info['currency'].'</span>',Lang::string('sell-total')) ?></span>
								</div>
								<div class="value"><span class="sell_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="sell_total"><?= number_format($sell_total1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="sell" value="1" />
							<input type="hidden" name="uniq" value="<?= $_SESSION["editorder_uniq"][$uniq_time] ?>" />
							<input type="submit" name="submit" value="<?= $page_title ?>" class="but_user" />
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="mar_top3"></div>
		<div class="clear"></div>
		<div id="filters_area">
<? } ?>
			<div class="one_half">
				<h3><?= Lang::string('orders-bid-top-10') ?></h3>
	        	<div class="table-style">
	        		<table class="table-list trades" id="bids_list">
	        			<tr>
	        				<th><?= Lang::string('orders-price') ?></th>
	        				<th><?= Lang::string('orders-amount') ?></th>
	        				<th><?= Lang::string('orders-value') ?></th>
	        			</tr>
	        			<? 
						if ($bids) {
							foreach ($bids as $bid) {
								$mine = (!empty(User::$info['user']) && $bid['user_id'] == User::$info['user'] && $bid['btc_price'] == $bid['fiat_price']) ? '<a class="fa fa-user" href="open-orders.php?id='.$bid['id'].'" title="'.Lang::string('home-your-order').'"></a>' : '';
								echo '
						<tr id="bid_'.$bid['id'].'" class="bid_tr">
							<td>'.$mine.$currency_info['fa_symbol'].'<span class="order_price">'.number_format($bid['btc_price'],2).'</span> '.(($bid['btc_price'] != $bid['fiat_price']) ? '<a title="'.str_replace('[currency]',$CFG->currencies[$bid['currency']]['currency'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
							<td><span class="order_amount">'.number_format($bid['btc'],8).'</span></td>
							<td>'.$currency_info['fa_symbol'].'<span class="order_value">'.number_format(($bid['btc_price'] * $bid['btc']),2).'</span></td>
						</tr>';
							}
						}
						echo '<tr id="no_bids" style="'.(is_array($bids) ? 'display:none;' : '').'"><td colspan="4">'.Lang::string('orders-no-bid').'</td></tr>';
	        			?>
	        		</table>
				</div>
			</div>
			<div class="one_half last">
				<h3><?= Lang::string('orders-ask-top-10') ?></h3>
				<div class="table-style">
					<table class="table-list trades" id="asks_list">
						<tr>
							<th><?= Lang::string('orders-price') ?></th>
	        				<th><?= Lang::string('orders-amount') ?></th>
	        				<th><?= Lang::string('orders-value') ?></th>
						</tr>
	        			<? 
						if ($asks) {
							foreach ($asks as $ask) {
								$mine = (!empty(User::$info['user']) && $ask['user_id'] == User::$info['user'] && $ask['btc_price'] == $ask['fiat_price']) ? '<a class="fa fa-user" href="open-orders.php?id='.$ask['id'].'" title="'.Lang::string('home-your-order').'"></a>' : '';
								echo '
						<tr id="ask_'.$ask['id'].'" class="ask_tr">
							<td>'.$mine.$currency_info['fa_symbol'].'<span class="order_price">'.number_format($ask['btc_price'],2).'</span> '.(($ask['btc_price'] != $ask['fiat_price']) ? '<a title="'.str_replace('[currency]',$CFG->currencies[$ask['currency']]['currency'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
							<td><span class="order_amount">'.number_format($ask['btc'],8).'</span></td>
							<td>'.$currency_info['fa_symbol'].'<span class="order_value">'.number_format(($ask['btc_price'] * $ask['btc']),2).'</span></td>
						</tr>';
							}
						}
						echo '<tr id="no_asks" style="'.(is_array($asks) ? 'display:none;' : '').'"><td colspan="4">'.Lang::string('orders-no-ask').'</td></tr>';
	        			?>
					</table>
				</div>
				<div class="clear"></div>
			</div>
<? if (!$bypass) { ?>
		</div>
		<div class="mar_top5"></div>
	</div>
</div>
<? include 'includes/foot.php'; ?>
<? } ?>