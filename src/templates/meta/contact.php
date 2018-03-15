<?php
	// Get important files
	require_once('../config.php');
	require_once(DOC_ROOT.'/lib/team.php');

	// Use country list
	use SameerShelavale\PhpCountriesArray\CountriesArray;

	// Use JWT
	use \Firebase\JWT\JWT;

	// PDO
	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);		
	} catch(PDOException $e) {
		
	}

	// Declare global user object
	if(isset($_COOKIE['auth_token']) && auth_verify($_COOKIE['auth_token'])) {
		$user = auth_verify($_COOKIE['auth_token']);
	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>Contact Us &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Contact us and say hello!'
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" integrity="sha384-wCtV4+Y0Qc1RNg341xqADYvciqiG4lgd7Jf6Udp0EQ0PoEv83t+MLRtJyaO5vAEh" crossorigin="anonymous">
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/order.min.css" type="text/css" media="screen">
</head>
<body class="contact">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title(array('Info', 'Contact'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>Contact Us</h2>
		<p>If you are unable to find the answers to your questions in the FAQ page, or have other queries pertaining this project, feel free to contact us through the form below. All fields are required unless otherwise stated.</p>
		<?php

			$row = array();

			// Check if order ID is in the GET variable
			if(isset($_GET) && !empty($_GET['id'])) {

				$key = escapeHTML($_GET['id']);

				try {
					// Database connection
					$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

					// Query for order
					$q1 = $db->prepare('SELECT
						t1.FirstName AS FirstName,
						t1.LastName AS LastName,
						t1.Email AS Email,
						t1.Institution AS Institution,
						t1.Address AS Address,
						t1.City AS City,
						t1.State AS State,
						t1.PostalCode AS PostalCode,
						t1.Country AS Country,
						t1.Comments AS Comments,
						t1.Timestamp AS OrderTime,
						t1.Verified AS VerificationStatus,
						t1.Shipped AS ShippingStatus,
						t1.ShippedTimestamp AS ShippingTime,
						t1.Payment AS Payment,
						t1.PaymentWaiver AS PaymentWaiver,
						t1.Salt AS Salt,
						GROUP_CONCAT(t2.PlantID ORDER BY t2.OrderLineID ASC) AS PlantID,
						GROUP_CONCAT(t2.SeedQuantity ORDER BY t2.OrderLineID ASC) AS SeedQuantity,
						GROUP_CONCAT(t2.ProcessDate ORDER BY t2.OrderLineID ASC) AS ProcessDate,
						GROUP_CONCAT(t2.AdminSalt ORDER BY t2.OrderLineID ASC) AS AdminSalt,
						GROUP_CONCAT(t2.AdminComments ORDER BY t2.OrderLineID ASC) AS AdminComments,
						COUNT(t2.ProcessDate) AS ProcessedLines,
						COUNT(t2.PlantID) AS TotalLines
					FROM orders_unique AS t1
					LEFT JOIN orders_lines AS t2 ON
						t1.Salt = t2.Salt
					WHERE t1.Salt = ?
					GROUP BY t1.Salt
					ORDER BY t1.Timestamp DESC
					LIMIT 1');
					$q1->execute(array($_GET['id']));

					// Query for other unprocessed orders
					$q2 = $db->prepare('SELECT
						t1.Salt AS Salt,
						COUNT(t2.ProcessDate) AS ProcessedLines,
						COUNT(t2.PlantID) AS TotalLines
					FROM orders_unique AS t1
					LEFT JOIN orders_lines AS t2 ON
						t1.Salt = t2.Salt
					WHERE
						t1.Verified = 1
					GROUP BY t1.Salt
					HAVING
						COUNT(t2.ProcessDate) < COUNT(t2.PlantID)
						');
					$q2->execute();

					// Retrieve order data
					$row = $q1->fetch(PDO::FETCH_ASSOC);

				} catch(PDOException $e) {

				}
			}

		?>
		<form id="contact-form" method="GET" action="api.php" class="has-group">
			<div class="cols has-legend" role="group">
				<p class="user-message full-width minimal legend">How can we help?</p>

				<label for="topic" class="col-one">Topic</label>
				<div class="col-two">
					<select id="topic" name="topic" tabindex="1">

						<option value="Miscellaneous" data-target="misc" data-message="1" selected>General enquiry</option>
						
						<optgroup label="LORE1 orders">
							<option value="LORE1 order" data-target="lore1-order" data-message="1" <?php echo ($row ? 'selected' : ''); ?>>Issue with a specific order</option>
							<option value="Transfer LORE1 orders" data-target="transfer-lore1-orders" data-message="1" <?php echo (isset($_GET['topic']) && !empty($_GET['topic']) && $_GET['topic'] === 'transfer-lore1-orders' ? 'selected' : ''); ?>>Transfer LORE1 orders from another email</option>
						</optgroup>

						<optgroup label="Others">
							<option value="Bacteria strain request" data-target="strains" data-message="0">Bacteria strain request</option>
							<option value="Bug report" data-target="bug-report" data-message="0">Bug report</option>
							<option value="Datasets" data-target="datasets" data-message="1">Dataset availability</option>
							<option value="User account" data-target="users" data-message="1">User account</option>
						</optgroup>

					</select>
				</div>
			</div>

			<div id="contact-form__fields">

				<div class="contact-form__field hidden" data-message="0">
					<div data-field="bug-report">
						<h3>Technical issues</h3>
						<p>Visitors that are using outdated web browsers might encounter usability and/or technical issues (the latter being less common, but possible). Try using our site again with the latest version of browser, and the issue(s) might have been resolved. <em>Lotus</em> Base is developed and tested in the latest webkit browsers (Safari and Chrome), but also extensively tested in Firefox.</p>
						<p>If all fail, please <a href="/issues">open an issue</a> at the <em>Lotus</em> Base repository. We use a centralized bug/issue-tracking system so our team can work on resolving issues in a timely and efficient manner. You may open an issue anonymously, but if you require an update on the status of the issue and get into contact with us, we strongly recommend <a href="https://bitbucket.org/account/signup/">creating a Bitbucket account</a>.</p>
					</div>
					<?php $member = $team['Lene H. Madsen']; ?>
					<div data-field="strains">
						<h3>Bacteria strain request</h3>
						<p>For requesting bacteria strains, please liaise with our lab manager, Lene H. Madsen (<strong><a href="mailto:lhm@mbg.au.dk">lhm@mbg.au.dk</a></strong>), for further information.</p>
					</div>
				</div>
				
				<div class="contact-form__field  <?php echo (!$row ? 'hidden' : ''); ?>" data-message="1">
					<div class="cols has-legend" role="group">
						<p class="user-message full-width minimal legend">Message</p>

						<?php
							if(
								!isset($_GET['not_user']) &&
								isset($user['FirstName']) &&
								!empty($user['FirstName']) &&
								isset($user['LastName']) &&
								!empty($user['LastName']) &&
								isset($user['Email']) &&
								!empty($user['Email'])
								) {
						?>

							<div class="full-width">
								<div class="user-message align-center">
									<h3>Hi <?php echo $user['FirstName']; ?>!</h3>
									<p>We have retrieved your contact details so you don't have to re-enter them.<br />We will correspond with you via your registered email: <a href="mailto:<?php echo $user['Email']; ?>"><?php echo $user['Email']; ?></a>.</p>
								</div>
							</div>

						<?php } else { ?>

							<label for="fname" class="col-one col-half-width">First name</label>
							<div class="col-two col-half-width">
								<input id="fname" name="fname" type="text" placeholder="First name" value="<?php echo isset($row['FirstName']) ? $row['FirstName'] : (isset($user['FirstName']) ? $user['FirstName'] : ''); ?>" tabindex="1" required />
							</div>

							<label for="lname" class="col-one col-half-width align-right">Last name</label>
							<div class="col-two col-half-width">
								<input id="lname" name="lname" type="text" placeholder="Last name" value="<?php echo isset($row['LastName']) ? $row['LastName'] : (isset($user['LastName']) ? $user['LastName'] : ''); ?>" tabindex="2" required />
							</div>

							<label for="email" class="col-one col-half-width">Email</label>
							<div class="col-two col-half-width">
								<input id="email" name="email" type="email" value="<?php echo isset($row['Email']) ? $row['Email'] : (isset($user['Email']) ? $user['Email'] : ''); ?>" placeholder="Email address" tabindex="3" required />
							</div>

							<label for="emailver" class="col-one col-half-width align-right">Re-enter email <a class="info" data-modal="search-help" title="Why do I have to re-enter my email?" data-modal-content="This is to ensure that you have entered the right email address. Our correspondence with you will be done &lt;em&gt;solely&lt;/em&gt; through the provided email address.">?</a></label>
							<div class="col-two col-half-width">
								<input id="emailver" name="emailver" type="email" value="<?php echo isset($row['Email']) ? $row['Email'] : (isset($user['Email']) ? $user['Email'] : ''); ?>" placeholder="Verify email address" tabindex="4" required />
							</div>

							<label for="organization" class="col-one">Organization (optional)</label>
							<input id="organization" name="organization" class="col-two" type="text" placeholder="The organization/institution you are affiliated with" value="<?php echo isset($row['Institution']) ? $row['Institution'] : (isset($user['Organization']) ? $user['Organization'] : ''); ?>" tabindex="5" />

						<?php } ?>

						<label data-field="lore1-order" for="salt" class="col-one">Order identifier</label>
						<div class="col-two" data-field="lore1-order">
							<input id="salt" name="salt" type="text" value="<?php echo isset($row['Salt']) ? $row['Salt'] : ''; ?>" placeholder="32-character order identification" tabindex="6" />
						</div>

						<label data-field="users" for="userkey" class="col-one">User key (optional)</label>
						<div class="col-two" data-field="users">
							<input id="userkey" name="userkey" type="text" value="<?php echo isset($_GET['key']) ? escapeHTML($_GET['key']) : ''; ?>" placeholder="32-character user verification key" tabindex="6" />
						</div>

						<label for="subject" class="col-one">Subject (optional)</label>
						<input id="subject" name="subject" class="col-two" type="text" placeholder="Message subject" tabindex="7">

						<label for="message" class="col-one">Message</label>
						<div class="col-two">
							<textarea id="message" name="message" placeholder="Message" rows="8" tabindex="8" required ></textarea>
						</div>

						<input type="hidden" name="t" value="9" />
						<?php if(!is_logged_in()) { ?>
							<label class="col-one">Human?</label>
							<div class="col-two" id="google-recaptcha"></div>
						<?php } ?>
					</div>
				</div>
			</div>

			<?php if(is_logged_in()) { ?>
				<input type="hidden" name="user_auth_token" value="<?php echo $_COOKIE['auth_token']; ?>" />
			<?php } ?>

			<button type="submit" data-message class="<?php echo (!$row ? 'hidden' : '').' '.(!is_logged_in() ? 'hidden disabled' : ''); ?>" tabindex="9" <?php echo !is_logged_in() ? 'disabled' : ''; ?>>Send mail</button>

		</form>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js" integrity="sha384-iViGfLSGR6GiB7RsfWQjsxI2sFHdsBriAK+Ywvt4q8VV14jekjOoElXweWVrLg/m" crossorigin="anonymous"></script>
	<script>

		<?php if(is_logged_in()) { ?>

		// Manual recaptcha override
		globalVar.recaptcha = true;

		<?php } else { ?>

		// Google ReCaptcha
		var onloadCallback = function() {
				grecaptcha.render('google-recaptcha', {
					'sitekey' : '6Ld3VQ8TAAAAAO7VZkD4zFAvqUxUuox5VN7ztwM5',
					'callback': verifyCallback,
					'expired-callback': expiredCallback,
					'tabindex': 8
				});
			},
			verifyCallback = function(response) {
				$('#contact-form :input[type="submit"]')
					.removeClass('disabled')
					.prop('disabled', false);
			},
			expiredCallback = function() {
				grecaptcha.reset();
			};

		<?php } ?>

	</script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/contact.min.js"></script>
	<?php if(!is_logged_in()) { ?>
		<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&amp;render=explicit" async defer></script>
	<?php } ?>
</body>
</html>
