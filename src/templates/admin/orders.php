<?php
	// Require authorization
	require_once('../config.php');
	require_once('auth.php');

	// Declare namespace
	use SameerShelavale\PhpCountriesArray\CountriesArray;

?>
<!doctype html>
<html>
<head>
	<title>Orders &mdash; LORE1 Resource Site</title>
	<?php include_once('head.php'); ?>
</head>
<body class="admin orders">
	<?php include_once('header.php'); ?>

	<section class="wrapper" id="orders-section">
		<?php

		// Fetch and sanitize variables
		if(isset($_GET['fname'])) { $fname = $_GET['fname'];		} else { $fname = ''; }
		if(isset($_GET['lname'])) { $lname = $_GET['lname'];		} else { $lname = ''; }
		if(isset($_GET['email'])) { $email = $_GET['email']; 	} else { $email = ''; }
		if(isset($_GET['pid'])) { 	$pid = $_GET['pid']; 		} else { $pid = ''; }
		if(isset($_GET['addrs'])) { $addrs = $_GET['addrs'];		} else { $addrs = ''; }
		if(isset($_GET['cntry'])) { $cntry = $_GET['cntry'];		} else { $cntry = ''; }
		if(isset($_GET['ccode'])) { $ccode = $_GET['ccode'];		} else { $ccode = ''; }
		if(isset($_GET['comms'])) { $comms = $_GET['comms'];		} else { $comms = ''; }
		if(isset($_GET['salt'])) {	$salt = $_GET['salt'];		} else { $salt = ''; }
		if(isset($_GET['sort'])) {	$sort = $_GET['sort'];		} else { $sort = 'OrderKey'; }	// If order is undefined, default to processed date
		if(isset($_GET['ord'])) {	$ord = $_GET['ord'];			} else { $ord = ''; }
		if(isset($_GET['view'])) {	$view = $_GET['view'];		} else { $view = 'all'; }			// If view is undefined, default to all
		if(isset($_GET['n'])) {		$num = $_GET['n'];			} else { $num = '10'; }				// Set default rows per page to 50
		if(isset($_GET['p'])) {		$page = $_GET['p'];			} else { $page = '1'; }				// If page is undefined, redirect to page 1

		// If strings are empty, assign default values
		$sort=='' ? 'OrderKey' : $sort;
		$view=='' ? 'all' : $view;
		$num=='' ? '10' : $num;
		$page=='' ? '1' : $page;

		// Sanitize and convert to integers
		$page = intval($page);
		$num = intval($num);

		// Construct database query
		$dbq = "SELECT
			orders.Salt AS OrderSalt,
			orders.Timestamp AS OrderDate,
			orders.FirstName AS OrderFirstName,
			orders.LastName AS OrderLastName,
			orders.Email AS OrderEmail,
			orders.Institution AS Institution,
			orders.Address AS Address,
			orders.City AS City,
			orders.State AS State,
			orders.PostalCode AS PostalCode,
			orders.Country AS Country,
			orders.Comments AS Comments,
			orders.Verified AS OrderVerified,
			orders.OrderID AS OrderKey,
			orders.ShippingEmail AS ShippingEmail,
			COUNT(orderlines.PlantID) AS PIDCount
		FROM orders_unique AS orders
		LEFT JOIN orders_lines AS orderlines ON
			orders.Salt = orderlines.Salt
		WHERE 1=1";

		$dbq_params = array();
		if($fname) {
			$dbq .= " AND orders.FirstName LIKE ?";
			$dbq_params[] = '%'.$fname.'%';
		}
		if($lname) {
			$dbq .= " AND orders.LastName LIKE ?";
			$dbq_params[] = '%'.$lname.'%';
		}
		if($email) {
			$dbq .= " AND orders.Email = ?";
			$dbq_params[] = $email;
		}
		if($pid) {
			$dbq .= " AND lines.PlantID = ?";
			$dbq_params[] = $pid;
		}
		if($addrs) {
			$dbq .= " AND (orders.Institution LIKE ? OR orders.Address LIKE ?)";
			$dbq_params[] = '%'.$addrs.'%';
			$dbq_params[] = '%'.$addrs.'%';
		}
		if($cntry) {
			$dbq .= " AND orders.Country = ?";
			$dbq_params[] = $cntry;
		}
		if($comms) {
			$dbq .= " AND orders.Comments = ?";
			$dbq_params[] = '%'.$comms.'%';
		}
		if($salt) {
			$dbq .= " AND orders.Salt = ?";
			$dbq_params[] = $salt;
		}
		if($view=='unprocessed') {
			$dbq .= " AND orderlines.ProcessDate IS NULL AND orders.Verified = 1";
		} elseif($view=='processed') {
			$dbq .= " AND orderlines.ProcessDate IS NOT NULL";
		}

		$dbq .= " GROUP BY orders.Salt";

		// Run Query
		$q1 = $db->prepare($dbq);
		$q1->execute($dbq_params);

		// Fetch keys
		while($r1 = $q1->fetch(PDO::FETCH_ASSOC)) {
			$os[] = $r1['OrderSalt'];
		}

		// Get pagination variables
		$rows = $q1->rowCount();
		$last = intval(ceil($rows/$num));
		if($page <= 1) {
			$page = 1;
		} elseif($page > $last) {
			$page = $last;
		}

		// Pass search parameters into array, to reduce confusion
		// Item ID   [0]    [1]   [2]    [3]    [4]    [5]     [6]     [7]     [8]   [9]     [10]    [11]    [12]   [13]
		$arr = array($last, $num, $page, $sort, $view, $fname, $lname, $email, $pid, $addrs, $cntry, $comms, $salt, $ord);

		// Pagination function
		function urlstr($arr) {
			return 'fname='.$arr[5].'&lname='.$arr[6].'&email='.$arr[7].'&pid='.$arr[8].'&addrs='.$arr[9].'&cntry='.$arr[10].'&n='.$arr[1].'&sort='.$arr[3].'&view='.$arr[4].'&salt='.$arr[12].'&ord='.$arr[13];
		}
		function generatepagelink($i, $arr) {
			if($i == $arr[2]) {
				$str = ' <a href="'.$_SERVER['PHP_SELF'].'?'.urlstr($arr).'&p='.$i.'" title="Page '.$i.'" class="current">'.$i.'</a> ';
			} else {
				$str = ' <a href="'.$_SERVER['PHP_SELF'].'?'.urlstr($arr).'&p='.$i.'#rows" title="Page '.$i.'">'.$i.'</a> ';
			}
			return $str;
		}
		function paginate($arr) {
			// If there is more than one page
			if($arr[0] > 1) {
				$prev = $arr[2] - 1;
				$next = $arr[2] + 1;
				$nav = '<nav class="page-nav">';

				// If not on first page
				if($arr[2] !== 1) {
					$nav .= ' <a href="'.$_SERVER['PHP_SELF'].'?'.urlstr($arr).'&p=1" title="&laquo; First Page" class="arrow">&lt;&lt;</a> ';
					$nav .= ' <a href="'.$_SERVER['PHP_SELF'].'?'.urlstr($arr).'&p='.$prev.'" title="‹ Previous Page" class="arrow">&lt;</a> ';
				}

				// In between
				if($arr[0]<10) {
					// If there are less than 10 pages, list all pages
					for($i=1;$i<=$arr[0];$i++) {
						$nav .= generatepagelink($i, $arr);
					}
				} else {
					if($arr[2]<=5) {
						for($i=1;$i<=9;$i++) {
							$nav .= generatepagelink($i, $arr);
						}
						$nav .= ' <span>[...]</span> ';
					} elseif($arr[2]>5 && $arr[2]<=$arr[0]-5) {
						$nav .= " <span>[...]</span>";
						for($i=$arr[2]-4;$i<=$arr[2]+4; $i++) {
							$nav .= generatepagelink($i, $arr);
						}
						$nav .= ' <span>[...]</span> ';
					} else {
						$nav .= ' <span>[...]</span> ';
						for($i=$arr[0]-8;$i<=$arr[0];$i++) {
							$nav .= generatepagelink($i, $arr);
						}
					}
				}

				// If not on last page
				if($arr[2] !== $arr[0]) {
					$nav .= ' <a href="'.$_SERVER['PHP_SELF'].'?'.urlstr($arr).'&p='.$next.'" title="Next Page ›" class="arrow">&gt;</a>';
					$nav .= ' <a href="'.$_SERVER['PHP_SELF'].'?'.urlstr($arr).'&p='.$arr[0].'" title="Last Page &raquo;" class="arrow">&gt;&gt;</a>';
				}

				$nav .= '</nav>';
				return $nav;
			}
		}

		// Coerce sort
		$sorts = array('ProcessDate','FirstName','LastName','OrderKey','PlantID','SeedQuantity','Timestamp');
		if(!in_array($sort, $sorts)) {
			$sort = 'OrderKey';
		}

		// Define ordering - if undefined or is not the expected values, fall back to default
		if(empty($ord) || ($ord != 'asc' && $ord != 'desc')) {
			if($sort == 'ProcessDate' || $sort == 'OrderKey') {
				$ord = 'desc';
			} else {
				$ord = 'asc';
			}
		}

		// Perform actual query
		$data = $db->prepare($dbq." ORDER BY OrderVerified DESC, $sort $ord LIMIT ".($page-1)*$num.", ".$num);
		$data->execute($dbq_params);

		// Change page header ?>
		<nav>
			<ul id="tab-nav">
				<li><a href="orders.php?view=all"<?php echo ($view=='all' ? ' class="current ord-all"' : ''); ?>><h2>Orders Overview</h2>View all orders for LORE1 plant lines on our records.<span></span></a></li>
				<li><a href="orders.php?view=unprocessed"<?php echo ($view=='unprocessed' ? ' class="current ord-unprocessed"' : ''); ?>><h2>Unprocessed Orders</h2>You currently have <?php echo $ordercount; ?> <?php echo pl($ordercount, "order", "orders"); ?> waiting to be processed.<span></span></a></li>
				<li><a href="orders.php?view=processed"<?php echo ($view=='processed' ? ' class="current ord-processed"' : ''); ?>><h2>Processed Orders</h2>View all orders that have been previously processed.<span></span></a></li>
			</ul>
		</nav>
		<?php if($view == 'all' && !$salt) {
		?>
		<p>You can currently viewing <strong>all orders</strong> on our records. You can refine your view using the options below.</p>
		<?php } elseif($view == 'processed') { ?>
		<p>You can currently viewing <strong>all processed orders</strong> on our records. You can refine your view using the options below.</p>
		<?php } elseif($salt) { ?>
		<p>You are currently viewing the order with the identifier of <strong><?php echo $salt; ?></strong>. Now displaying <?php echo $page.' of '.$last; ?> with <?php echo $num; ?> rows per page.</p>
		<?php } else { ?>
		<p>There are currently <strong><?php echo $ordercount; ?> unprocessed <?php echo pl($ordercount, "order", "orders"); ?></strong> with a total of <?php echo pl($rows, $rows." line", $rows." lines"); ?>. Now displaying <?php echo $page.' of '.$last; ?> with <?php echo $num; ?> rows per page.</p>
		<?php } 
			if(isset($_SESSION['ord_error'])) {
		?>
		<div class="warning user-message">
			<p><?php echo $_SESSION['ord_error']; ?></p>
		</div>
		<?php unset($_SESSION['ord_error']); } ?>
		<div class="toggle hide-first">
			<h3><a href="#" title="Search &amp; View Options">Search &amp; View Options</a></h3>
			<form action="orders.php" method="GET">
				<fieldset>
					<legend><span class="pictogram icon-search"></span>Search</legend>
					<label class="col-one" for="order-key">Order Key <a data-modal="search-help" title="What is the order key?" data-modal-content="The order key is a unique 32-character hexadecimal code generated randomly when a customer places an order. The key is encoded in the URL from the verification email that you (the administrator) has received in the form of &lt;code&gt;http://<?php echo $_SERVER['HTTP_HOST']; ?>/admin/orders.php/?salt={32-character-key}&lt;/code&gt;">?</a></label>
					<input class="col-two" name="salt" id="order-key" type="text" placeholder="Enter 32 digit order key" value="<?php echo $salt; ?>" />

					<div class="separator"><span>or</span></div>

					<div class="way-wrap">
						<div class="two-way">
							<label class="col-one" for="order-fname">First Name</label>
							<input class="col-two" name="fname" id="order-fname" type="text" placeholder="Recepient First Name" value="<?php echo $fname; ?>" />
						</div>
						<div class="two-way">
							<label class="col-one" for="order-lname">Last Name</label>
							<input class="col-two" name="lname" id="order-lname" type="text" placeholder="Recepient Last Name" value="<?php echo $lname; ?>"/>
						</div>
					</div>
					<div class="way-wrap">
						<div class="two-way">
							<label class="col-one" for="order-email">Email</label>
							<input class="col-two" name="email" id="order-email" type="text" placeholder="Recepient Email" value="<?php echo $email; ?>"/>
						</div>
						<div class="two-way">
							<label class="col-one" for="order-pid">PlantID</label>
							<input class="col-two" name="pid" id="order-pid" type="text" placeholder="Ordered Plant ID" value="<?php echo $pid; ?>"/>
						</div>
					</div>
					<div class="way-wrap">
						<div class="two-way">
							<label class="col-one" for="order-address">Address</label>
							<input class="col-two" name="addrs" id="order-address" type="text" placeholder="Recepient Address" value="<?php echo $addrs; ?>"/>
						</div>
						<div class="two-way">
							<label class="col-one" for="order-country">Country</label>
							<select class="col-two" name="cntry" id="order-country">
								<option value="">Select a country</option>
							<?php
								$countries = CountriesArray::get('name', 'alpha3');
								foreach($countries as $name=>$alpha3) {
									echo '<option value="'.$alpha3.'">'.$name.'</option>';
								}
							?>
							</select>
						</div>
					</div>
				</fieldset>

				<fieldset>
					<legend><span class="pictogram icon-eye"></span>View Options</legend>
					<div class="way-wrap">
						<div class="two-way">
							<label class="col-one" for="order-num"># of Rows <a data-modal="search-help" title="What is number of rows?" data-modal-content="It displays the number of rows of orders placed on LORE1 lines on the page. It defaults to &lt;strong&gt;25&lt;/strong&gt;.">?</a></label>
							<select name="n" id="order-num" class="col-two">
								<option value="10">10</option>
								<option value="20" selected="selected">20</option>
								<option value="50">50</option>
							</select>
						</div>
						<div class="two-way">
							<label class="col-one" for="order-view">View</label>
							<select name="view" id="order-view" class="col-two">
								<option value="all"<?php echo ($view=='all' ? ' selected="selected"' : ''); ?>>All</option>
								<option value="unprocessed"<?php echo ($view=='unprocessed' ? ' selected="selected"' : ''); ?>>Unprocessed</option>
								<option value="processed"<?php echo ($view=='processed' ? ' selected="selected"' : ''); ?>>Processed</option>
							</select>
						</div>
					</div>

					<div class="way-wrap">
						<div class="two-way">
							<label class="col-one" for="order-sort">Order By <a data-modal="search-help" title="How do I order by results?" data-modal-content="There are several ways that you can organize orders. They are ordered by &lt;strong&gt;ProcessedDate&lt;/strong&gt; by default.">?</a></label>
							<select name="sort" id="order-sort" class="col-two">
								<option value="OrderKey" selected>Order Key</option>
								<option value="ProcessDate">Processed Date</option>
								<option value="FirstName">First Name</option>
								<option value="LastName">Last Name</option>
								<option value="PlantID">Plant ID</option>
								<option value="SeedQuantity"># Seeds Sent</option>
								<option value="Timestamp">Order Date</option>
							</select>
						</div>
						<div class="two-way">
							<label class="col-one" for="order-ord">Order Type</label>
							<select name="ord" id="order-ord" class="col-two">
								<option value="asc"<?php if($ord=='asc') echo ' selected'; ?>>Ascending</option>
								<option value="desc"<?php if($ord=='desc') echo ' selected'; ?>>Descending</option>
							</select>
						</div>
					</div>
				</fieldset>

				<input type="submit" value="Update Order Page" />
				<input type="reset" value="Reset Form" />
				<input type="hidden" value="<?php echo $user['Salt'];?>" id="admin-id" />
			</form>
		</div>
		<form id="order-rows" action="orders-exec.php" method="post">
		<div class="toggle">
			<h3><a href="#" class="open" title="Bulk Action">Bulk Action</a></h3>
			<div>
				<label for="item-scope" class="col-one">Item Scope</label>
				<select name="itemscope" id="item-scope" class="col-two">
					<option value="all">All orders</option>
					<option value="some">Selected orders only</option>
				</select>

				<label class="col-one">Download Actions</label>
				<div class="col-two">
					<button type="button" class="download-action" data-download-action="1">Postage Information<span> (<span class="count">0</span>)</span></button>
					<button type="button" class="download-action" data-download-action="2">Seedbag Labels<span> (<span class="count">0</span>)</span></button>
					<button type="button" class="download-action" data-download-action="3">All Columns<span> (<span class="count">0</span>)</span></button>

					<input type="hidden" name="actiontype" id="action-type" value="" />
					<input type="hidden" name="orderview" id="order-view" value="<?php echo $view; ?>" />
					<input type="hidden" name="CSRF_token" value="<?php echo CSRF_TOKEN; ?>" />
					<input type="hidden" name="origin" id="origin" value="<?php echo $origin = urlencode($_SERVER["REQUEST_URI"]); ?>" />
				</div>

				<?php
					if(!empty($user['Privileges']) && !empty($user['Privileges']['DeleteFile']) && $user['Privileges']['DeleteFile'] == 1) {
				?>
				<label class="col-one">Admin Actions</label>
				<div class="col-two">
					<button type="button" class="admin-action" data-admin-action="4">Manually Verify<span> (<span class="count">0</span>)</span></button>
					<button type="button" class="admin-action" data-admin-action="5">Delete<span> (<span class="count">0</span>)</span></button>
				</div>
				<?php } ?>
			</div>

		</div>
		<p>Now displaying <?php echo $page; ?> of <?php echo $last; ?> with <?php echo $num; ?> rows per page, with a total number of <?php echo $rows." ".pl($rows, "row", "rows"); ?>.</p>
		<?php
			if($rows > 0) {
				echo paginate($arr);
		?>
			<div id="order-table" class="table">
				<div class="table-heading" id="default-heading">
					<div class="item-pid">Plant ID</div>
					<div class="item-seeds">Seed Qty</div>
					<div class="item-com">Comments to Recepient</div>
					<div class="item-int">Internal Note</div>
					<div class="item-date">Process Date</div>
					<div class="item-admin">Administrator</div>
				</div>
				<?php while($results = $data->fetch(PDO::FETCH_ASSOC)) {
					// Filter results by escaping HTML tags
					$results = array_map('escapeHTML', $results);
				?>
				<div id="order-<?php echo $results['OrderSalt']; ?>" class="order-row">
					<div class="order-card order-<?php echo ($results['OrderVerified'] ? 'verified' : 'unverified'); ?>">
						<span class="card-name">
							<input type="checkbox" name="selectedsalts[]" id="selected-<?php echo $results['OrderSalt']; ?>" value="<?php echo $results['OrderSalt']; ?>" /><?php echo $results['OrderFirstName'].' '.$results['OrderLastName']; ?><span class="pictogram verification-status<?php echo ($results['OrderVerified'] ? ' icon-ok' : ' icon-cancel'); ?>" title="This order has <?php echo ($results['OrderVerified'] ? '' : 'NOT '); ?>been verified"></span>
							<br />
							<?php
								echo '<a class="card-email">'.$results['OrderEmail'].'</a>';
							?>
						</span>
						<span class="card-address"><?php echo $results['Institution'].'<br />'.$results['Address'].'<br />'.$results['City'].', '.($results['State'] ? $results['State'].', ' : '').$results['PostalCode'].' '.$results['Country']; ?></span>
						<span class="card-meta">
							<span class="card-pidcount"><strong>Lines Ordered: <?php echo $results['PIDCount']; ?></strong></span>
							<span class="card-date">Timestamp: <?php echo date("F j, Y g:ia", strtotime($results['OrderDate'])); ?></span>
							<span class="card-id">ID: <?php echo $results['OrderSalt']; ?></span>
							<?php echo !empty($results['Comments']) ? '<blockquote class="card-comments">'.$results['Comments'].'</blockquote>' : ''; ?>
						</span>
					</div>

					<div class="order-details">
						<table>
							<colgroup></colgroup>
							<colgroup></colgroup>
							<colgroup></colgroup>
							<colgroup></colgroup>
							<colgroup></colgroup>
							<colgroup></colgroup>
						<?php
							$ods = $results['OrderSalt'];
							$odq = $db->prepare("SELECT
								t1.PlantID AS PID,
								t1.SeedQuantity AS SQ,
								t1.AdminComments AS AC,
								t1.InternalComments AS IC,
								t1.ProcessDate AS PD,
								t1.OrderLineID AS ODK,
								t2.FirstName AS AN
								FROM orders_lines AS t1
								LEFT JOIN auth AS t2
								ON t1.AdminSalt = t2.Salt
								WHERE t1.Salt = :ordersalt
								");
							$odq->bindParam(':ordersalt', $ods);
							$odq->execute();

							while($od = $odq->fetch(PDO::FETCH_ASSOC)) {
								$odk = $od['ODK'];
								echo '<tr class="'.($od['PID'] == $pid ? 'highlighted' : '').'" id="order-'.$odk.'" data-order-salt="'.$results['OrderSalt'].'"><td class="item-pid">'.$od['PID'].(empty($od['PID']) ? ' ' : '').'</td>';
								echo '<td class="item-seeds allow-edit"><span id="seeds-'.$odk.'">'.$od['SQ'].(empty($od['SQ']) ? ' ' : '').'</span><input type="text" class="db-edit" id="input-seeds-'.$odk.'" value="'.$od['SQ'].'" placeholder="Seed Qty"/></td>';
								echo '<td class="item-com allow-edit"><span id="com-'.$odk.'">'.$od['AC'].(empty($od['AC']) ? ' ' : '').'</span><textarea class="db-edit" id="input-com-'.$odk.'" rows="5" placeholder="Comments to Recepient">'.$od['AC'].'</textarea><input type="button" class="db-edit com-ins" value="Bad Quality" data-text-insert="Low quality seeds." /><input type="button" class="db-edit com-ins" value="Few seeds + Return" data-text-insert="Few seeds remain in stock, please return seeds to our mailing address after propagation." /><input type="button" class="db-edit com-ins" value="No more seeds + Return" data-text-insert="No more seeds remain in stock, please return seeds to our mailing address after propagation." /><input type="button" class="db-edit com-ins" value="No seeds" data-text-insert="No more seeds remain in stock." /></td>';
								echo '<td class="item-int allow-edit"><span id="int-'.$odk.'">'.$od['IC'].(empty($od['IC']) ? ' ' : '').'</span><textarea class="db-edit" id="input-int-'.$odk.'" rows="5" placeholder="Internal Comments">'.$od['IC'].'</textarea></td>';
								echo '<td class="item-date allow-edit"><span id="date-'.$odk.'">'.$od['PD'].(empty($od['PD']) ? ' ' : '').'</span><input type="text" class="db-edit" id="input-date-'.$odk.'" value="'.$od['PD'].'" placeholder="YYYY-MM-DD" /><input type="button" class="db-edit today-date" value="Today" /></td>';
								echo '<td class="item-admin" id="admin-'.$odk.'">'.$od['AN'].(empty($od['AN']) ? ' ' : '').'</td></tr>';
							}
						?>
						</table>
					</div>
				</div>
				<?php } ?>
			</div>

			<input type="hidden" name="ordersalts" id="order-salts" value='<?php echo json_encode($os); ?>' />
		</form>
		<?php echo paginate($arr);
			} else {
			echo "<p>No entries found. Please try again.</p>";
			} ?>
	</section>

	<?php include_once('footer.php'); ?>
</body>
</html>