<?php
include '../cfg/cfg.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$currency1 = ereg_replace("[^a-z]", "",$_REQUEST['currency']);
$order_by1 = ereg_replace("[^a-z]", "",$_REQUEST['order_by']);
$order_desc1 = ereg_replace("[^0-9]", "",$_REQUEST['order_desc']);
$start_date1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['startdate']);
$type1 = ereg_replace("[^0-9]", "",$_REQUEST['type']);
$page1 = ereg_replace("[^0-9]", "",$_REQUEST['page']);
$trans_realized1 = ereg_replace("[^0-9]", "",$_REQUEST['transactions']);
$bypass = $_REQUEST['bypass'];

API::add('Transactions','get',array(1,$page1,30,$currency1,1,$start_date1,$type1,$order_by1,$order_desc1));
$query = API::send();
$total = $query['Transactions']['get']['results'][0];

API::add('Transactions','get',array(false,$page1,30,$currency1,1,$start_date1,$type1,$order_by1,$order_desc1));
API::add('Transactions','pagination',array('transactions.php',$page1,$total,30,5,$CFG->pagination_label));
API::add('Transactions','getTypes');
$query = API::send();

$transactions = $query['Transactions']['get']['results'][0];
$pagination = $query['Transactions']['pagination']['results'][0];
$transaction_types = $query['Transactions']['getTypes']['results'][0];

$currency_info = $CFG->currencies[strtoupper($currency1)];

if ($trans_realized1 > 0)
	Messages::add(str_replace('[transactions]',$trans_realized1,Lang::string('transactions-done-message')));

$page_title = Lang::string('transactions');

if (!$bypass) {
	include 'includes/head.php';
	
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="transactions.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<? Messages::display(); ?>
		<div class="filters">
			<input type="hidden" id="transactions_user" value="1" />
			<form id="filters" method="GET" action="transactions.php">
				<ul class="list_empty">
					<li>
						<label for="graph_orders_currency"><?= Lang::string('orders-filter-currency') ?></label>
						<select id="graph_orders_currency" name="currency">
							<option value=""><?= Lang::string('transactions-any') ?></option>
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
							<option value="date" <?= (!$order_by1 || $order_by1 == 'date') ? 'selected="selected"' : ''?>><?= Lang::string('transactions-time') ?></option>
							<option value="btcprice" <?= ($order_by1 == 'btcprice') ? 'selected="selected"' : ''?>><?= Lang::string('orders-order-by-btc-price') ?></option>
							<option value="fiat" <?= ($order_by1 == 'fiat') ? 'selected="selected"' : ''?>><?= Lang::string('transactions-fiat') ?></option>
						</select>
					</li>
					<li>
						<label for="type"><?= Lang::string('transactions-type') ?></label>
						<select id="type" name="type">
							<option value=""><?= Lang::string('transactions-any') ?></option>
							<?
							if ($transaction_types) {
								foreach ($transaction_types as $type) {
									echo '<option '.((strtolower($type['id']) == $type1) ? 'selected="selected"' : '').' value="'.$type['id'].'">'.$type['name_'.$CFG->language].'</option>';
								}
							}
							?>
						</select>
					</li>
					<li>
						<a class="download" href="transactions_download.php"><i class="fa fa-download"></i> <?= Lang::string('transactions-download') ?></a>
					</li>
				</ul>
			</form>
		</div>
		<div class="clear"></div>
		<div id="filters_area">
<? } ?>
        	<div class="table-style">
        		<input type="hidden" id="refresh_transactions" value="1" />
        		<input type="hidden" id="page" value="<?= $page1 ?>" />
        		<table class="table-list trades" id="transactions_list">
        			<tr id="table_first">
        				<th><?= Lang::string('transactions-type') ?></th>
        				<th><?= Lang::string('transactions-time') ?></th>
        				<th><?= Lang::string('transactions-btc') ?></th>
        				<th><?= Lang::string('transactions-fiat') ?></th>
        				<th><?= Lang::string('transactions-price') ?></th>
        				<th><?= Lang::string('transactions-fee') ?></th>
        			</tr>
        			<? 
        			if ($transactions) {
						foreach ($transactions as $transaction) {
							echo '
					<tr id="transaction_'.$transaction['id'].'">
						<td>'.$transaction['type'].'</td>
						<td><input type="hidden" class="localdate" value="'.(strtotime($transaction['date'])/* + $CFG->timezone_offset*/).'" /></td>
						<td>'.number_format($transaction['btc'],8).'</td>
						<td>'.$transaction['fa_symbol'].number_format($transaction['btc_net'] * $transaction['fiat_price'],2).'</td>
						<td>'.$transaction['fa_symbol'].number_format($transaction['fiat_price'],2).'</td>
						<td>'.$transaction['fa_symbol'].number_format($transaction['fee'] * $transaction['fiat_price'],2).'</td>
					</tr>';
						}
					}
					echo '<tr id="no_transactions" style="'.(is_array($transactions) ? 'display:none;' : '').'"><td colspan="6">'.Lang::string('transactions-no').'</td></tr>';
        			?>
        		</table>
        		<?= $pagination ?>
			</div>
			<div class="clear"></div>
		</div>
<? if (!$bypass) { ?>
		<div class="mar_top5"></div>
	</div>
</div>
<? include 'includes/foot.php'; ?>
<? } ?>