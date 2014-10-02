<?php
include '../cfg/cfg.php';

if ($_REQUEST['contact']) {
	$_REQUEST['contact']['first_name'] = preg_replace("/[^\da-z ]/i", "",$_REQUEST['contact']['first_name']);
	$_REQUEST['contact']['last_name'] = preg_replace("/[^\da-z ]/i", "",$_REQUEST['contact']['last_name']);
	$_REQUEST['contact']['company'] = preg_replace("/[^\da-z ]/i", "",$_REQUEST['contact']['company']);
	$_REQUEST['contact']['email'] = preg_replace("/[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/", "",$_REQUEST['contact']['email']);
	$_REQUEST['contact']['country'] = preg_replace("/[^0-9]/", "",$_REQUEST['contact']['country']);
	$_REQUEST['contact']['subject'] = preg_replace("/[^\da-z ]/i", "",$_REQUEST['contact']['subject']);
	$_REQUEST['is_caco'] = (!$_REQUEST['is_caco']) ? array('contact'=>1) : $_REQUEST['is_caco'];
}

API::add('Content','getRecord',array('contact'));
API::add('Content','getRecord',array('contact-small'));
API::add('User','getCountries');
$query = API::send();

$content = $query['Content']['getRecord']['results'][0];
$content1 = $query['Content']['getRecord']['results'][1];
$page_title = $content['title'];
$countries = $query['User']['getCountries']['results'][0];

$contact = new Form('contact',false,false,'form2');
$contact->verify();

if ($_REQUEST['contact'] && $_SESSION["contact_uniq"] != $_REQUEST['contact']['uniq'])
	$contact->errors[] = 'Page expired.';

if ($_REQUEST['contact'] && is_array($contact->errors)) {
	$errors = array();
	foreach ($contact->errors as $key => $error) {
		if (stristr($error,'login-required-error')) {
			$errors[] = Lang::string('settings-'.str_replace('_','-',$key)).' '.Lang::string('login-required-error');
		}
		elseif (strstr($error,'-')) {
			$errors[] = Lang::string($error);
		}
		else {
			$errors[] = $error;
		}
	}
	Errors::$errors = $errors;
}
elseif ($_REQUEST['contact'] && !is_array($contact->errors)) {
	API::add('SiteEmail','contactForm',array($contact->info));
	$query = API::send();
	
	Messages::$messages = array(Lang::string('contact-message'));
	$show_message = true;
	$show_mask = true;
}

$_SESSION["contact_uniq"] = md5(uniqid(mt_rand(),true));
include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="contact.php"><?= Lang::string('contact') ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_topics.php'; ?>
	<div class="content_right">
    	<div class="content_fullwidth">
    		<div class="text"><?= $content['content'] ?></div>
			    <br>
		    <div class="one_half">
			    <h3><i><?= Lang::string('contact-inquiries') ?></i></h3>
			    <?
			    Messages::display();
			    Errors::display();
			    $contact->textInput('first_name',Lang::string('settings-first-name'),1,User::$info['first_name']);
			    $contact->textInput('last_name',Lang::string('settings-last-name'),1,User::$info['last_name']);
			    $contact->textInput('company',Lang::string('settings-company'));
			    $contact->textInput('email',Lang::string('settings-email'),'email',User::$info['email']);
			    $contact->selectInput('country',Lang::string('settings-country'),0,User::$info['country'],$countries,false,array('name'));
			    $contact->textInput('subject',Lang::string('settings-subject'),1);
			    $contact->textEditor('message',Lang::string('settings-message'),1,false,false,false,false,true,false,200);
			    $contact->captcha(Lang::string('settings-capcha'));
			    $contact->HTML('<div class="form_button"><input type="submit" name="submit" value="'.Lang::string('contact-send').'" class="but_user" /></div>');
			    $contact->hiddenInput('uniq',1,$_SESSION["contact_uniq"]);
			    $contact->display();
			    ?>
		    </div>
		    <div class="one_half last">
		        <div class="address-info">
		            <h3><i><?= $content1['title'] ?></i></h3>
		                <ul>
		                <li><?= $content1['content'] ?></li>
		            </ul>
		        </div>
		   	</div>     
		</div>
    </div>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>