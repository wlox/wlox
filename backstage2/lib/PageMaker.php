<?php
class PageMaker {
	private $id,$action,$page_info,$is_tab;
	
	function __construct($page_id,$action=false,$is_tab=false) {
		$this->id = $page_id;
		$this->action = $action;
		$this->is_tab = $is_tab;
		$this->page_info = DB::getRecord((($this->is_tab) ? 'admin_tabs' : 'admin_pages'),$page_id,0,1);
	}
	
	function showToolbar() {
		global $CFG;

		if (!$this->page_info['is_ctrl_panel'] || $this->page_info['is_ctrl_panel'] == 'N') {
			echo "<div id=\"pm_toolbar\"><div class=\"l_shadow\"></div>";
			if (!$CFG->action || $CFG->action == 'list') {
				echo "
					<div class=\"panel_container\" id=\"panels_list\">
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"MultiList\"><div class=\"icon\"></div>$CFG->pmt_list</a>
						<a href=\"#\" class=\"pm_MultiList pm_icon\" id=\"addTable\"><div class=\"icon\"></div>$CFG->pmt_list_add_table</a>
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Grid\"><div class=\"icon\"></div>$CFG->pmt_grid</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"field\"><div class=\"icon\"></div>$CFG->pmt_grid_field</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"aggregate\"><div class=\"icon\"></div>$CFG->pmt_grid_aggregate</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"inlineForm\"><div class=\"icon\"></div>$CFG->pmt_grid_inline_form</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"gridLabel\"><div class=\"icon\"></div>$CFG->pmt_grid_label</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterAutocomplete\"><div class=\"icon\"></div>$CFG->pmt_grid_autocomplete</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterTokenizer\"><div class=\"icon\"></div>$CFG->pmt_grid_tokenizer</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterCats\"><div class=\"icon\"></div>$CFG->pmt_grid_cats</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterCheckbox\"><div class=\"icon\"></div>$CFG->pmt_grid_checkbox</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterDateStart\"><div class=\"icon\"></div>$CFG->pmt_grid_date_start</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterFirstLetter\"><div class=\"icon\"></div>$CFG->pmt_grid_first_letter</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterPerPage\"><div class=\"icon\"></div>$CFG->pmt_grid_per_page</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterRadio\"><div class=\"icon\"></div>$CFG->pmt_grid_radio</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterSearch\"><div class=\"icon\"></div>$CFG->pmt_grid_search</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterSelect\"><div class=\"icon\"></div>$CFG->pmt_grid_select</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterMonth\"><div class=\"icon\"></div>$CFG->pmt_grid_month</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterYear\"><div class=\"icon\"></div>$CFG->pmt_grid_year</a>
						
						<a href=\"#\" class=\"pm_Form mult pm_icon\" id=\"textInput\"><div class=\"icon\"></div>$CFG->pmt_form_text_input</a>
						<a href=\"#\" class=\"pm_Form mult pm_icon\" id=\"textArea\"><div class=\"icon\"></div>$CFG->pmt_form_text_area</a>
						<a href=\"#\" class=\"pm_Form mult pm_icon\" id=\"autoComplete\"><div class=\"icon\"></div>$CFG->pmt_form_auto_complete</a>
						<a href=\"#\" class=\"pm_Form mult pm_icon\" id=\"checkBox\"><div class=\"icon\"></div>$CFG->pmt_form_checkbox</a>
						<a href=\"#\" class=\"pm_Form mult pm_icon\" id=\"selectInput\"><div class=\"icon\"></div>$CFG->pmt_form_select_input</a>
						<a href=\"#\" class=\"pm_Form mult pm_icon\" id=\"hiddenInput\"><div class=\"icon\"></div>$CFG->pmt_form_hidden_input</a>
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Calendar\"><div class=\"icon\"></div>$CFG->pmt_calendar</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"addTable\"><div class=\"icon\"></div>$CFG->pmt_calendar_add_table</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"field\"><div class=\"icon\"></div>$CFG->pmt_calendar_field</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"placeholder\"><div class=\"icon\"></div>$CFG->pmt_calendar_placeholder</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"addNewButton\"><div class=\"icon\"></div>$CFG->pmt_calendar_add_new</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterAutocomplete\"><div class=\"icon\"></div>$CFG->pmt_calendar_autocomplete</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterTokenizer\"><div class=\"icon\"></div>$CFG->pmt_calendar_tokenizer</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterCheckbox\"><div class=\"icon\"></div>$CFG->pmt_calendar_checkbox</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterSelect\"><div class=\"icon\"></div>$CFG->pmt_calendar_select</a>
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"FileManager\"><div class=\"icon\"></div>$CFG->pmt_filemanager</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"addTable\"><div class=\"icon\"></div>$CFG->pmt_filemanager_add_table</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterAutocomplete\"><div class=\"icon\"></div>$CFG->pmt_grid_autocomplete</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterTokenizer\"><div class=\"icon\"></div>$CFG->pmt_grid_tokenizer</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterCats\"><div class=\"icon\"></div>$CFG->pmt_grid_cats</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterCheckbox\"><div class=\"icon\"></div>$CFG->pmt_grid_checkbox</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterDateStart\"><div class=\"icon\"></div>$CFG->pmt_grid_date_start</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterFirstLetter\"><div class=\"icon\"></div>$CFG->pmt_grid_first_letter</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterRadio\"><div class=\"icon\"></div>$CFG->pmt_grid_radio</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterSearch\"><div class=\"icon\"></div>$CFG->pmt_grid_search</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterSelect\"><div class=\"icon\"></div>$CFG->pmt_grid_select</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterMonth\"><div class=\"icon\"></div>$CFG->pmt_grid_month</a>
						<a href=\"#\" class=\"pm_FileManager pm_icon\" id=\"filterYear\"><div class=\"icon\"></div>$CFG->pmt_grid_year</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"FlowChart\"><div class=\"icon\"></div>$CFG->pmt_flowchart</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"PageMap\"><div class=\"icon\"></div>$CFG->pmt_page_map</a>
						<a href=\"#\" class=\"pm_PageMap pm_icon\" id=\"startArea\"><div class=\"icon\"></div>$CFG->pmt_page_map_start_area</a>
						<a href=\"#\" class=\"pm_PageMap pm_icon\" id=\"endArea\"><div class=\"icon\"></div>$CFG->pmt_page_map_end_area</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Excel\"><div class=\"icon\"></div>$CFG->pmt_excel</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Redirect\"><div class=\"icon\"></div>$CFG->pmt_redirect</a>
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"CustomPHP\"><div class=\"icon\"></div>Custom PHP</a>
						
						<div class=\"clear\"></div>
					</div>";
			}
			elseif ($CFG->action == 'form') {
				echo "		
					<div class=\"panel_container\" id=\"panels_form\">
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Form\"><div class=\"icon\"></div>$CFG->pmt_form</a>
						<a href=\"#\" class=\"pm_Form mult pm_icon\" id=\"textInput\"><div class=\"icon\"></div>$CFG->pmt_form_text_input</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"textEditor\"><div class=\"icon\"></div>$CFG->pmt_form_text_editor</a>
						<a href=\"#\" class=\"pm_Form mult pm_icon\" id=\"textArea\"><div class=\"icon\"></div>$CFG->pmt_form_text_area</a>
						<a href=\"#\" class=\"pm_Form pm_icon mult\" id=\"autoComplete\"><div class=\"icon\"></div>$CFG->pmt_form_auto_complete</a>
						<a href=\"#\" class=\"pm_Form pm_icon mult\" id=\"hiddenInput\"><div class=\"icon\"></div>$CFG->pmt_form_hidden_input</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"passwordInput\"><div class=\"icon\"></div>$CFG->pmt_form_password_input</a>
						<a href=\"#\" class=\"pm_Form pm_icon mult\" id=\"checkBox\"><div class=\"icon\"></div>$CFG->pmt_form_checkbox</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"radioInput\"><div class=\"icon\"></div>$CFG->pmt_form_radio_input</a>
						<a href=\"#\" class=\"pm_Form pm_icon mult\" id=\"selectInput\"><div class=\"icon\"></div>$CFG->pmt_form_select_input</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"fileInput\"><div class=\"icon\"></div>$CFG->pmt_form_file_input</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"fileMultiple\"><div class=\"icon\"></div>$CFG->pmt_form_file_multiple</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"catSelect\"><div class=\"icon\"></div>$CFG->pmt_form_cat_select</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"dateWidget\"><div class=\"icon\"></div>$CFG->pmt_form_date_widget</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"gallery\"><div class=\"icon\"></div>$CFG->pmt_form_gallery</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"submitButton\"><div class=\"icon\"></div>$CFG->pmt_form_submit_button</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"cancelButton\"><div class=\"icon\"></div>$CFG->pmt_form_cancel_button</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"button\"><div class=\"icon\"></div>$CFG->pmt_form_button</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"resetButton\"><div class=\"icon\"></div>$CFG->pmt_form_reset_button</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"printButton\"><div class=\"icon\"></div>$CFG->pmt_form_print_button</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"startFieldset\"><div class=\"icon\"></div>$CFG->pmt_form_start_fieldset</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"endFieldset\"><div class=\"icon\"></div>$CFG->pmt_form_end_fieldset</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"startGroup\"><div class=\"icon\"></div>$CFG->pmt_form_start_group</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"endGroup\"><div class=\"icon\"></div>$CFG->pmt_form_end_group</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"passiveField\"><div class=\"icon\"></div>$CFG->pmt_form_passive_field</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"colorPicker\"><div class=\"icon\"></div>$CFG->pmt_form_colorpicker</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"multiple\"><div class=\"icon\"></div>$CFG->pmt_form_multiple</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"grid\"><div class=\"icon\"></div>$CFG->pmt_form_grid</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"link\"><div class=\"icon\"></div>$CFG->pmt_form_link</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"aggregate\"><div class=\"icon\"></div>$CFG->pmt_form_aggregate</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"indicator\"><div class=\"icon\"></div>$CFG->pmt_form_indicator</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"emailNotify\"><div class=\"icon\"></div>$CFG->pmt_form_email_notify</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"createRecord\"><div class=\"icon\"></div>$CFG->pmt_form_create_record</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"editRecord\"><div class=\"icon\"></div>$CFG->pmt_form_edit_record</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"startArea\"><div class=\"icon\"></div>$CFG->pmt_form_start_area</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"endArea\"><div class=\"icon\"></div>$CFG->pmt_form_end_area</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"startRestricted\"><div class=\"icon\"></div>$CFG->pmt_form_start_restricted</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"endRestricted\"><div class=\"icon\"></div>$CFG->pmt_form_end_restricted</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"startTab\"><div class=\"icon\"></div>$CFG->pmt_form_start_tab</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"endTab\"><div class=\"icon\"></div>$CFG->pmt_form_end_tab</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterAutocomplete\"><div class=\"icon\"></div>$CFG->pmt_grid_autocomplete</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterTokenizer\"><div class=\"icon\"></div>$CFG->pmt_grid_tokenizer</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterCats\"><div class=\"icon\"></div>$CFG->pmt_grid_cats</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterCheckbox\"><div class=\"icon\"></div>$CFG->pmt_grid_checkbox</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterDateStart\"><div class=\"icon\"></div>$CFG->pmt_grid_date_start</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterFirstLetter\"><div class=\"icon\"></div>$CFG->pmt_grid_first_letter</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterPerPage\"><div class=\"icon\"></div>$CFG->pmt_grid_per_page</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterRadio\"><div class=\"icon\"></div>$CFG->pmt_grid_radio</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterSearch\"><div class=\"icon\"></div>$CFG->pmt_grid_search</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterSelect\"><div class=\"icon\"></div>$CFG->pmt_grid_select</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterMonth\"><div class=\"icon\"></div>$CFG->pmt_grid_month</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"filterYear\"><div class=\"icon\"></div>$CFG->pmt_grid_year</a>
						<a href=\"#\" class=\"pm_Form pm_icon\" id=\"includePage\"><div class=\"icon\"></div>$CFG->pmt_form_include_page</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Tabs\"><div class=\"icon\"></div>$CFG->pmt_tabs</a>
						<a href=\"#\" class=\"pm_Tabs pm_icon\" id=\"makeTab\"><div class=\"icon\"></div>$CFG->pmt_tabs_make_tab</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Comments\"><div class=\"icon\"></div>$CFG->pmt_comments</a>
						<a href=\"#\" class=\"pm_Comments pm_icon\" id=\"setParams\"><div class=\"icon\"></div>$CFG->pmt_comments_setparams</a>
						<a href=\"#\" class=\"pm_Comments pm_icon\" id=\"field\"><div class=\"icon\"></div>$CFG->pmt_calendar_field</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Calendar\"><div class=\"icon\"></div>$CFG->pmt_calendar</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"addTable\"><div class=\"icon\"></div>$CFG->pmt_calendar_add_table</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"field\"><div class=\"icon\"></div>$CFG->pmt_calendar_field</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"placeholder\"><div class=\"icon\"></div>$CFG->pmt_calendar_placeholder</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterAutocomplete\"><div class=\"icon\"></div>$CFG->pmt_calendar_autocomplete</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterTokenizer\"><div class=\"icon\"></div>$CFG->pmt_calendar_tokenizer</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterCheckbox\"><div class=\"icon\"></div>$CFG->pmt_calendar_checkbox</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterSelect\"><div class=\"icon\"></div>$CFG->pmt_calendar_select</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Redirect\"><div class=\"icon\"></div>$CFG->pmt_redirect</a>
						
						<div class=\"clear\"></div>
					</div>";
			}
			elseif ($CFG->action == 'record') {
				echo "
					<div class=\"panel_container\" id=\"panels_record\">
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Record\"><div class=\"icon\"></div>$CFG->pmt_record</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"field\"><div class=\"icon\"></div>$CFG->pmt_record_field</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"gallery\"><div class=\"icon\"></div>$CFG->pmt_record_gallery</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"startTable\"><div class=\"icon\"></div>$CFG->pmt_record_start_table</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"endTable\"><div class=\"icon\"></div>$CFG->pmt_record_end_table</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"files\"><div class=\"icon\"></div>$CFG->pmt_record_files</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"grid\"><div class=\"icon\"></div>$CFG->pmt_record_grid</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"link\"><div class=\"icon\"></div>$CFG->pmt_record_link</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"aggregate\"><div class=\"icon\"></div>$CFG->pmt_record_aggregate</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"indicator\"><div class=\"icon\"></div>$CFG->pmt_record_indicator</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"startArea\"><div class=\"icon\"></div>$CFG->pmt_form_start_area</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"endArea\"><div class=\"icon\"></div>$CFG->pmt_form_end_area</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"startGroup\"><div class=\"icon\"></div>$CFG->pmt_form_start_group</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"endGroup\"><div class=\"icon\"></div>$CFG->pmt_form_end_group</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"startRestricted\"><div class=\"icon\"></div>$CFG->pmt_form_start_restricted</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"endRestricted\"><div class=\"icon\"></div>$CFG->pmt_form_end_restricted</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"cancelButton\"><div class=\"icon\"></div>$CFG->pmt_record_cancel_button</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"button\"><div class=\"icon\"></div>$CFG->pmt_record_button</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"printButton\"><div class=\"icon\"></div>$CFG->pmt_form_print_button</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"includePage\"><div class=\"icon\"></div>$CFG->pmt_record_include_page</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"startTab\"><div class=\"icon\"></div>$CFG->pmt_form_start_tab</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"endTab\"><div class=\"icon\"></div>$CFG->pmt_form_end_tab</a>
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Tabs\"><div class=\"icon\"></div>$CFG->pmt_tabs</a>
						<a href=\"#\" class=\"pm_Tabs pm_icon\" id=\"makeTab\"><div class=\"icon\"></div>$CFG->pmt_tabs_make_tab</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Comments\"><div class=\"icon\"></div>$CFG->pmt_comments</a>
						<a href=\"#\" class=\"pm_Comments pm_icon\" id=\"setParams\"><div class=\"icon\"></div>$CFG->pmt_comments_setparams</a>
						<a href=\"#\" class=\"pm_Comments pm_icon\" id=\"field\"><div class=\"icon\"></div>$CFG->pmt_calendar_field</a>
						<a href=\"#\" class=\"pm_Comments pm_icon\" id=\"label\"><div class=\"icon\"></div>$CFG->pmt_grid_label</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Grid\"><div class=\"icon\"></div>$CFG->pmt_grid</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"field\"><div class=\"icon\"></div>$CFG->pmt_grid_field</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"aggregate\"><div class=\"icon\"></div>$CFG->pmt_grid_aggregate</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"gridLabel\"><div class=\"icon\"></div>$CFG->pmt_grid_label</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterAutocomplete\"><div class=\"icon\"></div>$CFG->pmt_grid_autocomplete</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterTokenizer\"><div class=\"icon\"></div>$CFG->pmt_grid_tokenizer</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterCats\"><div class=\"icon\"></div>$CFG->pmt_grid_cats</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterCheckbox\"><div class=\"icon\"></div>$CFG->pmt_grid_checkbox</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterDateStart\"><div class=\"icon\"></div>$CFG->pmt_grid_date_start</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterFirstLetter\"><div class=\"icon\"></div>$CFG->pmt_grid_first_letter</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterPerPage\"><div class=\"icon\"></div>$CFG->pmt_grid_per_page</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterRadio\"><div class=\"icon\"></div>$CFG->pmt_grid_radio</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterSearch\"><div class=\"icon\"></div>$CFG->pmt_grid_search</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterSelect\"><div class=\"icon\"></div>$CFG->pmt_grid_select</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Calendar\"><div class=\"icon\"></div>$CFG->pmt_calendar</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"addTable\"><div class=\"icon\"></div>$CFG->pmt_calendar_add_table</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"field\"><div class=\"icon\"></div>$CFG->pmt_calendar_field</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"placeholder\"><div class=\"icon\"></div>$CFG->pmt_calendar_placeholder</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterAutocomplete\"><div class=\"icon\"></div>$CFG->pmt_calendar_autocomplete</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterTokenizer\"><div class=\"icon\"></div>$CFG->pmt_calendar_tokenizer</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterCheckbox\"><div class=\"icon\"></div>$CFG->pmt_calendar_checkbox</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"filterSelect\"><div class=\"icon\"></div>$CFG->pmt_calendar_select</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Redirect\"><div class=\"icon\"></div>$CFG->pmt_redirect</a>
						
						
						<a href=\"#\" class=\"pm_function pm_icon\" onclick=\"editorSynch()\" id=\"Synch\"><div class=\"icon\"></div>$CFG->pmt_synch</a>

						<div class=\"clear\"></div>
					</div>";
				
			}
			echo "
					<div class=\"clear\"></div>
				</div>";
		}
		else {
			echo "
				<div id=\"pm_toolbar\">
					<div class=\"panel_container\" id=\"panels_ctrl\">
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"MultiList\"$CFG->pmt_list</a>
						<a href=\"#\" class=\"pm_MultiList pm_icon\" id=\"addTable\"$CFG->pmt_list_add_table</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Grid\"$CFG->pmt_grid</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"field\"$CFG->pmt_grid_field</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"aggregate\"$CFG->pmt_grid_aggregate</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"gridLabel\"$CFG->pmt_grid_label</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterAutocomplete\"$CFG->pmt_grid_autocomplete</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterCats\"$CFG->pmt_grid_cats</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterCheckbox\"$CFG->pmt_grid_checkbox</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterDateStart\"$CFG->pmt_grid_date_start</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterFirstLetter\"$CFG->pmt_grid_first_letter</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterPerPage\"$CFG->pmt_grid_per_page</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterRadio\"$CFG->pmt_grid_radio</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterSearch\"$CFG->pmt_grid_search</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterSelect\"$CFG->pmt_grid_select</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterMonth\"$CFG->pmt_grid_month</a>
						<a href=\"#\" class=\"pm_Grid pm_icon\" id=\"filterYear\"$CFG->pmt_grid_year</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Calendar\"$CFG->pmt_calendar</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"addTable\"$CFG->pmt_calendar_add_table</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"field\"$CFG->pmt_calendar_field</a>
						<a href=\"#\" class=\"pm_Calendar pm_icon\" id=\"placeholder\"$CFG->pmt_calendar_placeholder</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"PageMap\"$CFG->pmt_page_map</a>
						<a href=\"#\" class=\"pm_PageMap pm_icon\" id=\"startArea\"$CFG->pmt_page_map_start_area</a>
						<a href=\"#\" class=\"pm_PageMap pm_icon\" id=\"endArea\"$CFG->pmt_page_map_end_area</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Record\"$CFG->pmt_record</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"field\"$CFG->pmt_record_field</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"gallery\"$CFG->pmt_record_gallery</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"startTable\"$CFG->pmt_record_start_table</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"endTable\"$CFG->pmt_record_end_table</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"files\"$CFG->pmt_record_files</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"grid\"$CFG->pmt_record_grid</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"link\"$CFG->pmt_record_link</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"startArea\"$CFG->pmt_form_start_area</a>
						<a href=\"#\" class=\"pm_Record pm_icon\" id=\"endArea\"$CFG->pmt_form_end_area</a>
					
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Tabs\"$CFG->pmt_tabs</a>
						<a href=\"#\" class=\"pm_Tabs pm_icon\" id=\"makeTab\"$CFG->pmt_tabs_make_tab</a>
						
						<div class=\"panel_separator\"</div>
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Comments\"$CFG->pmt_comments</a>
						<a href=\"#\" class=\"pm_Comments pm_icon\" id=\"setParams\"$CFG->pmt_comments_setparams</a>
						<a href=\"#\" class=\"pm_Comments pm_icon\" id=\"field\"$CFG->pmt_calendar_field\"</a>
						<a href=\"#\" class=\"pm_Comments pm_icon\" id=\"label\"$CFG->pmt_grid_label</a>
						
						
						<a href=\"#\" class=\"pm_control pm_icon\" id=\"Redirect\"$CFG->pmt_redirect</a>

						<div class=\"clear\"></div>
					</div>
					<div class=\"clear\"></div>
				</div>";
		}
	}
	
	function showEditor($bypass=false) {
		global $CFG;
		
		if (!$bypass) echo '
		<div id="pm_wrap" class="pm_wrap">';
		
		echo '<div id="pm_editor">';
		
		$CFG->pm_editor = true;
		$control = new Control($this->id,$this->action,$this->is_tab,true);
		
		echo '
		<input type="hidden" id="pm_page_id" value="'.$this->id.'"/>
		<input type="hidden" id="pm_page_url" value="'.$this->page_info['url'].'"/>
		<input type="hidden" id="pm_action" value="'.$this->action.'"/>
		<input type="hidden" id="pm_is_tab" value="'.$this->is_tab.'"/>';
		
		echo '</div>';
		self::showToolbar();
		
		if (!$bypass) echo '<div class="clear"></div></div>';
	}
	
	function showTabsPages() {
		global $CFG;
		
		echo '
		<div class="faux_caption">'.$CFG->faux_editor_caption.'</div>
		<div class="faux_container">
			<div class="faux_select" onclick="openFauxSelect1(1,event)" id="fauxs_1">
				<div class="paste">';
		$result = db_query_array("SELECT * FROM admin_tabs");
		if ($result) {
			foreach ($result as $tab) {
				$o = '<a class="f_elem" onclick="fauxSelect1(this,event,1,'.$tab['id'].',1)"><div class="f_icon_tab"></div> '.$tab['name'].'</a>';
				$options[] = $o;
				if ($CFG->is_tab && $CFG->id == $tab['id'])
					echo $o;
				
				$result1 = db_query_array("SELECT * FROM admin_pages WHERE f_id = {$tab['id']}");
				if ($result1) {
					foreach($result1 as $page) {
						$o = '<a class="f_elem f_sub" onclick="fauxSelect1(this,event,1,'.$page['id'].')"><div class="f_icon_page"></div> '.$page['name'].'</a>';
						$options[] = $o;
						if (!$CFG->is_tab && $CFG->id == $page['id'])
							echo $o;
					}
				}
			}
		}
		else {
			echo '<div class="f_elem">'.$CFG->faux_no_options.'</div>';
		}
		echo '
				</div>
				<div class="f_down">'.$CFG->down.'</div>
			</div>
			<div class="faux_dropdown" id="faux_1">
				'.implode('',$options).'
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
		</div>
		';
		
	}
}
?>
