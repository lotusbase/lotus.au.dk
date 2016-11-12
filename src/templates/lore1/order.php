<?php

	// Database
	require_once('../config.php');

	// Error flag
	$error = false;
	$error_items = array();

	// Use country list
	use SameerShelavale\PhpCountriesArray\CountriesArray;

	// Declare global user object
	if(isset($_COOKIE['auth_token']) && auth_verify($_COOKIE['auth_token'])) {
		$user = auth_verify($_COOKIE['auth_token']);
	}

	// Check if any error messages are present
	if(isset($_SESSION['order_error'])) {
		$error = $_SESSION['order_error']['message'];
		$error_items = $_SESSION['order_error']['errors'];
		print_r($_SESSION['order_error']);
		unset($_SESSION['order_error']);
	}

	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		// Get average processing time
		$q = $db->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, `Timestamp`, `ShippedTimestamp`)) AS AverageProcessingTime
		FROM `orders_unique`
		WHERE
			`Timestamp` IS NOT NULL AND
			`ShippedTimestamp` IS NOT NULL
		");

		// Execute query
		$q->execute();

		// Get organisation
		$orgs = array('Select a pre-existing organization, or enter a new one' => '');
		$o = $db->prepare('SELECT Organization AS Organization FROM auth WHERE Organization != "" OR Organization IS NOT NULL GROUP BY Organization ORDER BY COUNT(*) DESC');
		$o->execute();
		while($rows = $o->fetch(PDO::FETCH_ASSOC)) {
			$orgs[$rows['Organization']] = $rows['Organization'];
		}

		// Get user address
		if($user['Salt']) {
			$ua = $db->prepare('SELECT Address, City, State, PostalCode, Country FROM auth WHERE Salt = ?');
			$ua->execute(array($user['Salt']));
			$ua_data = $ua->fetch(PDO::FETCH_ASSOC);
			
			foreach($ua_data as $ua_field => $ua_value) {
				$user[$ua_field] = $ua_value;
			}
		}

	} catch(PDOException $e) {
		$error = 'We have experienced a problem with the database: <code>'.$e->getMessage().'</code>';
	} catch(PDOException $err) {
		$error = 'We have encountered an error: <code>'.$e->getMessage().'</code>';
	}

	// Get statistics
	if($q->rowCount() > 0) {
		$row = $q->fetch();
		$processingTime = $row['AverageProcessingTime'];
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>Order Form &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/order.min.css" type="text/css" media="screen">
</head>
<body class="order">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<div class="align-center">
			<h1><em>LORE1</em> Order</h1>
			<span class="byline">Place an order for your <em>LORE1</em> mutants of interest</span>
		</div>');
		$header->set_header_background_image(WEB_ROOT.'/dist/images/header/lore1/lore1_01.jpg');
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title(array('<em>LORE1</em>', 'Order mutants'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<p>Please complete the following form to place an order on your <em>LORE1</em> lines for interest. You may only proceed to the next step when the current step is complete and valid, but you may navigate between previously cleared step.</p>
		<p>Your private information will be kept <em>strictly</em> confidential. Required fields are marked with an asterisk (<strong>*</strong>).</p>
		<?php if($error) { 
			echo '<p class="user-message warning">'.$error.'.</p>';
		} ?>
		<div id="incomplete-order" class="user-message note hidden">
			<p>An incomplete <em>LORE1</em> order has been found. You may proceed with completing it, or <a href="#" id="reset-local-storage">choose to remove this locally-stored order and start over again</a>.</p>
		</div>
		<form action="order-exec.php" method="POST" id="order-form" accept-charset="utf-8" class="has-group has-steps">

			<header id="form-step__header">
				<nav id="form-step__nav"><ul class="cols flex-wrap__nowrap"></ul></nav>

				<h3 class="align-center" id="form-step__title"></h3>
			
			</header>

			<div class="form-step form-step--disabled" id="form-step__facet-1" data-form-step="1" data-form-step-title="Customer contact details" data-form-step-title-short="Customer Details">
				<div class="cols" role="group">
					<div class="cols full-width">
						<label for="fname" class="col-one col-half-width">First Name <span class="asterisk" title="Required Field">*</span></label>
						<div class="col-two col-half-width">
							<input id="fname" name="fname" type="text" value="<?php echo is_logged_in() ? $user['FirstName'] : ''; ?>" placeholder="First Name" tabindex="1" required />
						</div>

						<label for="lname" class="col-one col-half-width align-right">Last Name <span class="asterisk" title="Required Field">*</span></label>
						<div class="col-two col-half-width">
							<input id="lname" name="lname" type="text" value="<?php echo is_logged_in() ? $user['LastName'] : ''; ?>" placeholder="Last Name" tabindex="2" required />
						</div>
					</div>

					<label for="email" class="col-one">Email <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two">
						<input id="email" name="email" type="email" value="<?php echo is_logged_in() ? $user['Email'] : ''; ?>" placeholder="Email address" tabindex="3" required />
					</div>
				</div>
			</div>

			<div class="form-step form-step--disabled" id="form-step__facet-2" data-form-step="2" data-form-step-title="Order information" data-form-step-title-short="Order Info.">
				<div class="cols" role="group">
					<label for="lines-input" class="col-one required"><em>LORE1</em> lines <a class="info" data-modal="search-help" title="How should I enter Plant ID?" data-modal-content="Please enter LORE1 lines separated by: a space &lt;kbd title='spacebar'&gt;_____&lt;/kbd&gt;, a comma &lt;kbd&gt;,&lt;/kbd&gt;, or a tab &lt;kbd&gt;&nbsp;&map;&nbsp;Tab&lt;/kbd&gt;.">?</a> <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two">
						<div class="multiple-text-input input-mimic">
							<ul class="input-values">
							<?php
								if($_GET['lore1']) {
									$lore1_array = explode(',', $_GET['lore1']);
									foreach($lore1_array as $lore1_item) {
										echo '<li data-input-value="'.escapeHTML($lore1_item).'">'.escapeHTML($lore1_item).'<span class="icon-cancel" data-action="delete"></span></li>';
									}
								}
							?>
								<li class="input-wrapper"><input type="text" class="validate--ignore" id="lines-input" placeholder="LORE1 Line ID" autocomplete="off" tabindex="4" /></li>
							</ul>
							<input class="input-hidden validate--ignore" type="hidden" name="lines" id="lines" value="<?php echo escapeHTML($_GET['lore1']); ?>" readonly required />
						</div>
						<small><strong>Separate each <em>LORE1</em> line identifier with a comma, space or tab.</strong></small>
						<div id="id-check"></div>
					</div>

					<label for="comments" class="col-one">Order Comments</label>
					<div class="col-two">
						<textarea id="comments" class="validate--ignore" name="comments" rows="3" placeholder="Order comments" tabindex="5"></textarea>
					</div>
				</div>
			</div>
			

			<div class="form-step form-step--disabled" id="form-step__facet-3" data-form-step="3" data-form-step-title="Shipping addresses" data-form-step-title-short="Shipping Info">
				<div class="cols has-legend" role="group">

					<span class="user-message legend">Shipping address</span>

					<p class="full-width">The address where your <em>LORE1</em> seeds will be shipped to.</p>

					<label for="shipping-institution" class="col-one">Organisation <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two">
						<select id="shipping-institution" name="shipping_institution" tabindex="12">
						<?php
							foreach($orgs as $title => $value) {
								echo '<option value="'.$value.'" '.(is_logged_in() && $user['Organization'] === $value ? 'selected': '').'>'.$title.'</option>';
							}
						?>
						</select>
					</div>

					<label for="shipping-address" class="col-one">Street Address <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two">
						<input id="shipping-address" name="shipping_address" placeholder="Street Address" value="<?php echo is_logged_in() ? $user['Address'] : ''; ?>" tabindex="13" value="" required />
					</div>

					<label for="shipping-city" class="col-one col-half-width">City <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<input id="shipping-city" name="shipping_city" type="text" value="<?php echo is_logged_in() && !empty($user['City']) ? $user['City'] : ''; ?>" placeholder="City" tabindex="14" required />
					</div>

					<label for="shipping-state" class="col-one col-half-width align-right">State / Region</label>
					<div class="col-two col-half-width">
						<input id="shipping-state" name="shipping_state" type="text" value="<?php echo is_logged_in() && !empty($user['State']) ? $user['State'] : ''; ?>" placeholder="State / Region" tabindex="15">
					</div>

					<label for="shipping-postalcode" class="col-one col-half-width">Postal Code <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<input id="shipping-postalcode" name="shipping_postalcode" type="text" value="<?php echo is_logged_in() && !empty($user['PostalCode']) ? $user['PostalCode'] : ''; ?>" placeholder="Postal Code"  tabindex="16" required />
					</div>

					<label for="shipping-country" class="col-one col-half-width align-right">Country <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<select name="shipping_country" id="shipping-country" tabindex="16" required>
							<option value="">Select a country</option>
						<?php
							$countries = CountriesArray::get2d('name', array('alpha2', 'alpha3'));
							foreach($countries as $name=>$meta) {
								echo '<option value="'.$meta['alpha3'].'" data-country-name="'.$name.'" data-country-alpha2="'.$meta['alpha2'].'" '.(is_logged_in() && !empty($user['Country']) && $user['Country'] === $meta['alpha3'] ? 'selected' : '').'>'.$name.'</option>';
							}
						?>
						</select>
					</div>
				</div>
			</div>

			<div class="form-step form-step--disabled" id="form-step__facet-4" data-form-step="4" data-form-step-title="Order Overview" data-form-step-title-short="Overview">
				<div class="full-width">
					<p>Before you submit your order, please review your order:</p>
					<div id="order-overview" class="cols">
						<div id="order-overview__meta">
							<div id="order-overview__shipping">
								<h4>Contact &amp; Shipping details</h4>
								<div id="order-overview__vcard" class="vcard"></div>
							</div>

							<div id="order-overview__lore1-lines">
								<h4><em>LORE1</em> lines</h4>
								<p>You have ordered <span id="order-overview__lore1-lines-count"></span>:</p>
								<ul id="order-overview__lore1-lines-list" class="list--floated"></ul>
								<table id="order-overview__lore1-lines-cost-table" class="table--no-stripes table--no-borders">
									<tbody>
										<tr><th>Cost of lines<br /><small>Unit cost: DKK 100.00</small></th><td data-type="num"><span id="order-overview__lore1-lines-cost"></span></td></tr>
										<tr><th>Handling fee</th><td data-type="num">DKK 100.00</td></tr>
										<tr class="total"><th>Subtotal</th><td data-type="num"><span id="order-overview__lore1-lines-subtotal"></span></td></tr>
										<tr class="total"><th>Total</th><td data-type="num"><span id="order-overview__lore1-lines-total"></span></td></tr>
									</tbody>
								</table>
								<p class="user-message approved">Order is placed under the payment waiver scheme, and lines are shipped free-of-charge.</p>
							</div>
						</div>
						<div id="order-overview__map"><div class="tooltip position--top">Your approximate location by address provided.</div></div>
					</div>
				</div>
				<div class="full-width" id="disclaimer">
					<h3>Disclaimer &amp; terms of use</h3>
					<article>
						<p>By placing an order with our system, you consent that:</p>
						<ul>
							<li><strong>You are responsible for the mailing outcome.</strong> We send <em>LORE1</em> seeds out in regular, untracked mail. Shipment to certain regions/countries in the world will require a phytosanitary certificate. Should such certificate be required, the customer will bear the cost of such application. Please contact us if you need to apply for a phytosanitary certificate.</li>
							<li><strong>You will cite the following manuscripts should plant materials derived from said ordered <em>LORE1</em> mutant lines are used in any published materials</strong>:
							<ul>
								<li><strong>The <em>LORE1</em> resource</strong>: Małolepszy et al. (2016). The <em>LORE1</em> insertion mutant resource. <em>Plant J.</em> <a href="https://www.ncbi.nlm.nih.gov/pubmed/27322352">doi:10.1111/tpj.13243</a>.</li>
								<li><strong>Use of <em>Lotus</em> Base</strong>: Mun et al. (in review). <em>Lotus</em> Base: An integrated information portal for the model legume <em>Lotus japonicus</em>.</li>
								<li><strong><em>LORE1</em> mutagenesis methods</strong> (two papers to be cited together):
									<ul>
										<li>Urbanski et al. (2012). Genome-wide <em>LORE1</em> retrotransposon mutagenesis and high-throughput insertion detection in <em>Lotus japonicus</em>. <em>Plant J.</em>, 69(4). <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014280" title="Genome-wide LORE1 retrotransposon mutagenesis and high-throughput insertion detection in Lotus japonicus.">doi:10.1111/j.1365-313X.2011.04827.x</a>); and</li>
										<li>Fukai et al. (2012) Establishment of a <em>Lotus japonicus</em> gene tagging population using the exon-targeting endogenous retrotransposon <em>LORE1</em></strong> (2012). <em>Plant J.</em>, 69(4). <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014259" title="Establishment of a Lotus japonicus gene tagging population using the exon-targeting endogenous retrotransposon LORE1.">doi:10.1111/j.1365-313X.2011.04826.x</a>.</li>
									</ul>
								</li>
							</ul>
						</ul>
					</article>
				</div>
				<div class="full-width" role="group">
					<label for="consent-disclaimer_checkbox"><input class="prettify" type="checkbox" id="consent-disclaimer_checkbox" name="consent_disclaimer" tabindex="17" required />I have read and understood the disclaimer above.</label>
				</div>
			</div>

			<!--<div class="form-step form-step--disabled" id="form-step__facet-5" data-form-step="5" data-form-step-title="Payment" data-form-step-title-short="Payment">
				<div class="full-width user-message note">
					<p>Please proceed to the Aarhus University payment form to pay for your order. Once your payment is successful, you will be provided with a unique order ID. That order ID will have to be copied and pasted into the field below.</p>
					<button id="au-payment">Proceed to payment</button>
				</div>
				<div role="group">
					<label for="payment_id" class="col_one">Your payment ID<span class="asterisk" title="Required Field">*</span></label>
					<input type="text" id="payment-id" name="payment_id" tabindex="19" required />
				</div>
			</div>-->

			<div class="form-step form-step--disabled" id="form-step__facet-5" data-form-step="5" data-form-step-title="Submit Order" data-form-step-title-short="Submit">
				<div role="group" class="cols">
					<?php if(is_logged_in()) { ?>
						<input type="hidden" name="user_auth_token" value="<?php echo $_COOKIE['auth_token']; ?>" />
						<h4 class="full-width align-center">Thanks for completing the order, <?php echo $user['FirstName']; ?></h4>
						<p class="full-width align-center">You're all set to go!</p>
					<?php } else { ?>
						<h4 class="full-width">Just one more thing:</h4>
						<p class="full-width">To make sure you are human, please complete the following captcha:</p>
						<label class="col-one">Human?</label>
						<div class="col-two" id="google-recaptcha"></div>
					<?php } ?>
				</div>
			</div>

			<nav id="form-step__nav-bottom" class="cols justify-content__space-between">
				<a id="form-step__prev" data-direction="prev" class="button button__nav" href="#"><span class="icon-left-open icon--no-spacing"></span> Back</a>
				<a id="form-step__next" data-direction="next" class="button button__nav disabled" href="#">Next <span class="icon-right-open icon--no-spacing"></span></a>
				<a id="form-step__submit" class="button button__submit <?php echo !is_logged_in() ? 'disabled' : ''; ?>" href="#">Place order</span></a>
			</nav>
			
		</form>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/order.min.js"></script>
	<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&amp;render=explicit" async defer></script>
	<script>
		// Google ReCaptcha
		var onloadCallback = function() {
				grecaptcha.render('google-recaptcha', {
					'sitekey' : '6Ld3VQ8TAAAAAO7VZkD4zFAvqUxUuox5VN7ztwM5',
					'callback': verifyCallback,
					'expired-callback': expiredCallback,
					'tabindex': 9
				});
			},
			verifyCallback = function(response) {
				console.log(globalVar.stepForm.validStepIndex, $('form.has-steps .form-step').length);

				// Only enable submit button when form is complete
				if(globalVar.stepForm.validStepIndex >= $('form.has-steps .form-step').length - 2) {
					$('#form-step__submit').removeClass('disabled');
				}
			},
			expiredCallback = function() {
				grecaptcha.reset();
			};

	</script>
</body>
</html>
