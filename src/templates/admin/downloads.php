<?php
	// Load site config
	require_once('../config.php');
	
	// Require authorization
	require_once('auth.php');
?>
<!doctype html>
<html>
<head>
	<title>Downloads &mdash; LORE1 Resource Site</title>
	<?php include_once('head.php'); ?>
</head>
<body class="admin downloads">
	<?php include_once('header.php'); ?>

	<section class="wrapper" id="orders-section">
		<h2><span class="pictogram icon-download"></span> Downloadable Resources</h2>
		<?php
			$del = $user_rights['DeleteFile'];

			if(isset($_SESSION['delete_success'])) {
				echo '<p class="user-message note">The file <code></code> has been successfully deleted from the file directory and removed from the database.</p>';
				unset($_SESSION['delete_success']);
			}

			try {
				$q = $db->prepare("SELECT * FROM download ORDER BY FileKey");
				$q->execute();
				if($q->rowCount() > 0) {
		?>
		<form id="download-rows">
			<table id="rows">
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<?php echo $del ? '<colgroup></colgroup>' : '' ?>
				<thead>
					<tr>
						<td scope="col">File Name</td>
						<td scope="col">File Location</td>
						<td scope="col">Description</td>
						<td scope="col">Download Count</td>
						<?php echo $del ? '<td scope="col"><span class="pictogram icon-trash" title="Delete"></span></td>' : '' ?>
					</tr>
				</thead>
				<tbody>
					<?php while($results = $q->fetch(PDO::FETCH_ASSOC)) { $results = array_map('escapeHTML', $results); ?>
					<tr class="edit" id="row-<?php echo $results['FileKey']; ?>">
						<td>
							<span id="name-<?php echo $results['FileKey']; ?>"><?php echo $results['FileName']; ?></span>
						</td>
						<td>
							<span id="loc-<?php echo $results['FileKey']; ?>"><?php echo $results['FilePath']; ?></span>
						</td>
						<td class="allow-edit">
							<span id="desc-<?php echo $results['FileKey']; ?>"><?php echo $results['FileDesc']; ?></span>
							<input type="text" class="db-edit" id="input-desc-<?php echo $results['FileKey']; ?>" value="<?php echo $results['FileDesc']; ?>" />
						</td>
						<td class="allow-edit">
							<span id="count-<?php echo $results['FileKey']; ?>"><?php echo $results['Count']; ?></span>
							<input type="text" class="db-edit" id="input-count-<?php echo $results['FileKey']; ?>" value="<?php echo $results['Count']; ?>" />
							<input type="button" class="db-edit reset-count" value="Reset Count" />
						</td>
						<?php echo $del ? '<td><a class="file-delete" id="delete-'.$results['FileKey'].'"><span class="pictogram icon-trash">Delete</span></a></td>' : '' ?>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<table id="sticky"></table>
		</form>
		<?php
				} else {
					echo "There are no entries found in the <code>download</code> table.";
				}
			} catch(PDOException $e) {
				echo '<p class="user-message">Database query has failed.</p>';
			}
		?>
	</section>

	<?php if($user_rights['Upload']) { ?>
	<section class="wrapper">
		<h2><span class="pictogram icon-upload"></span> Upload New File</h2>
		<form id="upload" action="upload" method="post" enctype="multipart/form-data">
			<?php 
			if(isset($_SESSION['upload_error'])) {
				echo '<p class="user-message warning">'.$_SESSION['upload_error'].'</p>';
				unset($_SESSION['upload_error']);
			}
			if(isset($_SESSION['upload_success'])) {
				echo '<p class="user-message approved">'.$_SESSION['upload_success'].'</p>';
				unset($_SESSION['upload_success']);
			}
			?>

			<label class="col-one" for="upload-filedesc">File description <a data-modal title="Enter a file description" data-modal-content="You should enter a file description when you upload a new file. The description should not exceed 255 characters.">?</a></label>
			<input class="col-two" type="text" name="filedesc" id="upload-filedesc" placeholder="Short file description (max. 255 characters)" maxlength="255" />

			<label class="col-one" for="upload-filename">Select file (max 50mb)</label>
			<input type="hidden" name="MAX_FILE_SIZE" value="52428800" />
			<input class="col-two" type="file" name="file" id="upload-filename" />

			<input type="submit" name="submit" value="Upload File" />
		</form>
	</section>
	<?php } ?>

	<?php include_once('footer.php'); ?>
</body>
</html>