<?php
// Load site config
require_once('../config.php');

if (isset($_SESSION['order_success']) && $_SESSION['order_success']) {
	// Fetch user input
	$user_input = $_SESSION['order_success']['user_input'];
	unset($_SESSION['order_success']);
} else {
	header("location: /");
	exit();
}

?>
<!doctype html>
<html lang="en">
<head>
	<title>Order &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
</head>
<body class="order-success">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<div class="align-center"><h1><span class="icon-mail-alt icon--big icon--no-spacing">You\'ve got mail!</span></h1><p>Please check your inbox at <strong>'.$user_input['Email'].'</strong> to verify your order.</p></div>');
		$header->set_header_background_image(WEB_ROOT.'/dist/images/hero/hero01.jpg');
		echo $header->get_header();
	?>

	<section class="wrapper">
		<h2>Order verification</h2>
		<p>Dear <?php echo escapeHTML($user_input['FirstName']); ?>, thank you for ordering with us. We have successfully received your order and therefore have sent a verification mail to your email address at <strong><?php echo escapeHTML($user_input['Email']); ?></strong>. <strong>Please verify your order as soon as possible</strong> by clicking on the link in the aforementioned email. Your early response will help us to expedite the processing of your order.</p>
		<p>We will process your order as soon as possible after you have verified your order. Should we experience any problems or delays with your order, we will contact you through the email you provided.</p>
		<p class="user-message warning"><strong>Warning:</strong> Orders that are <span style="text-decoration: underline;">not verified in 4 weeks after submission</span> will be cleared from our database by an automated process.</p>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script>
		// Clear out local storage
		if(window.localStorage !== void 0) {
			window.localStorage.clear();
		}
	</script>
</body>
</html>