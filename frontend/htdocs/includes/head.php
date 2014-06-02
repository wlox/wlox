
<!doctype html>
<!--[if IE 7 ]>    <html lang="<?= $CFG->language ?>" class="isie ie7 oldie no-js"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?= $CFG->language ?>" class="isie ie8 oldie no-js"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?= $CFG->language ?>" class="isie ie9 no-js"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?= $CFG->language ?>" class="no-js"> <!--<![endif]-->

<head>
	<title><?= $page_title ?></title>
	
	<meta charset="utf-8">
	<meta name="keywords" content="" />
	<meta name="description" content="" />
    
    <!-- Favicon --> 
	<link rel="shortcut icon" href="images/favicon.ico">
    
    <!-- this styles only adds some repairs on idevices  -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    
    <!-- Google fonts - witch you want to use - (rest you can just remove) -->
    <link rel='stylesheet' href='http://fonts.googleapis.com/css?family=Open+Sans:400,800,700italic,700,600italic,600,400italic,300italic,300|Roboto:100,300,400,500,700&amp;subset=latin,latin-ext' type='text/css' />
    
    <!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
    
    <!-- ######### CSS STYLES ######### -->
	
    <link rel="stylesheet" href="css/reset.css" type="text/css" />
	<link rel="stylesheet" href="css/style.css" type="text/css" />
    
    <link rel="stylesheet" href="css/font-awesome/css/font-awesome.min.css">
    
    <!-- responsive devices styles -->
	<link rel="stylesheet" media="screen" href="css/responsive-leyouts.css" type="text/css" />
    
<!-- just remove the below comments witch color skin you want to use -->
    <!--<link rel="stylesheet" href="css/colors/lightblue.css" />-->
    <!--<link rel="stylesheet" href="css/colors/lightgreen.css" />-->
    <!--<link rel="stylesheet" href="css/colors/blue.css" />-->
    <!--<link rel="stylesheet" href="css/colors/green.css" />-->
    <!--<link rel="stylesheet" href="css/colors/red.css" />-->
    <link rel="stylesheet" href="css/colors/cyan.css" />
    <!--<link rel="stylesheet" href="css/colors/purple.css" />-->
    <!--<link rel="stylesheet" href="css/colors/pink.css" />-->
    <!--<link rel="stylesheet" href="css/colors/brown.css" />-->
    
<!-- just remove the below comments witch bg patterns you want to use --> 
    <!--<link rel="stylesheet" href="css/bg-patterns/pattern-default.css" />-->
    <!--<link rel="stylesheet" href="css/bg-patterns/pattern-one.css" />-->
    <!--<link rel="stylesheet" href="css/bg-patterns/pattern-two.css" />-->
    <!--<link rel="stylesheet" href="css/bg-patterns/pattern-three.css" />-->
    <!--<link rel="stylesheet" href="css/bg-patterns/pattern-four.css" />-->
    <!--<link rel="stylesheet" href="css/bg-patterns/pattern-five.css" />-->
    <!--<link rel="stylesheet" href="css/bg-patterns/pattern-six.css" />-->
    <!--<link rel="stylesheet" href="css/bg-patterns/pattern-seven.css" />-->
    <!--<link rel="stylesheet" href="css/bg-patterns/pattern-eight.css" />-->
    <!--<link rel="stylesheet" href="css/bg-patterns/pattern-nine.css" />-->

    
    <!-- sticky menu -->
    <link rel="stylesheet" href="js/sticky-menu/core.css">
    

    <!-- REVOLUTION SLIDER -->
    <link rel="stylesheet" type="text/css" href="js/revolutionslider/css/fullwidth.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="js/revolutionslider/rs-plugin/css/settings.css" media="screen" />
    
    <!-- jquery jcarousel -->
    <link rel="stylesheet" type="text/css" href="js/jcarousel/skin.css" />
    <link rel="stylesheet" type="text/css" href="js/jcarousel/skin2.css" />
	
	<!-- faqs -->
    <link rel="stylesheet" href="js/accordion/accordion.css" type="text/css" media="all">
    
    <!-- tabs css -->
    <link rel="stylesheet" type="text/css" href="js/tabs/tabs.css" />
    
    <!-- testimonials -->
    <link rel="stylesheet" href="js/testimonials/fadeeffect.css" type="text/css" media="all">
    
    <!-- testimonials -->
    <link rel="stylesheet" href="js/testimonials/fadeeffect.css" type="text/css" media="all">
    
    <!-- authy -->
    <link href="https://www.authy.com/form.authy.min.css" media="screen" rel="stylesheet" type="text/css">
    
    <!--  script type="text/javascript" src="../shared2/js/form.js"></script -->
	<script type="text/javascript" src="ckeditor/ckeditor.js"></script>

</head>

<body>
<input type="hidden" id="javascript_date_format" value="<?= Lang::string('javascript-date-format') ?>" />
<input type="hidden" id="javascript_mon_0" value="<?= Lang::string('jan') ?>" />
<input type="hidden" id="javascript_mon_1" value="<?= Lang::string('feb') ?>" />
<input type="hidden" id="javascript_mon_2" value="<?= Lang::string('mar') ?>" />
<input type="hidden" id="javascript_mon_3" value="<?= Lang::string('apr') ?>" />
<input type="hidden" id="javascript_mon_4" value="<?= Lang::string('may') ?>" />
<input type="hidden" id="javascript_mon_5" value="<?= Lang::string('jun') ?>" />
<input type="hidden" id="javascript_mon_6" value="<?= Lang::string('jul') ?>" />
<input type="hidden" id="javascript_mon_7" value="<?= Lang::string('aug') ?>" />
<input type="hidden" id="javascript_mon_8" value="<?= Lang::string('sep') ?>" />
<input type="hidden" id="javascript_mon_9" value="<?= Lang::string('oct') ?>" />
<input type="hidden" id="javascript_mon_10" value="<?= Lang::string('nov') ?>" />
<input type="hidden" id="javascript_mon_11" value="<?= Lang::string('dec') ?>" />
<input type="hidden" id="gmt_offset" value="<?= $CFG->timezone_offset ?>" />
<input type="hidden" id="is_logged_in" value="<?= User::isLoggedIn() ?>" />
<input type="hidden" id="cfg_orders_edit" value="<?= Lang::string('orders-edit') ?>" />
<input type="hidden" id="cfg_orders_delete" value="<?= Lang::string('orders-delete') ?>" />
<div class="site_wrapper">
   
<!-- HEADER -->
<header id="header">

	<!-- Top header bar -->
	<div id="topHeader">
    
	<div class="wrapper">
         
        <div class="top_contact_info">
        
        <div class="container">
        
            <ul class="tci_list_left">
                <li><a href="help.php"><?= Lang::string('help') ?></a></li>
                <li>|</li>
                <li><a href="contact.php"><?= Lang::string('contact') ?></a></li>
            </ul>
        
            <ul class="tci_list">
                <? if (!User::isLoggedIn()) { ?>
                <li><a href="login.php"><i class="fa fa-key"></i> <?= Lang::string('home-login') ?></a></li>
                <li>|</li>
                <li><a href="register.php"><i class="fa fa-user"></i> <?= Lang::string('home-register') ?></a></li>
                <? } else { ?>
                <li><a href="logout.php?log_out=1"><i class="fa fa-unlock"></i> <?= Lang::string('log-out') ?></a></li>
                <? } ?>
                <li class="empty margin-left">
                	<label for="language_selector"><img src="images/<?= $CFG->language ?>.png" /></label>
                	<select id="language_selector" class="lang">
                		<option value="en" <?= ($CFG->language == 'en') ? 'selected="selected"' : '' ?>>English</option>
                		<option value="es" <?= ($CFG->language == 'es') ? 'selected="selected"' : '' ?>>Español</option>
                	</select>
                </li>
            </ul>
            
        </div>
        
    </div><!-- end top contact info -->
            
 	</div>
    
	</div>
	
    
	<div id="trueHeader">
    
	<div class="wrapper">
    
     <div class="container">
    
		<!-- Logo -->
		<div class="one_fourth"><a href="index.php" id="logo"></a></div>
		
        <!-- Menu -->
        <div class="three_fourth last">
           
           <nav id="access" class="access" role="navigation">
           
            <div id="menu" class="menu">
                
                <ul id="tiny">
                    <li><a href="index.php" <?= ($CFG->self == 'index.php' || !$CFG->self) ? 'class="active"' : '' ?>><?= Lang::string('home') ?></a></li>
                    <li><a href="order-book.php" <?= ($CFG->self == 'order-book.php') ? 'class="active"' : '' ?>><?= Lang::string('order-book') ?></a></li>
                    <? if (!User::isLoggedIn()) { ?>
                    <li><a href="what-are-bitcoins.php" <?= ($CFG->self == 'what-are-bitcoins.php' || $CFG->self == 'how-bitcoin-works.php' || $CFG->self == 'trading-bitcoins.php') ? 'class="active"' : '' ?>><?= Lang::string('what-are-bitcoins') ?> <i class="fa fa-angle-down"></i></a>
                        <ul>
                        	<li><a href="how-bitcoin-works.php"><?= Lang::string('how-bitcoin-works') ?></a></li>
                            <li><a href="trading-bitcoins.php"><?= Lang::string('trading-bitcoins') ?></a></li>
                        </ul>
                    </li>
                    <li><a href="how-to-register.php" <?= ($CFG->self == 'how-to-register.php') ? 'class="active"' : '' ?>><?= Lang::string('how-to-register') ?></a> <i class="fa fa-angle-down"></i>
                    	<ul>
                        	<li><a href="securing-account.php"><?= Lang::string('securing-account') ?></a></li>
                        	<li><a href="funding-account.php"><?= Lang::string('funding-account') ?></a></li>
                        	<li><a href="withdrawing-account.php"><?= Lang::string('withdrawing-account') ?></a></li>
                        </ul>
                    </li>
                    <li><a href="fee-schedule.php" <?= ($CFG->self == 'fee-schedule.php') ? 'class="active"' : '' ?>><?= Lang::string('fee-schedule') ?></a></li>
                    <? } else { ?>
                    <li><a href="account.php" <?= ($CFG->self == 'account.php' || $CFG->self == 'open-orders.php' || $CFG->self == 'transactions.php' || $CFG->self == 'security.php') ? 'class="active"' : '' ?>><?= Lang::string('account') ?> <i class="fa fa-angle-down"></i></a>
                        <ul>
                        	<li><a href="open-orders.php"><?= Lang::string('open-orders') ?></a></li>
                            <li><a href="transactions.php"><?= Lang::string('transactions') ?></a></li>
                            <li><a href="security.php"><?= Lang::string('security') ?></a></li>
                            <li><a href="settings.php"><?= Lang::string('settings') ?></a></li>
                            <li><a href="bank-accounts.php"><?= Lang::string('bank-accounts') ?></a></li>
							<li><a href="bitcoin-addresses.php"><?= Lang::string('bitcoin-addresses') ?></a></li>
                            <li><a href="logout.php?log_out=1"><?= Lang::string('log-out') ?></a></li>
                        </ul>
                    </li>
                    <li><a href="buy-sell.php" <?= ($CFG->self == 'buy-sell.php') ? 'class="active"' : '' ?>><?= Lang::string('buy-sell') ?></a></li>
                    <li><a href="deposit.php" <?= ($CFG->self == 'deposit.php') ? 'class="active"' : '' ?>><?= Lang::string('deposit') ?></a></li>
                    <li><a href="withdraw.php" <?= ($CFG->self == 'withdraw.php') ? 'class="active"' : '' ?>><?= Lang::string('withdraw') ?></a></li>
                    <? } ?>
                </ul>
                
            </div>
            
        </nav><!-- end nav menu -->
      
        </div>
        
        
		</div>
		
	</div>
    
	</div>
    
</header><!-- end header -->