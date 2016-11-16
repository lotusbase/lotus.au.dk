<?php
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>Lotus Expression Atlas (ExpAt)&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/expat.min.css" type="text/css" media="screen" />
</head>
<body class="tools expat wide">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<div class="align-center">
			<h1>ExpAt</h1>
			<span class="byline"><em>Lotus japonicus</em> Expression Atlas</span>
		</div><div>
			<p>Access array data from the <em>Lotus japonicus</em> Expression Atlas. As the expression data is anchored based on gene predictions and annotations on version 3.0 of the genome, only accessions from this version are accepted. Due to the use of modern HTML5 APIs in the graphing functions, you are strongly encouraged to use a standards-compliant browser to use this tool. The graphing process is CPU intensive, and may slow down the performance of your browser/tab momentarily&mdash;especially when large number of genes are queried at one go.</p>
			<p>If you wish to map your probe IDs against the version 3.0 accessions, please use the <a href="mapping.php">gene &amp; probe mapping tool</a> to retrieve the corresponding IDs before searching.</p>
		</div>');
		$header->set_header_background_image(WEB_ROOT.'/dist/images/header/expat/expat01.jpg');
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title('ExpAt');
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<?php if(isset($_SESSION['expat']) && !empty($_SESSION['expat']) && $_SESSION['expat']['error'] === true) {
			echo '<p class="user-message warning" id="expat-error">'.$_SESSION['expat']['message'].'</p>';
		}

		unset($_SESSION['expat']);
		?>

		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="expat-form" class="form--reset">

			<div class="cols" role="group">

				<label for="expat-row-input" class="col-one">Gene/Transcript/Probe ID <a data-modal="wide" class="info" title="How should I enter the IDs?" href="<?php echo WEB_ROOT; ?>/lib/docs/gene-transcript-probe-id">?</a></label>
				<div class="col-two">
					<div class="multiple-text-input input-mimic">
						<ul class="input-values">
							<?php
								if(isset($_GET['ids']) && !empty($_GET['ids'])) {
									// Check if IDs are passed as a comma-separated string, or as an array
									if(is_array($_GET['ids'])) {
										$ids_array = $_GET['ids'];
									} else {
										$ids_array = explode(",", $_GET['ids']);
									}

									foreach($ids_array as $ids_item) {
										echo '<li data-input-value="'.escapeHTML($ids_item).'">'.escapeHTML($ids_item).'<span class="icon-cancel" data-action="delete"></span></li>';
									}
								}
							?>
							<li class="input-wrapper"><input type="text" name="ids-input" id="expat-row-input" placeholder="Enter accession number or GI here" autocomplete="off" /></li>
						</ul>
						<input class="input-hidden" type="hidden" name="ids" id="expat-row" value="<?php echo (isset($_GET['ids']) && !empty($_GET['ids'])) ? escapeHTML($_GET['ids']) : ''; ?>" readonly />
					</div>
					<small><strong>Separate each accession number of GI with a comma, space or tab.</strong></small>
				</div>

				<label for="expat-dataset" class="col-one">Dataset <a data-modal="wide" class="info" title="What are the available datasets?" href="<?php echo WEB_ROOT; ?>/lib/docs/expat-datasets">?</a></label>
				<div class="col-two">
					<?php
						$expat_dataset = new \Lotusbase\ExpAt\Dataset();
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
					<input id="expat-condition" name="conditions" value="<?php echo (isset($_GET['conditions']) && !empty($_GET['conditions'])) ? escapeHTML($_GET['conditions']) : ''; ?>" placeholder="If left blank, all columns will be queried." />
				</div>

				<label lass="col-one">Data transform</label>
				<div class="col-two">
					<label for="expat-data-transform__raw">
						<input type="radio" id="expat-data-transform__raw" name="data_transform" value="false" <?php echo (!isset($_GET['data_transform']) || (isset($_GET['data_transform']) && !$_GET['data_transform'])) ? 'checked' : ''; ?>/> None (raw data)
					</label>
					<label for="expat-data-transform__normalize">
						<input type="radio" id="expat-data-transform__normalize" name="data_transform" value="normalize" <?php echo (isset($_GET['data_transform']) && !empty($_GET['data_transform']) && $_GET['data_transform'] === 'normalize') ? 'checked' : ''; ?>/> Normalize (across condition / by row)
					</label>
					<label for="exoat-data-transform__standardize">
						<input type="radio" id="expat-data-transform__standardize" name="data_transform" value="standardize" <?php echo (isset($_GET['data_transform']) && !empty($_GET['data_transform']) && $_GET['data_transform'] === 'standardize') ? 'checked' : ''; ?>/> Standardize (across condition / by row)
					</label>
				</div>
			</div>

			<input type="hidden" id="expat-idtype" name="idtype" value="" />

			<div class="cols justify-content__center">
				<button type="submit" role="primary"><span class="pictogram icon-search">Search</span></button>
				<button type="button" id="download-raw-data" role="secondary"><span class="pictogram icon-download">Download raw data without visualization</span></button>
			</div>
		</form>

		<div id="expat-results"></div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>

	<!-- Load plugins -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/stupidtable/0.0.1/stupidtable.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/lunr.js/0.6.0/lunr.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/colorbrewer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/d3-tip.min.js"></script>

	<!-- Load page scripts -->
	<script src="<?php echo WEB_ROOT; ?>/dist/js/expat-form.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/expat-output.min.js"></script>
	
</body>
</html>
