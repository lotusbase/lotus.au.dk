<?php
	// Load site config
	require_once('../config.php');
	
	// Require authorization
	require_once('auth.php');
?>
<!doctype html>
<html>
<head>
	<title>Settings &mdash; LORE1 Resource Site</title>
	<?php include_once('head.php'); ?>
</head>
<body class="admin settings">
	<?php include_once('header.php'); ?>

	<section class="wrapper">
		<h2><span class="pictogram icon-cog"></span> User Settings</h2>

		<?php if(isset($_SESSION['settings-ok'])) { ?>
		<p class="user-message approved">Settings changed successfully.</p>
		<?php
			unset($_SESSION['settings-ok']);
		} if(isset($_SESSION['settings-err'])) {
			$msg = '<p class="user-message warning">We have experienced some problem with your input: ';
			foreach($_SESSION['settings-err'] as $err) {
					$msg .= $err.", ";
				}
			$msg = substr($msg, 0, -2);
			$msg .= '.</p>';
			echo $msg;
			unset($_SESSION['settings-err']);
		}?>

		<p>You can update your user settings, profile information and password here. Remember to commit the changes so that your new information will be recorded in the database. <strong>Fields can be left blank if you are not updating them.</strong></p>

		<form action="settings-exec.php" method="post" id="settings-form">
			<?php
			$q = $db->prepare("SELECT * FROM auth WHERE UserID = :userid");
			$q->bindParam(':userid', $userID);
			$q->execute();
			$d = $q->fetch(PDO::FETCH_ASSOC);
			$opt = array($d['Notify']);
			?>
			<div class="form-wrapper">
				<fieldset>
					<legend><span class="pictogram icon-bell"></span> Notifications</legend>
					<label for="settings-notification">Receive order notifications? <a data-modal="search-help" title="What are order notifications?" data-modal-content="All site admins are automatically signed up for email notifications when a new order is placed. However, you can opt out of this by turning off order notifications.">?</a></label>
					<select id="settings-notification" name="notify">
						<option value="y"<?php echo ($opt[0]=='1' ? 'selected' : ''); ?>>Yes</option>
						<option value="n"<?php echo ($opt[0]=='0' ? 'selected' : ''); ?>>No</option>
					</select>
				</fieldset>
				<fieldset>
					<legend><span class="pictogram icon-user"></span> Update Profile Information</legend>
					<p>
						<label for="settings-fname">First name</label>
						<input type="text" name="fname" id="settings-fname" placeholder="New first name" autocomplete="off" />
					</p>
					<p>
						<label for="settings-fname">Last name</label>
						<input type="text" name="lname" id="settings-lname" placeholder="New last name" autocomplete="off" />
					</p>
					<p>
						<label for="settings-email">Email address</label>
						<input type="email" name="email" id="settings-email" placeholder="New Email address" autocomplete="off" />
					</p>
				</fieldset>
				<fieldset>
					<legend><span class="pictogram icon-key"></span> Update Authentication Information</legend>
					<p class="para">The system will log you out once you have changed your password, for security reasons. You can then log in with your <em>new password</em>.</p>
					<p>
						<label for="settings-opassword">Old password <a data-modal title="Why do I have to enter my old password?" data-modal-content="&lt;p&gt;This is to ensure that the actual account owner is updating the password, and not another third party who have gained unauthorized access to your account (e.g. by using your terminal when you have not logged off).&lt;/p&gt;&lt;p&gt;If you have forgotten your password, please &lt;a href=&quot;reset.php&quot;&gt;proceed to reset your password&lt;/a&gt;.&lt;/p&gt;">?</a></label>
						<input type="password" name="opassword" id="settings-opassword" placeholder="Old/Current password" autocomplete="off" />
					</p>
					<p>
						<label for="settings-password">New password</label>
						<input type="password" name="password" id="settings-password" placeholder="New password" autocomplete="off" />
					</p>
					<p>
						<label for="settings-cpassword">Retype new password</label>
						<input type="password" name="cpassword" id="settings-cpassword" placeholder="Retype new password" autocomplete="off" />
					</p>
				</fieldset>
			</div>
			<input type="hidden" value="<?php echo $_SESSION['sess_member_id']; ?>" name="userid" />
			<input type="submit" value="Update User Settings" />
		</form>
	</section>

	<?php include_once('footer.php'); ?>
</body>
</html>
