<?
include'lib/common.php';

$record_copy = array('Tabs','Comments','Calendar');
$list_copy = array();

$rec_normal = array('textInput','passwordInput','checkBox','radioInput','textArea','textEditor','dateWidget','colorPicker','selectInput','passiveField','autoComplete','catSelect');
$rec_no_display = array('submitButton','hiddenInput','permissionEditor','startFieldset','endFieldset','captcha','multiple','verify','save','display','get','send_email','peLabel','emailNotify','createRecord','editRecord','parseJscript','filterPerPage','filterSearch','filterAutocomplete','filterCats','filterFirstLetter','filterSelect','filterTokenizer','filterCheckbox','filterRadio','filterDateStart','filterDateEnd','filterMonth','filterYear','grid');
$rec_file = array('fileInput','fileMultiple');
$rec_gal = array('gallery');
$rec_same = array('aggregate','indicator','cancelButton','button','link','startArea','endArea','startRestricted','endRestricted','startGroup','endGroup','HTML','includePage');

$action = $_REQUEST['action'];
$pm_page_id = $_REQUEST['pm_page_id'];
$pm_action = $_REQUEST['pm_action'];
$pm_is_tab = $_REQUEST['pm_is_tab'];
$f_field = ($pm_is_tab) ? 'tab_id' : 'page_id';
$page_id = (!$pm_is_tab) ? $pm_page_id : 'page_id';
$tab_id = ($pm_is_tab) ? $pm_page_id : 'tab_id';

$sql = "SELECT * FROM admin_controls WHERE $f_field = $pm_page_id AND action = 'form' ";
$result = db_query_array($sql);

$control_order = 0;
$method_order = 0;

if ($result) {
	foreach ($result as $row) {
		if ($action == 'record') {
			if (in_array($row['class'],$record_copy)) {
				if (!db_query("INSERT INTO admin_controls (page_id,tab_id,action,class,arguments,admin_controls.order,is_static) SELECT {$page_id},{$tab_id},'record',class,arguments,'$control_order',is_static FROM admin_controls WHERE id = {$row['id']}")) {
					Errors::add($CFG->ajax_insert_error);
				}
				else {
					$insert_id = mysql_insert_id();
					$methods = db_query_array("SELECT id FROM admin_controls_methods WHERE control_id = {$row['id']} ");
					if ($methods) {
						foreach ($methods as $method) {
							if (!db_query("INSERT INTO admin_controls_methods (method,arguments,admin_controls_methods.order,control_id,p_id) SELECT method,arguments,'$method_order',".$insert_id.",p_id FROM admin_controls_methods WHERE id = {$method['id']}")) {
								if (!Errors::$errors)
									Errors::add($CFG->ajax_insert_error);
							}
							else {
								$method_id = mysql_insert_id();
								if ($method_id > 0) {
									$submethods = db_query_array("SELECT id FROM admin_controls_methods WHERE p_id = $method_id ");
									if ($submethods) {
										foreach ($submethods as $submethod) {
											if (!db_query("INSERT INTO admin_controls_methods (method,arguments,admin_controls_methods.order,control_id,p_id) SELECT method,arguments,'$method_order',".$insert_id.",".$method_id." FROM admin_controls_methods WHERE id = {$submethod['id']}")) {
												if (!Errors::$errors)
													Errors::add($CFG->ajax_insert_error);
											}
										}
									}
								}
							}
							$method_order++;
						}
					}
				}
				$control_order++;
			}
			elseif ($row['class'] == 'Form') {
				$control_args = Control::parseArguments($row['arguments'],'Form','__construct');
				$insert_args = serialize(array('table'=>$control_args['table']));
				if (!db_query("INSERT INTO admin_controls (page_id,tab_id,action,class,arguments,admin_controls.order,is_static) VALUES ({$page_id},{$tab_id},'record','Record','$insert_args','$control_order',0)")) {
					if (!Errors::$errors)
						Errors::add($CFG->ajax_insert_error);
				}
				else {
					$insert_id = mysql_insert_id();
					$methods = db_query_array("SELECT * FROM admin_controls_methods WHERE control_id = {$row['id']} ORDER BY admin_controls_methods.order ");
					if ($methods) {
						foreach ($methods as $method) {
							$insert_args = array();
							if (in_array($method['method'],$rec_no_display))
								continue;
								
							$method_args = Control::parseArguments($method['arguments'],'Form',$method['method']);
							if (in_array($method['method'],$rec_normal)) {
								$insert_args['name'] = $method_args['name'];
								$insert_args['caption'] = $method_args['caption'];
								$insert_args['subtable'] = ($method_args['subtable']) ? $method_args['subtable'] : '';
								$insert_args['subtable_fields'] = ($method_args['subtable_fields']) ? $method_args['subtable_fields'] : '';
								$insert_args['link_url'] = '';
								$insert_args['concat_char'] = ($method_args['concat_char']) ? $method_args['concat_char'] : '';
								$insert_args['in_form'] = '';
								$insert_args['order_by'] = ($method_args['order_by']) ? $method_args['order_by'] : '';
								$insert_args['order_asc'] = ($method_args['order_asc']) ? $method_args['order_asc'] : '';
								$insert_args['order_by'] = ($method_args['order_by']) ? $method_args['order_by'] : '';
								$insert_args['record_id'] = '';
								$insert_args['link_is_tab'] = '';
								$insert_args['limit_is_curdate'] = ($method_args['limit_is_curdate']) ? $method_args['limit_is_curdate'] : '';
								$insert_args['override_value'] = '';
								$insert_args['link_id_field'] = '';
								$args = serialize($insert_args);
								
								if (!db_query("INSERT INTO admin_controls_methods (method,arguments,admin_controls_methods.order,control_id,p_id) VALUES ('field','$args','$method_order','$insert_id','{$method['p_id']}')")) {
									if (!Errors::$errors)
										Errors::add($CFG->ajax_insert_error);
								}
							}
							elseif (in_array($method['method'],$rec_same)) {
								unset($method_args['static']);
								unset($method_args['update_variable_values']);
								unset($method_args['bypass_create_record']);
								$args = serialize($method_args);
								
								if (!db_query("INSERT INTO admin_controls_methods (method,arguments,admin_controls_methods.order,control_id,p_id) VALUES ('{$method['method']}','$args','$method_order','$insert_id','{$method['p_id']}')")) {
									if (!Errors::$errors)
										Errors::add($CFG->ajax_insert_error);
								}
							}
							elseif (in_array($method['method'],$rec_gal)) {
								$args = serialize($method_args);
								if (!db_query("INSERT INTO admin_controls_methods (method,arguments,admin_controls_methods.order,control_id,p_id) VALUES ('gallery','$args','$method_order','$insert_id','{$method['p_id']}')")) {
									if (!Errors::$errors)
										Errors::add($CFG->ajax_insert_error);
								}
							}
							elseif (in_array($method['method'],$rec_file)) {
								$insert_args['name'] = $method_args['name'];
								$insert_args['caption'] = $method_args['caption'];
								$insert_args['encrypted'] = $method_args['encrypt'];
								$args = serialize($insert_args);
								
								if (!db_query("INSERT INTO admin_controls_methods (method,arguments,admin_controls_methods.order,control_id,p_id) VALUES ('files','$args','$method_order','$insert_id','{$method['p_id']}')")) {
									if (!Errors::$errors)
										Errors::add($CFG->ajax_insert_error);
								}
							}
								
							$method_order++;
						}
					}
				}
				$control_order++;
			}
		}
		elseif (!$action) {
			
		}
	}
}

if (is_array(Errors::$errors))
	Errors::display();
else {
	Messages::add($CFG->ajax_save_message);
	Messages::display();
}

?>
