<?php
	require_once('../config.php');

	try {
		if(!empty($_GET) && !empty($_GET['id'])) {
			$id = escapeHTML($_GET['id']);
			if(!preg_match('/^(GO:)?\d+$/', $id)) {
				// If ID fails pattern check
				$_SESSION['view_error'] = 'Invalid gene ontology identifier detected. Please ensure that your gene ontology identifier follows the format <code>[GO:]\d+</code>, where the "GO:" suffix is optional, followed by one or more digits.';
				throw new Exception;
			} else if(!preg_match('/^GO:(.*)$/', $id)) {
				// If the format is not correct b
				$id = 'GO:'.str_pad($id, 7, '0', STR_PAD_LEFT);
				header('Location:'.WEB_ROOT.'/view/go/'.$id);
				exit();
			}

			// Check if GO term exists
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			$q1 = $db->prepare("SELECT
					go.GO_ID AS Term,
					go.Namespace AS Namespace,
					go.Definition AS Definition,
					go.ExtraData AS ExtraData,
					go.SubtermOf AS SubtermOf,
					go.Relationships AS Relationships,
					go.URL AS URL,
					go.Name AS Name
				FROM gene_ontology AS go
				WHERE go.GO_ID = ?
				GROUP BY go.GO_ID");
			$q1->execute(array($id));

			if(!$q1->rowCount()) {
				$_SESSION['view_error'] = 'GO term does not exist. You might want to consider searching for it on alternative online databases.';
				throw new Exception;
			}

			$go_data = $q1->fetch(PDO::FETCH_ASSOC);
			$go_namespace = array(
				'p' => 'Biological process',
				'f' => 'Molecular function',
				'c' => 'Cellular component'
				);

		} else {
			// If ID is not available
			throw new Exception;
		}
	} catch (Exception $e) {
		header('Location:'.WEB_ROOT.'/view');
		exit();
	}
	
?>
<!doctype html>
<html lang="en">
<head>
	<title>Gene Ontology &mdash; View &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Consolidated GO view: '.$id.' ('.$go_namespace[$go_data['Namespace']].'): '.$go_data['Name']
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/view.min.css" type="text/css" media="screen" />
</head>
<body class="view go init-scroll--disabled">

	<?php

		// Page header
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		// Breadcrumb
		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_crumbs(array(
			'View' => 'view',
			'Gene Ontology' => 'go',
			$id => $id
		));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<?php try { ?>
		<h2><?php echo $id; ?></h2>
		<div class="align-center"><?php
			// Generate dropdown
			$go_links_handler = new \LotusBase\Component\GODropdown();
			$go_links_handler->set_title('View this <abbr title="Gene Ontology">GO</abbr> term in other databases');
			$go_links_handler->add_html_attributes(array(
				'class' => array('button--small'),
				'role' => 'secondary'
				));
			$go_links_handler->set_go_term($id);
			echo $go_links_handler->get_html();
		?>
		</div>

		<div id="view__card" class="view__facet">
			<h3>Overview</h3>
			<table class="table--dense">
				<thead>
					<tr>
						<th scope="col">Field</th>
						<th scope="col">Value</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th scope="row">Namespace</th>
						<td><?php echo $go_namespace[$go_data['Namespace']]; ?></td>
					</tr>
					<tr>
						<th scope="row">Short description</th>
						<td><?php echo $go_data['Name']; ?></td>
					</tr>
					<tr>
						<th scope="row">Full defintion</th>
						<td><?php echo $go_data['Definition']; ?></td>
					</tr>
					<tr>
						<th scope="row">Subterm of</th>
						<td><?php
							if($go_data['SubtermOf']) {
								echo '<ul class="list--floated">';
								foreach(json_decode($go_data['SubtermOf'], true) as $go_parent) {
									echo '<li><a href="'.WEB_ROOT.'/view/go/'.$go_parent.'" class="link--reset">'.$go_parent.'</a></li>';
								}
								echo '</ul>';
							} else {
								echo 'Not a subterm/child of another GO identifier.';
							}
						?></td>
					</tr>
				</tbody>
			</table>
			<?php
				$q2 = $db->prepare("SELECT
					anno.Gene AS Transcript,
					anno.Annotation AS Description,
					anno.LjAnnotation AS LotusName,
					GROUP_CONCAT(DISTINCT ip_go2.GO_ID ORDER BY ip_go2.GO_ID ASC) AS GOTerm
				FROM annotations AS anno
				LEFT JOIN domain_predictions AS dompred ON (
					anno.Gene = dompred.Transcript
				)
				LEFT JOIN interpro_go_mapping AS ip_go ON (
					dompred.InterProID = ip_go.InterPro_ID
				)
				LEFT JOIN gene_ontology AS go ON (
					ip_go.GO_ID = go.GO_ID
				)
				LEFT JOIN interpro_go_mapping AS ip_go2 ON (
					dompred.InterProID = ip_go2.InterPro_ID
				)
				WHERE go.GO_ID = ?
				GROUP BY anno.Gene");
				$q2->execute(array($id));
			?>
		</div>

		<div id="view__relationship" class="view__facet">
			<h3>Relationships</h3>
			<p>The relationship of <strong><?php echo $id; ?></strong> with other <abbr title="Gene Ontology">GO</abbr> terms.</p>
			<?php
				$rels = json_decode($go_data['Relationships'], true);

				// Check if all sub-arrays are empty
				$empty = true;
				foreach($rels as $rel_type => $rel_items) {
					if(count($rel_items)) {
						$empty = false;
						break;
					}
				}

				if($empty) {
					echo '<p class="user-message reminder">This <abbr title="Gene Ontology">GO</abbr> term does not have any relationships with other terms.</p>';
				} else { ?>
					<table class="table--dense">
						<thead>
							<tr>
								<th scope="col">Relationship type</th>
								<th scope="col"><abbr title="Gene Ontology">GO</abbr> terms</th>
							</tr>
						</thead>
						<tbody><?php foreach($rels as $rel_type => $rel_items) { ?>
							<tr>
								<th scope="row"><?php echo ucfirst(str_replace('_', ' ', $rel_type)); ?></th>
								<td><?php
								if(count($rel_items)) {
									echo '<ul class="list--floated">';
									asort($rel_items);
									foreach($rel_items as $ri) {
										echo '<li><a href="'.WEB_ROOT.'/view/go/'.$ri.'" class="link--reset">'.$ri.'</a></li>';
									}
									echo '</ul>';
								} else {
									echo 'n.a.';
								} ?></td>
							</tr>
						<?php } ?></tbody>
					</table>
				<?php }
			?>
		</div>

		<div id="view__tree" class="view__facet">
			<h3>Ancestor tree</h3>
			<p>A force layout showing the ancestor tree for <strong><?php echo $id; ?></strong>, and its immediate children. If you wish to explore the tree dynamically, please use the <a href="<?php echo WEB_ROOT; ?>/go/explorer" title="GO Explorer">GO Explorer</a>.</p>
			<ul>
				<li>Drag and drop nodes to manually position (i.e. fix) them</li>
				<li>Double click on a node to unfix the node</li>
				<li>Press <kbd>Alt</kbd> and double click to visit the page containing further details of a GO term</li>
				<li>Right clicking on a node will reveal a context menu</li>
			</ul>
			<div class="facet controls--visible">
				<div class="facet__stage">
					<svg id="go-ancestor" data-go="<?php echo $id; ?>"></svg>
					<ul class="floating-controls position--right">
						<li><a href="#" class="icon-cog icon--no-spacing controls__toggle" title="Toggle controls"></a></li>
						<li><a href="#" id="go-ancestor__export-image" class="icon-camera icon--no-spacing" title="Image export options"></a><ul>
							<li><a href="#" data-image-type="jpg" data-source="go-ancestor" data-form="go-tree-export" title="Export current view as JPG file" class="image-export jpg-export">JPG (bitmap)</a></li>
							<li><a href="#" data-image-type="png" data-source="go-ancestor" data-form="go-tree-export" title="Export current view as PNG file" class="image-export png-export">PNG (bitmap)</a></li>
							<li><a href="#" data-image-type="svg" data-source="go-ancestor" data-form="go-tree-export" title="Export current view as SVG file" class="image-export svg-export">SVG (vector)</a></li>
							<!--<li><a href="#" data-image-type="svg" data-source="go-ancestor" data-form="go-tree-export" title="Export current view as SVG file" class="image-export svg-export">SVG (vector)</a></li>-->
						</ul></li>
					</ul>
					<form action="<?php echo WEB_ROOT; ?>/lib/export/svg.pl" method="post" class="hidden image-export__form" id="go-tree-export">
						<input type="hidden" class="svg-data" name="svg_data" />
						<input type="hidden" class="output-format" name="output_format" />
						<input type="hidden" class="filename-prefix" name="filename_prefix" value="go-tree" />
					</form>
				</div>
				<form class="facet__controls has-group" id="go-ancestor__controls" action="#" method="get">
					<div role="group" class="has-legend">
						<p class="legend">Controls</p>
						<div class="cols">
							<button type="button" class="button button--small" id="go-ancestor__reset"><span class="icon-eye">Reset view</span></button>
							<button type="button" class="button button--small" id="go-ancestor__play-pause" data-state="playing"><span class="icon-pause">Pause</span></button>
						</div>
					</div>

					<div role="group" class="has-legend cols">
						<p class="legend full-width">Force layout</p>
						<p class="full-width">Every force layout is different&mdash;we have picked <a href="https://github.com/d3/d3-3.x-api-reference/blob/master/Force-Layout.md">a set of parameters</a> which suits most GO ancestor tree chart well. If you mess something up&mdash;don't worry: hitting the "reset view" button above will reset the chart to its default layout.</p>
						<label for="force-charge" class="col-one">Charge</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-charge" name="force-charge" min="-10000" max="0" step="1" data-tree-function="charge" class="force has-output" />
							<output></output>
						</div>

						<label for="force-linkDistance" class="col-one">Distance</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-linkDistance" name="force-linkDistance" min="1" max="100" step="1" data-tree-function="linkDistance" class="force has-output" />
							<output></output>
						</div>

						<label for="force-friction" class="col-one">Friction</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-friction" name="force-friction" min="0" max="1" step="0.01" data-tree-function="friction" class="force has-output" />
							<output></output>
						</div>

						<label for="force-gravity" class="col-one">Gravity</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-gravity" name="force-gravity" min="0" max="1" step="0.01" data-tree-function="gravity" class="force has-output" />
							<output></output>
						</div>

						<label for="force-alpha" class="col-one">Alpha</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-alpha" name="force-alpha" min="0" max="1" step="0.01" data-tree-function="alpha" class="force has-output" />
							<output></output>
						</div>

						<label for="force-theta" class="col-one">Theta</label>
						<div class="col-two cols flex-wrap__nowrap">
							<input type="range" id="force-theta" name="force-theta" min="0" max="1" step="0.01" data-tree-function="theta" class="force has-output" />
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

		<?php
			if($go_data['ExtraData'] || $go_data['URL']) {
				$extra_data = json_decode($go_data['ExtraData'], true);
				if(count($extra_data) || $go_data['URL']) {
				?>
				<div id="view__extra-data" class="view__facet">
					<h3>Additional data</h3>
					<p>This table contains additional metadata associated with the <abbr title="Gene Ontology">GO</abbr> entry's definition field.</p>
					<table class="table--dense">
						<thead>
							<tr>
								<th scope="col">Field</th>
								<th scope="col">Value</th>
							</tr>
						</thead>
						<tbody><?php
							foreach($extra_data as $key => $value) {
								$go_metadata = new \LotusBase\View\GO\Metadata();
								$go_metadata->set_field($key);
								$go_metadata->set_value($value);
								echo '<tr><th scope="row">'.$key.'</th><td>'.$go_metadata->get_html().'</td></tr>';
							}
							if($go_data['URL']) {
								echo '<tr><th scope="row">URL</th><td>'.$go_data['URL'].'</td></tr>';
							}
						?></tbody>
					</table>
				</div>
				<?php }
			}
		?>

		<div id="view__transcript" class="view__facet">
			<h3>Associated <em>Lotus</em> transcripts<?php
				if($q2->rowCount()) {
					echo ' <span class="badge">'.$q2->rowCount().'</span>';
				}
			?></h3>
			<p><abbr title="Gene Ontology">GO</abbr> predictions are based solely on the InterPro to <abbr title="Gene Ontology">GO</abbr> mapping published by EMBL-EBI, which is in turn based on the mapping of predicted domains to the InterPro dataset. The <abbr title="Gene Ontology">GO</abbr> metadata was last updated on October 10, 2016.</p>
			<?php if($q2->rowCount()) { ?>
			<table class="table--dense">
				<thead>
					<tr>
						<th scope="col">Transcript</th>
						<th scope="col">Name</th>
						<th scope="col">Description</th>
						<th scope="col"><abbr title="Gene Ontology">GO</abbr> terms</th>
						<th scope="col"><abbr title="Gene Ontology">GO</abbr> count</th>
					</tr>
				</thead>
				<tbody><?php while($t = $q2->fetch(PDO::FETCH_ASSOC)) { ?>
					<tr>
						<td><?php
							// Transcript data
							$dd_handler = new \LotusBase\Component\TranscriptDropdown();
							$dd_handler->set_title($t['Transcript']);
							$dd_handler->set_transcript($t['Transcript']);
							echo $dd_handler->get_html();
						?></td>
						<td><?php echo $t['LotusName'] ? $t['LotusName'] : '&ndash;'; ?></td>
						<td><?php echo ucfirst(preg_replace('/\[([\w\s]+)\]?/', '[<em>$1</em>]', $t['Description'])); ?></td>
						<td><?php 
							$go_terms = explode(',', $t['GOTerm']);

							$go_terms_data = array();
							foreach($go_terms as $go_term) {
								// Generate array to be added to dropdown link
								$go_terms_data[] = array(
									'link' => WEB_ROOT.'/view/go/'.$go_term,
									'text' => 'View details of '.$go_term,
									'class' => 'icon-eye'
									);

								// Append to matrix for co-occurrence analysis
								$go_term_matrix = array();
								if(!isset($go_term_matrix[$go_term])) {
									$go_term_matrix[$go_term] = array('count' => 1);
								} else{
									$go_term_matrix[$go_term]['count'] += 1;
								}
							}

							$dd_handler = new \LotusBase\Component\Dropdown();
							$dd_handler->set_title('<abbr title="Gene Ontology">GO</abbr> terms');
							$dd_handler->set_data($go_terms_data);
							echo $dd_handler->get_html();
						?></td>
						<td><?php echo count($go_terms) ?></td>
					</tr>
				<?php } ?></tbody>
			</table>
			<?php } else {
				echo '<p class="user-message reminder">No transcripts are associated with this gene ontology identifier.</p>';
			} ?>
		</div>

		<?php if($q2->rowCount()) { ?>
		<div id="view__co-occurring" class="view__facet">
			<h3>Co-occuring <abbr title="Gene Ontology">GO</abbr> terms<?php
				if(count($go_term_matrix)) {
					echo ' <span class="badge">'.count($go_term_matrix).'</span>';
				}
			?></h3>
			<p>A list of co-occurring <abbr title="Gene Ontology">GO</abbr> terms within the <em>L. japonicus</em> gene space:</p>
			<table class="table--dense">
				<thead>
					<tr>
						<th scope="col"><abbr title="Gene Ontology">GO</abbr> term</th>
						<th scope="col">Namespace</th>
						<th scope="col">Name</th>
						<th scope="col">Observations</th>
						<th scope="col">Saturation (%)</th>
					</tr>
				</thead>
				<tbody>
				<?php
					arsort($go_term_matrix);

					// Database connection and query
					$q3 = $db->prepare("SELECT
						go.Namespace AS Namespace,
						go.Name AS Name,
						go.GO_ID AS GOTerm
						FROM gene_ontology AS go
						WHERE go.GO_ID IN (".str_repeat('?,', count(array_keys($go_term_matrix))-1)."?)
						");
					$q3->execute(array_keys($go_term_matrix));

					if($q3->rowCount()) {
						while($r = $q3->fetch(PDO::FETCH_ASSOC)) {
							$go_term_matrix[$r['GOTerm']]['Namespace'] = $r['Namespace'];
							$go_term_matrix[$r['GOTerm']]['Name'] = $r['Name'];
						}
					}

					foreach($go_term_matrix as $go_term => $data) {

						// Generate GO dropdown
						$go_links_handler = new \LotusBase\Component\GODropdown();
						$go_links_handler->internal_link(true);
						$go_links_handler->set_title($go_term);
						$go_links_handler->set_go_term($go_term);

						echo '<tr>
							<td>'.$go_links_handler->get_html().'</td>
							<td>'.$go_namespace[$data['Namespace']].'</td>
							<td>'.$data['Name'].'</td>
							<td>'.$data['count'].'</td>
							<td>'.number_format($data['count']*100/($q2->rowCount()), 2, '.', '').'</td>
						</tr>';
					}
				?>
				</tbody>
			</table>
		</div>
		<?php } ?>

		<?php
			} catch(PDOException $e) {
				echo '<p class="user-message warning">We have encountered an error with querying the database: '.$e->getMessage().'.</p>';
			} catch(Exception $e) {
				echo '<p class="user-message warning">'.$e->getMessage().'.</p>';
			}
		?>

	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>

	<!-- Vis -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js" integrity="sha384-N8EP0Yml0jN7e0DcXlZ6rt+iqKU9Ck6f1ZQ+j2puxatnBq4k9E8Q6vqBcY34LNbn" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/colorbrewer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/d3-tip.min.js"></script>

	<!-- Tabulation -->
	<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js" integrity="sha384-89aj/hOsfOyfD0Ll+7f2dobA15hDyiNb8m1dJ+rJuqgrGR+PVqNU8pybx4pbF3Cc" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/dataTables/buttons.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/dataTables/buttons-flash.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/dataTables/buttons-html5.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/dataTables/buttons-print.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/stupidtable/0.0.1/stupidtable.min.js" integrity="sha384-i1hs1xV885ynDTpLx248kPSNT7iUxg+8qxUH4P5Sm/5G8WDsvIHTh05JlnYudqPl" crossorigin="anonymous"></script>

	<!-- Functions -->
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/go-tree.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/view/go.min.js"></script>
</body>
</html>