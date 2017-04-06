<?php

	// Load configuration
	require_once('../config.php');

	// Population statistics query
	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		
	} catch(PDOException $e) {

	}

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
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/lore1.min.css" type="text/css" media="screen">
</head>
<body class="lore1 stats">
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
		<form id="stats-toggle" class="form--reset lore1__stats__form" action="<?php echo $_SERVER['PHP_SELF']?>">
			<div class="align-center">
				<label for="stats-toggle__subset">Insertion type</label>
				<span class="subset subset--genic">Genic</span><input type="checkbox" class="prettify" id="stats-toggle__subset" /><span class="subset subset--exonic inactive">Exonic</span>
			</div>
		</form>
		<p>Population statistics for the entire <em>LORE1</em> mutant collection that spans 20 individual batches. Numbers are based on unique insertions (redundant insertions are collapsed by chromosome, position, and orientation).</p>
		<div class="cols lore1__stats lore1__stats--total">
			<div class="align-center metric__insertions">
				<div class="lore1__stats--total">
					<div class="count" data-target-value="707573">0</div>
					total insertions
				</div>
				<svg class="lore1__pie" id="lore1__pie__insertions"></svg>
				<div class="lore1__stats--subset">
					<div class="count" data-target-value="0">0</div>
					<span class="subset-text">genic</span> insertions
				</div>
			</div>
			<div class="align-center metric__lines">
				<div class="lore1__stats--total">
					<div class="count" data-target-value="135716">0</div>
					total lines
				</div>
				<svg class="lore1__pie" id="lore1__pie__lines"></svg>
				<div class="lore1__stats--subset">
					<div class="count" data-target-value="0">0</div>
					lines with ≥1 <span class="subset-text">genic</span> insertion
				</div>
			</div>
			<div class="align-center metric__genes">
				<div class="lore1__stats--total">
					<div class="count" data-target-value="87230">0</div>
					total genes
				</div>
				<svg class="lore1__pie" id="lore1__pie__genes"></svg>
				<div class="lore1__stats--subset">
					<div class="count" data-target-value="0">0</div>
					genes with ≥1 <span class="subset-text">genic</span> insertion
				</div>
			</div>
		</div>
	</section>

	<section class="wrapper">
		<h2>Usage statistics</h2>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3-tip/0.6.7/d3-tip.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/lore1.min.js"></script>
</body>
</html>
