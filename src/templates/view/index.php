<?php
	require_once('../config.php');

	$error = false;
	$error_message = 'Please provide a valid &ge;v3.0 gene/transcript/protein or <abbr title="Gene Ontology">GO</abbr> term identifier. It should be in the format of <code>Lj{chr}g{version}v{id}[.{isoform}]</code>, or <code>GO:{7-digit-number}</code>.';

	if(!empty($_GET) && !empty($_GET['id'])) {
		$id = escapeHTML($_GET['id']);

		// Determine ID type
		if(preg_match('/^Lj(\d|chloro}mito)g3v(\d+)$/i', $id)) {
			$id_type = 'gene';
		} else if(preg_match('/^Lj(\d|chloro}mito)g3v(\d+)\.\d+$/i', $id)) {
			$id_type = 'transcript';
		} else if(preg_match('/^((DK\d+\-0)?3\d{7}|[apl]\d{4,})$/i', $id)) {
			$id_type = 'lore1';
		} else if(preg_match('/^(ipr|cd|g3dsa|mf|pthr|pf|pirsf|pr|pd|ps|sfldf|sm|ssf|tigr).*$/i', $id)) {
			$id_type = 'domain';
		} else if(preg_match('/^GO:\d{7}$/i', $id)){
			$id_type = 'go';
		} else {
			$error = true;
		}

		if(!$error) {
			header('Location: '.WEB_ROOT.'/view/'.$id_type.'/'.$_GET['id']);
			exit();
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
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Data viewer for Lotus japonicus&mdash;view gene, transcript, protein, and GO annotation definitions'
			));
		echo $document_header->get_document_header();
	?>
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
						<li><a href="#view-example__lore1" data-custom-smooth-scroll><em>LORE1</em> mutants</a></li>
						<li><a href="#view-example__domain-prediction" data-custom-smooth-scroll>Predicted domain</a></li>
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

				<div class="view-tabs__content" id="view-example__lore1">
					<ul class="list--floated input-suggestions">
						<li>Examples:</li>
						<li><a href="#" data-value="30010101">30010101</a><span class="term-type">Danish line</span></li>
						<li><a href="#" data-value="DK02-030010101">DK02-030010101</a><span class="term-type">Danish line (alternative format)</span></li>
						<li><a href="#" data-value="A00005">A00005</a><span class="term-type">Japanese line</span></li>
					</ul>
				</div>

				<div class="view-tabs__content" id="view-example__domain-prediction">
					<ul class="list--floated input-suggestions">
						<li>Examples:</li>
						<li><a href="#" data-value="IPR000719">IPR000719</a><span class="term-type">InterPro</span></li>
						<li><a href="#" data-value="cd14066">cd14066</a><span class="term-type">CDD</span></li>
						<li><a href="#" data-value="G3DSA:3.90.550.10">G3DSA:3.90.550.10</a><span class="term-type">Gene3D</span></li>
						<li><a href="#" data-value="MF_01928">MF_01928</a><span class="term-type">Hamap</span></li>
						<li><a href="#" data-value="PTHR24420">PTHR24420</a><span class="term-type">PANTHER</span></li>
						<li><a href="#" data-value="PS50011">PS50011</a><span class="term-type">PatternScan</span></li>
						<li><a href="#" data-value="PF00069">PF00069</a><span class="term-type">Pfam</span></li>
						<li><a href="#" data-value="PIRSF030250">PIRSF030250</a><span class="term-type">PIRSF</span></li>
						<li><a href="#" data-value="PR00019">PR00019</a><span class="term-type">PRINTS</span></li>
						<li><a href="#" data-value="PD005521">PD005521</a><span class="term-type">ProDom</span></li>
						<li><a href="#" data-value="PS00108">PS00108</a><span class="term-type">ProSite Patterns</span></li>
						<li><a href="#" data-value="PS50011">PS50011</a><span class="term-type">ProSite Profiles</span></li>
						<li><a href="#" data-value="SFLDF00063">SFLDF00063</a><span class="term-type">SFLD</span></li>
						<li><a href="#" data-value="SM00369">SM00369</a><span class="term-type">SMART</span></li>
						<li><a href="#" data-value="SSF52047">SSF52047</a><span class="term-type">SUPERFAMILY</span></li>
						<li><a href="#" data-value="TIGR01151">TIGR01151</a><span class="term-type">TIGRFAM</span></li>
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