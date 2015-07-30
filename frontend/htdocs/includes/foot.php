<!-- Footer
======================================= -->

<div class="footer">

	<div class="arrow_02"></div>
	
    <div class="clearfix mar_top5"></div>
	
    <div class="container">
    
   		<div class="one_fourth">
            
            <div class="footer_logo"><img src="images/logo4.png" alt="" /></div>
            
            <ul class="contact_address">
                <li><img src="images/footer-wmap.png" alt="" /></li>
            </ul>
            
        </div>
        
        <div class="one_fifth">
            <h2><?= Lang::string('home-basic-nav') ?></h2>
            <ul class="list">
             	<li><a href="<?= Lang::url('index.php') ?>"><?= Lang::string('home') ?></a></li>
                <li><a href="<?= Lang::url('order-book.php') ?>"><?= Lang::string('order-book') ?></a></li>
                <li><a href="<?= (User::isLoggedIn()) ? 'help.php' : 'https://support.1btcxe.com' ?>"><?= Lang::string('help') ?></a></li>
                <li><a href="<?= Lang::url('contact.php') ?>"><?= Lang::string('contact') ?></a></li>
                <li><a href="<?= Lang::url('terms.php') ?>"><?= Lang::string('terms') ?></a></li>
                <li><a href="api-docs.php"><?= Lang::string('api-docs') ?></a></li>
            </ul>
         </div>
         <div class="one_fifth">
            <h2><?= Lang::string('about') ?></h2>
            <ul class="list">
             	<li><a href="<?= Lang::url('about.php') ?>"><?= Lang::string('about') ?></a></li>
             	<li><a href="<?= Lang::url('our-security.php') ?>"><?= Lang::string('our-security') ?></a></li>
             	<li><a href="<?= Lang::url('news.php') ?>"><?= Lang::string('news') ?></a></li>
             	<li><a href="<?= Lang::url('fee-schedule.php') ?>"><?= Lang::string('fee-schedule') ?></a></li>
             	<li><a href="https://github.com/wlox/wlox/" target="_blank"><?= Lang::string('home-github') ?></a></li>
            </ul>
         </div>
         <? if (User::isLoggedIn()) { ?>
         <div class="one_fifth last">
            <h2><?= Lang::string('home-account-functions') ?></h2>
            <ul class="list"> 
                <li><a href="account.php"><?= Lang::string('account') ?></a></li>
                <li><a href="open-orders.php"><?= Lang::string('open-orders') ?></a></li>
                <li><a href="transactions.php"><?= Lang::string('transactions') ?></a></li>
                <li><a href="security.php"><?= Lang::string('security') ?></a></li>
                <li><a href="buy-sell.php"><?= Lang::string('buy-sell') ?></a></li>
                <li><a href="deposit.php"><?= Lang::string('deposit') ?></a></li>
                <li><a href="withdraw.php"><?= Lang::string('withdraw') ?></a></li>
            </ul>
        </div>
        <? } else { ?>
         <div class="one_fifth last">
            <h2><?= Lang::string('home-about-bitcoin') ?></h2>
            <ul class="list">   
                <li><a href="<?= Lang::url('what-are-bitcoins.php') ?>"><?= Lang::string('what-are-bitcoins') ?></a></li>
                <li><a href="<?= Lang::url('how-bitcoin-works.php') ?>"><?= Lang::string('how-bitcoin-works') ?></a></li>
                <li><a href="<?= Lang::url('how-to-register.php') ?>"><?= Lang::string('how-to-register') ?></a></li>
            </ul>
         </div>
         <? } ?>
    </div>
	
    <div class="clearfix mar_top5"></div>
    
</div>


<div class="copyright_info">

    <div class="container">
    
        <div class="one_half">
        
            <b>Copyright &copy; 2014 WLOX. All rights reserved.</b>
            
        </div>
    
    	<div class="one_half last">
     		
            <ul class="footer_social_links">
                <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
            </ul>
                
    	</div>
    
    </div>
    
</div><!-- end copyright info -->


<a href="#" class="scrollup">Scroll</a><!-- end scroll to top of the page-->

</div>
    
<!-- ######### JS FILES ######### -->
<script type="text/javascript" src="js/universal/jquery.js"></script>
<script type="text/javascript" src="js/universal/jquery-ui-1.10.3.custom.min.js"></script>

<!-- main js -->
<script type="text/javascript" src="js/ops.js?v=20150730"></script>

<? if ($CFG->self == 'index.php' || $CFG->self == 'order-book.php' || $CFG->self == 'btc_to_currency.php') { ?>
<!-- flot -->
<script type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script type="text/javascript" src="js/flot/jquery.flot.time.js"></script>
<script type="text/javascript" src="js/flot/jquery.flot.crosshairs.js"></script>
<? } ?>

<? if ($CFG->self == 'security.php') { ?>
<!-- authy -->
<script src="https://www.authy.com/form.authy.min.js" type="text/javascript"></script>
<? } ?>

<? if ($CFG->self == 'index.php' || $CFG->self == 'login.php') { ?>
<!-- countdown -->
<script type="text/javascript" src="js/countdown/jquery.countdown.js"></script>
<?= ($CFG->language != 'en' && !empty($CFG->language)) ? '<script type="text/javascript" src="js/countdown/jquery.countdown-'.(($CFG->language == 'zh') ? 'zh-CN' : $CFG->language).'.js"></script>' : '' ?>
<? } ?>

<? if ($CFG->self == 'api-docs.php') { ?>
<script type="text/javascript" src="js/prism.js"></script>
<? } ?>



</script>

</body>
</html>
