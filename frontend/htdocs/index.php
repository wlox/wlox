<?php
include '../lib/common.php';

if (empty($_REQUEST) && empty($_SESSION['currency']) && !empty(User::$info['default_currency_abbr']))
	$_SESSION['currency'] = User::$info['default_currency_abbr'];
elseif (empty($_REQUEST) && empty($_SESSION['currency']) && empty(User::$info['default_currency_abbr']))
	$_SESSION['currency'] = 'usd';
elseif (!empty($_REQUEST['currency']))
	$_SESSION['currency'] = preg_replace("/[^a-z]/", "",$_REQUEST['currency']);

if (empty($CFG->currencies[strtoupper($_SESSION['currency'])]))
	$_SESSION['currency'] = 'usd';

$page_title = Lang::string('home-title');
$currency1 = strtolower($_SESSION['currency']);
$currency_symbol = strtoupper($currency1);

if (!User::isLoggedIn()) {
	API::add('Content','getRecord',array('home'));
	API::add('Content','getRecord',array('home-feature1'));
	API::add('Content','getRecord',array('home-feature2'));
	API::add('Content','getRecord',array('home-feature3'));
	API::add('Content','getRecord',array('home-feature4'));
}

API::add('Currencies','getRecord',array($currency1));
$query = API::send();

if (!User::isLoggedIn()) {
	$content = $query['Content']['getRecord']['results'][0];
	$feature1 = $query['Content']['getRecord']['results'][1];
	$feature2 = $query['Content']['getRecord']['results'][2];
	$feature3 = $query['Content']['getRecord']['results'][3];
	$feature4 = $query['Content']['getRecord']['results'][4];
}
	
$currency_info = $query['Currencies']['getRecord']['results'][0];

API::add('Stats','getCurrent',array($currency_info['id']));
API::add('Transactions','get',array(false,false,5,$currency1));
API::add('Orders','get',array(false,false,5,$currency1,false,false,1));
API::add('Orders','get',array(false,false,5,$currency1,false,false,false,false,1));
API::add('News','get',array(false,false,3));
$query = API::send();

$stats = $query['Stats']['getCurrent']['results'][0];
$transactions = $query['Transactions']['get']['results'][0];
$bids = $query['Orders']['get']['results'][0];
$asks = $query['Orders']['get']['results'][1];
$currencies = $CFG->currencies;
$news = $query['News']['get']['results'][0];

include 'includes/head.php';

if (!User::isLoggedIn()) {
?>


<!-- Slider
======================================= -->  

<div class="container_full">
    
    <div class="fullwidthbanner-container">
    
		<div class="fullwidthbanner">
        
						<ul>    
                            <!-- SLIDE 1 -->
							<li data-transition="fade" data-slotamount="9" data-thumb="images/slider-bg1.jpg">
								
                                <img src="images/slider-bg1.jpg" alt="" />
                                
                                <div class="caption sfb" data-x="658" data-y="0" data-speed="900" data-start="900" data-easing="easeOutSine"><img src="images/slide1.png" alt="" /></div>

                                <!--  div class="caption lft big_white"  data-x="10" data-y="90" data-speed="900" data-start="700" data-easing="easeOutExpo">Business, Corporate, Creative &amp; Onepage Websites</div -->
                                
								<h1 class="caption lft large_text_two <?= ($CFG->language == 'ru') ? 'caption_ru' : false ?>"  data-x="10" data-y="149" data-speed="900" data-start="1300" data-easing="easeOutExpo"><?= $content['title'] ?></h1>
                                
                                <div class="caption lfb h_line"  data-x="10" data-y="214" data-speed="900" data-start="2000" data-easing="easeOutExpo"></div>

                                <?php 
                                if ($CFG->language == 'en' || $CFG->language == 'es' || empty($CFG->language))
                                	$wordwrap = 80;
                                elseif ($CFG->language == 'ru')
                                	$wordwrap = 150;
                                elseif ($CFG->language == 'zh')
                                	$wordwrap = 150;
                                ?>
                                <p class="caption lfb small_text"  data-x="10" data-y="238" data-speed="900" data-start="2700" data-easing="easeOutExpo"><?= wordwrap(strip_tags($content['content']),$wordwrap,'<br/>') ?> <a class="morestuff" href="<?= Lang::url('about.php') ?>">>></a></p>
                                
                                <div class="caption lfb h_line"  data-x="10" data-y="344" data-speed="900" data-start="3400" data-easing="easeOutExpo"></div>
                                
                                <div class="caption lfb"  data-x="10" data-y="378" data-speed="900" data-start="4000" data-easing="easeOutExpo"><a href="login.php" class="button_slider"><i class="fa fa-key"></i>&nbsp;&nbsp;<?= Lang::string('home-login') ?></a></div>
                                
                                <div class="caption lfb"  data-x="180" data-y="378" data-speed="900" data-start="4500" data-easing="easeOutExpo"><a href="register.php" class="button_slider"><i class="fa fa-user"></i>&nbsp;&nbsp;<?= Lang::string('home-register') ?></a></div>
                               
							</li>
						</ul>
                        
					</div>
                    
				</div>


</div><!-- end slider -->

<div class="clearfix"></div>

<div class="punch_text">

	<div class="container"><b><?= Lang::string('home-trading') ?></b> <a href="how-to-register.php"><?= Lang::string('home-more') ?></a></div>

</div><!-- end punch text -->

<div class="waves_01"></div>

<div class="clearfix mar_top6"></div>

<div class="four_col_fusection container">

	<div class="one_fourth">
    	
        <i class="fa fa-check fa-4x"></i>
        
        <div class="clearfix"></div>
        
    	<h2><?= $feature1['title'] ?></h2>
        
        <p><?= strip_tags($feature1['content']) ?></p>
		<br />
		<!-- a href="#"><?= Lang::string('home-more') ?> <i class="fa fa-angle-right"></i></a -->
    
    </div><!-- end section -->
    
     <div class="one_fourth">
    	
        <i class="fa fa-shield fa-4x"></i>
        
        <div class="clearfix"></div>
        
    	<h2><?= $feature3['title'] ?></h2>
        
        <p><?= strip_tags($feature3['content']) ?></p>
		<br />
		<!--  a href="#"><?= Lang::string('home-more') ?> <i class="fa fa-angle-right"></i></a -->
    
    </div><!-- end section -->
    
    <div class="one_fourth">
    	
        <i class="fa fa-money fa-4x"></i>
        
        <div class="clearfix"></div>
        
    	<h2><?= $feature2['title'] ?></h2>
        
        <p><?= strip_tags($feature2['content']) ?></p>
		<br />
		<!-- a href="#"><?= Lang::string('home-more') ?> <i class="fa fa-angle-right"></i></a -->
    
    </div><!-- end section -->
    
    <div class="one_fourth last">
    	
        <i class="fa fa-smile-o fa-4x"></i>
        
        <div class="clearfix"></div>
        
    	<h2><?= $feature4['title'] ?></h2>
        
        <p><?= strip_tags($feature4['content']) ?></p>
		<br />
		<!-- a href="#"><?= Lang::string('home-more') ?> <i class="fa fa-angle-right"></i></a -->
    
    </div><!-- end section -->

</div>

<div class="clearfix mar_top6"></div>
<? 
}
if ($stats['daily_change'] > 0) 
	$arrow = '<i id="up_or_down" class="fa fa-caret-up price-green"></i> ';
elseif ($stats['daily_change'] < 0)
	$arrow = '<i id="up_or_down" class="fa fa-caret-down price-red"></i> ';
else
	$arrow = '<i id="up_or_down" class="fa fa-minus"></i> ';
?>
<div class="fresh_projects global_stats">
	<a name="global_stats"></a>
	<div class="clearfix mar_top6"></div>
	<div class="container">
		<? if (!User::isLoggedIn()) { ?>
    	<h2><?= Lang::string('home-bitcoin-market') ?></h2>
        <p class="explain"><?= Lang::string('home-bitcoin-market-explain') ?></p>
        <? } else { ?>
        <h2><?= Lang::string('home-overview') ?></h2>
        <? } ?>
        <div class="mar_top3"></div>
        <div class="one_fifth">
        	<h6><?= Lang::string('home-stats-last-price') ?></h6>
        	<p class="stat1 <?= ($query['Transactions']['get']['results'][0][0]['maker_type'] == 'sell') ? 'price-green' : 'price-red' ?>"><?= $currency_info['fa_symbol'].'<span id="stats_last_price">'.number_format($stats['last_price'],2).'</span>'?><small id="stats_last_price_curr"><?= (strtolower($query['Transactions']['get']['results'][0][0]['currency']) == $currency1) ? false : ((strtolower($query['Transactions']['get']['results'][0][0]['currency1']) == $currency1) ? false : ' ('.$query['Transactions']['get']['results'][0][0]['currency1'].')') ?></small></p>
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
        <div class="one_third">
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
				foreach ($currencies as $key => $currency) {
					if (is_numeric($key) || $currency['currency'] == 'BTC')
						continue;
					
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
						<td><span class="time_since"></span><input type="hidden" class="time_since_seconds" value="'.strtotime($transaction['date']).'" /></td>
						<td>'.number_format($transaction['btc'],8).' BTC</td>
						<td>'.$currency_info['fa_symbol'].number_format($transaction['btc_price'],2).((strtolower($transaction['currency']) == $currency1) ? false : ((strtolower($transaction['currency1']) == $currency1) ? false : ' ('.$transaction['currency1'].')')).'</td>
					</tr>';
						}
					}
					echo '<tr id="no_transactions" style="'.(is_array($transactions) ? 'display:none;' : '').'"><td colspan="3">'.Lang::string('transactions-no').'</td></tr>';
        			?>
        		</table>
        	</div>
        </div>
        <div class="one_half last">
        	<h3><?= Lang::string('home-live-orders') ?> <a href="order-book.php" class="highlight gray"><i class="fa fa-plus-square"></i> <?= Lang::string('order-book-see') ?></a></h3>
        	<div class="one_half">
        		<div class="table-style">
        			<table class="table-list trades" id="bids_list">
        			<tr>
        				<th colspan="2"><?= Lang::string('orders-bid') ?></th>
        			</tr>
        			<? 
        			if ($bids) {
						foreach ($bids as $bid) {
							$mine = ($bid['mine']) ? '<a class="fa fa-user" href="open-orders.php?id='.$bid['id'].'" title="'.Lang::string('home-your-order').'"></a>' : '';
							echo '
					<tr id="bid_'.$bid['id'].'" class="bid_tr">
						<td>'.$mine.'<span class="order_amount">'.number_format($bid['btc'],8).'</span> BTC<input type="hidden" id="order_id" value="'.$bid['id'].'" /></td>
						<td>'.$currency_info['fa_symbol'].'<span class="order_price">'.number_format($bid['btc_price'],2).'</span> '.(($bid['btc_price'] != $bid['fiat_price']) ? '<a title="'.str_replace('[currency]',$bid['currency_abbr'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
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
							$mine = ($ask['mine']) ? '<a class="fa fa-user" href="open-orders.php?id='.$ask['id'].'" title="'.Lang::string('home-your-order').'"></a>' : '';
							echo '
					<tr id="ask_'.$ask['id'].'" class="ask_tr">
						<td>'.$mine.'<span class="order_amount">'.number_format($ask['btc'],8).'</span> BTC<input type="hidden" id="order_id" value="'.$ask['id'].'" /></td>
						<td>'.$currency_info['fa_symbol'].'<span class="order_price">'.number_format($ask['btc_price'],2).'</span> '.(($ask['btc_price'] != $ask['fiat_price']) ? '<a title="'.str_replace('[currency]',$ask['currency_abbr'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
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


<div class="clearfix mar_top5"></div>

<div class="features_sec03">
	<div class="container">
    	<h2><?= Lang::string('news-latest') ?></h2>
        <p class="explain"><?= Lang::string('news-explain') ?></p>
        <div class="clearfix mar_top3"></div>
        <? 
        if ($news) {
			$i = 1;
			$c = count($news);
			foreach ($news as $news_item) {
			?>
		<div class="blog_post">
			<div class="blog_postcontent">
				<div class="post_info_content_small fullwidth">
					<a class="date" href="#" onclick="return false;"><strong><?= date('j',strtotime($news_item['date']))?></strong><i><?= Lang::string(strtolower(date('M',strtotime($news_item['date'])))) ?></i></a>
					<div class="postcontent">	
						<h3><a href="#" onclick="return false;"><?= $news_item['title_'.$CFG->language] ?></a></h3>
						<div class="posttext"><?= $news_item['content_'.$CFG->language] ?></div>
					</div>
				</div>
			</div>
		</div>
		<?= ($c != $i) ? '<div class="clearfix divider_line3"></div>' : '' ?>
			<?
			$i++;
			}
		}
        ?>
        <div class="clearfix mar_top5"></div>
        <a href="news.php" class="highlight gray bigger"><i class="fa fa-plus-square"></i> <?= Lang::string('news-see-all') ?></a>
    </div>
	<div class="clearfix mar_top8"></div>
</div><!-- end features section 3 -->




<? include 'includes/foot.php'; ?>