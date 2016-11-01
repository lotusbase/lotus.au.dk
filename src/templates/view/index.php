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
?>
<!doctype html>
<html lang="en">
<head>
	<title>Gene Browser &mdash; Tools &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/viewer.min.css" type="text/css" media="screen" />
</head>
<body class="viewer init-scroll--disabled">

	<?php
		$header = new \LotusBase\PageHeader();
		echo $header->get_header();
	?>

	<?php echo get_breadcrumbs(); ?>

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
				<input class="col-two" type="text" name="id" id="id" placeholder="Enter gene/transcript/protein identifier (e.g. Lj4g3v0281040 or Lj4g3v0281040.1), or GO term (e.g. GO:0000018)" />
			</div>
			<button type="submit"><span class="icon-search">Search</span></button>
		</form>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>