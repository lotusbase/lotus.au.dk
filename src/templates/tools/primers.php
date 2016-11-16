<?php
	require_once('../config.php');

	$searched = false;

	if(!empty($_REQUEST) && isset($_REQUEST['t']) && in_array(intval($_REQUEST['t']), array(1, 2))) {

		if(intval($_REQUEST['t']) === 1 && $_GET) {

			// Sanitize GET values
			$version 	= escapeHTML(strval($_GET['v']));
			$blast		= escapeHTML($_GET['blast']);
			$ref		= escapeHTML($_GET['ref']);

			// Initialize error variables
			$error_message = array();
			$error_flag = false;

			// Validate inputs
			// Check if database version is specified
			if(empty($version) || !in_array($version, $lj_genome_versions)) {
				$error_message[] = 'No database version was selected';
				$error_flag = true;
			}

			// Is BLAST header specified
			if(empty($blast)) {
				$error_message[] = 'No BLAST header was provided';
				$error_flag = true;
			}

			// Is reference specified
			if(empty($ref)) {
				$error_message[] = 'No reference was provided';
				$error_flag = true;
			}

			// Parse BLAST array
			$blast_arr = array_filter(explode(",", $blast));
			
			// Perform query
			try {
				// Validate individual BLAST headers
				$dbq = "SELECT
					lore.FwPrimer AS FwPrimer,
					lore.RevPrimer AS RevPrimer,
					lore.Chromosome AS Chromosome,
					lore.Position AS Position,
					lore.Orientation AS Orientation,
					GROUP_CONCAT(DISTINCT lore.PlantID ORDER BY lore.PlantID DESC SEPARATOR '_') AS PID,
					gene.Gene AS Gene
					FROM lore1ins AS lore
					LEFT JOIN geneins AS gene ON (
						lore.Chromosome = gene.Chromosome AND
						lore.Position = gene.Position AND
						lore.Orientation = gene.Orientation AND
						lore.Version = gene.Version
						)
					WHERE
						lore.SeedStock = 'Y' AND
						lore.Version = ? AND
						";

				$err = $query_count = 0;
				$blast_invalid_items = array();
				$placeholder_array = array($version);
				foreach($blast_arr as $blast_item) {
					$blast_part = explode('_', $blast_item);
					$blast_count = count($blast_part);
					$dbq .= "(lore.Chromosome = ? AND lore.Position = ? AND lore.Orientation = ? AND lore.Version = ?) OR ";
					if($blast_count == 3) {
						// If the user enters a mapped insert e.g. chr5_3085263_R
						$placeholder_array = array_merge($placeholder_array, $blast_part);
						$placeholder_array[] = $version;
						$query_count++;
					} elseif($blast_count == 4) {
						// If the user enters an unmapped insert, e.g. LjSGA_055002_657_R
						$placeholder_array[] = $blast_part[0].'_'.$blast_part[1];
						$placeholder_array[] = $blast_part[2];
						$placeholder_array[] = $blast_part[3];
						$placeholder_array[] = $version;
						$query_count++;
					} else {
						// Invalid BLAST header
						$err++;
						$blast_invalid_items[] = $blast_item;
					}
				}

				// Handle BLAST header errors
				if($err >= count($blast_arr)) {
					// All entries are incorrect, do not conduct any database query
					$message = '<p>The BLAST '.pl($err, 'header', 'headers').' that you have submitted '.pl($err, 'is', 'are').' invalid. Please check that the '.pl($err, 'header is', 'headers are').' formatted properly according to the following format &mdash; <code>[chromosome]_[position]_[orientation]</code>. You have entered the following BLAST '.pl($err, 'header', 'headers').':</p><ul>';
					foreach($blast_invalid_items as $err) {
						$message .= '<li>'.$err.'</li>';
					}
					$message .= '</ul>';
					$error_message[] = $message;
					$error_flag = true;
				}

				// Catch all errors before executing query
				if($error_flag) {
					$_SESSION['primers_error'] = $error_message;
					session_write_close();
					header('location: primers.php'.http_build_query($_GET));
					exit();
				}

				// All entires are correct, or a subset of entries are invalid
				$dbq = substr($dbq, 0, -4);
				$dbq .= " GROUP BY lore.FwPrimer ORDER BY lore.PlantID";

				// Establish database connection
				$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

				// Make prepared statement
				$q = $db->prepare($dbq);
				$q->execute($placeholder_array);

				// Get results
				if($q->rowCount() > 0) {

					$searched = true;
					$results_count = 0;

					while($d = $q->fetch(PDO::FETCH_ASSOC)) {
						// Declare variables
						$fp = $d['FwPrimer'];
						$rp = $d['RevPrimer'];
						$pid = preg_replace('/30+/', '', $d['PID']);
						$chr = $d['Chromosome'];
						$pos = $d['Position'];
						$orn = $d['Orientation'];
						$gene = $d['Gene'];
						$out = '';

						// Row for forward primer
						if(strlen($pid)>12) {
							$out .= '<tr class="duplicate"><td><span class="icon-attention"></span></td>';
						} elseif(is_null($fp)) {
							$out .= '<tr class="noprimer"><td><span class="icon-cancel-circled"></span></td>';
							$fp = 'No primer designed';
						} else {
							$out .= '<tr><td><span class="icon-ok"></span></td>';
						}
						if(strlen($fp)<=36) {
							$out .= '<td class="ctr">Desalt<input type="hidden" name="oligotype[]" value="Desalt" /></td>';
						} elseif(strlen($fp)<=50) {
							$out .= '<td class="ctr">HPLC<input type="hidden" name="oligotype[]" value="HPLC" /></td>';
						} else {
							$out .= '<td class="ctr">PAGE<input type="hidden" name="oligotype[]" value="PAGE" /></td>';
						}
						$out .= '
							<td class="ctr">'.$ref.'<input type="hidden" name="ref[]" value="'.$ref.'" /></td>
							<td>LORE1_'.$pid.'_f<input type="hidden" name="oligoname[]" value="LORE1_'.$pid.'_f" /></td>
							<td class="naseq">'.$fp.'<input type="hidden" name="oligoseq[]" value="'.$fp.'" /></td>
							<td><input type="text" name="target[]" placeholder="Description of targeted sequence" value="'.$gene.'" /></td>
							<td class="ctr">NO</td>
							<td><input type="text" name="comments[]" value="LORE1 insertion genotyping. '.$chr.'_'.$pos.'_'.$orn.' (v'.$version.')" /></td>
						';
						$out .= '</tr>';

						// Row for reverse primer
						if(strlen($pid)>12) {
							$out .= '<tr class="duplicate"><td><span class="icon-attention"></span></td>';
						} elseif(is_null($rp)) {
							$out .= '<tr class="noprimer"><td><span class="icon-cancel-circled"></span></td>';
							$rp = 'No primer designed';
						} else {
							$out .= '<tr><td><span class="icon-ok"></span></td>';
						}
						if(strlen($rp)<=35) {
							$out .= '<td class="ctr">Desalt<input type="hidden" name="oligotype[]" value="Desalt"/></td>';
						} elseif(strlen($rp)<=50) {
							$out .= '<td class="ctr">HPLC<input type="hidden" name="oligotype[]" value="HPLC" /></td>';
						} else {
							$out .= '<td class="ctr">PAGE<input type="hidden" name="oligotype[]" value="PAGE" /></td>';
						}
						$out .= '
							<td class="ctr">'.$ref.'<input type="hidden" name="ref[]" value="'.$ref.'" /></td>
							<td>LORE1_'.$pid.'_r<input type="hidden" name="oligoname[]" value="LORE1_'.$pid.'_r" /></td>
							<td class="naseq">'.$rp.'<input type="hidden" name="oligoseq[]" value="'.$rp.'" /></td>
							<td><input type="text" name="target[]" placeholder="Description of targeted sequence" value="'.$gene.'" /></td>
							<td class="ctr">NO</td>
							<td><input type="text" name="comments[]" value="LORE1 insertion genotyping. '.$chr.'_'.$pos.'_'.$orn.' (v'.number_format(intval($version)*0.1, 1, '.', '').')" /></td>
						';
						$out .= '</tr>';

						$results_count++;
					}

				} else {
					throw new PDOException('Your search critera has returned no results. Please ensure that you have copied the correct BLAST header and selected the right version.');
				}

			} catch(PDOException $e) {

			}
		} else if(intval($_REQUEST['t']) === 2 && $_POST) {

			// Get POST data
			$oligotype 	= $_POST['oligotype'];
			$ref		= $_POST['ref'];
			$oligoname 	= $_POST['oligoname'];
			$oligoseq 	= $_POST['oligoseq'];
			$target 	= $_POST['target'];
			$comments 	= $_POST['comments'];

			$out = "\"Oligo type\",\"Ref\",\"Oligo name\",\"Oligo sequence\",\"Target\",\"Ordered\",\"Purpose & comments\"\n";

			foreach($oligotype as $key => $otype) {
				if(empty($target[$key])) {
					$target[$key] = "No user input";
				}
				$out .= "\"".$otype."\",\"".$ref[$key]."\",\"".$oligoname[$key]."\",\"".$oligoseq[$key]."\",\"".$target[$key]."\",\"NO\",\"".$comments[$key]."\"\n";
			}

			// Force download
			$file = "lore1_primers_db" . $_POST['version'] . "_" . date("Y-m-d_H-i-s") . ".csv";
			header("Content-disposition: csv; filename=\"".$file."\"");
			header("Content-type: application/vnd.ms-excel");
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			print $out;

			exit();
		}
	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>LORE1 Genotyping Primers &mdash; Tools &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/tools.min.css" type="text/css" media="screen" />
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
</head>
<body class="tools primers <?php echo ($searched ? 'results' : '');?>">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title('<em>LORE1</em> Genotyping Primers');
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<?php if(!$searched) { ?>
			<h2><em>LORE1</em> Genotyping Primers</h2>
			<p>This tool allows you to fetch primer pairs for a given <em>LORE1</em> insert by providing BLAST headers. BLAST headers are provided when you carry out a <a href="/blast/" title="BLAST">BLAST search</a> within our databases. The script will automatically generate a new CSV file containing the proper format in which you can import into our local Filemaker database to order your genotyping primers.</p>
			<p class="user-message reminder"><strong>Reminder:</strong> This tool does not check for primer duplicates. After your primer pairs have been retrieved, please verify with the local Filemaker database to ensure that the primers you are ordering have not been ordered before.</p>

			<?php
			if(isset($_SESSION['primers_error'])) {
				echo '<div class="user-message warning">'.$_SESSION['primers_error'].'</div>';
				unset($_SESSION['primers_error']);
			}
			?>

			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" id="primers-form">
				<div class="cols">
					<label for="version" class="col-one">Database Version <a class="info" data-modal="search-help" data-modal-content="It refers to the version of the reference genome. BLAST headers are specific to the database version." title="What does the database version mean?">?</a></label>
					<div class="col-two">
						<select name="v" id="version">
							<option value="" <?php echo (isset($_GET['v']) && empty($_GET['v'])) ? "selected" : ""; ?>>Select genome version</option>
							<?php foreach($lj_genome_versions as $v) {
								echo '<option value="'.$v.'" '.(isset($_GET['v']) && !empty($_GET['v']) && strval($_GET['v']) === $v ? 'selected' : '').'>v'.$v.'</option>';
							} ?>
						</select>
					</div>

					<label for="blastheader" class="col-one">BLAST Header <a class="info" data-modal="search-help" data-modal-content="It is in the format of &lt;code&gt;[chromosome number]_[position]_[orientation]&lt;/code&gt;&lt;br /&gt;For example: &lt;code&gt;chr5_3085263_R&lt;/code&gt; or &lt;code&gt;LjSGA_055002_657_R&lt;/code&gt;" title="What is the blast header?">?</a></label>
					<div class="col-two field__plant-id">
						<div class="multiple-text-input input-mimic">
							<ul class="input-values">
							<?php
								if(isset($_GET['pid']) && !empty($_GET['pid'])) {
									$blast_array = explode(',', $_GET['pid']);
									foreach($blast_array as $blast_item) {
										$blast_item = preg_replace('/^DK\d{2}\-0(3\d{7})$/', '$1', $blast_item);
										echo '<li data-input-value="'.escapeHTML($blast_item).'">'.escapeHTML($blast_item).'<span class="icon-cancel" data-action="delete"></span></li>';
									}
								}
							?>
								<li class="input-wrapper"><input type="text" id="blast-input" placeholder="BLAST Header (e.g. chr5_3085263_R or LjSGA_055002_657_R)" autocomplete="off" /></li>
							</ul>
							<input class="input-hidden search-param" type="hidden" name="blast" id="blastheader" value="<?php echo (!empty($_GET['pid'])) ? escapeHTML($_GET['pid']) : ''; ?>" readonly />
						</div>
						<small><strong>Separate each BLAST header with a comma, space or tab.</strong></small>
					</div>

					<label for="reference" class="col-one">Reference <a class="info" data-modal="search-help" data-modal-content="Reference should be in the format of your known short name, e.g. KAMA, SRR, TM." title="What is reference?">?</a></label>
					<div class="col-two">
						<input type="text" name="ref" id="reference" value="<?php echo (isset($_GET['ref']) && !empty($_GET['ref']) ? escapeHTML($_GET['ref']) : ''); ?>" placeholder="Reference (your short name, e.g. 'TM')" autocomplete="off" maxlength="5" />
					</div>
				</div>

				<input type="hidden" name="t" value="2" />
				<button type="submit">Generate primer order sheet</button>
			</form>
		<?php } else { ?>
			<h2><em>LORE1</em> Genotyping Primers</h2>
			<p>We have searched the database with the BLAST headers that you have provided, and have returned with <?php echo $results_count.' '.pl($results_count, 'pair', 'pairs'); ?> of PCR primers. The <strong>oligo type</strong> and <strong>oligo name</strong> are automatically generated, and we do not recommend modifying it (although you can, after you have downloaded the .csv file, which can be edited in any spreadsheet editor). <strong>Purpose &amp; comments</strong> are automatically generated and can be modified. A quick use guide:</p>
			<ol>
				<li>Enter the <strong>target</strong> information for the primers.</li>
				<li>Download the <code>.csv</code> file.</li>
				<li>Open the file in a spreadsheet editor (i.e. Microsoft Excel), and save it as an Excel spreadsheet (<code>.xls</code> or <code>.xlsx</code>.).</li>
				<li>Import the Excel file into the FileMaker server.</li>
			</ol>
			<p class="user-message reminder"><strong>Reminder:</strong> This tool does not check for primer duplicates. After your primer pairs have been retrieved, please verify with the local Filemaker database to ensure that the primers you are ordering have not been ordered before.</p>
			<?php
				if(count($blast_invalid_items) > 0) {
					echo '<div class="user-message warning"><p>We have found '.$err.' invalid BLAST '.pl($err, 'header', 'headers').' in your submission:</p><ul>';
					foreach($blast_invalid_items as $err) {
						echo '<li>'.$err.'</li>';
					}
					echo '</ul></div>';
				}
			?>
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="has-group">
				<table id="rows" class="table--dense">
					<colgroup></colgroup>
					<colgroup></colgroup>
					<colgroup></colgroup>
					<colgroup></colgroup>
					<colgroup></colgroup>
					<colgroup></colgroup>
					<colgroup></colgroup>
					<colgroup></colgroup>
					<thead>
						<tr>
							<th><a href="#" data-modal data-modal-content="&lt;ul&gt;&lt;li&gt;&lt;span class=&quot;icon-ok&quot;&gt;&lt;/span&gt; &mdash; All clear. Primer has been designed.&lt;/li&gt;&lt;li&gt;&lt;span class=&quot;icon-attention&quot;&gt;&lt;/span&gt; &mdash; &ge;1 &lt;em&gt;LORE1&lt;/em&gt; line has this insertion. Check the row and column coordinate details by downloading the search results, and determine which plant line to use (usually the one with the highest count).&lt;/li&gt;&lt;li&gt;&lt;span class=&quot;icon-cancel-circled&quot;&gt;&lt;/span&gt; &mdash; Primer has not been designed. You may use the &lt;a href=&quot;http://frodo.wi.mit.edu/&quot; title=&quot;Primer3&quot;&gt;Primer3 tool&lt;/a&gt; to do so.&lt;/li&gt;&lt;/ul&gt;" title="Status of primers">Status</a></td>
							<th><a href="#" data-modal data-modal-content="&lt;p&gt;The oligo type depends on the length of your PCR primer. This has been automatically determined:&lt;/p&gt;&lt;ul&gt;&lt;li&gt;&lt;code&gt;Desalt&lt;/code&gt; &mdash; up to 35 bases&lt;/li&gt;&lt;li&gt;&lt;code&gt;HPLC&lt;/code&gt; &mdash; up to 50 bases&lt;/li&gt;&lt;li&gt;&lt;code&gt;PAGE&lt;/code&gt; &mdash; over 50 bases&lt;/li&gt;&lt;/ul&gt; " title="What is the oligo type?">Oligo type</a></td>
							<th>Ref</td>
							<th>Oligo name</td>
							<th>Oligo sequence</td>
							<th>Target</td>
							<th>Ordered?</td>
							<th>Purpose &amp; comments</td>
						</tr>
					</thead>
					<tbody>
						<?php echo $out; ?>
					</tbody>
				</table>
				<input type="hidden" name="t" value="2" />
				<button type="submit">Download file</button>
			</form>
		<?php } ?>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
	<script>
		$(function() {
			$('#primers-form select').select2();
		});
	</script>
</body>
</html>