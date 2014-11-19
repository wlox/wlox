<?php 
include '../cfg/cfg.php';

$api_key1 = preg_replace("/[^0-9a-zA-Z]/","",$_POST['api_key']);
$api_signature1 = preg_replace("/[^0-9a-zA-Z]/","",$_POST['signature']);
$nonce1 = preg_replace("/[^0-9]/","",$_POST['nonce']);
$CFG->language = preg_replace("/[^a-z]/","",$_POST['lang']);
$currency1 = preg_replace("/[^a-zA-Z]/","",$_REQUEST['currency']);

$post = ($_SERVER['REQUEST_METHOD'] == 'POST');
$endpoint = $_REQUEST['endpoint'];

// check if API key/signature received
if ($api_key1 && (strlen($api_key1) != 16 || strlen($api_signature1) != 64)) {
	$return['errors'][] = array('message'=>'Invalid API key or signature.','code'=>'AUTH_INVALID_KEY');
	$invalid_signature = true;
}
elseif ($api_key1 && $api_signature1) {
	API::add('APIKeys','hasPermission',array($api_key1));
	$query = API::send($nonce1);
	$permissions = $query['APIKeys']['hasPermission']['results'][0];
}

// check if currency is supported
if ($currency1 && (!is_array($CFG->currencies[strtoupper($currency1)]) && !(strtolower($currency1) == 'btc' && $endpoint == 'withdrawals/new'))) {
	$return['errors'][] = array('message'=>'Invalid currency.','code'=>'INVALID_CURRENCY');
	$invalid_currency = true;
}

if ($endpoint == 'stats') {
	if (!$invalid_currency) {
		$currency1 = (!$currency1) ? 'usd' : strtolower($currency1);
		$currency = $CFG->currencies[strtoupper($currency1)];
		
		API::add('Stats','getCurrent',array(false,$currency1));
		$query = API::send();
		
		if (is_array($query['Stats']['getCurrent']['results'][0])) {
			$return['stats'] = $query['Stats']['getCurrent']['results'][0];
			$return['stats']['request_currency'] = strtoupper($currency1);
			$return['stats']['daily_change'] = round($return['stats']['daily_change'],2,PHP_ROUND_HALF_UP);
			$return['stats']['daily_change_percent'] = round($return['stats']['daily_change_percent'],2,PHP_ROUND_HALF_UP);
			$return['stats']['total_btc_traded'] = ($return['stats']['total_btc_traded']) ? $return['stats']['total_btc_traded'] : 0;
		}
		else
			$return['stats'] = array();
	}
}
elseif ($endpoint == 'historical-prices') {
	if (!$invalid_currency) {
		// timeframe values: 1mon, 3mon, 6mon, ytd, 1year
		$timeframe_values = array('1mon','3mon','6mon','ytd','1year');
		$currency1 = (!$currency1) ? 'usd' : strtolower($currency1);
		$timeframe1 = preg_replace("/[^0-9a-zA-Z]/","",$_REQUEST['timeframe']);
		$timeframe1 = (!$timeframe1 || !in_array($timeframe1,$timeframe_values)) ? '1mon' : $timeframe1;
		
		API::add('Stats','getHistorical',array(strtolower($timeframe1),$currency1,1));
		$query = API::send();
		
		if (is_array($query['Stats']['getHistorical']['results'][0])) {
			$return['historical-prices'] = $query['Stats']['getHistorical']['results'][0];
			$return['historical-prices']['request_currency'] = strtoupper($currency1);
		}
		else
			$return['historical-prices'] = array();
	}
}
elseif ($endpoint == 'order-book') {
	if (!$invalid_currency) {
		$currency1 = (!$currency1) ? 'usd' : strtolower($currency1);
		
		API::add('Orders','get',array(false,false,false,$currency1,false,false,1,false,false,1,false,1));
		API::add('Orders','get',array(false,false,false,$currency1,false,false,false,false,1,1,false,1));
		$query = API::send();
		
		$return['order-book']['request_currency'] = strtoupper($currency1);
		$return['order-book']['bid'] = ($query['Orders']['get']['results'][0]) ? $query['Orders']['get']['results'][0] : array();
		$return['order-book']['ask'] = ($query['Orders']['get']['results'][1]) ? $query['Orders']['get']['results'][1] : array();
	}
}
elseif ($endpoint == 'transactions') {
	// currency filters transactions involving that particular currency
	if (!$invalid_currency) {
		$limit1 = preg_replace("/[^0-9]/","",$_REQUEST['limit']);
		$limit1 = (!$limit1) ? 10 : $limit1;
		
		API::add('Transactions','get',array(false,false,$limit1,strtolower($currency1),false,false,false,false,false,1));
		$query = API::send();
		$return['transactions'] = ($query['Transactions']['get']['results'][0]) ? $query['Transactions']['get']['results'][0] : array();
		$return['transactions']['request_currency'] = (!$currency1) ? 'ORIGINAL' : strtoupper($currency1);
	}
}
elseif ($endpoint == 'balances-and-info') {
	if ($post) {
		if (!$invalid_signature && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_view'] == 'Y') {
				API::add('User','getOnHold');
				API::add('User','getAvailable');
				API::add('User','getVolume');
				API::add('FeeSchedule','getRecord',array(false,1));
				API::add('Stats','getBTCTraded');
				API::apiKey($api_key1);
				API::apiSignature($api_signature1);
				API::apiUpdateNonce();
				$query = API::send($nonce1);
				
				if (!$query['error']) {
					$return['balances-and-info']['on_hold'] = ($query['User']['getOnHold']['results'][0]) ? $query['User']['getOnHold']['results'][0] : array();
					$return['balances-and-info']['available'] = ($query['User']['getAvailable']['results'][0]) ? $query['User']['getAvailable']['results'][0] : array();
					$return['balances-and-info']['usd_volume'] = ($query['User']['getVolume']['results'][0]) ? $query['User']['getVolume']['results'][0] : 0;
					$return['balances-and-info']['fee_bracket']['maker'] = ($query['FeeSchedule']['getRecord']['results'][0]['fee1']) ? $query['FeeSchedule']['getRecord']['results'][0]['fee1'] : 0;
					$return['balances-and-info']['fee_bracket']['taker'] = ($query['FeeSchedule']['getRecord']['results'][0]['fee']) ? $query['FeeSchedule']['getRecord']['results'][0]['fee'] : 0;
					$return['balances-and-info']['global_btc_volume'] = ($query['Stats']['getBTCTraded']['results'][0][0]['total_btc_traded'] > 0) ? $query['Stats']['getBTCTraded']['results'][0][0]['total_btc_traded'] : 0;
				}
				else
					$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
	else
		$return['errors'][] = array('message'=>'Invalid HTTP method.','code'=>'AUTH_INVALID_HTTP_METHOD');
}
elseif ($endpoint == 'open-orders') {
	if ($post) {
		if (!$invalid_signature && !$invalid_currency && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_view'] == 'Y') {
				// currency filters by native currency
				API::add('Orders','get',array(false,false,false,strtolower($currency1),1,false,1,false,false,1,1));
				API::add('Orders','get',array(false,false,false,strtolower($currency1),1,false,false,false,1,1,1));
				API::apiKey($api_key1);
				API::apiSignature($api_signature1);
				API::apiUpdateNonce();
				$query = API::send($nonce1);
				
				if (!$query['error']) {
					$return['open-orders']['bid'] = ($query['Orders']['get']['results'][0]) ? $query['Orders']['get']['results'][0] : array();
					$return['open-orders']['ask'] = ($query['Orders']['get']['results'][1]) ? $query['Orders']['get']['results'][1] : array();
					$return['open-orders']['request_currency'] = (!$currency1) ? 'ORIGINAL' : strtoupper($currency1);
				}
				else
					$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature && !$invalid_currency)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
	else
		$return['errors'][] = array('message'=>'Invalid HTTP method.','code'=>'AUTH_INVALID_HTTP_METHOD');
}
elseif ($endpoint == 'user-transactions') {
	if ($post) {
		if (!$invalid_signature && !$invalid_currency && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_view'] == 'Y') {
				// currency filters by currency
				// type can be 'buy' or 'sell'
				$limit1 = preg_replace("/[^0-9]/","",$_REQUEST['limit']);
				$limit1 = (!$limit1) ? 10 : $limit1;
				$type1 = preg_replace("/[^a-zA-Z]/","",$_REQUEST['side']);
				
				API::add('Transactions','get',array(false,false,$limit1,$currency1,1,false,strtolower($type1),false,false,1));
				API::apiKey($api_key1);
				API::apiSignature($api_signature1);
				API::apiUpdateNonce();
				$query = API::send($nonce1);
				
				if (!$query['error'])
					$return['user-transactions'] = ($query['Transactions']['get']['results'][0]) ? $query['Transactions']['get']['results'][0] : array();
				else
					$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature && !$invalid_currency)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
	else
		$return['errors'][] = array('message'=>'Invalid HTTP method.','code'=>'AUTH_INVALID_HTTP_METHOD');
}
elseif ($endpoint == 'btc-deposit-address/get') {
	if ($post) {
		if (!$invalid_signature && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_view'] == 'Y') {
				$limit1 = preg_replace("/[^0-9]/","",$_REQUEST['limit']);
				$limit1 = (!$limit1) ? 10 : $limit1;
				
				API::add('BitcoinAddresses','get',array(false,false,$limit1,false,false,false,1));
				API::apiKey($api_key1);
				API::apiSignature($api_signature1);
				API::apiUpdateNonce();
				$query = API::send($nonce1);
				
				if (!$query['error'])
					$return['btc-deposit-address-get'] = ($query['BitcoinAddresses']['get']['results'][0]) ? $query['BitcoinAddresses']['get']['results'][0] : array();
				else
					$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
}
elseif ($endpoint == 'btc-deposit-address/new') {
	if ($post) {
		if (!$invalid_signature && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_view'] == 'Y') {
				API::add('BitcoinAddresses','get',array(false,false,1,1));
				API::apiKey($api_key1);
				API::apiSignature($api_signature1);
				$query = API::send($nonce1);
				$bitcoin_addresses = $query['BitcoinAddresses']['get']['results'][0];
	
				if (strtotime($bitcoin_addresses[0]['date']) >= strtotime('-1 day')) {
					$return['errors'][] = array('message'=>Lang::string('bitcoin-addresses-too-soon'),'code'=>'BTC_ADDRESS_TOO_SOON');
					$error = true;
				}
				
				if (!$error) {
					API::add('BitcoinAddresses','getNew',array(1));
					API::apiKey($api_key1);
					API::apiSignature($api_signature1);
					API::apiUpdateNonce();
					$query = API::send($nonce1);
					
					if (!$query['error'])
						$return['btc-deposit-address-new']['address'] = $query['BitcoinAddresses']['getNew']['results'][0];
					else
						$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
				}
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
	else
		$return['errors'][] = array('message'=>'Invalid HTTP method.','code'=>'AUTH_INVALID_HTTP_METHOD');
}
elseif ($endpoint == 'deposits/get') {
	if ($post) {
		if (!$invalid_signature && !$invalid_currency && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_view'] == 'Y') {
				// status can be 'pending' or 'completed'
				$limit1 = preg_replace("/[^0-9]/","",$_REQUEST['limit']);
				$limit1 = (!$limit1) ? 10 : $limit1;
				$status1 = strtolower(preg_replace("/[^a-zA-Z]/","",$_REQUEST['status']));
				
				if ($status1 && ($status1 != 'pending' && $status1 != 'completed' && $status1 != 'cancelled')) {
					$return['errors'][] = array('message'=>'Invalid status.','code'=>'DEPOSIT_INVALID_STATUS');
					$error = true;
				}
				
				if (!$error) {
					API::add('Requests','get',array(false,false,$limit1,false,strtolower($currency1),$status1,1));
					API::apiKey($api_key1);
					API::apiSignature($api_signature1);
					API::apiUpdateNonce();
					$query = API::send($nonce1);
					
					if (!$query['error'])
						$return['deposits'] = ($query['Requests']['get']['results'][0]) ? $query['Requests']['get']['results'][0] : array();
					else
						$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
				}
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature && !$invalid_currency)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
}
elseif ($endpoint == 'withdrawals/get') {
	if ($post) {
		if (!$invalid_signature && !$invalid_currency && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_view'] == 'Y') {
				// status can be 'pending' or 'completed'
				$limit1 = preg_replace("/[^0-9]/","",$_REQUEST['limit']);
				$limit1 = (!$limit1) ? 10 : $limit1;
				$status1 = strtolower(preg_replace("/[^a-zA-Z]/","",$_REQUEST['status']));
				
				if ($status1 && ($status1 != 'pending' && $status1 != 'completed' && $status1 != 'cancelled')) {
					$return['errors'][] = array('message'=>'Invalid status.','code'=>'DEPOSIT_INVALID_STATUS');
					$error = true;
				}
				
				API::add('Requests','get',array(false,false,$limit1,1,strtolower($currency1),$status1,1));
				API::apiKey($api_key1);
				API::apiSignature($api_signature1);
				API::apiUpdateNonce();
				$query = API::send($nonce1);
					
				if (!$query['error'])
					$return['withdrawals'] = ($query['Requests']['get']['results'][0]) ? $query['Requests']['get']['results'][0] : array();
				else
					$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature && !$invalid_currency)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
	else
		$return['errors'][] = array('message'=>'Invalid HTTP method.','code'=>'AUTH_INVALID_HTTP_METHOD');
}
elseif ($endpoint == 'orders/new') {
	if ($post) {
		if (!$invalid_signature && !$invalid_currency && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_orders'] == 'Y') {
				// new orders can be many or just one, can be in json or regular array (use http_build_query on all commands)
				// params: side(buy/sell), type(market,limit,stop), limit_price, stop_price, amount, currency
	
				$json = json_decode($_POST['orders'],1);
				if (is_array($_POST['orders']))
					$orders = $_POST['orders'];
				elseif (is_array($json))
					$orders = $json;
				else
					$orders[] = array('side'=>$_POST['side'],'type'=>$_POST['type'],'currency'=>strtolower($currency1),'limit_price'=>$_POST['limit_price'],'stop_price'=>$_POST['stop_price'],'amount'=>$_POST['amount']);
				
				if (is_array($orders)) {
					$i = 1;
					foreach ($orders as $order) {
						$order['side'] = strtolower(preg_replace("/[^a-zA-Z]/","",$order['side']));
						$order['type'] = strtolower(preg_replace("/[^a-zA-Z]/","",$order['type']));
						$order['currency'] = strtolower(preg_replace("/[^a-zA-Z]/","",$order['currency']));
						$order['limit_price'] = preg_replace("/[^0-9.]/", "",$order['limit_price']);
						$order['stop_price'] = ($order['type'] == 'stop') ? preg_replace("/[^0-9.]/", "",$order['stop_price']) : false;
						$order['amount'] = preg_replace("/[^0-9.]/", "",$order['amount']);
						
						// preliminary validation
						if ($order['side'] != 'buy' && $order['side'] != 'sell') {
							$return['errors'][] = array('message'=>'Invalid order side (must be buy or sell).','code'=>'ORDER_INVALID_SIDE');
							continue;
						}
						elseif ($order['type'] != 'market' && $order['type'] != 'limit' && $order['type'] != 'stop') {
							$return['errors'][] = array('message'=>'Invalid order type (must be market, limit or stop).','code'=>'ORDER_INVALID_TYPE');
							continue;
						}
						elseif (!is_array($CFG->currencies[strtoupper($order['currency'])])) {
							$return['errors'][] = array('message'=>'Unsupported currency.','code'=>'INVALID_CURRENCY');
							continue;
						}
						elseif (!($order['amount'] > 0)) {
							$return['errors'][] = array('message'=>'Amount to '.($order['side'] == 'buy' ? 'buy' : 'sell').' must be greater than zero.','code'=>'ORDER_INVALID_AMOUNT');
							continue;
						}
						elseif ($order['type'] == 'limit' && !($order['limit_price'] > 0)) {
							$return['errors'][] = array('message'=>'No limit price provided.','code'=>'ORDER_INVALID_LIMIT_PRICE');
							continue;
						}
						elseif ($order['type'] == 'stop' && !($order['stop_price'] > 0)) {
							$return['errors'][] = array('message'=>Lang::string('buy-errors-no-stop'),'code'=>'ORDER_INVALID_STOP_PRICE');
							continue;
						}
						
						// get things to check against
						if ($order['side'] == 'buy' && $order['type'] != 'market') {
							API::add('Orders','checkOutbidSelf',array($order['limit_price'],$order['currency']));
							API::add('Orders','checkOutbidStops',array($order['limit_price'],$order['currency']));
						}
						elseif ($order['side'] == 'sell' && $order['type'] != 'market') {
							API::add('Orders','checkOutbidSelf',array($order['limit_price'],$order['currency'],1));
							API::add('Orders','checkStopsOverBid',array($order['stop_price'],$order['currency']));
						}
						if (!$user_fee_both)
							API::add('FeeSchedule','getRecord',array(false,1));
						
						API::add('User','getAvailable');
						API::add('Orders','getCurrentBid',array($order['currency']));
						API::add('Orders','getCurrentAsk',array($order['currency']));
						API::add('Orders','get',array(false,false,10,$order['currency'],false,false,1));
						API::add('Orders','get',array(false,false,10,$order['currency'],false,false,false,false,1));
						API::add('Status','get');
						API::apiKey($api_key1);
						API::apiSignature($api_signature1);
						$query = API::send($nonce1);
						
						if ($query['error']) {
							$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
							break;
						}
						
						if ($query['Status']['get']['results'][0]['trading_status'] == 'suspended') {
							$return['errors'][] = array('message'=>Lang::string('buy-trading-disabled'),'code'=>'TRADING_SUSPENDED');
							break;
						}
							
						$user_fee_both = (!$user_fee_both) ? $query['FeeSchedule']['getRecord']['results'][0] : $user_fee_both;
						$user_available = $query['User']['getAvailable']['results'][0];
						$current_bid = $query['Orders']['getCurrentBid']['results'][0];
						$current_ask = $query['Orders']['getCurrentAsk']['results'][0];
						$bids = $query['Orders']['get']['results'][0];
						$asks = $query['Orders']['get']['results'][1];
						$self_orders = $query['Orders']['checkOutbidSelf']['results'][0][0]['price'];
						$self_stops = $query['Orders']['checkOutbidStops']['results'][0][0]['price'];
						$self_limits = $query['Orders']['checkStopsOverBid']['results'][0][0]['price'];
						$self_orders_currency = $query['Orders']['checkOutbidSelf']['results'][0][0]['currency'];
						$self_stops_currency = $query['Orders']['checkOutbidStops']['results'][0][0]['currency'];
						$self_limits_currency = $query['Orders']['checkStopsOverBid']['results'][0][0]['currency'];
						$order['limit_price'] = ($order['type'] == 'market') ? (($order['side'] == 'buy') ? $current_ask : $current_bid) : $order['limit_price'];
							
						$currency_info = $CFG->currencies[strtoupper($order['currency'])];
						$user_fee_bid = (($asks && $order['limit_price'] >= $asks[0]['btc_price']) || $order['type'] == 'market') ? $user_fee_both['fee'] : $user_fee_both['fee1'];
						$user_fee_ask = (($bids && $order['limit_price'] <= $bids[0]['btc_price']) || $order['type'] == 'market') ? $user_fee_both['fee'] : $user_fee_both['fee1'];
						$subtotal = $order['amount'] * $order['limit_price'];
						$fee_amount = ($order['side'] == 'buy') ? ($user_fee_bid * 0.01) * $subtotal : ($user_fee_ask * 0.01) * $subtotal;
						$total = ($order['side'] == 'buy') ? $subtotal + $fee_amount : $subtotal - $fee_amount;
						
						// advanced validation
						if (($order['side'] == 'buy' && $total > $user_available[strtoupper($order['currency'])]) || ($order['side'] == 'sell' && $order['amount'] > $user_available['BTC'])) {
							$return['errors'][] = array('message'=>Lang::string('buy-errors-balance-too-low'),'code'=>'ORDER_BALANCE_TOO_LOW');
							continue;
						}
						elseif ($order['type'] == 'market' && (($order['side'] == 'buy' && !$asks) || ($order['side'] == 'sell' && !$bids))) {
							$return['errors'][] = array('message'=>Lang::string('buy-errors-no-compatible'),'code'=>'ORDER_MARKET_NO_COMPATIBLE');
							continue;
						}
						elseif (($subtotal * $currency_info['usd_ask']) < $CFG->orders_min_usd) {
							$return['errors'][] = array('message'=>str_replace('[amount]',number_format(($CFG->orders_min_usd/$currency_info['usd_ask']),2),str_replace('[fa_symbol]',$currency_info['fa_symbol'],Lang::string('buy-errors-too-little'))),'code'=>'ORDER_UNDER_MINIMUM');
							continue;
						}
						elseif ($self_orders) {
							$return['errors'][] = array('message'=>Lang::string('buy-errors-outbid-self').(($currency_info['id'] != $self_orders_currency) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($self_orders,2),' '.Lang::string('limit-max-price')) : ''),'code'=>'ORDER_OUTBID_SELF');
							continue;
						}
						elseif ((($order['side'] == 'buy' && $order['type'] == 'stop' && $order['stop_price'] <= $current_ask) || ($order['side'] == 'sell' && $order['stop_price'] >= $current_bid)) && $order['type'] == 'stop') {
							$return['errors'][] = array('message'=>($order['side'] == 'buy') ? Lang::string('buy-stop-lower-ask') : Lang::string('sell-stop-higher-bid'),'code'=>'ORDER_STOP_IN_MARKET');
							continue;
						}
						elseif (($order['side'] == 'buy' && $order['type'] == 'stop' && $order['stop_price'] <= $order['limit_price']) || ($order['side'] == 'sell' && $order['stop_price'] >= $order['limit_price']) && $order['stop_price'] > 0 && $order['limit_price'] > 0) {
							$return['errors'][] = array('message'=>($order['side'] == 'buy') ? Lang::string('buy-stop-lower-price') : Lang::string('sell-stop-lower-price'),'code'=>'ORDER_STOP_OVER_LIMIT');
							continue;
						}
						elseif ($order['side'] == 'buy' && $order['limit_price'] < ($current_ask - ($current_ask * (0.01 * $CFG->orders_under_market_percent)))) {
							$return['errors'][] = array('message'=>str_replace('[percent]',$CFG->orders_under_market_percent,Lang::string('buy-errors-under-market')),'code'=>'ORDER_TOO_FAR_UNDER_MARKET');
							continue;
						}
						elseif ($self_stops) {
							$return['errors'][] = array('message'=>Lang::string('buy-limit-under-stops').(($currency_info['id'] != $self_stops_currency) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($self_stops,2),' '.Lang::string('limit-min-price')) : ''),'code'=>'ORDER_BUY_LIMIT_UNDER_STOPS');
							continue;
						}	
						elseif ($self_limits) {
							$return['errors'][] = array('message'=>Lang::string('sell-limit-under-stops').(($currency_info['id'] != $self_limits_currency) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($self_limits,2),' '.Lang::string('limit-max-price')) : ''),'code'=>'ORDER_BUY_LIMIT_UNDER_STOPS');
							continue;
						}
						
						API::add('Orders','executeOrder',array(($order['side'] == 'buy'),$order['limit_price'],$order['amount'],$order['currency'],false,($order['type'] == 'market'),false,false,false,$order['stop_price'],false,1));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1);
						
						if (count($orders) == $i)
							API::apiUpdateNonce();
						
						$query = API::send($nonce1);
						$result = $query['Orders']['executeOrder']['results'][0];
						
						if ($result) {
							unset($result['order_info']['comp_orig_prices']);
							unset($result['order_info']['replaced']);
							unset($result['edit_order']);
							unset($result['executed']);
							
							if ($order['limit_price'] > 0 && $order['stop_price'] > 0 && $order['type'] == 'stop')
								$result['order_info']['oco'] = true;
						}
						
						$return['orders-new'][] = ($result) ? $result : array();
						$i++;
					}
				}
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature && !$invalid_currency)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
	else
		$return['errors'][] = array('message'=>'Invalid HTTP method.','code'=>'AUTH_INVALID_HTTP_METHOD');
}
elseif ($endpoint == 'orders/edit') {
	// will return "replaced" as the id of the order that was replaced
	if ($post) {
		if (!$invalid_signature && !$invalid_currency && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_orders'] == 'Y') {
				$json = json_decode($_POST['orders'],1);
				if (is_array($_POST['orders']))
					$orders = $_POST['orders'];
				elseif (is_array($json))
					$orders = $json;
				else
					$orders[] = array('id'=>$_POST['id'],'type'=>$_POST['type'],'limit_price'=>$_POST['limit_price'],'stop_price'=>$_POST['stop_price'],'amount'=>$_POST['amount']);
					
				if (is_array($orders)) {
					$i = 1;
					foreach ($orders as $order) {
						$order['id'] = preg_replace("/[^0-9]/", "",$order['id']);
						$order['type'] = strtolower(preg_replace("/[^a-zA-Z]/","",$order['type']));
						$order['limit_price'] = preg_replace("/[^0-9.]/", "",$order['limit_price']);
						$order['stop_price'] = preg_replace("/[^0-9.]/", "",$order['stop_price']);
						$order['amount'] = preg_replace("/[^0-9.]/", "",$order['amount']);
						
						// preliminary validation
						if ($order['type'] != 'market' && $order['type'] != 'limit' && $order['type'] != 'stop') {
							$return['errors'][] = array('message'=>'Invalid order type (must be market, limit or stop).','code'=>'ORDER_INVALID_TYPE');
							continue;
						}
						elseif (!($order['amount'] > 0)) {
							$return['errors'][] = array('message'=>'Amount to '.($order['side'] == 'buy' ? 'buy' : 'sell').' must be greater than zero.','code'=>'ORDER_INVALID_AMOUNT');
							continue;
						}
						elseif ($order['type'] == 'limit' && !($order['limit_price'] > 0)) {
							$return['errors'][] = array('message'=>'No limit price provided.','code'=>'ORDER_INVALID_LIMIT_PRICE');
							continue;
						}
						elseif ($order['type'] == 'stop' && !($order['stop_price'] > 0)) {
							$return['errors'][] = array('message'=>Lang::string('buy-errors-no-stop'),'code'=>'ORDER_INVALID_STOP_PRICE');
							continue;
						}
						
						// get the original order
						API::add('Orders','getRecord',array(false,$order['id']));
						API::add('Status','get');
						API::apiKey($api_key1);
						API::apiSignature($api_signature1);
						$query = API::send($nonce1);
						$orig_order = $query['Orders']['getRecord']['results'][0];
						
						if ($query['error']) {
							$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
							break;
						}
						
						if ($query['Status']['get']['results'][0]['trading_status'] == 'suspended') {
							$return['errors'][] = array('message'=>Lang::string('buy-trading-disabled'),'code'=>'TRADING_SUSPENDED');
							break;
						}
						
						if (!($orig_order['id']) > 0) {
							$return['errors'][] = array('message'=>'Order not found.','code'=>'ORDER_NOT_FOUND');
							continue;
						}
						else {
							$order['side'] = ($orig_order['is_bid']) ? 'buy' : 'sell';
							$order['currency'] = strtolower($orig_order['currency_abbr']);
							$order['order_id'] = $orig_order['id'];
						}
							
						// get things to check against
						if ($order['side'] == 'buy' && $order['type'] != 'market') {
							API::add('Orders','checkOutbidSelf',array($order['limit_price'],$order['currency']));
							API::add('Orders','checkOutbidStops',array($order['limit_price'],$order['currency']));
						}
						elseif ($order['side'] == 'sell' && $order['type'] != 'market') {
							API::add('Orders','checkOutbidSelf',array($order['limit_price'],$order['currency'],1));
							API::add('Orders','checkStopsOverBid',array($order['stop_price'],$order['currency']));
						}
						if (!$user_fee_both)
							API::add('FeeSchedule','getRecord',array(false,1));
							
						API::add('User','getAvailable');
						API::add('Orders','getCurrentBid',array($order['currency']));
						API::add('Orders','getCurrentAsk',array($order['currency']));
						API::add('Orders','get',array(false,false,10,$order['currency'],false,false,1));
						API::add('Orders','get',array(false,false,10,$order['currency'],false,false,false,false,1));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1);
						$query = API::send($nonce1);
		
						$user_fee_both = (!$user_fee_both) ? $query['FeeSchedule']['getRecord']['results'][0] : $user_fee_both;
						$user_available = $query['User']['getAvailable']['results'][0];
						$current_bid = $query['Orders']['getCurrentBid']['results'][0];
						$current_ask = $query['Orders']['getCurrentAsk']['results'][0];
						$bids = $query['Orders']['get']['results'][0];
						$asks = $query['Orders']['get']['results'][1];
						$self_orders = $query['Orders']['checkOutbidSelf']['results'][0][0]['price'];
						$self_stops = $query['Orders']['checkOutbidStops']['results'][0][0]['price'];
						$self_limits = $query['Orders']['checkStopsOverBid']['results'][0][0]['price'];
						$self_orders_currency = $query['Orders']['checkOutbidSelf']['results'][0][0]['currency'];
						$self_stops_currency = $query['Orders']['checkOutbidStops']['results'][0][0]['currency'];
						$self_limits_currency = $query['Orders']['checkStopsOverBid']['results'][0][0]['currency'];
						$order['limit_price'] = ($order['type'] == 'market') ? (($order['side'] == 'buy') ? $current_ask : $current_bid) : $order['limit_price'];
		
						$currency_info = $CFG->currencies[strtoupper($order['currency'])];
						$user_fee_bid = (($asks && $order['limit_price'] >= $asks[0]['btc_price']) || $order['type'] == 'market') ? $user_fee_both['fee'] : $user_fee_both['fee1'];
						$user_fee_ask = (($bids && $order['limit_price'] <= $bids[0]['btc_price']) || $order['type'] == 'market') ? $user_fee_both['fee'] : $user_fee_both['fee1'];
						$subtotal = $order['amount'] * $order['limit_price'];
						$fee_amount = ($order['side'] == 'buy') ? ($user_fee_bid * 0.01) * $subtotal : ($user_fee_ask * 0.01) * $subtotal;
						$total = ($order['side'] == 'buy') ? $subtotal + $fee_amount : $subtotal - $fee_amount;
							
						// advanced validation
						if (($order['side'] == 'buy' && $total > $user_available[strtoupper($order['currency'])]) || ($order['side'] == 'sell' && $order['amount'] > $user_available['BTC'])) {
							$return['errors'][] = array('message'=>Lang::string('buy-errors-balance-too-low'),'code'=>'ORDER_BALANCE_TOO_LOW');
							continue;
						}
						elseif ($order['type'] == 'market' && (($order['side'] == 'buy' && !$asks) || ($order['side'] == 'sell' && !$bids))) {
							$return['errors'][] = array('message'=>Lang::string('buy-errors-no-compatible'),'code'=>'ORDER_MARKET_NO_COMPATIBLE');
							continue;
						}
						elseif (($subtotal * $currency_info['usd_ask']) < $CFG->orders_min_usd) {
							$return['errors'][] = array('message'=>str_replace('[amount]',number_format(($CFG->orders_min_usd/$currency_info['usd_ask']),2),str_replace('[fa_symbol]',$currency_info['fa_symbol'],Lang::string('buy-errors-too-little'))),'code'=>'ORDER_UNDER_MINIMUM');
							continue;
						}
						elseif ($self_orders) {
							$return['errors'][] = array('message'=>Lang::string('buy-errors-outbid-self').(($currency_info['id'] != $self_orders_currency) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($self_orders,2),' '.Lang::string('limit-max-price')) : ''),'code'=>'ORDER_OUTBID_SELF');
							continue;
						}
						elseif ((($order['side'] == 'buy' && $order['stop_price'] <= $current_ask) || ($order['side'] == 'sell' && $order['stop_price'] >= $current_bid)) && $order['type'] == 'stop') {
							$return['errors'][] = array('message'=>($order['side'] == 'buy') ? Lang::string('buy-stop-lower-ask') : Lang::string('sell-stop-higher-bid'),'code'=>'ORDER_STOP_IN_MARKET');
							continue;
						}
						elseif (($order['side'] == 'buy' && $order['stop_price'] <= $order['limit_price']) || ($order['side'] == 'sell' && $order['stop_price'] >= $order['limit_price']) && $order['stop_price'] > 0 && $order['limit_price'] > 0) {
							$return['errors'][] = array('message'=>($order['side'] == 'buy') ? Lang::string('buy-stop-lower-price') : Lang::string('sell-stop-lower-price'),'code'=>'ORDER_STOP_OVER_LIMIT');
							continue;
						}
						elseif ($order['side'] == 'buy' && $order['limit_price'] < ($current_ask - ($current_ask * (0.01 * $CFG->orders_under_market_percent)))) {
							$return['errors'][] = array('message'=>str_replace('[percent]',$CFG->orders_under_market_percent,Lang::string('buy-errors-under-market')),'code'=>'ORDER_TOO_FAR_UNDER_MARKET');
							continue;
						}
						elseif ($self_stops) {
							$return['errors'][] = array('message'=>Lang::string('buy-limit-under-stops').(($currency_info['id'] != $self_stops_currency) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($self_stops,2),' '.Lang::string('limit-min-price')) : ''),'code'=>'ORDER_BUY_LIMIT_UNDER_STOPS');
							continue;
						}
						elseif ($self_limits) {
							$return['errors'][] = array('message'=>Lang::string('sell-limit-under-stops').(($currency_info['id'] != $self_limits_currency) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($self_limits,2),' '.Lang::string('limit-max-price')) : ''),'code'=>'ORDER_BUY_LIMIT_UNDER_STOPS');
							continue;
						}
							
						API::add('Orders','executeOrder',array(($order['side'] == 'buy'),$order['limit_price'],$order['amount'],$order['currency'],false,($order['type'] == 'market'),$order['order_id'],false,false,$order['stop_price'],false,1));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1);
						
						if (count($orders) == $i)
							API::apiUpdateNonce();
						
						$query = API::send($nonce1);
						$result = $query['Orders']['executeOrder']['results'][0];
							
						if ($result) {
							unset($result['order_info']['comp_orig_prices']);
							unset($result['new_order']);
							unset($result['executed']);
							
							if ($order['limit_price'] > 0 && $order['stop_price'] > 0 && $order['type'] == 'stop')
								$result['order_info']['oco'] = true;
						}
							
						$return['orders-edit'][] = ($result) ? $result : array();
						$i++;
					}
				}
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature && !$invalid_currency)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
	else
		$return['errors'][] = array('message'=>'Invalid HTTP method.','code'=>'AUTH_INVALID_HTTP_METHOD');
}
elseif ($endpoint == 'orders/cancel') {
	if ($post) {
		if (!$invalid_signature && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_orders'] == 'Y') {
				if (!$_POST['all']) {
					$json = json_decode($_POST['orders'],1);
					if (is_array($_POST['orders']))
						$orders = $_POST['orders'];
					elseif (is_array($json))
						$orders = $json;
					else
						$orders[] = array('id'=>$_POST['id']);
			
					if (is_array($orders)) {
						$i++;
						foreach ($orders as $order) {
							$order['id'] = preg_replace("/[^0-9]/", "",$order['id']);
							
							API::add('Orders','getRecord',array(false,$order['id']));
							API::apiKey($api_key1);
							API::apiSignature($api_signature1);
							$query = API::send($nonce1);
							$orig_order = $query['Orders']['getRecord']['results'][0];
							
							if ($query['error']) {
								$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
								break;
							}
							
							if (!$orig_order) {
								$return['errors'][] = array('message'=>'Order not found.','code'=>'ORDER_NOT_FOUND');
								continue;
							}
							
							API::add('Orders','delete',array($orig_order['id']));
							API::apiKey($api_key1);
							API::apiSignature($api_signature1);
							
							if (count($orders) == $i)
								API::apiUpdateNonce();
							
							$query = API::send($nonce1);
							$result = $query['Orders']['delete']['results'][0];
							
							if (!$result) {
								$return['errors'][] = array('message'=>'Order not found.','code'=>'ORDER_NOT_FOUND');
								continue;
							}
							
							$return['orders-cancel'][] = ($result) ? $result : array();
							$i++;
						}
					}
				}
				else {
					API::add('Orders','deleteAll');
					API::apiKey($api_key1);
					API::apiSignature($api_signature1);
					API::apiUpdateNonce();
					$query = API::send($nonce1);
					$return['orders-cancel'] = $query['Orders']['deleteAll']['results'][0];
				}
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
	else
		$return['errors'][] = array('message'=>'Invalid HTTP method.','code'=>'AUTH_INVALID_HTTP_METHOD');
}
elseif ($endpoint == 'orders/status') {
	if ($post) {
		if (!$invalid_signature && !$invalid_currency && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_view'] == 'Y') {
				$json = json_decode($_POST['orders'],1);
				if (is_array($_POST['orders']))
					$orders = $_POST['orders'];
				elseif (is_array($json))
					$orders = $json;
				else
					$orders[] = array('id'=>$_POST['id']);
				
				if (is_array($orders)) {
					$i = 1;
					foreach ($orders as $order) {
						$order['id'] = preg_replace("/[^0-9]/", "",$order['id']);
						
						API::add('Orders','getStatus',array($order['id']));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1);
						
						if (count($orders) == $i)
							API::apiUpdateNonce();
						
						$query = API::send($nonce1);
						$result = $query['Orders']['getStatus']['results'][0];
						
						if ($query['error']) {
							$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
							break;
						}
						
						if (!$result) {
							$return['errors'][] = array('message'=>'Order not found.','code'=>'ORDER_NOT_FOUND');
							continue;
						}
						
						$return['orders-status'][] = ($result) ? $result : array();
						$i++;
					}
				}
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature && !$invalid_currency)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
}
elseif ($endpoint == 'withdrawals/new') {
	if ($post) {
		if (!$invalid_signature && !$invalid_currency && $api_key1 && $nonce1 > 0) {
			if ($permissions['p_withdraw'] == 'Y') {
				$amount1 = preg_replace("/[^0-9.]/", "",$_POST['amount']);
				$address1 = ereg_replace("/[^\da-z]/i", "",$_POST['address']);
				$account1 = preg_replace("/[^0-9]/", "",$_POST['account_number']);
				
				if (!($amount1 > 0))
					$return['errors'][] = array('message'=>Lang::string('withdraw-amount-zero'),'code'=>'WITHDRAW_INVALID_AMOUNT');
				elseif (strtolower($currency1) == 'btc' && !$address1)
					$return['errors'][] = array('message'=>Lang::string('withdraw-address-invalid'),'code'=>'WITHDRAW_INVALID_ADDRESS');
				elseif (strtolower($currency1) != 'btc' && !$account1)
					$return['errors'][] = array('message'=>Lang::string('withdraw-no-account'),'code'=>'WITHDRAW_INVALID_ACCOUNT');
				else {				
					if (strtolower($currency1) == 'btc') {
						API::add('Status','get');
						API::add('User','getAvailable');
						API::add('BitcoinAddresses','validateAddress',array($address1));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1);
						$query = API::send($nonce1);
						$user_available = $query['User']['getAvailable']['results'][0];
						
						if ($query['Status']['get']['results'][0]['withdrawals_status'] == 'suspended') {
							$return['errors'][] = array('message'=>Lang::string('withdrawal-suspended'),'code'=>'WITHDRAWALS_SUSPENDED');
							$error = true;
						}
						elseif ($query['error']) {
							$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
							$error = true;
						}
						elseif ($amount1 > $user_available['BTC']) {
							$return['errors'][] = array('message'=>Lang::string('withdraw-too-much'),'code'=>'WITHDRAW_BALANCE_TOO_LOW');
							$error = true;
						}
						elseif (!$query['BitcoinAddresses']['validateAddress']['results'][0]) {
							$return['errors'][] = array('message'=>Lang::string('withdraw-address-invalid'),'code'=>'WITHDRAW_INVALID_ADDRESS');
							$error = true;
						}
					}
					else {
						API::add('BankAccounts','getRecord',array(false,$account1));
						API::add('BankAccounts','get',array($CFG->currencies[strtoupper($currency1)]['id']));
						API::add('User','getAvailable');
						API::add('Status','get');
						API::apiKey($api_key1);
						API::apiSignature($api_signature1);
						$query = API::send($nonce1);
						$bank_account = $query['BankAccounts']['getRecord']['results'][0];
						$bank_accounts = $query['BankAccounts']['get']['results'][0];
						$user_available = $query['User']['getAvailable']['results'][0];
						
						if ($query['Status']['get']['results'][0]['withdrawals_status'] == 'suspended') {
							$return['errors'][] = array('message'=>Lang::string('withdrawal-suspended'),'code'=>'WITHDRAWALS_SUSPENDED');
							$error = true;
						}
						elseif ($query['error']) {
							$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
							$error = true;
						}
						elseif (!is_array($bank_account)) {
							$return['errors'][] = array('message'=>Lang::string('withdraw-account-not-found'),'code'=>'WITHDRAW_INVALID_ACCOUNT');
							$error = true;
						}
						elseif (!$bank_accounts[$bank_account['account_number']]) {
							$return['errors'][] = array('message'=>Lang::string('withdraw-account-not-found'),'code'=>'WITHDRAW_INVALID_ACCOUNT');
							$error = true;
						}
						elseif ($amount1 > $user_available[strtoupper($currency1)]) {
							$return['errors'][] = array('message'=>Lang::string('withdraw-too-much'),'code'=>'WITHDRAW_BALANCE_TOO_LOW');
							$error = true;
						}
					}
					
					if (!$error) {
						API::add('Requests','insert',array((strtolower($currency1) == 'btc'),$CFG->currencies[strtoupper($currency1)]['id'],$amount1,$address1,$account1));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1);
						API::apiUpdateNonce();
						$query = API::send($nonce1);
	
						$return['withdraw'] = ($query['Requests']['insert']['results'][0]) ? $query['Requests']['insert']['results'][0] : array();
					}
				}
			}
			else
				$return['errors'][] = array('message'=>'Not authorized.','code'=>'AUTH_NOT_AUTHORIZED');
		}
		elseif (!$invalid_signature && !$invalid_currency)
			$return['errors'][] = array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR');
	}
}

if ($return)
	echo json_encode($return);


?>