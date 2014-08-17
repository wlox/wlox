<?php

include '../cfg/cfg.php';

$page_title = Lang::string('order-book');

if ($_REQUEST['currency'])
	$_SESSION['currency'] = ereg_replace("[^a-z]", "",$_REQUEST['currency']);
elseif (!$_SESSION['currency'])
	$_SESSION['currency'] = (User::$info['default_currency_abbr']) ? strtolower(User::$info['default_currency_abbr']) : 'usd';
	
$currency1 = $_SESSION['currency'];
$currency_symbol = strtoupper($currency1);
$currency_info = $CFG->currencies[$currency_symbol];

API::add('Orders','get',array(false,false,false,$currency1,false,false,1,false,false,1));
API::add('Orders','get',array(false,false,false,$currency1,false,false,false,false,1,1));
$query = API::send();

$bids = $query['Orders']['get']['results'][0];
$asks = $query['Orders']['get']['results'][1];

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="order-book.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<div class="content_fullwidth">
		<div class="graph_contain">
			<input type="hidden" id="graph_orders_currency" value="<?= $currency1 ?>" />
			<div id="graph_orders"></div>
			<div id="tooltip">
				<div class="price"></div>
				<div class="bid"><?= Lang::string('orders-bid') ?> <span></span> BTC</div>
				<div class="ask"><?= Lang::string('orders-ask') ?> <span></span> BTC</div>
			</div>
		</div>
		<div class="mar_top3"></div>
		<div class="filters">
			<form method="GET" action="order-book.php">
				<ul class="list_empty">
					<li>
						<label for="ob_currency"><?= Lang::string('currency') ?></label>
						<select id="ob_currency" name="currency">
							<? 
							if ($CFG->currencies) {
								foreach ($CFG->currencies as $currency) {
									echo '<option '.((strtolower($currency['currency']) == $currency1) ? 'selected="selected"' : '').' value="'.strtolower($currency['currency']).'">'.$currency['currency'].'</option>';
								}
							}
							?>
						</select>
					</li>
				</ul>
			</form>
		</div>
		<div class="one_half">
			<h3><?= Lang::string('orders-bid') ?></h3>
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
							$mine = ($bid['mine']) ? '<a class="fa fa-user" href="javascript:return false;" title="'.Lang::string('home-your-order').'"></a>' : '';
							echo '
					<tr id="bid_'.$bid['id'].'" class="bid_tr">
						<td>'.$mine.$currency_info['fa_symbol'].'<span class="order_price">'.number_format($bid['btc_price'],2).'</span> '.(($bid['btc_price'] != $bid['fiat_price']) ? '<a title="'.str_replace('[currency]',$bid['currency_abbr'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
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
			<h3><?= Lang::string('orders-ask') ?></h3>
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
							$mine = ($ask['mine']) ? '<a class="fa fa-user" href="javascript:return false;" title="'.Lang::string('home-your-order').'"></a>' : '';
							echo '
					<tr id="ask_'.$ask['id'].'" class="ask_tr">
						<td>'.$mine.$currency_info['fa_symbol'].'<span class="order_price">'.number_format($ask['btc_price'],2).'</span> '.(($ask['btc_price'] != $ask['fiat_price']) ? '<a title="'.str_replace('[currency]',$ask['currency_abbr'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
						<td><span class="order_amount">'.number_format($ask['btc'],8).'</span></td>
						<td>'.$currency_info['fa_symbol'].'<span class="order_value">'.number_format(($ask['btc_price'] * $ask['btc']),2).'</span></td>
					</tr>';
						}
					}
					echo '<tr id="no_asks" style="'.(is_array($asks) ? 'display:none;' : '').'"><td colspan="4">'.Lang::string('orders-no-ask').'</td></tr>';
        			?>
				</table>
			</div>
		</div>
		<div class="mar_top5"></div>
	</div>
</div>
<? include 'includes/foot.php'; ?>