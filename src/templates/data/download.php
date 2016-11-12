<?php
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>Downloads &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
</head>
<body class="data downloads">

	<div id="wrap">
	<?php
		$header = new \LotusBase\Components\PageHeader();
		$header->set_header_content('<h1>Downloadable Resources</h1>
		<p>You will find a list of downloadable resources we have made available to the public. Click on the file names to initiate download.</p>');
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title('Downloads');
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<?php
			if(isset($_SESSION['download_error'])) {
				echo '<p class="user-message warning">'.$_SESSION['download_error'].'</p>';
				unset($_SESSION['download_error']);
			}
		?>
		<?php

			// Database connection
			try {
				$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);		

				// Prepare and execute statement
				$q = $db->prepare("SELECT * FROM download ORDER BY FileName");
				$q->execute();

				// Retrieve results
				if($q->rowCount() > 0) {
					echo '<ul class="list--big" id="downloads__file-list">';
					while($row = $q->fetch(PDO::FETCH_ASSOC)) {
						$count = $row['Count'];
						echo '<li>
						<a href="'.WEB_ROOT.'/'.$row['FilePath'].$row['FileName'].'" title="'.$row['FileDesc'].'">
							<div class="file-meta__desc ext-'.str_replace('.', '', $row['FileExt']).'">
								<span class="file-meta__file-name">'.$row['FileName'].'</span>
								<span class="file-meta__file-desc">'.$row['FileDesc'].'</span>
							</div>
							<div class="file-meta__downloads">
								<span class="file-meta__download-count" data-count="'.$row['Count'].'">'.nf($count).'</span>
								<span class="file-meta__download-desc">'.pl($count, 'download', 'downloads').'</span>
							</div>
						</a></li>';
					}
					echo '</ul>';
				} else {
					echo '<p class="user-message warning">No downloadable resources are currently available.</p>';
				}

			} catch(PDOException $e) {
				echo '<p class="user-message warning">Unable to esablish database connection. Should this problem persist, please contact the site administrator.</p>';
			}

		?>
	</section>
	</div>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>