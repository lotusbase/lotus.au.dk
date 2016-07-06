<?php
// Convert to object
$d = $_POST;

// Assign variables
$fname 			= $d['fname'];
$lname 			= $d['lname'];
$email			= $d['email'];
$institution 	= $d['institution'];
$address 		= $d['address'];
$city 			= $d['city'];
$state 			= $d['state'];
$postalcode 	= $d['postalcode'];
$country 		= $d['country'];
$lines 			= escapeHTML($d['lines']);
$comments 		= $d['comments'];
$captcha		= $d['g-recaptcha-response'];

// Validate inputs
if($fname == '') {
	$order_error[] = 'First name is required';
	$flag = true;
}
if($lname == '') {
	$order_error[] = 'Last name is required';
	$flag = true;
}
if($email == '') {
	$order_error[] = 'Email address is required';
	$flag = true;
}
if($institution == '' || $address == '' || $city == '' || $postalcode == '' || $country == '') {
	$order_error[] = 'Postal address is incomplete';
	$flag = true;
}
if($lines == '') {
	$order_error[] = 'No LORE1 lines have been entered';
	$flag = true;
}

// Error catch
if($flag) {
	$error->set_message($order_error);
	$error->execute();
}

// Format input for LORE1 lines such that each value is separated by ", "
$lines_pattern = array(
	'/[\r\n]+/',		// Checks for one or more line breaks
	'/(\w)\s+(\w)/',	// Checks for words separated by one or more spaces
	'/,\s*/',			// Checks for words separated by comma, but with variable spaces
	'/,\s*,/'			// Checks for empty strings (i.e. no words between two commas)
	);
$lines_replace = array(
	',',
	'$1, $2',
	',',
	','
	);
$lines = preg_replace($lines_pattern, $lines_replace, $lines);


// 1. Convert string $lines into array
// 2. Filter array, remove empty strings
// 3. Reset array keys
$lines_array = array_values(array_filter(explode(",", $lines)));
sort($lines_array);

// Declare variables for error reporting
$lines_error_title = '';
$lines_error = array();
$lines_placeholder = str_repeat('?,', count($lines_array)-1).'?';

// Check plant IDs
try {
	// Prepare query
	$q1 = $db->prepare("SELECT DISTINCT PlantID FROM lore1seeds
		WHERE PlantID IN ($lines_placeholder) AND SeedStock = 1 AND Ordering = 1
		ORDER BY PlantID
	");

	// Execute query with array of values
	$q1->execute($lines_array);

	// Fetch results
	if($q1->rowCount() <= 0) {
		$error->set_status(404);
		$error->set_message('All lines that you have entered are invalid or unavailable.');
		$error->execute();
	}
	while($row = $q1->fetch(PDO::FETCH_ASSOC)) {
		$pids[] = $row['PlantID'];
	}
	$diff = array_diff($lines_array, $pids);

	// Are there any differences between user input and actual plant IDs
	if(count($diff) > 0) {
		$error->set_status(404);
		$error->set_message(count($diff)." of the ".pl(count($lines_array), "line", "lines")." that you have entered currently ".pl(count($lines_array), "is", "are")." unavailable. Please make sure you're using the correct format.");
		$error->execute();
	}

} catch(PDOException $e) {
	$errorInfo = $db->errorInfo();
	$error->set_message('MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage());
	$error->execute();
}

// Prepare for database insertion
// Generate salt
$salt = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));

// Try:
// 1. Insert general order information
// 2. Insertion specific line information
try {
	// 1.
	$q2_params = array($fname, $lname, $email, $institution, $address, $city, $state, $postalcode, $country, $comments, $salt);
	$q2 = $db->prepare("INSERT INTO orders_unique (FirstName,LastName,Email,Institution,Address,City,State,PostalCode,Country,Comments,Salt) VALUES(".str_repeat('?,', count($q2_params)-1).'?'.")");
	$q2->execute($q2_params);

	// 2.
	$q3 = $db->prepare("INSERT INTO orders_lines (PlantID,Salt) VALUES(:pid, :salt)");
	foreach($pids as $pid) {
		$q3->bindParam(':pid', $pid);
		$q3->bindParam(':salt', $salt);
		$q3->execute();
	}

	// Send verification email if all is good
	// Load PHP mailer
	$ordered_lines = explode(",", $lines);
	sort($ordered_lines);

	require_once('./vendor/phpmailer/PHPMailerAutoload.php');
	$mail = new PHPMailer(true);

	// Construct mail
	$mail_generator = new \LotusBase\MailGenerator();
	$mail_generator->set_title('<em>Lotus</em> Base: LORE1 order receipt');
	$mail_generator->set_html_link('https://lotus.au.dk/mail');
	$mail_generator->set_header_image('cid:mail_header_image');
	$mail_generator->set_content(array(
		'Hi&nbsp;'.$fname.',
		<br><br>
		Thank you for ordering with us. However, before we can proceed to process your order, we will require you to verify your order by visiting the link below. Your quick verification will help us to expedite the processing of your order.
		<br><br>
		<strong><a href="https://'.$_SERVER['HTTP_HOST'].'/verify.php?email='.urlencode($email).'&amp;key='.urlencode($salt).'" style="color: #4a7298;">https://'.$_SERVER['HTTP_HOST'].'/verify.php?email='.urlencode($email).'&amp;key='.urlencode($salt).'</a></strong>
		<br><br>You will be notified by email when we have processed and shipped your order to the mailing address provided.',
		'*|RULER|*',
		'<h3>Order information</h3>
		<br>
		For your information, you have placed an order on the following '.pl(count($ordered_lines), 'line', 'lines').':
		<ul style="background-color: #ddd; padding: 16px 16px 16px 32px; margin: 0; border: 1px solid #ccc;"><li>'.implode('</li><li>', $ordered_lines).'</li></ul>
		<br>
		Your order has been assigned an automatically generated identification chit (order key):<br /> <strong>'.urlencode($salt).'</strong>',
		'*|RULER|*',
		'<h3>Further information</h3>
		<br>
		Should you require any assistance, or have any enquiries, kindly contact us through the <a href="https://'.$_SERVER['HTTP_HOST'].'/contact.php?key='.urlencode($salt).'" style="color: #4a7298;">contact form</a> on our site. <strong>Do not reply to this email because mails to this account ('.NOREPLY_EMAIL.') will not be directed to any staff.</strong>'
		));

	$mail->IsSMTP();
	$mail->IsHTML(true);
	$mail->Host			= SMTP_MAILSERVER;
	$mail->AddReplyTo($email, $fname.' '.$lname);
	$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
	$mail->CharSet		= "utf-8";
	$mail->Encoding		= "base64";
	$mail->Subject		= "Lotus Base: LORE1 order receipt";
	$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
	$mail->MsgHTML($mail_generator->get_mail());
	$mail->AddAddress($email, $fname." ".$lname);
	$mail->AddEmbeddedImage("data/mail/header.jpg", mail_header_image);
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

	// Mail successfully sent
	$dataReturn->execute();

} catch(PDOException $e) {
	$errorInfo = $db->errorInfo();
	$error->set_message('MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage());
	$error->execute();
} catch(phpmailerException $e) {
	// Mail has failed to send
	$error->set_status(555);
	$error->set_message('We have encountered an error with delivering your email. However, you may click on this link in order to verify your order: <a href="https://lotus.au.dk/verify.php?email='.urlencode($email).'&amp;key='.urlencode($salt).'">verify order</a>.');
	$error->execute();
} 
?>