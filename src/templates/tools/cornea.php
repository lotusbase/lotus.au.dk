<?php
	require_once('../config.php');

	// High level function to catch errors
	function cornea_error_catch($e, $w) {
		$_SESSION['cornea'] = array(
			'errors' => $e,
			'warnings' => $w
			);
		session_write_close();
		header('Location: ./cornea');
		exit();
	}

	// We use REQUEST here because:
	// 1. Job submission is done using POST (so that people can't submit GET requests all the time)
	// 2. Job status polling is done using GET
	if(isset($_REQUEST)) {

		if(!empty($_REQUEST['action'])) {

			// Define action
			$action = $_REQUEST['action'];

			if ($action === 'submit') {

				// Warnings and errors
				$warnings = array();
				$errors = array();

				if(!isset($_POST) || empty($_POST)) {
					$errors[] = 'Job submission request is improperly constructed.';
					cornea_error_catch($errors);
				}

				// Attempt to decode JWT. If user is not logged in, perform additional checks
				$user = is_logged_in();
				if(!$user) {
					// Check if Google's recaptcha is completed
					$recaptcha = new \ReCaptcha\ReCaptcha(GRECAPTCHA_API_KEY);
					if(!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
						$errors[] = 'You have not completed the captcha';
					} else {
						$resp = $recaptcha->verify($_POST['g-recaptcha-response'], get_ip());
						if(!$resp->isSuccess()) {
							$errors[] = 'You have provided an incorrect verification token.';
						}
					}
				}

				// Threshold
				if(isset($_POST['threshold']) && !empty($_POST['threshold'])) {
					if(floatval($_POST['threshold']) < 1.00 && floatval($_POST['threshold']) >= 0.8) {
						$threshold = floatval($_POST['threshold']);
					} else {
						$threshold = min(max(floatval($_POST['threshold']), 0.8), 0.999999999);
						$warnings[] = 'Threshold provided <code>'.floatval($_POST['threshold']).'</code> is beyond range (0.75&le;threshold&lt;1.00) and therefore set to: '.$threshold;
					}
				} else {
					$threshold = 0.9;
					$warnings[] = 'Threshold not set, defaulting to '.$threshold;
				}

				// Cluster size
				if(isset($_POST['clustersize']) && !empty($_POST['clustersize'])) {
					if(intval($_POST['clustersize']) >= 5) {
						$clustersize = intval($_POST['clustersize']);
					} else {
						$clustersize = 5;
						$warnings[] = 'Cluster size provided (<code>'.$_POST['clustersize'].'</code>) is too small (minimum '.$clustersize.') and therefore set to '.$clustersize;
					}
					
				} else {
					$clustersize = 15;
					$warnings[] = 'Cluster size not set, defaulting to '.$clustersize;
				}

				// Parse columns
				if(isset($_POST['dataset']) && !empty($_POST['dataset'])) {
					$dataset = $_POST['dataset'];
					if (isset($_POST['column'])) {
						foreach ($_POST['column'] as $key => $c) {
							$_POST['column'][$key] = 'Mean_'.$c;
						}
						$columns = implode(',', $_POST['column']);
					} else {
						$columns = NULL;
					}
				} else {
					$errors[] = 'No dataset was selected.';
				}

				// Prepare exec statement
				$exec_str = PYTHON_PATH.' '.DOC_ROOT.'/lib/corx/CorrelationNetworkClient.py submit ';
				$exec_args = escapeshellarg($dataset).' --threshold '.escapeshellarg($threshold).' --minimum-cluster-size '.escapeshellarg($clustersize).' --verbose 1';

				// Additional params
	//			if (isset($_POST['candidates']) && !empty($_POST['candidates'])) {
	//				$exec_args .= ' --candidates '.escapeshellarg(str_replace(',', ';', $_POST['candidates']));
	//			}
				if(isset($_POST['owner']) && !empty($_POST['owner'])) {
					$exec_args .= ' --owner '.escapeshellarg($_POST['owner']);
				} elseif(isset($user['Email']) && !empty($user['Email'])) {
					$exec_args .= ' --owner '.escapeshellarg($user['Email']);
				}

				if($user && isset($user['Salt']) && !empty($user['Salt'])) {
					$exec_args .= ' --owner-salt '.escapeshellarg($user['Salt']);
				}
				if($columns != NULL) {
					$exec_args .= ' --columns '.escapeshellarg($columns);
				}

				// Catch errors and warnings
				if(!empty($errors)) {
					cornea_error_catch($errors, $warnings);
				}

				// Execute and retrieve job details
				$job_data = json_decode(exec(escapeshellcmd($exec_str.$exec_args)), true);

				// Redirect
				if(!isset($job_data['job_hash_id']) || empty($job_data['job_hash_id'])) {
					header('Location: ./cornea/');
				} else {
					header('Location: ./cornea/job/'.$job_data['job_hash_id']);
				}
				exit();

			}
		}
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>CORNEA &mdash; Tools &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="/dist/css/tools.min.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="/dist/css/expat.min.css" type="text/css" media="screen" />
</head>
<body class="tools expat cornea">
	<?php
		$header = new \LotusBase\PageHeader();
		$header->set_header_content('<div class="align-center">
			<h1>CORNEA</h1>
			<span class="byline"><strong>Coexpression Network Analysis</strong><br />for <em>L.</em> japonicus reference genome <strong>v3</strong></span>
			<p>The <strong>Coexpression Network Analysis</strong> tool allows you to visualize gene co-expression networks.</p>
		</div>');
		echo $header->get_header();
	?>

	<?php echo get_breadcrumbs(array('custom_breadcrumb' => array(
		'Tools' => 'tools',
		'CORNEA' => 'cornea'
	))); ?>

	<section class="wrapper">
		<?php
			if(isset($_SESSION['cornea']) && isset($_SESSION['cornea']['errors']) && isset($_SESSION['cornea']['warnings'])) {
				if(!empty($_SESSION['cornea']['errors'])) {
					$errors = $_SESSION['cornea']['errors'];
					echo '<div class="user-message warning"><p><span class="icon-cancel-circled"></span> The following error'.(count($errors) > 1 ? 's have' : ' has').' to be fixed before proceeding:</p><ul><li>'.implode('</li><li>', $errors).'</li></ul></div>';
				}
				if(!empty($_SESSION['cornea']['warnings'])) {
					$warnings = $_SESSION['cornea']['warnings'];
					echo '<div class="user-message reminder"><p><span class="icon-attention"></span> The following setting'.(count($warnings) > 1 ? 's are' : ' is').' invalid and will be coerced to predefined ranges:</p><ul><li>'.implode('</li><li>', $warnings).'</li></ul></div>';
				}
				unset($_SESSION['cornea']);
			}
		?>

		<div id="cornea-tabs">
			<ul>
				<li><a href="#preset-job" title="Select a standard network"><span class="icon-ok">Pre-made networks</span></a></li>
				<li><a href="#submit-job" title="Create new coexpression network"><span class="icon-network icon--big">Create new network</span></a></li>
				<li><a href="#upload-job" title="Upload an old CORNEA job"><span class="icon-upload icon--big">Upload previous job</span></a></li>
				<li><a href="#check-job" title="Check your job status"><span class="icon-clock icon--big">Check job</span></a></li>
			</ul>

			<div id="preset-job">
				<p>Creating networks of genes whose expression meet a certain correlation threshold is a time-consuming process. Therefore, there is a provision for pre-made networks of most commonly-used parameters, so that you do not have to wait for your own job to be finished.</p>
				<p>Select from the dropdown list below of pre-made networks. All networks are made with all columns included: should you want to subset the data, you will have to create your own network.</p>
				<p class="user-message note">Do not be alarmed if a network generated with identical settings look different that our pre-made sets: network generation is a non-deterministic process. While the nodes and their relationships are identical, the way they are laid out differs from job to job.</p>
				<?php
				// Database connection
					try {
						$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
						$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);		

						// Prepare and execute statement
						$q = $db->prepare("SELECT * FROM correlationnetworkjob WHERE hash_id LIKE 'standard_%' ORDER BY id ASC");
						$q->execute();

						// Retrieve results
						if($q->rowCount() > 0) {
							echo '<ul class="list--big" id="cornea__premade-job-list">';
							while($row = $q->fetch(PDO::FETCH_ASSOC)) {
								echo '<li>
								<a href="'.WEB_ROOT.'/tools/cornea/job/'.$row['hash_id'].'">
									<div class="job-image"><div class="image" style="background-image: url(\''.WEB_ROOT.'/dist/images/cornea/'.$row['hash_id'].'.png\');"></div></div>
									<div class="job-settings">
										<h4 class="align-center">Settings</h4>
										<ul>
											<li class="job-settings__dataset">Dataset: '.$row['dataset'].'</li>
											<li class="job-settings__threshold">Threshold: '.$row['threshold'].'</li>
											<li class="job-settings__minimum-cluster-size">Min. cluster size: '.$row['minimum_cluster_size'].'</li>
										</ul>
									</div>
									<div class="job-meta">
										<h4 class="align-center">Metadata</h4>
										<ul>
											<li class="job-meta__node-count">&numero; of nodes: '.$row['node_count'].'</li>
											<li class="job-meta__edge-count">&numero; of edges: '.$row['edge_count'].'</li>
											<li class="job-meta__cluster-count">&numero; of '.pl($row['cluster_count'], 'cluster').': '.$row['cluster_count'].'</li>
											<li class="job-meta__time-taken">Time elapsed: '.friendly_duration(strtotime($row['end_time'])-strtotime($row['start_time'])).'</li>
										</ul>
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
			</div>

			<div id="submit-job">

				<form action="<?php echo WEB_ROOT; ?>/tools/cornea" method="post" id="cornea-form__submit" class="has-group">
					<input class="input-hidden" type="hidden" name="action" value="submit" readonly />

					<div class="cols has-legend" role="group">
						<p class="user-message full-width minimal legend">Query</p>

						<label for="cornea-threshold" class="col-one">Threshold</label>
						<input type="number" class="col-two" id="cornea-threshold" name="threshold" placeholder="Correlation threshold" value="<?php echo (isset($_GET['threshold']) && is_numeric($_GET['threshold'])) ? floatval($_GET['threshold']) : '0.9'; ?>" min="0.75" max="1.0" step="0.01" />

						<label for="cornea-min-cluster-size" class="col-one">Minimum cluster size</label>
						<input type="number" class="col-two" id="cornea-min-cluster-size" name="clustersize" placeholder="Minimum cluster size" value="<?php echo (isset($_GET['min_cluster_size']) && is_numeric($_GET['min_cluster_size'])) ? intval($_GET['min_cluster_size']) : '15'; ?>" min="5" step="1" />

						<!--<label for="cornea-candidates-multi" class="col-one">Candidate(s) (optional) <a data-modal class="info" title="What are candidate genes?" data-modal-content="Candidate genes can be seen as &quot;filters&quot; to your search results. If candidate genes are specified, we filter the returned gene list with your gene. If no candidate genes are specified, the entire gene list will be returned.">?</a></label>
						<div class="col-two">
							<div class="multiple-text-input input-mimic">
								<ul class="input-values">
									<?php
//										if(isset($_GET['candidates']) && !empty($_GET['candidates'])) {
//											$items_array = explode(",", $_GET['candidates']);
//											foreach($items_array as $item) {
//												echo '<li data-input-value="'.escapeHTML($item).'">'.escapeHTML($iitem).'<span class="icon-cancel" data-action="delete"></span></li>';
//											}
//										}
									?>
									<li class="input-wrapper"><input type="text" name="ids-input" id="cornea-candidates-multi" placeholder="Enter candidate gene(s) here (optional)" autocomplete="off" /></li>
								</ul>
								<input class="input-hidden" type="hidden" name="candidates" id="expat-row" value="<?php // echo (isset($_GET['candidates']) && !empty($_GET['candidates'])) ? $_GET['candidates'] : ''; ?>" readonly />
							</div>
							<small><strong>Separate each candidate with a comma, space or tab.</strong></small>
						</div>-->

						<div class="separator"><span>and</span></div>

						<label for="expat-dataset" class="col-one">Dataset <a href="<?php echo WEB_ROOT.'/lib/docs/expat-datasets'; ?>" data-modal="wide" class="info" title="What are the available datasets?">?</a></label>
						<div class="col-two">
							<?php
								$expat_dataset = new \Lotusbase\ExpAt\Dataset();
								$expat_dataset->set_blacklist(array('rnaseq-marcogiovanetti-2015-am'));
								if(!empty($_GET['dataset'])) {
									$expat_dataset->set_dataset($_GET['dataset']);
								}
								echo $expat_dataset->render();
							?>
						</div>

						<div id="expat-dataset-subset">
							<div class="cols">
								<label class="col-one">Data subset (optional)</label>
								<div class="col-two">
									<p>If no columns are selected by the user, all columns will be queried, returning a full dataset (default behavior).</p>
									<p>Enter a keyword to filter conditions. Each space-separated keyword is treated as an <code>AND</code>, i.e. the condition has to match all listed keywords:<input type="text" placeholder="Filter conditions by entering a keyword&hellip;" disabled /></p>
									<div class="table-overflow">
										<table class="table--x-dense">
											<thead></thead>
											<tbody></tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="cols has-legend" role="group">
						<p class="user-message full-width minimal legend">Notification</p>
						<?php if(!is_logged_in()) { ?>
							<p class="full-width">By providing us an email, you will be promptly notified when your job has been completed. This field is optional.</p>
							<label for="cornea-new-job-owner" class="col-one">Email</label>
							<input class="col-two" type="email" name="owner" id="cornea-new-job-owner" value="<?php echo (isset($_GET['owner']) && !empty($_GET['owner'])) ? $_GET['owner'] : ''; ?>" placeholder="Enter your email here" />
						<?php } else {
							$user = is_logged_in();
							?>
							<p class="full-width">Welcome back, <?php echo $user['FirstName']; ?>. You will be automatically notified when your job is completed, at <strong><?php echo $user['Email']; ?></strong>.</p>
						<?php } ?>
					</div>

					<?php if(!is_logged_in()) { ?>
					<div class="cols has-legend" role="group">
						<p class="user-message full-width minimal legend">Control</p>
						<p class="user-message full-width minimal">Generating co-expression network is a resource-intensive process, and therefore we require anonymous users to authenticate themselves as human before proceeding. To avoid the need to verify your identity, you can <a href="<?php echo WEB_ROOT.'/users/login'; ?>">log in with your account</a>, or <a href="<?php echo WEB_ROOT.'/users/register'; ?>">register for new one</a>.</p>
						<label>Human?</label>
						<div class="col-two" id="google-recaptcha"></div>
					</div>
					<?php } else { ?>
					<input type="hidden" name="user_auth_token" value="<?php echo $_COOKIE['auth_token']; ?>" />
					<?php } ?>

					<div class="cols justify-content__center">
						<button type="submit" role="primary"><span class="pictogram icon-search">Submit</span></button>
					</div>
				</form>
			</div>

			<div id="upload-job">
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" id="upload-job__drop" class="form--reset">
					<p>Select, or drag and drop your CORNEA job output into the box below. You can provide either a <code>.json</code> or <code>.json.gz</code> file.</p>
					<div class="dropzone" data-dropzone-type="job">
						<label for="upload-job__input-file"><span class="icon-upload icon--big icon--no-spacing">Choose a file, or drag and drop a file here</span></label>
						<input type="file" id="upload-job__input-file" name="corneaFile" class="dropzone__input-file" accept="application/x-gzip, application/json" />
						<div class="dropzone__message dropzone__message--normal">
							<div class="dropzone__progress"><span></span></div>
							<h3>Drop JSON or gzipped JSON here</h3>
							<p>Release your job file here for processing. Press <kbd>Esc</kbd> to close this dialog</p>
						</div>
						<div class="dropzone__message warning dropzone__message--aborted">
							<h3><span class="icon-attention"></span> File read aborted</h3>
							<p>File reading has been manually aborted by the user.</p>
							<p>Press <kbd>Esc</kbd> to dismiss this message.</p>
						</div>
						<div class="dropzone__message error dropzone__message--multi">
							<h3><span class="icon-attention"></span> Multiple files detected</h3>
							<p>You have attempted to upload multiple files. Please only upload a single file: we accept both the original <code>.json.gz</code> file, and the unzipped/uncompressed <code>json</code> file.</p>
							<p>Press <kbd>Esc</kbd> to dismiss this message.</p>
						</div>
						<div class="dropzone__message error dropzone__message--none">
							<h3><span class="icon-attention"></span> No files dropped</h3>
							<p>No files have been dropped. Please try again.</p>
							<p>Press <kbd>Esc</kbd> to dismiss this message.</p>
						</div>
						<div class="dropzone__message error dropzone__message--large">
							<h3><span class="icon-attention"></span> File too big</h3>
							<p>The file you have attempted to upload is too big.</p>
							<p>Press <kbd>Esc</kbd> to dismiss this message.</p>
						</div>
						<div class="dropzone__message error dropzone__message--invalid-filetype">
							<h3><span class="icon-attention"></span> Invalid file type</h3>
							<p>The file you have attempted to upload is not a JSON or a gzipped JSON file.</p>
							<p>Press <kbd>Esc</kbd> to dismiss this message.</p>
						</div>
						<div class="dropzone__message error dropzone__message--invalid-json">
							<h3><span class="icon-attention"></span> Invalid JSON</h3>
							<p>The JSON file you have provided is not valid. However, not all is lost&mdash;you can paste your raw JSON into a <a href="http://jsonlint.com">linting tool</a>, and you might be able to fix the error yourself. If you are confident that the JSON file was not modified and the error is from the server-side, please <a href="<?php echo WEB_ROOT;?>/issues">open a ticket</a>.</p>
							<p>Press <kbd>Esc</kbd> to dismiss this message.</p>
						</div>
						<div class="dropzone__message error dropzone__message--incomplete-json">
							<h3><span class="icon-attention"></span> Incomplete data</h3>
							<p>The <code>data</code> object, and/or its compulsory constituents, <code>edges</code> and <code>nodes</code>, are missing. Without these data a network map cannot be generated.</p>
							<p>Press <kbd>Esc</kbd> to dismiss this message.</p>
						</div>
					</div>
				</form>
			</div>

			<div id="check-job">
				<?php echo (isset($_GET['job']) ? '<div class="toggle hide-first"><h3><a href="#">Search for another job</a></h3>' : ''); ?>
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" id="cornea-form__status">
					<div class="cols">
						<label for="cornea-job-id" class="col-one">Job ID <a href="<?php echo WEB_ROOT; ?>/lib/docs/cornea/job-id" data-modal class="info" title="What is a job ID?">?</a></label>
						<input type="text" class="col-two" id="cornea-job-id" name="job" placeholder="Enter the job ID here." required />
					</div>

					<button type="submit" role="primary"><span class="pictogram icon-search">Search</span></button>
				</form>

				<?php if (isset($_GET['job'])) { echo '</div><div id="sigma-output"><div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div><h2 id="sigma-status--short">Searching for your job</h2><span class="byline">Please wait&hellip;</span><div id="sigma-status--long"></div></div>'; } ?>
			</div>
		</div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/stupidtable/0.0.1/stupidtable.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/lunr.js/0.6.0/lunr.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha/js/bootstrap.min.js"></script>
	
	<!-- File parsing -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pako/0.2.8/pako.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/4.1.2/papaparse.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/js/jquery.dataTables.min.js"></script>

	<!-- Visualization -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/colorbrewer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/sigma.min.js"></script>

	<!-- Page specific files -->
	<script src="<?php echo WEB_ROOT; ?>/dist/js/expat-form.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/cornea.min.js"></script>

	<!-- Google Recaptcha -->
	<?php if(!is_logged_in()) { ?><script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&amp;render=explicit"></script><?php } ?>
	<script>

		<?php if(is_logged_in()) { ?>

		// Manual recaptcha override
		globalVar.recaptcha = true;

		<?php } else { ?>

		// Google ReCaptcha
		var onloadCallback = function() {
				grecaptcha.render('google-recaptcha', {
					'sitekey' : '6Ld3VQ8TAAAAAO7VZkD4zFAvqUxUuox5VN7ztwM5',
					'callback': verifyCallback,
					'expired-callback': expiredCallback
				});
			},
			verifyCallback = function(response) {
				
			},
			expiredCallback = function() {
				grecaptcha.reset();
				
			};

		<?php } ?>

	</script>

</body>
</html>