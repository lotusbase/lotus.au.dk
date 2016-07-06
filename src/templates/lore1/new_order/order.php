<?php

	// Database
	require_once('../config.php');

	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);		
	} catch(PDOException $e) {
		echo json_encode(
			array(
				'error' => true,
				'errorCode' => 400,
				'message' => 'We have experienced a problem trying to establish a database connection. Please contact the system administrator as soon as possible'
			)
		);
		exit();
	}

	// Get average processing time
	try {
		$q = $db->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, `Timestamp`, `ShippedTimestamp`)) AS AverageProcessingTime
		FROM `orders_unique`
		WHERE
			`Timestamp` IS NOT NULL AND
			`ShippedTimestamp` IS NOT NULL
		");

		// Execute query
		$q->execute();
	} catch(PDOException $err) {
		$e = $db->errorInfo();
		echo json_encode(
			array(
				'error' => true,
				'errorCode' => 100,
				'message' => 'MySQL Error '.$e[1].': '.$e[2].'<br />'.$err->getMessage()
			)
		);
	}

	// Get statistics
	if($q->rowCount() > 0) {
		$row = $q->fetch();
		$processingTime = $row['AverageProcessingTime'];
	}

	// Use country list
	use SameerShelavale\PhpCountriesArray\CountriesArray;

?>
<!doctype html>
<html lang="en">
<head>
	<title>Order Form &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/order.min.css" type="text/css" media="screen">
</head>
<body class="order">
	<?php
		$header = new \LotusBase\PageHeader();
		echo $header->get_header();
	?>

	<?php echo get_breadcrumbs(array('page_title' => 'Order Lines')); ?>

	<section class="wrapper">
		<h2>LORE1 lines order form</h2>
		<p>Please complete the following form to place an order on your LORE1 lines for interest. You may only proceed to the next step when the current step is complete and valid, but you may navigate between previously cleared step.</p>
		<p>Your private information will be kept <em>strictly</em> confidential. Required fields are marked with an asterisk (<strong>*</strong>).</p>
		<form action="#" method="POST" id="order-form" accept-charset="utf-8" class="has-group has-steps">

			<header id="form-step__header">
				<nav id="form-step__nav"><ul class="cols flex-wrap__nowrap"></ul></nav>

				<h3 class="align-center" id="form-step__title"></h3>
			
			</header>

			<div class="form-step form-step--disabled" id="form-step__facet-1" data-form-step="1" data-form-step-title="Customer contact details" data-form-step-title-short="Customer Details">
				<div class="cols" role="group">
					<div class="cols full-width">
						<label for="fname" class="col-one col-half-width">First Name <span class="asterisk" title="Required Field">*</span></label>
						<div class="col-two col-half-width">
							<input id="fname" name="fname" type="text" value="" placeholder="First Name" tabindex="1" required />
						</div>

						<label for="lname" class="col-one col-half-width align-right">Last Name <span class="asterisk" title="Required Field">*</span></label>
						<div class="col-two col-half-width">
							<input id="lname" name="lname" type="text" value="" placeholder="Last Name" tabindex="2" required />
						</div>
					</div>

					<label for="email" class="col-one">Email <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two">
						<input id="email" name="email" type="email" value="" placeholder="Email address" tabindex="3" required />
					</div>
				</div>
			</div>

			<div class="form-step form-step--disabled" id="form-step__facet-2" data-form-step="2" data-form-step-title="Order information" data-form-step-title-short="Order Info.">
				<div class="cols" role="group">
					<label for="lines-input" class="col-one required">LORE1 lines <a data-modal="search-help" title="How should I enter Plant ID?" data-modal-content="Please enter LORE1 lines separated by: a space &lt;kbd title='spacebar'&gt;_____&lt;/kbd&gt;, a comma &lt;kbd&gt;,&lt;/kbd&gt;, or a tab &lt;kbd&gt;&nbsp;&map;&nbsp;Tab&lt;/kbd&gt;.">?</a> <span class="asterisk" title="Required Field">*</span></label>
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
							<input class="input-hidden" type="hidden" name="lines" id="lines" value="<?php echo escapeHTML($_GET['lore1']); ?>" readonly required />
						</div>
						<small><strong>Separate each LORE1 with a comma, space or tab.</strong></small>
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

					<p class="full-width">The address where your LORE1 seeds will be shipped to.</p>

					<label for="shipping-institution" class="col-one">Institution <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two">
						<input id="shipping-institution" name="shipping_institution" type="text" value="" placeholder="Institution / Organization" tabindex="12" required />
					</div>

					<label for="shipping-address" class="col-one">Street Address <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two">
						<input id="shipping-address" name="shipping_address" placeholder="Street Address" tabindex="13" value="" required />
					</div>

					<label for="shipping-city" class="col-one col-half-width">City <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<input id="shipping-city" name="shipping_city" type="text" value="" placeholder="City" tabindex="14" required />
					</div>

					<label for="shipping-state" class="col-one col-half-width align-right">State / Region</label>
					<div class="col-two col-half-width">
						<input id="shipping-state" name="shipping_state" type="text" value="" placeholder="State / Region" tabindex="15">
					</div>

					<label for="shipping-postalcode" class="col-one col-half-width">Postal Code <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<input id="shipping-postalcode" name="shipping_postalcode" type="text" value="" placeholder="Postal Code"  tabindex="16" required />
					</div>

					<label for="shipping-country" class="col-one col-half-width align-right">Country <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<select name="shipping_country" id="shipping-country" tabindex="16" required>
							<option value="">Select a country</option>
						<?php
							$countries = CountriesArray::get2d('name', array('alpha2', 'alpha3'));
							foreach($countries as $name=>$meta) {
								echo '<option value="'.$meta['alpha3'].'" data-country-name="'.$name.'" data-country-alpha2="'.$meta['alpha2'].'">'.$name.'</option>';
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
								<h4>LORE1 lines</h4>
								<p>You have ordered <span id="order-overview__lore1-lines-count"></span>:</p>
								<ul id="order-overview__lore1-lines-list"></ul>
								<table id="order-overview__lore1-lines-cost-table" class="table--no-stripes table--no-borders">
									<tbody>
										<tr><th>Cost of lines<br /><small>Unit cost: DKK 100.00</small></th><td data-type="num"><span id="order-overview__lore1-lines-cost"></span></td></tr>
										<tr><th>Handling fee</th><td data-type="num">DKK 100.00</td></tr>
										<tr class="total"><th>Total</th><td data-type="num"><span id="order-overview__lore1-lines-total"></span></td></tr>
									</tbody>
								</table>
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
							<li><strong>You will cite the following manuscripts should plant materials derived from said ordered <em>LORE1</em> mutant lines are used in any published materials.</strong> Two <em>LORE1</em> papers should be cited back-to-back: <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014280" title="Genome-wide LORE1 retrotransposon mutagenesis and high-throughput insertion detection in Lotus japonicus.">Urbanski <em>et al.</em>, 2012</a> and <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014259" title="Establishment of a Lotus japonicus gene tagging population using the exon-targeting endogenous retrotransposon LORE1.">Fukai <em>et al.</em>, 2012</a>.</li>
						</ul>
					</article>
				</div>
				<div class="full-width" role="group">
					<label for="consent-disclaimer"><input type="checkbox" id="consent-disclaimer" name="consent_disclaimer" tabindex="17" required />I have read and understood the disclaimer above.</label>
				</div>
			</div>

			<div class="form-step form-step--disabled" id="form-step__facet-5" data-form-step="5" data-form-step-title="Payment" data-form-step-title-short="Payment">
				<div class="full-width user-message note">
					<p>Please proceed to the Aarhus University payment form to pay for your order. Once your payment is successful, you will be provided with a unique order ID. That order ID will have to be copied and pasted into the field below.</p>
					<button id="au-payment">Proceed to payment</button>
				</div>
				<div role="group">
					<label for="payment_id" class="col_one">Your payment ID<span class="asterisk" title="Required Field">*</span></label>
					<input type="text" id="payment-id" name="payment_id" tabindex="19" required />
				</div>
			</div>

			<div class="form-step form-step--disabled" id="form-step__facet-6" data-form-step="6" data-form-step-title="Submit Order" data-form-step-title-short="Submit">
				<div role="group">
					<label class="col-one">Human?</label>
					<input type="hidden" name="t" value="9" />
					<div class="col-two" id="google-recaptcha"></div>
				</div>
			</div>

			<nav id="form-step__nav-bottom" class="cols justify-content__space-between">
				<a id="form-step__prev" data-direction="prev" class="button" href="#"><span class="icon-left-open icon--no-spacing"></span> Back</a>
				<a id="form-step__next" data-direction="next" class="button disabled" href="#">Next <span class="icon-right-open icon--no-spacing"></span></a>
			</nav>
			
		</form>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/order.min.js"></script>
	<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&amp;render=explicit" async defer></script>
</body>
</html>
