<?php
	// Load site config
	require_once('../config.php');
	
	// Require authorization
	require_once('auth.php');
?>
<!doctype html>
<html>
<head>
	<title>Shipping &mdash; LORE1 Resource Site</title>
	<?php include_once('head.php'); ?>
</head>
<body class="admin shipping">
	<?php include_once('header.php'); ?>

	<section class="wrapper">
		<h2><span class="pictogram icon-paper-plane"></span> Shipping Notifications</h2>
		<p>This page displays orders that you have already processed, but have not sent out a notification email.<?php echo $shippingcount > 0 ? ' You currently have <strong>'.$shippingcount.' '.pl($shippingcount, 'customer').' to notify</strong>.' : ''; ?> Below is the legend to help you to visually identify different types of orders:</p>
		<div class="legend">
			<h3>Legend for shipping managements:</h3>
			<ul>
				<li><span class="pid-item no-seeds">30000001</span><p>No seeds &mdash; when you have entered a seed count of zero (0) and have processed the order. Appears red. Customer will be notified that the line has no seeds.</p></li>
				<li><span class="pid-item has-comment">30000001</span><p>Has comment &mdash; marked with a rounded dot on the top left corner.</p></li>
				<li><span class="pid-item not-processed">30000001</span><p>Unprocessed &mdash; chances are, you have accidentally left out this line when processing. <strong>You will be unable to send email notifications until all lines in an order have been marked as processed.</strong></p></li>
			</ul>
		</div>

		<?php
			$q = $db->prepare("SELECT temp.* FROM (
					SELECT
						ord.OrderID AS OrderKey,
						ord.Salt AS OrderSalt,
						ord.FirstName AS OrderFirstName,
						ord.LastName AS OrderLastName,
						ord.Email AS OrderEmail,
						ord.Institution AS Institution,
						ord.Address AS Address,
						ord.City AS City,
						ord.State AS State,
						ord.PostalCode AS PostalCode,
						ord.Country AS Country,
						ord.Comments AS Comments,
						ord.Verified AS OrderVerified,
						ord.ShippingEmail AS ShippingEmail,
						MIN(lin.ProcessDate) AS PD
					FROM orders_unique AS ord
					LEFT JOIN orders_lines AS lin ON
						lin.Salt = ord.Salt
					WHERE
						ord.ShippingEmail = 0 AND
						ord.Verified = 1
					GROUP BY ord.Salt
				) AS temp
				ORDER BY
					PD DESC,
					OrderKey DESC");
			$q->execute();
		?>

		<div id="shipping-table" class="table">
			<?php while($results = $q->fetch(PDO::FETCH_ASSOC)) {
				// Filter results by escaping HTML tags
				$results = array_map('escapeHTML', $results);
				$ods = $results['OrderSalt'];

				$odq = $db->prepare("SELECT
						PlantID AS PID,
						SeedQuantity AS SQ,
						AdminComments AS AC,
						InternalComments AS IC,
						ProcessDate AS PD
					FROM orders_lines
					WHERE Salt = :salt"
				);
				$odq->bindParam(':salt', $ods);
				$odq->execute();

				$odlist = array();
				$dates = array();
				while($od = $odq->fetch(PDO::FETCH_ASSOC)) {
					$odlist[] = '<li class="'.($od['SQ'] == 0 && !empty($od['PD']) ? 'no-seeds' : '').(empty($od['AC']) ? ' no-comment' : ' has-comment').(empty($od['PD']) ? ' not-processed' : ' processed').'" data-modal title="Details of line '.$od['PID'].' ordered by '.$results['OrderFirstName'].' '.$results['OrderLastName'].'" data-modal-content="The details with regards to this order (ID: '.$ods.')&lt;/p&gt;&lt;h3&gt;Admin comments&lt;/h3&gt;&lt;blockquote&gt;'.($od['AC']!='' ? $od['AC'] : 'No comments for the recepient has been entered.').'&lt;/blockquote&gt;&lt;h3&gt;Internal comments&lt;/h3&gt;&lt;blockquote&gt;'.($od['IC']!='' ? $od['IC'] : 'No internal comments has been entered.').'&lt;/blockquote&gt;">'.$od['PID'].'</li>';
					$dates[] = (empty($od['PD']) ? '' : $od['PD']);
				}

				$orderComplete = !in_array('',$dates,true);
			?>
			<div id="order-<?php echo $ods; ?>" class="order-row order-<?php echo $orderComplete ? 'complete' : 'incomplete'; ?>">
				<div class="order-card order-<?php echo ($results['OrderVerified'] ? 'verified' : 'unverified'); ?>">
					<span class="card-name">
						<?php echo $results['OrderFirstName'].' '.$results['OrderLastName']; ?><span class="pictogram verification-status<?php echo ($results['OrderVerified'] ? ' icon-ok' : ' icon-cancel'); ?>" title="This order has <?php echo ($results['OrderVerified'] ? '' : 'NOT '); ?>been verified"></span>
						<br />
						<?php
							echo '<a class="card-email">'.$results['OrderEmail'].'</a>';
						?>
					</span>
					<span class="card-address"><?php echo $results['Institution'].'<br />'.$results['Address'].'<br />'.$results['City'].', '.($results['State'] ? $results['State'].', ' : '').$results['PostalCode'].' '.$results['Country']; ?></span>
					<span class="card-action">
						<button type="button" class="<?php if($orderComplete) { echo 'action-send'; } else { echo 'action-nosend'; } ?>">
							<?php
								if($orderComplete) {
									echo '<span class="pictogram icon-paper-plane"></span>Send Notification';
								} else {
									echo '<span class="pictogram icon-cancel"></span>Incomplete Order';
								}
							?>
						</button>
					</span>
					<span class="card-action">
						<button type="button" class="action-review">
							<a href="orders.php?salt=<?php echo $ods; ?>">
								<span class="pictogram icon-reply"></span>Review Order
							</a>
						</button>
					</span>
				</div>

				<div class="order-details">
					<ul>
						<?php
							foreach($odlist as $item) {
								echo $item;
							}
						?>
					</ul>					
				</div>
			</div>
			<?php } ?>
		</div>

	</section>

	<?php include_once('footer.php'); ?>
</body>
</html>
