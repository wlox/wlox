<?php
class Paypal {
	public static $item_count;
	
	function receivePayment() {
		global $CFG;
		
		if (empty($_REQUEST['tx']))
			return false;
		
		$auth_info = Link::executeScript($CFG->paypal_submit_url,array('tx'=>$_REQUEST['tx'],'at'=>$CFG->paypal_tocken,'cmd' => '_notify-synch'));
		$is_approved = stristr($auth_info,'SUCCESS');
		
		if ($is_approved) {
			$auth_info = str_ireplace('SUCCESS','',$auth_info);
			Messages::add($CFG->paypal_success_message);
			self::$item_count = mb_substr_count($auth_info,'item_number');
			return self::parseInfo($auth_info);
		}
		else {
			Errors::add($CFG->paypal_failure_message);
			return false;
		}
	}
	
	function parseInfo($info) {
		if (!empty($info)) {
			$p_variables = array();
			$p1 = explode("\n",$info);
			foreach ($p1 as $value) {
				$p2 = explode('=',$value);
				$key = $p2[0];
				if (!empty($key))
					$p_variables[$key] = $p2[1];
			}
		}
		return $p_variables;
	}
}
?>