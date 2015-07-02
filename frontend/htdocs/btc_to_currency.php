<?php
include '../lib/common.php';

$_SESSION['currency'] = preg_replace("/[^a-z]/", "",$_REQUEST['currency']);

$currency1 = $_SESSION['currency'];
$currency_symbol = strtoupper($currency1);

$currency_info = $CFG->currencies[$currency_symbol];

API::add('Stats','getCurrent',array($currency_info['id']));
API::add('Transactions','get',array(false,false,5,$currency1));
API::add('Orders','get',array(false,false,5,$currency1,false,false,1));
API::add('Orders','get',array(false,false,5,$currency1,false,false,false,false,1));
API::add('Currencies','getRecord',array('BTC'));
$query = API::send();

$stats = $query['Stats']['getCurrent']['results'][0];
$transactions = $query['Transactions']['get']['results'][0];
$bids = $query['Orders']['get']['results'][0];
$asks = $query['Orders']['get']['results'][1];
$btc_info = $query['Currencies']['getRecord']['results'][0];
$currencies = $CFG->currencies;
$page_title = strip_tags(str_replace('[currency]',$currency_symbol,Lang::string('home-landing-currency')));
$meta_desc = String::substring(strip_tags(str_replace('[currency]','<strong>'.$currency_symbol.'</strong>',Lang::string('home-landing-currency-explain'))),300);

include 'includes/head.php';

if ($stats['daily_change'] > 0) 
	$arrow = '<i id="up_or_down" class="fa fa-caret-up" style="color:#60FF51;"></i> ';
elseif ($stats['daily_change'] < 0)
	$arrow = '<i id="up_or_down" class="fa fa-caret-down" style="color:#FF5151;"></i> ';
else
	$arrow = '<i id="up_or_down" class="fa fa-minus"></i> ';
?>
<div class="fresh_projects global_stats">
	<div class="clearfix mar_top6"></div>
	<div class="container">
    	<h1 style="margin-bottom:5px;"><?= str_replace('BTC',$btc_info['name_'.$CFG->language],str_replace('[currency]','<strong>'.$currency_info['name_'.$CFG->language].'</strong>',Lang::string('home-landing-currency'))) ?></h1>
        <p class="explain"><?= str_replace('[currency]','<strong>'.$currency_symbol.'</strong>',Lang::string('home-landing-currency-explain')) ?></p>
         <div class="mar_top3"></div>
        <a class="but_user" href="<?= Lang::url('register.php') ?>"><i class="fa fa-user"></i> <?= str_replace('[currency]','<strong>'.$currency_symbol.'</strong>',Lang::string('home-landing-sign-up')) ?></a>
        <div class="mar_top3"></div>
        <div class="one_fifth">
        	<h6><?= Lang::string('home-stats-last-price') ?></h6>
        	<p class="stat1"><?= $currency_info['fa_symbol'].'<span id="stats_last_price">'.number_format($stats['last_price'],2).'</span>'?></p>
        </div>
        <div class="one_fifth">
        	<h6><?= Lang::string('home-stats-daily-change') ?></h6>
        	<p class="stat1"><?= $arrow.'<span id="stats_daily_change_abs">'.number_format(abs($stats['daily_change']),2).'</span>' ?> <small><?= '<span id="stats_daily_change_perc">'.number_format(abs($stats['daily_change_percent']),2).'</span>%'?></small></p>
        </div>
        <div class="one_fifth">
        	<h6><?= Lang::string('home-stats-days-range') ?></h6>
        	<p class="stat1"><?= $currency_info['fa_symbol'].'<span id="stats_min">'.number_format($stats['min'],2).'</span> - <span id="stats_max">'.number_format($stats['max'],2).'</span>' ?></p>
        </div>
        <div class="one_fifth">
        	<h6><?= Lang::string('home-stats-todays-open') ?></h6>
        	<p class="stat1"><?= $currency_info['fa_symbol'].'<span id="stats_open">'.number_format($stats['open'],2).'</span>'?></p>
        </div>
        <div class="one_fifth last">
        	<h6><?= Lang::string('home-stats-24h-volume') ?></h6>
        	<p class="stat1"><?= '<span id="stats_traded">'.number_format($stats['total_btc_traded'],2).'</span>' ?> BTC</p>
        </div>
        <div class="mar_top3"></div>
        <div class="one_third" style="clear:left;">
        	<h5><?= Lang::string('home-stats-market-cap') ?>: <em class="stat2">$<?= '<span id="stats_market_cap">'.number_format($stats['market_cap']).'</span>'?></em></h5>
        </div>
        <div class="one_third">
        	<h5><?= Lang::string('home-stats-total-btc') ?>: <em class="stat2"><?= '<span id="stats_total_btc">'.number_format($stats['total_btc']).'</span>' ?></em></h5>
        </div>
        <div class="one_third last">
        	<h5><?= Lang::string('home-stats-global-volume') ?>: <em class="stat2">$<?= '<span id="stats_trade_volume">'.number_format($stats['trade_volume']).'</span>' ?></em></h5>
        </div>
        <div class="mar_top2"></div>
        <div class="graph_options">
        	<a href="#" data-option="1mon">1m</a>
        	<a href="#" data-option="3mon">3m</a>
        	<a href="#" data-option="6mon">6m</a>
        	<a href="#" data-option="ytd">YTD</a>
        	<a href="#" class="selected last" data-option="1year">1y</a>
        	<span>
        	<label for="currency_selector"><?= Lang::string('currency') ?></label>
        	<select id="currency_selector">
        	<? 
        	if ($currencies) {
				foreach ($currencies as $currency) {
					echo '<option value="'.strtolower($currency['currency']).'" '.(($currency1 == strtolower($currency['currency']) || (!$currency1 && strtolower($currency['currency']) == 'usd')) ? 'selected="selected"' : '').'>'.$currency['currency'].'</option>';
				}
			}
        	?>
        	</select>
        	</span>
        </div>
        <div class="graph_contain">
        	<input type="hidden" id="graph_price_history_currency" value="<?= ($currency1) ? $currency1 : 'usd' ?>" />
        	<div id="graph_price_history"></div>
        	<div id="tooltip">
	        	<div class="date"></div>
	        	<div class="price"></div>
	        </div>
        </div>
        <div class="mar_top4"></div>
        <div class="one_half">
        	<input type="hidden" id="transactions_timestamp" value="<?= time() * 1000 ?>" />
        	<h3><?= Lang::string('home-live-trades') ?></h3>
        	<div class="table-style">
        		<table class="table-list trades" id="transactions_list">
        			<tr>
        				<th><?= Lang::string('transactions-time-since') ?></th>
        				<th><?= Lang::string('transactions-amount') ?></th>
        				<th><?= Lang::string('transactions-price') ?></th>
        			</tr>
        			<? 
        			if ($transactions) {
						foreach ($transactions as $transaction) {
							echo '
					<tr id="order_'.$transaction['id'].'">
						<td><span class="time_since"></span><input type="hidden" class="time_since_seconds" value="'.$transaction['time_since'].'" /></td>
						<td>'.number_format($transaction['btc'],8).' BTC</td>
						<td>'.$currency_info['fa_symbol'].number_format($transaction['btc_price'],2).'</td>
					</tr>';
						}
					}
					echo '<tr id="no_transactions" style="'.(is_array($transactions) ? 'display:none;' : '').'"><td colspan="3">'.Lang::string('transactions-no').'</td></tr>';
        			?>
        		</table>
        	</div>
        </div>
        <div class="one_half last">
        	<h3><?= Lang::string('home-live-orders') ?> <a href="<?= Lang::url('order-book.php') ?>" class="highlight gray"><i class="fa fa-plus-square"></i> <?= Lang::string('order-book-see') ?></a></h3>
        	<div class="one_half">
        		<div class="table-style">
        			<table class="table-list trades" id="bids_list">
        			<tr>
        				<th colspan="2"><?= Lang::string('orders-bid') ?></th>
        			</tr>
        			<? 
        			if ($bids) {
						foreach ($bids as $bid) {
							echo '
					<tr id="bid_'.$bid['id'].'" class="bid_tr">
						<td><span class="order_amount">'.number_format($bid['btc'],8).'</span> BTC<input type="hidden" id="order_id" value="'.$bid['id'].'" /></td>
						<td>'.$currency_info['fa_symbol'].'<span class="order_price">'.number_format($bid['btc_price'],2).'</span></td>
					</tr>';
						}
					}
					echo '<tr id="no_bids" style="'.(is_array($bids) ? 'display:none;' : '').'"><td colspan="2">'.Lang::string('orders-no-bid').'</td></tr>';
        			?>
        		</table>
        		</div>
        	</div>
        	<div class="one_half last">
        		<div class="table-style">
        			<table class="table-list trades" id="asks_list">
        			<tr>
        				<th colspan="2"><?= Lang::string('orders-ask') ?></th>
        			</tr>
        			<? 
        			if ($asks) {
						foreach ($asks as $ask) {
							echo '
					<tr id="ask_'.$ask['id'].'" class="ask_tr">
						<td><span class="order_amount">'.number_format($ask['btc'],8).'</span> BTC<input type="hidden" id="order_id" value="'.$ask['id'].'" /></td>
						<td>'.$currency_info['fa_symbol'].'<span class="order_price">'.number_format($ask['btc_price'],2).'</span></td>
					</tr>';
						}
					}
					echo '<tr id="no_asks" style="'.(is_array($asks) ? 'display:none;' : '').'"><td colspan="2">'.Lang::string('orders-no-ask').'</td></tr>';
        			?>
        		</table>
        		</div>
        	</div>
        </div>
    </div>
    
	<div class="clearfix mar_top3"></div>
    
</div><!-- end fresh projects -->
        			
<? include 'includes/foot.php'; ?>