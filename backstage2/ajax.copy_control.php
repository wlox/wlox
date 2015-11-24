<?
include'lib/common.php';

$action = $_REQUEST['action'];
$old_id = $_REQUEST['id'];
$pm_page_id = $_REQUEST['pm_page_id'];
$pm_action = $_REQUEST['pm_action'];
$pm_is_tab = $_REQUEST['pm_is_tab'];
$page_id = (!$pm_is_tab) ? $pm_page_id : 'page_id';
$tab_id = ($pm_is_tab) ? $pm_page_id : 'tab_id';

if ($action == 'control') {
	if (!db_query("INSERT INTO admin_controls (page_id,tab_id,action,class,arguments,admin_controls.order,is_static) SELECT {$page_id},{$tab_id},action,class,arguments,admin_controls.order,is_static FROM admin_controls WHERE id = $old_id")) {
		Errors::add($CFG->ajax_insert_error);
	}
	else {
		$insert_id = mysql_insert_id();
		$return_values[] = 'control_id='.$insert_id;
		$methods = db_query_array("SELECT id FROM admin_controls_methods WHERE control_id = $old_id ");
		if ($methods) {
			foreach ($methods as $method) {
				if (!db_query("INSERT INTO admin_controls_methods (method,arguments,admin_controls_methods.order,control_id,p_id) SELECT method,arguments,admin_controls_methods.order,".$insert_id.",p_id FROM admin_controls_methods WHERE id = {$method['id']}")) {
					if (!Errors::$errors)
						Errors::add($CFG->ajax_insert_error);
				}
				else {
					$method_id = mysql_insert_id();
					$return_values[] = 'method_'.$method['id'].'='.$method_id;
					if ($method_id > 0) {
						$submethods = db_query_array("SELECT id FROM admin_controls_methods WHERE p_id = $method_id ");
						if ($submethods) {
							foreach ($submethods as $submethod) {
								if (!db_query("INSERT INTO admin_controls_methods (method,arguments,admin_controls_methods.order,control_id,p_id) SELECT method,arguments,admin_controls_methods.order,".$insert_id.",".$method_id." FROM admin_controls_methods WHERE id = {$submethod['id']}")) {
									if (!Errors::$errors)
										Errors::add($CFG->ajax_insert_error);
								}
								else {
									$return_values[] = 'method_'.$submethod['id'].'='.mysql_insert_id();
								}
							}
						}
					}
				}
			}
		}
	}
}
elseif ($action == 'method') {
	if (!db_query("INSERT INTO admin_controls_methods (method,arguments,admin_controls_methods.order,control_id,p_id) SELECT method,arguments,admin_controls_methods.order,".$_REQUEST['control_id'].",p_id FROM admin_controls_methods WHERE id = {$old_id}")) {
		if (!Errors::$errors)
			Errors::add($CFG->ajax_insert_error);
	}
	else {
		$method_id = mysql_insert_id();
		$return_values[] = 'method_'.$old_id.'='.$method_id;
		if ($method_id > 0) {
			$submethods = db_query_array("SELECT id FROM admin_controls_methods WHERE p_id = $method_id ");
			if ($submethods) {
				foreach ($submethods as $submethod) {
					if (!db_query("INSERT INTO admin_controls_methods (method,arguments,admin_controls_methods.order,control_id,p_id) SELECT method,arguments,admin_controls_methods.order,control_id,".$method_id." FROM admin_controls_methods WHERE id = {$submethod['id']}")) {
						if (!Errors::$errors)
							Errors::add($CFG->ajax_insert_error);
					}
					else {
						$return_values[] = 'method_'.$submethod['id'].'='.mysql_insert_id();
					}
				}
			}
		}
	}
}

if (is_array(Errors::$errors))
	Errors::display();
else {
	echo '[Return values:'.(implode('|',$return_values)).']';
	Messages::add($CFG->ajax_save_message);
	Messages::display();
}
?>
