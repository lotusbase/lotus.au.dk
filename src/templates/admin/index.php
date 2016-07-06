<?php

	// Load site config
	require_once('../config.php');

	// Require authorization
	require_once('auth.php');

	// Declare namespace
	use SameerShelavale\PhpCountriesArray\CountriesArray;
	
?>
<!doctype html>
<html>
<head>
	<title>Dashboard &mdash; LORE1 Resource Site</title>
	<?php include_once('head.php'); ?>
</head>
<body class="admin dash">
	<?php include_once('header.php'); ?>

	<section class="wrapper">
		<?php
		if(isset($_SESSION['activate'])) {
			echo $_SESSION['activate'];
			unset($_SESSION['activate']);
		}

		if(isset($_SESSION['newpass_success'])) {
			echo '<p class="user-message approved">Welcome back, <strong>'.$_SESSION['user']['FirstName'].'</strong>. Your account password has been successfully reset.</p>';
			unset($_SESSION['newpass_success']);
		}

		?>

		<div class="dashboard-item" id="order-metrics">
			<div class="col-2" id="order-country">
				<h3>Top countries by order</h3>
				<?php
					try {
						$q1 = $db->prepare("SELECT
								ord.Country AS Country,
								COUNT(DISTINCT ord.Salt) as OrderCount,
								COUNT(lin.OrderLineID) as LineCount,
								SUM(lin.SeedQuantity) AS SeedCount
							FROM
								orders_unique AS ord
								INNER JOIN orders_lines AS lin
								ON ord.Salt = lin.Salt
							WHERE
								lin.ProcessDate IS NOT NULL
								AND ord.Verified = 1
								AND ord.ShippingEmail = 1
							GROUP BY ord.Country
							ORDER BY OrderCount DESC
							LIMIT 20");

						$q1->execute();

						// Get countries
						$alpha3 = CountriesArray::get2d('alpha3', array('alpha2', 'name'));

						echo '<table data-sort><thead><tr><th data-sort="string">Country</th><th data-sort="int">Orders</th><th data-sort="int">Lines</th><th data-sort="int">Seeds</th></tr></thead><tbody>';
						while($r1 = $q1->fetch(PDO::FETCH_ASSOC)) {
							$cn = $r1['Country'];
							$oc = $r1['OrderCount'];
							$lc = $r1['LineCount'];
							$sc = $r1['SeedCount'];
							echo '<tr><td><img src="./includes/images/flags/'.strtolower($alpha3[$cn]['alpha2']).'.png" title="'.$alpha3[$cn]['name'].' ('.$cn.')" alt="'.$alpha3[$cn]['name'].' ('.$cn.')" class="flag" />'.$alpha3[$cn]['name'].'</td><td>'.$oc.'</td><td>'.$lc.'</td><td>'.$sc.'</td></tr>';
						}
						echo '</tbody></table>';

					} catch(PDOException $e) {
						echo '<p class="user-message warning">We have experienced an error connecting to the database.</p>';
					}
				?>
			</div>

			<div class="col-2" id="order-lines">
				<h3>Top lines</h3>
				<?php
					try {
						$q2 = $db->prepare("SELECT
								lin.PlantID AS PlantID,
								COUNT(DISTINCT ord.Salt) AS OrderCount
							FROM
								orders_unique AS ord
								INNER JOIN orders_lines AS lin
								ON ord.Salt = lin.Salt
							WHERE
								lin.ProcessDate IS NOT NULL
								AND ord.Verified = 1
								AND ord.ShippingEmail = 1
							GROUP BY lin.PlantID
							ORDER BY OrderCount DESC
							LIMIT 20");

						$q2->execute();

						echo '<table data-sort><thead><tr><th data-sort="string-ins">Plant Line</th><th data-sort="int">Orders</th></tr></thead><tbody>';
						while($d2 = $q2->fetch(PDO::FETCH_ASSOC)) {
							$pid = $d2['PlantID'];
							$oc = $d2['OrderCount'];
							echo '<tr><td>'.$pid.'</td><td>'.$oc.'</td></tr>';
						}
						echo '</tbody></table>';

					} catch(PDOException $e) {
						echo '<p class="user-message warning">We have experienced an error connecting to the database.</p>';
					}
				?>				
			</div>
		</div>
	</section>

	<?php include_once('footer.php'); ?>
	<script>
		$(function() {
			$("table[data-sort]").stupidtable();
		});
	</script>
</body>
</html>
