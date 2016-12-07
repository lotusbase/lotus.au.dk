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
<body class="users dashboard theme--white init-scroll--disabled">
	<?php

		// Hello!
		$hello = array(
			'English' => 'Hello',
			'French' => 'Salut',
			'German' => 'Hallo',
			'Danish' => 'Hej',
			'Italian' => 'Ciao',
			'Czech' => 'Ahoj',
			'Greek' => '&#915;&#949;&#953;&#945; &#963;&#959;&#965;',
			'Croatian' => 'Bog',
			'Dutch' => 'Hallo',
			'Swedish' => 'Hej',
			'Polish' => 'Dzień dobry',
			'Spanish' => 'Hola',
			'Japanese' => '&#12371;&#12435;&#12395;&#12385;&#12399;',
			'Chinese' => '&#20320;&#22909;',
			'Thai' => 'Sawatdi',
			'Russian' => '&#1047;&#1076;&#1088;&#1072;&#1074;&#1089;&#1090;&#1074;&#1091;&#1081;&#1090;&#1077;',
			'Serbian' => 'Zdravo',
			'Turkish' => 'Merhaba',
			'Portugese' => 'Olá',
			'Korean' => '&#50504;&#45397;&#54616;&#49464;&#50836;'
			);
		$random_hello = array_rand($hello);

		$header_content = '<div class="align-center"><h1>'.$hello[$random_hello].', '.$user['FirstName'].'.</h1><span class="byline">That’s “<em>hello</em>” in '.$random_hello.'</span></div>';

		// Generate header
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content($header_content);
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title('Dashboard');
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<?php
			if(isset($_SESSION['user_privilege_error'])) {
				echo '<p class="user-message warning">'.$_SESSION['user_privilege_error'].'</p>';
				unset($_SESSION['user_privilege_error']);
			}

			if(isset($_SESSION['user_login_message'])) {
				echo '<p class="user-message '.implode(' ', $_SESSION['user_login_message']['classes']).'">'.$_SESSION['user_login_message']['message'].'</p>';
				unset($_SESSION['user_login_message']);
			}
		?>
		<div id="account-tabs">

			<div id="dashboard-tabs__nav" class="cols align-items__flex-end ui-tabs-nav__wrapper">
				<h1>Recent activity</h1>
				<ul class="minimal">
					<li><a href="#lore1-orders" data-custom-smooth-scroll><em>LORE1</em> orders</a></li>
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
						ORDER BY t1.Timestamp DESC
						LIMIT 5');

					// Execute
					$orders_query->execute(array(
						$user['Salt']
						));
					if($orders_query->rowCount()) {
						echo '<p>Here is a list of the most recent LORE1 line orders you have placed. To view all the <em>LORE1</em> lines ordered with your account, please visit your <a href="'.WEB_ROOT.'/users/data#lore1-orders" title="View all LORE1 orders">accounts page</a>.</p><ul class="list--big user__lore1-orders" id="dashboard__recent-lore1-orders">';
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
					echo '<p class="user-message warning">'.$e->getMessage().'</p>';
				}
			?>
			</div>

			<div id="cornet-networks">
			<?php
			// Database connection
				try {
					// Prepare and execute statement
					$cornea_query = $db->prepare("SELECT * FROM correlationnetworkjob WHERE owner_salt = ? ORDER BY id DESC LIMIT 10");
					$cornea_query->execute(array($user['Salt']));

					// Retrieve results
					if($cornea_query->rowCount() > 0) {
						$out = '
						<p>Here is the list of the most recent CORNEA jobs you have created. To view all the <em>CORNEA</em> jobs that you have created, please visit your <a href="'.WEB_ROOT.'/users/data#cornea-networks" title="View all CORNEA jobs">data page</a>.</p>
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
							'node_count' => -INF,
							'edge_count' => -INF,
							'cluster_count' => -INF,
							'time_elapsed' => -INF
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
							if($time_elapsed < 0) {
								$time_elapsed = 0;
							}
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
								$job_status = '<span class="user-message warning icon-attention status minimal" title="Error encountered when attempting to process job'.(isset($job['status_reason']) && !empty($job['status_reason']) ? ': '.$job['status_reason'] : '.').'">Error</span>';
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
								<td style="background-image: linear-gradient(90deg, rgba(51,101,138,0.25) '.floatval($job['time_elapsed']/$cornea_max['time_elapsed']*100).'%, rgba(51,101,138,0) '.floatval($job['time_elapsed']/$cornea_max['time_elapsed']*100).'%);">'.friendly_duration($job['time_elapsed']).'</td>
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
