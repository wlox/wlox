<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$delete_id1 = (!empty($_REQUEST['delete_id'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['delete_id']) : false;
if ($delete_id1 > 0 && $_SESSION["openorders_uniq"] == $_REQUEST['uniq']) {
	API::add('Orders','getRecord',array($delete_id1));
	$query = API::send();
	$del_order = $query['Orders']['getRecord']['results'][0];

	if (!$del_order) {
		Link::redirect('open-orders.php?message=order-doesnt-exist');
	}
	elseif ($del_order['site_user'] != $del_order['user_id'] || !($del_order['id'] > 0)) {
		Link::redirect('open-orders.php?message=not-your-order');
	}
	else {
		API::add('Orders','delete',array($delete_id1));
		$query = API::send();
		
		Link::redirect('open-orders.php?message=order-cancelled');
	}
}

$currency1 = (!empty($_REQUEST['currency']) && array_key_exists(strtoupper($_REQUEST['currency']),$CFG->currencies)) ? $_REQUEST['currency'] : false;
$order_by1 = (!empty($_REQUEST['order_by'])) ? preg_replace("/[^a-z]/", "",$_REQUEST['order_by']) : false;
$trans_realized1 = (!empty($_REQUEST['transactions'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['transactions']) : false;
$id1 = (!empty($_REQUEST['id'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['id']) : false;
$bypass = (!empty($_REQUEST['bypass']));

API::add('Orders','get',array(false,false,false,$currency1,1,false,1,$order_by1,false,1));
API::add('Orders','get',array(false,false,false,$currency1,1,false,false,$order_by1,1,1));
$query = API::send();

$bids = $query['Orders']['get']['results'][0];
$asks = $query['Orders']['get']['results'][1];
$currency_info = ($currency1) ? $CFG->currencies[strtoupper($currency1)] : false;

if (!empty($_REQUEST['new_order']) && !$trans_realized1)
	Messages::add(Lang::string('transactions-orders-new-message'));
if (!empty($_REQUEST['edit_order']) && !$trans_realized1)
	Messages::add(Lang::string('transactions-orders-edit-message'));
elseif (!empty($_REQUEST['new_order']) && $trans_realized1 > 0)
	Messages::add(str_replace('[transactions]',$trans_realized1,Lang::string('transactions-orders-done-message')));
elseif (!empty($_REQUEST['edit_order']) && $trans_realized1 > 0)
	Messages::add(str_replace('[transactions]',$trans_realized1,Lang::string('transactions-orders-done-edit-message')));
elseif (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'order-doesnt-exist')
	Errors::add(Lang::string('orders-order-doesnt-exist'));
elseif (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'not-your-order')
	Errors::add(Lang::string('orders-not-yours'));
elseif (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'order-cancelled')
	Messages::add(Lang::string('orders-order-cancelled'));

$page_title = Lang::string('open-orders');
$_SESSION["openorders_uniq"] = md5(uniqid(mt_rand(),true));

if (!$bypass) {
	include 'includes/head.php';
	
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="open-orders.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<? Messages::display(); ?>
		<? Errors::display(); ?>
		<div class="filters">
			<input type="hidden" id="open_orders_user" value="1" />
			<input type="hidden" id="uniq" value="<?= $_SESSION["openorders_uniq"] ?>" />
			<form id="filters" method="GET" action="open-orders.php">
				<ul class="list_empty">
					<li>
						<label for="graph_orders_currency"><?= Lang::string('orders-filter-currency') ?></label>
						<select id="graph_orders_currency" name="currency">
							<option><?= Lang::string('all-currencies') ?></option>
							<? 
							if ($CFG->currencies) {
								foreach ($CFG->currencies as $currency) {
									echo '<option '.((strtolower($currency['currency']) == $currency1) ? 'selected="selected"' : '').' value="'.strtolower($currency['currency']).'">'.$currency['currency'].'</option>';
								}
							}
							?>
						</select>
					</li>
					<li>
						<label for="order_by"><?= Lang::string('orders-order-by') ?></label>
						<select id="order_by" name="order_by">
							<option value="btcprice"><?= Lang::string('orders-order-by-btc-price') ?></option>
							<option value="date"><?= Lang::string('orders-order-by-date') ?></option>
							<option value="fiat"><?= Lang::string('orders-order-by-fiat') ?></option>
						</select>
					</li>
				</ul>
			</form>
		</div>
		<div class="clear"></div>
		<div id="filters_area">
<? } ?>
			<div class="one_half">
				<h3><?= Lang::string('orders-bid') ?></h3>
	        	<div class="table-style">
	        		<table class="table-list trades" id="bids_list">
	        			<tr>
	        				<th></th>
	        				<th><?= Lang::string('orders-price') ?></th>
	        				<th><?= Lang::string('orders-amount') ?></th>
	        				<th><?= Lang::string('orders-value') ?></th>
	        				<th></th>
	        			</tr>
	        			<? 
	        			if ($bids) {
							foreach ($bids as $bid) {
								$blink = ($bid['id'] == $id1) ? 'blink' : '';
								$double = 0;
								if ($bid['market_price'] == 'Y')
									$type = '<div class="identify market_order">M</div>';
								elseif ($bid['fiat_price'] > 0 && !($bid['stop_price'] > 0))
									$type = '<div class="identify limit_order">L</div>';
								elseif ($bid['stop_price'] > 0 && !($bid['fiat_price'] > 0))
									$type = '<div class="identify stop_order">S</div>';
								elseif ($bid['stop_price'] > 0 && $bid['fiat_price'] > 0) {
									$type = '<div class="identify limit_order">L</div>';
									$double = 1;
								}
								
								echo '
						<tr id="bid_'.$bid['id'].'" class="bid_tr '.$blink.'">
							<input type="hidden" class="usd_price" value="'.number_format($bid['usd_price'],2).'" />
							<td>'.$type.'</td>
							<td>'.$bid['fa_symbol'].'<span class="order_price">'.number_format(($bid['fiat_price'] > 0) ? $bid['fiat_price'] : $bid['stop_price'],2).'</span></td>
							<td><span class="order_amount">'.number_format($bid['btc'],8).'</span></td>
							<td>'.$bid['fa_symbol'].'<span class="order_value">'.number_format($bid['fiat'],2).'</span></td>
							<td><a href="edit-order.php?order_id='.$bid['id'].'" title="'.Lang::string('orders-edit').'"><i class="fa fa-pencil"></i></a> <a href="open-orders.php?delete_id='.$bid['id'].'&uniq='.$_SESSION["openorders_uniq"].'" title="'.Lang::string('orders-delete').'"><i class="fa fa-times"></i></a></td>
						</tr>';
								if ($double) {
									echo '
						<tr id="bid_'.$bid['id'].'" class="bid_tr double">
							<td><div class="identify stop_order">S</div></td>
							<td>'.$bid['fa_symbol'].'<span class="order_price">'.number_format($bid['stop_price'],2).'</span></td>
							<td><span class="order_amount">'.number_format($bid['btc'],8).'</span></td>
							<td>'.$bid['fa_symbol'].'<span class="order_value">'.number_format($bid['btc']*$bid['stop_price'],2).'</span></td>
							<td><span class="oco"><i class="fa fa-arrow-up"></i> OCO</span></td>
						</tr>';
								}
							}
						}
						echo '<tr id="no_bids" style="'.(is_array($bids) ? 'display:none;' : '').'"><td colspan="5">'.Lang::string('orders-no-bid').'</td></tr>';
	        			?>
	        		</table>
				</div>
			</div>
			<div class="one_half last">
				<h3><?= Lang::string('orders-ask') ?></h3>
				<div class="table-style">
					<table class="table-list trades" id="asks_list">
						<tr>
							<th></th>
							<th><?= Lang::string('orders-price') ?></th>
	        				<th><?= Lang::string('orders-amount') ?></th>
	        				<th><?= Lang::string('orders-value') ?></th>
	        				<th></th>
						</tr>
	        			<? 
	        			if ($asks) {
							foreach ($asks as $ask) {
								$blink = ($ask['id'] == $id1) ? 'blink' : '';
								$double = 0;
								if ($ask['market_price'] == 'Y')
									$type = '<div class="identify market_order">M</div>';
								elseif ($ask['fiat_price'] > 0 && !($ask['stop_price'] > 0))
									$type = '<div class="identify limit_order">L</div>';
								elseif ($ask['stop_price'] > 0 && !($ask['fiat_price'] > 0))
									$type = '<div class="identify stop_order">S</div>';
								elseif ($ask['stop_price'] > 0 && $ask['fiat_price'] > 0) {
									$type = '<div class="identify limit_order">L</div>';
									$double = 1;
								}
								
								echo '
						<tr id="ask_'.$ask['id'].'" class="ask_tr '.$blink.'">
							<input type="hidden" class="usd_price" value="'.number_format($ask['usd_price'],2).'" />
							<td>'.$type.'</td>
							<td>'.$ask['fa_symbol'].'<span class="order_price">'.number_format(($ask['fiat_price'] > 0) ? $ask['fiat_price'] : $ask['stop_price'],2).'</span></td>
							<td><span class="order_amount">'.number_format($ask['btc'],8).'</span></td>
							<td>'.$ask['fa_symbol'].'<span class="order_value">'.number_format($ask['fiat'],2).'</span></td>
							<td><a href="edit-order.php?order_id='.$ask['id'].'" title="'.Lang::string('orders-edit').'"><i class="fa fa-pencil"></i></a> <a href="open-orders.php?delete_id='.$ask['id'].'&uniq='.$_SESSION["openorders_uniq"].'" title="'.Lang::string('orders-delete').'"><i class="fa fa-times"></i></a></td>
						</tr>';
								
								if ($double) {
									echo '
						<tr id="ask_'.$ask['id'].'" class="ask_tr double">
							<td><div class="identify stop_order">S</div></td>
							<td>'.$ask['fa_symbol'].'<span class="order_price">'.number_format($ask['stop_price'],2).'</span></td>
							<td><span class="order_amount">'.number_format($ask['btc'],8).'</span></td>
							<td>'.$ask['fa_symbol'].'<span class="order_value">'.number_format($ask['stop_price']*$ask['btc'],2).'</span></td>
							<td><span class="oco"><i class="fa fa-arrow-up"></i> OCO</span></td>
						</tr>';
								}
							}
						}
						echo '<tr id="no_asks" style="'.(is_array($asks) ? 'display:none;' : '').'"><td colspan="5">'.Lang::string('orders-no-ask').'</td></tr>';
	        			?>
					</table>
				</div>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
		</div>
<? if (!$bypass) { ?>
		<div class="mar_top5"></div>
	</div>
</div>
<? include 'includes/foot.php'; ?>
<? } ?>