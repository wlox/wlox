<?
class ValidateData{

	//Validate dollar amount
	//no charachters except optional $ at begining and ,
	function validateDollars($amount){

		$pattern='/^([1-9][0-9])+(\.[0-9]{2})?|0?\.[0-9][0-9]/';

		if (preg_match($pattern,$amount))
			return true;
		else
			return false;
	}


	//Validate Phone number
	function validatePhone($number)
	{
		//regex for phone numbers
		//$pattern='(\d{3})\D*(\d{3})\D*(\d{4})\D*(\d*)$';//with extensions
		$pattern='/(\d{3})\D*(\d{3})\D*(\d{4})$/';//without extensions

		if (preg_match($pattern,$number))
			return true;
		else
			return false;
	}

	//Validate email
	function validateEmail($email){

		$pattern='/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i';

		if (preg_match($pattern,$email))
			return true;
		else
			return false;

	}

	function validateSSN($ssn){

		$pattern='/^\d{3}-?\d{2}-?\d{4}$/';

		if (preg_match($pattern,$ssn))
			return true;
		else
			return false;
	}


	//Validate zip
	//Valid Zip
	function validateZip($zip)
	{
		$match = preg_match('/^[0-9]{5}$/',$zip);
		return $match;
		//return true;
	//	return true;
	}

	function checkField($data,$min_size=2,$max_size=0)
	{
		$data = preg_replace('/[^a-zA-Z0-9]/','',$data);
		$len  = strlen($data);

		if ($len < $min_size) {
			return false;
		}
		else if ($max_size > 0 && $len > $max_size) {
			return false;
		}
		else {
			return true;
		}
	}


	/**
	 * validates a single credit card number
	 * takes cc# as string with or without delimiters
	 *
	 * @param string $ccNumber
	 * @return bool
	 */
	function validateCC($ccNumber){


		$ccNumber = preg_replace('/[^0-9]+/m', '', $ccNumber);
		if (preg_match('/(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})/',
			$ccNumber)){
			return true;
		} else {
			return false;
		}
	}

	function validatePassword($password,$size=3){
		if (strlen($password) < $size)
			return false;
		else
			return true;
	}
}

?>