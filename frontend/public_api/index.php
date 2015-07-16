<?php 
include '../lib/common.php';

$CFG->public_api = true;
$post = ($_SERVER['REQUEST_METHOD'] == 'POST');
$params_json = file_get_contents('php://input');

// check if params sent as payload or http params
if (!empty($params_json)) {
	$decoded = json_decode($params_json,1);
	
	if (!empty($decoded) && is_array($decoded))
		$_POST = $decoded;
	
	if (!empty($_REQUEST) && is_array($_REQUEST) && !empty($_POST) && is_array($_POST))
		$_REQUEST = array_merge($_REQUEST,$_POST);
	elseif (!empty($_POST))
		$_REQUEST = $_POST;
}
else {
	$params_json = json_encode($_POST,JSON_NUMERIC_CHECK);
}

$api_key1 = (!empty($_POST['api_key'])) ? preg_replace("/[^0-9a-zA-Z]/","",$_POST['api_key']) : false;
$api_signature1 = (!empty($_POST['signature'])) ? preg_replace("/[^0-9a-zA-Z]/","",$_POST['signature']) : false;
$nonce1 = (!empty($_POST['nonce'])) ? preg_replace("/[^0-9]/","",$_POST['nonce']) : false;
$CFG->language = (!empty($_POST['lang'])) ? preg_replace("/[^a-z]/","",$_POST['lang']) : 'en';
$currency1 = (!empty($_REQUEST['currency'])) ? preg_replace("/[^a-zA-Z]/","",$_REQUEST['currency']) : false;
$endpoint = $_REQUEST['endpoint'];

$invalid_signature = false;
$invalid_currency = false;

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
		$limit1 = (!empty($_REQUEST['limit'])) ? preg_replace("/[^0-9]/","",$_REQUEST['limit']) : 50;
		$limit1 = ($limit1 > 100) ? 100 : $limit1;
		
		API::add('Orders','get',array(false,false,$limit1,$currency1,false,false,1,false,false,false,false,1));
		API::add('Orders','get',array(false,false,$limit1,$currency1,false,false,false,false,1,false,false,1));
		$query = API::send();
		
		$return['order-book']['request_currency'] = strtoupper($currency1);
		$return['order-book']['bid'] = ($query['Orders']['get']['results'][0]) ? $query['Orders']['get']['results'][0] : array();
		$return['order-book']['ask'] = ($query['Orders']['get']['results'][1]) ? $query['Orders']['get']['results'][1] : array();
	}
}
elseif ($endpoint == 'transactions') {
	// currency filters transactions involving that particular currency
	if (!$invalid_currency) {
		$limit1 = (!empty($_REQUEST['limit'])) ? preg_replace("/[^0-9]/","",$_REQUEST['limit']) : false;
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
				API::add('User','getBalancesAndInfo');
				API::apiKey($api_key1);
				API::apiSignature($api_signature1,$params_json);
				API::apiUpdateNonce();
				$query = API::send($nonce1);
				

				if (empty($query['error'])) {
					$return['balances-and-info'] = $query['User']['getBalancesAndInfo']['results'][0];
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
				API::apiSignature($api_signature1,$params_json);
				API::apiUpdateNonce();
				$query = API::send($nonce1);
				
				if (empty($query['error'])) {
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
				$limit1 = (!empty($_REQUEST['limit'])) ? preg_replace("/[^0-9]/","",$_REQUEST['limit']) : false;
				$limit1 = (!$limit1) ? 10 : $limit1;
				$type1 = (!empty($_REQUEST['side'])) ? preg_replace("/[^a-zA-Z]/","",$_REQUEST['side']) : false;
				
				API::add('Transactions','get',array(false,false,$limit1,$currency1,1,false,strtolower($type1),false,false,1));
				API::apiKey($api_key1);
				API::apiSignature($api_signature1,$params_json);
				API::apiUpdateNonce();
				$query = API::send($nonce1);
				
				if (empty($query['error']))
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
				$limit1 = (!empty($_REQUEST['limit'])) ? preg_replace("/[^0-9]/","",$_REQUEST['limit']) : false;
				$limit1 = (!$limit1) ? 10 : $limit1;
				
				API::add('BitcoinAddresses','get',array(false,false,$limit1,false,false,false,1));
				API::apiKey($api_key1);
				API::apiSignature($api_signature1,$params_json);
				API::apiUpdateNonce();
				$query = API::send($nonce1);
				
				if (empty($query['error']))
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
				API::apiSignature($api_signature1,$params_json);
				$query = API::send($nonce1);
				$bitcoin_addresses = $query['BitcoinAddresses']['get']['results'][0];
	
				if (strtotime($bitcoin_addresses[0]['date']) >= strtotime('-1 day')) {
					$return['errors'][] = array('message'=>Lang::string('bitcoin-addresses-too-soon'),'code'=>'BTC_ADDRESS_TOO_SOON');
					$error = true;
				}
				
				if (empty($error)) {
					API::add('BitcoinAddresses','getNew',array(1));
					API::apiKey($api_key1);
					API::apiSignature($api_signature1,$params_json);
					API::apiUpdateNonce();
					$query = API::send($nonce1);
					
					if (empty($query['error']))
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
				$limit1 = (!empty($_REQUEST['limit'])) ? preg_replace("/[^0-9]/","",$_REQUEST['limit']) : false;
				$limit1 = (!$limit1) ? 10 : $limit1;
				$status1 = (!empty($_REQUEST['status'])) ? strtolower(preg_replace("/[^a-zA-Z]/","",$_REQUEST['status'])) : false;
				
				if ($status1 && ($status1 != 'pending' && $status1 != 'completed' && $status1 != 'cancelled')) {
					$return['errors'][] = array('message'=>'Invalid status.','code'=>'DEPOSIT_INVALID_STATUS');
					$error = true;
				}
				
				if (empty($error)) {
					API::add('Requests','get',array(false,false,$limit1,false,strtolower($currency1),$status1,1));
					API::apiKey($api_key1);
					API::apiSignature($api_signature1,$params_json);
					API::apiUpdateNonce();
					$query = API::send($nonce1);
					
					if (empty($query['error']))
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
				$limit1 = (!empty($_REQUEST['limit'])) ? preg_replace("/[^0-9]/","",$_REQUEST['limit']) : false;
				$limit1 = (!$limit1) ? 10 : $limit1;
				$status1 = (!empty($_REQUEST['status'])) ? strtolower(preg_replace("/[^a-zA-Z]/","",$_REQUEST['status'])) : false;
				
				if ($status1 && ($status1 != 'pending' && $status1 != 'completed' && $status1 != 'cancelled')) {
					$return['errors'][] = array('message'=>'Invalid status.','code'=>'DEPOSIT_INVALID_STATUS');
					$error = true;
				}
				
				API::add('Requests','get',array(false,false,$limit1,1,strtolower($currency1),$status1,1));
				API::apiKey($api_key1);
				API::apiSignature($api_signature1,$params_json);
				API::apiUpdateNonce();
				$query = API::send($nonce1);
					
				if (empty($query['error']))
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
	
				$json = (!empty($_POST['orders'])) ? json_decode($_POST['orders'],1) : false;
				if (!empty($_POST['orders']) && is_array($_POST['orders']))
					$orders = $_POST['orders'];
				elseif (is_array($json))
					$orders = $json;
				else
					$orders[] = array('side'=>((!empty($_POST['side'])) ? $_POST['side'] : false),'type'=>((!empty($_POST['type'])) ? $_POST['type'] : false),'currency'=>strtolower($currency1),'limit_price'=>((!empty($_POST['limit_price'])) ? $_POST['limit_price'] : false),'stop_price'=>((!empty($_POST['stop_price'])) ? $_POST['stop_price'] : false),'amount'=>((!empty($_POST['amount'])) ? $_POST['amount'] : false));
				
				if (is_array($orders)) {
					$i = 1;
					foreach ($orders as $order) {
						$order['side'] = (!empty($order['side'])) ? strtolower(preg_replace("/[^a-zA-Z]/","",$order['side'])) : false;
						$order['type'] = (!empty($order['type'])) ? strtolower(preg_replace("/[^a-zA-Z]/","",$order['type'])) : false;
						$order['currency'] = (!empty($order['currency'])) ? strtolower(preg_replace("/[^a-zA-Z]/","",$order['currency'])) : false;
						$order['limit_price'] = (!empty($order['limit_price'])) ? preg_replace("/[^0-9.]/", "",$order['limit_price']) : false;
						$order['stop_price'] = (!empty($order['type']) && $order['type'] == 'stop') ? preg_replace("/[^0-9.]/", "",$order['stop_price']) : false;
						$order['amount'] = (!empty($order['amount'])) ? preg_replace("/[^0-9.]/", "",$order['amount']) : false;
						
						// preliminary validation
						if ($CFG->trading_status == 'suspended') {
							$return['errors'][] = array('message'=>Lang::string('buy-trading-disabled'),'code'=>'TRADING_SUSPENDED');
							break;
						}
						elseif ($order['side'] != 'buy' && $order['side'] != 'sell') {
							$return['errors'][] = array('message'=>'Invalid order side (must be buy or sell).','code'=>'ORDER_INVALID_SIDE');
							continue;
						}
						elseif ($order['type'] != 'market' && $order['type'] != 'limit' && $order['type'] != 'stop') {
							$return['errors'][] = array('message'=>'Invalid order type (must be market, limit or stop).','code'=>'ORDER_INVALID_TYPE');
							continue;
						}
						elseif (empty($CFG->currencies[strtoupper($order['currency'])])) {
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
						
						API::add('Orders','executeOrder',array(($order['side'] == 'buy'),$order['limit_price'],$order['amount'],$order['currency'],false,($order['type'] == 'market'),false,false,false,$order['stop_price'],false,1));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1,$params_json);
						
						if (count($orders) == $i)
							API::apiUpdateNonce();
						
						$query = API::send($nonce1);
						$result = $query['Orders']['executeOrder']['results'][0];
						
						if ($result && empty($result['error'])) {
							unset($result['order_info']['comp_orig_prices']);
							unset($result['order_info']['replaced']);
							unset($result['edit_order']);
							unset($result['executed']);
							
							if ($order['limit_price'] > 0 && $order['stop_price'] > 0 && $order['type'] == 'stop')
								$result['order_info']['oco'] = true;
							
							$return['orders-new'][] = ($result) ? $result : array();
						}
						else
							$return['errors'][] = $result['error'];
						
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
				$json = (!empty($_POST['orders'])) ? json_decode($_POST['orders'],1) : false;
				if (!empty($_POST['orders']) && is_array($_POST['orders']))
					$orders = $_POST['orders'];
				elseif (is_array($json))
					$orders = $json;
				else
					$orders[] = array('id'=>((!empty($_POST['id'])) ? $_POST['id'] : false),'type'=>((!empty($_POST['type'])) ? $_POST['type'] : false),'limit_price'=>((!empty($_POST['limit_price'])) ? $_POST['limit_price'] : false),'stop_price'=>((!empty($_POST['stop_price'])) ? $_POST['stop_price'] : false),'amount'=>((!empty($_POST['amount'])) ? $_POST['amount'] : false));
					
				if (is_array($orders)) {
					$i = 1;
					foreach ($orders as $order) {
						$order['id'] = (!empty($order['id'])) ? preg_replace("/[^0-9]/", "",$order['id']) : false;
						$order['type'] = (!empty($order['type'])) ? strtolower(preg_replace("/[^a-zA-Z]/","",$order['type'])) : false;
						$order['limit_price'] = (!empty($order['limit_price'])) ? preg_replace("/[^0-9.]/", "",$order['limit_price']) : false;
						$order['stop_price'] = (!empty($order['stop_price'])) ? preg_replace("/[^0-9.]/", "",$order['stop_price']) : false;
						$order['amount'] = (!empty($order['amount'])) ? preg_replace("/[^0-9.]/", "",$order['amount']) : false;
						
						// preliminary validation
						if ($CFG->trading_status == 'suspended') {
							$return['errors'][] = array('message'=>Lang::string('buy-trading-disabled'),'code'=>'TRADING_SUSPENDED');
							break;
						}
						elseif (empty($order['id']) || !($order['id'] > 0)) {
							$return['errors'][] = array('message'=>'Invalid order id.','code'=>'ORDER_INVALID_ID');
							continue;
						}
						elseif ($order['type'] != 'market' && $order['type'] != 'limit' && $order['type'] != 'stop') {
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
							
						API::add('Orders','executeOrder',array(false,$order['limit_price'],$order['amount'],false,false,($order['type'] == 'market'),$order['id'],false,false,$order['stop_price'],false,1));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1,$params_json);
						
						if (count($orders) == $i)
							API::apiUpdateNonce();
						
						$query = API::send($nonce1);
						$result = $query['Orders']['executeOrder']['results'][0];
							
						if ($result && empty($result['error'])) {
							unset($result['order_info']['comp_orig_prices']);
							unset($result['new_order']);
							unset($result['executed']);
							
							if ($order['limit_price'] > 0 && $order['stop_price'] > 0 && $order['type'] == 'stop')
								$result['order_info']['oco'] = true;
							
							$return['orders-edit'][] = ($result) ? $result : array();
						}
						else
							$return['errors'][] = $result['error'];
							
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
				if (empty($_POST['all'])) {
					$json = (!empty($_POST['orders'])) ? json_decode($_POST['orders'],1) : false;
					if (!empty($_POST['orders']) && is_array($_POST['orders']))
						$orders = $_POST['orders'];
					elseif (is_array($json))
						$orders = $json;
					else
						$orders[] = array('id'=>((!empty($_POST['id'])) ? $_POST['id'] : false));
			
					if (is_array($orders)) {
						$i = 1;
						foreach ($orders as $order) {
							if ($CFG->trading_status == 'suspended') {
								$return['errors'][] = array('message'=>Lang::string('buy-trading-disabled'),'code'=>'TRADING_SUSPENDED');
								break;
							}
							
							$order['id'] = (!empty($order['id'])) ? preg_replace("/[^0-9]/", "",$order['id']) : false;
							API::add('Orders','delete',array(false,$order['id']));
							API::apiKey($api_key1);
							API::apiSignature($api_signature1,$params_json);
							
							if (count($orders) == $i)
								API::apiUpdateNonce();
							
							$query = API::send($nonce1);
							$result = $query['Orders']['delete']['results'][0];
							
							if (empty($result)) {
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
					API::apiSignature($api_signature1,$params_json);
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
				$json = (!empty($_POST['orders'])) ? json_decode($_POST['orders'],1) : false;
				if (!empty($_POST['orders']) && is_array($_POST['orders']))
					$orders = $_POST['orders'];
				elseif (is_array($json))
					$orders = $json;
				else
					$orders[] = array('id'=>((!empty($_POST['id'])) ? $_POST['id'] : false));
				
				if (is_array($orders)) {
					$i = 1;
					foreach ($orders as $order) {
						$order['id'] = (!empty($order['id'])) ? preg_replace("/[^0-9]/", "",$order['id']) : false;
						
						API::add('Orders','getStatus',array($order['id']));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1,$params_json);
						
						if (count($orders) == $i)
							API::apiUpdateNonce();
						
						$query = API::send($nonce1);
						$result = $query['Orders']['getStatus']['results'][0];
						
						if (!empty($query['error'])) {
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
				$amount1 = (!empty($_POST['amount'])) ? preg_replace("/[^0-9.]/", "",$_POST['amount']) : false;
				$address1 = (!empty($_POST['address'])) ? preg_replace("/[^\da-z]/i", "",$_POST['address']) : false;
				$account1 = (!empty($_POST['account_number'])) ? preg_replace("/[^0-9]/", "",$_POST['account_number']) : false;
				
				if (!($amount1 > 0))
					$return['errors'][] = array('message'=>Lang::string('withdraw-amount-zero'),'code'=>'WITHDRAW_INVALID_AMOUNT');
				elseif (strtolower($currency1) == 'btc' && !$address1)
					$return['errors'][] = array('message'=>Lang::string('withdraw-address-invalid'),'code'=>'WITHDRAW_INVALID_ADDRESS');
				elseif (strtolower($currency1) != 'btc' && !$account1)
					$return['errors'][] = array('message'=>Lang::string('withdraw-no-account'),'code'=>'WITHDRAW_INVALID_ACCOUNT');
				else {				
					if (strtolower($currency1) == 'btc') {
						API::add('User','getAvailable');
						API::add('BitcoinAddresses','validateAddress',array($address1));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1,$params_json);
						$query = API::send($nonce1);
						$user_available = $query['User']['getAvailable']['results'][0];
						
						if ($CFG->withdrawals_status == 'suspended') {
							$return['errors'][] = array('message'=>Lang::string('withdrawal-suspended'),'code'=>'WITHDRAWALS_SUSPENDED');
							$error = true;
						}
						elseif (!empty($query['error'])) {
							$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
							$error = true;
						}
						elseif ($amount1 > $user_available['BTC']) {
							$return['errors'][] = array('message'=>Lang::string('withdraw-too-much'),'code'=>'WITHDRAW_BALANCE_TOO_LOW');
							$error = true;
						}
						elseif (empty($query['BitcoinAddresses']['validateAddress']['results'][0])) {
							$return['errors'][] = array('message'=>Lang::string('withdraw-address-invalid'),'code'=>'WITHDRAW_INVALID_ADDRESS');
							$error = true;
						}
					}
					else {
						API::add('BankAccounts','getRecord',array(false,$account1));
						API::add('BankAccounts','get',array($CFG->currencies[strtoupper($currency1)]['id']));
						API::add('User','getAvailable');
						API::apiKey($api_key1);
						API::apiSignature($api_signature1,$params_json);
						$query = API::send($nonce1);
						$bank_account = $query['BankAccounts']['getRecord']['results'][0];
						$bank_accounts = $query['BankAccounts']['get']['results'][0];
						$user_available = $query['User']['getAvailable']['results'][0];
						
						if ($CFG->withdrawals_status == 'suspended') {
							$return['errors'][] = array('message'=>Lang::string('withdrawal-suspended'),'code'=>'WITHDRAWALS_SUSPENDED');
							$error = true;
						}
						elseif (!empty($query['error'])) {
							$return['errors'][] = array('message'=>'Invalid authentication.','code'=>$query['error']);
							$error = true;
						}
						elseif (!is_array($bank_account)) {
							$return['errors'][] = array('message'=>Lang::string('withdraw-account-not-found'),'code'=>'WITHDRAW_INVALID_ACCOUNT');
							$error = true;
						}
						elseif (empty($bank_accounts[$bank_account['account_number']])) {
							$return['errors'][] = array('message'=>Lang::string('withdraw-account-not-found'),'code'=>'WITHDRAW_INVALID_ACCOUNT');
							$error = true;
						}
						elseif ($amount1 > $user_available[strtoupper($currency1)]) {
							$return['errors'][] = array('message'=>Lang::string('withdraw-too-much'),'code'=>'WITHDRAW_BALANCE_TOO_LOW');
							$error = true;
						}
					}
					
					if (empty($error)) {
						API::add('Requests','insert',array((strtolower($currency1) == 'btc'),$CFG->currencies[strtoupper($currency1)]['id'],$amount1,$address1,$account1));
						API::apiKey($api_key1);
						API::apiSignature($api_signature1,$params_json);
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

if (!empty($return))
	echo json_encode($return);


?>