<?php

	require_once('../config.php');

?>
<!doctype html>
<html lang="en">
<head>
	<title>LORE1 &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'LORE1 population and usage statistics'
			));
		echo $document_header->get_document_header();
	?>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/order.min.css" type="text/css" media="screen">
</head>
<body class="order">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<div class="align-center">
			<h1><em>LORE1</em> Overview</h1>
			<span class="byline">Population and usage statistics of the <em>LORE1</em> mutant population</span>
		</div>');
		$header->set_header_background_image(WEB_ROOT.'/dist/images/header/lore1/lore1_01.jpg');
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title(array('<em>LORE1</em>', 'Overview'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>Population statistics</h2>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/lore1.min.js"></script>
</body>
</html>
