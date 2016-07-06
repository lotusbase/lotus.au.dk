<?php

	// Load site config
	require_once('../config.php');

	// Use country list
	use SameerShelavale\PhpCountriesArray\CountriesArray;

	// Database
	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		// Get average processing time
		$q1 = $db->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, `Timestamp`, `ShippedTimestamp`)) AS AverageProcessingTime
		FROM `orders_unique`
		WHERE
			`Timestamp` IS NOT NULL AND
			`ShippedTimestamp` IS NOT NULL
		");

		// Execute query
		$q1->execute();

		// Get statistics
		if($q1->rowCount() > 0) {
			$row = $q1->fetch();
			$processingTime = $row['AverageProcessingTime'];
		}

		// Get user information if is logged in
		$user = is_logged_in();
		$userData = array();
		if($user) {
			$q2 = $db->prepare("SELECT * FROM auth WHERE Salt = ?");
			$q2->execute(array($user['Salt']));
			$userData = $q2->fetch(PDO::FETCH_ASSOC);
		}

	} catch(PDOException $e) {
		$e = $db->errorInfo();
		$_SESSION['ORD_ERROR'] = $e->getMessage();
		session_write_close();
		header('Location: '.$_SERVER['PHP_SELF']);
		exit();
	}

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
		<h2><em>LORE1</em> lines order form</h2>
		<p>Fill up your personal details below in order to place an order for <em>LORE1</em> line(s). We promise your private information will be kept <em>strictly</em> confidential. Required fields are marked with an asterisk (<strong>*</strong>).</p>
		<?php 
			if(isset($_SESSION['ORD_ERROR']) && is_array($_SESSION['ORD_ERROR']) && count($_SESSION['ORD_ERROR'])>0) {
				echo '<div class="reminder user-message"><h3>'.$_SESSION['ORD_ERROR_TITLE'].'</h3><ul class="err">';
				foreach($_SESSION['ORD_ERROR'] as $msg) {
					echo '<li>',$msg,'</li>'; 
				}
				echo '</ul></div>';
				unset($_SESSION['ORD_ERROR']);
				unset($_SESSION['ORD_ERROR_TITLE']);
			}
			if(isset($_SESSION['USER_INPUT']) && is_array($_SESSION['USER_INPUT']) && count($_SESSION['USER_INPUT'])>0) {
				// Fetches the user's previous input from a failed submission, so they don't have to reenter everything (dugald@mb.au.dk)
				$user_input = $_SESSION['USER_INPUT'];
				unset($_SESSION['USER_INPUT']);
			} else {
				// Attempt to retrieve user information from database
				if($userData) {
					$user_input = array(
						$userData['FirstName'],
						$userData['LastName'],
						$userData['Email'],
						$userData['Organization'],
						$userData['Address'],
						$userData['City'],
						$userData['State'],
						$userData['PostalCode'],
						$userData['Country'],
						'',
						''
						);
				} else {
					// Declare empty array if nothing has been entered by the user
					$user_input = array('','','','','','','','','','','');
				}
			}
		?>
		<form action="./order-exec" method="POST" id="order-form" accept-charset="utf-8" class="has-group">
			<div class="has-legend cols" role="group">
				<span class="legend">Personal and contact details</span>
				<div class="cols full-width">
					<label for="fname" class="col-one col-half-width">First Name <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<input id="fname" name="fname" type="text" value="<?php echo $user_input[0]; ?>" placeholder="First Name" tabindex="1" />
					</div>

					<label for="lname" class="col-one col-half-width align-right">Last Name <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<input id="lname" name="lname" type="text" value="<?php echo $user_input[1]; ?>" placeholder="Last Name" tabindex="2" />
					</div>
				</div>

				<label for="email" class="col-one">Email <span class="asterisk" title="Required Field">*</span></label>
				<div class="col-two">
					<input id="email" name="email" type="email" value="<?php echo $user_input[2]; ?>" placeholder="Email address" tabindex="3" />
				</div>
			</div>

			<div class="has-legend cols" role="group">
				<div class="legend">Postal information</div>
				<?php
					if(
						is_logged_in() &&
						(
							empty($userData['Organization']) ||
							empty($userData['Address']) ||
							empty($userData['City']) ||
							empty($userData['PostalCode']) ||
							empty($userData['Country'])
							)
						) {
						echo '<p class="user-message reminder full-width">We have detected incomplete address information on your <em>Lotus</em> Base user account. If you want your address to be saved for future uses, please <a href="'.WEB_ROOT.'/users/account#profile">update your profile</a>.</p>';
					}
				?>
				<label for="institution" class="col-one">Institution <span class="asterisk" title="Required Field">*</span></label>
				<div class="col-two">
					<input id="institution" name="institution" type="text" value="<?php echo $user_input[3]; ?>" placeholder="Institution / Organization" tabindex="5" />
				</div>

				<label for="address" class="col-one">Street Address <span class="asterisk" title="Required Field">*</span></label>
				<div class="col-two">
					<input id="address" name="address" placeholder="Street Address" tabindex="6" value="<?php echo $user_input[4]; ?>" />
				</div>

				<div class="cols full-width">
					
					<label for="city" class="col-one col-half-width">City <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<input id="city" name="city" type="text" value="<?php echo $user_input[5]; ?>" placeholder="City" tabindex="7" />
					</div>

					<label for="state" class="col-one col-half-width align-right">State / Region</label>
					<div class="col-two col-half-width">
						<input id="state" name="state" type="text" value="<?php echo $user_input[6]; ?>" placeholder="State / Region" tabindex="8" />
					</div>

					<label for="postalcode" class="col-one col-half-width">Postal Code <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<input id="postalcode" name="postalcode" type="text" value="<?php echo $user_input[7]; ?>" placeholder="Postal Code"  tabindex="9" />
					</div>

					<label for="country" class="col-one col-half-width align-right">Country <span class="asterisk" title="Required Field">*</span></label>
					<div class="col-two col-half-width">
						<select name="country" id="country" tabindex="10">
						<?php
							$countries = CountriesArray::get2d('name', array('alpha2', 'alpha3'));
							foreach($countries as $name=>$meta) {
								echo '<option value="'.$meta['alpha3'].'" data-country-name="'.$name.'" data-country-alpha2="'.$meta['alpha2'].'" '.($user_input[8] === $meta['alpha3'] ? 'selected' : '').'>'.$name.'</option>';
							}
						?>
						</select>
					</div>
				</div>
			</div>

			<div class="has-legend cols" role="group">
				<div class="legend">Order information</div>
				<?php
					if(isset($_SESSION['LINES_ERROR']) && is_array($_SESSION['LINES_ERROR']) && count($_SESSION['LINES_ERROR'])>0) {
						echo '<div class="reminder user-message"><h3>'.$_SESSION['LINES_ERROR_TITLE'].'</h3><ul class="err">';
						foreach($_SESSION['LINES_ERROR'] as $msg) {
							echo '<li>',$msg,'</li>'; 
						}
						echo '</ul></div>';
						unset($_SESSION['LINES_ERROR']);
						unset($_SESSION['LINES_ERROR_TITLE']);
					}						
				?>
				<label for="lines-input" class="col-one required"><em>LORE1</em> lines <a class="help" data-modal title="How should I enter Plant ID?" data-modal-content="A valid plant ID is an eight-digit number that starts with '3', e.g. &lt;code&gt;30000001&lt;/code&gt;. Alternative formats such as &lt;code&gt;DK01-030000001&lt;/code&gt; are not accepted. Please enter &lt;em&gt;LORE1&lt;/em&gt; lines separated by: a space &lt;kbd title='spacebar'&gt;_____&lt;/kbd&gt;, a comma &lt;kbd&gt;,&lt;/kbd&gt;, or a tab &lt;kbd&gt;&nbsp;&map;&nbsp;Tab&lt;/kbd&gt;.">?</a> <span class="asterisk" title="Required Field">*</span></label>
				<div class="col-two">
					<div class="multiple-text-input input-mimic">
						<ul class="input-values">
						<?php
							if($user_input[9]) {
								$lore1_array = explode(',', $user_input[9]);
								foreach($lore1_array as $lore1_item) {
									echo '<li data-input-value="'.html($lore1_item).'">'.html($lore1_item).'<span class="icon-cancel" data-action="delete"></span></li>';
								}
							}
						?>
							<li class="input-wrapper"><input type="text" name="lines-input" id="lines-input" placeholder="LORE1 Line ID" autocomplete="off" tabindex="10" /></li>
						</ul>
						<input class="input-hidden" type="hidden" name="lines" id="lines" value="<?php echo $user_input[9]; ?>" readonly />
					</div>
					<small><strong>Separate each <em>LORE1</em> with a comma, space or tab.</strong></small>
					<div id="id-check"></div>
				</div>

				<label for="comments" class="col-one">Order Comments</label>
				<div class="col-two">
					<textarea id="comments" name="comments" rows="3" placeholder="Comments associated with your order (viewable by administration)" tabindex="11"><?php echo $user_input[10]; ?></textarea>
				</div>

			</div>

			<div class="has-legend cols" role="group">
				<div class="legend">Consent &amp; verification</div>
				<div class="full-width">
					<p>By placing an order with our system, you consent that:</p>
					<ul>
						<li><strong>You are responsible for the mailing outcome.</strong> We send <em>LORE1</em> seeds out in regular, untracked mail. Shipment to certain regions/countries in the world will require a phytosanitary certificate: your <em>LORE1</em> seeds may require the aforementioned documents before being allowed through the recepient's customs&mdash;not applying for such documentation in advance may result in loss of your ordered seeds. Should such certificate be required, the customer will bear the cost of such application. Please contact us if you need to apply for a phytosanitary certificate.</li>
						<li><strong>You will cite the following manuscripts should plant materials derived from said ordered <em>LORE1</em> mutant lines are used in any published materials.</strong> Two <em>LORE1</em> papers should be cited back-to-back: <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014280" title="Genome-wide LORE1 retrotransposon mutagenesis and high-throughput insertion detection in Lotus japonicus.">Urbanski <em>et al.</em>, 2012</a> and <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014259" title="Establishment of a Lotus japonicus gene tagging population using the exon-targeting endogenous retrotransposon LORE1.">Fukai <em>et al.</em>, 2012</a>.</li>
					</ul>
					<label for="consent-disclaimer"><input type="checkbox" id="consent-disclaimer" name="consent_disclaimer" tabindex="12" required /><span>I have read and understood the disclaimer above.</span></label>
				</div>
				
				<?php if(!is_logged_in()) { ?>
					<label class="col-one">Human?</label>
					<div class="col-two" id="google-recaptcha"></div>
				<?php } else { ?>
					<input type="hidden" name="user_auth_token" value="<?php echo $_COOKIE['auth_token']; ?>" />
				<?php } ?>
			</div>

			<button type="submit">Place order</button>

		</form>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<?php if(!is_logged_in()) { ?>
	<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&amp;render=explicit" async defer></script>
	<script>
		var onloadCallback = function() {
				grecaptcha.render('google-recaptcha', {
					'sitekey' : '6Ld3VQ8TAAAAAO7VZkD4zFAvqUxUuox5VN7ztwM5',
					'callback': verifyCallback,
					'expired-callback': expiredCallback,
					'tabindex': 13
				});
			},
			verifyCallback = function(response) {
				$('#order-form :input[type="submit"]')
					.removeClass('disabled')
					.prop('disabled', false);
			},
			expiredCallback = function() {
				grecaptcha.reset();
			};
	</script>
	<?php } ?>
	<script>
		$(function() {
			var _validator = $('#order-form').validate({
				rules: {
					fname: 'required',
					lname: 'required',
					email: {
						required: true,
						email: true
					},
					lines: 'required',
					institution: 'required',
					address: 'required',
					city: 'required',
					postalcode: 'required',
					country: 'required',
					consent_disclaimer: 'required',
				},
				ignore: '.validate--ignore',
				errorElement: 'label',
				errorPlacement: function(error, element) {
					if(element.attr('type') === 'checkbox') {
						error.appendTo(element.closest('label'));
					} else {
						if(element.hasClass('input-hidden')) {
							element.closest('.input-mimic').addClass('error');
						}
						error.insertAfter(element);
					}
				}
			});

			// Order validation of entered Plant ID
			$('#id-check').hide();
			$('#lines').on('change manualchange', function() {
				var $t = $(this);

				// Remove error class if any
				$t.closest('.input-mimic').removeClass('error');

				// Execute AJAX call
				if($t.val()) {
					var linesCheck = $.ajax({
							url: root + '/api/v1/lore1/'+encodeURIComponent($t.val())+'/verify',
							type: 'GET',
							dataType: 'json'
						}),
						$msg = $('#id-check');

					linesCheck
					.done(function(d) {
						var data = d.data;

						if(d.status === 207) {
							// Partial success
							$msg
							.addClass('warning')
							.removeClass('approved')
							.html([
								'<p>We have found some errors in your input. Please see see highlighted.</p>',
								'<ul>',
								(data.pid_notFound && data.pid_notFound.length ? '<li>'+data.pid_notFound.length+' '+globalFun.pl(data.pid_notFound.length, 'line does', 'lines do')+' not exist, or '+globalFun.pl(data.pid_notFound.length, 'has', 'have')+' depleted seed stock, in our system</li>' : ''),
								(data.pid_invalid && data.pid_invalid.length ? '<li>'+data.pid_invalid.length+' '+globalFun.pl(data.pid_invalid.length, 'line is', 'lines are')+' incorrectly formatted. Only use the 8-digit identifier starting with 3, i.e. <code>30000001</code>.</li>' : ''),
								'</ul>'].join(''));

							// Highlight problematic entries
							if(data.pid_notFound) {
								$.each(data.pid_notFound, function(i, pid) {
									$('#lines').prev('ul.input-values').find('li').filter(function() {
										return $(this).data('input-value') == pid;
									}).addClass('user-message warning')
									.closest('.input-mimic')
									.addClass('error');
								});
							}
							if(data.pid_invalid) {
								$.each(data.pid_invalid, function(i, pid) {
									$('#lines').prev('ul.input-values').find('li').filter(function() {
										return $(this).data('input-value') == pid;
									}).addClass('user-message warning')
									.closest('.input-mimic')
									.addClass('error');
								});
							}

						} else {
							// Everything is okay
							$msg
							.addClass('approved')
							.removeClass('warning')
							.html('<p><span class="pictogram icon-check"></span>Your <em>LORE1</em> '+globalFun.pl(data.pid_found.length, 'line is', 'lines are')+' available and valid.</p>');
						}
						$msg.slideDown(125);
					})
					.fail(function(jqXHR, textStatus, errorThrown) {
						// Incorrect Plant ID is found
						var d = jqXHR.responseJSON;

						if(d.status === 404) {
							$msg
							.addClass('warning')
							.removeClass('approved')
							.html('<p><span class="pictogram icon-cancel"></span>None of your lines are valid.</p>')
							.slideDown(125);

							$('#lines')
							.prev('ul.input-values').find('li[data-input-value]').addClass('user-message warning')
							.closest('.input-mimic')
							.addClass('error');
						} else {
							$msg
							.addClass('warning')
							.removeClass('approved')
							.html('<p><span class="pictogram icon-cancel"></span>We have a problem contacting the database. Please contact system administrator should this problem persists.</p>')
							.slideDown(125);
						}
					});
				} else {
					$('#id-check').slideUp(125);
				}
			});
		});
	</script>
</body>
</html>
