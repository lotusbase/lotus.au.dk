<?php
	require_once('../config.php');

	$error = false;
	$searched = false;

?>
<!doctype html>
<html lang="en">
<head>
	<title>GO Enrichment &mdash; Tools &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/tools.min.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/go.min.css" type="text/css" media="screen" />
</head>
<body class="tools go explorer <?php echo (!$error && $searched) ? 'results' : ''; ?> init-scroll--disabled">

	<?php
		$header = new \LotusBase\PageHeader();
		echo $header->get_header();

		// Breadcrumb
		echo get_breadcrumbs(array('custom_breadcrumb' => array(
			'Gene Ontology' => WEB_ROOT.'/go',
			'Explorer' => WEB_ROOT.'/go/explorer'
		)));
	?>

	<section class="wrapper">
		<h2>GO Explorer</h2>
		<span class="byline">Spatial exploration of <abbr title="Gene Ontology">GO</abbr> terms</span>

		<form action="#" method="get" class="has-group">
			<div role="group" class="cols">
				<label for="go-root" class="col-one">Namespace</label>
				<div class="col-two">
					<select id="go-root" name="namespace" disabled>
					<?php
						$go_namespace = array(
							'GO:0003674' => 'Molecular function',
							'GO:0008150' => 'Biological process',
							'GO:0005575' => 'Cellular component'
						);
						asort($go_namespace);
						foreach($go_namespace as $gn => $gn_desc) {
							echo '<option value="'.$gn.'" '.($gn === 'GO:0008150' ? 'selected': '').'>'.$gn_desc.' ('.$gn.')</option>';
						}
					?>
					</select>
				</div>
			</div>
		</form>

		<div class="d3-chart"><svg id="go-explorer"></svg></div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script type="text/javascript" src="//d3js.org/d3.v3.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/colorbrewer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/d3-tip.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/go-tree.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/go/explorer.min.js"></script>
</body>
</html>