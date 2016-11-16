<?php
	require_once('../config.php');

	$search = false;
	if(isset($_GET['id']) && isset($_GET['idtype']) && !empty($_GET['id']) && !empty($_GET['idtype'])) {
		$search = true;
	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>Probe Search&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/css/tools.css?v=1" type="text/css" media="screen" />
</head>
<body class="tools expat expat-mapping">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title(array('ExpAt', 'Gene &amp; Probe ID Mapping'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>Gene &amp; Probe ID Mapping</h2>
		<span class="byline">Linking gene IDs in <em>Lotus japonicus</em> v3.0 accessions<br />with probe IDs from the Samuel Roberts Nobel Foundation.</span>
		<p>As the ExpAt data are based on <em>Lotus japonicus</em> v3.0 accessions, there is no direct way to query for expression patterns based on a list of probe IDs. However, you can map your probe IDs against the accessions database, therefore providing an indirect link:</p>


		<?php
			if($search) {
				?>
				<div class="toggle hide-first"><h3><a href="#">Search again</a></h3>
				<?php
			}
		?>
		<form action="mapping.php" method="get" id="expat-form">

			<div class="cols">
				<label for="expat-idtype" class="col-one">ID type</label>
				<div class="col-two">
					<select id="expat-idtype" name="idtype">
						<option value="geneid">Gene ID</option>
						<option value="probeid">Probe ID</option>
					</select>
				</div>

				<label for="expat-id-input" class="col-one">ID to map</label>
				<div class="col-two">
					<div class="multiple-text-input input-mimic">
						<ul class="input-values">
							<?php
								if(isset($_GET['id']) && !empty($_GET['id'])) {
									$id_array = explode(",", $_GET['id']);
									foreach($id_array as $id_item) {
										echo '<li data-input-value="'.escapeHTML($id_item).'">'.escapeHTML($id_item).'<span class="icon-cancel" data-action="delete"></span></li>';
									}
								}
							?>
							<li class="input-wrapper"><input type="text" id="expat-id-input" placeholder="Enter accession number or GI here" autocomplete="off" /></li>
						</ul>
						<input class="input-hidden" type="hidden" name="id" id="expat-id" value="<?php echo (isset($_GET['id']) && !empty($_GET['ids'])) ? escapeHTML($_GET['id']) : ''; ?>" readonly />
					</div>
					<small><strong>Separate each ID with a comma, space or tab.</strong></small>
				</div>
			</div>

			<button type="submit"><span class="pictogram icon-search">Search</span></button>
		</form>
		<?php
			if($search) {
				?>
				</div>
				<?php
			}
		?>

		<?php
			if($search) {

				// Establish datanase connection
				try {
					$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				} catch(PDOException $e) {
					echo '<p class="user-message warning">We have experienced a problem trying to establish a database connection. Please contact the system administrator as soon as possible.</p>';
				}


				// Construct query
				$ids = trim($_GET['id']);
				$id_pattern = array(
					'/ *[\r\n]+/',		// Checks for one or more line breaks
					'/(\w)\s+(\w)/',	// Checks for words separated by one or more spaces
					'/,\s*/',			// Checks for words separated by comma, but with variable spaces
					'/,\s*,/'			// Checks for empty strings (i.e. no words between two commas)
					);
				$id_replace = array(
					',',
					'$1, $2',
					',',
					',',
					''
					);
				$id_rep = preg_replace($id_pattern, $id_replace, $ids);
				$id_arr = array_filter(explode(",", $id_rep));

				// Accepted ID
				$id_type = $_GET['idtype'];
				$accepted_id_type = array('geneid', 'probeid');

				// Map ID type to column
				$id_col = array(
					'geneid' => 'GeneID',
					'probeid' => 'ProbeID'
				);

				// Clean up
				$idArr = str_repeat('?,', count($id_arr)-1).'?';

				if(in_array($id_type, $accepted_id_type)) {
					// Replace transcript IDs with gene IDs
					if($id_type == 'geneid') {
						$id_arr = preg_replace('/^(.*)\.\d+$/', '$1', $id_arr);
					}

					try {
						// Prepare query
						$q = $db->prepare("SELECT
								t1.GeneID AS GeneID,
								t2.ProbeID AS ProbeID,
								count(t2.ProbeID) AS Observations,
								GROUP_CONCAT(t2.Lj30_ID		ORDER BY t2.ID ASC SEPARATOR '<br />') AS Isoform,
								GROUP_CONCAT(t2.Evalue		ORDER BY t2.ID ASC SEPARATOR '<br />') AS Evalue,
								GROUP_CONCAT(t2.AlignLength ORDER BY t2.ID ASC SEPARATOR '<br />') AS AlignLength
							FROM
								expat_ljgea_geneid AS t1
							LEFT JOIN 
								expat_mapping AS t2
								ON t1.GeneID = t2.GeneID
							WHERE
								t2.".$id_col[$id_type]." IN ($idArr)
							GROUP BY CONCAT(t1.GeneID, t2.ProbeID)
							ORDER BY
								t1.GeneID
							");

						// Execute query with array of values
						$q->execute($id_arr);

						// Get results
						if($q->rowCount() > 0) {
							$out = '<div id="probes-result"><form action="index.php" method="post" class="has-group"><table id="rows"><thead><tr><th class="chk"><input class="ca" type="checkbox" /></th><th>Gene ID</th><th>Probe ID</th><th data-type="numeric">Observations</th><th>Isoform</th><th data-type="numeric">E-value</th><th data-type="numeric">Align Length</th></tr></thead><tbody>';
							while($row = $q->fetch(PDO::FETCH_ASSOC)) {
								$out .= '<tr>';
								$out .= '<td class="chk"><input type="checkbox" data-geneid="'.$row['GeneID'].'" data-probeid="'.$row['ProbeID'].'" value="" /></td>';
								$out .= '<td>'.$row['GeneID'].'</td>';
								$out .= '<td>'.$row['ProbeID'].'</td>';
								$out .= '<td data-type="numeric">'.$row['Observations'].'</td>';
								$out .= '<td>'.$row['Isoform'].'</td>';
								$out .= '<td data-type="numeric">'.$row['Evalue'].'</td>';
								$out .= '<td data-type="numeric">'.$row['AlignLength'].'</td>';
								$out .= '</tr>';
							}
							$out .= '</tbody></table><p>Search selected rows in the Expression Atlas based on:</p><div class="cols"><button type="button" data-idtype="geneid"><span class="pictogram icon-search">Gene ID</span></button> <button type="button" data-idtype="probeid"><span class="pictogram icon-search">Probe ID</span></button></div></form></div>';

							echo $out;
						} else {
							echo '<p class="user-message warning">No results returned. Make sure that you are using the correct ID type for your ID(s) that you are mapping for.</p>';
						}

					} catch(PDOException $err) {
						$e = $db->errorInfo();
						echo '<p class="user-message warning">MySQL has encountered an error&mdash; '.$e[1].': '.$e[2].'<br />'.$err->getMessage().'</p>';
					}
				} else {
					echo '<p class="user-message warning">You have selected an invalid ID type.</p>';
				}
			}
		?>
		
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>
