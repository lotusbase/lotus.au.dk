<?php
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head itemscope itemtype="http://schema.org/WebSite">
	<title itemprop="name">Tools &amp; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/tools.min.css?bc32f641571e176b" type="text/css" media="screen" />
</head>
<body class="tools">
	<?php
		$header = new \LotusBase\PageHeader();
		$header->set_header_content('
		<h1><em>Lotus</em> Base tool suite</h1>
		<p>We have developed some tools that may be useful for you. Should you encounter any difficulties with the tools, check out the end-user documentation for more information.</p>
		<ul class="list--big">'.(is_intranet_client() ? '
			<li class="tool-corgi">
			<a href="'.WEB_ROOT.'/tools/corgi/" title="Correlated Genes Identifier">
					<div class="tool-icon"><span class="pictogram icon--no-spacing icon-fork"></span></div>
					<div class="tool-desc"><h3>CORGI</h3><p>The Correlated Genes Identifier (CORGI) tool allows users to look for genes whose expression pattern closely matches to their gene(s) of interest.</p></div>
				</a>
			</li>
			<li class="tool-cornea">
			<a href="'.WEB_ROOT.'/tools/cornea/" title="Coexpression Network Analysis">
					<div class="tool-icon"><span class="pictogram icon--no-spacing icon-network"></span></div>
					<div class="tool-desc"><h3>CORNEA</h3><p>The Coexpression Network Analysis (CORNEA) tool constructs a clustered network map of genes whose expression levels are highly correlated to each other.</p></div>
				</a>
			</li>
			<li class="tool-expat">
				<a href="'.WEB_ROOT.'/expat/" title="Expression Atlas (ExpAt)">
					<div class="tool-icon"><span class="pictogram icon--no-spacing icon-map"></span></div>
					<div class="tool-desc"><h3>ExpAt</h3><p>The <em>Lotus japonicus</em> gene expression atlas (ExpAt) allows users to visualize relative gene expression across multiple sets of genes (annotated as of version 3.0) over all growth conditions.</p></div>
				</a>
			</li>
			' : '').'
			<li class="tool-genomebrowser">
				<a href="'.WEB_ROOT.'/genome/" title="Lotus genome browser">
					<div class="tool-icon"><span class="pictogram icon--no-spacing icon-book"></span></div>
					<div class="tool-desc"><h3><em>Lotus</em> genome browser</h3><p>Browse through the latest version of the <em>Lotus japonicus</em> genome through a user-friendly web interface.</p></div>
				</a>
			</li>'.(is_intranet_client() ? '
			<li class="tool-primers">
				<a href="'.WEB_ROOT.'/tools/primers" title="Genotyping primer order generator">
					<div class="tool-icon"><span class="pictogram icon--no-spacing icon-basket"></span></div>
					<div class="tool-desc"><h3>Primer ordering</h3><p>Generate an order sheet for genotyping primers for LORE1 lines.</p></div>
				</a>
			</li>
			' : '').'
			<li class="tool-seqret">
				<a href="'.WEB_ROOT.'/tools/seqret" title="Sequence Retriever">
					<div class="tool-icon"><span class="pictogram icon--no-spacing icon-switch"></span></div>
					<div class="tool-desc"><h3>Sequence Retriever</h3><p>Retrieve sequences from BLAST databases based on accession numbers or GIs.</p></div>
				</a>
			</li>
			<li class="tool-seqpro">
				<a href="'.WEB_ROOT.'/tools/seqpro" title="Sequence Processor">
					<div class="tool-icon"><span class="pictogram icon--no-spacing icon-thermometer"></span></div>
					<div class="tool-desc"><h3>Sequence Processor</h3><p>Remove clutter from annotated sequences or BLAST output.</p></div>
				</a>
			</li>
			<li class="tool-trex">
				<a href="'.WEB_ROOT.'/tools/trex" title="Transcript Explorer">
					<div class="tool-icon"><span class="pictogram icon--no-spacing icon-direction"></span></div>
					<div class="tool-desc"><h3>Transcript Explorer</h3><p>Retrieve metadata for <em>Lotus</em> v3.0 transcripts, such as genome coordinates, genome browser uplink, exonic LORE1 insertions and more.</p></div>
				</a>
			</li>
		</ul>');
		echo $header->get_header();
	?>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>