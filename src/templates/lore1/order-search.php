<?php

	// Load site config
	require_once('../config.php');

	// Use country list
	use SameerShelavale\PhpCountriesArray\CountriesArray;

?>
<!doctype html>
<html lang="en">
<head>
	<title>Order Search &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
</head>
<body class="lore1 order-search">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title(array('<em>LORE1</em>', 'Order Search'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>Order Search</h2>
		<p>Search the order history of specific <em>LORE1</em> lines. You can search using a <abbr title="Basic Local Alignment Search Tool">BLAST</abbr> header or a known Plant ID.</p>
		<?php
			// Define search form
			$form = '<form id="order-search__form" method="get" action="'.$_SERVER['PHP_SELF'].'">
				<div class="cols">
					<label for="blhd" class="col-one">BLAST Header <a class="info" data-modal="search-help" data-modal-content="It is in the format of &lt;code&gt;[chromosome number]_[position]_[orientation]&lt;/code&gt;&lt;br /&gt;For example: &lt;code&gt;chr5_3085263_R&lt;/code&gt; or &lt;code&gt;LjSGA_055002_657_R&lt;/code&gt;" title="What is the blast header?">?</a></label>
					<input type="text" name="blast" class="col-two" id="blhd" placeholder="BLAST Header (e.g. chr5_3085263_R or LjSGA_055002_657_R)">

					<div class="separator full-width"><span>or</span></div>

					<label for="pid" class="col-one">Plant ID</label>
					<div class="col-two">
						<input type="text" name="pid" id="pid" placeholder="Plant ID (e.g. 30000146)" autocomplete="off">
						<div id="pid-output"></div>
					</div>
				</div>

				<button type="submit"><span class="pictogram icon-search">Search for related orders</span></button>
			</form>';

			// Check if user has input
			if(isset($_GET) && !empty($_GET)) {
				try {
					$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

					// Fetch plant IDs if blast header is provided
					$blast	= $_GET['blast'];
					$pid	= $_GET['pid'];
					if($blast && !empty($blast)) {

						// Construct q1 fragment
						$blast = explode("_", $blast);
						$blast_values = array();
						if(count($blast) === 4) {
							$blast_values[] = $blast[0]."_".$blast[1];
							$blast_values[] = $blast[2];
							$blast_values[] = $blast[3];
						} elseif(count($blast) === 3) {
							$blast_values[] = $blast[0];
							$blast_values[] = $blast[1];
							$blast_values[] = $blast[2];
						}

						// Prepare and execute q1
						$q1 = $db->prepare("SELECT PlantID FROM lore1ins WHERE Chromosome = ? AND Position = ? AND Orientation = ?");
						$q1->execute($blast_values);

						if($q1->rowCount() < 1) {
							throw new Exception('BLAST header returned no results.');
						} else {
							$row = $q1->fetch(PDO::FETCH_ASSOC);
							$pid = $row['PlantID'];
						}
					}

					// Get order details for line
					$q2 = $db->prepare("SELECT
						t1.FirstName AS FirstName,
						t1.LastName AS LastName,
						t1.Institution AS Institution,
						t1.Country AS Country,
						t1.Timestamp AS OrderTimestamp,
						t2.PlantID AS PlantID,
						t2.ProcessDate AS ProcessDate
					FROM orders_unique AS t1 
					LEFT JOIN orders_lines AS t2 ON t1.Salt = t2.Salt
					WHERE t2.PlantID = :plantid
					ORDER BY t1.Timestamp DESC");
					$q2->bindParam(':plantid', $pid);
					$q2->execute();

					echo '<div class="toggle hide-first"><h3><a href="#" data-toggled="off">Search again</a></h3>'.$form.'</div>';
					echo '<table id="rows">
						<colgroup></colgroup>
						<colgroup></colgroup>
						<colgroup></colgroup>
						<colgroup></colgroup>
						<colgroup></colgroup>
						<colgroup></colgroup>
						<thead>
							<tr>
								<th>Name</th>
								<th>Institution</th>
								<th>Country</th>
								<th><em>LORE1</em> Line</th>
								<th>Order Date</th>
								<th>Process Date</th>
							</tr>
						</thead>
						<tbody>';

					// Countries
					$countries = CountriesArray::get2d('alpha3', array('alpha2', 'name'));

					while($row = $q2->fetch(PDO::FETCH_ASSOC)) {
						$countryName	= $countries[$row['Country']]['name'];
						$alpha2			= $countries[$row['Country']]['alpha2'];
						?>
						<tr>
							<td><?php echo $row['FirstName']." ".$row['LastName']; ?></td>
							<td><?php echo $row['Institution']; ?></td>
							<td><?php echo '<img src="'.WEB_ROOT.'/admin/includes/images/flags/'.strtolower($alpha2).'.png" alt="'.$countryName.'" title="'.$countryName.'"> '.$countryName; ?></td>
							<td><?php echo $row['PlantID']; ?></td>
							<td><?php echo date('j M Y', strtotime($row['OrderTimestamp'])); ?></td>
							<td><?php if(!empty($row['ProcessDate'])) { echo date('j M Y', strtotime($row['ProcessDate'])); } else { echo 'Queued'; } ?></td>
						</tr>
						<?php
					}

					echo '</tbody></table>';
					
				} catch(PDOException $e) {
					echo '<p class="user-message warning">We have encountered a MySQL error: '.$e->getMessage().'</p>';
				} catch(Exception $e) {
					echo '<p class="user-message warning">We have encountered an issue: '.$e->getMessage().'</p>';
				}
			} else {
				echo $form;
			}
		?>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>
