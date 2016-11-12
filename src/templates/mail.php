<?php

	// Important stuff
	require_once('config.php');

	// If page is access directly without parameters
	if(!$_GET) {
		header("location: /");
		exit();
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>HTML Email Viewer &mdash; Lotus Base</title>
	<?php include('head.php'); ?>
</head>
<body class="mail">

<?php $header = new \LotusBase\Component\PageHeader(); echo $header->get_header(); ?>

<section class="wrapper">
	<h2>HTML Email Viewer</h2>
	<p>You have chosen to view the HTML-formatted verification email we have sent you in a web-browser. You may use the link displayed in the email below to verify your order, too.</p>
	<?php
		if(isset($_GET['key']) && isset($_GET['email'])) {
			$salt = $_GET['key'];
			$email = $_GET['email'];	
		}

		// Verify that order exists
		try {
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			// Prepare and execute statement
			$q = $db->prepare("SELECT
					ord.FirstName AS FirstName,
					ord.Email AS Email,
					ord.Salt AS Salt,
					GROUP_CONCAT(lin.PlantID) AS PlantID
				FROM orders_unique AS ord
				LEFT JOIN orders_lines AS lin ON ord.Salt = lin.Salt
				WHERE
					ord.Salt = :salt AND
					ord.Email = :email
				GROUP BY ord.Salt");
			$q->bindParam(':salt', $salt);
			$q->bindParam(':email', $email);
			$q->execute();


			// Fetch
			if($q->rowCount() > 0) {
				$row = $q->fetch(PDO::FETCH_ASSOC);
				$lines = explode(',', $row['PlantID']);
				echo '
					<div class="mail-table"><table style="background-color: #eee; line-height: 21px; font-size: 14px;" cellspacing="0" cellpadding="0" class="table--reset">
		<tr>
			<td style="padding: 24px;" width="48"><a href="https://lotus.au.dk/"><img src="./data/mail/branding.gif" alt="LORE1 Logo"></a></td>
			<td style="padding: 14px;"><h1 style="color: #3e4d59; font-family: Arial, Helvetica, sans-serif; font-size: 28px; line-height: 36px; margin: 0; text-align: center;"><em>Lotus</em> Base<br /><span style="font-size: 18px; line-height: 20px;">LORE1 Line Order Verification</span></h1></td>
		</tr>
		<tr>
			<td colspan="2" style="background-color: #aaa;" height="1"></td>
		</tr>
		<tr>
			<td colspan="2" style="background-color: #fff;" height="1"></td>
		</tr>
		<tr>
			<td colspan="2" style="background-color: transparent;" height="10"></td>
		</tr>
		<tr>
			<td style="padding: 14px;" colspan="2">
				<strong>Dear '.$row['FirstName'].',</strong>
				<br ><br />
				Thank you for ordering with us. Before we can proceed to process your order, we will require you to verify your order by visiting the link below. Your quick verification will help us to expedite the processing of your order.
				<br /><br />
				<strong><a href="https://lotus.au.dk/verify.php?email='.$row['Email'].'&amp;key='.$row['Salt'].'" style="color: #4a7298;">https://lotus.au.dk/verify.php?email='.$row['Email'].'&amp;key='.$row['Salt'].'</a></strong>
				<br /><br />
				You will be <strong>notified by email</strong> when we have processed and shipped your order to the mailing address provided.
				<br /><br />
				For your information, you have placed an order on the following lines:
				<ul style="background-color: #fff; border: 1px solid #aaa; margin: 0; padding: 8px 8px 8px 32px;">';
				foreach($lines as $line) {
					echo '<li>'.$line.'</li>';
				}
				echo '
				</ul>
				<br /><br />
				Your order has been assigned an automatically generated identification chit (order key):<br /> <strong>'.$row['Salt'].'</strong>
			</td>
		</tr>
		<tr>
			<td style="padding: 14px;" colspan="2">Should you require any assistance, or have any enquiries, kindly contact us through the <a href="/contact.php?key='.$row['Salt'].'" style="color: #4a7298;">contact form</a> on our site. <strong>Do not reply to this email because mails to this account will not be directed to any staff.</strong><br /><br /><br />Yours sincerely,<br /><em>Lotus</em> Base &amp; LORE1 Project Team<br />Centre for Carbohydrate Recognition and Signalling<br />Aarhus University<br />Gustav Wieds Vej 10<br />DK-8000 Aarhus C
		</td>
		</tr>
		<tr>
			<td colspan="2" style="background-color: #aaa;" height="1"></td>
		</tr>
		<tr>
			<td colspan="2" style="background-color: #fff;" height="1"></td>
		</tr>
		<tr>
			<td colspan="2" style="background-color: transparent;" height="10"></td>
		</tr>
		<tr>
			<td style="padding: 14px;" colspan="2">If you received this email but has not placed an order with us (or have no idea what this email is about), do not worry. It is most likely that a customer has entered an email address incorrectly. Please ignore this email.</td>
		</tr>
	</table></div>
				';
			} else {
				echo '
	<h2>Houston, we have a problem!</h2>
	<p>Unfortunately we are unable to locate the order with the information that you have provided. Please ensure you\'ve copied the right URL, and try again.</p>';
			}

		} catch(PDOException $e) {
			$errorInfo = $db->errorInfo();
			echo json_encode(
				array(
					'error' => true,
					'errorCode' => 100,
					'message' => 'MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage()
				)
			);
			exit();
		}
	?>
</section>


<?php include('footer.php'); ?>

</body>
</html>