<?php
	require_once('../config.php');

	try {
		if(!empty($_GET) && !empty($_GET['id'])) {
			$id = escapeHTML($_GET['id']);
			if(!preg_match('/^(ipr|cd|g3dsa|mf|pthr|pf|pirsf|pr|pd|ps|sfldf|sm|ssf|tigr).*$/i', $id)) {
				// If ID fails pattern check
				throw new Exception('Invalid protein domain identifier detected. Please ensure that your protein domain identifier is valid and is generated from the following prediction algorithms: CDD, Gene3D, InterPro, PANTHER, PatternScan, PFam, PRINTS, ProSite Patterns, ProSite Profiles, SMART, SUPERFAMILY, or TIGRFAM.');
			}

			// Check if GO term exists
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			// InterPro Domain flag
			$is_interpro = false;
			if(strpos($id, 'IPR') > -1) {
				$is_interpro = true;
			}

			// Determine if domain is InterPro
			if($is_interpro) {
				$q1 = $db->prepare("SELECT
						GROUP_CONCAT(DISTINCT dompred.SourceID) AS MappedIDs,
						GROUP_CONCAT(DISTINCT ip_go.GO_ID) AS GOTerms,
						'InterPro' AS Source
					FROM domain_predictions AS dompred
					LEFT JOIN interpro_go_mapping AS ip_go ON
						dompred.InterProID = ip_go.InterPro_ID
					WHERE dompred.InterProID = ?
					GROUP BY dompred.InterProID");
			} else {
				$q1 = $db->prepare("SELECT
						GROUP_CONCAT(DISTINCT dompred.InterProID) AS MappedIDs,
						GROUP_CONCAT(DISTINCT ip_go.GO_ID) AS GOTerms,
						dompred.Source AS Source,
						dommeta.SourceDescription AS Description
					FROM domain_predictions AS dompred
					LEFT JOIN interpro_go_mapping AS ip_go ON
						dompred.InterProID = ip_go.InterPro_ID
					LEFT JOIN domain_metadata AS dommeta ON
						dompred.SourceID = dommeta.SourceID
					WHERE dompred.SourceID = ?
					GROUP BY dompred.SourceID");
			}
			$q1->execute(array($id));

			if(!$q1->rowCount()) {
				throw new Exception('Predicted domain does not exist. You might want to consider searching for it on alternative online databases.');
			}

			$domain_data = $q1->fetch(PDO::FETCH_ASSOC);

			if($is_interpro) {
				// Perform second query to obtain transcript info
				$q2 = $db->prepare("SELECT
						anno.Gene AS Transcript,
						anno.Annotation AS Description,
						anno.LjAnnotation AS LotusName,
						GROUP_CONCAT(DISTINCT dompred2.SourceID ORDER BY dompred2.SourceID ASC) AS SourceIDs,
						GROUP_CONCAT(DISTINCT dompred2.InterProID ORDER BY dompred2.InterProID ASC) AS InterProIDs
					FROM annotations AS anno
					LEFT JOIN domain_predictions AS dompred ON (
						anno.Gene = dompred.Transcript
					)
					LEFT JOIN interpro_go_mapping AS ip_go ON (
						dompred.InterProID = ip_go.InterPro_ID
					)
					LEFT JOIN domain_predictions AS dompred2 ON (
						dompred.Transcript = dompred2.Transcript
					)
					WHERE dompred.InterProID = ?
					GROUP BY anno.Gene");
			} else {
				// Perform second query to obtain transcript info
				$q2 = $db->prepare("SELECT
						anno.Gene AS Transcript,
						anno.Annotation AS Description,
						anno.LjAnnotation AS LotusName,
						GROUP_CONCAT(DISTINCT dompred2.SourceID ORDER BY dompred2.SourceID ASC) AS SourceIDs,
						GROUP_CONCAT(DISTINCT dompred2.InterProID ORDER BY dompred2.InterProID ASC) AS InterProIDs
					FROM annotations AS anno
					LEFT JOIN domain_predictions AS dompred ON (
						anno.Gene = dompred.Transcript
					)
					LEFT JOIN interpro_go_mapping AS ip_go ON (
						dompred.InterProID = ip_go.InterPro_ID
					)
					LEFT JOIN domain_predictions AS dompred2 ON (
						dompred.Transcript = dompred2.Transcript
					)
					WHERE dompred.SourceID = ?
					GROUP BY anno.Gene");
			}
			$q2->execute(array($id));

			// Perform third query to obtain GO term data
			$go_terms = explode(',', $domain_data['GOTerms']);
			if(count($go_terms)) {
				$q3 = $db->prepare("SELECT
					go.GO_ID AS GOTerm,
					go.NameSpace AS Namespace,
					go.Name AS Name,
					go.Definition AS Definition,
					go.SubtermOf AS SubtermOf,
					go.Relationships AS Relationships,
					go.URL AS URL
				FROM gene_ontology AS go
				WHERE go.GO_ID IN (".str_repeat("?,", count($go_terms)-1)."?)
					");
				$q3->execute($go_terms);
			}

			// Get description
			if($is_interpro) {
				// Retrieve description from EB-eye service
				$ebeye_handler = new \LotusBase\EBI\EBeye();
				$ebeye_handler->set_domain('interpro');
				$ebeye_handler->set_ids($id);
				$data = $ebeye_handler->get_data();
				$short_desc = $data[$id]['fields']['name'][0];
				$desc = '<p><strong>'.$id.'</strong> is a '.$data[$id]['fields']['name'][0].'.</p><p>'.$data[$id]['fields']['description'][0].'</p><p>This description is obtained from <a href="http://www.ebi.ac.uk/Tools/webservices/services/eb-eye_rest" target="_blank">EB-eye REST</a>.</p>';

			} else {
				$desc = $domain_data['Description'];
				$short_desc = $desc;
			}

		} else {
			// If ID is not available
			throw new Exception;
		}
	} catch (Exception $e) {
		if(!empty($e->getMessage())) {
			$_SESSION['view_error'] = $e->getMessage();
		}
		header('Location:'.WEB_ROOT.'/view');
		exit();
	}
	
?>
<!doctype html>
<html lang="en">
<head>
	<title>Domain Predictions &mdash; View &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Consolidated domain prediction view: '.$id.' ('.$domain_data['Source'].'), a '.$short_desc
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
			'Domain Prediction' => 'domain',
			$id => $id
		));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2><?php echo $id; ?></h2>
		<span class="byline">Source: <strong><?php
			$source = $domain_data['Source'];
			$source_links = array(
				'CDD' => 'https://www.ncbi.nlm.nih.gov/Structure/cdd/cddsrv.cgi?uid={{id}}',
				'Gene3D' => 'http://www.cathdb.info/version/latest/superfamily/{{id}}',
				'Hamap' => 'http://hamap.expasy.org/profile/{{id}}',
				'PANTHER' => 'http://www.pantherdb.org/panther/family.do?clsAccession={{id}}',
				'Pfam' => 'http://pfam.xfam.org/family/{{id}}',
				'PIRSF' => 'http://pir.georgetown.edu/cgi-bin/ipcSF?id={{id}}',
				'PRINTS' => 'http://www.bioinf.manchester.ac.uk/cgi-bin/dbbrowser/sprint/searchprintss.cgi?prints_accn={{id}}&display_opts=Prints&category=None&queryform=false&regexpr=off',
				'ProDom' => 'http://prodom.prabi.fr/prodom/current/cgi-bin/request.pl?question=DBEN&query={{id}}',
				'ProSitePatterns' => 'http://prosite.expasy.org/cgi-bin/prosite/prosite-search-ac?{{id}}',
				'ProSiteProfiles' => 'http://prosite.expasy.org/cgi-bin/prosite/prosite-search-ac?{{id}}',
				'SFLD' => 'http://sfld.rbvi.ucsf.edu/django/family/{{id}}',
				'SMART' => 'http://smart.embl-heidelberg.de/smart/do_annotation.pl?BLAST=DUMMY&DOMAIN={{id}}',
				'SUPERFAMILY' => 'http://supfam.org/SUPERFAMILY/cgi-bin/scop.cgi?sunid={{id}}',
				'TIGRFAM' => 'http://jcvi.org/cgi-bin/tigrfams/HmmReportPage.cgi?acc={{id}}'
				);

			$source_replace = array(
				'Gene3D' => array('G3DSA:', ''),
				'SFLD' => array('SFLDF', ''),
				'SUPERFAMILY' => array('SSF', '')
				);

			if(array_key_exists($source, $source_links)) {
				if(array_key_exists($source, $source_replace)) {
					$_id = str_replace($source_replace[$source][0], $source_replace[$source][1], $id);
				} else {
					$_id = $id;
				}
				$url = str_replace('{{id}}', $_id, $source_links[$source]);
				echo '<a href="'.$url.'" title="View '.$id.' on '.$source.'" target="_blank">'.$source.'</a>';
			} else {
				echo $source;
			}
		?></strong></span>

		<div id="view__description" class="view__facet">
			<h3>Description</h3>
			<?php
				if(!empty($desc)) {
					echo '<p>'.$desc.'</p>';
				} else {
					echo '<p>No description is available for this domain.</p>';
				}
			?>
		</div>

		<div id="view__go" class="view__facet">
			<h3>Associated <abbr title="Gene Ontology">GO</abbr> terms</h3>
			<?php if(!empty($q3) && $q3->rowCount()) { ?>
			<p>
				<abbr title="Gene Ontology">GO</abbr> predictions are based solely on the InterPro-to-<abbr title="Gene Ontology">GO</abbr> mappings published by EMBL-EBI, which are in turn based on the mapping of predicted domains to the InterPro dataset.
				The InterPro-to-<abbr title="Gene Ontology">GO</abbr> mapping was last updated on <?php echo get_date_from_timestamp(INTERPRO_TO_GO_LAST_UPDATED, true); ?>, while
				the <abbr title="Gene Ontology">GO</abbr> metadata was last updated on <?php echo get_date_from_timestamp(GO_METADATA_LAST_UPDATED, true); ?>.
			</p>
			<table class="table--dense">
				<thead>
					<tr>
						<th data-sort="string" scope="col"><abbr title="Gene Ontology">GO</abbr>&nbsp;term</th>
						<th data-sort="string" scope="col">Namespace</th>
						<th scope="col">Name</th>
						<th scope="col">Definition</th>
						<th scope="col">Relationships</th>
					</tr>
				</thead>
				<tbody>
				<?php
					$go_namespace = array(
						'p' => 'Biological process',
						'f' => 'Molecular function',
						'c' => 'Cellular component'
						);
					while($go = $q3->fetch(PDO::FETCH_ASSOC)) {
				?>
					<tr>
						<td><?php
							// Generate GO dropdown
							$go_links_handler = new \LotusBase\Component\GODropdown();
							$go_links_handler->internal_link(true);
							$go_links_handler->set_title($go['GOTerm']);
							$go_links_handler->set_go_term($go['GOTerm']);
							echo $go_links_handler->get_html();
						?></td>
						<td><?php echo $go_namespace[$go['Namespace']]; ?></td>
						<td><?php echo $go['Name']; ?>
						<td><?php echo $go['Definition']; ?></td>
						<td><?php
							$go_rels = json_decode($go['Relationships'], true);
							foreach($go_rels as $type => $r) {
								if(is_array($r) && count($r)) {
									if($type === 'is_a') {
										$type = 'Subterm of';
									}
									echo '<div class="dropdown button"><span class="dropdown--title">'.ucfirst(str_replace('_', ' ', $type)).'</span><ul class="dropdown--list">';
									asort($r);
									foreach($r as $_r) {
										echo '<li><a href="'.WEB_ROOT.'/view/go/'.$_r.'">'.$_r.'</a></li>';
									}
									echo '</ul></div>';
								}
							}
						?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			<?php } else { ?>
			<p class="user-message reminder">Unable to find any <abbr title="Gene Ontology">GO</abbr> terms for the transcript with the identifier.</p>
			<?php } ?>
		</div>

		<div id="view__transcript" class="view__facet">
			<h3>Associated <em>Lotus</em> transcripts<?php
				if($q2->rowCount()) {
					echo ' <span class="badge">'.$q2->rowCount().'</span>';
				}
			?></h3>
			<?php if($q2->rowCount()) { ?>
			<table class="table--dense">
				<thead>
					<tr>
						<th scope="col">Transcript</th>
						<th scope="col">Name</th>
						<th scope="col">Description</th>
						<th scope="col">Predicted domains</th>
						<th scope="col">Domain count</th>
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
							$domains = array_merge(explode(',', $t['SourceIDs']), explode(',', $t['InterProIDs']));
							asort($domains);

							$domain_data = array();
							foreach($domains as $domain) {
								// Generate array to be added to dropdown link
								$domain_data[] = array(
									'link' => WEB_ROOT.'/view/domain/'.$domain,
									'text' => $domain
									);

								// Append to matrix for co-occurrence analysis
								$domain_matrix = array();
								if(!isset($domain_matrix[$domain])) {
									$domain_matrix[$domain] = array('count' => 1);
								} else {
									$domain_matrix[$domain]['count'] += 1;
								}
							}

							$dd_handler = new \LotusBase\Component\Dropdown();
							$dd_handler->set_title('Domains');
							$dd_handler->set_data($domain_data);
							echo $dd_handler->get_html();
						?></td>
						<td><?php echo count($domains); ?></td>
					</tr>
				<?php } ?></tbody>
			</table>
			<?php } else {
				echo '<p class="user-message reminder">No transcripts are associated with this gene ontology identifier.</p>';
			} ?>
		</div>

		<?php if($q2->rowCount()) { ?>
		<div id="view__co-occurring" class="view__facet">
			<h3>Co-occuring domains<?php
				if(count($domain_matrix)) {
					echo ' <span class="badge">'.count($domain_matrix).'</span>';
				}
			?></h3>
			<p>A list of co-occurring predicted domains within the <em>L. japonicus</em> gene space:</p>
			<table class="table--dense">
				<thead>
					<tr>
						<th scope="col">Predicted domain</th>
						<th scope="col">Source</th>
						<th scope="col">Observations</th>
						<th scope="col">Saturation (%)</th>
					</tr>
				</thead>
				<tbody>
				<?php
					arsort($domain_matrix);

					// Database connection and query
					$q3 = $db->prepare("SELECT
							dompred.SourceID AS SourceID,
							dompred.Source AS Source
							FROM domain_predictions AS dompred
							WHERE dompred.SourceID IN (".str_repeat('?,', count(array_keys($domain_matrix))-1)."?)
						UNION
						SELECT
							dompred.InterProID AS InterProID,
							'InterPro' AS Source
							FROM domain_predictions AS dompred
							WHERE dompred.InterProID IN (".str_repeat('?,', count(array_keys($domain_matrix))-1)."?)
						");
					$q3->execute(array_merge(array_keys($domain_matrix), array_keys($domain_matrix)));

					if($q3->rowCount()) {
						while($r = $q3->fetch(PDO::FETCH_ASSOC)) {
							$domain_matrix[$r['SourceID']]['source'] = $r['Source'];
						}
					}

					foreach($domain_matrix as $domain => $data) {

						echo '<tr>
							<td>'.(preg_match('/^(ipr|cd|g3dsa|mf|pthr|pf|pirsf|pr|pd|ps|sfldf|sm|ssf|tigr).*$/i', $domain) ? '<a href="'.WEB_ROOT.'/view/domain/'.$domain.'">'.$domain.'</a>' : $domain).'</td>
							<td>'.$data['source'].'</td>
							<td>'.$data['count'].'</td>
							<td>'.number_format($data['count']*100/($q2->rowCount()), 2, '.', '').'</td>
						</tr>';
					}
				?>
				</tbody>
			</table>
		</div>
		<?php } ?>

	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js" integrity="sha384-89aj/hOsfOyfD0Ll+7f2dobA15hDyiNb8m1dJ+rJuqgrGR+PVqNU8pybx4pbF3Cc" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/dataTables/buttons.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/dataTables/buttons-flash.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/dataTables/buttons-html5.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/dataTables/buttons-print.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/stupidtable/0.0.1/stupidtable.min.js" integrity="sha384-i1hs1xV885ynDTpLx248kPSNT7iUxg+8qxUH4P5Sm/5G8WDsvIHTh05JlnYudqPl" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/view/domain.min.js"></script>
</body>
</html>