<?php
class BitcoinAddresses{
	static $bitcoin;
	
	public static function get($count=false,$page=false,$per_page=false,$user=false,$unassigned=false,$system=false) {
		global $CFG;
		
		$page = ($page > 0) ? $page - 1 : 0;
		$r1 = $page * $per_page;
		$user = User::$info['id'];
		
		if (!$count)
			$sql = "SELECT * FROM bitcoin_addresses WHERE 1 ";
		else
			$sql = "SELECT COUNT(id) AS total FROM bitcoin_addresses WHERE 1  ";
		
		if ($user > 0)
			$sql .= " AND site_user = $user ";
		
		if ($unassigned)
			$sql .= " AND site_user = 0 ";
		
		if ($system)
			$sql .= " AND system_address = 'Y' ";
		else
			$sql .= " AND system_address != 'Y' ";
		
		if ($per_page > 0 && !$count)
			$sql .= " ORDER BY bitcoin_addresses.date DESC LIMIT $r1,$per_page ";
		
		$result = db_query_array($sql);
		if (!$count)
			return $result;
		else
			return $result[0]['total'];
	}
	
	public static function getNew($hot_wallet=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		if (!self::$bitcoin) {
			require_once('easybitcoin.php');
			$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
		}
		else
			$bitcoin = self::$bitcoin;
		
		$new_address = $bitcoin->getnewaddress($CFG->bitcoin_accountname);
		
		if (!$hot_wallet)
			$new_id = db_insert('bitcoin_addresses',array('address'=>$new_address,'site_user'=>User::$info['id'],'date'=>date('Y-m-d H:i:s')));
		else
			$new_id = db_insert('bitcoin_addresses',array('address'=>$new_address,'date'=>date('Y-m-d H:i:s'),'hot_wallet'=>'Y','system_address'=>'Y'));
		
		return $new_id;
	}
	
	public static function getAddress($address) {
		global $CFG;
		
		$sql = "SELECT bitcoin_addresses.id, bitcoin_addresses.site_user,bitcoin_addresses.date,bitcoin_addresses.system_address, bitcoin_addresses.hot_wallet, site_users.trusted, site_users.first_name, site_users.last_name, site_users.notify_deposit_btc FROM bitcoin_addresses LEFT JOIN site_users ON (site_users.id = bitcoin_addresses.site_user) WHERE bitcoin_addresses.address = '$address' LIMIT 0,1";
		$result = db_query_array($sql);
		return $result[0];
	}
	
	public static function getBalance() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$sql = "SELECT SUM(balance) AS balance FROM bitcoin_addresses WHERE confirmed = 'Y' ";
		$result = db_query_array($sql);
		return $result[0]['balance'];
	}
	
	public static function getBitcoindBalance() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		if (!self::$bitcoin) {
			require_once('easybitcoin.php');
			$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
		}
		else
			$bitcoin = self::$bitcoin;
		
		$accounts = $bitcoin->listaccounts(3);
		$total = 0;
		if (is_array($accounts)) {
			foreach ($accounts as $account) {
				$total += $account;
			}
		}
		return $total;
	}
	
	public static function cheapsweep($destination) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$destination = preg_replace("/[^0-9a-zA-Z]/",'',$destination);
		if (!$destination)
			return false;
		
		if (!self::$bitcoin) {
			require_once('easybitcoin.php');
			$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
		}
		else
			$bitcoin = self::$bitcoin;
		
		$bitcoin->walletpassphrase($CFG->bitcoin_passphrase,3);
		$addresses1 = $bitcoin->listaddressgroupings();
		if ($addresses1) {
			foreach ($addresses1 as $address1) {
				if (is_array($address1)) {
					foreach ($address1 as $address2) {
						if (!($address2[1] > 0) || $address2[0] == $destination)
							continue;
							
						$addresses[] = $address2[0];
					}
				}
			}
		}
		
		if ($addresses) {
			$address_str = implode(' ', $addresses);
			$response = shell_exec('cd '.$CFG->dirroot.'lib/ && ./cheapsweap -d '.$destination.' '.$address_str);
			return $response;
		}
	}
	
	public static function getHotWallet() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$sql = "SELECT * FROM bitcoin_addresses WHERE system_address = 'Y' AND hot_wallet = 'Y' ORDER BY `date` ASC LIMIT 0,1";
		$result = db_query_array($sql);
		if ($result[0])
			return $result[0];
		else {
			$new_id = self::getNew(1);
			return DB::getRecord('bitcoin_addresses',$new_id,0,1);
		}
			
	}
	
	public static function getWarmWallet() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$sql = "SELECT * FROM bitcoin_addresses WHERE system_address = 'Y' AND warm_wallet = 'Y' LIMIT 0,1";
		$result = db_query_array($sql);
		return $result[0];
	}
	
	public static function validateAddress($btc_address) {
		global $CFG;
		
		$btc_address = preg_replace("/[^0-9a-zA-Z]/",'',$btc_address);
		
		if (!$btc_address)
			return false;
	
		if (!self::$bitcoin) {
			require_once('easybitcoin.php');
			$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
		}
		else
			$bitcoin = self::$bitcoin;
		
		$response = $bitcoin->validateaddress($btc_address);
	
		if (!$response['isvalid'] || !is_array($response))
			return false;
		else
			return true;
	}
}