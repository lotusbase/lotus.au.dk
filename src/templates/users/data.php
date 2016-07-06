<?php
	// Get important files
	require_once('../config.php');
	require_once('./auth.php');

	// Use country list
	use SameerShelavale\PhpCountriesArray\CountriesArray;
?>
<!doctype html>
<html lang="en">
<head>
	<title>User Dashboard&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/users.min.css" type="text/css" media="screen" />
</head>
<body class="users data theme--white init-scroll--disabled">
	<?php
		// Generate header
		$header = new \LotusBase\PageHeader();
		echo $header->get_header();
	?>

	<?php echo get_breadcrumbs(); ?>

	<section class="wrapper">
		<div id="data-tabs">

			<div id="data-tabs__nav" class="cols align-items__flex-end ui-tabs-nav__wrapper">
				<h1>Associated data</h1>
				<ul class="minimal">
					<li><a href="#lore1-orders" data-custom-smooth-scroll>LORE1 orders</a></li>
					<li><a href="#cornea-networks" data-custom-smooth-scroll>CORNEA</a></li>
				</ul>
			</div>

			<div id="lore1-orders">

				<?php
					function order_status($row) {
						// Order status
						$order_status = 0;
						if($row['VerificationStatus']) {
							$order_status = 1;
						}
						if($row['ProcessedLines'] > 0) {
							$order_status = 2;
						}
						if($row['ProcessedLines'] === $row['TotalLines']) {
							$order_status = 3;
						}
						if($row['ShippingStatus']) {
							$order_status = 4;
						}

						// Array
						$order_statuses = array(
							array(
								'desc' => 'Awaiting user verification',
								'icon' => 'icon-attention',
								'step_title' => 'Verify',
								'description' => 'We have received your order, but will not process it until you have verified it. Please follow the instructions in the email you have received from us.'
								),
							array(
								'desc' => 'Order submitted to queue',
								'icon' => 'icon-clock',
								'step_title' => 'Queued',
								'description' => 'Thank you for verifying your order&mdash;it has been submitted to the processing queue. We will have a look at your order at the next possibile opportunity.'
								),
							array(
								'desc' => 'Order is currently being processed',
								'icon' => 'icon-clock',
								'step_title' => 'Processing',
								'description' => 'Your order is currently being processed.'
								),
							array(
								'desc' => 'Order procssed on '.date("M j, Y", strtotime($row['ProcessDate'])).'. Awaiting for dispatch',
								'icon' => 'icon-ok',
								'step_title' => 'Dispatching',
								'description' => 'Your order has been processed, and is currently awaiting to be dispatched for shipping.'
								),
							array(
								'desc' => 'Order shipped'.($row['ShippedTimestamp'] ? ' on '.date("M j, Y", strtotime($row['ShippedTimestamp'])) : ''),
								'icon' => 'icon-paper-plane',
								'step_title' => 'Shipped',
								'description' => 'Your order has been shipped. '.($row['ShippedTimestamp'] ? 'You have received an email from us on: '.date("F j, Y, g:ia", strtotime($row['ShippedTimestamp'])).' (Central European Time, GMT'.(date('P')).'). ' : '').'Regardless of your geographical location, you should expect to receive your shipment within 4 weeks.'
								)
							);

						// Return
						return $order_statuses[$order_status];
					}

					try {
						$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306", DB_USER, DB_PASS);
						$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

						// Prepare queries
						$orders_query = $db->prepare('SELECT
								t1.Salt AS OrderSalt,
								t1.Institution AS Organization,
								t1.Address AS Address,
								t1.City AS City,
								t1.State AS State,
								t1.PostalCode AS PostalCode,
								t1.Country AS Country,
								t1.Comments AS Comments,
								t1.Timestamp AS OrderTime,
								t1.Verified AS VerificationStatus,
								t1.Shipped AS ShippingStatus,
								t1.ShippedTimestamp AS ShippedTimestamp,
								t1.Payment AS Payment,
								t1.PaymentWaiver AS PaymentWaiver,
								GROUP_CONCAT(t2.PlantID ORDER BY t2.OrderLineID ASC) AS PlantID,
								GROUP_CONCAT(t2.SeedQuantity ORDER BY t2.OrderLineID ASC) AS SeedQuantity,
								MAX(t2.ProcessDate) AS ProcessDate,
								GROUP_CONCAT(t2.AdminSalt ORDER BY t2.OrderLineID ASC) AS AdminSalt,
								GROUP_CONCAT(t2.AdminComments ORDER BY t2.OrderLineID ASC) AS AdminComments,
								COUNT(t2.ProcessDate) AS ProcessedLines,
								COUNT(t2.PlantID) AS TotalLines
							FROM orders_unique AS t1
							LEFT JOIN orders_lines AS t2 ON
								t1.Salt = t2.Salt
							WHERE t1.UserSalt = ?
							GROUP BY t1.Salt
							ORDER BY t1.Timestamp DESC');

						// Execute
						$orders_query->execute(array(
							$user['Salt']
							));
						if($orders_query->rowCount()) {

							$order_count = $orders_query->rowCount();

							echo '<p>We have located <strong>'.$order_count.' '.pl($orders_query->rowCount(), 'order').'</strong> from our system that was placed under your email.';
							echo '<p class="user-message note">If you have placed orders with an email other than the current one registered with this account (<a href="mailto: '.$user['Email'].'"><strong>'.$user['Email'].'</strong></a>), you might not be able to see it. Please <a href="'.WEB_ROOT.'/meta/contact?topic=transfer-lore1-orders">contact us</a> and include your old email(s), so we can map the missing orders to your account.</p>';
							echo '<ul class="list--big user__lore1-orders">';
							while($row = $orders_query->fetch(PDO::FETCH_ASSOC)) {

								$status = order_status($row);
								$pid_list = split(',', $row['PlantID']);

								$countries = CountriesArray::get2d('alpha3', array('alpha2', 'name'));
								$country_name = $countries[$row['Country']]['name'];

								echo '<li>';
								echo '<div class="info cols align-items__center">';
								echo '<div class="order-status"><h4>Status: '.$status['step_title'].'</h4><p>'.$status['desc'].' &middot; '.($row['Organization'] ? $row['Organization'].', ' : '').$country_name.'</p>';
								echo '</div>';
								echo '<a href="'.WEB_ROOT.'/lore1/order-status?id='.$row['OrderSalt'].'" class="button button--small"><span class="icon-search">View order</span></a>';
								echo '</div>';
								echo '<ul class="list--floated">';
								foreach ($pid_list as $pid) {
									echo '<li><a href="'.WEB_ROOT.'/lore1/search?v=3.0&pid='.$pid.'">'.$pid.'</a></li>';
								}
								echo '</ul>';
								echo '</li>';
							}
							echo '</ul>';
						} else {
							echo '<p>You have not placed any <em>LORE1</em> orders yet. You can <a href="'.WEB_ROOT.'/lore1/search">search for your <em>LORE1</em> line(s) of interest</a>, and <a href="'.WEB_ROOT.'/lore1/order">order them</a>.</p>';
						}
					} catch(PDOException $e) {

					}
				?>
			</div>

			<div id="cornea-networks">
			<?php
			
				try {
					// Database connection
					$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306", DB_USER, DB_PASS);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

					// Prepare and execute statement
					$cornea_query = $db->prepare("SELECT * FROM correlationnetworkjob WHERE owner_salt = ? ORDER BY id DESC");
					$cornea_query->execute(array($user['Salt']));

					// Retrieve results
					if($cornea_query->rowCount() > 0) {
						$out = '
						<p>Here is the list of all CORNEA jobs you have created. Expired jobs are no longer available on the server, but we have stored the metadata in case you want to recreate said networks.</p>
						<table class="table--dense"><thead>
							<tr>
								<th class="align-center" colspan="4">Job info</th>
								<th class="align-center" colspan="4">Metadata</th>
								<th rowspan="2">Action</th>
							</tr>
							<tr>
								<th>Status</th>
								<th>Dataset</th>
								<th>Threshold</th>
								<th>Min. cluster size</th>
								<th>Nodes</th>
								<th>Edges</th>
								<th>Clusters</th>
								<th>Elapsed time</th>
							</tr>
						</thead><tbody>';

						// Store max values
						$cornea_max = array(
							'node_count' => -Inf,
							'edge_count' => -Inf,
							'cluster_count' => -Inf,
							'time_elapsed' => -Inf
							);

						while($row = $cornea_query->fetch(PDO::FETCH_ASSOC)) {
							
							// Calculate max of each column
							foreach($row as $k => $v) {
								if(in_array($k, array('node_count', 'edge_count', 'cluster_count'))) {
									if($v > $cornea_max[$k]) {
										$cornea_max[$k] = $v;
									}
								}
							}
							$time_elapsed = strtotime($row['end_time'])-strtotime($row['start_time']);
							if($time_elapsed > $cornea_max['time_elapsed']) {
								$cornea_max['time_elapsed'] = $time_elapsed;
							}
							$row['time_elapsed'] = $time_elapsed;

							// Push rows
							$cornea_jobs[] = $row;

						}

						foreach($cornea_jobs as $job) {
							// Show job status
							if($job['status'] == 3) {
								$job_status = '<span class="user-message approved icon-ok status minimal" title="Job completed'.(isset($job['start_time']) && !empty($job['start_time']) ? ' on '.$job['end_time'] : '').'">Finished</span>';
							} elseif($job['status'] == 2) {
								$job_status = '<span class="user-message icon-clock status minimal" title="Job processing started'.(isset($job['start_time']) && !empty($job['start_time']) ? ' on '.$job['start_time'] : '').'">Processing</span>';
							} elseif($job['status'] == 1) {
								$job_status = '<span class="user-message reminder icon-clock status minimal" title="Job submitted'.(isset($job['submit_time']) && !empty($job['submit_time']) ? ' on '.$job['submit_time'] : '').'">Queued</span>';
							} elseif($job['status'] == 4) {
								$job_status = '<span class="user-message warning icon-clock status minimal" title="Error encountered when attempting to process job'.(isset($job['status_reason']) && !empty($job['status_reason']) ? ': '.$job['status_reason'] : '.').'">Error</span>';
							} elseif($job['status'] == 5) {
								$job_status = '<span class="user-message unknown icon-clock status minimal" title="Job is more than 30 days old and has expired.">Expired</span>';
							} else {
								$job_status = '<span class="user-message unknown icon-attention status minimal" title="Job status unknown">Unknown</span>';
							}

							// Display rows
							$out .= '<tr>
								<td>'.$job_status.'</td>
								<td>'.$job['dataset'].'</td>
								<td>'.$job['threshold'].'</td>
								<td>'.$job['minimum_cluster_size'].'</td>
								<td style="background-image: linear-gradient(90deg, rgba(51,101,138,0.25) '.floatval($job['node_count']/$cornea_max['node_count']*100).'%, rgba(51,101,138,0) '.floatval($job['node_count']/$cornea_max['node_count']*100).'%);">'.number_format($job['node_count'], 0, '.', ',').'</td>
								<td style="background-image: linear-gradient(90deg, rgba(51,101,138,0.25) '.floatval($job['edge_count']/$cornea_max['edge_count']*100).'%, rgba(51,101,138,0) '.floatval($job['edge_count']/$cornea_max['edge_count']*100).'%);">'.number_format($job['edge_count'], 0, '.', ',').'</td>
								<td style="background-image: linear-gradient(90deg, rgba(51,101,138,0.25) '.floatval($job['cluster_count']/$cornea_max['cluster_count']*100).'%, rgba(51,101,138,0) '.floatval($job['cluster_count']/$cornea_max['cluster_count']*100).'%);">'.number_format($job['cluster_count'], 0, '.', ',').'</td>
								<td style="background-image: linear-gradient(90deg, rgba(51,101,138,0.25) '.floatval($job['time_elapsed']/$cornea_max['time_elapsed']*100).'%, rgba(51,101,138,0) '.floatval($job['time_elapsed']/$cornea_max['time_elapsed']*100).'%);">'.friendly_duration(strtotime($job['end_time'])-strtotime($job['start_time'])).'</td>
								<td>
									<div class="dropdown button button--small">
										<span class="dropdown--title">Action</span>
										<ul class="dropdown--list">
											<li><a href="'.WEB_ROOT.'/tools/cornea/job/'.$job['hash_id'].'" class="icon-network">View network</a></li>
											'.($job['status'] == 3 ? '<li><a href="'.WEB_ROOT.'/api/v1/cornea/job/data/'.$job['hash_id'].'" class="icon-download">Export gzipped JSON</a></li>' : '').'
										</ul>
									</div>
								</td>
							</tr>';
						}
						$out .= '</tbody></table>';

						echo $out;
					} else {
						echo '<p>You have not started a coexpression network analysis job yet. <a href="'.WEB_ROOT.'/tools/cornea">Create a new network</a>.</p>';
					}

				} catch(PDOException $e) {
					echo '<p class="user-message warning">Unable to esablish database connection. Should this problem persist, please contact the site administrator.</p>';
				}
			?>
			</div>
		</div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/users.min.js"></script>
</body>
</html>
