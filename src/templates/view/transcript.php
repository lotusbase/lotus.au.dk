<?php
	require_once('../config.php');

	try {
		if(!empty($_GET) && !empty($_GET['id'])) {
			$id = escapeHTML($_GET['id']);

			// Match gene patterns
			if(
				// MG20 v3.0
				preg_match('/^Lj(\d|chloro}mito)g3v(\d+)$/', $id) ||

				// Gifu v1.2
				preg_match('/^LotjaGi\dg\dv\d+?$/', $id)
			) {
				// If gene pattern is match, redirect to gene page
				header('Location:'.WEB_ROOT.'/view/gene/'.$id);
				exit();
			}
			
			// Doesn't match any known transcirpt patterns
			else if(
				// MG20 v3.0
				!preg_match('/^Lj(\d|chloro}mito)g3v(\d+)\.\d+$/', $id) &&

				// Gifu v1.2
				!preg_match('/^LotjaGi\dg\dv\d+?\.\d+?$/', $id)
			) {
				// If ID fails pattern check
				$_SESSION['view_error'] = 'Invalid transcript ID format detected. Please ensure that your transcript ID follows the formats:
					<ul>
						<li><strong>MG20 v3.0</strong>: <code>Lj{chr}g{version}v{id}.{isoform}</code>, e.g. Lj4g3v0281040.1</li>
						<li><strong>Gifu v1.2</strong>: <code>LotjaGi{chr}g{version}v{id}.{isoform}</code>, e.g. LotjaGi4g1v0024900.1</li>
					</ul>';
				throw new Exception;
			} else {
				// Coerce gene ID to transcript ID
				$coerced = false;
				if(preg_match('/^Lj(\d|chloro}mito)g3v(\d+)\.\d+$/', $_GET['id'])) {
					$genome = 'MG20_3.0';
					$gene = escapeHTML($_GET['id']);
				} else if(preg_match('/^LotjaGi\dg\dv\d+?\.\d+?$/', $_GET['id'])) {
					$genome = 'Gifu_1.2';
					$gene = escapeHTML($_GET['id']);
				} else {
					$gene = escapeHTML($_GET['id'].'.1');
					$coerced = true;
				}
	
				// Perform first query
				$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

				$q1 = $db->prepare("SELECT
						anno.Gene AS Transcript,
						anno.Ecotype AS Ecotype,
						anno.Version AS Version,
						GROUP_CONCAT(DISTINCT isoforms.Transcript) AS Isoforms,
						tc.Gene AS Gene,
						tc.StartPos AS Start,
						tc.EndPos AS End,
						tc.Strand AS Strand,
						tc.Chromosome AS Chromosome,
						anno.Annotation AS Annotation,
						anno.LjAnnotation AS LjAnnotation,
						GROUP_CONCAT(DISTINCT geniclore1.PlantID ORDER BY geniclore1.PlantID ASC) AS GenicPlantID,
						GROUP_CONCAT(DISTINCT exoniclore1.PlantID ORDER BY exoniclore1.PlantID ASC) AS ExonicPlantID
					FROM annotations AS anno
					LEFT JOIN transcriptcoord AS tc ON (
						anno.Gene = tc.Transcript AND
						anno.Ecotype = tc.Ecotype AND
						anno.Version = tc.Version
					)
					LEFT JOIN transcriptcoord AS isoforms ON (
						tc.Gene = isoforms.Gene
					)
					LEFT JOIN exonins AS exon ON (
						tc.Transcript = exon.Gene AND
						tc.Ecotype = exon.Ecotype AND
						tc.Version = exon.Version
					)
					LEFT JOIN geneins AS genic ON (
						tc.Gene = genic.Gene AND
						tc.Ecotype = genic.Ecotype AND
						tc.Version = genic.Version
					)
					LEFT JOIN lore1ins AS geniclore1 ON (
						genic.Chromosome = geniclore1.Chromosome AND
						genic.Position = geniclore1.Position AND
						genic.Orientation = geniclore1.Orientation AND
						genic.Ecotype = geniclore1.Ecotype AND
						genic.Version = geniclore1.Version
					)
					LEFT JOIN lore1ins AS exoniclore1 ON (
						exon.Chromosome = exoniclore1.Chromosome AND
						exon.Position = exoniclore1.Position AND
						exon.Orientation = exoniclore1.Orientation AND
						exon.Ecotype = exoniclore1.Ecotype AND
						exon.Version = exoniclore1.Version
					)
					WHERE anno.Gene = ?
					LIMIT 1");
				$q1->execute(array($gene));
				$g = $q1->fetch(PDO::FETCH_ASSOC);
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
	<title>Transcript &mdash; View &mdash; Lotus Base</title>
	<script>
		window.lotusbase = {
			genome: '<?php echo $genome; ?>'
		};
	</script>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Consolidated transcript/protein view: '.$g['Transcript'].(!empty($g['LjAnnotation']) ? ' ('.$g['LjAnnotation'].')' : '').(!empty($g['Annotation']) ? ' is a '.$g['Annotation'] : '')
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/view.min.css" type="text/css" media="screen" />
</head>
<body class="view transcript init-scroll--disabled">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2><?php echo $gene; ?></h2>
		<?php
			if($coerced) {
				echo '<p class="user-message"><span class="icon-attention"></span>We have converted your gene (<strong>'.escapehtml($_GET['id']).'</strong>) to a specific isoform (<strong>'.$gene.'</strong>).</p>';
			}
		?>
		<div id="view__card" class="view__facet">
			<h3>Overview</h3>
			<?php
				try {
					if($q1->rowCount()) {
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
									<th scope="row">Gene ID</th>
									<td><a href="<?php echo WEB_ROOT.'/view/gene/'.$g['Gene']; ?>" title="View gene <?php echo $g['Gene']; ?>"><?php echo $g['Gene']; ?></a></td>
								</tr>
								<tr>
									<th scope="row">Transcript ID</th>
									<td><?php echo $g['Transcript']; ?></td>
								</tr>
								<?php
									$isoforms = explode(',', $g['Isoforms']);
									$index = array_search($gene, $isoforms);
									if($index !== false){
										unset($isoforms[$index]);
									}
									if(count($isoforms)) {
										echo '<tr><th>Related isoforms <span class="badge">'.count($isoforms).'</span></th><td><ul class="list--floated list--no-spacing">';
										foreach ($isoforms as $i) {
											echo '<li><a class="link--reset" href="'.WEB_ROOT.'/gene/'.$i.'">'.$i.'</a></li>';
										}
										echo '</ul></td></tr>';
									}
								?>
								<tr>
									<th scope="row"><em>Lotus japonicus</em> genome version</th>
									<td><?php echo $g['Ecotype'].' v'.$g['Version']; ?></td>
								</tr>
								<tr>
									<th scope="row">Description</th>
									<td><?php echo preg_replace('/\[([\w\s]+)\]?/', '[<em>$1</em>]', $g['Annotation']); ?></td>
								</tr>
								<tr>
									<th scope="row">Working <em>Lj</em> name</th>
									<td><em><?php echo !empty($g['LjAnnotation']) ? $g['LjAnnotation'] : 'n.a.'; ?></em></td>
								</tr>
							</tbody>
						</table>
						<?php
					} else {
						throw new Exception('Unable to find any records for the transcript with the identifer <strong>'.$gene.'</strong>');
					}

					$q2 = $db->prepare("SELECT
						dompred.Source AS Source,
						dompred.SourceID AS SourceID,
						dompred.DomainStart AS DomainStart,
						dompred.DomainEnd AS DomainEnd,
						dompred.Evalue AS Evalue,
						CASE
							WHEN dompred.InterProID IS NULL THEN 'Unassigned'
							ELSE dompred.InterProID
						END AS InterProID
					FROM domain_predictions AS dompred
					WHERE dompred.Transcript = ?
					ORDER BY DomainStart ASC
						");
					$q2->execute(array($gene));

					if($q2->rowCount()) {
						while($ip = $q2->fetch(PDO::FETCH_ASSOC)) {
							$_ip[] = $ip;
						}

						// Group by interpro ID
						$ip_unique = array();
						foreach($_ip as $key => $item) {
							$ip_grouped[$item['InterProID']][] = $item;
							if(!in_array($item['InterProID'], $ip_unique) && $item['InterProID'] !== 'Unassigned') {
								$ip_unique[] = $item['InterProID'];
							}
						}

						// Get Interpro data
						$ip_handler = new \LotusBase\EBI\EBeye();
						$ip_handler->set_domain('interpro');
						$ip_handler->set_ids($ip_unique);
						$ip_data = $ip_handler->get_data();

						// Collect GO predictions and type
						$ip_data_itemised = array(
							'go' => array()
							);
						foreach($ip_data as $ip) {
							$ip_data_itemised['go'][$ip['id']] = $ip['fields']['GO'];
						}
						
						// Flatten GO array
//						$gos = array_unique(array_flatten($ip_data_itemised['go']));
//
//						// Retrieve Gene Ontology data
//						$go_handler = new \LotusBase\EBI\EBeye();
//						$go_handler->set_domain('go');
//						$go_handler->set_ids($gos);
//						$go_data = $go_handler->get_data();

					} else {
						throw new Exception('No InterPro domain data available');
					}

					if(!empty($ip_unique)) {
						$q3 = $db->prepare("SELECT
							GROUP_CONCAT(ip_go.InterPro_ID) AS InterPro,
							ip_go.GO_ID AS GeneOntology,
							go.NameSpace AS Namespace,
							go.Name AS Name,
							go.Definition AS Definition,
							go.ExtraData AS ExtraData,
							go.SubtermOf AS SubtermOf,
							go.Relationships AS Relationships,
							go.URL AS URL
						FROM interpro_go_mapping AS ip_go
						LEFT JOIN gene_ontology AS go
							ON ip_go.GO_ID = go.GO_ID
						WHERE ip_go.InterPro_ID IN (".str_repeat("?,", count($ip_unique)-1)."?)
						GROUP BY ip_go.GO_ID
						ORDER BY ip_go.GO_ID ASC
							");
						$q3->execute($ip_unique);
					} else {
						$q3 = null;
					}

				} catch(PDOException $e) { ?>
					<p class="user-message warning">We have encountered an error with querying the database: <?php echo $e->getMessage(); ?></p>
				<?php } catch(Exception $e) { ?>
					<p class="user-message warning">We have encountered a general error: <?php echo $e->getMessage(); ?></p>
				<?php }
			?>
		</div>

		<div id="view__sequence" class="view__facet">
			<?php
				$sequenceDB_metadata = array(
					'MG20_v3.0' => array(
						'genomic' => array(
							'title' => 'Genomic',
							'database' => array(
								'type' => 'nucleotide',
								'file' => 'lj_r30.fa'
							)
						),
						'cds' => array(
							'title' => 'CDS',
							'database' => array(
								'type' => 'transcript',
								'file' => '20130521_Lj30_CDS.fa'
							)
						),
						'cdna' => array(
							'title' => 'cDNA/mRNA',
							'database' => array(
								'type' => 'transcript',
								'file' => '20130521_Lj30_cDNA.fa'
							)	
						),
						'protein' => array(
							'title' => 'Protein',
							'database' => array(
								'type' => 'transcript',
								'file' => '20130521_Lj30_proteins.fa'
							)
						)
					),
					'Gifu_1.2' => array(
						'genomic' => array(
							'title' => 'Genomic',
							'database' => array(
								'type' => 'nucleotide',
								'file' => '20190809_Lj_Gifu_v1.2_genome.fa'
							)
						),
						'cds' => array(
							'title' => 'CDS',
							'database' => array(
								'type' => 'transcript',
								'file' => '20190809_Lj_Gifu_v1.2_CDS.fa'
							)
						),
						'protein' => array(
							'title' => 'Protein',
							'database' => array(
								'type' => 'transcript',
								'file' => '20190809_Lj_Gifu_v1.2_proteins.fa'
							)
						)
					)
				);
			?>
			<div id="sequence-tabs__nav" class="cols align-items__flex-end ui-tabs-nav__wrapper">
				<h3>Sequence information</h3>
				<ul class="minimal">
				<?php
					foreach($sequenceDB_metadata[$genome] as $id => $db) {
						echo '<li><a href="#sequence-tabs__'.$id.'" data-custom-smooth-scroll>'.$db['title'].'</a></li>';
					}
				?>
				</ul>
			</div>

			<?php foreach($sequenceDB_metadata[$genome] as $id => $db) {
				echo '<div id="sequence-tabs__'.$id.'">';
				$strand = (!empty($g['Strand']) ? ($g['Strand'] === '+' ? 'plus' : 'minus') : 'auto');
				try {
					$sequence = new \LotusBase\BLAST\Query();

					echo '<p>'.$db['title'].' sequence (';

					$sequence_position = '';

					// Set query settings
					if($db['database']['type'] === 'nucleotide') {
						$sequence_id = $g['Chromosome'];
						$sequence_position = '&from='.$g['Start'].'&to='.$g['End'];

						$sequence->set_id($sequence_id);
						$sequence->set_database($db['database']['file']);
						$sequence->set_position($g['Start'], $g['End']);
						$sequence->set_strand($strand);

						// Display info
						echo $g['Chromosome'].':'.$g['Start'].'..'.$g['End'];

					} else if($db['database']['type'] === 'transcript') {
						$sequence_id = $gene;

						$sequence->set_id($sequence_id);
						$sequence->set_database($db['database']['file']);
						$sequence->set_strand('plus');

						// Display info
						echo $gene;

					} else {
						throw new Exception('Invalid database type detected');
					}

					$blast_db = new \LotusBase\BLAST\DBMetadata();

					echo ') extracted from <strong>'.$blast_db->get_metadata($db['database']['file'])['title'].'</strong>.';

					// Retrieve sequence
					$seq = $sequence->execute()[0]['sequence'];
					echo '<pre class="sequence">'.naseq($seq).'</pre>';
					echo '<div class="align-center"><a href="'.WEB_ROOT.'/api/v1/blast/'.$db['database']['file'].'/'.$sequence_id.'?download'.$sequence_position.'&access_token='.LOTUSBASE_API_KEY.'" class="button"><span class="icon-download">Download '.$db['title'].' Sequence</span></a></div>';

				} catch(Exception $e) {
					echo '<p class="user-message warning">We have encountered an error when attempting to retrieve your sequence: '.$e->getMessage().'</p>';
				}
				echo '</div>';
			} ?>

		</div>

		<div id="view__domain-prediction" class="view__facet">
			<h3>Domain prediction</h3>
			<p>Data for domain prediction are obtained with <a href="https://github.com/ebi-pf-team/interproscan">InterProScan</a>, and merged with InterPro data obtained from the <a href="http://www.ebi.ac.uk/Tools/webservices/services/eb-eye_rest">EB-eye REST service</a>.</p>
			<?php
				try {
					if($q2->rowCount()) {
						?>
						<div class="facet floating-controls__hide d3-chart">
							<div class="facet__stage" id="domain-prediction" data-protein="<?php echo $gene; ?>">
								<div id="domain-prediction__loader" class="align-center loader__wrapper">
									<div class="loader"><svg class="loader"><circle class="path" cx="40" cy="40" r="30" /></svg></div>
									<p>Merging data from EBeye. Please wait&hellip;</p>
								</div>
								<ul class="floating-controls position--right">
									<li><a href="#" class="icon-cog icon--no-spacing controls__toggle" title="Toggle controls"></a></li>
								</ul> 
							</div>
							<form class="facet__controls has-group" id="domain__controls" action="#" method="get">
								<div class="has-legend cols" role="group">
									<p class="legend">Domains</p>
									<label for="dc__fill" class="col-one">Color</label>
									<select id="dc__fill" name="dc__fill" class="col-two">
										<option value="interpro_type" selected>InterPro domain type (default)</option>
										<option value="prediction_algorithm">Prediction algorithm</option>
									</select>

									<label class="col-one">Filter</label>
									<div class="col-two">
										<?php
											$pred = array();
											foreach($_ip as $key => $item) {
												if(!in_array($item['Source'], $pred)) {
													$pred[] = $item['Source'];
												}
											}
											sort($pred);
											foreach($pred as $p) {
												echo '<label for="dc__source-'.$p.'"><input type="checkbox" class="dc__filter prettify" id="dc__source-'.$p.'" value="'.$p.'" checked>'.$p.'</label>';
											}
										?>
									</div>
								</div>

								<div class="has-legend cols" role="group">
									<p class="legend">Sorting</p>
									<label for="dc__sort" class="col-one">Sort</label>
									<select id="dc__sort" name="dc__sort" class="col-two">
										<option value="start" seleted>Start position (default)</option>
										<option value="end">End position</option>
										<option value="length">Length</option>
										<option value="prediction_algorithm">Prediction algorithm</option>
										<option value="interpro_id">InterPro ID</option>
										<option value="interpro_namespace">InterPro namespace</option>
									</select>
								</div>
							</form>
						</div>
						<table class="table--dense">
							<thead>
								<tr>
									<th data-sort="string" scope="col">Prediction algorithm</th>
									<th data-sort="string" scope="col">Identifier</th>
									<th data-sort="int" scope="col" data-type="numeric">Start</th>
									<th data-sort="int" scope="col" data-type="numeric">End</th>
									<th data-sort="int" scope="col" data-type="numeric">Length</th>
									<th data-sort="float" scope="col" data-type="numeric">E-value</th>
									<th data-sort="string" scope="col">InterPro ID</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach($_ip as $key => $item) { ?>
								<tr>
									<td><?php echo $item['Source']; ?></td>
									<td><?php
										try {
											$sourceHandler = new \LotusBase\Component\DomainDropdown();
											$sourceHandler->set_source($item['Source'], $item['SourceID']);
											echo $sourceHandler->get_HTML();
										} catch(Exception $e) {
											echo '&ndash;';
										}
									?></td>
									<td data-type="numeric"><?php echo $item['DomainStart']; ?></td>
									<td data-type="numeric"><?php echo $item['DomainEnd']; ?></td>
									<td data-type="numeric"><?php echo $item['DomainEnd'] - $item['DomainStart'] + 1; ?></td>
									<td data-type="numeric" data-order="<?php echo 0 + $item['Evalue']; ?>"><?php echo $item['Evalue'] !== null ? $item['Evalue'] : '&ndash;'; ?></td>
									<td><?php if($item['InterProID'] !== 'Unassigned') { ?>
										<div class="dropdown button">
											<span class="dropdown--title"><a href="<?php echo WEB_ROOT.'/view/domain/'.$item['InterProID']; ?>"><?php echo $item['InterProID']; ?></a></span>
											<ul class="dropdown--list">
												<li><a href="<?php echo WEB_ROOT.'/api/v1/view/domain/interpro/'.$item['InterProID']; ?>" data-desc-id="<?php echo $item['InterProID']; ?>" data-desc-source="interpro"><span class="icon-eye">Show description in modal box</span></a></li>
												<li><a href="<?php echo WEB_ROOT.'/view/domain/'.$item['InterProID']; ?>" title="View details on <?php echo $item['InterProID']; ?>"><span class="icon-eye">View details on <?php echo $item['InterProID']; ?></span></a></li>
												<li><a href="<?php echo WEB_ROOT; ?>/tools/trex/?ids=<?php echo $item['InterProID']; ?>" title="Search for proteins/transripts with this domain"><span class="icon-search">Search for proteins/transripts with this domain</span></a></li>
												<li><a href="http://www.ebi.ac.uk/interpro/entry/<?php echo $item['InterProID']; ?>" target="_blank">View on EBI InterPro Service</a></li>
											</ul>
										</div>
									<?php } else { echo '&ndash;'; } ?>
								</tr>
							<?php } ?>
							</tbody>
						</table>
						<?php

					} else {
						throw new Exception('Unable to find any records for the transcript with the identifer <strong>'.$gene.'</strong>');
					}
				} catch(Exception $e) {
					echo '<p class="user-message">'.$e->getMessage().'.</p>';
				}
			?>
		</div>

		<div id="view__function" class="view__facet">
			<h3>Gene function (<abbr title="Gene Ontology">GO</abbr> predictions)</h3>
			<p>
				<abbr title="Gene Ontology">GO</abbr> predictions are based solely on the InterPro-to-<abbr title="Gene Ontology">GO</abbr> mappings published by EMBL-EBI, which are in turn based on the mapping of predicted domains to the InterPro dataset.
				The InterPro-to-<abbr title="Gene Ontology">GO</abbr> mapping was last updated on <?php echo get_date_from_timestamp(INTERPRO_TO_GO_LAST_UPDATED, true); ?>, while
				the <abbr title="Gene Ontology">GO</abbr> metadata was last updated on <?php echo get_date_from_timestamp(GO_METADATA_LAST_UPDATED, true); ?>.
			</p>
			<?php
				try {
					if(!empty($q3) && $q3->rowCount()) { ?>
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
						<?php while($go = $q3->fetch(PDO::FETCH_ASSOC)) {
							$go_term = $go['GeneOntology'];
							$go_namespace = array(
								'p' => 'Biological process',
								'f' => 'Molecular function',
								'c' => 'Cellular component'
								);
							?>
							<tr>
								<td><?php
									// Generate GO dropdown
									$go_links_handler = new \LotusBase\Component\GODropdown();
									$go_links_handler->internal_link(true);
									$go_links_handler->set_title($go_term);
									$go_links_handler->set_go_term($go_term);
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
												echo '<li><a href="'.WEB_ROOT.'/view/go/'.$_r.'" class="icon-eye">View details of '.$_r.'</a></li>';
											}
											echo '</ul></div>';
										}
									}
								?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					<?php } else {
						throw new Exception('Unable to find any <abbr title="Gene Ontology">GO</abbr> terms for the transcript with the identifier');
					}
				} catch(Exception $e) {
					echo '<p class="user-message warning">'.$e->getMessage().'.</p>';
				}
			?>
		</div>

		<div id="view__jbrowse" class="view__facet">
			<h3>Genome browser</h3>
			<?php
				$jbrowse = array(
					'MG20_3.0' => array(
						'id' => $gene,
						'data' => 'genomes%2Flotus-japonicus%2Fmg20%2Fv3.0'
					),
					'Gifu_1.2' => array(
						'id' => preg_replace('/^(.*?)\.\d+$/', '$1', $gene),
						'data' => 'genomes%2Flotus-japonicus%2Fgifu%2Fv1.2'
					)
				);
			?>
			<iframe name="jbrowse-embed" class="jbrowse-embed" src="<?php echo WEB_ROOT.'/genome/?data='.$jbrowse[$genome]['data'].'&loc='.$jbrowse[$genome]['id'].'&amp;embed=true'; ?>"></iframe>
			<ul class="list--reset cols flex-wrap__nowrap justify-content__flex-start jbrowse__action">
				<li><a href="<?php echo WEB_ROOT.'/genome/?data='.$jbrowse[$genome]['data'].'&loc='.$jbrowse[$genome]['id'].'&amp;embed=true'; ?>" target="jbrowse-embed"><span class="icon-eye">Center view on <strong><?php echo $gene; ?></strong></span></a></li>
				<li><a href="<?php echo WEB_ROOT.'/genome/?data='.$jbrowse[$genome]['data'].'&loc='.$jbrowse[$genome]['id']; ?>"><span class="icon-resize-full">View larger version</span></a></li>
				<li><a href="https://jbrowse.org" title="JBrowse">Powered by JBrowse <span class="icon-link-ext-alt icon--no-spacing"></span></a></li>
			</ul>
		</div>
		
		<?php if ($genome === 'MG20_3.0') { ?>
			<div id="view__lore1-inserts" class="view__facet">
				<h3><em>LORE1</em> insertions<?php
					// Generate lore1 list
					$genic_lore1 = array_filter(explode(',', $g['GenicPlantID']));
					$exonic_lore1 = array_filter(explode(',', $g['ExonicPlantID']));
					$intronic_lore1 = array_diff($genic_lore1, $exonic_lore1);

					// Display count
					if(count($genic_lore1)) {
						echo ' <span class="badge">'.count($genic_lore1).'</span>';
					}
				?></h3>
				<?php if(count($genic_lore1)) {?>
					<form id="lore1-filter__form" action="#" method="get" class="has-group">
						<div class="cols" role="group">
							<label class="col-one" for="lore1-type">Insertion filter</label>
							<div class="col-two">
								<select id="lore1-type" name="lore1_type">
									<option value="genic" selected>Genic (all)</option>
									<option value="intronic">Intronic (only in introns)</option>
									<option value="exonic">Exonic (only in exons)</option>
								</select>
							</div>
						</div>
					</form>
					<ul class="list--floated" id="lore1-list">
					<?php
						foreach($genic_lore1 as $pid) {
							echo '<li class="'.(in_array($pid, $exonic_lore1) ? 'lore1--exonic' : 'lore1--intronic').'"><a class="link--reset" href="'.WEB_ROOT.'/lore1/search?v=MG20_3.0&pid='.$pid.'" title="View details for this line">'.$pid.'</a></li>';
						}
					?>
					</ul>
				<?php } else { ?>
				<p class="user-message">No <em>LORE1</em> insertions have been found in the genomic region that overlaps with this gene.</p>
				<?php } ?>
			</div>
		<?php } ?>

		<div id="view__expression" data-gene="<?php echo $gene; ?>" class="view__facet">
			<h3>Expression data</h3>
			<form id="corgi__form" class="has-group">
				<div class="cols" role="group">
					<label for="expat-dataset" class="col-one">Dataset <a data-modal="wide" class="info" title="What are the available datasets?" href="<?php echo WEB_ROOT; ?>/lib/docs/expat-datasets">?</a></label>
					<div class="col-two">
						<?php
							$expat_dataset = new \Lotusbase\ExpAt\Dataset();
							if(!empty($_GET['dataset'])) {
								$expat_dataset->set_dataset($_GET['dataset']);
							}
							$expat_dataset->set_idType(array(
								'geneid',
								'transcriptid'
								));

							if ($genome === 'MG20_3.0') {
								$selected_dataset = 'ljgea-geneid';
							} else if ($genome === 'Gifu_1.2') {
								$selected_dataset = 'reidd-2020-gifuatlas';
							}

							if ($selected_dataset) {
								$expat_dataset->set_selected_dataset($selected_dataset);
							}
							
							$expat_dataset->set_species('Lotus');
							$expat_dataset->set_genome($genome);
							echo $expat_dataset->render();
						?>
					</div>
				</div>
			</form>

			<h4>Expression pattern</h4>
			<p>Expression pattern of <strong><?php echo $gene; ?></strong>, powered by <a href="<?php echo WEB_ROOT; ?>/expat" title="Expression Atlas">ExpAt</a>. For advanced configuration, data transformation and export options, <a id="view__expat__link" href="<?php echo WEB_ROOT; ?>/expat?ids=<?php echo $gene; ?>&amp;dataset=ljgea-geneid&amp;idtype=geneid" title="" data-root="<?php echo WEB_ROOT; ?>">view expression data in the ExpAt application</a>.</p>
			<div id="expat__loader" class="align-center loader__wrapper">
				<div class="loader"><svg class="loader"><circle class="path" cx="40" cy="40" r="30" /></svg></div>
				<p>Loading expression data from <span id="expat__loader__dataset">ljgea-geneid</span>. Please wait&hellip;</p>
			</div>
			<div id="view__expat" class="hidden"></div>
			
			<?php if ($genome === 'MG20_3.0') { ?>
				<h4>Co-expressed genes</h4>
				<p>A list of the top 25 highly co-expressed genes of <strong><?php echo $gene; ?></strong>, powered by <a href="<?php echo WEB_ROOT; ?>/tools/corgi" title="Co-expressed Genes Identifier (CORGI)">CORGI</a>.</p>
				<div id="coexpression__loader" class="align-center loader__wrapper">
					<div class="loader"><svg class="loader"><circle class="path" cx="40" cy="40" r="30" /></svg></div>
					<p>Loading co-expressed genes from the dataset <span id="coexpression__loader__dataset">ljgea-geneid</span>. This will take 20&ndash;30 seconds to construct.</p>
				</div>
				<table id="coexpression__table" class="table--dense hidden">
					<thead>
						<tr>
							<th scope="col">ID</th>
							<th scope="col">Score</th>
							<th scope="col">Description</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			<?php } ?>
		</div>

	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>

	<!-- Visualisation -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js" integrity="sha384-gOxMGMgqQH8iYyQE8rmgpaokSRE608gSIXXdC2a/yT+OywUqbNmTCQa3qNO4wvyc" crossorigin="anonymous"></script>
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
	<script src="<?php echo WEB_ROOT; ?>/dist/js/view/transcript.min.js"></script>
</body>
</html>