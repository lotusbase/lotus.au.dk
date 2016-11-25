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

			$q1 = $db->prepare("SELECT
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
					lore.PCRWT
				FROM lore1ins AS lore
				WHERE lore.PlantID = ? AND lore.Version = '3.0'");
			$q1->execute(array($id));

			if(!$q1->rowCount()) {
				throw new Exception('<em>LORE1</em> line does not exist. Please ensure that you have entered a valid line identifier.');
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
			'<em>LORE1</em>' => 'lore1',
			$id => $id
		));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2><?php echo $id; ?></h2>
		
		<div id="view__lore1ins" class="view__facet">
			<h3>Insertion data<?php
				if($q1->rowCount()) {
					echo ' <span class="badge">'.$q1->rowCount().'</span>';
				}
			?></h3>
			<p>This table does not show all the data pertaining to each insertion due to space contraints. To view additional columns, please export the dataset.</p>
			<div class="table-overflow">
				<table class="table--dense">
					<thead>
						<tr>
							<th scope="col"><abbr title="Chromosome">Chr</abbr></th>
							<th scope="col"><abbr title="Position">Pos</abbr></th>
							<th scope="col"><abbr title="Orientation">Orn</abbr></th>
							<th scope="col">Total coverage</th>
							<!--<th scope="col">Forward primer</th>
							<th scope="col">Reverse primer</th>-->
							<th scope="col">PCR size for WT</th>
							<th scope="col">PCR size for insert</th>
							<th scope="col"><abbr title="Coordinates">Coord</abbr></th>
							<th scope="col">Coordinate list</th>
							<th scope="col">Coordinate count</th>
						</tr>
					</thead>
					<tbody>
					<?php while($r = $q1->fetch(PDO::FETCH_ASSOC)) { ?>
						<tr>
							<td><?php echo $r['Chromosome']; ?></td>
							<td><?php echo $r['Position']; ?></td>
							<td><?php echo $r['Orientation']; ?></td>
							<td><?php echo $r['TotalCoverage']; ?></td>
							<!--<td><?php echo $r['FwPrimer']; ?></td>
							<td><?php echo $r['RevPrimer']; ?></td>-->
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
		</div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/view/lore1.min.js"></script>
</body>
</html>