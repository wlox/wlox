<?php
date_default_timezone_set($CFG->default_timezone);
String::magicQuotesOff();

if ($_REQUEST['new_settings']) {
	foreach ($_REQUEST['new_settings'] AS $name => $value) {
		Settings::set($name,$value);
		if ($_FILES['new_settings']['name']) {
			foreach ($_FILES['new_settings']['name'] as $input_name => $file_name) {
				if ($file_name) {
					if (!DB::tableExists('settings_files')) {
						$sql = "
						CREATE TABLE `settings_files` (
						id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`f_id` INT( 10 ) UNSIGNED NOT NULL ,
						`ext` CHAR( 4 ) NOT NULL ,
						`dir` VARCHAR( 255 ) NOT NULL ,
						`url` TEXT NOT NULL ,
						`old_name` VARCHAR( 255 ) NOT NULL ,
						`field_name` VARCHAR( 50 ) NOT NULL ,
						INDEX ( `f_id`)
						) ENGINE = MYISAM ";
						db_query($sql);
					}
					$temp_files1[] = Upload::saveTemp('new_settings',$input_name);
				}
			}
			if (is_array($temp_files1)) {
				foreach ($temp_files1 as $file_info) {
					$field_name = $file_info['input_name'];
					if ($file_info['error']) {
						$errors[$field_name] = $file_info['error'];
					}
					else {
						$temp_files[$field_name] = $file_info['filename'];
					}
				}
			}
		}
		if (!$errors && is_array($temp_files)) {
			foreach ($temp_files as $field_name => $file_name) {
				$field_name_parts = explode('__',$field_name);
				$field_name_n = $field_name_parts[0];
				$file_reqs = $_REQUEST['files'][$field_name_n];
				$image_sizes = ($file_reqs['image_sizes']) ? $file_reqs['image_sizes'] : $CFG->image_sizes;
				if (Upload::save($file_name,$field_name_n,'settings',1,$file_reqs['dir'],$image_sizes,$field_name)) {
					$messages[$file_name] = $CFG->file_save_message;
					unset($temp_files[$field_name]);
				}
				else {
					$errors[$file_name] = $CFG->file_save_error;
				}
			}
		}
	}
}

$skins_raw = scandir('css');
if (is_array($skins_raw)) {
	foreach ($skins_raw as $skin) {
		$skins[$skin] = $skin;
	}
}

$settings = new Form('new_settings',false,false,false,'settings',true);
$settings->record_id = 1;
$settings->info = Settings::getStructured();
$settings->show_errors();
$settings->show_messages();

$settings->startFieldset('Appearance');
$settings->fileInput('logo','Logo',false,false,false,false,array('logo'=>array('width'=>190,'height'=>55)),1,false,false,false,false,1,1);
$settings->autoComplete('skin','Skin',false,false,false,$skins);
$settings->endFieldset();

$settings->startFieldset('URL Rewriting');
$settings->checkBox('url_rewrite','Url Rewrite');
$settings->endFieldset();

$settings->startFieldset('Locale');
$settings->textInput('locale','Locale');
$settings->endFieldset();

$settings->startFieldset('Form Behavior');
$settings->textInput('pass_regex','Pass Regex');
$settings->textInput('verify_default_error','Default Verify Error');
$settings->textInput('verify_email_error','Email Error');
$settings->textInput('verify_phone_error','Phone Error');
$settings->textInput('verify_file_type_error','File Type Error');
$settings->textInput('verify_file_size_error','File Size Error');
$settings->textInput('verify_file_misc_error','File Miscelaneous Error');
$settings->textInput('verify_file_required_error','File Required Error');
$settings->textInput('verify_password_error','Password Error');
$settings->textInput('verify_zip_error','Zip Code Error');
$settings->textInput('verify_date_error','Date Error');
$settings->textInput('verify_custom_error','Custom Regex Error');
$settings->textInput('verify_invalid_char_error','Invalid Character Error');
$settings->textInput('capcha_error','CAPCHA Error');
$settings->textInput('verify_cat_select_error','Category Select Error');
$settings->textInput('verify_error_class','CSS Error Class');
$settings->textInput('req_img','Req. Field Marker'); // img or text for required fields
$settings->textInput('table_creation_error','Table Creation Error');
$settings->textInput('form_get_record_error','Get Record Error');
$settings->textInput('form_save_error','Form Save Error');
$settings->textInput('form_update_error','Form Update Error');
$settings->textInput('file_save_error','File Save Error');
$settings->textInput('compare_error','Password Compare Error');
$settings->textInput('value_exists_error','Value Exists Error');
$settings->textInput('alt_url_label','Alt Url Label');
$settings->textInput('file_input_button','File Input Button Text');
$settings->textInput('table_created','Successfully created database table.');
$settings->textInput('form_save_message','Form saved successfully.');
$settings->textInput('form_update_message','Record [field] updated successfully.');
$settings->textInput('file_save_message','File [field] saved successfully.');
$settings->checkBox('auto_create_table','Auto-Create Tables');
$settings->textInput('fck_css_file','FCKEditor CSS File');
$settings->textInput('faux_no_options','Faux Select No Options');
$settings->textInput('faux_editor_caption','Faux Editor Caption');
$settings->endFieldset();

$settings->startFieldset('File System Options');
$settings->textInput('temp_file_location','Temp File Dir');
$settings->textInput('default_upload_location','Default Uploads Dir');
$settings->textArea('accepted_file_formats','Accepted File Formats',false,false,false,false,false,false,true);
$settings->textArea('accepted_image_formats','Accepted Image Formats',false,false,false,false,false,false,true);
$settings->textArea('accepted_audio_formats','Accepted Audio Formats',false,false,false,false,false,false,true);
$settings->textArea('image_sizes','Image Sizes',false,false,false,false,false,false,true);
$settings->endFieldset();

$settings->startFieldset('Date Picker Options');
$settings->textInput('date_picker_icon','DatePicker Icon');
$settings->textInput('date_picker_css','DatePicker CSS File');
$settings->endFieldset();

$settings->startFieldset('Default Date Format');
$settings->textInput('default_date_format','Default Date Format');
$settings->textInput('default_time_format','Default Time Format');
$settings->textInput('default_timezone','Timezone');
$settings->endFieldset();

$settings->startFieldset('Media Gallery Options');
$settings->textInput('placeholder_image','Placeholder Image');
$settings->textInput('gallery_left_arrow','Gallery L-Arrow');
$settings->textInput('gallery_right_arrow','Gallery R-Arrow');
$settings->textInput('gallery_desc_icon','Gallery Description Icon');
$settings->textInput('gallery_desc_label','Gallery Description Label');
$settings->textInput('gallery_desc_tooltip','Gallery Description Tooltip');
$settings->endFieldset();

$settings->startFieldset('Grid Options');
$settings->textInput('grid_no_table_error','No Table Error');
$settings->textInput('grid_no_field_error','No Field Error');
$settings->textInput('grid_no_results','No Results Message');
$settings->textInput('pagination_label','Pagination Label');
$settings->textInput('first_page_text','First Page Link');
$settings->textInput('last_page_text','Last Page Link');
$settings->textInput('results_per_page_text','Results Per Page Text');
$settings->textInput('search_text','Search Label');
$settings->textInput('filter_submit_text','Filter Submit Text');
$settings->textInput('rows_per_page','Default Rows Per Page');
$settings->textInput('subtotal_label','Subtotal Label');
$settings->textInput('subavg_label','Subaverage Label');
$settings->textInput('total_label','Grand Total Label');
$settings->textInput('avg_label','Grand Average Label');
$settings->textInput('switch_to_graph','Switch To Bar Graph Label');
$settings->textInput('switch_to_graph_line','Switch To Line Graph Label');
$settings->textInput('switch_to_graph_pie','Switch To Pie Graph Label');
$settings->textInput('switch_to_table','Switch To Table Label');
$settings->textInput('switch_to_list','Switch To List Label');
$settings->textInput('name_column_label','Name Column Label');
$settings->textInput('value_column_label','Value Column Label');
$settings->textInput('x_axis','X Axis Label');
$settings->textInput('combine_label','Combine Label');
$settings->textInput('grid_activate_button','Grid Activate Button');
$settings->textInput('grid_deactivate_button','Grid Deactivate Button');
$settings->textInput('combine_label','Combine Label');
$settings->textInput('grid_delete_multiple','Delete Mult. Records Prompt');
$settings->textInput('grid_change','Change Records Prompt');
$settings->textInput('grid_until_label','Date Until Label');
$settings->textInput('grid_view_filters','Grid View Filters Label');
$settings->textInput('grid_hide_filters','Grid Hide Filters Label');
$settings->textInput('grid_click_to_select','Click to select...');
$settings->textInput('grid_n_selected','N selected.');
$settings->endFieldset();

$settings->startFieldset('Operations / Navigation');
$settings->textInput('add_directory','Add Folder');
$settings->textInput('add_step','Add Step');
$settings->textInput('add_new_caption','Add New Caption');
$settings->textInput('delete_button_label','Delete Button Label');
$settings->textInput('admin_button','Admin Button Label');
$settings->textInput('back','Back');
$settings->textInput('cancel_button','Cancel Button Label');
$settings->textInput('delete_hover_caption','Delete Hover Caption');
$settings->textInput('download_all','Download All');
$settings->textInput('download_results','Download Results');
$settings->textInput('dragdrop_add_child_button','Add Child Label');
$settings->textInput('edit_hover_caption','Edit Hover Caption');
$settings->textInput('edit_tabs_button','Edit Pages Label');
$settings->textInput('edit_tabs_this_button','Edit This Page Label');
$settings->textInput('forward','Forward');
$settings->textInput('home_label','Home Label');
$settings->textInput('logout_button','LogOut Label');
$settings->textInput('messages_yes_button','You Have Messages Button Label');
$settings->textInput('messages_no_button','No New Messages Button Label');
$settings->textInput('move_hover_caption','Move Hover Caption');
$settings->textInput('my_account_button','My Account Label');
$settings->textInput('ok_button','OK Button Label');
$settings->textInput('path_attributes','Attributes Path Label');
$settings->textInput('print_caption','Print Caption');
$settings->textInput('path_ctrl','Control Panel Path Label');
$settings->textInput('path_edit','Edit Path Label');
$settings->textInput('path_edit_tabs','Edit Tabs Path Label');
$settings->textInput('path_error','Error Path Label');
$settings->textInput('path_message','Message Path Label');
$settings->textInput('path_prompt','Prompt Path Label');
$settings->textInput('path_separator','Path Separator ');
$settings->textInput('path_settings','Settings Path Label');
$settings->textInput('path_users','Users and Groups Path Label');
$settings->textInput('path_view','View Path Label');
$settings->textInput('grid_default_reset','Reset Button');

$settings->textInput('view_hover_caption','View Hover Caption');
$settings->textInput('save_caption','Save Caption');

$settings->textInput('up_directory','Up One Directory');
$settings->textInput('save_order','Save Order');
$settings->endFieldset();

$settings->startFieldset('Ajax Options');
$settings->textInput('ajax_save_message','Ajax Save Message');
$settings->textInput('ajax_save_error','Ajax Save Error.');
$settings->textInput('ajax_insert_error','Ajax Insert Error');
$settings->textInput('ajax_delete_error','Ajax Delete Error');
$settings->textInput('ajax_confirm_delete','Ajax Confirm Delete');
$settings->textInput('ajax_confirm_delete_sub','Ajax Confirm Delete Sub-Elements');
$settings->textInput('ajax_synch','Ajax Confirm Synch Record/List');
$settings->endFieldset();

$settings->startFieldset('User System');
$settings->textInput('login_empty_user','Username Empty Error');
$settings->textInput('login_empty_pass','Password Empty Error');
$settings->textInput('user_unique_error','User Unique Error');
$settings->textInput('login_invalid','Invalid Login Error');
$settings->textInput('no_database_error','No Database Error');
$settings->textInput('user_username','Username Label');
$settings->textInput('user_password','Password Label');
$settings->textInput('user_first_name','First Name Label');
$settings->textInput('user_last_name','Last Name Label');
$settings->textInput('user_phone','Phone Label');
$settings->textInput('user_email','Email Label');
$settings->textInput('user_group','Group Label');
$settings->textInput('user_group_name','Group Name Label');
$settings->textInput('user_is_admin','Is Admin Label');
$settings->endFieldset();

$settings->startFieldset('Email Options');
$settings->textInput('email_send_error','Email Send Error');
$settings->textInput('invalid_email_error','Invalid Email');
$settings->textInput('email_sent_message','Email Sent Message');
$settings->textInput('form_email','Form Email');
$settings->textInput('form_email_from','Form Email From');
$settings->endFieldset();

$settings->startFieldset('Comments Options');
$settings->textInput('comments_sent_message','Message Sent');
$settings->textInput('comments_there_are','"There are..."');
$settings->textInput('comments_expand','Comments Expand');
$settings->textInput('comments_hide','Comments Hide');
$settings->textInput('comments_none','Comments None');
$settings->textInput('comments_be_first','"Be the first"');
$settings->textInput('comments_no_url_error','No URL Error');
$settings->textInput('comments_no_record_error','No Record Error');
$settings->textInput('comments_less_than_minute','"Less than a minute ago"');
$settings->textInput('comments_minutes_ago','"..minutes ago"');
$settings->textInput('comments_hours_ago','"..hours ago"');
$settings->textInput('comments_days_ago','"...days ago"');
$settings->textInput('comments_months_ago','"...months ago"');
$settings->textInput('comments_wrote_label','"...wrote..."');
$settings->textInput('comments_reply_label','Reply Label');
$settings->textInput('comments_name_label','Name Label');
$settings->textInput('comments_email_label','Email Label');
$settings->textInput('comments_website_label','Website Label');
$settings->textInput('comments_comments_label','Comments Label');
$settings->textInput('comments_submit','Submit Label');
$settings->textInput('comment_type_1','Comment Type 1');
$settings->textInput('comment_type_2','Comment Type 2');
$settings->textInput('comment_type_3','Comment Type 3');
$settings->textInput('comment_type_4','Comment Type 4');
$settings->textInput('comment_type_5','Comment Type 5');
$settings->textInput('comments_action_2','Action 2');
$settings->textInput('comments_action_2_short','Action 2 Short');
$settings->textInput('comments_action_3','Action 3');
$settings->textInput('comments_action_3_short','Action 3 Short');
$settings->textInput('comments_action_4','Action 4');
$settings->textInput('comments_action_4_short','Action 4 Short');
$settings->textInput('comments_action_5','Action 5');
$settings->textInput('comments_action_5_short','Action 5 Short');
$settings->textInput('comments_set_to','[old_value] "set to" [new_value]');
$settings->textInput('comments_show_details','"Show details" label');
$settings->textInput('comments_hide_details','"Hide details" label');
$settings->endFieldset();

$settings->startFieldset('Calendar Options');
$settings->textInput('cal_week_from','"Week From"');
$settings->textInput('cal_week_until','"..until"');
$settings->textInput('cal_today','"Today" label');
$settings->textInput('cal_sun','Sunday label');
$settings->textInput('cal_mon','Monday label');
$settings->textInput('cal_tue','Tuesday label');
$settings->textInput('cal_wed','Wednesday label');
$settings->textInput('cal_thur','Thursday label');
$settings->textInput('cal_fri','Friday label');
$settings->textInput('cal_sat','Saturday label');
$settings->textInput('cal_every','Every label');
$settings->textInput('cal_day','Day label');
$settings->textInput('cal_dont_repeat','Dont Repeat label');
$settings->textInput('cal_view_month','View Month label');
$settings->endFieldset();

$settings->startFieldset('PageMap Options');
$settings->textInput('pagemap_nothing','No Pages error');
$settings->endFieldset();

$settings->startFieldset('Excel Uploader Options');
$settings->textInput('excel_add_new','Upload New DB Label');
$settings->textInput('excel_table_label','Table Label');
$settings->textInput('excel_upload_file_label','Upload File Label');
$settings->textInput('excel_photos_label','Select Photos Label');
$settings->textInput('excel_template','Template Label');
$settings->textInput('excel_next_label','Next Step Label');
$settings->textInput('excel_records_updated_label','Records Updated Label');
$settings->textInput('excel_photos_updated_label','Photos Updated Label');
$settings->textInput('excel_upload_success_label','Upload Success Label');
$settings->textInput('excel_upload_date_label','Upload Date Label');
$settings->textInput('excel_user_label','User Updated Label');
$settings->textInput('excel_invalid_file','Invalid File Error');
$settings->textInput('excel_invalid_table','Invalid Table Error');
$settings->textInput('excel_step1','Step 1 Instruction');
$settings->textInput('excel_step2','Step 2 Instruction');
$settings->textInput('excel_ignore_label','Ignore Label');
$settings->textInput('excel_primary_label','Primary Label');
$settings->textInput('excel_photo_index_label','Photo Index Label');
$settings->textInput('excel_ignore_first_label','Ignore First Row Label');
$settings->textInput('excel_delete_all_label','Delete All Records Label');
$settings->textInput('excel_update_label','Update Label');
$settings->textInput('excel_auto_activate_label','Auto Activate Label');
$settings->textInput('excel_template_label','Save Template As Label');
$settings->textInput('excel_update_message','Updated [records] [photos] Message');
$settings->textInput('excel_not_update_message','Not Updated [records] [photos] Message');
$settings->textInput('excel_no_primary_error','No Primary Error');
$settings->textInput('excel_no_photo_index_error','No Photo Index Error');
$settings->endFieldset();

$settings->startFieldset('Page Maker Options');
$settings->checkBox('pm_decouple_cancel','Decouple Cancel-Button');
$settings->checkBox('pm_cancel_back','Cancel-Button Goes To Last Page');
$settings->textInput('pm_copy_wrong_class','Copy+Paste Wrong Class');
$settings->textInput('pm_list_tab','List Tab');
$settings->textInput('pm_form_tab','Form Tab');
$settings->textInput('pm_record_tab','Record Tab');
$settings->textInput('pm_ctrl_tab','Control Panel Tab');
$settings->textInput('pm_exit','Exit Editor Label');
$settings->textInput('pmt_list','List Control');
$settings->textInput('pmt_list_add_table','List AddTable');
$settings->textInput('pmt_grid','Grid Control');
$settings->textInput('pmt_grid_field','Grid Field');
$settings->textInput('pmt_grid_label','Grid Label');
$settings->textInput('pmt_grid_aggregate','Grid Aggregate Field');
$settings->textInput('pmt_grid_inline_form','Grid Inline Form');
$settings->textInput('pmt_grid_autocomplete','Grid Autocomplete Filter');
$settings->textInput('pmt_grid_tokenizer','Grid Tokenizer Filter');
$settings->textInput('pmt_grid_cats','Grid Categories Filter');
$settings->textInput('pmt_grid_checkbox','Grid Checkbox Filter');
$settings->textInput('pmt_grid_date_end','Grid EndDate Filter');
$settings->textInput('pmt_grid_date_start','Grid StartDate Filter');
$settings->textInput('pmt_grid_first_letter','Grid FirstLetter Filter');
$settings->textInput('pmt_grid_per_page','Grid ResultsPerPage Filter');
$settings->textInput('pmt_grid_radio','Grid Radio Filter');
$settings->textInput('pmt_grid_search','Grid Search Filter');
$settings->textInput('pmt_grid_select','Grid Select Filter');
$settings->textInput('pmt_grid_month','Grid Month Filter');
$settings->textInput('pmt_grid_year','Grid Year Filter');
$settings->textInput('pmt_form','Form Control');
$settings->textInput('pmt_form_text_input','Form TextInput');
$settings->textInput('pmt_form_text_editor','Form TextEditor');
$settings->textInput('pmt_form_text_area','Form TextArea');
$settings->textInput('pmt_form_auto_complete','Form Autocomplete');
$settings->textInput('pmt_form_button','Form Button');
$settings->textInput('pmt_form_cat_select','Form Category Select');
$settings->textInput('pmt_form_checkbox','Form CheckBox');
$settings->textInput('pmt_form_date_widget','Form Date Input');
$settings->textInput('pmt_form_end_fieldset','Form End Fieldset');
$settings->textInput('pmt_form_end_group','Form End Group');
$settings->textInput('pmt_form_file_input','Form File Input');
$settings->textInput('pmt_form_file_multiple','Form File Multiple Input');
$settings->textInput('pmt_form_gallery','Form Gallery');
$settings->textInput('pmt_form_hidden_input','Form Hidden Input');
$settings->textInput('pmt_form_password_input','Form Password Input');
$settings->textInput('pmt_form_radio_input','Form Radio Input');
$settings->textInput('pmt_form_reset_button','Form Reset Button');
$settings->textInput('pmt_form_select_input','Form Select Input');
$settings->textInput('pmt_form_start_fieldset','Form Start Fieldset');
$settings->textInput('pmt_form_start_group','Form Start Group');
$settings->textInput('pmt_form_submit_button','Form Submit Button');
$settings->textInput('pmt_form_cancel_button','Form Cancel Button');
$settings->textInput('pmt_form_passive_field','Passive Field Button');
$settings->textInput('pmt_form_colorpicker','Color Picker');
$settings->textInput('pmt_form_colorpicker_nametext','Color Picker Nametext');
$settings->textInput('pmt_form_multiple','Multiple');
$settings->textInput('pmt_form_grid','Input Grid');
$settings->textInput('pmt_form_email_notify','Email Notify');
$settings->textInput('pmt_form_create_record','Create Record');
$settings->textInput('pmt_form_edit_record','Edit Record');
$settings->textInput('pmt_form_start_area','Start Area');
$settings->textInput('pmt_form_end_area','End Area');
$settings->textInput('pmt_form_link','Form Link');
$settings->textInput('pmt_form_aggregate','Form Aggregate');
$settings->textInput('pmt_form_indicator','Form Indicator');
$settings->textInput('pmt_form_start_restricted','Start Restricted');
$settings->textInput('pmt_form_end_restricted','End Restricted');
$settings->textInput('pmt_form_start_tab','Start Tab');
$settings->textInput('pmt_form_end_tab','End Tab');
$settings->textInput('pmt_form_include_page','Form Include Page');
$settings->textInput('pmt_form_print_button','Print Button');
$settings->textInput('pmt_record','Record Control');
$settings->textInput('pmt_record_end_table','Record End Table');
$settings->textInput('pmt_record_field','Record AddField');
$settings->textInput('pmt_record_aggregate','Record Aggregate');
$settings->textInput('pmt_record_indicator','Record Indicator');
$settings->textInput('pmt_record_gallery','Record Gallery');
$settings->textInput('pmt_record_heading','Record Heading');
$settings->textInput('pmt_record_image','Record Image');
$settings->textInput('pmt_record_start_table','Record Subtable');
$settings->textInput('pmt_record_files','Record Files');
$settings->textInput('pmt_record_grid','Record Grid');
$settings->textInput('pmt_record_link','Record Link');
$settings->textInput('pmt_record_include_page','Record Include Page');
$settings->textInput('pmt_record_cancel_button','Record Cancel Button');
$settings->textInput('pmt_record_button','Record Button');
$settings->textInput('pmt_gallery_image','Gallery Image');
$settings->textInput('pmt_gallery_multiple','Gallery Multiple');
$settings->textInput('pmt_tabs','Tabs Control');
$settings->textInput('pmt_tabs_make_tab','Tabs Add');
$settings->textInput('pmt_comments','Comments Control');
$settings->textInput('pmt_comments_setparams','Comments Set Parameters');
$settings->textInput('pmt_calendar','Calendar Control');
$settings->textInput('pmt_calendar_add_table','Calendar Add Table');
$settings->textInput('pmt_calendar_field','Calendar Field');
$settings->textInput('pmt_calendar_placeholder','Calendar Placeholder Text');
$settings->textInput('pmt_calendar_autocomplete','Calendar Autocomplete Filter');
$settings->textInput('pmt_calendar_tokenizer','Calendar Tokenizer Filter');
$settings->textInput('pmt_calendar_checkbox','Calendar Checkbox Filter');
$settings->textInput('pmt_calendar_select','Calendar Select Filter');
$settings->textInput('pmt_calendar_add_new','Calendar Add New Button');
$settings->textInput('pmt_page_map','PageMap Control');
$settings->textInput('pmt_page_map_start_area','PageMap Start Area');
$settings->textInput('pmt_page_map_end_area','PageMap End Area');
$settings->textInput('pmt_filemanager','FileManager Control');
$settings->textInput('pmt_filemanager_add_table','FileManager Add Table');
$settings->textInput('pmt_redirect','Redirect Control');
$settings->textInput('pmt_flowchart','FlowChart Control');
$settings->textInput('pmt_excel','Excel Uploader Control');
$settings->textInput('pmt_synch','Synch Function');
$settings->endFieldset();

$settings->submitButton('submit','Set!');
$settings->display();


?>