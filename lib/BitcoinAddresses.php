<?php
class BitcoinAddresses{
	function get($count=false,$page=false,$per_page=false,$user=false,$unassigned=false,$system=false) {
		$page = mysql_real_escape_string($page);
		$page = ($page > 0) ? $page - 1 : 0;
		$r1 = $page * $per_page;
		
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
	
	function getAddress($address) {
		$sql = "SELECT id, site_user,`date` FROM bitcoin_addresses WHERE address = '$address' ";
		$result = db_query_array($sql);
		return $result[0];
	}
	
	function getBalance() {
		$sql = "SELECT SUM(balance) AS balance FROM bitcoin_addresses WHERE confirmed = 'Y' ";
		$result = db_query_array($sql);
		return $result[0]['balance'];
	}
	
	function getBitcoindBalance($bitcoin) {
		$accounts = $bitcoin->listaccounts(3);
		$total = 0;
		if (is_array($accounts)) {
			foreach ($accounts as $account) {
				$total += $account;
			}
		}
		return $total;
	}
	
	function cheapsweep($bitcoin,$destination) {
		global $CFG;
		
		if (!$destination || !$bitcoin)
			return false;
		
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
			$response = shell_exec('cd '.$CFG->bitcoin_directory.' && ./cheapsweap -d '.$destination.' '.$address_str);
			return $response;
		}
	}
	
	function getHotWallet() {
		$sql = "SELECT * FROM bitcoin_addresses WHERE system_address = 'Y' AND hot_wallet = 'Y' ORDER BY `date` ASC LIMIT 0,1";
		$result = db_query_array($sql);
		return $result[0];
	}
	
	function getWarmWallet() {
		$sql = "SELECT * FROM bitcoin_addresses WHERE system_address = 'Y' AND warm_wallet = 'Y' LIMIT 0,1";
		$result = db_query_array($sql);
		return $result[0];
	}
}