<?php
	require_once('config.php');

	$error_meta = array(
		400 => array(
			'pageTitle' => 'Bad Request',
			'headerTitle' => 'Malformed Request',
			'message' => '<p>You have submitted a request of a malformed syntax, and we are unable to process it. Are you sure you have followed the correct link? If you are sure this is a mistake, <a href="../issues">open an issue</a>&mdash;we will have a look at it as soon as possible.</p><p>Otherwise, try searching for the content you are looking for:</p>'
			),
		401 => array(
			'pageTitle' => 'Closed Beta',
			'headerTitle' => 'Closed Beta',
			'message' => '<p>You have attempted to access a restricted section of the site with incorrect credentials. This happens when you attempt to access a resource that we have not made publicly available yet. <strong>If you are a member of CARB, please connect to the AU VPN before proceeding.</strong></p><p>If you are sure this is a mistake, <a href="../issues">open an issue</a>&mdash;we will have a look at it as soon as possible. Otherwise, try searching for the content you are looking for:</p>'
			),
		403 => array(
			'pageTitle' => 'Access Denied',
			'headerTitle' => 'Access Denied',
			'message' => '<p>You do not have the sufficient privileges to access this page or directory. If you are sure this is a mistake, <a href="../issues">open an issue</a>&mdash;we will have a look at it as soon as possible. Otherwise, try searching for the content you are looking for:</p>'
			),
		404 => array(
			'pageTitle' => 'Not Found',
			'headerTitle' => 'Page Not Found',
			'message' => '<p>We are unable to find the page you are looking for. Are you sure you have followed the correct link? If you are sure this is a mistake, <a href="../issues">open an issue</a>&mdash;we will have a look at it as soon as possible.</p><p>Otherwise, try searching for the content you are looking for:</p>'
			),
		500 => array(
			'pageTitle' => 'Server Error',
			'headerTitle' => 'Server Error',
			'message' => '<p>We have encountered an internal server error. Should this issue persist, please <a href="../issues">open an issue</a>&mdash;we will have a look at it as soon as possible.</p><p>Otherwise, try searching for the content you are looking for:</p>'
			),
		503 => array(
			'pageTitle' => 'Component under maintenance',
			'headerTitle' => 'Maintenance notice',
			'message' => '<p>We are currently updating this tool. Please check back again later. We expect the service to be up again by 14<sup>th</sup> March, 2016 (Monday).</p>'
			),
		'default' => array(
			'pageTitle' => 'Error',
			'headerTitle' => 'General Error',
			'message' => '<p>We have encountered a general error of unspecified nature. Should this issue persist, please <a href="../issues">open an issue</a>&mdash;we will have a look at it as soon as possible.</p><p>Otherwise, try searching for the content you are looking for:</p>'
			)
		);

	if(isset($_GET['status'])) {
		$status = intval($_GET['status']);
	} else {
		$status = intval($_SERVER['REDIRECT_STATUS']);
	}

	if(isset($status) && !empty($status) && array_key_exists($status, $error_meta)) {
		if(!array_key_exists($status, $error_meta)) {
			$status = 'default';
		}
	} else {
		header('Location: /'.WEB_ROOT);
	}
?>
<!doctype html>
<html lang="en">
<head>
	<title><?php echo $error_meta[$status]['pageTitle']; ?>&mdash;Lotus Base</title>
	<?php include('head.php'); ?>
</head>
<body class="error">
	<?php
		$header = new \LotusBase\PageHeader();
		$search_form2 = new \LotusBase\SiteSearchForm();

		$header->set_header_content('<h1 class="align-center"><span class="pictogram icon--big icon-attention icon--no-spacing">'.$error_meta[$status]['headerTitle'].'</span></h1>'.(isset($_GET['message']) ? '<p class="user-message warning align-center">'.$_GET['message'].'</p>': '').$error_meta[$status]['message'].$search_form2->get_form());
		echo $header->get_header();
	?>


	<?php include('footer.php'); ?>
</body>
</html>