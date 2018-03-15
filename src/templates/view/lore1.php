<?php
	require_once('../config.php');

	try {
		if(!empty($_GET) && !empty($_GET['id'])) {
			$id = escapeHTML($_GET['id']);
			if(!preg_match('/^((DK\d+\-0)?3\d{7}|[apl]\d{4,})$/i', $id)) {
				// If ID fails pattern check
				throw new Exception('Invalid <em>LORE1</em> mutant line identifier. Please ensure that it is a valid format&mdash;an eight digit number starting with <code>3</code> for Danish lines (e.g. <code>30010101</code> or <code>DK02-030010101</code>), or an alphabet (A, L, or P) followed by five digits for Japanese lines (e.g. <code>A00005</code>).');
			} else {
				// Coerce LORE1 ID if necessary
				$id = preg_replace('/^DK\d+\-0(.*)/i', '$1', $id);
			}

			// Check if LORE1 line exists
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			// Fetch line data
			$q1 = $db->prepare("SELECT
				lore.PlantID,
				lore.Batch,
				lore.CoordList
				FROM lore1ins AS lore
				WHERE lore.PlantID = ? AND lore.Version = '3.0'
				GROUP BY lore.PlantID
				LIMIT 1
				");
			$q1->execute(array($id));
			if(!$q1->rowCount()) {
				throw new Exception('<em>LORE1</em> line does not exist. Please ensure that you have entered a valid line identifier.');
			}
			$q1_data = $q1->fetch(PDO::FETCH_ASSOC);

			// Fetch insertion data
			$q2 = $db->prepare("SELECT
					lore.PlantID,
					lore.Batch,
					lore.ColCoord,
					lore.RowCoord,
					lore.Chromosome,
					lore.Position,
					lore.Orientation,
					lore.CoordList,
					lore.CoordCount,
					lore.TotalCoverage,
					lore.FwPrimer,
					lore.RevPrimer,
					lore.PCRInsPos,
					lore.PCRWT,
					GROUP_CONCAT(DISTINCT gene.Gene ORDER BY gene.Gene) AS Gene,
					GROUP_CONCAT(DISTINCT exon.Gene ORDER BY exon.Gene) AS Exon,
					CASE
						WHEN GROUP_CONCAT(DISTINCT gene.Gene ORDER BY gene.Gene) IS NOT NULL AND GROUP_CONCAT(DISTINCT exon.Gene ORDER BY exon.Gene) IS NULL THEN 'Intronic'
						WHEN GROUP_CONCAT(DISTINCT exon.Gene ORDER BY exon.Gene) IS NOT NULL THEN 'Exonic'
						WHEN GROUP_CONCAT(DISTINCT gene.Gene ORDER BY gene.Gene) IS NULL AND GROUP_CONCAT(DISTINCT exon.Gene ORDER BY exon.Gene) IS NULL THEN 'Intergenic'
						ELSE 'Other'
					END AS Type
				FROM lore1ins AS lore
				LEFT JOIN geneins AS gene ON (
					lore.Chromosome = gene.Chromosome AND
					lore.Position = gene.Position AND
					lore.Orientation = gene.Orientation AND
					lore.Version = gene.Version
				)
				LEFT JOIN exonins AS exon ON (
					lore.Chromosome = exon.Chromosome AND
					lore.Position = exon.Position AND
					lore.Orientation = exon.Orientation AND
					lore.Version = exon.Version
				)
				WHERE lore.PlantID = ? AND lore.Version = '3.0' GROUP BY lore.Salt");
			$q2->execute(array($id));

			// Store data
			$q2_data = array();
			$insertion_counts = array(
				'type' => array('Exonic' => 0, 'Intronic' => 0, 'Intergenic' => 0),
				'chromosome' => array()
				);
			while($r = $q2->fetch(PDO::FETCH_ASSOC)) {
				$q2_data[] = $r;

				// Store insertion types
				$insertion_counts['type'][$r['Type']] += 1;

				// Store by chromosome counts
				if(!array_key_exists($r['Chromosome'], $insertion_counts['chromosome'])) {
					$insertion_counts['chromosome'][$r['Chromosome']] = 1;
				} else {
					$insertion_counts['chromosome'][$r['Chromosome']] += 1;
				}
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
	<title>LORE1 &mdash; View &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Consolidated LORE1 mutant view: '.$id
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" integrity="sha384-wCtV4+Y0Qc1RNg341xqADYvciqiG4lgd7Jf6Udp0EQ0PoEv83t+MLRtJyaO5vAEh" crossorigin="anonymous">
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/view.min.css" type="text/css" media="screen" />
</head>
<body class="view lore1 init-scroll--disabled">

	<?php

		// Page header
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		// Breadcrumb
		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_crumbs(array(
			'View' => 'view',
			'<em>LORE1</em>' => 'lore1',
			$id => $id
		));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2><?php echo $id; ?></h2>
		<span class="byline">View in <a href="<?php echo WEB_ROOT.'/lore1/search?pid='.$id.'&v=3.0'; ?>"><em>LORE1</em> search tool</a></span>

		<div id="view__card" class="view__facet">
			<h3>Overview</h3>
			<p>The <em>LORE1</em> mutant line <strong><?php echo $id; ?></strong> is from the <?php echo (strpos($q1_data['Batch'], 'DK') > -1 ? 'Danish' : 'Japanese'); ?> batch <?php echo $q1_data['Batch']; ?>, containing <?php echo $q2->rowCount().pl($q2->rowCount(), ' identified insertion'); ?> across the genome. The positions of each insert, <strong>excluding those found in chromosome 0, chloroplast, or mitochondria</strong>, are displayed in the chromosome map below:</p>
			<div class="d3-chart"><svg id="lore1-by-chromosome" data-lore1="<?php echo $id; ?>"></svg></div>
		</div>

		<div id="view__jbrowse" class="view__facet">
			<h3>Genome browser</h3>
			<?php
				// Get coordinates of first insertion
				$ins_coord = $q2_data[0]['Chromosome'].':'.$q2_data[0]['Position'];
			?>
			<p>You are currently viewing the insertion at <strong><span id="current-insertion"><?php echo $ins_coord; ?></span></strong>.</p>
			<p>The dispersed location of <em>LORE1</em> inserts mean that only one insertion is typically present in the genomic interval shown in JBrowse. To navigate between the different insertions, please select an insertion using the dropdown menu under the JBrowse window.</p>
			<iframe name="jbrowse-embed" class="jbrowse-embed" src="<?php echo WEB_ROOT.'/genome/?data=genomes%2Flotus-japonicus%2Fv3.0&amp;loc='.urlencode($ins_coord).'&amp;embed=true'; ?>"></iframe>
			<ul class="list--reset cols flex-wrap__nowrap justify-content__flex-start jbrowse__action">
				<li><div><label for="insertion-dropdown">Select insertion:</label><select id="insertion-dropdown"><?php
					// Generate dropdown options for all insertion positions
					// Bin by chromosome
					$insertions_by_chr = array();
					foreach($q2_data as $ins) {
						if(!array_key_exists($ins['Chromosome'], $insertions_by_chr)) {
							$insertions_by_chr[$ins['Chromosome']] = array($ins);
						} else {
							$insertions_by_chr[$ins['Chromosome']][] = $ins;
						}
					}

					ksort($insertions_by_chr);
					foreach($insertions_by_chr as $chr => $chr_ins) {
						echo '<optgroup label="'.$chr.'">';

						// Sort nested arrays by position
						usort($chr_ins, function($a, $b) {
							return $a['Position'] - $b['Position'];
						});

						foreach($chr_ins as $ins) {
							echo '<option value="'.$ins['Chromosome'].':'.$ins['Position'].'">'.$ins['Chromosome'].':'.$ins['Position'].' ('.($ins['Orientation'] === 'F' ? 'Forward' : 'Reverse').', '.$ins['Type'].($ins['Type'] !== 'Intergenic' ? ': '.(count(explode(',', $ins['Gene'])) > 1 ? count(explode(',', $ins['Gene'])).' genes' : $ins['Gene']) : '').')</option>';
						}
						echo '</optgroup>';
					}
				?></select></div></li>
				<li><a href="<?php echo WEB_ROOT.'/genome/?loc='.urlencode($ins_coord); ?>"><span class="icon-resize-full">View larger version</span></a></li>
				<li><a href="https://jbrowse.org" title="JBrowse">Powered by JBrowse <span class="icon-link-ext-alt icon--no-spacing"></span></a></li>
			</ul>
		</div>

		<div id="view__insertion-stats" class="view__facet">
			<h3>Insertion statistics</h3>

			<div id="view__insertion-stats__tabs">
				<div id="view__insertion-stats__nav" class="cols align-items__flex-end ui-tabs-nav__wrapper">
					<ul class="tabbed">
						<li><a href="#view__insertion-stats__by-type" data-custom-smooth-scroll>By type</a></li>
						<li><a href="#view__insertion-stats__by-chromosome" data-custom-smooth-scroll>By chromosome</a></li>
					</ul>
				</div>

				<div class="view__insertion-stats__tab" id="view__insertion-stats__by-type">
					<p>Insertions can be classified as intergenic or genic, the latter of which can be further subdivided into exonic or intronic in nature.</p>
					<ul class="cols flex-wrap__nowrap"><?php
						foreach($insertion_counts['type'] as $type => $count) {
							echo '<li><span class="count">'.$count.'</span><span class="desc">'.$type.'</span></li>';
						}
					?>
						<li><span class="count"><?php echo $q2->rowCount(); ?></span><span class="desc">Total</span></li>
					</ul>
				</div>

				<div class="view__insertion-stats__tab" id="view__insertion-stats__by-chromosome">
					<p>Distribution of insertions among <em>Lotus</em> v3.0 chromosomes.</p>
					<ul class="cols flex-wrap__nowrap"><?php
						ksort($insertion_counts['chromosome']);
						foreach($insertion_counts['chromosome'] as $chromosome => $count) {
							echo '<li><span class="count">'.$count.'</span><span class="desc">'.$chromosome.'</span></li>';
						}
					?>
						<li><span class="count"><?php echo $q2->rowCount(); ?></span><span class="desc">Total</span></li>
					</ul>
				</div>
			</div>
		</div>

		<div id="view__lore1ins" class="view__facet">
			<h3>Insertion data<?php
				if($q2->rowCount()) {
					echo ' <span class="badge">'.$q2->rowCount().'</span>';
				}
			?></h3>
			<p>The table is wide and may contain columns that are only visible after horizontal scrolling. To view the data in its entirety, we recommend exporting the table as a CSV file.</p>
			<table class="table--dense">
				<thead>
					<tr>
						<th scope="col"><abbr title="Chromosome">Chr</abbr></th>
						<th scope="col"><abbr title="Position">Pos</abbr></th>
						<th scope="col"><abbr title="Orientation">Orn</abbr></th>
						<th scope="col">Gene</th>
						<th scope="col">Transcript</th>
						<th scope="col">Type</th>
						<th scope="col">Total coverage</th>
						<th scope="col">Forward primer</th>
						<th scope="col">Reverse primer</th>
						<th scope="col">PCR size for WT</th>
						<th scope="col">PCR size for insert</th>
						<th scope="col"><abbr title="Coordinates">Coord</abbr></th>
						<th scope="col">Coordinate list</th>
						<th scope="col">Coordinate count</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($q2_data as $r) { ?>
					<tr>
						<td><?php echo $r['Chromosome']; ?></td>
						<td><?php echo $r['Position']; ?></td>
						<td><?php echo $r['Orientation']; ?></td>
						<td><?php
							if(!empty($r['Gene'])) {
								$genes = explode(',', $r['Gene']);
								foreach($genes as $gene) {
									echo '<a href="'.WEB_ROOT.'/view/gene/'.$gene.'" title="View details on '.$gene.'">'.$gene.'</a> ';
								}
							} else {
								echo '&ndash;';
							}
						?></td>
						<td><?php
							if(!empty($r['Exon'])) {
								$transcripts = explode(',', $r['Exon']);
								foreach($transcripts as $transcript) {
									echo '<a href="'.WEB_ROOT.'/view/transcript/'.$transcript.'" title="View details on '.$transcript.'">'.$transcript.'</a> ';
								}
							} else {
								echo '&ndash;';
							}
						?></td>
						<td><?php echo $r['Type']; ?></td>
						<td><?php echo $r['TotalCoverage']; ?></td>
						<td><?php echo $r['FwPrimer']; ?></td>
						<td><?php echo $r['RevPrimer']; ?></td>
						<td><?php echo $r['PCRWT']; ?></td>
						<td><?php echo $r['PCRInsPos']; ?></td>
						<td><?php echo $r['RowCoord'].','.$r['ColCoord']; ?></td>
						<td><?php echo $r['CoordList']; ?></td>
						<td><?php echo $r['CoordCount']; ?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
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

	<!-- Select2 -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js" integrity="sha384-iViGfLSGR6GiB7RsfWQjsxI2sFHdsBriAK+Ywvt4q8VV14jekjOoElXweWVrLg/m" crossorigin="anonymous"></script>

	<!-- Functions -->
	<script src="<?php echo WEB_ROOT; ?>/dist/js/view/lore1.min.js"></script>
</body>
</html>