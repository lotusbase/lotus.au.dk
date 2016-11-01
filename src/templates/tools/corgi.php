<?php
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>CORGI &mdash; Tools &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="/dist/css/tools.min.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="/dist/css/expat.min.css" type="text/css" media="screen" />
</head>
<body class="tools corgi">
	<?php
		$header = new \Lotusbase\PageHeader();
		$header->set_header_content('<div class="align-center">
			<h1>CORGI</h1>
			<span class="byline"><strong>Correlated Genes Identifier</strong><br />for <em>L.</em> japonicus reference genome <strong>v3</strong></span>
			<p>The <strong>Correlated Genes Identifier</strong> tool allows you pull out genes that have expression patterns that are strongly statistically correlated.</p>
		</div>');
		$header->set_header_background_image(WEB_ROOT.'/dist/images/header/cornea/cornea01.jpg');
		echo $header->get_header();
	?>

	<?php echo get_breadcrumbs(array('page_title' => 'CORGI')); ?>

	<section class="wrapper">
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="expat-form">

			<div class="cols has-legend" role="group">
				<p class="user-message full-width minimal legend">Query</p>

				<label for="expat-row-input" class="col-one">Gene/Transcript/Probe ID <a data-modal="wide" class="info" title="How should I enter the IDs?" href="<?php echo WEB_ROOT; ?>/lib/docs/gene-transcript-probe-id">?</a></label>
				<div class="col-two">
					<input type="text" id="expat-row" name="ids" value="<?php echo (isset($_GET['ids']) ? $_GET['ids'] : ''); ?>" placeholder="Enter query here" />
				</div>

			</div>

			<div class="cols has-legend" role="group">
				<p class="user-message full-width minimal legend">Filter</p>

				<label for="corgi-count" class="col-one">Rows returned</label>
				<input type="number" id="corgi-count" class="col-two" name="n" placeholder="Number of genes/probes to return" value="<?php echo (isset($_GET['n']) && !empty($_GET['n'])) ? $_GET['n'] : '10'; ?>" />
				
			</div>

			<div class="cols has-legend" role="group">
				<p class="user-message full-width minimal legend">Database</p>

				<label for="expat-dataset" class="col-one">Dataset <a data-modal="wide" class="info" title="What are the available datasets?" href="<?php echo WEB_ROOT; ?>/lib/docs/expat-datasets">?</a></label>
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
							<p>Enter a keyword to filter conditions. Each space-separated keyword is treated as an <code>AND</code>,  i.e. the condition has to match all listed keywords:<input type="text" placeholder="Filter conditions by entering a keyword&hellip;" disabled /></p>
							<div class="table-overflow">
								<table class="table--x-dense">
									<thead></thead>
									<tbody></tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				<label for="expat-condition" class="col-one">Custom sort (optional)</label>
				<div class="col-two">
					<ul class="sort-list expat-sort-list ui-state-empty" id="expat-sort-conditions"></ul>
					<input id="expat-condition" name="conditions" value="<?php echo (isset($_GET['conditions']) && !empty($_GET['conditions'])) ? $_GET['conditions'] : ''; ?>" placeholder="If left blank, all columns will be queried." />
				</div>
			</div>

			<?php if(!is_logged_in()) { ?>
			<div class="cols has-legend" role="group">
				<p class="user-message full-width minimal legend">Control</p>
				<p class="user-message full-width minimal">Pulling genes with correlated expression patterns from the database is a resource-intensive process, and therefore we require anonymous users to authenticate themselves as human before proceeding. To avoid the need to verify your identity, you can <a href="<?php echo WEB_ROOT.'/users/login'; ?>">log in with your account</a>, or <a href="<?php echo WEB_ROOT.'/users/register'; ?>">register for new one</a>.</p>
				<label>Human?</label>
				<div class="col-two" id="google-recaptcha"></div>

				<input type="hidden" id="expat-idtype" name="idtype" value="" />
				
				<div class="full-width"><button type="submit" role="primary" id="expat-form__submit" disabled><span class="pictogram icon-search">Search</span></button></div>
			</div>
			<?php } else { ?>
			<input type="hidden" id="expat-idtype" name="idtype" value="" />
			<input type="hidden" name="user_auth_token" value="<?php echo $_COOKIE['auth_token']; ?>" />

			<div class="full-width"><button type="submit" role="primary" id="expat-form__submit"><span class="pictogram icon-search">Search</span></button></div>
			<?php } ?>
		</form>
	</section>

	<section class="wrapper" id="corgi-results"></section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<?php if(!is_logged_in()) { ?><script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&amp;render=explicit"></script><?php } ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/stupidtable/0.0.1/stupidtable.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/lunr.js/0.6.0/lunr.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/expat-form.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/corgi.min.js"></script>
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
				globalVar.recaptcha = true;
				if($('#expat-form :input.error, #expat-form .input-mimic.error').length === 0 && globalVar.corgi.form.validator.errorList.length === 0 && globalVar.recaptcha) {
					$('#expat-form__submit').prop('disabled', false);
				} else {
					$('#expat-form__submit').prop('disabled', true);
				}
			},
			expiredCallback = function() {
				grecaptcha.reset();
				$('#expat-form__submit').prop('disabled', true);
			};

		<?php } ?>

	</script>
</body>
</html>