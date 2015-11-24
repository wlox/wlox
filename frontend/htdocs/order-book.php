<?php

include '../lib/common.php';

$page_title = Lang::string('order-book');

if (empty($_REQUEST['currency']) && empty($_SESSION['currency']) && !empty(User::$info['default_currency_abbr']))
	$_SESSION['currency'] = User::$info['default_currency_abbr'];
elseif (empty($_REQUEST['currency']) && empty($_SESSION['currency']) && empty(User::$info['default_currency_abbr']))
	$_SESSION['currency'] = 'usd';
elseif (!empty($_REQUEST['currency']))
	$_SESSION['currency'] = preg_replace("/[^a-z]/", "",$_REQUEST['currency']);
	
$currency1 = (!empty($CFG->currencies[strtoupper($_SESSION['currency'])])) ? strtolower($_SESSION['currency']) : 'usd';
$currency_symbol = strtoupper($currency1);
$currency_info = $CFG->currencies[$currency_symbol];

API::add('Orders','get',array(false,false,false,$currency1,false,false,1));
API::add('Orders','get',array(false,false,false,$currency1,false,false,false,false,1));
API::add('Transactions','get',array(false,false,1,$currency1));
$query = API::send();

$bids = $query['Orders']['get']['results'][0];
$asks = $query['Orders']['get']['results'][1];
$last_transaction = $query['Transactions']['get']['results'][0][0];
$last_trans_currency = ($last_transaction['currency'] == $currency_info['id']) ? false : (($last_transaction['currency1'] == $currency_info['id']) ? false : ' ('.$CFG->currencies[$last_transaction['currency1']]['currency'].')');
$last_trans_symbol = $currency_info['fa_symbol'];
$last_trans_color = ($last_transaction['maker_type'] == 'sell') ? 'price-green' : 'price-red';

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
		<div class="clear"></div>
		<div class="filters">
			<form method="GET" action="order-book.php">
				<ul class="list_empty">
					<li>
						<label for="ob_currency"><?= Lang::string('currency') ?></label>
						<select id="ob_currency" name="currency">
							<? 
							if ($CFG->currencies) {
								foreach ($CFG->currencies as $key => $currency) {
									if (is_numeric($key) || $currency['currency'] == 'BTC')
										continue;
									
									echo '<option '.((strtolower($currency['currency']) == $currency1) ? 'selected="selected"' : '').' value="'.strtolower($currency['currency']).'">'.$currency['currency'].'</option>';
								}
							}
							?>
						</select>
					</li>
					<li>
						<label for="last_price"><?= Lang::string('home-stats-last-price') ?></label>
						<input type="text" id="last_price" class="<?= $last_trans_color ?>" value="<?= $last_trans_symbol.number_format($last_transaction['btc_price'],2).$last_trans_currency ?>" disabled="disabled" />
						<a target="_blank" href="https://support.1btcxe.com/support/solutions/articles/1000146628" title="<?= Lang::string('order-book-last-price-explain') ?>"><i class="fa fa-question-circle"></i></a>
					</li>
				</ul>
			</form>
			<div class="clear"></div>
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
        				$i = 0;
						foreach ($bids as $bid) {
							$min_bid = (empty($min_bid) || $bid['btc_price'] < $min_bid) ? $bid['btc_price'] : $min_bid;
							$max_bid = (empty($max_bid) || $bid['btc_price'] > $max_bid) ? $bid['btc_price'] : $max_bid;
							$mine = (!empty(User::$info['user']) && $bid['user_id'] == User::$info['user'] && $bid['btc_price'] == $bid['fiat_price']) ? '<a class="fa fa-user" href="open-orders.php?id='.$bid['id'].'" title="'.Lang::string('home-your-order').'"></a>' : '';
							
							echo '
					<tr id="bid_'.$bid['id'].'" class="bid_tr">
						<td>'.$mine.$currency_info['fa_symbol'].'<span class="order_price">'.number_format($bid['btc_price'],2).'</span> '.(($bid['btc_price'] != $bid['fiat_price']) ? '<a title="'.str_replace('[currency]',$CFG->currencies[$bid['currency']]['currency'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
						<td><span class="order_amount">'.number_format($bid['btc'],8).'</span></td>
						<td>'.$currency_info['fa_symbol'].'<span class="order_value">'.number_format(($bid['btc_price'] * $bid['btc']),2).'</span></td>
					</tr>';
							$i++;
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
        				$i = 0;
						foreach ($asks as $ask) {
							$min_ask = (empty($min_ask) || $ask['btc_price'] < $min_ask) ? $ask['btc_price'] : $min_ask;
							$max_ask = (empty($max_ask) || $ask['btc_price'] > $max_ask) ? $ask['btc_price'] : $max_ask;
							$mine = (!empty(User::$info['user']) && $ask['user_id'] == User::$info['user'] && $ask['btc_price'] == $ask['fiat_price']) ? '<a class="fa fa-user" href="open-orders.php?id='.$ask['id'].'" title="'.Lang::string('home-your-order').'"></a>' : '';
							
							echo '
					<tr id="ask_'.$ask['id'].'" class="ask_tr">
						<td>'.$mine.$currency_info['fa_symbol'].'<span class="order_price">'.number_format($ask['btc_price'],2).'</span> '.(($ask['btc_price'] != $ask['fiat_price']) ? '<a title="'.str_replace('[currency]',$CFG->currencies[$ask['currency']]['currency'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
						<td><span class="order_amount">'.number_format($ask['btc'],8).'</span></td>
						<td>'.$currency_info['fa_symbol'].'<span class="order_value">'.number_format(($ask['btc_price'] * $ask['btc']),2).'</span></td>
					</tr>';
							$i++;
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
<script type="text/javascript">
	<?php 
	$bid_range = $max_bid - $min_bid;
	$ask_range = $max_ask - $min_ask;
	$c_bids = count($bids);
	$c_asks = count($asks);
	$lower_range = ($bid_range < $ask_range) ? $bid_range : $ask_range;
	$vars = array('bids'=>array(),'asks'=>array());
	
	if ($bids) {
		$cum_btc = 0;
		foreach ($bids as $bid) {
			if ($max_bid && $c_asks > 1 && (($max_bid - $bid['btc_price']) >  $lower_range))
				continue;
				
			$cum_btc += $bid['btc'];
			$vars['bids'][] = array($bid['btc_price'],$cum_btc);
		}
	
		if ($max_bid && $c_asks > 1)
			$vars['bids'][] = array(($max_bid - $lower_range),$cum_btc);
	
	}
	if ($asks) {
		$cum_btc = 0;
		foreach ($asks as $ask) {
			if ($min_ask && $c_bids > 1 && (($ask['btc_price'] - $min_ask) >  $lower_range))
				continue;
				
			$cum_btc += $ask['btc'];
			$vars['asks'][] = array($ask['btc_price'],$cum_btc);
		}
	
		if ($min_ask && $c_bids > 1)
			$vars['asks'][] = array(($min_ask + $lower_range),$cum_btc);
	}
	echo 'var static_data = '.json_encode($vars).';';
	?>
</script>
<? include 'includes/foot.php'; ?>