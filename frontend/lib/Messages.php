<?php
class Messages {
	public static $messages = array();
	
	public static function add($message) {
		if (!empty($message))
			self::$messages[] = $message;
	}
	
	public static function merge($message_array) {
		if (!is_array(self::$messages)) {
			self::$messages = $message_array;
		}
		if (is_array($message_array)) {
			self::$messages = array_merge(self::$messages,$message_array);
		}
	}
	
	public static function display() {
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