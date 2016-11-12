<?php

	// Load site config
	require_once('../config.php');

	// Use country list
	use SameerShelavale\PhpCountriesArray\CountriesArray;

	// Search status
	$searched = false;
	$error = '';

	if($_GET && isset($_GET['id']) && !empty($_GET['id'])) {
		if(preg_match('/^[A-Fa-f0-9]{32}$/', $_GET['id'])) {
			$searched = true;
		} else {
			$error = 'You have provided an invalid order identifier. Make sure that it is a 32-character hexademical string.';
		}
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>Order Status &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/order.min.css" type="text/css" media="screen">
</head>
<body class="lore1 order-status">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title(array('<em>LORE1</em>', 'Order Status'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>Order Status</h2>
		<?php if($searched) { ?>
			<div class="toggle hide-first"><h3><a href="#" data-toggled="off">Search again</a></h3>
		<?php } else { ?>
			<p>Track the status of your <em>LORE1</em> order with the form below by pasting the <strong>32-character hexademical order identifier</strong> you have received in an email:</p>
			<?php echo (!empty($error) > 0 ? '<p class="user-message warning"><span class="icon-attention"></span>'.$error.'</p>' : ''); ?>
		<?php } ?>
		<form id="order-status__form" method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<div class="cols">
				<label class="col-one" for="order-id">Order ID</label>
				<input class="col-two" type="text" id="order-id" name="id" placeholder="32-character order identifier" />
			</div>
			<button type="submit"><span class="icon-search">Get order status</span></button>
		</form>
		<?php if($searched) { ?>
			</div>
		<?php
			try {
				// Database connection
				$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

				// Query for order
				$q1 = $db->prepare('SELECT
					t1.FirstName AS FirstName,
					t1.LastName AS LastName,
					t1.Email AS Email,
					t1.Institution AS Institution,
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
					GROUP_CONCAT(t2.ProcessDate ORDER BY t2.OrderLineID ASC) AS ProcessDate,
					GROUP_CONCAT(t2.AdminSalt ORDER BY t2.OrderLineID ASC) AS AdminSalt,
					GROUP_CONCAT(t2.AdminComments ORDER BY t2.OrderLineID ASC) AS AdminComments,
					COUNT(t2.ProcessDate) AS ProcessedLines,
					COUNT(t2.PlantID) AS TotalLines
				FROM orders_unique AS t1
				LEFT JOIN orders_lines AS t2 ON
					t1.Salt = t2.Salt
				WHERE t1.Salt = ?
				GROUP BY t1.Salt
				ORDER BY t1.Timestamp DESC
				LIMIT 1');
				$q1->execute(array($_GET['id']));

				// Query for other unprocessed orders
				$q2 = $db->prepare('SELECT
					t1.Salt AS Salt,
					COUNT(t2.ProcessDate) AS ProcessedLines,
					COUNT(t2.PlantID) AS TotalLines
				FROM orders_unique AS t1
				LEFT JOIN orders_lines AS t2 ON
					t1.Salt = t2.Salt
				WHERE
					t1.Verified = 1
				GROUP BY t1.Salt
				HAVING
					COUNT(t2.ProcessDate) < COUNT(t2.PlantID)
					');
				$q2->execute();

				// Retrieve order data
				$row = $q1->fetch(PDO::FETCH_ASSOC);

				// Retrieve queue data and set queue message
				$queue_size = $q2->rowCount();
				$queue_message = '';
				if($queue_size === 1) {
					$queue_message = 'Your order is the only one in the queue, and it will be processed immediately.';
				} else if($queue_size > 1) {
					$queue_message = 'There are <strong>'.($queue_size-1).' other '.pl($queue_size-1, 'order', 'orders').'</strong> currently in the queue.';
				}

				// Reverse geocoding
				$countries = CountriesArray::get2d('alpha3', array('alpha2', 'name'));
				$country_name = $countries[$row['Country']]['name'];
				$country_alpha2 = $countries[$row['Country']]['alpha2'];
				$mapbox_curl = curl_init();
				curl_setopt($mapbox_curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($mapbox_curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($mapbox_curl, CURLOPT_URL, 'https://api.mapbox.com/geocoding/v5/mapbox.places/'.urlencode(preg_replace('/\s{2,}/', ' ', implode(' ', array($row['City'], $row['State'])))).'.json?country='.strtolower($countries[$row['Country']]['alpha2']).'&access_token='.MAPBOX_API_KEY);
				$mapbox_result = curl_exec($mapbox_curl);
				curl_close($mapbox_curl);



				$places = json_decode($mapbox_result, true);
				$map_image = null;
				if(count($places['features'])) {
					$c = $places['features'][0]['geometry']['coordinates'];
					$map_image = 'https://api.mapbox.com/v4/lotusbase.o9e761mh/'.$c[0].','.$c[1].',6/1280x1280.png?access_token='.MAPBOX_API_KEY;
				}

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
				$order_statuses = array(
					array(
						'title' => 'Please verify order',
						'icon' => 'icon-attention',
						'step_title' => 'Verify',
						'description' => 'We have received your order, but will not process it until you have verified it. Please follow the instructions in the email you have received from us.'
						),
					array(
						'title' => 'Submitted to queue',
						'icon' => 'icon-clock',
						'step_title' => 'Queued',
						'description' => 'Thank you for verifying your order&mdash;it has been submitted to the processing queue. We will have a look at your order at the next possibile opportunity. '.$queue_message
						),
					array(
						'title' => 'Order being processed',
						'icon' => 'icon-clock',
						'step_title' => 'Processing',
						'description' => 'Your order is currently being processed.'
						),
					array(
						'title' => 'Order awaiting for dispatch',
						'icon' => 'icon-ok',
						'step_title' => 'Dispatching',
						'description' => 'Your order has been processed, and is currently awaiting to be dispatched for shipping.'
						),
					array(
						'title' => 'Order shipped',
						'icon' => 'icon-paper-plane',
						'step_title' => 'Shipped',
						'description' => 'Your order has been shipped. '.($row['ShippedTimestamp'] ? 'You have received an email from us on: '.date("F j, Y, g:ia", strtotime($row['ShippedTimestamp'])).' (Central European Time, GMT'.(date('P')).'). ' : '').'Regardless of your geographical location, you should expect to receive your shipment within 4 weeks.'
						)
					);

				// LORE1 lines
				$lines = explode(',', $row['PlantID']);
				$lines_seed_count = explode(',', $row['SeedQuantity']);
				$lines_comment = explode(',', $row['AdminComments']);

				?>
				<div id="order-status">
					<h3 class="align-center"><span class="<?php echo $order_statuses[$order_status]['icon']; ?> icon--no-spacing icon--big"><?php echo $order_statuses[$order_status]['title']; ?></span></h3>
					<p><?php echo $order_statuses[$order_status]['description']; ?></p>
					<div class="progress">
						<div class="progress__track">
							<span class="progress__value" style="width: <?php echo $order_status/(count($order_statuses)-1)*100; ?>%;"></span>
						</div>
						<ol class="progress__steps cols flex-wrap__no-wrap">
							<?php
								foreach ($order_statuses as $i => $status) {
									echo '<li class="progress__step '.($i <= $order_status ? 'step--completed' : '').'"><span class="step__count">'.($i + 1).'</span><span class="step__title">'.$status['step_title'].'</span></li>';
								}
							?>
						</ol>
					</div>
				</div>

				<div id="order-overview" class="cols">
					<div id="order-overview__meta">

						<div id="order-overview__shipping">
							<h4>Contact &amp; Shipping details</h4>
							<div id="order-overview__vcard" class="vcard">
								<p><span class="fn"><?php echo $row['FirstName'].' '.$row['LastName']; ?></span><br /><a href="mailto:<?php echo $row['Email']; ?>" class="email"><?php echo $row['Email']; ?></a></p>
								<p class="adr"><span class="organization"><?php echo $row['Institution']; ?></span><br /><span class="street-address"><?php echo $row['Address']; ?></span><br /><span class="city"><?php echo $row['City']; ?></span>, <?php isset($row['State']) ? '<span class="state">'.$row['State'].'</span> ' : ''; ?><span class="postal-code"><?php echo $row['PostalCode']; ?></span><br /><span class="country-name"><img src="<?php echo WEB_ROOT; ?>/admin/includes/images/flags/<?php echo strtolower($country_alpha2);?>.png" alt="<?php echo $country_name; ?>" title="<?php echo $country_name; ?>"> <?php echo $country_name; ?></span></p>
							</div>
						</div>

						<div id="order-overview__lore1-lines">
							<h4><em>LORE1</em> lines</h4>
							<p>You have ordered <span id="order-overview__lore1-lines-count"><strong><?php echo count($lines); ?></strong> <em>LORE1</em> lines</span>:</p>
							<ul id="order-overview__lore1-lines-list" class="list--floated"><li><?php echo implode('</li><li>', $lines); ?></li></ul>
							<?php if($row['PaymentWaiver'] === 0) { ?>
							<table id="order-overview__lore1-lines-cost-table" class="table--no-stripes table--no-borders">
								<tbody>
									<tr><th>Cost of lines<br><small>Unit cost: DKK 100.00</small></th><td data-type="num"><span id="order-overview__lore1-lines-cost"><?php echo count($lines); ?> × 100 = DKK <?php echo number_format(count($lines)*100, 2, '.', ''); ?></span></td></tr>
									<tr><th>Handling fee</th><td data-type="num">DKK 100.00</td></tr>
									<tr class="total"><th>Total</th><td data-type="num"><span id="order-overview__lore1-lines-total">DKK <?php echo number_format(count($lines)*100 + 100, 2, '.', ''); ?></span></td></tr>
								</tbody>
							</table>
							<?php } else { ?>
							<p class="user-message approved">Order is placed under the payment waiver scheme, and lines are shipped free-of-charge.</p>
							<?php } ?>
						</div>
					</div>
					<?php if($map_image) { ?>
					<div id="order-overview__map" style="<?php echo 'background-image: url('.$map_image.')'; ?>"><div class="tooltip position--top show">Your approximate location by address provided.</div></div>
					<?php } ?>
				</div>
				<?php
					if($order_status >= 3) {
				?>
					<h3>Additional details</h3>
					<p>Your order have been successfully processed with the following details. <?php ($row['ShippedTimestamp'] ? 'It is also available in the email we have sent you on : '.date("F j, Y, g:ia", strtotime($row['ShippedTimestamp'])).' (Central European Time, GMT'.(date('P')).'). ' : '')?></p>
					<table id="rows">
						<thead>
							<tr>
								<th>Plant ID</th>
								<th>Seed Quantity</th>
								<th>Admin comment(s)</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach($lines as $i => $line) { ?>
							<tr>
								<td><div class="dropdown button">
									<span class="dropdown--title"><?php echo $line; ?></span>
									<ul class="dropdown--list">
										<li><a href="<?php echo WEB_ROOT.'/lore1/search?v=3.0&pid='.$line; ?>" title="Search database for this LORE1 line: <?php echo $line; ?>"><span class="icon-search">Search the <em>LORE1</em> v3.0 database</span></a></li>
										<li><a href="<?php echo WEB_ROOT.'/lore1/order-search?pid='.$line; ?>"><span class="icon-clock">Check order history</span></a></li>
									</ul>
								</div></td>
								<td><?php echo $lines_seed_count[$i]; ?></td>
								<td><?php echo ($lines_comment[$i] ? $lines_comment[$i] : 'n.a.'); ?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				<?php }

			} catch(PDOException $e) {
				echo '<p class="user-message warning">We have encountered a database error: '.$e->getMessage().'</p>';
			} catch(Exception $e) {
				echo '<p class="user-message warning">We have encountered an error: '.$e->getMessage().'</p>';
			}
		} ?>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/lore1.min.js"></script>
</body>
</html>
