<?php
class Messages {
	public static $messages = array();
	
	function add($message) {
		if (!empty($message))
			self::$messages[] = $message;
	}
	
	function merge($message_array) {
		if (!is_array(self::$messages)) {
			self::$messages = $message_array;
		}
		if (is_array($message_array)) {
			self::$messages = array_merge(self::$messages,$message_array);
		}
	}
	
	function display() {
		if (!empty(self::$messages)) {
			echo '<ul class="messages">';
			foreach (self::$messages as $name => $message) {
				echo '<li><div class="warning_icon"></div>'.ucfirst(str_ireplace('[field]',$name,$message)).'</li>';
			}
			echo '</ul><div class="clear">&nbsp;</div>';
		}
	}
}
?>