<?php
class Auction {
	public $id,$item_info,$time_remaining,$is_expired,$errors,$messages,$high_bid,$high_bid_user_id,$initial_bid;
	private $proxy_bids,$table,$minimum_increase,$anti_sniping,$anti_sniping_increase,$now;
	
	function __construct($id,$table,$minimum_increase=false,$place_proxy_bids=false,$anti_sniping_window=false,$anti_sniping_increase=false,$initial_bid=false) {
		$this->id = $id;
		$this->table = $table;
		$this->proxy_bids = $place_proxy_bids;
		$this->anti_sniping = $anti_sniping_window;
		$this->anti_sniping_increase = $anti_sniping_increase;
		$this->minimum_increase = ($minimum_increase) ? $minimum_increase : 0.01;
		$this->initial_bid = $initial_bid;
		$this->item_info = DB::getRecord($this->table,$this->id,false,true);
		$this->time_remaining = Auction::getTimeRemaining();
		$this->is_expired = ($this->time_remaining <= 0);
		$this->high_bid = $this->item_info['high_bid'];
		$this->high_bid_user_id = $this->item_info['high_bid_user_id'];
		$this->now = date('Y-m-d H:i:s',time());
	}
	
	function getHighestProxy($user_id=0) {
		$sql = "SELECT amount,user_id FROM {$this->table}_bids WHERE item_id = {$this->id} AND item_table = '{$this->table}' ";
		if ($user_id > 0) {
			$sql .= " AND user_id = $user_id ";
		}
		$sql .= " ORDER BY amount DESC LIMIT 0,1";
		$result = db_query_array($sql);
		
		return $result[0];
	}
	
	function getTimeRemaining() {
		$diff = Settings::mysqlTimeDiff();
		return (strtotime($this->item_info['exp_date']) - time()) + 3600 + ($diff * 3600);
	}
	
	function addTime($seconds) {
		$sql = "UPDATE {$this->table} SET exp_date = (exp_date + INTERVAL {$seconds} SECOND) WHERE id = {$this->id}";
		$result = db_query($sql);
		$this->time_remaining = $this->time_remaining + $seconds;
		return $result;
	}
	
	function realizeBids($amount) {
		$sql = "UPDATE {$this->table}_bids SET is_realized = 'Y' WHERE is_realized = 'N' AND item_id = {$this->id} AND amount <= $amount";
		return db_query($sql);
	}
	
	function bid($amount) {
		global $CFG;
		
		date_default_timezone_set($CFG->default_timezone);
		
		if (!($this->id > 0))
			return false;
		
		if ($this->is_expired)
			return false;
		
		if (!is_numeric($amount)) {
			$this->errors[] = $CFG->auction_invalid_bid_error;
			return false;
		}
		
		if (User::isLoggedIn()) {
			if (DB::tableExists($this->table.'_bids')) {
				$user_id = User::$info['id'];
				$this->minimum_increase = (!($this->high_bid > 0)) ? $this->initial_bid : $this->minimum_increase;
				
				if ($amount >= ($this->item_info['high_bid'] + $this->minimum_increase) || ($this->high_bid_user_id == User::$info['id'] && $amount > $this->item_info['high_bid'])) {
					if ($this->proxy_bids) {
						$proxy = Auction::getHighestProxy();
						
						if ($this->high_bid_user_id != User::$info['id']) {
							if ($amount <= $proxy['amount']) {
								$proxy_increase = (($amount + $this->minimum_increase) <= $proxy['amount']) ? $amount + $this->minimum_increase : $proxy['amount'];
								
								DB::insert($this->table.'_bids',array('item_id'=>$this->id,'item_table'=>$this->table,'user_id'=>$user_id,'amount'=>$amount,'date'=>$this->now));
								DB::insert($this->table.'_bids',array('item_id'=>$this->id,'item_table'=>$this->table,'user_id'=>$this->high_bid_user_id,'amount'=>$proxy_increase,'is_proxy'=>'Y','date'=>$this->now));
								DB::update($this->table,array('high_bid'=>($proxy_increase)),$this->id);
								//Auction::realizeBids($proxy_increase);
								$this->high_bid = $proxy_increase;
								$outbid = true;
							}
							elseif ($amount > $proxy['amount'] && $amount < ($proxy['amount'] + $this->minimum_increase) && $proxy['user_id'] != $user_id) {
								$this->errors[] = str_ireplace('[field]',$this->minimum_increase,$CFG->auction_min_increase_error);
								
								DB::insert($this->table.'_bids',array('item_id'=>$this->id,'item_table'=>$this->table,'user_id'=>$user_id,'amount'=>$amount,'date'=>$this->now));
								DB::insert($this->table.'_bids',array('item_id'=>$this->id,'item_table'=>$this->table,'user_id'=>$this->high_bid_user_id,'amount'=>$proxy['amount'],'is_proxy'=>'Y','date'=>$this->now));
								DB::update($this->table,array('high_bid'=>($proxy['amount'])),$this->id);
								//Auction::realizeBids($proxy['amount']);
								$this->high_bid = $proxy['amount'];
								$outbid = true;
							}
							else {
								if ($proxy['amount'] > $this->high_bid) {
									if ($proxy['amount'] > $this->high_bid + $this->minimum_increase)
										$this->high_bid = $proxy['amount'];
								}
								
								if ($amount > ($proxy['amount'] + $this->minimum_increase)) {
									DB::insert($this->table.'_bids',array('item_id'=>$this->id,'item_table'=>$this->table,'user_id'=>$user_id,'amount'=>$amount,'is_proxy'=>'N','is_realized'=>'N','date'=>$this->now));
									$this->high_bid = $proxy['amount'];
								}
							}
						}
						else {
							if ($amount < $proxy['amount']) {
								$this->errors[] = str_ireplace('[field]',$amount,$CFG->auction_outbid_self_proxy_error);
								$outbid = true;
							}
							else {
								$this->messages[] = str_ireplace('[field]',$amount,$CFG->auction_new_proxy_message);
								DB::insert($this->table.'_bids',array('item_id'=>$this->id,'item_table'=>$this->table,'user_id'=>$user_id,'amount'=>$amount,'is_proxy'=>'N','is_realized'=>'N','date'=>$this->now));
								$bypass = true;
							}
						}
					}
					
					if ($this->anti_sniping && $this->anti_sniping_increase) {
						if ($this->time_remaining < $this->anti_sniping) {
							if (!($this->proxy_bids && $this->high_bid_user_id == User::$info['id']))
								Auction::addTime($this->anti_sniping_increase);
						}
					}

					//DB::insert($this->table.'_bids',array('item_id'=>$15this->id,'item_table'=>$this->table,'user_id'=>$user_id,'amount'=>$amount));
					if (!$outbid) {
						$new_bid = ($this->proxy_bids) ? $this->high_bid + $this->minimum_increase : $amount;
						$new_bid = ($this->proxy_bids && $this->high_bid_user_id == $user_id) ? $this->high_bid : $new_bid;
						//$new_bid = (!($this->high_bid > 0)) ? $amount : $new_bid;
						$this->high_bid = $new_bid;
						$this->high_bid_user_id = $user_id;
						$is_proxy = ($this->proxy_bids && ($amount > ($this->high_bid + $this->minimum_increase))) ? 'Y' : 'N';
						
						Auction::realizeBids($new_bid);
						
						if (!$bypass)
							DB::insert($this->table.'_bids',array('item_id'=>$this->id,'item_table'=>$this->table,'user_id'=>$user_id,'amount'=>$new_bid,'is_proxy'=>$is_proxy,'date'=>$this->now));
						
						DB::update($this->table,array('high_bid'=>$new_bid,'high_bid_user_id'=>$user_id),$this->id);
						$this->messages[] = $CFG->auction_high_bid_message;
						return true;
					}
					else {
						if (!is_array($this->errors))
							$this->errors[] = $CFG->auction_outbid_error;
							
						return false;
					}
				}
				elseif ($amount >= $this->item_info['high_bid'] && $amount < ($this->item_info['high_bid'] + $this->minimum_increase)) {
					$this->errors[] = str_ireplace('[field]',$this->minimum_increase,$CFG->auction_min_increase_error);
					return false;
				}
				elseif ($amount < $this->item_info['high_bid'] && $this->item_info['high_bid_user_id'] != $user_id) {
					$this->errors[] = str_ireplace('[field]',$this->high_bid,$CFG->auction_bid_too_low_error);
					return false;
				}
				elseif ($amount < $this->item_info['high_bid'] && $this->item_info['high_bid_user_id'] == $user_id) {
					$this->errors[] = $CFG->auction_outbid_self_error;
					return false;
				}
			}
			else {
				$this->errors[] = $CFG->auction_table_error;
			}
		}
		else {
			$this->errors[] = $CFG->auction_login_error;
		}
	}
}
?>