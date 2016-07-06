<?php
	// Load site config
	require_once('../config.php');
	
	// Require authorization
	require_once('auth.php');
?>
<!doctype html>
<html>
<head>
	<title>Users &mdash; LORE1 Resource Site</title>
	<?php include_once('head.php'); ?>
</head>
<body class="admin users">
	<?php include_once('header.php'); ?>

	<section class="wrapper">
		<h2><span class="pictogram icon-users"></span> Site Admins
		<?php 
			if($user_rights['AddUser']) {
				echo '<a href="register.php"><span class="pictogram icon-user-add"></span> Add New Admin</a>';
			}
		?>
		</h2>
		<table id="rows">
			<colgroup></colgroup>
			<colgroup></colgroup>
			<colgroup></colgroup>
			<colgroup></colgroup>
			<colgroup></colgroup>
			<colgroup></colgroup>
			<colgroup></colgroup>
			<colgroup></colgroup>
			<colgroup></colgroup>
			<colgroup></colgroup>
			<colgroup></colgroup>
			<thead>
				<tr>
					<td scope="col" class="user-chk"><input type="checkbox" class="ca" /></td>
					<td scope="col">First Name</td>
					<td scope="col">Last Name</td>
					<td scope="col">Email</td>
					<td scope="col">Username</td>
					<td scope="col">Notifications</td>
					<td scope="col">File upload</td>
					<td scope="col">File deletion</td>
					<td scope="col">Adding new user</td>					
					<td scope="col">Verified?</td>
					<td scope="col">Activated?</td>
				</tr>
			</thead>
			<tbody>
		<?php 
			$q = $db->prepare("SELECT * FROM auth LEFT JOIN adminprivileges ON auth.Authority = adminprivileges.Authority");
			$q->execute();

			while($d = $q->fetch(PDO::FETCH_ASSOC)) {
				$user = $d['Username'];
		?>
			<tr>
				<td class="user-chk">
					<span><input type="checkbox" name="keys[]" value="<?php echo $d['UserID']; ?>"></span>
				</td>
				<td>
					<span><?php echo $d['FirstName']; ?></span>
				</td>
				<td>
					<span><?php echo $d['LastName']; ?></span>
				</td>
				<td>
					<span><?php echo $d['Email']; ?></span>
				</td>
				<td>
					<span><?php echo $user; ?></span>
				</td>
				<td>
					<span class="pictogram<?php echo ($d['Notify']==1 ? ' yes icon-check' : ' no icon-cancel'); ?>" title="This user (<?php echo $user; ?>) chooses <?php echo ($d['Notify']==1 ? '' : 'NOT '); ?>to be notified by email when a new order is received."></span>
				</td>
				<td>
					<span class="pictogram<?php echo ($d['Upload']==1 ? ' yes icon-check' : ' no icon-cancel'); ?>" title="This user (<?php echo $user; ?>) is <?php echo ($d['Upload']==1 ? '' : 'NOT '); ?>able to upload new files to the server for public download."></span>
				</td>
				<td>
					<span class="pictogram<?php echo ($d['DeleteFile']==1 ? ' yes icon-check' : ' no icon-cancel'); ?>" title="This user (<?php echo $user; ?>) is <?php echo ($d['DeleteFile']==1 ? '' : 'NOT '); ?>able to delete files available for public download."></span>
				</td>
				<td>
					<span class="pictogram<?php echo ($d['AddUser']==1 ? ' yes icon-check' : ' no icon-cancel'); ?>" title="This user (<?php echo $user; ?>) is <?php echo ($d['AddUser']==1 ? '' : 'NOT '); ?>able to add new users to this system."></span>
				</td>
				<td>
					<span class="pictogram<?php echo ($d['Verified']==1 ? ' yes icon-check' : ' no icon-cancel'); ?>" title="This user (<?php echo $user; ?>) has <?php echo ($d['Verified']==1 ? '' : 'NOT '); ?>been verified."></span>
				</td>
				<td>
					<span class="pictogram<?php echo ($d['Activated']==1 ? ' yes icon-check' : ' no icon-cancel'); ?>" title="This user (<?php echo $user; ?>) has <?php echo ($d['Activated']==1 ? '' : 'NOT '); ?>been activated by a system administrator."></span>
				</td>
			</tr>
				<?php } ?>
			</tbody>
		</table>
		<table id="sticky"></table>
	</section>

	<?php include_once('footer.php'); ?>
</body>
</html>
