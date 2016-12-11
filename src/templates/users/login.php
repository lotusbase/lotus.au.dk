<?php
	namespace LotusBase;
	use \PDO;

	class AuthException	extends \Exception {};
	class Exception		extends \Exception {};

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

	// Unset user integration data
	unset($_SESSION['user_integration']);

	// Redirect to dashboard if user is already logged in
	if(is_logged_in()) {
		$userData = is_logged_in();
		if(isset($userData['Authority']) && $userData['Authority'] > 3) {
			header('location: ../users/');
		} else {
			header('location: ../admin/');
		}
	}

	// Check if user has failed too many logins
	$captcha = false;
	try {

		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		$q0 = $db->prepare('SELECT COUNT(UserIP) AS failed_attempts FROM login_attempts WHERE UserIP = ? AND `Timestamp` > (NOW() - INTERVAL 24 HOUR)');
		$q0->execute(array(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']));
		$r0 = $q0->fetch(PDO::FETCH_ASSOC);
		if($q0->rowCount() && $r0['failed_attempts'] > 2) {
			$captcha = true;
		}

	} catch(PDOException $e) {
		if(!isset($_SESSION['user_login_error'])) {
			$_SESSION['user_login_error'] = $e->getMessage();
			session_write_close();
			header("location: login.php");
			exit();
		}
	}

	// Check login attempt
	if(isset($_POST['login']) && isset($_POST['password'])) {
		
		try {

			// Check login and password
			if(empty($_POST['login'])) {
				throw new \LotusBase\AuthException('Username or email is missing.');
			}
			if(empty($_POST['password'])) {
				throw new \LotusBase\AuthException('Password is missing.');
			}

			// Check if grecaptcha was required
			if($captcha) {
				if(!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
					throw new Exception('You have not completed the captcha.');
				} else {
					$resp = $recaptcha->verify($_POST['g-recaptcha-response'], get_ip());
					if(!$resp->isSuccess()) {
						throw new Exception('You have failed to captcha check.');
					}
				}
			}

			$login = $_POST['login'];
			$password = $_POST['password'];

			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			$q1 = $db->prepare("SELECT
					auth.*,
					adminprivileges.*,
					GROUP_CONCAT(userGroup.ComponentPath) as ComponentPath
				FROM auth
				LEFT JOIN adminprivileges ON
					auth.Authority = adminprivileges.Authority 
				LEFT JOIN auth_group AS userGroup ON
					auth.UserGroup = userGroup.UserGroup
				WHERE
					auth.Username = :username OR
					auth.Email = :email
				GROUP BY auth.UserID
				");
			$q1->bindParam(':username', $login);
			$q1->bindParam(':email', $login);
			$r1 = $q1->execute();

			if($r1 && $q1->rowCount() > 0) {
				$row = $q1->fetch(PDO::FETCH_ASSOC);

				// Check password
				if (password_verify($password, $row['Password'])) {

					// Check account status, and allow user to log in if
					// - user is verified, or
					// - user has not verified, but the verification key is issued less than 24 hours ago 
					if(
						intval($row['Verified']) === 1 ||
						(intval($row['Verified']) === 0 && strtotime($row['VerificationKeyTimestamp']) >= strtotime('-24 hours'))
						) {
						session_regenerate_id();

						// Perform second query
						// 1. Remove password reset token (user has remembered password)
						// 2. Set session key
						$q2 = $db->prepare("UPDATE auth SET ResetKey = NULL WHERE Username = :username OR Email = :email");
						$q2->bindParam(':username', $login);
						$q2->bindParam(':email', $login);
						$q2->execute();

						// Remove failed login attempts by IP
						$db_admin = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
						$db_admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						$db_admin->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
						$q3 = $db_admin->prepare("DELETE FROM login_attempts WHERE UserIP = ?");
						$q3->execute(array(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']));

						// Explode access
						$componentPath = explode(',', $row['ComponentPath']);
						$row['ComponentPath'] = $componentPath;

						// Create JWT token and store it on the client-side
						$jwt = new \LotusBase\Users\AuthToken();
						$jwt->setUserData($row);
						$jwt->setValidDuration(isset($_POST['remember_login']) ? 60*60*24*7 : MAX_USER_TOKEN_LIFESPAN);
						$jwt->setCookie();
						
						if (intval($row['Verified']) === 1) {
							// Clear verification reminder if user is already verified
							if(isset($_SESSION['user_privilege_error'])) {
								unset($_SESSION['user_privilege_error']);
							}
						} else {
							// Generate reminder for users that have not verified
							$_SESSION['user_verification_required'] = true;
						}

						// Get redirection url if any
						if(isset($_POST['redir']) && !empty($_POST['redir'])) {
							header("location: ".urldecode($_POST['redir']));
						} else {
							if($row['Authority'] <= 3) {
								header("location: ../admin");
							} else {
								header("location: ../");
							}
						}
						exit();

					} elseif(intval($row['Verified']) === 0 && strtotime($row['VerificationKeyTimestamp']) < strtotime('-24 hours')) {
						throw new Exception('Account not yet verified and activation period of 24 hours have expired. Please check your email for verification link to continue using your account.');
					}
				} else {
					// Invalid password
					// Throw exception
					throw new Exception('Username and password combination is invalid.');
				}
			} else {
				// No user found
				// Throw exception
				throw new Exception('Username and password combination is invalid.');
			}

		} catch(PDOException $e) {
			$_SESSION['user_login_error'] = $e->getMessage();
			session_write_close();
			header("location: login.php");
			exit();
		} catch(Exception $e) {
			// Log into database indicating incorrect login attempts
			$q4 = $db->prepare('INSERT INTO login_attempts (UserLogin, UserIP) VALUES (?, ?)');
			$q4->execute(array(
				$_POST['login'],
				isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']
				));

			$_SESSION['user_login_error'] = $e->getMessage();
			session_write_close();
			header("location: login.php");
			exit();
		}
	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>User Login&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/users.min.css" type="text/css" media="screen" />
</head>
<body class="users login theme--white init-scroll--disabled">
	<?php

		// Login form
		$login_form = '<div class="align-center">
			<h1>Sign in</h1>
			<span class="byline">Not a user yet? <a href="'.WEB_ROOT.'/users/register" title="Register new user">Register a new account here</a>.</span>
		';

		if(isset($_SESSION['user_login_error'])) {
			$login_form .= '<p class="user-message warning">'.$_SESSION['user_login_error'].(isset($_SESSION['oauth_error']) && $_SESSION['oauth_error'] === true ? '<br />If this error persists, please sign in using the regular login form.' : '').'</p>';
			unset($_SESSION['user_login_error']);
//		} else if(isset($_SESSION['user_logged_out'])) {
//			$login_form .= '<p><span class="icon-ok"></span>You have successfully logged out.</p>';
//			unset($_SESSION['user_logged_out']);
		} else if(isset($_SESSION['user_pass_changed'])) {
			$login_form .= '<p><span class="icon-ok"></span>You have successfully changed your password. Please log in with your new password.</p>';
			unset($_SESSION['user_pass_changed']);
		} else {
			$login_form .= '<p>Log in to your <em>Lotus</em> Base account for more convenient access to various tools.</p>';
		}

		$login_form .= '</div>
		<div id="login-form--tabs" class="form--tabs">

			<ul class="tabbed">
				<li><a href="#login__normal" data-custom-smooth-scroll>Regular login</a></li>
				<li><a href="#login__oauth" data-custom-smooth-scroll>'.(isset($_SESSION['oauth_error']) ? call_user_func(function() {
					unset($_SESSION['oauth_error']);
					return '<span class="icon-attention"></span>';
				}) : '').'Third party identity providers</a></li>
			</ul>

			<div id="login__normal">
				<form id="login-form login__normal" method="post" action="#">
					<div class="cols">
						<label class="col-one" for="login">Username / email</label>
						<input class="col-two" type="text" name="login" id="login" placeholder="Username or email" autofocus tabindex="1" />

						<label class="col-one" for="password">Password</label>
						<div class="col-two">
							<input type="password" name="password" id="password" placeholder="Password" tabindex="2" />
							<a href="./reset" title="Reset password">Forgot password?</a>
						</div>';

		if($captcha) {
			$login_form .= '<label class="col-one">Human?</label><div class="col-two" id="google-recaptcha"></div>';
		}

		$login_form .= '<label class="col-one" for="remember-login"></label>
						<div class="col-two">
							<label for="remember-login"><input type="checkbox" id="remember-login" name="remember_login" tabindex="'.($captcha ? 4 : 3).'" /><span>Remember me for one week</span></label>
						</div>

						<button type="submit" tabindex="3">Login</button>

						<input type="hidden" name="redir" value="'.(isset($_GET['redir']) ? htmlspecialchars($_GET['redir']) : '').'">
					</div>
				</form>
			</div>

			<div id="login__oauth">
				<p>You may also use third-party identity providers to authenticate your login. If you do not have an account with us, you will still be required to create an account after authentication.</p>
				<ul class="cols justify-content__center align-items__center list--reset">
					<li class="oauth oauth__google"><a href="'.$client->createAuthUrl().'"><img src="'.WEB_ROOT.'/dist/images/users/oauth/google.svg" alt="Sign in with Google" title="Sign in with Google" class="oauth__logo" />Sign in with Google</a></li>
					<li class="oauth oauth__linkedin"><a href="https://www.linkedin.com/oauth/v2/authorization?response_type=code&amp;client_id='.LINKEDIN_CLIENT_ID.'&amp;redirect_uri='.urlencode($oauth2_redirect_uri.'/linkedin').'&amp;state='.$oauth2_state.'"><img src="'.WEB_ROOT.'/dist/images/users/oauth/linkedin.svg" alt="Sign in with LinkedIn" title="Sign in with LinkedIn" class="oauth__logo" />Sign in with LinkedIn</a></li>
					<li class="oauth oauth__github"><a href="https://github.com/login/oauth/authorize?client_id='.GITHUB_CLIENT_ID.'&redirect_uri='.urlencode($oauth2_redirect_uri.'/github').'&scope=user:email&state='.$oauth2_state.'"><img src="'.WEB_ROOT.'/dist/images/users/oauth/github.svg" alt="Sign in with GitHub" title="Sign in with GitHub" class="oauth__logo" />Sign in with GitHub</a></li>
				</ul>
			</div>
		</div>';

		// Generate header
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_theme('white');
		$header->set_header_content($login_form);
		echo $header->get_header();
	?>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/users.min.js"></script>
	<?php if($captcha) { ?>
		<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&amp;render=explicit" async defer></script>
		<script>
			// Google ReCaptcha
			var onloadCallback = function() {
					grecaptcha.render('google-recaptcha', {
						'sitekey' : '6Ld3VQ8TAAAAAO7VZkD4zFAvqUxUuox5VN7ztwM5',
						'callback': verifyCallback,
						'expired-callback': expiredCallback,
						'tabindex': 3
					});
				},
				verifyCallback = function(response) {
					globalFun.users.registration.validate();
				},
				expiredCallback = function() {
					grecaptcha.reset();
				};
		</script>
	<?php } ?>
</body>
</html>