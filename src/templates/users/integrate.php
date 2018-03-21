<?php
	namespace LotusBase;
	use \PDO;

	class IntegrationException	extends \Exception {};
	class Exception				extends \Exception {};

	// Load important files
	require_once('../config.php');

	try {

		// Database connection
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		// Get all organizations
		$orgs = array('Select a pre-existing organization, or enter a new one' => '');
		$o = $db->prepare('SELECT DISTINCT(Organization) AS Organization FROM auth');
		$o->execute();
		while($rows = $o->fetch(PDO::FETCH_ASSOC)) {
			$orgs[$rows['Organization']] = $rows['Organization'];
		}

		if(isset($_SESSION['user_integration'])) {
			$session = $_SESSION['user_integration'];
		} else {
			$session = null;
		}

		// GET requests are from the OAuth callback page
		if($_GET && $_GET['state']) {

			// Check state
			if($_GET['state'] !== $session['state']) {
				throw new Exception('Session expired. Please connect again.');
			}

			// Clear state to prevent replay attacks
			unset($_SESSION['user_integration']['state']);

			// Create new state
			$state = base64_encode(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM));
			$_SESSION['user_integration']['state'] = $state;

			if(!in_array($_GET['action'], array('link', 'create'))) {
				throw new Exception('Account integration action is not valid.');
			}
			$action = $_GET['action'];

			// Check if user salt is present
			if(!isset($_SESSION['user_integration']['local_userData']['Salt']) || empty($_SESSION['user_integration']['local_userData']['Salt'])) {
				$action = 'create';
			}

			$stage = 1;

		}
		// POST requests are from user interaction on this page
		else if($_POST) {

			// Check state
			if($_POST['state'] !== $_SESSION['user_integration']['state'])	 {
				throw new Exception('Session expired. Please connect again.');
			}

			// Check action
			if(!in_array($_POST['action'], array('link', 'create'))) {
				throw new Exception('Account integration action is not valid.');
			}

			// Clear state to prevent replay attacks
			unset($_SESSION['user_integration']['state']);

			// Create new state
			$state = base64_encode(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM));
			$_SESSION['user_integration']['state'] = $state;

			// Select action
			switch ($_POST['action']) {

				// Integrate
				case 'link':
					// Check if user salt is present
					if(!isset($_SESSION['user_integration']['local_userData']['Salt']) || empty($_SESSION['user_integration']['local_userData']['Salt'])) {
						throw new Exception('User identifier not found.');
					}
					// Check if fields exist
					if(!isset($_POST['fields']) || empty($_POST['fields'])) {
						throw new \LotusBase\IntegrationException('Request is incomplete.');
					}

					// Check if user picked an option
					if(in_array('Email', unserialize($_POST['fields'])) && (!isset($_POST['Email']) || empty($_POST['Email']))) {
						throw new \LotusBase\IntegrationException('Please resolve email address conflict before account integration.');
					}
					if(in_array('FirstName', unserialize($_POST['fields'])) && (!isset($_POST['FirstName']) || empty($_POST['FirstName']))) {
						throw new \LotusBase\IntegrationException('Please resolve first name conflict before account integration.');
					}

					// Update database
					// We mark user as verified since they have authenticated via an OAuth provider
					$q1 = $db->prepare('UPDATE auth SET
						Verified = 1,
						Email = ?,
						FirstName = ?,
						LastName = ?,
						'.$session['provider'].'ID = ?
					WHERE Salt = ?');
					$e1 = $q1->execute(array(
						!empty($_POST['Email']) ? $_POST['Email'] : $_SESSION['user_integration']['local_userData']['Email'],
						!empty($_POST['FirstName']) ? $_POST['FirstName'] : ($_SESSION['user_integration']['local_userData']['FirstName'] ? $_SESSION['user_integration']['local_userData']['FirstName'] : null),
						!empty($_POST['LastName']) ? $_POST['LastName'] : ($_SESSION['user_integration']['local_userData']['LastName'] ? $_SESSION['user_integration']['local_userData']['LastName'] : null),
						$_SESSION['user_integration']['OAuth_userData']['ID'],
						$_SESSION['user_integration']['local_userData']['Salt']
						));

					if(!$e1) {
						throw new \LotusBase\IntegrationException('Unable to update your internal database with your updated account details.');
					}

					// Log the user in
					$q3 = $db->prepare("SELECT * FROM auth WHERE Salt = ?");
					$r3 = $q3->execute(array($_SESSION['user_integration']['local_userData']['Salt']));

					if(!$r3 || $q3->rowCount() !== 1) {
						throw new \LotusBase\IntegrationException('Unable to retrieve user data from database to generate authentication token.');
					} else {
						$row = $q3->fetch(PDO::FETCH_ASSOC);
					}

					// Create JWT token and store it on the client-side
					// We give the user 24 hours of activity before enforcing verification
					$jwt = new \LotusBase\Users\AuthToken();
					$jwt->setUserData($row);
					$jwt->setValidDuration(60*60*24);
					$jwt->setCookie();

					// Set refresh header
					header('Refresh: 5');

					// Integration successful, display info
					$stage = 2;

					break;

				// Create new account
				case 'create':
					
					// Check if name and email fields are present
					if(
						!isset($session['OAuth_userData']['FirstName']) ||
						empty($session['OAuth_userData']['FirstName']) ||

						!isset($session['OAuth_userData']['LastName']) ||
						empty($session['OAuth_userData']['LastName']) ||

						!isset($session['OAuth_userData']['Email']) ||
						empty($session['OAuth_userData']['Email'])
						) {
						throw new \LotusBase\IntegrationException('Missing name and email information from third party ID provider ('.$session['provider'].')');
					}

					if(!isset($_POST['consent']) || empty($_POST['consent'])) {
						throw new \LotusBase\IntegrationException('You must accept the terms and conditions of use in order to open an account.');
					}

					// Proceed with generating new user
					// We create a verifcation key so that the user can verify their account from the email they will receive from us
					// We also flag user as verfied, since they are using a sanctioned OAuth ID provider
					$verificationkey = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
					$usersalt = bin2hex(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM));
					$password = password_hash($password, PASSWORD_DEFAULT);
					$q2 = $db->prepare("INSERT INTO auth(Username, FirstName, LastName, Email, ".$session['provider']."ID, Organization, Verified, VerificationKey, MailingList, Salt) VALUES(?,?,?,?,?,?,?,?,?,?)");
					$r2 = $q2->execute(array(
						$session['OAuth_userData']['Email'],
						$session['OAuth_userData']['FirstName'],
						$session['OAuth_userData']['LastName'],
						$session['OAuth_userData']['Email'],
						$session['OAuth_userData']['ID'],
						isset($_POST['organization']) ? $_POST['organization'] : null,
						1,
						$verificationkey,
						0,
						$usersalt));

					if(!$r2 || !$q2->rowCount()) {
						throw new \LotusBase\IntegrationException('Unable to insert user data into database.');
					}

					// Fetch newly inserted user data
					$q3 = $db->prepare("SELECT * FROM auth WHERE Salt = ?");
					$r3 = $q3->execute(array($usersalt));

					if(!$r3 || $q3->rowCount() !== 1) {
						throw new \LotusBase\IntegrationException('Unable to retrieve user data from database to generate authentication token.');
					} else {
						$row = $q3->fetch(PDO::FETCH_ASSOC);
					}

					// Create JWT token and store it on the client-side
					// We give the user 24 hours of activity before enforcing verification
					$jwt = new \LotusBase\Users\AuthToken();
					$jwt->setUserData($row);
					$jwt->setValidDuration(60*60*24);
					$jwt->setCookie();

					// Send mail to user
					$mail = new \PHPMailer(true);

					// Construct mail
					$mail_generator = new \LotusBase\MailGenerator();
					$mail_generator->set_title('<em>Lotus</em> Base: New user registration');
					$mail_generator->set_header_image('cid:mail_header_image');
					$mail_generator->set_content(array(
						'<h3 style="text-align: center; ">Welcome to <em>Lotus</em> Base</h3>
						<p>Thank you for signing up to join the <em>Lotus</em> Base community. You have successfully signed up for an account through a third party identity provider, '.$session['provider'].'&mdash;since it is a trust provider, your account has been automatically verified. You may continue to use the site now.</p>
						<p>You can also <a href="'.DOMAIN_NAME.'/users">visit your account dashboard</a> to have an overview of your account, and update your profile as of when you see fit.</p>
						'));

					$mail->IsSMTP();
					$mail->IsHTML(true);
					$mail->Host			= SMTP_MAILSERVER;
					$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
					$mail->CharSet		= "utf-8";
					$mail->Encoding		= "base64";
					$mail->Subject		= "Lotus Base: Welcome to the community!";
					$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
					$mail->MsgHTML($mail_generator->get_mail());
					$mail->AddAddress($session['OAuth_userData']['Email'], $session['OAuth_userData']['FirstName'].' '.$session['OAuth_userData']['LastName']);

					$mail->AddEmbeddedImage(DOC_ROOT."/dist/images/branding/logo-256x256.png", mail_header_image);
					$mail->smtpConnect(
						array(
							"ssl" => array(
								"verify_peer" => false,
								"verify_peer_name" => false,
								"allow_self_signed" => true
							)
						)
					);

					$mail->Send();

					// Set refresh header
					header('Refresh: 5');

					// Account creation successful, display info
					$stage = 3;

					break;
				
				default:
					throw new Exception('Account integration action is not valid.');
					break;
			}
		}
		// Disallow direct access
		else {
			throw new Exception();
		}
	} catch(\LotusBase\IntegrationException $e) {
		$_SESSION['user_integration_error'] = $e->getMessage();
		session_write_close();
		header("location: ".$_SERVER['PHP_SELF'].'?action='.$_REQUEST['action'].'&state='.$state);
		exit();
	} catch(\PDOException $e) {
		$_SESSION['user_integration_error'] = $e->getMessage();
		session_write_close();
		header("location: ".$_SERVER['PHP_SELF'].'?action='.$_REQUEST['action'].'&state='.$state);
		exit();
	} catch(Exception $e) {
		if(isset($_SESSION['user_integration_error'])) {
			unset($_SESSION['user_integration_error']);
		}
		$_SESSION['user_login_error'] = array('message' => $e->getMessage());
		session_write_close();
		header("location: login.php");
		exit();
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>User Login&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" integrity="sha384-wCtV4+Y0Qc1RNg341xqADYvciqiG4lgd7Jf6Udp0EQ0PoEv83t+MLRtJyaO5vAEh" crossorigin="anonymous">
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/users.min.css" type="text/css" media="screen" />
</head>
<body class="users integrate theme--white">
	<?php
		// Generate header
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_theme('white');
		echo $header->get_header();
	?>

	<section class="wrapper">
	<?php
	if($stage === 1) {
		// How can we integrate users?
		switch ($action) {
			case 'link':
				// Account exists (by checking email or first+last name combinations)
				// We simply offer users to pick which email they want to use

				// Get differences in OAuth and local data
				$diff = $session['diff_userData'];

				// Get name
				$name = $session['OAuth_userData']['FirstName'].' '.$session['OAuth_userData']['LastName'];

				?>
				<h1 class="align-center">Account integration</h1>
				<span class="byline">Update your <em>Lotus</em> Base account</span>

				<div class="cols align-items__center justify-content__center user-card__compare">
					<div id="user-card__<?php echo $session['provider']; ?>" class="user-card" item-scope itemtype="http://schema.org/Person">
						<div class="user-card__image">
							<div class="user-card__avatar-wrapper">
								<?php if(isset($session['OAuth_userData']['Avatar'])) { ?>
								<img class="user-card__avatar" src="<?php echo $session['OAuth_userData']['Avatar']; ?>" alt="<?php echo $name; ?> user" title="<?php echo $name; ?> user" />
								<?php } else { ?>
								<span class="icon-user icon--no-spacing"></span>
								<?php } ?>
							</div>
							<div class="user-card__provider-wrapper">
								<img class="user-card__provider" src="<?php echo WEB_ROOT.'/dist/images/users/oauth/'.strtolower($session['provider']).'.svg'; ?>" alt="<?php echo $session['provider']; ?>" title="<?php echo $session['provider']; ?>" />
							</div>
						</div>
						<div class="user-card__meta">
							<span class="user-card__name" itemprop="name"><?php echo $name; ?></span>
							<span class="user-card__email" itemprop="email"><?php echo $session['OAuth_userData']['Email']; ?></span>
						</div>
					</div>
					<span class="vs icon-right-open-big icon--no-spacing"></span>
					<div id="user-card__lotus-base" class="user-card" item-scope itemtype="http://schema.org/Person">
						<div class="user-card__image">
							<div class="user-card__avatar-wrapper">
								<span class="icon-user icon--no-spacing"></span>
							</div>
							<div class="user-card__provider-wrapper">
								<img class="user-card__provider" src="<?php echo WEB_ROOT.'/dist/images/branding/logo.svg'; ?>" alt="Lotus Base user" title="Lotus Base user" />
							</div>
						</div>
						<div class="user-card__meta">
							<span class="user-card__name" itemprop="name"><?php echo $session['local_userData']['FirstName'].' '.$session['local_userData']['LastName']; ?></span>
							<span class="user-card__email" itemprop="email"><?php echo $session['local_userData']['Email']; ?></span>
						</div>
					</div>
				</div>

				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="has-group">
					<p class="user-message"> <strong>The account integration process will not modify any details on your <?php echo $session['provider']; ?> account</strong>&mdash;we will only modify the account details of your <em>Lotus</em> Base account, with your explicit permission.</p>
					<p>We have found an account registered in our database that matches your personal details from your <?php echo $session['provider']; ?> account.<?php echo (count($diff) ? ' Before we proceed, we need your help at resolving some differential information:' : ''); ?></p>
					<?php if(count($diff)) { ?>
					<div class="cols has-legend" role="group">
						<span class="legend minimal user-message">Update <em>Lotus</em> Base account</span>
						<?php
							if(isset($_SESSION['user_integration_error'])) {
								echo '<p class="user-message reminder full-width">'.$_SESSION['user_integration_error'].'</p>';
								unset($_SESSION['user_integration_error']);
							}

							// Resolve different emails
							if(array_key_exists('Email', $diff)) {
						?>
						<label class="col-one">Pick an email</label>
						<div class="col-two">
							<label for="email__lotusbase"><input type="radio" id="email__lotusbase" name="Email" value="<?php echo $session['local_userData']['Email']; ?>" <?php if(empty($session['OAuth_userData']['Email'])) { echo 'checked'; }?> /><span><strong><?php echo $session['local_userData']['Email']; ?></strong> (registered with <em>Lotus</em> Base)</span></label>
							<?php if(!empty($session['OAuth_userData']['Email'])) { ?>
								<label for="email__<?php echo $session['provider']; ?>"><input type="radio" id="email__<?php echo $session['provider']; ?>" name="Email" value="<?php echo $session['OAuth_userData']['Email']; ?>" /><span><strong><?php echo $session['OAuth_userData']['Email']; ?></strong> (registered with <?php echo $session['provider']; ?>)</span></label>
							<?php } ?>
						</div>
						<?php } ?>

						<?php
							// Resolve different emails
							if(array_key_exists('FirstName', $diff)) {
						?>
						<label class="col-one">First name</label>
						<div class="col-two">
							<label for="fname__lotusbase"><input type="radio" id="fname__lotusbase" name="FirstName" value="<?php echo $session['local_userData']['FirstName']; ?>" /><span><strong><?php echo $session['local_userData']['FirstName']; ?></strong> (registered with <em>Lotus</em> Base)</span></label>
							<label for="fname__<?php echo $session['provider']; ?>"><input type="radio" id="fname__<?php echo $session['provider']; ?>" name="FirstName" value="<?php echo $session['OAuth_userData']['FirstName']; ?>" /><span><strong><?php echo $session['OAuth_userData']['FirstName']; ?></strong> (registered with <?php echo $session['provider']; ?>)</span></label>
						</div>
						<?php } ?>

					</div>
					<input type="hidden" name="action" value="<?php echo $action; ?>" />
					<input type="hidden" name="state" value="<?php echo $state; ?>" />
					<input type="hidden" name="fields" value='<?php echo serialize(array_keys($diff)); ?>' />
					<div class="cols align-items__center">
						<button type="submit" role="primary"><span class="icon-user">Integrate accounts</span><br /><small>Merge account data</small></button>
						<a href="<?php echo $_SERVER['PHP_SELF'].'?action=create&state='.$state; ?>" class="button align-center" role="secondary"><span class="icon-user-plus">Create new account</span><br /><small>Do not integrate</small></a>
					</div>

				</form>
				<?php } ?>
				<?php
				break;

			case 'create':
				// Account does not exist
				// We create new account using details gleaned from the OAuth data

				// Get name
				$name = $session['OAuth_userData']['FirstName'].' '.$session['OAuth_userData']['LastName'];

				?>
				<h1 class="align-center">Account creation</h1>
				<span class="byline">Create new <em>Lotus</em> Base account</span>

				<div class="cols align-items__center justify-content__center user-card__compare">
					<div id="user-card__<?php echo $session['provider']; ?>" class="user-card" item-scope itemtype="http://schema.org/Person">
						<div class="user-card__image">
							<div class="user-card__avatar-wrapper">
								<?php if(isset($session['OAuth_userData']['Avatar'])) { ?>
								<img class="user-card__avatar" src="<?php echo $session['OAuth_userData']['Avatar']; ?>" alt="<?php echo $name; ?> user" title="<?php echo $name; ?> user" />
								<?php } ?>
							</div>
							<div class="user-card__provider-wrapper">
								<img class="user-card__provider" src="<?php echo WEB_ROOT.'/dist/images/users/oauth/'.strtolower($session['provider']).'.svg'; ?>" alt="<?php echo $session['provider']; ?>" title="<?php echo $session['provider']; ?>" />
							</div>
						</div>
						<div class="user-card__meta">
							<span class="user-card__name" itemprop="name"><?php echo $name; ?></span>
							<span class="user-card__email" itemprop="email"><?php echo $session['OAuth_userData']['Email']; ?></span>
						</div>
					</div>
					<span class="vs icon-right-open-big icon--no-spacing"></span>
					<div id="user-card__lotus-base" class="user-card" item-scope itemtype="http://schema.org/Person">
						<div class="user-card__image">
							<div class="user-card__avatar-wrapper">
								<span class="icon-user-plus icon--no-spacing"></span>
							</div>
							<div class="user-card__provider-wrapper">
								<img class="user-card__provider" src="<?php echo WEB_ROOT.'/dist/images/branding/logo.svg'; ?>" alt="Lotus Base user" title="Lotus Base user" />
							</div>
						</div>
						<div class="user-card__meta">
							<span class="user-card__name" itemprop="name"><?php echo $name; ?></span>
							<span class="user-card__email" itemprop="email"><?php echo $session['OAuth_userData']['Email']; ?></span>
						</div>
					</div>
				</div>

				<p>We will use the following information from your <?php echo $session['provider']; ?> account, as displayed above, to create a new account on <em>Lotus</em> Base for you.</p>
				<p>No further information has been extracted from your <?php echo $session['provider']; ?> account&mdash;you may, however, update your user profile after you have successfully signed up, to provide us with additional information should you desire.</p>

				<p>Please provide us with a little bit more information to faciliate with your new account creation:</p>

				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="oauth-registration-form">
					<div class="cols">
						<?php
						if(isset($_SESSION['user_integration_error'])) {
							echo '<p class="user-message reminder full-width">'.$_SESSION['user_integration_error'].'</p>';
							unset($_SESSION['user_integration_error']);
						}

						// Prompt user to provide email if it is missing
						if(empty($session['OAuth_userData']['Email']) && empty($session['local_userData']['Email'])) {
							?>
							<label class="col-one" for="email">Email</label>
							<div class="col-two">
								<input type="email" id="email" name="email" placeholder="Email address" />
							</div>
							<?php
						}

						?>
						<label class="col-one" for="organization">Organization</label>
						<div class="col-two">
							<select name="organization" id="organization">
							<?php
							foreach($orgs as $title => $value) {
								echo '<option value="'.$value.'">'.$title.'</option>';
							}
							?>	
							</select>
						</div>

						<div class="col-one"></div>
						<div class="col-two">
							<label for="mailing-list"><input type="checkbox" id="mailing-list" name="mailinglist" checked /><span>Subscribe to the <em>Lotus</em> Base newsletter</span></label>
							<label for="consent"><input type="checkbox" id="consent" name="consent" /><span>I accept the <a href="'.WEB_ROOT.'/meta/legal">terms of service and privacy policy</a>.</span></label>
						</div>
					</div>

					<input type="hidden" name="action" value="<?php echo $action; ?>" />
					<input type="hidden" name="state" value="<?php echo $state; ?>" />
					<button type="submit"><span class="icon-user-plus">Create new <em>Lotus</em> Base account</span></button>
				</form>
				<?php
				break;
			
			default:
				break;
		}
	}
	else if($stage === 2) {
		// Successful integration
		?>
		<h1 class="align-center"><span class="icon-ok icon--big icon--no-spacing">Account integration</span></h1>
		<span class="byline"><em>Lotus</em> Base account successfully updated</span>

		<div class="cols align-items__center justify-content__center user-card__compare">
			<div id="user-card__lotus-base" class="user-card" item-scope itemtype="http://schema.org/Person">
				<div class="user-card__image">
					<div class="user-card__avatar-wrapper">
						<span class="icon-user icon--no-spacing"></span>
					</div>
					<div class="user-card__provider-wrapper">
						<img class="user-card__provider" src="<?php echo WEB_ROOT.'/dist/images/branding/logo.svg'; ?>" alt="Lotus Base user" title="Lotus Base user" />
					</div>
				</div>
				<div class="user-card__meta">
					<span class="user-card__name" itemprop="name"><?php echo $session['local_userData']['FirstName'].' '.$session['local_userData']['LastName']; ?></span>
					<span class="user-card__email" itemprop="email"><?php echo $session['local_userData']['Email']; ?></span>
				</div>
			</div>
		</div>

		<p>We have successfully integrated your <em>Lotus</em> Base account with your <?php echo $session['provider']; ?> account details, and you have been logged in for your convenience. <strong>This page will refresh in 5 seconds and you will be redirected to your dashboard.</strong></p>
		<p>You may proceed to <a href="/">use the site normally</a>, or <a href="<?php echo WEB_ROOT.'/users'; ?>">view your dashboard</a>.</p>

		<?php

		// Unset entire session
		unset($_SESSION['user_integration']);
	}
	else if($stage === 3) {
		// Successful account creation
		?>
		<h1 class="align-center"><span class="icon-ok icon--big icon--no-spacing">Account created</span></h1>
		<span class="byline"><em>Lotus</em> Base account successfully created</span>

		<div class="cols align-items__center justify-content__center user-card__compare">
			<div id="user-card__lotus-base" class="user-card" item-scope itemtype="http://schema.org/Person">
				<div class="user-card__image">
					<div class="user-card__avatar-wrapper">
						<span class="icon-user icon--no-spacing"></span>
					</div>
					<div class="user-card__provider-wrapper">
						<img class="user-card__provider" src="<?php echo WEB_ROOT.'/dist/images/branding/logo.svg'; ?>" alt="Lotus Base user" title="Lotus Base user" />
					</div>
				</div>
				<div class="user-card__meta">
					<span class="user-card__name" itemprop="name"><?php echo $session['OAuth_userData']['FirstName'].' '.$session['OAuth_userData']['LastName']; ?></span>
					<span class="user-card__email" itemprop="email"><?php echo $session['OAuth_userData']['Email']; ?></span>
				</div>
			</div>
		</div>

		<p>We have successfully integrated your <em>Lotus</em> Base account with your <?php echo $session['provider']; ?> account details, and you have been logged in for your convenience. You may proceed to <a href="/">use the site normally</a>, or <a href="<?php echo WEB_ROOT.'/users'; ?>">view your dashboard</a>.</p>

		<?php

		// Unset entire session
		unset($_SESSION['user_integration']);
	}
	?>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js" integrity="sha384-iViGfLSGR6GiB7RsfWQjsxI2sFHdsBriAK+Ywvt4q8VV14jekjOoElXweWVrLg/m" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/users.min.js"></script>
	<script>
		$(function() {
			// Select2
			$('#organization').select2({
				placeholder: "Select a pre-existing organization, or enter a new one",
				tags: true
			});
		});
	</script>
</body>
</html>