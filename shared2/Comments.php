<?php
class Comments {
	public $table,$url,$record_id,$i,$class,$user_table,$count,$autoshow,$pass_vars,$fields,$show_all,$comments_closed,$short_version,$label,$sql_filter,$max_comments;
	public static $j;
	
	function __construct($table=false,$url=false,$record_id=false,$class=false,$user_table=false,$autoshow=false,$pass_vars=false,$comments_closed=false,$show_all=false,$short_version=false,$sql_filter=false,$max_comments=false) {
		global $i,$CFG;
		
		Comments::$j = (Comments::$j > 0) ? Comments::$j + 1 : 1;
		$this->table = ($table) ? $table : 'comments';
		$this->url = $url;
		$this->record_id = $record_id;
		$this->i = Comments::$j;
		$this->class = 'comments'.(($class) ? ' '.$class : false);
		$this->user_table = ($user_table) ? $user_table : 'site_users';
		$this->count = 0;
		$this->autoshow = $autoshow;
		$this->pass_vars = $pass_vars;
		$this->comments_closed = $comments_closed;
		$this->show_all = $show_all;
		$this->short_version = $short_version;
		$this->sql_filter = $sql_filter;
		$this->max_comments = $max_comments;
		
		if ($CFG->backstage_mode) {
			$this->url = $CFG->url;
			$this->record_id = $CFG->id;
		}
	}
	
	function setParams($use_fckeditor=false,$require_email=false,$ask_website=false,$editor_height=false) {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'setParams');
			echo '[setParams]'.$method_name;
		}
		
		$this->use_fckeditor = $use_fckeditor;
		$this->require_email = $require_email;
		$this->ask_website = $ask_website;
		$this->editor_height = $editor_height;
	}
	
	function field($table,$name,$caption=false,$link_url=false,$subtable=false,$subtable_fields=false,$concat_char=false,$is_media=false,$media_size=false,$f_id_field=false,$order_by=false,$order_asc=false,$link_is_tab=false,$target_elem_id=false,$link_id_field=false,$limit_is_curdate=false) {
		global $CFG;
		
		if ($CFG->pm_editor) {
			$method_name = Form::peLabel($CFG->method_id,'field');
			echo '<li>'.$table.' [field] '.$method_name.'</li>';
		}
		
		$rand = (@array_key_exists($name,$this->fields)) ? 'lll'.rand(1,99) : false;
		$this->fields[$name] = array(
			'table'=>$table,
			'name'=>$name,
			'caption'=>$caption,
			'link_url'=>$link_url,
			'is_media'=>$is_media,
			'subtable'=>$subtable,
			'subtable_fields'=>$subtable_fields,
			'concat_char'=>$concat_char,
			'thumb_amount'=>0,
			'media_amount'=>1,
			'media_size'=>$media_size,
			'method_id'=>$CFG->method_id,
			'f_id_field'=>$f_id_field,
			'order_by'=>$order_by,
			'order_asc'=>$order_asc,
			'link_is_tab'=>$link_is_tab,
			'target_elem_id'=>$target_elem_id,
			'link_id_field'=>$link_id_field,
			'limit_is_curdate'=>$limit_is_curdate);
	}
	
	function label($text) {
		global $CFG;
		$this->label = array('text'=>$text,'method_id'=>$CFG->method_id);
	}
	
	function display($use_fckeditor=false,$require_email=false,$ask_website=false,$editor_height=false) {
		global $CFG;

		if ($CFG->backstage_mode && !($this->record_id > 0) && !$this->show_all)
			return false;

		$use_fckeditor = ($this->use_fckeditor) ? $this->use_fckeditor : $use_fckeditor;
		$require_email = ($this->require_email) ? $this->require_email : $require_email;
		$ask_website = ($this->ask_website) ? $this->ask_website : $ask_website;
		$editor_height = ($this->editor_height) ? $this->editor_height : $editor_height;

		if ($_REQUEST['comments_'.$this->i] && !$this->comments_closed) {
			if (!empty($_REQUEST['comments_'.$this->i]['comments1'])) {
				$_REQUEST['comments_'.$this->i]['comments'] = $_REQUEST['comments_'.$this->i]['comments1'];
				unset($_REQUEST['comments_'.$this->i]['comments1']);
			}
			
			$CFG->save_called = false;
			$form = new Form('comments_'.$this->i,false,false,$this->class.'_form','comments');
			$form->verify();
			if (!$form->errors) {
				$form->save();
				Messages::add($CFG->comments_sent_message);
				Messages::display();
			}
			else  {
				$form->show_errors();
			}
		}
		
		$comments = Comments::get();
		$c = count(Comments::get(false,true));
		$show = ($this->autoshow) ? '' : 'style="display:none;"';
		
		if ($this->label) {
			if ($CFG->pm_editor)
				$method_name = Form::peLabel($this->label['method_id'],'label');
		
			echo '<div class="grid_label"><div class="label">'.$this->label['text'].' '.$method_name.'</div><div class="clear"></div></div>';
		}
		
		if (!$this->short_version) {
			if ($comments) {
				echo '<div class="expand">'.str_ireplace('[field]',$c,$CFG->comments_there_are).' '.((!$_REQUEST['comments_'.$this->i]) ? '<a href="#" onclick="showComments('.$this->i.',this);return false;">'.$CFG->comments_expand.'</a>' : '').'<a style="display:none;" href="#" onclick="hideComments('.$this->i.',this);return false;">'.$CFG->comments_hide.'</a></div>';
			}
			else {
	
				echo '<div class="expand">'.$CFG->comments_none.' <a href="#" onclick="showComments('.$this->i.',this);return false;">'.$CFG->comments_be_first.'</a><a style="display:none;" href="#" onclick="hideComments('.$this->i.',this);return false;">'.$CFG->comments_hide.'</a></div>';
			}
		}
		echo '
		<div id="comments_'.$this->i.'" class="'.$this->class.'" '.((!$_REQUEST['comments_'.$this->i]) ? $show : '').'>';
		
		if ($comments) {
			Comments::show($comments);
		}
		
		echo '
			<div id="movable_form" style="display:none;">';
		if (!$this->comments_closed)
			Comments::showForm($use_fckeditor,$require_email,$ask_website,1,$editor_height);
		echo '
			</div>';
		if (!$this->comments_closed)		
		Comments::showForm($use_fckeditor,$require_email,$ask_website,0,$editor_height);
		
		echo '
			<div style="clear:both;height:0;"></div>
		</div>';
	}
	
	function get($p_id=0,$count=false) {
		global $CFG;

		if (empty($this->url) && !$this->show_all) {
			Errors::add($CFG->comments_no_url_error);
			return false;
		}
			
		if (!($this->record_id > 0) && !$this->show_all) {
			Errors::add($CFG->comments_no_record_error);
			return false;
		}
		
		$sql_filter = $this->sql_filter;
		$sql = "SELECT comments.* FROM {$this->table} ";

		if ($sql_filter) {
			$matches = String::getSubstring($sql_filter,'[',']');
			foreach ($matches as $match) {
				if (strstr($match,','))  {
					$join_path = explode(',',$match);
					if (is_array($join_path)) {
						foreach ($join_path as $join_field) {
							$join_field_parts = explode('.',$join_field);
							$join_table = $join_field_parts[0];
							$j_field = $join_field_parts[1];
							$join_tables[$join_table][] = $j_field;
						}
						$sql_filter = str_ireplace('['.$match.']',$join_field,$sql_filter);
					}
				}
				elseif (strstr($match,'.')) {
					$join_field_parts = explode('.',$match);
					$join_table = $join_field_parts[0];
					$j_field = $join_field_parts[1];
					$join_tables[$join_table][] = $j_field;
					$sql_filter = str_replace('[','',str_replace(']','',$sql_filter));
				}
			}
		}
		
		if ($join_tables) {
			foreach ($join_tables as $r_table => $r_field) {
				$j_field = ($prev_field == 'id') ? $r_field[0] : 'id';
				$j_field = ($r_table == $prev_table) ? $prev_field : $r_field[0];
				
				if ($r_table != $this->table)
					$sql .= " LEFT JOIN {$r_table} ON ({$prev_table}.{$prev_field} = {$r_table}.{$j_field}) ";
				
				$prev_table = $r_table;
				$prev_field = (count($r_field) > 1) ? $r_field[1] : $r_field[0];
			}
		}
		
		$sql .= " WHERE 1 ";
		
		if ($sql_filter) {
			$sql_filter = String::doFormulaReplacements($sql_filter);
			$sql .= " AND (".$sql_filter.') ';
		}
		
		$sql .= ((!$this->show_all) ? "AND {$this->table}.url = '{$this->url}' AND {$this->table}.record_id = {$this->record_id}" : "")." ".((!$count) ? "AND {$this->table}.p_id = $p_id" : '')." 
		ORDER BY {$this->table}.date DESC ";
		if ($this->max_comments)
			$sql .= " LIMIT 0,{$this->max_comments}";
		$result = db_query_array($sql);
		
		if ($result) {
			foreach ($result as $row) {
				$this->count++;
				$id = $row['id'];
				$comments[$id] = $row;
				$comments[$id]['children'] = Comments::get($id);
			}
		}
		return $comments;
	}
	
	private function show($comments) {
		global $CFG;
		
		if ($comments) {
			echo '<ul>';
			foreach ($comments as $comment) {
				$elapsed = (time() + (Settings::mysqlTimeDiff() * 3600)) - strtotime($comment['date']);
				if ($elapsed < 60) {
					$time_ago = $CFG->comments_less_than_minute;
				}
				elseif ($elapsed > 60 && $elapsed < (60 * 60)) {
					$minutes = floor($elapsed / 60);
					$time_ago = str_ireplace('[field]',$minutes,$CFG->comments_minutes_ago);
				}
				elseif ($elapsed > (60 * 60) && $elapsed < (60 * 60 * 24)) {
					$hours = floor(($elapsed / 60) / 60);
					$time_ago = str_ireplace('[field]',$hours,$CFG->comments_hours_ago);
				}
				elseif ($elapsed > (60 * 60 * 24) && $elapsed < (60 * 60 * 24 * 30.4)) {
					$days = floor((($elapsed / 60) / 60) / 24);
					$time_ago = str_ireplace('[field]',$days,$CFG->comments_days_ago);
				}
				else {
					$months = floor(((($elapsed / 60) / 60) / 24) / 30.4);
					$time_ago = str_ireplace('[field]',$months,$CFG->comments_months_ago);
				}
				
				if ($comment['user_id'] > 0) {
					$user = DB::getRecord($this->user_table,$comment['user_id'],false,true);
					$name = (!empty($comment['website'])) ? Link::url($comment['website'],$user['user']) : $user['user'];
				}
				else {
					$name = (!empty($comment['website'])) ? Link::url($comment['website'],$comment['name']) : $comment['name'];
				}
				
				$short = ($this->short_version) ? '_short' : '';
				$icon = ($comment['type']) ? eval('return $CFG->comment_type_'.$comment['type'].';') : $CFG->comment_type_1;
				$action = ($comment['type']) ? eval('return $CFG->comments_action_'.$comment['type'].$short.';') : $CFG->comments_wrote_label;
				$action = String::doFormulaReplacements($action,unserialize($comment['f_table_row']),1,1);

				echo '
				<li id="comment_'.$comment['id'].'" class="level_'.$comment['type'].'">
					<div class="c_head">';
				
				if ($this->fields) {
					foreach ($this->fields as $f_name => $field) {
						$CFG->o_method_id = $field['method_id'];
						$CFG->o_method_name = 'field';
						$record = new Record($field['table'],$comment['record_id']);
						echo '<div class="added_field">'.$record->field($field['name'],$field['caption'],$field['subtable'],$field['subtable_fields'],$field['link_url'],$field['concat_char'],true,$field['f_id_field'],$field['order_by'],$field['order_asc'],$comment['record_id'],$field['link_is_tab'],$field['limit_is_curdate'],false,$field['link_id_field']).'</div>';
					}
				}

				echo '
						'.$icon.' '.$name.' ('.$time_ago.') '.$action.'
					</div>';
				if (!$this->short_version) {
					echo '
						<div class="c_comment">
							'.((strlen($comment['comments']) != strlen(strip_tags($comment['comments']))) ? $comment['comments'] : nl2br($comment['comments'])).'
						</div>';
				}
				echo '
					'.(($comment['type'] <= 1 && !$this->short_version) ? '<div class="c_reply"><a href="#" onclick="showReplyBox('.$comment['id'].','.$this->i.');return false;">'.$CFG->comments_reply_label.'</a></div>':'').'
					<div class="c_form"></div>
				</li>';
				if(is_array($comment['children'])) {
					Comments::show($comment['children']);
				}
			}
			echo '<div style="clear:both;height:0;"></div></ul>';
		}
	}
	
	private function showForm($use_fckeditor=false,$require_email=false,$ask_website=false,$hidden=false,$editor_height=false) {
		global $CFG;
		
		$form = new Form('comments_'.$this->i,false,false,$this->class.'_form');
		$CFG->o_method_suppress = true;
		$form->hiddenInput('p_id');
		$CFG->o_method_suppress = true;
		$form->hiddenInput('url',false,$this->url);
		$CFG->o_method_suppress = true;
		$form->hiddenInput('record_id',false,$this->record_id);
		
		if (is_array($this->pass_vars)) {
			foreach ($this->pass_vars as $var => $val) {
				$CFG->o_method_suppress = true;
				$form->HTML('<input type="hidden" name="'.$var.'" value="'.$val.'" />');				
			}
		}
		
		if ($CFG->backstage_mode) {
			$CFG->o_method_suppress = true;
			$form->HTML('<input type="hidden" name="current_url" value="'.$CFG->url.'" />');	
			$CFG->o_method_suppress = true;
			$form->HTML('<input type="hidden" name="action" value="'.$CFG->action.'" />');	
			$CFG->o_method_suppress = true;	
			$form->HTML('<input type="hidden" name="is_tab" value="'.$CFG->is_tab.'" />');	
			$CFG->o_method_suppress = true;
			$form->HTML('<input type="hidden" name="id" value="'.$CFG->id.'" />');
			$CFG->o_method_suppress = true;
			$form->HTML('<input type="hidden" name="return_to_self" value="1" />');		
		}
		
		if (!User::isLoggedIn()) {
			$require_email = ($require_email) ? 'email' : false;
			$form->textInput('name',$CFG->comments_name_label,true);
			$form->textInput('email',$CFG->comments_email_label,$require_email);
			if ($ask_website)
				$form->textInput('website',$CFG->comments_website_label);
		}
		else {
			$CFG->o_method_suppress = true;
			$form->hiddenInput('user_id',false,User::$info['id']);
		}

		if ($use_fckeditor) {
			$CFG->o_method_suppress = true;
			$form->textEditor((($hidden) ? 'comments1' : 'comments'),$CFG->comments_comments_label,true,false,false,false,false,true,false,$editor_height);
		
		} 
		else {
			$CFG->o_method_suppress = true;
			$form->textArea((($hidden) ? 'comments1' : 'comments'),$CFG->comments_comments_label,true);
		}
		
		$CFG->o_method_suppress = true;
		$form->submitButton('submit',$CFG->comments_submit,false,'button');
		$form->display();
	}
}
?>