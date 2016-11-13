<?php
	require_once('../config.php');

	$error = false;
	$error_message = 'Please provide a valid &ge;v3.0 gene/transcript/protein or <abbr title="Gene Ontology">GO</abbr> term identifier. It should be in the format of <code>Lj{chr}g{version}v{id}[.{isoform}]</code>, or <code>GO:{7-digit-number}</code>.';

	if(!empty($_GET) && !empty($_GET['id'])) {
		$id = $_GET['id'];

		// Determine ID type
		if(preg_match('/^Lj(\d|chloro}mito)g3v(\d+)$/', $id)) {
			$id = 'gene';
		} else if(preg_match('/^Lj(\d|chloro}mito)g3v(\d+)\.\d+$/', $id)) {
			$id = 'transcript';
		} else if(preg_match('/^GO:\d{7}$/', $id)){
			$id = 'go';
		} else {
			$error = true;
		}

		if(!$error) {
			header('Location: '.WEB_ROOT.'/view/'.$id.'/'.$_GET['id']);
		}
	}

	if(!empty($_SESSION['view_error'])) {
		$error = true;
		$error_message = $_SESSION['view_error'];
		unset($_SESSION['view_error']);
	}

	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		$q1 = $db->prepare("SELECT
			GO_ID AS GOTerm,
			TranscriptCount AS TranscriptCount
		FROM gene_ontology_lotus_by_goID
		ORDER BY TranscriptCount DESC
		LIMIT 10");
		$q1->execute();
	} catch(Exception $e) {

	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>Gene Browser &mdash; Tools &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/view.min.css" type="text/css" media="screen" />
</head>
<body class="viewer init-scroll--disabled">

	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>Viewer</h2>
		<span class="byline">Data viewer for <em>L. japonicus</em>.</span>
		<p class="align-center">For a more full-fledged search, please use the <a href="<?php echo WEB_ROOT.'/tools/trex'; ?>">TREX tool</a>.</p>
		<?php
			if($error) {
				echo '<p class="user-message warning"><span class="icon-attention"></span>'.$error_message.'</p>';
			}
		?>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="has-group">
			<div class="cols" role="group">
				<label class="col-one" for="id">Identifier</label>
				<div class="col-two">
					<input type="text" name="id" id="id" placeholder="Enter a recognised identifier here" />
					<small><strong>Unsure about what identifiers to use? Pick an example from the tabs below:</strong></small>
				</div>
			</div>
			
			<div id="view-tabs">
				<div id="view-tabs__nav" class="cols align-items__flex-end ui-tabs-nav__wrapper">
					<ul class="tabbed">
						<li><a href="#view-example__gene-transcript" data-custom-smooth-scroll>Gene/Transcript/Protein</a></li>
						<li><a href="#view-example__go-annotation" data-custom-smooth-scroll>GO Annotation</a></li>
					</ul>
				</div>

				<div class="view-tabs__content" id="view-example__gene-transcript">
					<ul class="list--floated input-suggestions">
						<li>Examples:</li>
						<li><a href="#" data-value="Lj4g3v0281040">Lj4g3v0281040</a><span class="term-type"><em>LjFls2</em> (Gene)</span></li>
						<li><a href="#" data-value="Lj4g3v0281040.1">Lj4g3v0281040.1</a><span class="term-type">LjFls2 (Transcript/Protein)</span></li>
						<li><a href="#" data-value="Lj2g3v3373110">Lj2g3v3373110</a><span class="term-type"><em>LjNin</em> (Gene)</span></li>
						<li><a href="#" data-value="Lj2g3v3373110.1">Lj2g3v3373110.1</a><span class="term-type">LjNin (Transcript/Protein)</span></li>
					</ul>
				</div>

				<div class="view-tabs__content" id="view-example__go-annotation">
					<?php
						if($q1->rowCount()) {
							echo '<ul class="list--floated input-suggestions"><li>Examples:</li>';
							while ($row = $q1->fetch(PDO::FETCH_ASSOC)) {
								echo '<li><a href="#" data-value="'.$row['GOTerm'].'">'.$row['GOTerm'].'</a><span class="term-type">'.$row['TranscriptCount'].' transcripts</span></li>';
							}
							echo '</ul>';
						}
					?>
				</div>
			</div>

			<button type="submit"><span class="icon-search">Search</span></button>
		</form>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/view/index.min.js"></script>
</body>
</html>