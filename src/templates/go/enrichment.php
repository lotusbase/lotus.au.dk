<?php
	require_once('../config.php');

	$error = false;
	$searched = false;

	$allowed_go_namespaces = array(
		'c' => 'Cellular component',
		'p' => 'Biological process',
		'f' => 'Molecular function'
		);

	$corrections = array(
		'none' => 'None (not recommended)',
		'bonferroni' => 'Bonferroni',
		'bh' => 'Benjamini-Hochberg (default)'
		);

	if(!empty($_GET) && !empty($_GET['ids']) && !empty($_GET['genome'])) {
		$ids = !is_array($_GET['ids']) ? explode(',', $_GET['ids']) : $_GET['ids'];
		$ids = array_values(array_unique($ids));

		$correction = !empty($_GET['correction']) &&  in_array($_GET['correction'], $corrections) ? $_GET['correction'] : 'bh';

		$go_namespaces = array_intersect(array_keys($allowed_go_namespaces), $_GET['go_namespace_subset']);

		try {

			// Initiate time count
			$start_time = microtime(true);

			// Validate genome
			$genome_checker = new \LotusBase\LjGenomeVersion(array('genome' => $_GET['genome']));
			$genome = $genome_checker->check();
			if (!$genome) {
				throw new Exception('Invalid genome provided');
			}

			$genome_parts = explode('_', $genome);
			$ecotype = $genome_parts[0];
			$version = $genome_parts[1];

			// Check if any of the ids match
			foreach($ids as &$id) {
				if(
					preg_match('/^Lj(\d|chloro|mito)g\dv\d+(\.\d+)?$/', $id) ||
					preg_match('/^LotjaGi\dg\dv\d+?(_LC)?(\.\d+)?$/', $id)
				) {
					if(
						!preg_match('/^Lj(\d|chloro|mito)g\dv\d+\.\d+$/', $id) &&
						!preg_match('/^LotjaGi\dg\dv\d+?(_LC)?\.\d+$/', $id)
					) {
						$id = $id.".1";
					}
					$valid_ids[] = $id;
				}
			}
			if(empty($valid_ids)) {
				throw new Exception('None of the identifiers provided are valid');
			}

			// Retrieve all GO terms and the counts associated
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			$q1 = $db->prepare("SELECT
				logo1.GO_ID AS GOTerm,
				GROUP_CONCAT(DISTINCT logo1.Transcript) AS QueryList,
				logo2.TranscriptCount AS MappedCount,
				go.Namespace AS Namespace,
				go.Name AS Name
				FROM gene_ontology_lotus AS logo1
				LEFT JOIN gene_ontology AS go ON
					logo1.GO_ID = go.GO_ID
				LEFT JOIN gene_ontology_lotus_by_goID AS logo2 ON 
					logo1.GO_ID = logo2.GO_ID
				WHERE
					logo1.GO_ID IS NOT NULL AND
					go.Namespace IN (".str_repeat('?,', count($go_namespaces)-1)."?) AND
					logo1.Transcript IN (".str_repeat('?,', count($ids)-1)."?) AND
					logo1.Ecotype = ? AND
					logo1.Version = ?
				GROUP BY logo1.GO_ID");
			$q1->execute(array_merge($go_namespaces, $ids, array($ecotype, $version)));

			$id_counts = array(
				'MG20_3.0' => 98305,
				'Gifu_1.2' => 49868
			);
			$id_count = $id_counts[$genome];

			if(!$q1->rowCount()) {
				throw new Exception('There are no GO terms mapped to the '.pl(count($ids), 'identifier').' you have provided.');
			} else {
				$searched = true;
			}

			// Convert to scientific notation when a threshold is met
			function sn($f, $threshold = 0.01) {
				return $f > $threshold ? number_format($f, '2', '.', '') : sprintf('%.2e', $f);
			}

			// List of GO roots to exclude
			$go_exclude = array('GO:0003674','GO:0008150','GO:0005575');




			// Process data
			$data = array();
			$scipy_data = array('settings' => array(
				'correction' => $correction
				));

			while($r = $q1->fetch(PDO::FETCH_ASSOC)) {


				// Query Count
				$query_list = explode(',', $r['QueryList']);
				$r['QueryCount'] = count($query_list);



				// Compute data
				$r['QueryIDHasTerm'] = $r['QueryCount'];
				$r['DatasetIDHasTerm'] = $r['MappedCount'];
				$r['QueryIDHasTermNot'] = count($ids) - $r['QueryCount'];
				$r['DatasetIDHasTermNot'] = $id_count - $r['MappedCount'];

				// Pass data to SciPy
				$scipy_data['go_data'][$r['GOTerm']] = array(
					'data' => array(
						'queryCount' => $r['QueryCount'],
						'mappedCount' => $r['MappedCount']
						),
					'matrix' => array(
						array($r['QueryIDHasTerm'], $r['DatasetIDHasTerm']),
						array($r['QueryIDHasTermNot'], $r['DatasetIDHasTermNot'])
						)
					);

				// Push row to data array for display later
				$data[] = $r;
			}



			// Compute Scipy
			$temp_file = tempnam(sys_get_temp_dir(), "go-enrichment_");
			if($writing = fopen($temp_file, 'w')) {
				fwrite($writing, json_encode($scipy_data));
			}
			fclose($writing);
			$scipy_output = exec(PYTHON_PATH.' '.DOC_ROOT.'/lib/go/go-enrichment.py '.$temp_file);
			unlink($temp_file);

			// Define GO namespace
			$go_namespace = array(
				'p' => 'Biological process',
				'f' => 'Molecular function',
				'c' => 'Cellular component'
				);

		} catch(PDOException $e) {
			$error = 'We have encountered an issue with the database query: '.$e->getMessage();
		} catch(Exception $e) {
			$error = $e->getMessage();
		}
	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>GO Enrichment &mdash; Tools &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Compute gene ontology (GO) term enrichment using a list of genes/transcripts.'
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/tools.min.css" type="text/css" media="screen" />
</head>
<body class="tools <?php echo (!$error && $searched) ? 'results' : ''; ?> init-scroll--disabled">

	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<div class="align-center">
			<h1>GO Enrichment</h1>
			<span class="byline"><abbr title="Gene Ontology">GO</abbr> term enrichment analysis for <em>L. japonicus</em>.</span>
		</div>');
		$header->set_header_background_image(WEB_ROOT.'/dist/images/header/go/go01.jpg');
		echo $header->get_header();

		// Breadcrumb
		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_crumbs(array(
			'Gene Ontology' => 'go',
			'Enrichment' => 'enrichment'
		));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<?php
			if($error) {
				echo '<p class="user-message warning"><span class="icon-attention"></span>'.$error.'</p>';
			}
			echo $searched ? '<div class="toggle'.(empty($error) ? ' hide-first' : '').'"><h3><a href="#" title="Repeat Search">Repeat Search</a></h3>' : '';
		?>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="has-group">
			<div class="cols" role="group">
				<label for="ids-input" class="col-one">Query <a href="<?php echo WEB_ROOT; ?>/lib/docs/trex-query" class="info" title="How should I look for my gene of interest?" data-modal="wide">?</a></label>
				<div class="col-two">
					<div class="multiple-text-input input-mimic">
						<ul class="input-values">
						<?php
							if(!empty($_GET['ids'])) {
								if(is_array($_GET['ids'])) {
									$id_array = $_GET['ids'];
								} else {
									$id_array = explode(",", $_GET['ids']);
								}
								foreach($id_array as $id_item) {
									echo '<li data-input-value="'.escapeHTML($id_item).'" class="'.(!preg_match('/^(Lj(\d|chloro|mito)g\dv\d+(\.\d+)?|LotjaGi\dg\dv\d+?(_LC)?(\.\d+)?)$/', $id_item) ? 'warning' : '').'">'.escapeHTML($id_item).'<span class="icon-cancel" data-action="delete"></span></li>';
								}
							}
						?>
							<li class="input-wrapper"><input type="text" id="ids-input" placeholder="Keyword, or gene/transcript ID" autocomplete="off" autocorrect="off"  autocapitalize="off" spellcheck="false" data-boolean-mode="true" /></li>
						</ul>
						<input class="input-hidden" type="hidden" name="ids" id="ids" value="<?php echo (!empty($_GET['ids'])) ? (is_array($_GET['ids']) ? implode(',', preg_replace('/\"/', '&quot;', escapeHTML($trx_array))) : preg_replace('/\"/', '&quot;', escapeHTML($_GET['ids']))) : ''; ?>" readonly />
					</div>
					<small><strong>Separate each keyword, or gene/transcript ID, with a comma, space, or tab.</strong></small>
					<br />
					<small><strong>Unsure what to do? <a href="#" id="sample-data" data-ids="Lj4g3v0281040 Lj4g3v2139970 Lj2g3v0205600 Lj1g3v0414750 Lj0g3v0249089 Lj4g3v2775550 Lj0g3v0245539 Lj3g3v2693010 Lj2g3v1105370 Lj4g3v1736080 Lj4g3v2573630 Lj1g3v2975920 Lj6g3v1052420">Try a sample data</a> from Mun et al., 2016.</strong></small>
				</div>

				<label for="genome-version" class="col-one">Genome version <a href="<?php echo WEB_ROOT.'/lib/docs/lj-genome-versions';?>" class="info" data-modal="search-help" title="What does the genome version mean?">?</a></label>
				<div class="col-two field__version">
					<select name="genome" id="genome-version">
						<option value="" <?php echo (isset($_GET['genome']) && empty($_GET['genome'])) ? "selected" : ""; ?>>Select genome version</option>
						<?php foreach($lj_genome_versions as $label => $lj_genome) {
							$lj_genome_id = implode('_', [$lj_genome['ecotype'], $lj_genome['version']]);
							$lj_genome_version = $lj_genome['version'];

							// NOTE: We disable LORE1 search for Gifu v1.2 for now, because data is not yet available
							if ($lj_genome_id === 'MG20_2.5') {
								continue;
							}

							echo '<option value="'.$lj_genome_id.'" '.(isset($_GET['genome']) && !empty($_GET['genome']) && strval($_GET['genome']) === $lj_genome_id ? 'selected' : '').' '.($disabled ? 'disabled' : '').'>'.$label.'</option>';
						} ?>
					</select>
				</div>

				<label class="col-one" for="correction">p-value correction</label>
				<div class="col-two">
					<select id="correction" name="correction">
					<?php
						foreach($corrections as $c_key => $c_name) {
							echo '<option value="'.$c_key.'" '.(!empty($_GET['correction']) && $_GET['correction'] === $c_key ? 'selected': ($c_key === 'bh' ? 'selected': 'none')).'>'.$c_name.'</option>';
						}
					?>
					</select>
				</div>

				<label class="col-one">Namespace subset</label>
				<div class="col-two">
				<?php
					foreach($allowed_go_namespaces as $n_key => $n_name) {
						$n_label = str_replace(' ', '-', strtolower($n_name));
						echo '<label for="go-namespace__'.$n_label.'"><input id="go-namespace__'.$n_label.'" value="'.$n_key.'" name="go_namespace_subset[]" type="checkbox" class="prettify" '.(!empty($_GET['go_namespace_subset'] && !in_array($n_key, $_GET['go_namespace_subset'])) ? '' : 'checked').' /><span>'.$n_name.'</span></label>';
					}
				?>
				</div>
			</div>
			<button type="submit"><span class="icon-search">Search</span></button>
		</form>
		<?php
			echo $searched ? '</div>' : '';

			if($searched && empty($scipy_output)) {
				// Warn if SciPy did not return any data
				echo '<div class="user-message warning">Fisher\'s exact test has failed to be executed. No p-values were computed.</div>';
			} else {
				// Process output
				$scipy = json_decode($scipy_output, true);
			}

			// Process results
			if($searched && !$error) {

				$rows = $q1->rowCount();
				echo '<p>We have found <strong>'.$rows.'</strong> unique GO '.pl($rows, 'term').' mapped to your list of <strong>'.count($ids).'</strong> '.pl(count($ids), 'identifier').'. This search has taken <strong>'.number_format((microtime(true) - $start_time), 3).'s</strong> to perform.</p>';
		?>
		<table id="go-enrichment" class="table--dense">
			<thead>
				<tr>
					<th colspan="3" class="align-center">Gene Ontology</th>
					<th colspan="2" class="align-center">In query (observed)</th>
					<th colspan="2" class="align-center">In dataset (expected)</th>
					<th scope="col" rowspan="2" data-type="numeric">Enrichment</th>
					<th scope="col" rowspan="2" data-type="numeric">p-value</th>
				</tr>
				<tr>
					<th scope="col">Term</th>
					<th scope="col">Namespace</th>
					<th scope="col">Name</th>
					<th scope="col" data-type="numeric">Count</th>
					<th scope="col" data-type="numeric">Freq.</th>
					<th scope="col" data-type="numeric">Count</th>
					<th scope="col" data-type="numeric">Freq.</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($data as $d) { ?>
				<tr>
					<td><?php
						// Generate GO dropdown
						$go_links_handler = new \LotusBase\Component\GODropdown();
						$go_links_handler->internal_link(true);
						$go_links_handler->set_title($d['GOTerm']);
						$go_links_handler->set_go_term($d['GOTerm']);
						echo $go_links_handler->get_html();
					?></td>
					<td><?php echo $go_namespace[$d['Namespace']];
					?></td>
					<td><?php echo $d['Name']; ?></td>
					<td data-type="numeric"><?php echo $d['QueryCount']; ?></td>
					<td data-type="numeric"><?php echo sn($d['QueryCount'] / count($ids)) ?></td>
					<td data-type="numeric"><?php echo $d['MappedCount']; ?></td>
					<td data-type="numeric"><?php echo sn($d['MappedCount'] / $id_count); ?></td>
					<td data-type="numeric"><?php echo number_format(($d['QueryCount'] / count($ids))/($d['MappedCount'] / $id_count), '2', '.', ''); ?></td>
					<td data-type="numeric"><?php
						if(!empty($scipy_output)) {
							$pvalue = $scipy['go_data'][$d['GOTerm']]['pvalue'];
							if(!empty($pvalue['corrected'][$correction])) {
								$pvalue_c = $pvalue['corrected'][$correction];
								echo sprintf('%.2e', $pvalue_c);
							} else if(!is_string($pvalue['uncorrected'])) {
								echo sprintf('%.2e', $pvalue['uncorrected']);
							} else {
								echo $pvalue['uncorrected'];
							}
						} else {
							echo '&ndash;';
						}
						?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php } ?>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js" integrity="sha384-89aj/hOsfOyfD0Ll+7f2dobA15hDyiNb8m1dJ+rJuqgrGR+PVqNU8pybx4pbF3Cc" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/go/enrichment.min.js"></script>
</body>
</html>