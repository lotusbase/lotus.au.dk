<?php
	require_once('../config.php');

	$error = false;
	$searched = false;

	if(!empty($_GET) && !empty($_GET['ids'])) {
		$ids = !is_array($_GET['ids']) ? explode(',', $_GET['ids']) : $_GET['ids'];
		$ids = array_values(array_unique($ids));

		try {

			// Initiate time count
			$start_time = microtime(true);

			// Check if any of the ids match
			foreach($ids as &$id) {
				if(preg_match('/^Lj(\d|chloro|mito)g\dv\d+(\.\d+)?$/', $id)) {
					if(!preg_match('/^Lj(\d|chloro|mito)g\dv\d+\.\d+$/', $id)) {
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
				COUNT(DISTINCT logo1.Transcript) AS QueryCount,
				COUNT(DISTINCT logo2.Transcript) AS MappedCount,
				go.Namespace AS Namespace,
				go.Name AS Name
				FROM gene_ontology_lotus AS logo1
				LEFT JOIN gene_ontology AS go ON
					logo1.GO_ID = go.GO_ID
				LEFT JOIN gene_ontology_lotus AS logo2 ON 
					logo1.GO_ID = logo2.GO_ID
				WHERE
					logo1.GO_ID IS NOT NULL AND
					logo1.Transcript IN (".str_repeat('?,', count($ids)-1)."?)
				GROUP BY logo1.GO_ID");
			$q1->execute($ids);

			$id_count = 98305;

			if(!$q1->rowCount()) {
				throw new Exception('There are no GO terms mapped to the '.pl(count($ids), 'identifier').' you have provided.');
			} else {
				$searched = true;
			}

			// Process data
			$data = array();
			$scipy_data = array();
			while($r = $q1->fetch(PDO::FETCH_ASSOC)) {

				// Compute data
				$r['QueryIDHasTerm'] = $r['QueryCount'];
				$r['DatasetIDHasTerm'] = $r['MappedCount'];
				$r['QueryIDHasTermNot'] = count($ids) - $r['QueryCount'];
				$r['DatastIDHasTermNot'] = $id_count - $r['MappedCount'];

				// Pass data to SciPy
				$scipy_data[$r['GOTerm']] = array(
					'data' => array(
						'queryCount' => $r['QueryCount'],
						'mappedCount' => $r['MappedCount']
						),
					'matrix' => array(
						array($r['QueryIDHasTerm'], $r['DatasetIDHasTerm']),
						array($r['QueryIDHasTermNot'], $r['DatastIDHasTermNot'])
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
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/tools.min.css" type="text/css" media="screen" />
</head>
<body class="tools <?php echo (!$error) ? 'results' : ''; ?> init-scroll--disabled">

	<?php
		$header = new \LotusBase\PageHeader();
		echo $header->get_header();
		echo get_breadcrumbs(array('page_title' => 'GO Enrichment'));
	?>

	<section class="wrapper">
		<h2>GO Enrichment</h2>
		<span class="byline"><abbr title="Gene Ontology">GO</abbr> term enrichment analysis for <em>L. japonicus</em>.</span>
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
										echo '<li data-input-value="'.$id_item.'" class="'.(!preg_match('/^Lj(\d|chloro|mito)g\dv\d+(\.\d+)?$/', $id_item) ? 'warning' : '').'">'.$id_item.'<span class="icon-cancel" data-action="delete"></span></li>';
									}
								}
							?>
								<li class="input-wrapper"><input type="text" id="ids-input" placeholder="Keyword, or gene/transcript ID" autocomplete="off" autocorrect="off"  autocapitalize="off" spellcheck="false" data-boolean-mode="true" /></li>
							</ul>
							<input class="input-hidden" type="hidden" name="ids" id="ids" value="<?php echo (!empty($_GET['ids'])) ? (is_array($_GET['ids']) ? implode(',', preg_replace('/\"/', '&quot;', $trx_array)) : preg_replace('/\"/', '&quot;', $_GET['ids'])) : ''; ?>" readonly />
						</div>
						<small><strong>Separate each keyword, or gene/transcript ID, with a comma, space, or tab.</strong></small>
						<br />
						<small><strong>Unsure what to do? <a href="#" id="sample-data" data-ids="Lj4g3v0281040 Lj4g3v2139970 Lj2g3v0205600 Lj1g3v0414750 Lj0g3v0249089 Lj4g3v2775550 Lj0g3v0245539 Lj3g3v2693010 Lj2g3v1105370 Lj4g3v1736080 Lj4g3v2573630 Lj1g3v2975920 Lj6g3v1052420">Try a sample data</a> from Mun et al., 2016.</strong></small>
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
				echo '<p>We have found <strong>'.$rows.'</strong> unique GO '.pl($rows, 'term').' mapped to your list of '.pl(count($ids), 'identifier').'. This search has taken <strong>'.number_format((microtime(true) - $start_time), 3).'s</strong> to perform.</p>';
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
					<th scope="col" data-type="numeric">Freq. (%)</th>
					<th scope="col" data-type="numeric">Count</th>
					<th scope="col" data-type="numeric">Freq. (%)</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($data as $d) { ?>
				<tr>
					<td><div class="dropdown button">
						<?php $go_term = $d['GOTerm']; ?>
						<span class="dropdown--title"><a href="<?php echo WEB_ROOT.'/view/go/'.$go_term; ?>"><?php echo $go_term; ?></a></span>
						<ul class="dropdown--list">
							<?php
								$go_links_handler = new \LotusBase\View\GO\Link();
								$go_links_handler->set_id($go_term);
								$go_links_handler->add_internal_link();
								echo $go_links_handler->get_html();
							?>
						</ul>
					</div></td>
					<td><?php
						$go_namespace = array(
							'p' => 'Biological process',
							'f' => 'Molecular function',
							'c' => 'Cellular component'
							);
						echo $go_namespace[$d['Namespace']];
					?></td>
					<td><?php echo $d['Name']; ?></td>
					<td data-type="numeric"><?php echo $d['QueryCount']; ?></td>
					<td data-type="numeric"><?php echo number_format($d['QueryCount'] / count($ids), '2', '.', ''); ?></td>
					<td data-type="numeric"><?php echo $d['MappedCount']; ?></td>
					<td data-type="numeric"><?php echo number_format($d['MappedCount'] / $id_count, '2', '.', ''); ?></td>
					<td data-type="numeric"><?php echo number_format($scipy[$d['GOTerm']]['oddsratio'], '2', '.', ''); ?></td>
					<td data-type="numeric"><?php echo sprintf('%.2e', $scipy[$d['GOTerm']]['pvalue']); ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>	
		<?php } ?>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/go-enrichment.min.js"></script>
</body>
</html>