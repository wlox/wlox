<?php 
class APIKeys {
	public static function get() {
		global $CFG;
		
		if (!$CFG->session_active || $CFG->session_locked)
			return false;
		
		$sql = "SELECT id, `key`, `view`, orders, withdraw FROM api_keys WHERE site_user = ".User::$info['id'];
		return db_query_array($sql);
	}
	
	public static function edit($ids_array) {
		global $CFG;
		
		if (!$CFG->session_active || $CFG->session_locked || !is_array($ids_array) || !$CFG->token_verified)
			return false;
		
		foreach ($ids_array as $id => $permissions) {
			$id = preg_replace("/[^0-9]/", "",$id);
			if (!($id > 0))
				continue;
			
			$existing = DB::getRecord('api_keys',$id,0,1);
			if (!$existing || $existing['site_user'] != User::$info['id'])
				continue;
			
			db_update('api_keys',$id,array('view'=>($permissions['view'] == 'Y' ? 'Y' : 'N'),'orders'=>($permissions['orders'] == 'Y' ? 'Y' : 'N'),'withdraw'=>($permissions['withdraw'] == 'Y' ? 'Y' : 'N')));
		}
	}
	
	public static function add() {
		global $CFG;
		
		if (!$CFG->session_active || $CFG->session_locked || !$CFG->token_verified)
			return false;
		
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$key = substr(str_shuffle($chars),0,16);
		$secret = substr(str_shuffle($chars),0,32);
		
		$sql = 'SELECT id FROM api_keys WHERE api_keys.key = \''.$key.'\'';
		$exists = db_query_array($sql);
		if ($exists)
			return false;
		
		db_insert('api_keys',array('key'=>$key,'secret'=>$secret,'site_user'=>User::$info['id'],'view'=>'Y','orders'=>'Y','withdraw'=>'Y'));
		return $secret;
	}
	
	public static function delete($remove_id) {
		global $CFG;
	
		$remove_id = preg_replace("/[^0-9]/", "",$remove_id);
		if (!$CFG->session_active || $CFG->session_locked || !($remove_id > 0) || !$CFG->token_verified)
			return false;
		
		$existing = DB::getRecord('api_keys',$remove_id,0,1);
		if (!$existing || $existing['site_user'] != User::$info['id'])
			continue;
		
		return db_delete('api_keys',$remove_id);
	}
	
	public static function hasPermission($api_key) {
		global $CFG;
		
		if ($CFG->memcached) {
			$cached = $CFG->m->get('api_'.$api_key.'_p');
			if ($cached)
				return $cached;
		}
		
		$sql = 'SELECT api_keys.view AS p_view, api_keys.orders AS p_orders, api_keys.withdraw AS p_withdraw FROM api_keys WHERE api_keys.key = "'.$api_key.'"';
		$result = db_query_array($sql);

		if ($result) {
			if ($CFG->memcached)
				$CFG->m->set('api_'.$api_key.'_p',$result[0],300);
			
			return $result[0];
		}
		else
			return array('p_view'=>'Y','p_orders'=>'Y','p_withdraw'=>'Y');
	}
}
?>
