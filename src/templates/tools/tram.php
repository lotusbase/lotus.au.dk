<?php
	// Get important files
	require_once('../config.php');

	// Initiate time count
	$start_time = microtime(true);

	// Error
	$error = array();

	// Search status
	$searched = false;

	try {
		// Establish connection
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		if($_GET) {
			// Check if both versions are specified
			if(empty($_GET['source_version']) || empty($_GET['target_version'])) {
				throw new Exception('Source and/or target version(s) is/are not specified.');
			} else {
				$source_version = escapeHTML($_GET['source_version']);
				$target_version = escapeHTML($_GET['target_version']);
			}

			// Define regex for ID verification
			$id_pattern = array(
				'MG20_2.5' => '/^(chr\d\.(CM\d+|Lj.*)\.\d+\.r\d\.[amd]|LjSGA_\d+\.\d+(\.\d+)?|Lj.*\.\d+\.r\d\.[amd])$/i',
				'MG20_3.0' => '/^Lj([0-6]|chloro|mito|1_4_FUCT|XYLT)g(\dv)?\d+(\.\d+)?$/i',
				'Gifu_1.2' => '/^LotjaGi\dg\dv\d+?(_LC)?(\.\d+)?$/i',
				);

			// Check if IDs are provided
			if(empty($_GET['ids'])) {
				throw new Exception('No IDs have been provided.');
			}
			// Check if IDs are properly formatted
			else {
				$ids_array = explode(",", $_GET['ids']);
				$ids_error = array();
				foreach($ids_array as $id) {
					if(!preg_match($id_pattern[$source_version], $id)) {
						$ids_error[] = $id;
					}
				}
				if(count($ids_error) === count($ids_array)) {
					throw new Exception('Invalid format for all provided identifiers.');
				}
			}

			// Construct query
			$dbq = 'FROM transcript_mapping AS gm
				WHERE (SourceEcotype = ? AND SourceVersion = ? AND TargetEcotype = ? AND TargetVersion = ? AND ';
			$source_data = explode('_', $_GET['source_version']);
			$target_data = explode('_', $_GET['target_version']);
			$dbq_vars = array($source_data[0], $source_data[1], $target_data[0], $target_data[1]);
			foreach (array_diff($ids_array, $ids_error) as $key => $id) {
				$dbq .= 'MATCH (SourceTranscriptID) AGAINST (?) OR ';
				$dbq_vars[] = $id;
			};
			$dbq = substr($dbq, 0 ,-4).')';

			if(!empty($_GET['identity_cutoff'])) {
				$_ic = max(min(floatval($identity_cutoff), 1),0);
				$dbq .= ' AND Identity > ?'; 
				$dbq_vars[] = $_GET['identity_cutoff'];
			}

			if(!empty($_GET['evalue_cutoff'])) {
				$_ec = max(min(floatval($evalue_cutoff), 1),0);
				$dbq .= ' AND Evalue < ?';
				$dbq_vars[] = $_GET['evalue_cutoff'];
			}

			// Prequery
			$q1 = $db->prepare("SELECT
				gm.IDKey AS ID
				$dbq ORDER BY gm.IDKey ASC");
			$q1->execute($dbq_vars);

			$rows = $q1->rowCount();
			if($rows < 1) {
				if(!empty($_GET['identity_cutoff']) || !empty($_GET['evalue_cutoff'])) {
					throw new Exception('No rows has been returned. You might want to relax your search criteria.');
				} else {
					throw new Exception('No rows has been returned.');
				}
			} else {
				while($r = $q1->fetch(PDO::FETCH_ASSOC)) {
					$q1_rows['ID'][] = $r['ID'];
				}
			}

			// Default pagination variables
			$num	= (isset($_GET['n']) && !empty($_GET['n'])) ? intval($_GET['n']) : 50;
			$page	= (isset($_GET['p']) && !empty($_GET['p'])) ? intval($_GET['p']) : 1;

			// Get pagination variables
			$last = intval(ceil($rows/$num));
			if($page <= 1) {
				$page = 1;
			} elseif($page > $last) {
				$page = $last;
			}

			// Perform actual query
			$q2 = $db->prepare("SELECT
				gm.IDKey AS ID,
				gm.SourceTranscriptID AS SourceTranscriptID,
				gm.SourceEcotype AS SourceEcotype,
				gm.SourceVersion AS SourceVersion,
				gm.TargetTranscriptID AS TargetTranscriptID,
				gm.TargetEcotype AS TargetEcotype,
				gm.TargetVersion as TargetVersion,
				gm.Identity AS IdentityScore,
				gm.Evalue AS Evalue
				$dbq
				ORDER BY gm.IDKey ASC
				LIMIT ".($page-1)*$num.", ".$num);
			$q2->execute($dbq_vars);

			$searched = true;

		} else if($_POST) {
			// Get IDs
			$ids = explode(',', $_POST['ids']);

			// Gene mapping table
			// Check if both versions are specified
			if(empty($_POST['source_version']) || empty($_POST['target_version'])) {
				throw new Exception('Source and/or target version(s) is/are not specified.');
			} else {
				$source_version = $_POST['source_version'];
				$target_version = $_POST['target_version'];
			}
			

			// Execute query
			$q3 = $db->prepare("SELECT
				gm.SourceTranscriptID AS SourceTranscriptID,
				CONCAT(gm.SourceEcotype, ' v', gm.SourceVersion) AS SourceGenome,
				gm.TargetTranscriptID AS TargetTranscriptID,
				CONCAT(gm.TargetEcotype, ' v', gm.TargetVersion) AS TargetGenome,
				gm.Identity AS IdentityScore,
				gm.Evalue AS Evalue
				FROM transcript_mapping AS gm
				WHERE
					gm.IDKey IN (".str_repeat('?,', count($ids)-1)."?)
				ORDER BY gm.IDKey ASC");
			$q3->execute($ids);

			// Generate download file
			$header = array("Source Transcript ID", "Source Genome", "Target Transcript ID", "Target Genome", "Identity Score", "E-value");
			$out = implode("\t", $header)."\n";
			while($r = $q3->fetch(PDO::FETCH_ASSOC)) {
				$out .= implode("\t", $r)."\n";
			}

			$file = "lore1_trex_" . date("Y-m-d_H-i-s") . ".tsv";
			header("Content-disposition: csv; filename=\"".$file."\"");
			header("Content-type: application/vnd.ms-excel");
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			print $out;
			exit();
		}

	} catch(PDOException $e) {
		$error = array(
			'error' => true,
			'message' => 'We have encountered a MySQL error: '.$e->getMessage()
		);
	} catch(Exception $e) {
		$error = array(
			'error' => true,
			'message' => $e->getMessage()
		);
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>TRAM &mdash; Tools &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'The Transcript Mapper (TRAM) tool maps transcript identifiers of one version of the Lotus genome assembly to another.'
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/tools.min.css" type="text/css" media="screen" />
</head>
<body class="tools tram <?php echo ($searched) ? 'results' : ''; ?>">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title('TRAM');
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>TRAM</h2>
		<span class="byline"><strong>Transcript Mapper</strong><br />for <em>L.</em> japonicus</span>
		<p>The <strong>Transcript Mapper</strong> tool can be used to map transcript identifiers for one version of the <em>Lotus</em> genome assembly to another. Mapping is performed internally by identifying high confidence matching candidates across genome assembly versions using NCBI BLAST.</p>
		<p>You can also <a href="/data/download?search=Transcript%20mapping">download the full bidrectional mapping table</a> for personal use from our downloads page.</p>

		<?php 
			// Display error if any
			if(!empty($error)) {
				echo '<div class="user-message warning align-center"><h3>Houston, we have a problem!</h3>'.$error['message'].'</div>';
			}
			else if(isset($_SESSION['tram_error'])) {
				echo '<div class="user-message warning align-center"><h3>Houston, we have a problem!</h3>'.$_SESSION['tram_error'].'</div>';
				unset($_SESSION['tram_error']);
			}

			// Allow collapsible form when search is initiated
			if($searched) {
				echo '<div class="toggle'.(empty($error) ? ' hide-first' : '').'"><h3><a href="#" title="Repeat Search">Repeat Search</a></h3>';
			}

		?>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" id="tram-form" class="has-group">

			<div class="cols" role="group">
				<label for="ids-input" class="col-one">Query <a data-modal="wide" class="info" title="How should I enter the IDs?" href="<?php echo WEB_ROOT; ?>/lib/docs/gene-transcript-probe-id">?</a></label>
				<div class="col-two">
					<div class="multiple-text-input input-mimic">
						<ul class="input-values">
						<?php
							if(!empty($_GET['ids'])) {
								if(is_array($_GET['ids'])) {
									$tram_array = $_GET['ids'];
								} else {
									$tram_array = explode(",", $_GET['ids']);
								}
								foreach($tram_array as $tram_item) {
									echo '<li data-input-value="'.escapeHTML($tram_item).'" class="'.(!empty($ids_error) && in_array($tram_item, $ids_error) ? 'warning' : '').'">'.escapeHTML($tram_item).'<span class="icon-cancel" data-action="delete"></span></li>';
								}
							}
						?>
							<li class="input-wrapper"><input type="text" id="ids-input" placeholder="Gene/transcript ID" autocomplete="off" autocorrect="off"  autocapitalize="off" spellcheck="false" data-boolean-mode="true" /></li>
						</ul>
						<input class="input-hidden" type="hidden" name="ids" id="ids" value="<?php echo (!empty($_GET['ids'])) ? (is_array($_GET['ids']) ? implode(',', preg_replace('/\"/', '&quot;', escapeHTML($trx_array))) : preg_replace('/\"/', '&quot;', escapeHTML($_GET['ids']))) : ''; ?>" readonly />
					</div>
					<small><strong>Separate each gene/transcript ID with a comma, space, or tab.</strong></small>
					<?php if(count($ids_error)) { ?>
						<p class="user-message warning">You have provided IDs that do not match the expected format for the given genome version.</p>
					<?php } ?>
				</div>
			</div>

			<div class="cols has-legend" role="group">
				<p class="legend">Mapping settings</p>
				<div class="cols full-width">
					<div class="col-one col-half-width"><label>Source version</label></div>
					<select class="col-two col-half-width" name="source_version" id="source-version">
						<?php
							foreach($lj_genome_versions as $label => $lj_genome) {
								$v = implode('_', [$lj_genome['ecotype'], $lj_genome['version']]);

								echo '<option value="'.$v.'" '.(isset($_GET['source_version']) && $_GET['source_version'] === $v ? 'selected' : '').'>'.$label.'</option>';
							}
						?>
					</select>
					<div class="col-one col-half-width align-right"><label>Target version</label></div>
					<select class="col-two col-half-width" name="target_version" id="target-version" class="disabled">
						<?php
							foreach($lj_genome_versions as $label => $lj_genome) {
								$v = implode('_', [$lj_genome['ecotype'], $lj_genome['version']]);
								echo ((isset($_GET['source_version']) && $_GET['source_version'] === $v) ? '' : '<option value="'.$v.'">'.$label.'</option>');
							}
						?>
					</select>
				</div>

				<label for="identity-cutoff" class="col-one">Min. identity cutoff</label>
				<input type="number" id="identity-cutoff" name="identity_cutoff" class="col-two" min="0" max="100" step="0.1" value="<?php echo !empty($_GET['identity_cutoff']) ? floatval($_GET['identity_cutoff']) : '' ?>" placeholder="Minimum Identity cutoff" />

				<label for="evalue-cutoff" class="col-one">Max. E-value cutoff</label>
				<input type="number" id="evalue-cutoff" name="evalue_cutoff" class="col-two" min="0" max="1" value="<?php echo !empty($_GET['evalue_cutoff']) ? floatval($_GET['evalue_cutoff']) : '' ?>" placeholder="Maximum E-value cutoff" />
			</div>

			<input type="hidden" name="n" value="25" />
			<input type="hidden" name="p" value="1" />

			<button type="submit"><span class="icon-map">Map transcripts</span></button>
		</form>
		<?php
			if($searched) {
				echo '</div><div class="toggle">
				<h3><a href="#" data-toggled="on" class="open">Export options</a></h3>
				<p>Download the entire search result as a CSV file.</p>
				<div class="cols justify-content__space-around">
					<form action="'.$_SERVER['PHP_SELF'].'" method="post" class="form--no-spacing">
						<button type="submit">Download tabular results</button>
						<input type="hidden" name="ids" value="'.implode(',', $q1_rows['ID']).'" />
						<input type="hidden" name="source_version" value="'.escapeHTML($_GET['source_version']).'" />
						<input type="hidden" name="target_version" value="'.escapeHTML($_GET['target_version']).'" />
						<input type="hidden" name="redir" value="'.$_SERVER['REQUEST_URI'].'" />
					</form>
				</div>
			</div>';
			}

			// Display search results
			if(empty($error) && $searched && $q2->rowCount() > 0) {
		?>
		<p>We have found <?php echo "<strong>".$rows."</strong> ".pl($rows,'result','results'); ?>. Now displaying <?php echo $page." of ".$last." with ".$num." ".pl($num,'row','rows');?> per page. This search has taken <strong><?php echo number_format((microtime(true) - $start_time), 3); ?>s</strong> to perform.</p>

			<?php
				if($last > 1) {
					$paginate = new \LotusBase\Component\Paginate();
					$paginate->set_current_page($page);
					$paginate->set_last_page($last);
					$paginate->set_rows_per_page($num);
					$paginate->set_query_string($_GET);
					$paginate->set_version($version); 
					$pagination = $paginate->get_pagination();
					echo $pagination;
				}

				function dropdown($transcript, $version) {
					$out = '<div class="dropdown button"><span class="dropdown--title"><a href="'.WEB_ROOT.'/gene/'.$transcript.'">'.$transcript.'</a></span><ul class="dropdown--list">';

					// View gene
					if ($version === 'MG20_3.0' || $version === 'Gifu_1.2') {
						$out .= '<li><a href="'.WEB_ROOT.'/view/gene/'.$transcript.'"><span class="icon-search">View gene</span></a></li>';
					}

					// LORE1 search
					if ($version === 'MG20_2.5' || $version === 'MG20_3.0') {
						$out .= '<li><a href="'.WEB_ROOT.'/lore1/search?v=MG20_'.$version.'&gene='.$transcript.'"><span class="icon-leaf"><em>LORE1</em></span></a></li>';
					}

					// Genome browser
					if ($version === 'MG20_3.0') {
						$out .= '<li><a href="'.WEB_ROOT.'/genome?data=genomes%2Flotus-japonicus%2Fmg20%2Fv3.0&loc='.$transcript.'" title="View in genome browser"><span class="icon-book">Genome browser</span></a></li>';
					} elseif ($version === 'Gifu_1.2') {
						$out .= '<li><a href="'.WEB_ROOT.'/genome?data=genomes%2Flotus-japonicus%2Fgifu%2Fv1.2&loc='.$transcript.'" title="View in genome browser"><span class="icon-book">Genome browser</span></a></li>';
					}
					$out .= '</ul>';
					return $out;
				}
			?>

			<table id="rows" data-sticky>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<thead>
					<tr>
						<th scope="col">Query (<?php echo str_replace('_', ' v', escapeHTML($_GET['source_version'])); ?>)</th>
						<th scope="col">Mapped transcript (<?php echo str_replace('_', ' v', escapeHTML($_GET['target_version'])); ?>)</th>
						<th scope="col" data-type="numeric">Identity score (%)</th>
						<th scope="col" data-type="numeric">E-value</th>
					</tr>
				</thead>
				<tbody>
				<?php while($row = $q2->fetch(PDO::FETCH_ASSOC)) { ?>
					<tr>
						<td><?php echo dropdown($row['SourceTranscriptID'], $source_version); ?></td>
						<td><?php echo dropdown($row['TargetTranscriptID'], $target_version); ?></td>
						<td data-type="numeric"><?php echo $row['IdentityScore']; ?></td>
						<td data-type="numeric"><?php echo $row['Evalue']; ?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		<?php } ?>

	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/tram.min.js"></script>
</body>
</html>