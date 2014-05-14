<?php
class Cart {
	function add($item_id,$item_table,$qty=false,$table=false) {
		global $CFG;
		
		$table = ($table) ? $table : 'cart';
		$item = DB::getRecord($item_table,$item_id,false,true);
		$item_name = $item['name'];
		
		if (!($item_id > 0) && !$item) {
			$errors[$item_id] = $CFG->cart_item_error;
			Errors::merge($errors);
			return false;
		}
		
		if (empty($item_table)) {
			$errors[$item_id] = $CFG->cart_table_error;
			Errors::merge($errors);
			return false;
		}
		
		if (!($qty > 0)) {
			$errors[$item_id] = $CFG->cart_qty_error;
			Errors::merge($errors);
			return false;
		}
			
		if ($c_item = Cart::getItem($item_id,$item_table,$table)) {
			return Cart::update($item_id,$item_table,($c_item['qty'] + $qty),$table);
		}

		if (User::isLoggedIn()) {
			$id = DB::insert($table,array('item_id'=>$item_id,'item_table'=>$item_table,'qty'=>$qty,'user_id'=>User::$info['id']));
			$messages[$item_name] = str_ireplace('[qty]',$qty,$CFG->cart_add_message);
			Messages::merge($messages);
			return $id;
		}
		else {
			$_SESSION['cart'][$item_table.'_'.$item_id] = array('item_id'=>$item_id,'item_table'=>$item_table,'qty'=>$qty);
			$messages[$item_name] = str_ireplace('[qty]',$qty,$CFG->cart_add_message);
			Messages::merge($messages);
			return true;
		}
	}
	
	function update($item_id,$item_table,$qty=false,$table=false) {
		global $CFG;
		
		$table = ($table) ? $table : 'cart';
		$item = DB::getRecord($item_table,$item_id,false,true);
		$item_name = $item['name'];

		if (!($item_id > 0) && !$item) {
			$errors[$item_id] = $CFG->cart_item_error;
			Errors::merge($errors);
			return false;
		}
		
		if (empty($item_table)) {
			$errors[$item_id] = $CFG->cart_table_error;
			Errors::merge($errors);
			return false;
		}
		
		if (!($qty > 0)) {
			$errors[$item_id] = $CFG->cart_qty_error;
			Errors::merge($errors);
			return false;
		}
		
		if (User::isLoggedIn()) {
			$result = getItem($item_id,$item_table,$table);
			$id = $result['id'];
			
			if ($id > 0) {
				if ($qty > 0) {
					DB::update($table,array('item_id'=>$item_id,'item_table'=>$item_table,'qty'=>$qty,'user_id'=>User::$info['id']),$id);
				}
				else {
					DB::delete($id);
				}
				$messages[$item_name] = str_ireplace('[qty]',$qty,$CFG->cart_update_message);
				Messages::merge($messages);
				return $id;
			}
			else {
				$errors[$item_id] = $CFG->cart_item_error;
				Errors::merge($errors);
				return false;
			}
		}
		else {
			if ($qty > 0) {
				$_SESSION['cart'][$item_table.'_'.$item_id]['qty'] = $qty;
				$messages[$item_name] = str_ireplace('[qty]',$qty,$CFG->cart_update_message);
				Messages::merge($messages);
			}
			else {
				unset($_SESSION['cart'][$item_table.'_'.$item_id]);
			}
			return true;
		}
	}
	
	function delete($item_id,$item_table,$table=false) {
		global $CFG;
		
		$table = ($table) ? $table : 'cart';
		$item = DB::getRecord($item_table,$item_id,false,true);
		$item_name = $item['name'];
		
		if (User::isLoggedIn()) {
			$result = getItem($item_id,$item_table,$table);
			if ($result) {
				DB::delete($result['id']);
				$messages[$item_name] = $CFG->cart_delete_message;
				Messages::merge($messages);
			}
		}
		else {
			unset($_SESSION['cart'][$item_table.'_'.$item_id]);
			$messages[$item_name] = $CFG->cart_delete_message;
			Messages::merge($messages);
		}
	}
	
	function get($table=false) {
		$table = ($table) ? $table : 'cart';
		
		if (User::isLoggedIn()) {
			$sql = "SELECT * FROM $table WHERE user_id = ".User::$info['id'];
			return db_query_array($sql);
		}
		else {
			return $_SESSION['cart'];
		}
	}
	
	function getItem($item_id,$item_table,$table=false) {
		$table = ($table) ? $table : 'cart';
		
		if (!($item_id > 0))
			return false;
		
		if (empty($item_table))
			return false;
		
		if (User::isLoggedIn()) {
			$sql = "SELECT * FROM $table WHERE item_id = $item_id AND item_table = '$item_table' AND user_id = ".User::$info['id'];
			$result = db_query_array($sql);
			return $result[0];
		}
		else {
			return $_SESSION['cart'][$item_table.'_'.$item_id];
		}
	}
	
	function saveToDB() {
		global $CFG;
		
		
		if (!User::isLoggedIn())
			return false;
		
		$table = ($table) ? $table : 'cart';
		
		if (is_array($_SESSION['cart'])) {
			foreach ($_SESSION['cart'] as $row) {
				$row['user_id'] = User::$info['id'];
				if (!DB::insert($table,$row)) {
					$id = $row['item_id'];
					$errors[$id] = $CFG->cart_item_conversion_error;
				}
			}
		}
		
		if ($errors) {
			Errors::merge($errors);
			return false;
		}
		else {
			return true;
		}
	}
	
	function count($table=false) {
		$table = ($table) ? $table : 'cart';
		
		if (User::isLoggedIn()) {
			$sql = "SELECT count(*) AS c FROM $table WHERE user_id = ".User::$info['id'];
			$result = db_query_array($sql);
			return $result[0]['c'];
		}
		else {
			return count($_SESSION['cart']);
		}
	}
}
?>