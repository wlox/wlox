<?php

// needs for variable to be called $ccform
class Credicorp {
	public static $auth_info;
	
	function verifyData($variables) {
		global $CFG,$ccform;
		
		if (is_array($variables)) {
			foreach ($variables as $key => $value) {
				$variables[$key] = htmlentities(mysql_real_escape_string($value));
			}
		}
		else
			return false;
		
		if (!is_numeric($variables['amount']))
			$ccform->errors[] = $CFG->cc_qty_error;
		
		if ($variables['cctype'] == 'visa') {
			if (!preg_match('/^(4[0-9]{12}(?:[0-9]{3})?)*$/',$variables['ccnumber'])) $ccform->errors[] = $CFG->cc_regex_error;	
		}
		elseif ($variables['cctype'] == 'mastercard') {
			if (!preg_match('/^(5[1-5][0-9]{14})*$/',$variables['ccnumber'])) $ccform->errors[] = $CFG->cc_regex_error;	
		}
		elseif ($variables['cctype'] == 'amex') {
			if (!preg_match('/^(3[47][0-9]{13})*$/',$variables['ccnumber'])) $ccform->errors[] = $CFG->cc_regex_error;	
		}
		elseif ($variables['cctype'] == 'discover') {
			if (!preg_match('^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$',$variables['ccnumber'])) $ccform->errors[] = $CFG->cc_regex_error;	
		}
		
		if ($variables['cctype'] == 'amex') {
			if (!preg_match('/^[0-9]{4}$/',$variables['cvv'])) $ccform->errors[] = $CFG->cc_cvv_error;
		}
		else {
			if (!preg_match('/^[0-9]{3}$/',$variables['cvv'])) $ccform->errors[] = $CFG->cc_cvv_error;
		}
	
		if (strtotime('28-'.$variables['ccexp_m'].'-'.$variables['ccexp_y']) < time())
			$ccform->errors[] = $CFG->cc_expired_error;
		
		$variables['ccexp'] = $variables['ccexp_m'].$variables['ccexp_y'];
		
		unset($variables['cctype']);
		unset($variables['recurrente']);
		unset($variables['fin']);
		unset($variables['recday']);
		unset($variables['ccexp_m']);
		unset($variables['ccexp_y']);
		unset($variables['terminos']);
		
		return $variables;
	}
	
	function postPaymentData($variables) {
		global $CFG;
		
		if (!is_array($variables))
			return false;
		
		$variables['username'] = $CFG->cc_user;
		$variables['password'] = $CFG->cc_pass;
		$variables['type'] = 'sale';
		
		$response = Link::executeScript($CFG->cc_submit_url,$variables);
		parse_str($response,Credicorp::$auth_info);
	}
	
	function getResponse() {
		global $CFG,$ccform;
		$return_vars = Credicorp::$auth_info;
		if ($return_vars['response'] == 1) {
			$ccform->messages[] = $CFG->cc_transaction_success;
			return true;
		}
		elseif ($return_vars['response'] == 2)
			$ccform->errors[] = $CFG->cc_transaction_denied;
		elseif ($return_vars['response'] == 3)
			$ccform->errors[] = $CFG->cc_transaction_error;
			
		return false;
	}
}