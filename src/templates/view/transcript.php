<?php
	require_once('../config.php');

	try {
		if(!empty($_GET) && !empty($_GET['id'])) {
			$id = $_GET['id'];
			if(preg_match('/^Lj(\d|chloro}mito)g3v(\d+)$/', $id)) {
				// If gene pattern is match, redirect to gene page
				header('Location:'.WEB_ROOT.'/view/gene/'.$id);
				exit();
			} else if(!preg_match('/^Lj(\d|chloro}mito)g3v(\d+)\.\d+$/', $id)) {
				// If ID fails pattern check
				$_SESSION['view_error'] = 'Invalid transcript ID format detected. Please ensure that your transcript follows the format <code>Lj{chr}g{version}v{id}</code>.';
				throw new Exception;
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
	<title>Gene Browser &mdash; Tools &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/viewer.min.css" type="text/css" media="screen" />
</head>
<body class="viewer gene init-scroll--disabled">
	<?php
		$header = new \LotusBase\PageHeader();
		echo $header->get_header();
	?>

	<?php echo get_breadcrumbs(); ?>

	<section class="wrapper">
	<?php
		// Coerge gene ID to transcript ID
		$coerced = false;
		if(preg_match('/^Lj(\d|chloro}mito)g3v(\d+)\.\d+$/', $_GET['id'])) {
			$gene = $_GET['id'];
		} else {
			$gene = $_GET['id'].'.1';
			$coerced = true;
		}
		
		?>
		<h2><?php echo $gene; ?></h2>
		<?php
			if($coerced) {
				echo '<p class="user-message"><span class="icon-attention"></span>We have converted your gene (<strong>'.$_GET['id'].'</strong>) to a specific isoform (<strong>'.$gene.'</strong>).</p>';
			}
		?>
		<div id="view__card">
			<h3>Overview</h3>
			<?php
				try {
					$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

					$q1 = $db->prepare("SELECT
							anno.Gene AS Transcript,
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
							anno.Version = tc.Version
						)
						LEFT JOIN transcriptcoord AS isoforms ON (
							tc.Gene = isoforms.Gene
						)
						LEFT JOIN exonins AS exon ON (
							tc.Transcript = exon.Gene AND
							tc.Version = exon.Version
						)
						LEFT JOIN geneins AS genic ON (
							tc.Gene = genic.Gene AND
							tc.Version = genic.Version
						)
						LEFT JOIN lore1ins AS geniclore1 ON (
							genic.Chromosome = geniclore1.Chromosome AND
							genic.Position = geniclore1.Position AND
							genic.Orientation = geniclore1.Orientation AND
							genic.Version = geniclore1.Version
						)
						LEFT JOIN lore1ins AS exoniclore1 ON (
							exon.Chromosome = exoniclore1.Chromosome AND
							exon.Position = exoniclore1.Position AND
							exon.Orientation = exoniclore1.Orientation AND
							exon.Version = exoniclore1.Version
						)
						WHERE anno.Gene = ?
						LIMIT 1");
					$q1->execute(array($gene));

					if($q1->rowCount()) {
						$g = $q1->fetch(PDO::FETCH_ASSOC);

						?>
						<table class="table--dense">
							<thead>
								<tr>
									<th>Field</th>
									<th>Value</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<th>Gene ID</th>
									<td><?php echo $g['Gene']; ?></td>
								</tr>
								<tr>
									<th>Transcript ID</th>
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
									<th><em>Lotus japonicus</em> genome version</th>
									<td><?php echo $g['Version']; ?></td>
								</tr>
								<tr>
									<th>Description</th>
									<td><?php echo preg_replace('/\[([\w\s]+)\]?/', '[<em>$1</em>]', $g['Annotation']); ?></td>
								</tr>
								<tr>
									<th>Working <em>Lj</em> name</th>
									<td><em><?php echo !empty($g['LjAnnotation']) ? $g['LjAnnotation'] : 'n.a.'; ?></em></td>
								</tr>
							</tbody>
						</table>
						<?php
					} else {
						throw new Exception('Unable to find any records for the transcript with the identifer <strong>'.$gene.'</strong>');
					}


					$q2 = $db->prepare("SELECT
						ip.Source AS Source,
						ip.SourceID AS SourceID,
						ip.SourceDescription AS SourceDescription,
						ip.DomainStart AS DomainStart,
						ip.DomainEnd AS DomainEnd,
						ip.Evalue AS Evalue,
						CASE
							WHEN ip.InterproID IS NULL THEN 'Unassigned'
							ELSE ip.InterproID
						END AS InterproID
					FROM interpro AS ip
					WHERE ip.Transcript = ?
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
							$ip_grouped[$item['InterproID']][] = $item;
							if(!in_array($item['InterproID'], $ip_unique) && $item['InterproID'] !== 'Unassigned') {
								$ip_unique[] = $item['InterproID'];
							}
						}

						// Get Interpro data
						$ip_handler = new \LotusBase\View\Interpro();
						$ip_handler->set_ids($ip_unique);
						$ip_data = $ip_handler->get_data();
					}

				} catch(PDOException $e) {
					echo '<p class="user-message warning">We have encountered an error with querying the database: '.$e->getMessage().'.</p>';
				} catch(Exception $e) {
					echo '<p class="user-message warning">'.$e->getMessage().'.</p>';
				}
			?>
		</div>

		<div id="view__sequence">
			<?php
				$sequenceDB_metadata = array(
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
					)
			?>
			<div id="sequence-tabs__nav" class="cols align-items__flex-end ui-tabs-nav__wrapper">
				<h3>Sequence information</h3>
				<ul class="minimal">
				<?php
					foreach($sequenceDB_metadata as $id => $db) {
						echo '<li><a href="#sequence-tabs__'.$id.'" data-custom-smooth-scroll>'.$db['title'].'</a></li>';
					}
				?>
				</ul>
			</div>

			<?php foreach($sequenceDB_metadata as $id => $db) {
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
						$sequence->set_strand('plus');

						// Display info
						echo $g['Chromosome'].':'.$g['Start'].'..'.$g['End'];

					} else if($db['database']['type'] === 'transcript') {
						$sequence_id = $gene;

						$sequence->set_id($sequence_id);
						$sequence->set_database($db['database']['file']);
						$sequence->set_strand($strand);

						// Display info
						echo $gene;

					} else {
						throw new Exception('Invalid database type detected.');
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

		<div id="view__domain-prediction">
			<h3>Domain prediction</h3>
			<?php
				try {
					if($q2->rowCount()) {
						?>
						<svg id="domain-prediction" data-protein="<?php echo $gene; ?>"></svg>
						<table class="table--dense">
							<thead>
								<tr>
									<th>Prediction</th>
									<th>Identifier</th>
									<th>Description</th>
									<th data-type="num">Start</th>
									<th data-type="num">End</th>
									<th data-type="num">E-value</th>
									<th>Interpro ID</th>
									<th>Gene Ontology</th>
								</tr>
							</thead>
							<tbody>
							<?php
								foreach($_ip as $key => $item) {
									echo '<tr>';
									foreach($item as $field_name => $field_value) {
										if($field_name !== 'InterproID') {
											echo '<td data-type="'.(in_array($field_name, array('DomainStart','DomainEnd','Evalue')) ? 'numeric' : '').'">'.$field_value.'</td>';
										} else {
											$go_annotations = $ip_data['data'][$field_value]['fields']['GO'];
											echo '<td>'.$field_value.'</td>';
											if(empty($go_annotations)) {
												echo '<td>n.a.</td>';
											} else {
												echo '<td>'.implode(',',$go_annotations).'</td>';
											}
										}
									}
									echo '</tr>';
								} ?>
							</tbody>
						</table>
						<?php

					} else {
						throw new Exception('Unable to find any records for the transcript with the identifer <strong>'.$gene.'</strong>');
					}
				} catch(PDOException $e) {
					echo '<p class="user-message warning">We have encountered an error with querying the database: '.$e->getMessage().'.</p>';
				} catch(Exception $e) {
					echo '<p class="user-message warning">'.$e->getMessage().'.</p>';
				}
			?>
		</div>

		<div id="view__jbrowse">
			<h3>Genome browser</h3>
			<iframe name="jbrowse-embed" class="jbrowse-embed" src="<?php echo WEB_ROOT.'/genome/?loc='.$gene.'&amp;embed=true'; ?>"></iframe>
			<ul class="list--reset cols flex-wrap__nowrap justify-content__flex-start jbrowse__action">
				<li><a href="<?php echo WEB_ROOT.'/genome/?loc='.$gene.'&amp;embed=true'; ?>" target="jbrowse-embed"><span class="icon-eye">Center view on <strong><?php echo $gene; ?></strong></span></a></li>
				<li><a href="<?php echo WEB_ROOT.'/genome/?loc='.$gene; ?>"><span class="icon-resize-full">View larger version</span></a></li>
				<li><a href="https://jbrowse.org" title="JBrowse">Powered by JBrowse <span class="icon-link-ext-alt icon--no-spacing"></span></a></li>
			</ul>
		</div>

		<div id="view__lore1-inserts">
			<h3>LORE1 insertions <?php
				// Generate lore1 list
				$genic_lore1 = explode(',', $g['GenicPlantID']);
				$exonic_lore1 = explode(',', $g['ExonicPlantID']);
				$intronic_lore1 = array_diff($genic_lore1, $exonic_lore1);

				// Display count
				if(count($genic_lore1)) {
					echo '<span class="badge">'.count($genic_lore1).'</span>';
				}
			?></h3>
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
			<?php	
				if(count($genic_lore1)) {
					echo '<ul class="list--floated" id="lore1-list">';
					foreach($genic_lore1 as $pid) {
						echo '<li class="'.(in_array($pid, $exonic_lore1) ? 'lore1--exonic' : 'lore1--intronic').'"><a class="link--reset" href="'.WEB_ROOT.'/lore1/search?v=3.0&pid='.$pid.'" title="View details for this line">'.$pid.'</a></li>';
					}
					echo '</ul>';
				}
			?>
		</div>

		<div id="view__expression" data-gene="<?php echo $gene; ?>">
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
							$expat_dataset->set_selected_dataset('ljgea-geneid');
							echo $expat_dataset->render();
						?>
					</div>
				</div>
			</form>

			<h4>Expression pattern</h4>
			<p>Expression pattern of <strong><?php echo $gene; ?></strong>, powered by <a href="<?php echo WEB_ROOT; ?>/expat" title="Expression Atlas">ExpAt</a>. For advanced configuration, data transformation and export options, <a id="view__expat__link" href="<?php echo WEB_ROOT; ?>/expat?ids=<?php echo gene; ?>&amp;dataset=ljgea-geneid&amp;idtype=geneid" title="" data-root="<?php echo WEB_ROOT; ?>">view expression data in the ExpAt application</a>.</p>
			<div id="expat__loader" class="align-center loader__wrapper">
				<div class="loader"><svg class="loader"><circle class="path" cx="40" cy="40" r="30" /></svg></div>
				<p>Loading expression data from <span id="expat__loader__dataset">ljgea-geneid</span>. Please wait&hellip;</p>
			</div>
			<div id="view__expat" class="hidden"></div>
			
			<h4>Co-expressed genes</h4>
			<p>A list of the top 25 highly co-expressed genes of <strong><?php echo $gene; ?></strong>, powered by <a href="<?php echo WEB_ROOT; ?>/tools/corgi" title="Co-expressed Genes Identifier (CORGI)">CORGI</a>.</p>
			<div id="coexpression__loader" class="align-center loader__wrapper">
				<div class="loader"><svg class="loader"><circle class="path" cx="40" cy="40" r="30" /></svg></div>
				<p>Loading co-expressed genes from the dataset <span id="coexpression__loader__dataset">ljgea-geneid</span>. This will take 20&ndash;30 seconds to construct.</p>
			</div>
			<table id="coexpression__table" class="table--dense hidden">
				<thead>
					<tr>
						<th>ID</th>
						<th>Score</th>
						<th>Annotation</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/colorbrewer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/d3-tip.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/gene.min.js"></script>
</body>
</html>