<!-- Footer
======================================= -->

<div class="footer">

	<div class="arrow_02"></div>
	
    <div class="clearfix mar_top5"></div>
	
    <div class="container">
    
   		<div class="one_fourth">
            
            <div class="footer_logo"><img src="images/logo2.png" alt="" /></div>
            
            <ul class="contact_address">
                <li><img src="images/footer-wmap.png" alt="" /></li>
            </ul>
            
        </div>
        
        <div class="one_fifth">
            <h2><?= Lang::string('home-basic-nav') ?></h2>
            <ul class="list">
             	<li><a href="index.php"><?= Lang::string('home') ?></a></li>
                <li><a href="order-book.php"><?= Lang::string('order-book') ?></a></li>
                <li><a href="help.php"><?= Lang::string('help') ?></a></li>
                <li><a href="news.php"><?= Lang::string('news') ?></a></li>
                <li><a href="contact.php"><?= Lang::string('contact') ?></a></li>
                <li><a href="terms.php"><?= Lang::string('terms') ?></a></li>
            </ul>
         </div>
         <div class="one_fifth">
            <h2><?= Lang::string('home-about-bitcoin') ?></h2>
            <ul class="list">   
                <li><a href="what-are-bitcoins.php"><?= Lang::string('what-are-bitcoins') ?></a></li>
                <li><a href="how-bitcoin-works.php"><?= Lang::string('how-bitcoin-works') ?></a></li>
                <li><a href="trading-bitcoins.php"><?= Lang::string('trading-bitcoins') ?></a></li>
                <li><a href="how-to-register.php"><?= Lang::string('how-to-register') ?></a></li>
                <li><a href="fee-schedule.php"><?= Lang::string('fee-schedule') ?></a></li>
            </ul>
         </div>
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
    </div>
	
    <div class="clearfix mar_top5"></div>
    
</div>


<div class="copyright_info">

    <div class="container">
    
        <div class="one_half">
        
            <b>Copyright &copy; 2014 WLOX. All rights reserved.  <a href="terms.php">Terms of Use</a> | <a href="privacy.php">Privacy Policy</a></b>
            
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
<script type="text/javascript" src="js/ops.js"></script>

<? if ($CFG->self == 'index.php' || $CFG->self == 'order-book.php') { ?>
<!-- flot -->
<script type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script type="text/javascript" src="js/flot/jquery.flot.time.js"></script>
<script type="text/javascript" src="js/flot/jquery.flot.crosshairs.js"></script>
<? } ?>

<? if ($CFG->self == 'security.php') { ?>
<!-- authy -->
<script src="https://www.authy.com/form.authy.min.js" type="text/javascript"></script>
<? } ?>

<? if ($CFG->self == 'index.php') { ?>
<!-- countdown -->
<script type="text/javascript" src="js/countdown/jquery.countdown.js"></script>
<? ($CFG->language == 'es') ? '<script type="text/javascript" src="js/countdown/jquery.countdown-es.js"></script>' : '' ?>
<? } ?>

<!-- main menu -->
<script type="text/javascript" src="js/mainmenu/ddsmoothmenu.js"></script>
<script type="text/javascript" src="js/mainmenu/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="js/mainmenu/selectnav.js"></script>

<!-- jquery jcarousel -->
<script type="text/javascript" src="js/jcarousel/jquery.jcarousel.min.js"></script>

<!-- REVOLUTION SLIDER -->
<script type="text/javascript" src="js/revolutionslider/rs-plugin/js/jquery.themepunch.revolution.min.js"></script>

<script type="text/javascript" src="js/mainmenu/scripts.js"></script>

<? if ($CFG->self == 'api-docs.php') { ?>
<script type="text/javascript" src="js/prism.js"></script>
<? } ?>

<!-- scroll up -->
<script type="text/javascript">
    $(document).ready(function(){
 
        $(window).scroll(function(){
            if ($(this).scrollTop() > 100) {
                $('.scrollup').fadeIn();
            } else {
                $('.scrollup').fadeOut();
            }
        });
 
        $('.scrollup').click(function(){
            $("html, body").animate({ scrollTop: 0 }, 500);
            return false;
        });
 
    });
</script>


<!-- jquery jcarousel -->
<script type="text/javascript">

	jQuery(document).ready(function() {
			jQuery('#mycarousel').jcarousel();
	});
	
	jQuery(document).ready(function() {
			jQuery('#mycarouseltwo').jcarousel();
	});
	
	jQuery(document).ready(function() {
			jQuery('#mycarouselthree').jcarousel();
	});
	
	jQuery(document).ready(function() {
			jQuery('#mycarouselfour').jcarousel();
	});
	
</script>


<!-- REVOLUTION SLIDER -->
<script type="text/javascript">

	var tpj=jQuery;
	tpj.noConflict();

	tpj(document).ready(function() {

	if (tpj.fn.cssOriginal!=undefined)
		tpj.fn.css = tpj.fn.cssOriginal;

		var api = tpj('.fullwidthbanner').revolution(
			{
				delay:9000,
				startwidth:1170,
				startheight:500,

				onHoverStop:"on",						// Stop Banner Timet at Hover on Slide on/off

				thumbWidth:100,							// Thumb With and Height and Amount (only if navigation Tyope set to thumb !)
				thumbHeight:50,
				thumbAmount:3,

				hideThumbs:200,
				navigationType:"none",				// bullet, thumb, none
				navigationArrows:"solo",				// nexttobullets, solo (old name verticalcentered), none

				navigationStyle:"round",				// round,square,navbar,round-old,square-old,navbar-old, or any from the list in the docu (choose between 50+ different item), custom


				navigationHAlign:"center",				// Vertical Align top,center,bottom
				navigationVAlign:"bottom",					// Horizontal Align left,center,right
				navigationHOffset:30,
				navigationVOffset:-40,

				soloArrowLeftHalign:"left",
				soloArrowLeftValign:"center",
				soloArrowLeftHOffset:0,
				soloArrowLeftVOffset:0,

				soloArrowRightHalign:"right",
				soloArrowRightValign:"center",
				soloArrowRightHOffset:0,
				soloArrowRightVOffset:0,

				touchenabled:"on",						// Enable Swipe Function : on/off


				stopAtSlide:-1,							// Stop Timer if Slide "x" has been Reached. If stopAfterLoops set to 0, then it stops already in the first Loop at slide X which defined. -1 means do not stop at any slide. stopAfterLoops has no sinn in this case.
				stopAfterLoops:-1,						// Stop Timer if All slides has been played "x" times. IT will stop at THe slide which is defined via stopAtSlide:x, if set to -1 slide never stop automatic

				hideCaptionAtLimit:0,					// It Defines if a caption should be shown under a Screen Resolution ( Basod on The Width of Browser)
				hideAllCaptionAtLilmit:0,				// Hide all The Captions if Width of Browser is less then this value
				hideSliderAtLimit:0,					// Hide the whole slider, and stop also functions if Width of Browser is less than this value


				fullWidth:"on",

				shadow:0								//0 = no Shadow, 1,2,3 = 3 Different Art of Shadows -  (No Shadow in Fullwidth Version !)

			});

});



</script>

<script type="text/javascript" src="js/sticky-menu/core.js"></script>

<!-- testimonials -->
<script type="text/javascript">//<![CDATA[ 
$(window).load(function(){
$(".controlls li a").click(function(e) {
    e.preventDefault();
    var id = $(this).attr('class');
    $('#slider div:visible').fadeOut(500, function() {
        $('div#' + id).fadeIn();
    })
});
});//]]>  

</script>

</body>
</html>
