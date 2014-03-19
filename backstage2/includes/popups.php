<div id="mask"></div>
<div id="edit_box" class="popup area">
	<h2 class="popup_bar">
		<div class="bpath"></div>
		<a class="close" onclick="closePopup(this)"></a>
	</h2>
	<div class="box_bar"></div>
	<div class="box_tl"></div>
	<div class="box_tr"></div>
	<div class="box_bl"></div>
	<div class="box_br"></div>
	<div class="t_shadow"></div>
	<div class="r_shadow"></div>
	<div class="b_shadow"></div>
	<div class="l_shadow"></div>
	<div class="box_b"></div>
	<div class="popup_content"></div>
	<div class="resize"></div>
</div>
<div id="message_box" class="popup area">
	<h2 class="popup_bar">
		<?= $CFG->path_message ?>
		<a class="close" onclick="closePopup(this)"></a>
	</h2>
	<div class="box_bar"></div>
	<div class="box_tl"></div>
	<div class="box_tr"></div>
	<div class="box_bl"></div>
	<div class="box_br"></div>
	<div class="t_shadow"></div>
	<div class="r_shadow"></div>
	<div class="b_shadow"></div>
	<div class="l_shadow"></div>
	<div class="box_b"></div>
	<div class="prompt_space">
		<div class="popup_content"></div>
		<div onmouseover="Ops.buttonOver(this)" onmouseout="Ops.buttonOut(this)" class="button primary">
			<input onclick="closePopup(this,1)" type="button" value="<?= $CFG->ok_button ?>" />
			<div class="l"></div>
			<div class="c"></div>
			<div class="r"></div>
		</div>
		<div class="clear"></div>
	</div>
	<div class="resize"></div>
</div>
<div id="error_box" class="popup area">
	<h2 class="popup_bar">
		<?= $CFG->path_error ?>
		<a class="close" onclick="closePopup(this)"></a>
	</h2>
	<div class="box_bar"></div>
	<div class="box_tl"></div>
	<div class="box_tr"></div>
	<div class="box_bl"></div>
	<div class="box_br"></div>
	<div class="t_shadow"></div>
	<div class="r_shadow"></div>
	<div class="b_shadow"></div>
	<div class="l_shadow"></div>
	<div class="box_b"></div>
	<div class="popup_content"></div>
	<input id="button" onclick="closePopup(this)" type="button" value="<?= $CFG->ok_button ?>" />
	<div class="resize"></div>
</div>
<div id="prompt_box" class="popup area">
	<h2 class="popup_bar">
		<?= $CFG->path_prompt ?>
		<a class="close" onclick="closePopup(this)"></a>
	</h2>
	<div class="box_bar"></div>
	<div class="box_tl"></div>
	<div class="box_tr"></div>
	<div class="box_bl"></div>
	<div class="box_br"></div>
	<div class="t_shadow"></div>
	<div class="r_shadow"></div>
	<div class="b_shadow"></div>
	<div class="l_shadow"></div>
	<div class="box_b"></div>
	<div class="prompt_space">
		<ul class="messages">
			<li><div class="warning_icon"></div><div class="popup_content"></div></li>
		</ul>
		<div onmouseover="Ops.buttonOver(this)" onmouseout="Ops.buttonOut(this)" class="button primary">
			<input onclick="closePopup(this,1)" type="button" value="<?= $CFG->ok_button ?>" />
			<div class="l"></div>
			<div class="c"></div>
			<div class="r"></div>
		</div>
		<div onmouseover="Ops.buttonOver(this)" onmouseout="Ops.buttonOut(this)" class="button">
			<input onclick="closePopup(this)" type="button" value="<?= $CFG->cancel_button ?>" />
			<div class="l"></div>
			<div class="c"></div>
			<div class="r"></div>
		</div>
		<div class="clear"></div>
	</div>
	<div class="resize"></div>
</div>
<div id="attributes_box" class="popup area">
	<h2 class="popup_bar">
		<?= $CFG->path_attributes ?>
		<div class="bpath"></div>
		<a class="close" onclick="closePopup(this)"></a>
	</h2>
	<div class="box_bar"></div>
	<div class="box_tl"></div>
	<div class="box_tr"></div>
	<div class="box_bl"></div>
	<div class="box_br"></div>
	<div class="t_shadow"></div>
	<div class="r_shadow"></div>
	<div class="b_shadow"></div>
	<div class="l_shadow"></div>
	<div class="box_b"></div>
	<div class="popup_content"></div>
	<input type="hidden" id="confirm_action" value="" />
	<div onmouseover="Ops.buttonOver(this)" onmouseout="Ops.buttonOut(this)" class="button primary">
		<input onclick="confirmAction()" type="button" value="<?= $CFG->ok_button ?>" />
		<div class="l"></div>
		<div class="c"></div>
		<div class="r"></div>
	</div>
	<div onmouseover="Ops.buttonOver(this)" onmouseout="Ops.buttonOut(this)" class="button">
		<input onclick="closePopup(this)" type="button" value="<?= $CFG->cancel_button ?>" />
		<div class="l"></div>
		<div class="c"></div>
		<div class="r"></div>
	</div>
	<div class="resize"></div>
</div>