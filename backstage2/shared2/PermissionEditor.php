<?php
class PermissionEditor {
	private $table,$group_id,$tabs;
	
	function __construct($table,$group_id) {
		global $CFG;
		
		$this->table = ($table) ? $table : 'admin';
		$this->group_id = $group_id;
		$this->tabs = PermissionEditor::getTabsPages();
		
		if ($this->tabs) {
			echo '<div class="pe_editor">';
			echo '<ul>';
			echo "
			<input type=\"hidden\" id=\"pe_table\" name=\"pe_table\" value=\"{$this->table}\" />
			<input type=\"hidden\" id=\"pe_group_id\" name=\"pe_group_id\" value=\"{$this->group_id}\" />
			";
			foreach ($this->tabs as $id => $tab) {
				$p = $tab['info']['permission'];
				echo "
				<li>
					<span>{$tab['info']['name']}</span> 
					<span id=\"pe_0\" onclick=\"peCycle(this)\" class=\"pe_icon".(($p == 0) ? '_visible' : '')."\" >{$CFG->permissions[0]}</span>
					<span id=\"pe_1\" onclick=\"peCycle(this)\" class=\"pe_icon".(($p == 1) ? '_visible' : '')."\" >{$CFG->permissions[1]}</span>
					<span id=\"pe_2\" onclick=\"peCycle(this)\" class=\"pe_icon".(($p == 2) ? '_visible' : '')."\" >{$CFG->permissions[2]}</span>
					<input type=\"hidden\" id=\"pe_permission\" name=\"pe[tabs][$id]\" value=\"$p\" />
				</li>";
				
				if (is_array($tab['pages'])) {
					echo '<ul>';
					foreach ($tab['pages'] as $page) {
						$p1 = $page['permission'];
						echo "
						<li>
							<span>{$page['name']}</span> 
							<span id=\"pe_0\" onclick=\"peCycle(this)\" class=\"pe_icon".(($p1 == 0) ? '_visible' : '')."\" >{$CFG->permissions[0]}</span>
							<span id=\"pe_1\" onclick=\"peCycle(this)\" class=\"pe_icon".(($p1 == 1) ? '_visible' : '')."\" >{$CFG->permissions[1]}</span>
							<span id=\"pe_2\" onclick=\"peCycle(this)\" class=\"pe_icon".(($p1 == 2) ? '_visible' : '')."\" >{$CFG->permissions[2]}</span>
							<input type=\"hidden\" id=\"pe_permission\" name=\"pe[pages][{$page['id']}]\" value=\"$p1\" />
						</li>";
					}
					echo '</ul>';
				}
			}
			echo '</ul><div class="clear">&nbsp;</div></div>';
		}
	}
	
	function getTabsPages() {
		$tabs = db_query_array("
			SELECT {$this->table}_tabs.*,{$this->table}_groups_tabs.permission AS permission 
			FROM {$this->table}_tabs
			LEFT JOIN {$this->table}_groups_tabs ON ({$this->table}_groups_tabs.tab_id = {$this->table}_tabs.id AND {$this->table}_groups_tabs.group_id = {$this->group_id}) 
			ORDER BY {$this->table}_tabs.order");
		
		if ($tabs) {
			foreach ($tabs as $tab) {
				$t_id = $tab['id'];
				$pages = db_query_array("
					SELECT {$this->table}_pages.*, {$this->table}_groups_pages.permission AS permission
					FROM {$this->table}_pages 
					LEFT JOIN {$this->table}_groups_pages ON ({$this->table}_groups_pages.page_id = {$this->table}_pages.id AND {$this->table}_groups_pages.group_id = {$this->group_id})
					WHERE {$this->table}_pages.f_id = {$t_id} 
					ORDER BY {$this->table}_pages.order");
				
				$structured[$t_id]['info'] = $tab;
				$structured[$t_id]['pages'] = $pages;
			}
		}
		return $structured;
	}
	
	function save() {
		$table = $_REQUEST['pe_table'];
		$g_id = $_REQUEST['pe_group_id'];
		
		if (is_array($_REQUEST['pe']['tabs'])) {
			foreach ($_REQUEST['pe']['tabs'] as $id => $permission) {
				if ($result = db_query_array("SELECT id FROM {$table}_groups_tabs WHERE tab_id = $id && group_id = $g_id")) {
					$r_id = $result[0]['id'];
					DB::update("{$table}_groups_tabs",array('permission'=>$permission),$r_id);
				}
				else {
					DB::insert("{$table}_groups_tabs",array('permission'=>$permission,'tab_id'=>$id,'group_id'=>$g_id));
				}
			}
		}
		
		if (is_array($_REQUEST['pe']['pages'])) {
			foreach ($_REQUEST['pe']['pages'] as $id => $permission) {
				if ($result = db_query_array("SELECT id FROM {$table}_groups_pages WHERE page_id = $id && group_id = $g_id")) {
					$r_id = $result[0]['id'];
					DB::update("{$table}_groups_pages",array('permission'=>$permission),$r_id);
				}
				else {
					DB::insert("{$table}_groups_pages",array('permission'=>$permission,'page_id'=>$id,'group_id'=>$g_id));
				}
			}
		}
	}
}
?>