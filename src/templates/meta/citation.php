<?php 
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>Citations&mdash;Meta&mdash;Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'How to cite the use of Lotus Base'
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/meta.min.css" type="text/css" media="screen" />
</head>
<body class="meta citation init-scroll--disabled">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();
	?>

	<?php
		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_titles(array('Info', 'Citing <em>Lotus</em> Base'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>Citing <em>Lotus</em> Base</h2>
		<p>We are glad that you have found <em>Lotus</em> Base useful in your research, be it in the field of legume biology or otherwise. As it is difficult to track use of <em>Lotus</em> Base if only the URL is cited in a manuscript (e.g. "<em>Lotus</em> Base, <a href="https://<?php echo $_SERVER['SERVER_NAME']; ?>">https://<?php echo $_SERVER['SERVER_NAME']; ?></a> [Online; accessed 2016-11-28]"), we encourage users to cite the relevant published manuscripts instead.</p>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="has-group">
			<div role="group" class="has-legend">
				<p class="legend">Citation helper</p>
				<p>Select one or more of the following that is relevant to you:</p>
				<label for="citation__lotus-base"><input id="citation__lotus-base" name="citation[]" type="checkbox" class="prettify citation__filter" value="lotus-base" /><span>Use of <strong>any</strong> <em>Lotus</em> Base resources (e.g. <em>LORE1</em> ordering, Expression Atlas)</span></label>
				<label for="citation__lore1-mutants"><input id="citation__lore1-mutants" name="citation[]" type="checkbox" class="prettify citation__filter" value="lore1-mutants" /><span>Use of <em>LORE1</em> mutants</span></label>
				<label for="citation__lore1-methods"><input id="citation__lore1-methods" name="citation[]" type="checkbox" class="prettify citation__filter" value="lore1-methods" /><span>Generation of <em>LORE1</em> mutant population</span></label>
				<label for="citation__gifu-genome"><input id="citation__gifu-genome" name="citation[]" type="checkbox" class="prettify citation__filter" value="gifu-genome" /><span>Gifu v1.1 genome assembly</span></label>
			</div>
		</form>
		<h3 id="citation__header">Reference list <span class="badge" data-original-count="4">4</span></h3>
		<p>This list will be filtered based on the criteria above. If no criteria is set, all references will be displayed.</p>
		<div id="citation-tabs">
			<div id="citation-tabs__nav" class="cols align-items__flex-end ui-tabs-nav__wrapper">
				<ul class="tabbed">
					<li><a href="#citation__list" data-custom-smooth-scroll>List</a></li>
					<li><a href="#citation__html" data-custom-smooth-scroll>HTML</a></li>
					<li><a href="#citation__bibtex" data-custom-smooth-scroll>BibTex</a></li>
				</ul>
			</div>

			<div id="citation__list">
				<ul></ul>
			</div>

			<div id="citation__html">
				<textarea readonly></textarea>
			</div>

			<div id="citation__bibtex">
				<textarea readonly></textarea>
    		<p class="align-center form__controls"><button type="button" data-action="export" data-mime-type="text/plain" data-file-extension="bib" data-file-name="lotusbase_citation" class="icon-download">Export</button></p>
			</div>
		</div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/1.3.3/FileSaver.min.js" integrity="sha384-VgWGwiEJnh9P379lbU8DxPcfRuFkfLl0uPuL9tolOHtm2tx8Qy8d/KtvovfM0Udh" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/citation.min.js"></script>
</body>
</html>
