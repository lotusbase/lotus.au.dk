<?php

	namespace LotusBase;
	use \PDO;
	use \PHPMailer;

	class Exception	extends \Exception {};

	// Load important files
	require_once('../config.php');

	// Use JWT
	use \Firebase\JWT\JWT;

	// Google Recaptcha
	$recaptcha = new \ReCaptcha\ReCaptcha(GRECAPTCHA_API_KEY);

	// OAuth Redirect URI
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

	// Redirect to dashboard if user is already logged in
	if(is_logged_in()) {
		$userData = is_logged_in();
		if(isset($userData['Authority']) && $userData['Authority'] > 3) {
			header('location: ../users/');
		} else {
			header('location: ../admin/');
		}
		exit();
	}

	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		$orgs = array('Select a pre-existing organization, or enter a new one' => '');
		$o = $db->prepare('SELECT DISTINCT(Organization) AS Organization FROM auth');
		$o->execute();
		while($rows = $o->fetch(PDO::FETCH_ASSOC)) {
			$orgs[$rows['Organization']] = $rows['Organization'];
		}

	} catch(PDOException $e) {

	}

	// Error
	$error = array();

	// User inputs
	$inputs;

	// If POST variable is defined
	if(isset($_POST) && !empty($_POST)) {

		$inputs = $_POST;

		// Verify CSRF token
		try {
			$csrf_protector->verify_token();
		} catch(CSRFTokenVerificationException $e) {
			$error[] = $e->getMessage();
		}

		// Check user input
		if(!isset($_POST['firstname']) || !isset($_POST['lastname']) || empty($_POST['firstname']) || empty($_POST['lastname'])) {
			$error[] = 'Please enter your name.';
		}
		else if(!isset($_POST['username']) || empty($_POST['username'])) {
			$error[] = 'Please enter your username.';
		}
		else if(strlen($_POST['username']) < 2) {
			$error[] = 'Selected username must be at least <strong>two</strong> characters long.';
		}
		else if(strlen($_POST['username']) > 255) {
			$error[] = 'Selected username must not be longer than <strong>255</strong> characters.';
		}
		else if(!isset($_POST['email']) || empty($_POST['email'])) {
			$error[] = 'Please enter your email.';
		}
		else if(strlen($_POST['email']) > 255) {
			$error[] = 'Email address must not be longer than <strong>255</strong> characters.';
		}
		else if(!isset($_POST['password']) || empty($_POST['password'])) {
			$error[] = 'Please enter a password.';
		}
		else if(!isset($_POST['consent']) || empty($_POST['consent'])) {
			$error[] = 'You must accept the terms and conditions of use in order to open an account.';
		}
		else if(!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
			$error[] = 'You have not completed the captcha.';
		}
		else {
			$resp = $recaptcha->verify($_POST['g-recaptcha-response'], get_ip());
			if(!$resp->isSuccess()) {
				$error[] = 'You have provided an invalid verification token.';
			}
		}

		if(!$error) {

			// Connect to database
			try {

				// Retrieve POST values
				$username		= $_POST['username'];
				$email			= $_POST['email'];
				$password		= $_POST['password'];
				$firstname		= $_POST['firstname'];
				$lastname		= $_POST['lastname'];
				$captcha		= $_POST['g-recaptcha-response'];
				$organization	= (empty($_POST['organization']) ? null : $_POST['organization']);
				$mailinglist	= (isset($_POST['mailinglist']) ? 1 : 0);

				// Check for duplicate users
				$q1 = $db->prepare("SELECT * FROM auth WHERE Username = :username OR Email = :email");
				$q1->bindParam(':username', $username);
				$q1->bindParam(':email', $email);
				$q1->execute();

				if($q1->rowCount() > 0) {
					$_SESSION['reg_error'] = array(
						'message' => 'User already exists in the system. If you have forgotten your password, please <a href="'.WEB_ROOT.'/users/reset" title="Reset Lotus Base user account password">reset it here</a>.',
						'inputs' => $_POST
					);
					session_write_close();
					header("location: ".$_SERVER['PHP_SELF']);
					exit();
				}

				// Proceed with generating new user
				// We create a verifcation key so that the user can verify their account from the email they will receive from us
				$verificationkey = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
				$usersalt = bin2hex(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM));
				$password = password_hash($password, PASSWORD_DEFAULT);
				$q2 = $db->prepare("INSERT INTO auth(Username, FirstName, LastName, Email, Password, Organization, VerificationKey, MailingList, Salt) VALUES(?,?,?,?,?,?,?,?,?)");
				$r2 = $q2->execute(array($username, $firstname, $lastname, $email, $password, $organization, $verificationkey, $mailinglist, $usersalt));

				if(!$r2 || !$q2->rowCount()) {
					throw new Exception('Unable to insert user data into database.');
				}

				// Fetch newly inserted user data
				$q3 = $db->prepare("SELECT
						auth.*,
						adminprivileges.*,
						GROUP_CONCAT(DISTINCT authUserGroup.UserGroup) as UserGroups,
						GROUP_CONCAT(DISTINCT components.Path) as ComponentPath
					FROM auth
					LEFT JOIN adminprivileges ON
						auth.Authority = adminprivileges.Authority
					LEFT JOIN auth_usergroup AS authUserGroup ON
						auth.UserID = authUserGroup.UserID
					LEFT JOIN auth_group AS authGroup ON
						auth.UserGroup = authGroup.UserGroup
					LEFT JOIN components ON
						authGroup.ComponentID = components.IDKey
					WHERE
						auth.Salt = ?
					GROUP BY auth.UserID");
				$r3 = $q3->execute(array($usersalt));

				if(!$r3 || $q3->rowCount() !== 1) {
					throw new Exception('Unable to retrieve user data from database to generate authentication token.');
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
				$mail = new PHPMailer(true);

				// Construct mail
				$mail_generator = new \LotusBase\MailGenerator();
				$mail_generator->set_title('<em>Lotus</em> Base: New user registration');
				$mail_generator->set_header_image('cid:mail_header_image');
				$mail_generator->set_content(array(
					'<h3 style="text-align: center; ">Welcome to <em>Lotus</em> Base</h3>
					<p>Thank you for signing up to join the <em>Lotus</em> Base community. While you may start using the site immediately, <strong>we require new users to validate their new accounts within 24 hours</strong> using the following link:</p>
					<p><strong><a href="https://'.$_SERVER['HTTP_HOST'].'/users/verify.php?email='.urlencode($email).'&amp;key='.urlencode($verificationkey).'" style="color: #4a7298;">https://'.$_SERVER['HTTP_HOST'].'/users/verify.php?email='.urlencode($email).'&amp;key='.urlencode($verificationkey).'</a></strong></p>
					<p>Newly created user accounts that are not validated within 24 hours of creation will be subjected to trash collection and removed from the database, to prevent others from registering with email accounts they do not have access to.</p>
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
				$mail->AddAddress($email, $email);

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

				// All is good, now we can log the user in
				$_SESSION['user_verification_required'] = true;
				session_write_close();
				header("location: ./");
				exit();

			} catch(\PDOException $e) {
				// PDO Exception
				$_SESSION['reg_error'] = array(
					'message' => 'We have encountered an issue with our backend. Should this problem persist, <a href="'.WEB_ROOT.'/issues">file a bug report with us</a>. The error we have encounetered: '.$e->getMessage(),
					'inputs' => $_POST
				);
				session_write_close();
				header("location: ".$_SERVER['PHP_SELF']);
				exit();

			} catch(\phpmailerException $e) {
				// Mail has failed to send
				$_SESSION['reg_error'] = array(
					'message' => 'We have encountered an error sending you a verification email: '.$e->getMessage().' . Please <a href="'.WEB_ROOT.'/meta/contact?key='.$verificationkey.'">contact us</a> with this code: <code>'.$verificationkey.'</code>.',
					'inputs' => $_POST
				);
				session_write_close();
				header("location: ".$_SERVER['PHP_SELF']);
				exit();

			} catch(Exception $e) {
				// General exception
				$_SESSION['reg_error'] = array(
					'message' => $e->getMessage(),
					'inputs' => $_POST
				);
				session_write_close();
				header("location: ".$_SERVER['PHP_SELF']);
				exit();
			}
		}
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>User Registration&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" integrity="sha384-wCtV4+Y0Qc1RNg341xqADYvciqiG4lgd7Jf6Udp0EQ0PoEv83t+MLRtJyaO5vAEh" crossorigin="anonymous">
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/users.min.css" type="text/css" media="screen" />
</head>
<body class="users register theme--white">
	<?php

		// Registration form
		$registration_form = '<div class="align-center">
			<h1>Join the community</h1>
			<span class="byline">Create a new account with us</span>';

		if($error) {
			$registration_form .= '<p class="user-message warning"><span class="icon-attention"></span>'.$error[0].'</p>';
		} else if(isset($_SESSION['reg_error'])) {
			$registration_form .= '<p class="user-message warning"><span class="icon-attention"></span>'.$_SESSION['reg_error']['message'].'</p>';
			unset($_SESSION['reg_error']);
		} else {
			$registration_form .= '<p>Already have an account? <a href="'.WEB_ROOT.'/users/login" title="User login">Sign in here</a>.</p>';
		}

		$registration_form .= '</div>';

		foreach($orgs as $title => $value) {
			$orgs_opts[] = '<option value="'.$value.'" '.(!empty($inputs['organization'] && $inputs['organization'] === $value) ? 'selected' : '').'>'.$title.'</option>';
		}

		$registration_form .= '<div id="registration-form--tabs" class="form--tabs">

			<ul class="tabbed">
				<li><a href="#register__normal" data-custom-smooth-scroll>Registration form</a></li>
				<li><a href="#register__oauth" data-custom-smooth-scroll>'.(isset($_SESSION['oauth_error']) ? call_user_func(function() {
					unset($_SESSION['oauth_error']);
					return '<span class="icon-attention"></span>';
				}) : '').'Register using third party identity providers</a></li>
			</ul>

			<div id="register__normal">

				<form id="registration-form" method="post" action="'.$_SERVER['PHP_SELF'].'">
					<div class="cols">

						<p class="full-width user-message">Complete the form below to open a new user account with us. All fields are necessary unless otherwise stated.</p>

						<label class="col-one" for="firstname">Name</label>
						<div class="col-two">
							<div class="cols flex-wrap__nowrap">
								<input type="text" name="firstname" id="firstname" placeholder="First name" style="margin-right: 1rem; " tabindex="1" value="'.(!empty($inputs['firstname']) ? escapeHTML($inputs['firstname']) : '').'" />
								<input type="text" name="lastname" id="lastname" placeholder="Last name" tabindex="2" value="'.(!empty($inputs['lastname']) ? escapeHTML($inputs['lastname']) : '').'" />
							</div>
						</div>

						<label class="col-one" for="username">Username</label>
						<div class="col-two">
							<input type="text" name="username" id="username" placeholder="Username (min. 2 characters)" tabindex="3" value="'.(!empty($inputs['username']) ? escapeHTML($inputs['username']) : '').'" data-check />
							<span class="input__ajax-indicator icon-ok"></span>
						</div>

						<label class="col-one" for="email">Email</label>
						<div class="col-two">
							<input type="text" name="email" id="email" placeholder="Email address" tabindex="4" value="'.(!empty($inputs['email']) ? escapeHTML($inputs['email']) : '').'" data-check />
							<span class="input__ajax-indicator icon-ok"></span>
						</div>

						<label class="col-one" for="password">Password</label>
						<div class="col-two"><input type="password" name="password" id="password" placeholder="Password" tabindex="5" /></div>

						<label class="col-one" for="organization">Organization (optional)</label>
						<div class="col-two">
							<select name="organization" id="organization">
								'.implode('', $orgs_opts).'
							</select>
						</div>

						<div class="col-one"></div>
						<div class="col-two">
							<label for="mailing-list"><input class="prettify" type="checkbox" id="mailing-list" name="mailinglist" tabindex="7" checked /><span>Subscribe to the <em>Lotus</em> Base newsletter</span></label>
							<label for="consent"><input class="prettify" type="checkbox" id="consent" name="consent" tabindex="8" /><span>I accept the <a href="'.WEB_ROOT.'/meta/legal">terms of service and privacy policy</a>.</span></label>
						</div>

						<label class="col-one">Human?</label>
						<div class="col-two" id="google-recaptcha"></div>

						<input type="hidden" name="CSRF_token" value="'.CSRF_TOKEN.'" />

						<button type="submit" tabindex="8" disabled>Create new account</button>
					</div>
				</form>
			</div>

			<div id="register__oauth">
				<p>You may also use third-party identity providers to create a new account. If we already have your account on file, we will offer the opportunity to intergrate them.</p>
				<ul class="cols justify-content__center align-items__center list--reset">
					<li class="oauth oauth__google"><a href="'.$client->createAuthUrl().'"><img src="'.WEB_ROOT.'/dist/images/users/oauth/google.svg" alt="Register or sign in in with Google" title="Register or sign in with Google" class="oauth__logo" />Register/Sign in with Google</a></li>
					<li class="oauth oauth__linkedin"><a href="https://www.linkedin.com/oauth/v2/authorization?response_type=code&amp;client_id='.LINKEDIN_CLIENT_ID.'&amp;redirect_uri='.urlencode($oauth2_redirect_uri.'/linkedin').'&amp;state='.$oauth2_state.'"><img src="'.WEB_ROOT.'/dist/images/users/oauth/linkedin.svg" alt="Register or sign in in with LinkedIn" title="Register or sign in with LinkedIn" class="oauth__logo" />Register/Sign in with LinkedIn</a></li>
					<li class="oauth oauth__github"><a href="https://github.com/login/oauth/authorize?client_id='.GITHUB_CLIENT_ID.'&redirect_uri='.urlencode($oauth2_redirect_uri.'/github').'&scope=user:email&state='.$oauth2_state.'"><img src="'.WEB_ROOT.'/dist/images/users/oauth/github.svg" alt="Register or sign in in with GitHub" title="Register or sign in in with GitHub" class="oauth__logo" />Register/Sign in with GitHub</a></li>
				</ul>
			</div>

		</div>';

		// Generate header
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content($registration_form);
		$header->set_header_theme('white');
		$header->set_header_background_image(array(
			'image-url' => WEB_ROOT.'/dist/images/team/carb.jpg'
			));
		echo $header->get_header();
	?>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&amp;render=explicit" async defer></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js" integrity="sha384-iViGfLSGR6GiB7RsfWQjsxI2sFHdsBriAK+Ywvt4q8VV14jekjOoElXweWVrLg/m" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/users.min.js"></script>
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
				globalFun.users.registration.validate();
			},
			expiredCallback = function() {
				grecaptcha.reset();
			};

		// Select2
		$(function() {
			$('#organization').select2({
				placeholder: "Select a pre-existing organization, or enter a new one",
				tags: true
			});
		});
	</script>
</body>
</html>