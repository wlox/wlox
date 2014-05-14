<?php
class Redirect {
	private $HTML;
	function __construct($url=false,$is_tab=false,$go_to_last_url=false,$target_elem_id=false,$variables=false,$delay_ms=false) {
		global $CFG;
		
		$delay_ms = ($delay_ms > 0) ? $delay_ms : '0';
		$variables['current_url'] = ($go_to_last_url) ? $_SESSION['last_query'] : $url;
		$variables['is_tab'] = $is_tab;
		$reserved_keywords = array('current_url','action','bypass','is_tab');

		if (is_array($variables)) {
			foreach ($variables as $k => $v) {
				$v1 = str_replace('[','',str_replace(']','',$v));
				if (in_array($k,$reserved_keywords))
					$variables1[$k] = $v;
				elseif ($k == 'id')
					$variables1[$k] = $this->record_id;
				elseif ($v1 == 'id')
					$variables1["{$url}[{$k}]"] = $this->record_id;
				else
					$variables1["{$url}[{$k}]"] = $this->info[$v1];
			}
		}
		$variables1['current_url'] = $url;
		$variables1['bypass_save'] = 1;

		if (!$CFG->pm_editor) {
			$this->HTML = '
			<script type="text/javascript">
				setTimeout(function() {
					ajaxGetPage(\'index.php'.((is_array($variables1)) ? '?'.http_build_query($variables1) : '').'\',\''.$target_elem_id.'\');
				},'.$delay_ms.');
			</script>';
		}
	}
	function display() {
		echo $this->HTML;
	}
}
?>