<div id="wrap">
	<div id="container">
		<header class="wrapper">
			<h1>Dashboard</h1>
			<p>Welcome, <?php echo $user['FirstName'];?>.
				<?php
					try {
						// Get number of orders
						$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
						$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
						
						$orderquery = $db->prepare("SELECT COUNT(*) AS OrderCount FROM (SELECT ord.Salt
							FROM orders_unique AS ord
							LEFT JOIN orders_lines AS lin ON
								ord.Salt = lin.Salt
							WHERE lin.ProcessDate IS NULL
							AND ord.Verified = 1
							GROUP BY ord.Salt) AS temp");
						$orderquery->execute();

						$orderdata = $orderquery->fetch(PDO::FETCH_ASSOC);
						$ordercount = $orderdata['OrderCount'];
						if($ordercount > 0) {
							echo 'There are currently <a href="orders.php?view=unprocessed"><span>'.$ordercount.'</span> unprocessed '.pl($ordercount, "order", "orders").'</a>';
						} else {
							echo 'There are currently no outstanding orders. Hurrah!';
						}
						echo '&nbsp;&nbsp;|&nbsp;&nbsp;<a href="../">Return to LORE1 Site</a>';

						// Get shipping query
						$shippingquery = $db->prepare("SELECT COUNT(temp.Salt) AS ShippingCount
							FROM (
								SELECT lin.Salt, MIN(lin.ProcessDate) AS PD
								FROM orders_lines AS lin
								LEFT JOIN orders_unique AS ord ON
									lin.Salt = ord.Salt
								WHERE ord.ShippingEmail = 0
								GROUP BY Salt
								) AS temp
							WHERE temp.PD IS NOT NULL");
						$shippingquery->execute();
						$shippingdata = $shippingquery->fetch(PDO::FETCH_ASSOC);
						$shippingcount = $shippingdata['ShippingCount'];

					} catch(PDOException $e) {
						echo 'We have experienced an issue connecting to the database.';
					}

				?>
			</p>
			<nav>
				<ul>
					<li class="dash"><a href="./" title="Dashboard"><span class="pictogram icon-home"></span>Dash</a></li>
					<li><a href="orders.php<?php if($ordercount > 0) { echo '?view=unprocessed'; } ?>" title="Seed Orders"<?php if (strpos($_SERVER['PHP_SELF'], 'orders.php')) echo ' class="current"';?>><span class="pictogram icon-basket"></span>Orders <?php if($ordercount > 0) { ?><span class="notification"><?php echo $ordercount; ?></span><?php } ?></a></li>
					<li><a href="shipping.php" title="Shipping"<?php if (strpos($_SERVER['PHP_SELF'], 'shipping.php')) echo ' class="current"';?>><span class="pictogram icon-map"></span>Shipping <?php if($shippingcount > 0) { ?><span class="notification"><?php echo $shippingcount; ?></span><?php } ?></a></li>
					<li><a href="downloads.php" title="Downloads"<?php if (strpos($_SERVER['PHP_SELF'], 'downloads.php')) echo ' class="current"';?>><span class="pictogram icon-download"></span>Downloads</a></li>
					<li><a href="settings.php" title="User Settings"<?php if (strpos($_SERVER['PHP_SELF'], 'settings.php')) echo ' class="current"';?>><span class="pictogram icon-cog"></span>Settings</a></li>
					<li><a href="users.php" title="Site Admins"<?php if (strpos($_SERVER['PHP_SELF'], 'users.php')) echo ' class="current"';?>><span class="pictogram icon-user"></span>Accounts</a></li>
					<li><a href="../readme/" title="Help"><span class="pictogram icon-lifebuoy"></span>Help</a></li>
					<li><a href="logout.php" title="Logout / Logoff"<?php if (strpos($_SERVER['PHP_SELF'], 'logout.php')) echo ' class="current"';?>><span class="pictogram icon-logout"></span>Logout</a></li>
				</ul>
			</nav>
		</header>