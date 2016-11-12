<?php 
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>About&mdash;Meta&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/meta.min.css" type="text/css" media="screen" />
</head>
<body class="meta about">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->add_header_class('alt');
		$header->set_header_content('<div class="align-center"><h1>'.file_get_contents(DOC_ROOT."/dist/images/branding/logo.svg").'About <em>Lotus</em> Base</h1></div>');
		echo $header->get_header();
	?>

	<?php
		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_titles(array('Info', 'About <em>Lotus</em> Base'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<p class="user-message align-center">We are still crafting a description of our project. Maybe you can <a href="./team">checkout our team</a>?</p>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>
