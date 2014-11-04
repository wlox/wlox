<?php 
class Competition{
	function get() {
		global $CFG;
		
		$me = (User::$info['id'] > 0) ? ', IF('.User::$info['id'].' = site_users.id,1,0) AS me' : '';
		
		$currencies[] = '(site_users.btc * currencies.usd_ask)';
		foreach ($CFG->currencies as $currency) {
			$currencies[] = '(site_users.'.strtolower($currency['currency']).' * '.$currency['usd_ask'].')';
		}
		$currency_str = ', ('.implode(' + ',$currencies).' - 100000) AS usd_gain ';
		
		$sql = "SELECT LOWER(iso_countries.code) AS country, UPPER(LEFT(site_users.first_name,1)) AS f, UPPER(LEFT(site_users.last_name,1)) AS l $currency_str $me FROM site_users
				LEFT JOIN currencies ON (currencies.id = {$CFG->btc_currency_id})
				LEFT JOIN iso_countries ON (site_users.country = iso_countries.id)
				ORDER BY usd_gain DESC LIMIT 0,10";
		
		return db_query_array($sql);
	}
	
	function getUserRank() {
		global $CFG;
		
		if (!(User::$info['id'] > 0))
			return false;
		
		foreach ($CFG->currencies as $currency) {
			$currencies[] = '(site_users.'.strtolower($currency['currency']).' * '.$currency['usd_ask'].')';
		}
		$currency_str = '((site_users.btc * currencies.usd_ask) + '.implode(' + ',$currencies).' - 100000) ';
		$currency_str1 = '((s1.btc * currencies.usd_ask) + '.str_replace('site_users','s1',implode(' + ',$currencies)).' - 100000)';
		
		$sql = "SELECT LOWER(iso_countries.code) AS country, UPPER(LEFT(site_users.first_name,1)) AS f, UPPER(LEFT(site_users.last_name,1)) AS l ,$currency_str AS usd_gain FROM site_users
		LEFT JOIN currencies ON (currencies.id = {$CFG->btc_currency_id})
		LEFT JOIN iso_countries ON (site_users.country = iso_countries.id)
		WHERE site_users.id = ".User::$info['id']." LIMIT 0,1";
		$result = db_query_array($sql);
		$return = $result[0];
		
		$sql = "SELECT COUNT(DISTINCT $currency_str) AS rank FROM site_users LEFT JOIN currencies ON (currencies.id = {$CFG->btc_currency_id}) WHERE $currency_str >= {$return['usd_gain']}";
		$result = db_query_array($sql);
		$return['rank'] = $result[0]['rank'];
		return $return;
	}
}

?>