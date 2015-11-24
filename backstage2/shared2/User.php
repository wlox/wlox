<?php
class User {
	public $user,$pass,$errors;
	private static $logged_in,$session_name;
	public static $info;
	
	public static function logIn($user=false,$pass=false,$table=false,$session_name=false) {
		global $CFG;
		
		$user = strip_tags(mysql_real_escape_string($user));
		$pass = strip_tags(mysql_real_escape_string($pass));
		$table = ($table) ? $table : 'admin';
		$session_name = ($session_name) ? $session_name : 'user_info';
		self::$session_name = $session_name;
		

		if (empty($user) && empty($pass) && !$_SESSION[$session_name]['user']) {
			return false;
		}

		if (!empty($user) || !empty($pass)) {
			if (empty($user)) {
				Errors::add($CFG->login_empty_user);
				return false;
			}

			if (empty($pass)) {
				Errors::add($CFG->login_empty_pass);
				return false;
			}
		}
		
		if (empty($CFG->dbname)) {
			Errors::add($CFG->no_database_error);
			return false;
		}

		$user = ($user) ? trim($user) : $_SESSION[$session_name]['user'];
		$pass = ($pass) ? trim($pass) : $_SESSION[$session_name]['pass'];

		if (empty($user) || empty($pass))
			return false;
		
		if (!(User::verify($user,$pass,$table))) {
			Errors::add($CFG->login_invalid);
			User::logOut(1);
			return false;
		}

		
		$_SESSION[$session_name] = User::getInfo($user,$table);
		
		self::$logged_in = true;
		self::$info = $_SESSION[$session_name];
		return true;
	}
	
	public static function isLoggedIn() {
		return self::$logged_in;
	}
	
	public static function logOut($logout) {
		if ($logout) {
			unset($_SESSION[self::$session_name]);
			unset($_SESSION['user_info']);
			unset($_SESSION['token_verified']);
			self::$logged_in = false;
			self::$info = false;
		}
	}
	
	public static function verify($user,$pass,$table=false) {
		global $CFG;

		if (empty($user) || empty($pass))
			return false;
			
		if (String::sanitize($user,true,true)) {
			return false;
		}
			
		if (String::sanitize($pass,true,true)) {
			return false;
		}
		
		$table = ($table) ? $table : 'admin';
		return db_query_array("SELECT user FROM {$table}_users WHERE user = '$user' AND pass = '$pass' ");
	}
	
	public static function getInfo($user,$table=false) {
		if (String::sanitize($user,true,true))
			return false;
		
		$table = ($table) ? $table : 'admin';
		
		$result = db_query_array("SELECT * FROM {$table}_users WHERE user = '$user'");
		return $result[0];
	}
	
	public static function permission($page_id=0,$tab_id=0,$page_url=false,$table=false,$is_tab=false) {
		global $CFG;

		if (!$CFG->backstage_mode)
			return 2;
		if (!$page_id && !$tab_id && !$page_url)
			return 2;
		if ($_SESSION[self::$session_name]['is_admin'] == 'Y')
			return 2;
		if ($page_url == 'index.php')
			return 2;
		$table = ($table) ? $table : 'admin';
		$is_tab = ($page_url == $CFG->url) ? $CFG->is_tab : $is_tab;
		
		if ($page_id > 0) {
			$sql = "
				SELECT {$table}_groups_pages.permission FROM {$table}_users
				LEFT JOIN {$table}_groups ON ({$table}_users.f_id = {$table}_groups.id)
				LEFT JOIN {$table}_groups_pages ON ({$table}_groups.id = {$table}_groups_pages.group_id)
				WHERE {$table}_groups_pages.page_id = $page_id AND {$table}_users.id = '{$_SESSION['user_info']['id']}'
				";
		}
		elseif ($tab_id > 0) {
			$sql = "
				SELECT {$table}_groups_tabs.permission FROM {$table}_users
				LEFT JOIN {$table}_groups ON ({$table}_users.f_id = {$table}_groups.id)
				LEFT JOIN {$table}_groups_tabs ON ({$table}_groups.id = {$table}_groups_tabs.group_id)
				WHERE {$table}_groups_tabs.tab_id = $tab_id AND {$table}_users.id = '{$_SESSION['user_info']['id']}'
				";
		}
		elseif ($page_url) {
			if ($is_tab) {
				$sql = "
					SELECT {$table}_groups_tabs.permission FROM {$table}_users
					LEFT JOIN {$table}_groups ON ({$table}_users.f_id = {$table}_groups.id)
					LEFT JOIN {$table}_groups_tabs ON ({$table}_groups.id = {$table}_groups_tabs.group_id)
					LEFT JOIN {$table}_tabs ON ({$table}_groups_tabs.tab_id = {$table}_tabs.id)
					WHERE {$table}_tabs.url = '$page_url' AND {$table}_users.id = '{$_SESSION['user_info']['id']}'";
			}
			else {
				$sql = "
					SELECT {$table}_groups_pages.permission FROM {$table}_users
					LEFT JOIN {$table}_groups ON ({$table}_users.f_id = {$table}_groups.id)
					LEFT JOIN {$table}_groups_pages ON ({$table}_groups.id = {$table}_groups_pages.group_id)
					LEFT JOIN {$table}_pages ON ({$table}_groups_pages.page_id = {$table}_pages.id)
					WHERE {$table}_pages.url = '$page_url' AND {$table}_users.id = '{$_SESSION['user_info']['id']}'";
			}
		}
		
		$result = db_query_array($sql);
		return $result[0]['permission'];
	}
	
	public static function getIP() {
	    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
	      $ip = $_SERVER['HTTP_CLIENT_IP'];
	    }
	    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    }
	    else {
	      $ip = $_SERVER['REMOTE_ADDR'];
	    }
	    return $ip;
	}
}
?>