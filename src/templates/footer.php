	</div><!-- #container -->
</div><!-- #wrap -->

<footer id="footer">
	<span id="top"><a href="#lotusbase__wrap" title="Return to top"><span class="pictogram icon-up-open-big">Top</span></a></span>
	<section class="cols">
		<div class="col about-us">
			<h2>About us</h2>
			<p>Developed with the end-user in mind, <em>Lotus</em> Base is an user-friendly web interface that brings together various resources, tools and datasets available for the model legume <em>Lotus japonicus</em>. We aim to make access to these data easy for researchers, scientists and members of the public across the world.</p>
			<ul class="cols">
				<li class="col bg-fill"><a href="/meta/about/"><img src="/dist/images/header/lore1/lore1_01.jpg" alt="" title="" /><span>About Us</span></a></li>
				<li class="col bg-fill"><a href="/meta/team/"><img src="/dist/images/team/carb.jpg" alt="" title="" /><span>Meet the Team</span></a></li>
			</ul>
		</div>
		<div class="col newsletter">
			<h2>Stay updated</h2>
			<p>Register to be part of the <em>Lotus</em> Base mailing list to be informed of latest updates and developments. We promise, no spam!</p>
			<form action="https://carb.us5.list-manage.com/subscribe/post?u=b05ff730875b834016cea7a13&amp;id=c469e14ec3" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
				<input type="text" value="" name="FNAME" class="" id="mce-FNAME" placeholder="First name">
				<input type="text" value="" name="LNAME" class="" id="mce-LNAME" placeholder="Last name">
				<input type="email" value="" name="EMAIL" class="" id="mce-EMAIL" placeholder="Email">
				<input type="submit" value="Subscribe Now" name="subscribe" id="mc-embedded-subscribe" class="button">
			</form>
		</div>
	</section>

	<ul class="cols affiliations flex-wrap__no">
		<li><a href="http://carb.au.dk"><img src="/dist/images/logos/carb.png" title="Centre for Carbohydrate Recognition and Signalling" alt="Centre for Carbohydrate Recognition and Signalling" /></a></li>
		<li><a href="http://au.dk"><img src="/dist/images/logos/au.png" title="Aarhus University" alt="Aarhus University" /></a></li>
		<li><a href="http://dg.dk"><img src="/dist/images/logos/dg.png" title="Danish National Research Foundation" alt="Danish National Research Foundation" /></a></li>
	</ul>

	<section class="lastnote">	
		<p>All rights reserved. 2012&ndash;<?php echo date('Y'); ?> &copy; <a href="http://carb.au.dk/" title="Centre for Carbohydrate Recognition and Signalling">Centre for Carbohydrate Recognition and Signalling</a>, Denmark.<br />Brought to you by the <a href="<?php echo WEB_ROOT; ?>/meta/team"><em>Lotus</em> Base Team</a>, powered by coffee.</p>
	</section>

	<!-- Load jQuery Libraries -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js"></script>

	<!-- Load jQuery plugins -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.14.0/jquery.validate.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.0.3/js.cookie.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-throttle-debounce/1.1/jquery.ba-throttle-debounce.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.isotope/2.2.2/isotope.pkgd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/3.2.0/imagesloaded.pkgd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.1/moment.min.js"></script>
    <script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/jquery-deparam.min.js"></script>

    <!-- Non jQuery plugins -->
    <script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/regex-colorizer.min.js"></script>

	<!-- Load site functions -->
	<script>
		var access_token = '<?php echo LOTUSBASE_API_KEY; ?>';
	</script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/functions.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/modernizr-custom.min.js"></script>

	<!-- Google Analyrics -->
	<script>
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	ga('create', 'UA-37291877-2', 'auto');
	ga('send', 'pageview');
	</script>

</footer>