<?php
	require_once('../config.php');

	try {
		if(!empty($_GET) && !empty($_GET['id'])) {
			$id = $_GET['id'];
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
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/view.min.css" type="text/css" media="screen" />
</head>
<body class="view go init-scroll--disabled">

	<?php

		// Page header
		$header = new \LotusBase\PageHeader();
		echo $header->get_header();

		// Breadcrumb
		echo get_breadcrumbs(array('custom_breadcrumb' => array(
			'View' => WEB_ROOT.'/view',
			'Gene Ontology' => WEB_ROOT.'/view/go',
			$id => WEB_ROOT.'/view/go/'.$id
		)));

		// Check if GO term exists
		try {
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
	?>

	<section class="wrapper">
		<?php
			if(!$q1->rowCount()) {
				throw new Exception('GO term does not exist. You might want to consider searching for it on alternative online databases');
			}
		?>

		<h2><?php echo $id; ?></h2>
		<div class="align-center">
			<div class="dropdown button button--small" role="secondary">
				<span class="dropdown--title">View this GO term in other databases</span>
				<ul class="dropdown--list">
					<?php
						$go_links_handler = new \LotusBase\View\GO\Link();
						$go_links_handler->set_id($id);
						echo $go_links_handler->get_html();
					?>
				</ul>
			</div>
		</div>

		<div id="view__card" class="view__facet">
			<h3>Overview</h3>
			<?php
				$go_data = $q1->fetch(PDO::FETCH_ASSOC);
				$go_namespace = array(
					'p' => 'Biological process',
					'f' => 'Molecular function',
					'c' => 'Cellular component'
					);
				?>
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
				<li>Double click on a node to update the tree</li>
				<li>Press <kbd>Shift</kbd> and double click to unfix the node</li>
				<li>Press <kbd>Alt</kbd> and double click to visit the page containing further details of a GO term</li>
				<li>Right clicking on a node will reveal a context menu</li>
			</ul>
			<div class="facet floating-controls__hide controls--visible">
				<div class="facet__stage">
					<svg id="go-ancestor" data-go="<?php echo $id; ?>"></svg>
					<ul class="floating-controls position--right">
						<li><a href="#" class="icon-cog icon--no-spacing controls__toggle" title="Toggle controls"></a></li>
						<li><a href="#" id="go-ancestor__export-image" class="icon-camera icon--no-spacing" title="Image export options"></a><ul>
							<li><a href="#" data-image-type="svg" data-source="go-ancestor" data-form="go-tree-export" title="Export current view as SVG file" class="image-export svg-export">SVG (vector)</a></li>
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
						<td><div class="dropdown button">
							<span class="dropdown--title"><a href="<?php echo WEB_ROOT.'/view/transcript/'.$t['Transcript']; ?>"><?php echo $t['Transcript']; ?></a></span>
							<ul class="dropdown--list">
								<li><a href="<?php echo WEB_ROOT.'/view/transcript/'.$t['Transcript']; ?>" title="View transcript"><span class="icon-eye">View transcript</span></a></li>
								<li><a href="<?php echo WEB_ROOT.'/lore1/search-exec?gene='.$t['Transcript'].'&amp;v=3.0'; ?>" title="Search for LORE1 insertions in this transcript"><span class="pictogram icon-leaf"><em>LORE1</em> v3.0</span></a></li>
								<li><a href="<?php echo WEB_ROOT.'/genome?loc='.$t['Transcript']; ?>" title="View in genome browser"><span class="icon-book">Genome browser</span></a></li>
								<li><a href="<?php echo WEB_ROOT.'/expat?ids='.$t['Transcript']; ?>" title="Access expression data from the Expression Atlas"><span class="icon-map">Expression Atlas (ExpAt)</span></a></li>
							</ul>
						</div></td>
						<td><?php echo $t['LotusName'] ? $t['LotusName'] : '&ndash;'; ?></td>
						<td><?php echo ucfirst(preg_replace('/\[([\w\s]+)\]?/', '[<em>$1</em>]', $t['Description'])); ?></td>
						<td><?php 
							$go_terms = explode(',', $t['GOTerm']);

							$go_terms_data = array();
							foreach($go_terms as $go_term) {
								// Generate array to be added to dropdown link
								$go_terms_data[] = array(
									'link' => WEB_ROOT.'/view/go/'.$go_term,
									'text' => $go_term
									);

								// Append to matrix for co-occurrence analysis
								$go_term_matrix[$go_term]['count'] += 1;
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
						$go_links_handler = new \LotusBase\View\GO\Link();
						$go_links_handler->set_id($go_term);
						$go_links_handler->add_internal_link();

						echo '<tr>
							<td>
								<div class="dropdown button">
									<span class="dropdown--title"><a href="'.WEB_ROOT.'/view/go/'.$go_term.'">'.$go_term.'</a></span>
									<ul class="dropdown--list">'.$go_links_handler->get_html().'</ul>
								</div>
							</td>
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
	<script type="text/javascript" src="//d3js.org/d3.v3.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/colorbrewer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/d3-tip.min.js"></script>
	<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/stupidtable/0.0.1/stupidtable.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/go-tree.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/view/go.min.js"></script>
</body>
</html>