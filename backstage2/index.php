<?
include 'lib/common.php';
ini_set("memory_limit","200M");

$CFG->print = $_REQUEST['print'];
$CFG->url = ($_REQUEST['current_url'] != 'index.php') ? ereg_replace("[^a-zA-Z_\-]", "",$_REQUEST['current_url']) : '';
$CFG->action = ereg_replace("[^a-zA-Z_\-]", "",$_REQUEST['action']);
$CFG->bypass = ($_REQUEST['bypass'] || $CFG->print);
$CFG->is_tab = (!$CFG->url) ? 1 : $_REQUEST['is_tab'];
$CFG->id = ereg_replace("[^0-9]", "",$_REQUEST['id']);
$CFG->target_elem = ereg_replace("[^a-zA-Z_\-]", "",$_REQUEST['target_elem']);
$CFG->in_popup = ($CFG->target_elem == 'edit_box' || $CFG->target_elem == 'message_box' || $CFG->target_elem == 'attributes box');

$_SESSION['last_query'] = $_SESSION['this_query'];
$_SESSION['this_query'] = 'index.php?'.http_build_query((is_array($_POST)) ? $_POST : $_GET);

date_default_timezone_set($CFG->default_timezone);
String::magicQuotesOff();

if ($CFG->locale) {
	setlocale(LC_ALL,$CFG->locale);
}

if (!$CFG->bypass || ($CFG->bypass && $CFG->print)) {
	$header = new Header();
	$header->metaAuthor();
	$header->metaDesc();
	$header->metaKeywords();
	$header->cssFile('css/colorpicker.css');
	$header->cssFile('css/reset.css');
	$header->cssFile('css/'.$CFG->skin.'/default.css','all');
	$header->cssFile('css/'.$CFG->skin.'/default_ie6.css','all','IE 6');
	$header->cssFile('css/'.$CFG->skin.'/default_ie7.css','all','IE 7');
	$header->cssFile('css/'.$CFG->skin.'/default_ie8.css','all','IE 8');
	$header->jsFile('js/jquery-1.4.2.min.js');
	$header->jsFile('js/jquery-ui-1.8.5.custom.min.js');
	$header->jsFile('js/ajax.js');
	$header->jsFile('js/calendar.js');
	$header->jsFile('js/colorpicker.js');
	$header->jsFile('js/comments.js');
	$header->jsFile('js/form.js');
	$header->jsFile('js/file_manager.js');
	$header->jsFile('js/flow_chart.js');
	$header->jsFile('js/gallery.js');
	$header->jsFile('js/grid.js');
	$header->jsFile('js/multi_list.js');
	$header->jsFile('js/popups.js');
	$header->jsFile('js/page_maker.js');
	$header->jsFile('js/permissions.js');
	$header->jsFile('js/swfupload.js');
	$header->jsFile('js/jquery.swfupload.js');
	$header->jsFile('ckeditor/ckeditor.js');
	$header->jsFile('js/Ops.js');
	$header->js('CKEDITOR.dtd.$removeEmpty[\'span\'] = false;');
	$header->display();
	$header->getJsGlobals();
}

if ($_REQUEST['authy_form']) {
	$token1 = preg_replace("/[^0-9]/", "",$_REQUEST['authy_form']['token']);
	
	if (!($token1 > 0))
		Errors::add('Invalid token.');

	if (!is_array(Errors::$errors)) {
		$response = Google2FA::verify_key(User::$info['authy_id'],$token1);
		if (!$response)
			Errors::add('Invalid token.');

		if (!is_array(Errors::$errors)) {
			$_SESSION['token_verified'] = 1;
			Errors::$errors = false;
		}
	}
}

if (User::isLoggedIn() && !(User::$info['verified_authy'] == 'Y' && !($_SESSION['token_verified'] > 0))) {
	$CFG->user_id = User::$info['id'];
	$CFG->group_id = User::$info['f_id'];
	if (!$CFG->bypass || ($CFG->url == 'edit_page' && !$_REQUEST['tab_bypass'])) {
		include_once 'includes/popups.php';
?>
<div id="head">
	<?
	$logos = DB::getFiles('settings_files',1,'logo',1);
	$logo_img = ($logos) ? 'uploads/'.$logos[0]['name'].'_logo.png' : 'images/logo.png';
	?>
	<div class="logo"><img src="<?= $logo_img ?>" /></div>
	<div class="nav_buttons">
		<? if (User::$info['is_admin'] == 'Y') { ?>
		<div class="nav_button admin">
			<div class="c">
				<div class="icon"></div>
				<div class="label"><?= $CFG->admin_button ?></div>
				<div class="drop"></div>
				<div class="clear"></div>
			</div>
			<div class="options">
				<div class="contain">
					<?= Link::url('settings','<div class="icon settings"></div><div class="label1">'.$CFG->path_settings.'</div>') ?>
					<?= Link::url('users','<div class="icon users"></div><div class="label1">'.$CFG->path_users.'</div>',false,false,false,'content','alt') ?>
					<? if ($CFG->url != 'edit_page') { ?>
					<a href="#" onclick="pmOpenPage()"><div class="icon edit_this"></div><div class="label1"><?= $CFG->edit_tabs_this_button?></div></a>
					<?= Link::url('edit_tabs','<div class="icon edit_pages"></div><div class="label1">'.$CFG->edit_tabs_button.'</div>',false,false,false,'content','alt') ?>
					<? } ?>
					<div class="t_shadow"></div>
					<div class="r_shadow"></div>
					<div class="b_shadow"></div>
					<div class="l_shadow"></div>
					<div class="tl1_shadow"></div>
					<div class="tl2_shadow"></div>
					<div class="tr1_shadow"></div>
					<div class="tr2_shadow"></div>
					<div class="bl1_shadow"></div>
					<div class="bl2_shadow"></div>
					<div class="br1_shadow"></div>
					<div class="br2_shadow"></div>
				</div>
				<div class="clear"></div>
			</div>
			<div class="r"></div>
			<div class="l"></div>
		</div>
		<? } ?>
		<!--  div class="nav_button messages">
			<div class="c">
				<div class="icon"></div>
				<div class="label"><?= ($messages > 0) ? str_ireplace('[messages]',$messages,$CFG->messages_yes_button) : $CFG->messages_no_button ?></div>
				<div class="drop"></div>
				<div class="clear"></div>
			</div>
			<div class="options">
				<div class="contain">
					<?
					// MESSAGES GO HERE
					?>
					<div class="t_shadow"></div>
					<div class="r_shadow"></div>
					<div class="b_shadow"></div>
					<div class="l_shadow"></div>
					<div class="tl1_shadow"></div>
					<div class="tl2_shadow"></div>
					<div class="tr1_shadow"></div>
					<div class="tr2_shadow"></div>
					<div class="bl1_shadow"></div>
					<div class="bl2_shadow"></div>
					<div class="br1_shadow"></div>
					<div class="br2_shadow"></div>
				</div>
				<div class="clear"></div>
			</div>
			<div class="r"></div>
			<div class="l"></div>
		</div -->
		<div class="nav_button user">
			<div class="c">
				<div class="icon"></div>
				<div class="label"><?= User::$info['first_name'].' '.User::$info['last_name'] ?></div>
				<div class="drop"></div>
				<div class="clear"></div>
			</div>
			<div class="options">
				<div class="contain">
					<?= Link::url('my-account','<div class="icon my_account"></div><div class="label1">'.$CFG->my_account_button.'</div>') ?>
					<a class="alt" href="index.php?logout=1&current_url=<?= $CFG->current_url ?>"><div class="icon logout"></div><div class="label1"><?= $CFG->logout_button ?></div></a>
					<div class="t_shadow"></div>
					<div class="r_shadow"></div>
					<div class="b_shadow"></div>
					<div class="l_shadow"></div>
					<div class="tl1_shadow"></div>
					<div class="tl2_shadow"></div>
					<div class="tr1_shadow"></div>
					<div class="tr2_shadow"></div>
					<div class="bl1_shadow"></div>
					<div class="bl2_shadow"></div>
					<div class="br1_shadow"></div>
					<div class="br2_shadow"></div>
				</div>
				<div class="clear"></div>
			</div>
			<div class="r"></div>
			<div class="l"></div>
		</div>
	</div>
	<div class="clear"></div>
	<div class="main_menu">
<?
		if ($CFG->url != 'edit_page') {
		?>
		<div class="menu_item">
			<?= Link::url('index.php','<div class="home_icon"></div>') ?>
		</div>
		<?
			$tabs = Ops::getTabs();
			if ($tabs) {
				foreach ($tabs as $tab) {
					$pages = Ops::getPages($tab['id']);
					$c = count($pages);
					$split = false;
					$width_class = false;

		
					if ($c > 10) {
						$split = ceil($c/3);
						$width_class = 'triple';
					}
					elseif ($c > 5) {
						$split = ceil($c/2);
						$width_class = 'double';
					}		

					
					echo '
					<div class="menu_item">
						'.Link::url($tab['url'],$tab['name'].(($pages) ? '<div class="drop"></div>' : ''),false,array('is_tab'=>1));
					
					if ($pages) {
						$i = 0;
						
						echo '
						<div class="options '.$width_class.'">
							<div class="contain">
								<ul>';
						foreach ($pages as $page) {
							echo '<li>'.Link::url($page['url'],$page['name']).'</li>';
							$i++;
							
							if ($split && ($i % $split == 0))
								echo '<div class="clear"></div></ul><ul>';
						}
						echo '
							<div class="clear"></div>
								</ul>
								<div class="t_shadow"></div>
								<div class="r_shadow"></div>
								<div class="b_shadow"></div>
								<div class="l_shadow"></div>
								<div class="tl1_shadow"></div>
								<div class="tl2_shadow"></div>
								<div class="tr1_shadow"></div>
								<div class="tr2_shadow"></div>
								<div class="bl1_shadow"></div>
								<div class="bl2_shadow"></div>
								<div class="br1_shadow"></div>
								<div class="br2_shadow"></div>
								<div class="clear"></div>
							</div>
						</div>';
					}
					echo '
					</div>';
				}
			}
		}
		else {
			$page_info = DB::getRecord((($CFG->is_tab) ? 'admin_tabs' : 'admin_pages'),$CFG->id,0,1);
			if (!$page_info['is_ctrl_panel'] || $page_info['is_ctrl_panel'] == 'N') {
				echo '
				<div class="menu_item"><a class="'.((!$CFG->action) ? 'high' : false).'" href="#">'.$CFG->pm_list_tab.'</a></div>
				<div class="menu_item"><a class="'.(($CFG->action == 'form') ? 'high' : false).'" href="#">'.$CFG->pm_form_tab.'</a></div>
				<div class="menu_item"><a class="'.(($CFG->action == 'record') ? 'high' : false).'" href="#">'.$CFG->pm_record_tab.'</a></div>';
			}
			else {
				echo '
				<div class="menu_item"><a class="'.((!$CFG->action) ? 'high' : false).'" href="#">'.$CFG->pm_ctrl_tab.'</a></div>';
			}
			
			echo '
			<div class="pm_nav">';
			PageMaker::showTabsPages();
			echo '
				<div class="pm_exit"><div class="pm_exit_icon" onclick="pmExitEditor();"></div> <a href="index.php" onclick="pmExitEditor();return false;">'.$CFG->pm_exit.'</a></div>
			</div>';
		}
?>
	</div>
</div>
<?	
		if ($CFG->url != 'edit_page')
			echo '<div id="content">';
	}
	
	if (!$_REQUEST['inset_id'] && !$_REQUEST['cal_bypass'] && !$_REQUEST['fm_bypass'] && $CFG->url != 'edit_page' && !$CFG->print)
		String::showPath();
		
	Errors::display();
	Messages::display();
	
	if ($CFG->print)
		echo '<div class="print_container">';
	if ($CFG->url == 'edit_tabs') {
		include_once 'includes/edit_tabs.php';
	}
	elseif ($CFG->url == 'edit_page') {
		include_once 'includes/edit_page.php';
	}
	elseif ($CFG->url == 'users') {
		include_once 'includes/users.php';
	}
	elseif ($CFG->url == 'settings') {
		include_once 'includes/settings.php';
	}
	elseif ($CFG->url == 'my-account') {
		include_once 'includes/account.php';
	}
	else {
		$form_name = ereg_replace("[^a-zA-Z_\-]", "",$_REQUEST['form_name']);
		if (!empty($form_name) && $form_name != 'form_filters' && $form_name != 'loginform' && !$_REQUEST['return_to_self']) {
			$form = new Form($form_name);
			$form->verify();
			$form->save();
			$form->show_errors();
			$form->show_messages();
		}

		$control = new Control($CFG->url,$CFG->action,$CFG->is_tab);
	}
	
	if ($CFG->print)
		echo '</div>';

	echo '
	<div class="clear">&nbsp;</div>
	<input type="hidden" id="page_url" value="'.$CFG->editor_page_id.'" />
	<input type="hidden" id="page_is_tab" value="'.$CFG->editor_is_tab.'" />
	<input type="hidden" id="page_action" value="'.$CFG->action.'" />
	<script type="text/javascript">footerToBottom(\'credits\');scaleBackstage();</script>';
		
	if (!$CFG->bypass || $CFG->url != 'edit_page') {
		echo '</div>';
	}
}
elseif (User::isLoggedIn() && (User::$info['verified_authy'] == 'Y' && !($_SESSION['token_verified'] > 0))) {
	if ($_REQUEST['authy_form']) {
		Errors::display();
		Messages::display();
	}
	
	$logos = DB::getFiles('settings_files',1,'logo',1);
	$logo_img = ($logos) ? 'uploads/'.$logos[0]['name'].'_logo.png' : 'images/logo.png';
	
	echo '<div class="login_box">
				<div class="login_logo"><img src="'.$logo_img.'" title="Logo" alt="Logo" /></div>
				<div class="logform">
		';
	
	$l_form = new Form('authy_form');
	$l_form->info['token'] = preg_replace("/[^0-9]/", "",$l_form->info['token']);
	$l_form->textInput('token','Enter token');
	$l_form->submitButton('submit','Verify Token');
	$l_form->display();
	
	echo '</div><div class="clear"></div></div>';
}
else {
	
	if ($_REQUEST['loginform']) {
		Errors::display();
		Messages::display();
	}
	
	$logos = DB::getFiles('settings_files',1,'logo',1);
	$logo_img = ($logos) ? 'uploads/'.$logos[0]['name'].'_logo.png' : 'images/logo.png';

	echo '<div class="login_box">
			<div class="login_logo"><img src="'.$logo_img.'" title="Logo" alt="Logo" /></div>
			<div class="logform">
	';
	
	$l_form = new Form('loginform');
	$l_form->info['user'] = ereg_replace("[^0-9a-zA-Z!@#$%&*?\.\-\_]", "",$l_form->info['user']);
	$l_form->info['pass'] = ereg_replace("[^0-9a-zA-Z!@#$%&*?\.\-\_]", "",$l_form->info['pass']);
	$l_form->textInput('user',$CFG->user_username,false,false,false,false,false,false,false,false,false,false,false,true);
	$l_form->passwordInput('pass',$CFG->user_password);
	$l_form->submitButton('submit','Log In');
	$l_form->hiddenInput('bs_db_name',false,$_REQUEST['bs_db_name']);
	$l_form->display();
	
	echo '</div><div class="clear"></div></div>';
}
if (!$CFG->bypass || ($CFG->url == 'edit_page' && !$_REQUEST['tab_bypass'])) {
	echo '
	<div class="credits" id="credits"><div>&copy; 2011 <a href="http://www.organic.com.pa">Organic Technologies</a>. Derechos reservados.</div></div>
	</body></html>'; 
}

?>