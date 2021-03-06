<?php
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>Downloads &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Downloadable resources from Lotus Base.'
			));
		echo $document_header->get_document_header();
	?>
</head>
<body class="data downloads">

	<div id="wrap">
	<?php
		$hasSearchTerm = isset($_GET['search']) && strlen($_GET['search']) > 2;
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<h1>Data download</h1>
		<p>
			You will find a list of downloadable resources we have made available to the public.
			The integrity of downloaded files can be checked with the published md5 checksum.
			If any of the downloaded files have been used in your published manuscript and data,
			we kindly ask you to refer to the <a href="/meta/citation">citation guide</a>.
		</p>
		<p>
			You can also use the search field below to filter the list of downloadable files:
		</p>
		<form id="downloads-filter" class="search-form" action="#" method="get">
			<input type="search" id="filter" name="q" value="'.($hasSearchTerm ? escapeHTML($_GET['search']) : '').'" placeholder="Filter downloads using keywords" autocomplete="off">
			<button type="submit"><span class="pictogram icon-search">Filter</span></button>
		</form>
		');
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

			echo '<p id="download__user-message" class="user-message warning hidden"></p>';

			// Database connection
			try {
				$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);		

				// Prepare and execute statement
				$q = $db->prepare("SELECT
						t1.FileName AS FileName,
						t1.FilePath AS FilePath,
						t1.FileExtension AS FileExtension,
						t1.Title AS Title,
						t1.Description AS Description,
						t1.Tags AS Tags,
						t1.Count AS Count,
						t1.PMID AS PMID,
						t1.FileKey AS FileKey,
						t1.MD5 AS MD5Hash,
						GROUP_CONCAT(t2.AuthGroup) AS AuthGroups
					FROM download AS t1
					LEFT JOIN download_auth AS t2 ON
						t1.FileKey = t2.FileKey
					GROUP BY t1.FileKey
					ORDER BY t1.SortOrder, t1.Category, t1.FileName");
				$q->execute();

				// Retrieve results
				if($q->rowCount() > 0) {
					echo '<ul class="list--big" id="downloads__file-list">';
					while($row = $q->fetch(PDO::FETCH_ASSOC)) {
						$count = $row['Count'];
						$tags = explode(',', $row['Tags']);
						$fileMeta = array(
							'MD5 hash' => $row['MD5Hash'],
							'Filename' => $row['FileName']
						);
						$filePath = DOC_ROOT.'/'.$row['FilePath'].$row['FileName'];
						if(file_exists($filePath)) {
							$fileMeta['Size'] = human_filesize(filesize($filePath));
						}

						// Show file when AuthGroup is null, or when is not null, is found in the user
						$user_auth = is_logged_in();
						$user_groups = explode(',', $row['AuthGroups']);
						if ($row['AuthGroups'] !== null && !in_array($user_auth['UserGroup'], $user_groups)) {
							continue;
						}

						// Get citation metadata if exists
						$ref = false;
						if($row['PMID']) {
							try {
								// Make GET request
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_URL, 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&retmode=json&id='.$row['PMID']);
								curl_setopt($ch, CURLOPT_HEADER, 0);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

								$resp = json_decode(curl_exec($ch), true);
								
								// Parse response and construct reference
								$pub = $resp['result'][$row['PMID']];

								$_ref = array(
									explode(' ', $pub['authors'][0]['name'])[0].' et al.',
									'('.date('Y', strtotime($pub['sortpubdate'])).')',
									$pub['source'].', '.$pub['volume'].(!empty($pub['issue']) ? '('.$pub['issue'].')' : '').'.',
									$pub['elocationid']
									);
								$ref = implode(' ', $_ref);

							} catch(Exception $e) {

							}
						}

						echo '<li id="file-'.$row['FileKey'].'" '.($hasSearchTerm ? 'style="display: none;"' : '').'>
						<div title="'.$row['Description'].'" class="downloads__file-list-item" data-file-path="'.WEB_ROOT.'/'.$row['FilePath'].$row['FileName'].'">
							<div class="file-meta__desc ext-'.str_replace('.', '', $row['FileExtension']).'">
								<h3 class="file-meta__file-title">'.$row['Title'].'</h3>
								'.(!empty($row['Description']) ? '<p class="file-meta__file-desc">'.$row['Description'].'</p>' : '').'
								'.(!empty($ref) ? '<p class="file-meta__ref">'.$ref.'</p>' : '').'
								'.($row['AuthGroups'] !== null ? '<p class="user-message info"><span class="icon-lock">This file is available to internal users only. Please do not redistribute this file.</span></p>' : '');
								echo '<ul class="list--reset file-meta__file-data">';
									foreach ($fileMeta as $key => $value) {
										echo '<li><strong>'.$key.'</strong>: <code>'.$value.'</code></li>';
									}
								echo '</ul>';
								echo '<ul class="list--floated file-meta__tags">';
						foreach($tags as $tag) {
							echo '<li>'.$tag.'</li>';
						}
						echo'</ul>
							</div>
							<div class="file-meta__downloads">
								<span class="file-meta__download-count" data-count="'.$row['Count'].'">'.nf($count).'</span>
								<span class="file-meta__download-desc">'.pl($count, 'download', 'downloads').'</span>
							</div>
						</div></li>';
					}
					echo '</ul>';
				} else {
					echo '<p class="user-message warning">No downloadable resources are currently available.</p>';
				}

			} catch(PDOException $e) {
				echo '<p class="user-message warning">We have encountered an error: '.$e->getMessage().'</p>';
			}

		?>
	</section>
	</div>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/lunr.js/0.6.0/lunr.min.js" integrity="sha384-uPz/M+hHXIBYS/cPEE4+ycdXOIpVuakCky8PLcjO1VTAn3RXaQAguOLfDZC3QQIX" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/download.min.js"></script>
</body>
</html>