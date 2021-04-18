<?php

// Load site config
require_once('../config.php');

// Get variables
if(is_valid_request_uri($_POST['origin'])) {
	$origin = 'search.php';
} else {
	$origin = $_POST['origin'];
}

// General error function
function error_function($error_message, $origin) {
	$_SESSION['download_error'] = $error_message;
	session_write_close();
	header('Location: '.WEB_ROOT.$origin);
	exit();
}

try {

	// Verify CSRF token
	$csrf_protector->verify_token();

	// Establish database connection
	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
	error_function('We have experienced a problem trying to establish a database connection. Please contact the system administrator should this issue persist.', $origin);
} catch(Exception $e) {
	error_function($e->getMessage(), $origin);
}

// Is version specified?
$genome_version_checker = new \LotusBase\LjGenomeVersion(array('genome' => $_POST['v']));
$genome_version = $genome_version_checker->check();
if(!isset($_POST['v']) || !$genome_version) {
	error_function('You have not specified a version number.', $origin);
}
$genome_parts = explode('_', $genome_version);
$ecotype = $genome_parts[0];
$version = $genome_parts[1];

// Coerce values
if(isset($_POST['k'])) 	{	$k = $_POST['k'];			} else { $k = ''; }				// Get selected keys for checked rows
if(isset($_POST['ak'])) {	$ak = $_POST['ak'];			} else { $ak = ''; }			// Get all keys
if(isset($_POST['d'])) 	{	$download = $_POST['d'];	} else { $download = "all"; }	// Get download type (checked or all)
if(isset($_POST['t'])) 	{	$t = $_POST['t'];			} else { $t = 'csv'; }			// Get download file format

// Sanity check for file type
if(!in_array($t, $export_file_types)) {
	error_function('You have selected an invalid export format.', $origin);
}

// Construct keys
if($download === "checked" || !$ak) {
	if(count($k) > 0) {
		// If user chooses to only download checked rows
		$downloadkeys = $k;
	} else {
		error_function('You have not selected any rows to be downloaded. Please try again.', $origin);
	}
} else {
	// If user chooses to download entire search result
	$downloadkeys = unserialize($ak);
}

// Convert download keys to binaries
foreach ($downloadkeys as $i => $key) {
	$downloadkeys[$i] = hex2bin($key);
}

// Perform query
$downloadkeys_placeholder = str_repeat('?,', count($downloadkeys)-1).'?';
try {
	// Prepare query
	$q = $db->prepare("SELECT
		lore.PlantID AS PlantID,
		lore.Batch AS Batch,
		lore.ColCoord AS ColCoord,
		lore.RowCoord AS RowCoord,
		lore.Chromosome AS Chromosome,
		lore.Position AS Position,
		lore.Orientation AS Orientation,
		seeds.SeedStock AS SeedStock,
		seeds.Ordering AS Ordering,
		lore.CoordList AS CoordList,
		lore.CoordCount AS CoordCount,
		lore.TotalCoverage AS TotalCoverage,
		lore.FwPrimer AS FwPrimer,
		lore.RevPrimer AS RevPrimer,
		lore.PCRInsPos AS PCRInsPos,
		lore.PCRWT AS PCRWT,
		lore.Ecotype AS Ecotype,
		lore.Version AS Version,
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
			lore.Ecotype = gene.Ecotype AND
			lore.Version = gene.Version
		)

		LEFT JOIN exonins AS exon ON (
			lore.Chromosome = exon.Chromosome AND
			lore.Position = exon.Position AND
			lore.Orientation = exon.Orientation AND
			lore.Ecotype = exon.Ecotype AND
			lore.Version = exon.Version
		)

		LEFT JOIN lore1seeds AS seeds ON (
			lore.PlantID = seeds.PlantID
		)

		WHERE lore.Salt IN ($downloadkeys_placeholder) AND seeds.SeedStock = 1 AND lore.Ecotype = ? AND lore.Version = ?

		GROUP BY lore.Salt
		ORDER BY lore.PlantID
		");

	// Execute query with array of values
	$q->execute(array_merge($downloadkeys, [$ecotype, $version]));

	// Parse output
	if($q->rowCount() > 0) {
		// Determine separator
		if ($t === 'tsv') {
			$sep = "\"\t\"";
		} else {
			$sep = "\",\"";
		}

		// Write header
		$headerData = array("Genome","Plant ID","Batch","Chromosome","Position","Orientation","Gene","Exon","Exon Annotation","Total Coverage","Forward Primer","Reverse Primer","PCR Product Size with Insertion","PCR Product Size in Wild Type","+/-1000bp Insertion Flank","Seed Availability","OrderingAllowed","Column Coordinate","Row Coordinate","Coordinate List","Coordinate Counts");
		$out = "\"".implode($sep, $headerData)."\""."\n";

		while($row = $q->fetch(PDO::FETCH_ASSOC)) {
			// Perform subqueries, because annotations are too long and group_concat may not work well
			$exons = explode(',', $row['Exon']);
			$exons_placeholder = str_repeat('?,', count($exons)-1).'?';
			$subq = $db->prepare("SELECT
				exon.Gene AS Exon,
				anno.Annotation AS Annotation
				FROM exonins AS exon
				LEFT JOIN annotations AS anno ON
					exon.Gene = anno.Gene AND
					exon.Ecotype = anno.Ecotype AND
					exon.Version = anno.Version
				WHERE exon.Gene IN ($exons_placeholder) AND exon.Ecotype = ? AND exon.Version = ?
				GROUP BY exon.Gene
				");
			$subq->execute(array_merge($exons, [$row['Ecotype'], $row['Version']]));
			$exon_arr = array();
			$anno_arr = array();
			while($annos = $subq->fetch(PDO::FETCH_ASSOC)) {
				$exon_arr[] = $annos['Exon'];
				$anno_arr[] = $annos['Annotation'];
			}

			if(count($exon_arr) === 0) {
				$exon_arr = array('n.a.');
			}
			if(count($anno_arr) === 0) {
				$anno_arr = array('n.a.');
			}

			// Row data
			$rowData = array(
				$row['Ecotype'].' '.$row['Version'],
				$row['PlantID'],
				$row['Batch'],
				$row['Chromosome'],
				$row['Position'],
				$row['Orientation'],
				$row['Gene'],
				implode(($t === 'csv' ? "\r\n" : "#"), $exon_arr),
				implode(($t === 'csv' ? "\r\n" : "#"), $anno_arr),
				$row['TotalCoverage'],
				$row['FwPrimer'],
				$row['RevPrimer'],
				$row['PCRInsPos'],
				$row['PCRWT'],
				$row['SeedStock'],
				$row['Ordering'],
				$row['ColCoord'],
				$row['RowCoord'],
				$row['CoordList'],
				$row['CoordCount']
				);

			// Write data
			$out .= "\"".implode($sep, $rowData)."\""."\n";
		}

		$file = "lore1_search_" . $ecotype . '-v' . $version . "_" . date("Y-m-d_H-i-s") . "." . $t;
		header("Content-disposition: csv; filename=\"".$file."\"");
		header("Content-type: application/vnd.ms-excel");
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		print $out;
		exit();
	} else {
		error_function('No results have been returned from the database. Make sure that you have checked at least one row.', $origin);
	}

} catch(PDOException $err) {
	$e = $db->errorInfo();
	error_function('There is a problem generating the download file. We have encountered the following MySQL error: '.$e[1].': '.$e[2].'.', $origin);
}