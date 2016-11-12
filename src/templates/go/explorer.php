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
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<div class="align-center">
			<h1>GO Explorer</h1>
			<span class="byline">Spatial exploration of <abbr title="Gene Ontology">GO</abbr> terms</span>
		</div>');
		$header->set_header_background_image(WEB_ROOT.'/dist/images/header/go/go01.jpg');
		echo $header->get_header();

		// Breadcrumb
		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_crumbs(array(
			'Gene Ontology' => 'go',
			'Explorer' => 'explorer'
		));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<form action="#" method="get" class="has-group">
			<div role="group" class="cols">
				<label for="go-root" class="col-one">Select a namespace</label>
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

				<div class="separator"><span>or</span></div>

				<label for="go-node" class="col-one">Enter a <abbr title="Gene Ontology">GO</abbr> term</label>
				<div class="col-two">
					<input id="go-node" name="go_node" type="text" value="<?php echo empty($_GET['go_node']) ? 'GO:0008150' : $_GET['go_node']; ?>" disabled />
				</div>
			</div>
		</form>

		<div class="user-message">
			<h3><span class="icon-ok">Helpful tips</span></h3>
			<ul>
				<li>Drag and drop nodes to manually position (i.e. fix) them</li>
				<li>Double click on a node to update the tree</li>
				<li>Press <kbd>Shift</kbd> and double click to unfix the node</li>
				<li>Press <kbd>Alt</kbd> and double click to visit the page containing further details of a GO term</li>
				<li>Right clicking on a node will reveal a context menu</li>
			</ul>
		</div>

		<div class="view__facet">
			<div class="facet">
				<div class="facet__stage">
					<svg id="go-explorer"></svg>
					<ul class="floating-controls position--right">
						<li><a href="#" class="icon-cog icon--no-spacing controls__toggle" title="Toggle controls"></a></li>
						<li><a href="#" id="go-explorer__export-image" class="icon-camera icon--no-spacing" title="Image export options"></a><ul>
							<li><a href="#" data-image-type="jpg" data-source="go-explorer" data-form="go-tree-export" title="Export current view as JPG file" class="image-export jpg-export">JPG (bitmap)</a></li>
							<li><a href="#" data-image-type="png" data-source="go-explorer" data-form="go-tree-export" title="Export current view as PNG file" class="image-export png-export">PNG (bitmap)</a></li>
							<li><a href="#" data-image-type="svg" data-source="go-explorer" data-form="go-tree-export" title="Export current view as SVG file" class="image-export svg-export">SVG (vector)</a></li>
						</ul></li>
					</ul>
					<form action="<?php echo WEB_ROOT; ?>/lib/export/svg.pl" method="post" class="hidden image-export__form" id="go-tree-export">
						<input type="hidden" class="svg-data" name="svg_data" />
						<input type="hidden" class="output-format" name="output_format" />
						<input type="hidden" class="filename-prefix" name="filename_prefix" value="go-tree" />
					</form>
				</div>
				<form class="facet__controls has-group" id="go-explorer__controls" action="#" method="get">
					<div role="group" class="has-legend">
						<p class="legend">Controls</p>
						<div class="cols">
							<button type="button" class="button button--small" id="go-explorer__reset"><span class="icon-eye">Reset view</span></button>
							<button type="button" class="button button--small" id="go-explorer__play-pause" data-state="playing"><span class="icon-pause">Pause</span></button>
						</div>
					</div>

					<div role="group" class="has-legend cols">
						<p class="legend full-width">Force layout</p>
						<p class="full-width">Every force layout is different&mdash;we have picked <a href="https://github.com/d3/d3-3.x-api-reference/blob/master/Force-Layout.md">a set of parameters</a> which suits most GO ancestor tree chart well. If you mess something up&mdash;don't worry: hitting the "reset view" button above will reset the chart to its default layout.</p>

						<label for="force-charge" class="col-one">Charge</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-charge" min="-10000" max="0" step="1" data-tree-function="charge" class="force has-output" />
							<output></output>
						</div>

						<label for="force-linkDistance" class="col-one">Distance</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-linkDistance" min="1" max="100" step="1" data-tree-function="linkDistance" class="force has-output" />
							<output></output>
						</div>

						<label for="force-friction" class="col-one">Friction</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-friction" min="0" max="1" step="0.01" data-tree-function="friction" class="force has-output" />
							<output></output>
						</div>

						<label for="force-gravity" class="col-one">Gravity</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-gravity" min="0" max="1" step="0.01" data-tree-function="gravity" class="force has-output" />
							<output></output>
						</div>

						<label for="force-alpha" class="col-one">Alpha</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-alpha" min="0" max="1" step="0.01" data-tree-function="alpha" class="force has-output" />
							<output></output>
						</div>

						<label for="force-theta" class="col-one">Theta</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-theta" min="0" max="1" step="0.01" data-tree-function="theta" class="force has-output" />
							<output></output>
						</div>

						<div class="separator"></div>

						<label for="force-bound" class="full-width">
							<input type="checkbox" class="prettify" id="force-bound" />
							<span>Confine entire chart within bounds</span>
						</label>
					</div>
				</form>
			</div>
		</div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script type="text/javascript" src="//d3js.org/d3.v3.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/colorbrewer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/d3-tip.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/go-tree.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/go/explorer.min.js"></script>
</body>
</html>