<?php
	// Get important files
	require_once('../config.php');
	require_once('./auth.php');

	// Use country list
	use SameerShelavale\PhpCountriesArray\CountriesArray;

	// Mailchimp API
	use \DrewM\MailChimp\MailChimp;

	// OAuth identity providers
	// Redirect URI
	$oauth2_redirect_uri = DOMAIN_NAME.'/users/oauth';

	// Google+ login
	const GOOGLE_CLIENT_ID = '339332762863-r5hjkbsailkrdd0mii97230ifks373k8.apps.googleusercontent.com';
	const GOOGLE_CLIENT_SECRET = 'uEa9CBZMZ_Mqabb46EcyF1s4';
	const GOOGLE_APP_NAME = 'Lotus Base';

	// LinkedIn login
	const LINKEDIN_CLIENT_ID = '77zr2r19ed3dm0';
	const LINKEDIN_CLIENT_SECRET = 'LOwW4QrYoxiiSqQM';

	// GitHub login
	const GITHUB_CLIENT_ID = '651ee2e9ab91d2aa305f';
	const GITHUB_CLIENT_SECRET = '6f3aa3a1fdfc57046efad65ff92be32e5bc3a57c';

	// Create state
	$oauth2_state = base64_encode(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM));
	$_SESSION['oauth2_state'] = $oauth2_state;

	$client = new \Google_Client();
	$client->setApplicationName(GOOGLE_APP_NAME);
	$client->setClientId(GOOGLE_CLIENT_ID);
	$client->setClientSecret(GOOGLE_CLIENT_SECRET);
	$client->setRedirectUri($oauth2_redirect_uri.'/google');
	$client->addScope("email");
	$client->addScope("profile");
?>
<!doctype html>
<html lang="en">
<head>
	<title>User Account&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/users.min.css" type="text/css" media="screen" />
</head>
<body class="users account theme--white init-scroll--disabled">
	<?php
		// Generate header
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<div id="account-tabs">

			<div id="account-tabs__nav" class="cols align-items__flex-end ui-tabs-nav__wrapper">
				<h1>Your account</h1>
				<ul class="minimal">
					<li><a href="#profile" data-custom-smooth-scroll>Profile</a></li>
					<li><a href="#security" data-custom-smooth-scroll>Security</a></li>
					<li><a href="#access_token" data-custom-smooth-scroll>API Token</a></li>
					<li><a href="#integration" data-custom-smooth-scroll>Integration</a></li>
				</ul>
			</div>

			<div id="profile">
			<?php
				try {
					$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

					// Get list of organizations
					$orgs = array(
						'Select a pre-existing organization, or enter a new one' => '',
						'No affiliation' => 'none'
						);
					$o = $db->prepare('SELECT DISTINCT(Organization) AS Organization FROM auth');
					$o->execute();
					while($rows = $o->fetch(PDO::FETCH_ASSOC)) {
						$orgs[$rows['Organization']] = $rows['Organization'];
					}

					// Get user data
					$qu = $db->prepare('SELECT * FROM auth WHERE Salt = ?');
					$qu->execute(array($user['Salt']));
					if($qu->rowCount() === 1) {
						$userData = $qu->fetch(PDO::FETCH_ASSOC);
					} else {
						throw new Exception('We have a problem retrieving your user data from our database.');
					}

					?>
					
					<h3>User profile</h3>
					<p>Update your profile information with the form below. Once you've made the changes you want, remember to click the button to update your profile.</p>
					<form method="post" action="#" class="has-group" id="update-profile">
						<div class="has-legend cols" role="group">
							<span class="user-message full-width minimal legend">Personal information</span>
							<label for="firstname" class="col-one">Name <span class="asterisk" title="Required Field">*</span></label>
							<div class="col-two">
								<div class="cols flex-wrap__nowrap">
									<input type="text" name="firstname" id="firstname" placeholder="First name" style="margin-right: 1rem;" value="<?php echo $userData['FirstName']; ?>" required />
									<input type="text" name="lastname" id="lastname" placeholder="Last name" value="<?php echo $userData['LastName']; ?>" required />
								</div>
							</div>

							<label for="username" class="col-one">Username <span class="asterisk" title="Required Field">*</span></label>
							<div class="col-two"><input type="text" name="username" id="username" placeholder="Username (min. 2 characters)" value="<?php echo $userData['Username']; ?>" required /></div>

							<label class="col-one" for="email">Email <span class="asterisk" title="Required Field">*</span></label>
							<div class="col-two"><input type="text" name="email" id="email" placeholder="Email address" value="<?php echo $userData['Email']; ?>" required /></div>

							<label class="col-one" for="organization">Organization</label>
							<div class="col-two">
								<select name="organization" id="organization">
									<?php
										if(!isset($userData['Organization']) || empty($userData['Organization']) || !$userData['Organization']) {
											$userData['Organization'] = 'none';
										}
										foreach($orgs as $title => $value) {
											$orgs_opts[] = '<option value="'.$value.'" '.($userData['Organization'] === $value ? 'selected' : '').'>'.$title.'</option>';
										}
										echo implode('', $orgs_opts);
									?>
								</select>
							</div>
						</div>

						<div class="has-legend cols" role="group">
							<span class="user-message full-width minimal legend">Postal information</span>
							<p class="full-width">Provide your shipping address so you don't need to re-enter this information when placing orders.</p>

							<label for="address" class="col-one">Street Address</label>
							<div class="col-two">
								<input id="address" name="address" placeholder="Street Address" value="<?php echo $userData['Address']; ?>" /></input>
							</div>

							<div class="cols full-width">
								
								<label for="city" class="col-one col-half-width">City</label>
								<div class="col-two col-half-width">
									<input id="city" name="city" type="text" value="<?php echo $userData['City']; ?>" placeholder="City" />
								</div>

								<label for="state" class="col-one col-half-width align-right">State / Region</label>
								<div class="col-two col-half-width">
									<input id="state" name="state" type="text" value="<?php echo $userData['State']; ?>" placeholder="State / Region" />
								</div>

								<label for="postalcode" class="col-one col-half-width">Postal Code</label>
								<div class="col-two col-half-width">
									<input id="postalcode" name="postalcode" type="text" value="<?php echo $userData['PostalCode']; ?>" placeholder="Postal Code" />
								</div>

								<label for="country" class="col-one col-half-width align-right">Country</label>
								<div class="col-two col-half-width">
									<select name="country" id="country">
									<?php
										$countries = CountriesArray::get2d('name', array('alpha2', 'alpha3'));
										foreach($countries as $name=>$meta) {
											echo '<option value="'.$meta['alpha3'].'" data-country-name="'.$name.'" data-country-alpha2="'.$meta['alpha2'].'" '.($userData['Country'] === $meta['alpha3'] ? 'selected' : '').'>'.$name.'</option>';
										}
									?>
									</select>
								</div>
							</div>
						</div>

						<input type="hidden" name="salt" value="<?php echo $user['Salt']; ?>" />

						<button type="submit">Update profile information</button>
					</form>

					<h3>Account deletion</h3>
					<p><strong>Account deletion is permanent and cannot be undone.</strong> Please note that deleted accounts will only do the following:</p>
					<ul>
						<li>disassociate your unique user identifier with other data on <em>Lotus</em> Base.</li>
						<li>remove any associated third party OAuth IDs, if any, with <em>Lotus</em> Base, and</li>
						<li>unsubscribe you from the <em>Lotus</em> Base mailing list</li>
						<li>removal of CORNEA networks generated by a user</li>
					</ul>
					<p>Deleting your account will <em>not</em> perform the following operations:</p>
					<ul>
						<li>removal of <em>LORE1</em> orders placed by a user</li>
					</ul>
					<p>Should you wish to remove the above said metadata, please <a href="<?php echo WEB_ROOT.'/meta/contact'; ?>" title="Contact us">contact us</a>.</p>

					<form method="post" action="#" class="has-group" id="account-deletion">
						<div class="cols" role="group">
							<p class="user-message full-width">We simply want to reconfirm your identity before you delete your account.</p>
							<label class="col-one" for="ad_pass">Password</label>
							<div class="col-two">
								<input type="password" name="ad_pass" id="ad_pass" placeholder="Password" required />
								<a href="./reset" title="Reset password">Forgot password?</a>
							</div>

							<div class="col-one"></div>
							<div class="col-two">
								<label for="ad_consent"><input type="checkbox" name="ad_consent" id="ad_consent" class="prettify" required />I consent to the deletion of my <em>Lotus</em> Base account</label>
							</div>
							<input type="hidden" name="salt" value="<?php echo $user['Salt']; ?>" />

							<button type="submit" class="warning"><span class="icon-trash-empty">Delete account</span></button>
						</div>
					</form>
					<?php

				} catch(PDOException $e) {
					echo '<p class="user-message warning"><span class="icon-attention"></span>'.$e->getMessage().'</p>';
				} catch(Exception $e) {
					echo '<p class="user-message warning"><span class="icon-attention"></span>'.$e->getMessage().'</p>';
				}
			?>
			</div>

			<div id="security">
				<p>You can change your password any time you wish. We strongly recommend updating your password if you suspect that your password has been compromised, or disclosed to third parties. <strong>We never disclose your encrypted passwords</strong>. Passwords are encrypted before transmission and storage&mdash;meaning that we have no idea what your password is, too.</p>
				<form method="post" action="#" class="has-group" id="update-password">
					<div class="has-legend cols" role="group">
						<span class="user-message full-width minimal legend">Reauthenticate</span>
						<p class="full-width">We simply want to reconfirm your identity before a password is changed.</p>
						<label class="col-one" for="oldpass">Current password</label>
						<div class="col-two">
							<input type="password" name="oldpass" id="oldpass" placeholder="Current password" required />
							<a href="./reset" title="Reset password">Forgot password?</a>
						</div>
					</div>

					<div class="has-legend cols" role="group">
						<span class="user-message full-width minimal legend">Update</span>
						<label class="col-one" for="newpass">New password</label>
						<div class="col-two">
							<div class="cols flex-wrap__nowrap">
								<input type="password" name="newpass" id="newpass" placeholder="New password" style="margin-right: 1rem;" required />
								<input type="password" name="newpass_rep" placeholder="Retype new password" required />
							</div>
						</div>
					</div>

					<input type="hidden" name="salt" value="<?php echo $user['Salt']; ?>" />

					<button type="submit">Update password</button>
				</form>
			</div>

			<div id="integration">
				<h3>Third party integration</h3>
				<p>Integrate your account with third party identity providers:</p>
				<form class="form--reset" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
					<table id="oauth-list">
						<thead>
							<tr>
								<th>Provider</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
						<?php
							// Generate OAuth list
							$oauth_providers = array(
								'Google' => array(
									'url' => $client->createAuthUrl()
									),
								'LinkedIn' => array(
									'url' => 'https://www.linkedin.com/oauth/v2/authorization?response_type=code&amp;client_id='.LINKEDIN_CLIENT_ID.'&amp;redirect_uri='.urlencode($oauth2_redirect_uri.'/linkedin').'&amp;state='.$oauth2_state
									),
								'GitHub' => array(
									'url' => 'https://github.com/login/oauth/authorize?client_id='.GITHUB_CLIENT_ID.'&redirect_uri='.urlencode($oauth2_redirect_uri.'/github').'&scope=user:email&state='.$oauth2_state
									)
								);

							foreach($oauth_providers as $p => $d) {
								echo '
								<tr class="oauth oauth__'.strtolower($p).'">
									<td>
										<span class="oauth__logo-wrapper">
											<img
												src="'.WEB_ROOT.'/dist/images/users/oauth/'.strtolower($p).'.svg"
												alt=""
												title=""
												class="oauth__logo"
												/>
											'.$p.'
										</span>
									</td>
									<td>
										<input type="checkbox" class="prettify oauth__toggle" id="oath__'.$p.'" name="provider" value="'.$p.'" data-oauth-url="'.$d['url'].'" '.(isset($userData[$p.'ID']) ? 'checked' : '').' />
									</td>
								</tr>
								';
							}
						?>
						</tbody>
					</table>
					<input type="hidden" name="salt" value="<?php echo $user['Salt']; ?>" />
				</form>

				<h3>Newsletter subscription</h3>
				<p>We are currently using Mailchimp for email subscription management.</p>
				<form id="newsletter-subscription" class="form--reset" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
					<?php
						$MailChimp = new MailChimp(MAILCHIMP_API_KEY);

						$MailChimp_lists = array('c469e14ec3');

						foreach($MailChimp_lists as $l) {
							$MailChimp_list = $MailChimp->get("lists/".$l);
							$MailChimp_member = $MailChimp->get("lists/".$l."/members/".md5($user['Email']));

							if($MailChimp_member['status'] == 404) {
								$MailChimp_member = null;
							}

							echo '<input type="checkbox" class="prettify subscription__toggle" name="subscription" value="'.$l.'" data-provider="mailchimp" data-list=id="'.$l.'" data-list-name="'.$MailChimp_list['name'].'" data-email="'.$user['Email'].'" '.($MailChimp_member ? 'checked' : '').'/>'.preg_replace('/(Lotus)/', '<em>$1</em>', $MailChimp_list['name']);
						}

						
					?>
					<input type="hidden" name="salt" value="<?php echo $user['Salt']; ?>" />
					
				</form>
			</div>


			<div id="access_token">

				<p>Here is a list of currently active and in-use API keys you have created. Each token ID is generated randomly by our servers securely and cryotpgraphically as a 16-character hexadecimal string. This token ID is subsequently embedded in a JSON web token (JWT) alongside with a salt associated with your account, encoded with a secret site-wide key using <a href="https://en.wikipedia.org/wiki/Hash-based_message_authentication_code">HMAC-SHA256</a>.</p>
				<p class="user-message note">We do not restrict API keys to individual domains. If you suspect that your API key is being misused or abused, you can revoke it at any time and create a new one.</p>
				<?php
					try {
						$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306", DB_USER, DB_PASS);
						$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

						$apikeys = $db->prepare('SELECT
								t1.Token AS Token,
								t1.Comment AS Name,
								t1.UserSalt AS UserSalt,
								t1.Created AS Created,
								t1.LastAccessed AS LastAccessed
							FROM apikeys AS t1
							WHERE t1.UserSalt = ?');
						$apikeys->execute(array($user['Salt']));

						if($apikeys->rowCount()) {
							echo '<table id="api-keys" class="table--dense"><thead><tr>';
							echo '<th>Access token</th>';
							echo '<th>Name</th>';
							echo '<th>Created</th>';
							echo '<th>Last accessed</th>';
							echo '<th>Action</th>';
							echo '</tr></thead><tbody>';
							while($row = $apikeys->fetch(PDO::FETCH_ASSOC)) {
								echo '<tr id="token-'.$row['Token'].'">';
								foreach ($row as $key => $value) {
									echo $key === 'UserSalt' ? '' : ('<td class="'.($value ? $value : 'align-center').'">'.($key === 'Token' ? '<input type="text" class="access-token" value="'.create_api_access_token($row['Token'], $row['Created'], $row['UserSalt']).'" readonly />' : ($value ? $value : '&ndash;')).'</td>');
								}
								echo '<td><a href="#" class="api-key__revoke button button--small warning" data-token="'.$row['Token'].'"><span class="icon-cancel">Revoke</span></a></td>';
								echo '</tr>';
							}
							echo '</tbody></table>';
						} else {
							echo '<p>You have not created an API key yet. You can use the form below to create one:</p>';
						}

					} catch(PDOException $e) {

					}
				?>
				<form method="post" action="#" class="has-group" id="api-key__create">
					<div class="has-legend cols" role="group">
						<span class="user-message full-width minimal legend">Create new API key</span>
						<label class="col-one" for="api-key__comment">Name (optional)</label>
						<input class="col-two" type="text" name="comment" id="api-key__comment" placeholder="Optional name for API key for easy identification" maxlength="1024" />

						<button type="submit">Generate new API key</button>
					</div>
				</form>
			</div>
		</div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/users.min.js"></script>
	<script>
		// Select2
		$(function() {
			$('#organization').select2({
				placeholder: "Select a pre-existing organization, or enter a new one",
				tags: true
			});

			$("#organization")
			.on("select2:open", function() {
				$(".select2-search__field").attr("placeholder", "Select a pre-existing organization, or enter a new one");
			})
			.on("select2:close", function() {
				$(".select2-search__field").attr("placeholder", null);
			});
		});
	</script>
</body>
</html>
