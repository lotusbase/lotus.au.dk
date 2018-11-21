<?php
	require_once('config.php');
?>
<!doctype html>
<html lang="en">
<head itemscope itemtype="http://schema.org/WebSite">
	<title itemprop="name">Lotus Base</title>
	<?php include ('head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/home.min.css" type="text/css" media="screen" />
	<script type="application/ld+json">
	{
		"@context": "http://schema.org",
		"@type": "WebSite",
		"name" : "Lotus Base",
		"url": "https://lotus.au.dk/",
		"potentialAction": {
			"@type": "SearchAction",
			"target": "https://lotus.au.dk/search?q={search_term_string}",
			"query-input": "required name=search_term_string"
		}
	}
	</script>
</head>
<body class="home">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->add_header_class('alt');
		$header->set_header_content('<div class="align-center"><h1>'.file_get_contents(DOC_ROOT."/dist/images/branding/logo.svg").'<em>Lotus</em> Base</h1><span class="byline">Genomic, proteomic &amp; expression resources for <em>Lotus japonicus</em>.</span></div>');
		echo $header->get_header();
	?>

	<section id="intro" class="wrapper">
		<h2>An integrated information portal for <em>Lotus</em></h2>
		<p><em>Lotus</em> Base is an integrated information portal for the model legume <em>Lotus japonicus</em>. Similar to the creation of online portals for other model plants, <em>Lotus</em> Base is motivated by the fragmented landscape of <em>Lotus</em> data, and strives to provide comprehensive data and a unified workflow to legume researchers. In the spirit of open research, <em>Lotus</em> Base is open source and free-of-charge.</p>
		<p>Further information on how <em>Lotus</em> Base is implemented can be gleaned from our published paper:</p>
		<blockquote>Mun, T., Bachmann, A., Gupta, V., Stougaard, J., Andersen, S.U. (2016). <em>Lotus</em> Base: An integrated information portal for the model legume <em>Lotus japonicus</em>. <em>Sci. Rep.</em> <a href="http://www.nature.com/articles/srep39447">doi:10.1038/srep39447</a>.</blockquote>
	</section>

	<section class="wrapper cols">
		<div class="col">
			<h2>Search</h2>
			<p>Looking for something? Use the search form below to start mining through currently available <em>Lotus</em> data.</p>
			<div id="searchform-tabs">
				<div id="searchform-tabs__nav" class="cols align-items__flex-end ui-tabs-nav__wrapper">
					<ul class="tabbed">
						<li><a href="#searchform__gene" data-custom-smooth-scroll>Gene/Transcript</a></li>
						<li><a href="#searchform__prediction" data-custom-smooth-scroll>Prediction</a></li>
						<li><a href="#searchform__lore1" data-custom-smooth-scroll><em>LORE1</em></a></li>
					</ul>
				</div>

				<div id="searchform__gene">
					<form action="<?php echo WEB_ROOT; ?>/tools/trex" class="search-form flex-wrap__wrap" method="get">
					<p class="full-width">Search for a candidate gene or transcript using an internal identifier. Alternatively, use keywords for a full-text search.</p>
					<input type="search" name="ids" placeholder="Gene ID / name (e.g. Lj4g3v0281040.1 / LjFls2)" />
					<button type="submit"><span class="icon-search icon--no-spacing"></span></button>
					<div class="full-width">
						<ul class="list--floated input-suggestions">
							<li>Examples:</li>
							<?php if (!(isset($userComps) && in_array('Gifu_1.1', $userComps))) { ?>
							<li><a href="#" data-value="Lj4g3v0281040">Lj4g3v0281040</a><span class="tag term-type"><em>LjFls2</em> (Gene)</span><span class="tag genome-assembly">MG20 v3.0</span></li>
							<li><a href="#" data-value="Lj4g3v0281040.1">Lj4g3v0281040.1</a><span class="tag term-type">LjFls2 (Transcript/Protein)</span><span class="tag genome-assembly">MG20 v3.0</span></li>
							<?php } ?>
							<li><a href="#" data-value="Lj2g3v3373110">Lj2g3v3373110</a><span class="tag term-type"><em>LjNin</em> (Gene)</span><span class="tag genome-assembly">MG20 v3.0</span></li>
							<li><a href="#" data-value="Lj2g3v3373110.1">Lj2g3v3373110.1</a><span class="tag term-type">LjNin (Transcript/Protein)</span><span class="tag genome-assembly">MG20 v3.0</span></li>
							<?php if (isset($userComps) && in_array('Gifu_1.1', $userComps)) { ?>
								<!--
								<li><a href="#" data-value="PGSB_gene_73000">PGSB_gene_73000</a><span class="tag term-type"><em>LjFls2</em> (Gene)</span><span class="tag genome-assembly">Gifu v1.1</span></li>
								<li><a href="#" data-value="PGSB_mRNA_123440">PGSB_mRNA_123440</a><span class="tag term-type">LjFls2 (Transcript/Protein)</span><span class="tag genome-assembly">Gifu v1.1</span></li>
								-->
								<li><a href="#" data-value="PGSB_gene_1157">PGSB_gene_1157</a><span class="tag term-type"><em>LjNin</em> (Gene)</span><span class="tag genome-assembly">Gifu v1.1</span></li>
								<li><a href="#" data-value="PGSB_mRNA_2162">PGSB_mRNA_2162</a><span class="tag term-type">LjNin (Transcript/Protein)</span><span class="tag genome-assembly">Gifu v1.1</span></li>
							<?php } ?>
						</ul>
					</div>
					</form>
				</div>

				<div id="searchform__prediction">
					<form action="<?php echo WEB_ROOT; ?>/tools/trex" class="search-form flex-wrap__wrap" method="get">
					<p class="full-width">Search for predictions based on <abbr>GO</abbr> terms and prediction domains (InterPro, PFam, Superfamily, etc.).</p>
					<input type="search" name="ids" placeholder="GO term (GO:0004672)" />
					<button type="submit"><span class="icon-search icon--no-spacing"></span></button>
					<div class="full-width">
						<ul class="list--floated input-suggestions">
							<li>Examples:</li>
							<li><a href="#" data-value="GO:0004672">GO:0004672</a><span class="tag term-type">GO term</span></li>
							<li><a href="#" data-value="IPR000719">IPR000719</a><span class="tag term-type">InterPro</span></li>
							<li><a href="#" data-value="cd14066">cd14066</a><span class="tag term-type">CDD</span></li>
							<li><a href="#" data-value="G3DSA:3.90.550.10">G3DSA:3.90.550.10</a><span class="tag term-type">Gene3D</span></li>
							<li><a href="#" data-value="MF_01928">MF_01928</a><span class="tag term-type">Hamap</span></li>
							<li><a href="#" data-value="PTHR24420">PTHR24420</a><span class="tag term-type">PANTHER</span></li>
							<li><a href="#" data-value="PS50011">PS50011</a><span class="tag term-type">PatternScan</span></li>
							<li><a href="#" data-value="PF00069">PF00069</a><span class="tag term-type">Pfam</span></li>
							<li><a href="#" data-value="SIGNAL_PEPTIDE_N_REGION">SIGNAL_PEPTIDE_N_REGION</a><span class="tag term-type">Phobius</span></li>
							<li><a href="#" data-value="PIRSF030250">PIRSF030250</a><span class="tag term-type">PIRSF</span></li>
							<li><a href="#" data-value="PR00019">PR00019</a><span class="tag term-type">PRINTS</span></li>
							<li><a href="#" data-value="PD005521">PD005521</a><span class="tag term-type">ProDom</span></li>
							<li><a href="#" data-value="PS00108">PS00108</a><span class="tag term-type">ProSite Patterns</span></li>
							<li><a href="#" data-value="PS50011">PS50011</a><span class="tag term-type">ProSite Profiles</span></li>
							<li><a href="#" data-value="SFLDF00063">SFLDF00063</a><span class="tag term-type">SFLD</span></li>
							<li><a href="#" data-value="SignalP-TM_EUK">SignalP-TM_EUK</a><span class="tag term-type">SignalP</span></li>
							<li><a href="#" data-value="SM00369">SM00369</a><span class="tag term-type">SMART</span></li>
							<li><a href="#" data-value="SSF52047">SSF52047</a><span class="tag term-type">SUPERFAMILY</span></li>
							<li><a href="#" data-value="TIGR01151">TIGR01151</a><span class="tag term-type">TIGRFAM</span></li>
							<li><a href="#" data-value="TMHelix">TMHelix</a><span class="tag term-type">TMHMM</span></li>
						</ul>
					</div>
					</form>
				</div>

				<div id="searchform__lore1">
					<form action="<?php echo WEB_ROOT; ?>/lore1/search" class="search-form flex-wrap__wrap" method="get">
					<p class="full-width">Search for <em>LORE1</em> lines of interest using an internal ID.</p>
					<input type="search" name="pid" placeholder="Line ID (e.g. 30010101)" />
					<button type="submit"><span class="icon-search icon--no-spacing"></span></button>
					<div class="full-width">
						<ul class="list--floated input-suggestions">
							<li>Examples:</li>
							<li><a href="#" data-value="30010101">30010101</a><span class="tag term-type">Danish line</span></li>
							<li><a href="#" data-value="A00005">A00005</a><span class="tag term-type">Japanese line</span></li>
						</ul>
					</div>
					</form>
				</div>
			</div>
		</div>
		<div id="twitter-timeline" class="col align-center">
			<a class="twitter-timeline" data-lang="en" data-theme="light" data-link-color="#5ca4a9" href="https://twitter.com/lotusbase" data-dnt="true" data-height="300" data-chrome="noheader noborders transparent"><span class="icon-twitter">Find us on Twitter at @LotusBase</span></a>
		</div>
	</section>


	<section id="using-lore1-lines" class="wrapper">
		<h2>Using <em>LORE1</em> lines</h2>
		<p>The <em>LORE1</em> lines are currently shipped free of charge and MTAs are not required. If you have used published protocols in establishing an independent <em>LORE1</em> mutagenesis population, please cite the following papers, published back-to-back in <em>Plant Journal</em>:
		<ul>
			<li>Urbanski et al. (2012). Genome-wide <em>LORE1</em> retrotransposon mutagenesis and high-throughput insertion detection in <em>Lotus japonicus</em>. <em>Plant J.</em>, 69(4). <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014280" title="Genome-wide LORE1 retrotransposon mutagenesis and high-throughput insertion detection in Lotus japonicus.">doi:10.1111/j.1365-313X.2011.04827.x</a>.</li>
			<li>Fukai et al. (2012) Establishment of a <em>Lotus japonicus</em> gene tagging population using the exon-targeting endogenous retrotransposon <em>LORE1</em></strong> (2012). <em>Plant J.</em>, 69(4). <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014259" title="Establishment of a Lotus japonicus gene tagging population using the exon-targeting endogenous retrotransposon LORE1.">doi:10.1111/j.1365-313X.2011.04826.x</a>.</li>
		</ul>

		<p>For the general use of <em>LORE1</em> mutants, we ask you to cite:</p>

		<ul>
			<li>Małolepszy et al. (2016). The <em>LORE1</em> insertion mutant resource. <em>Plant J.</em> <a href="https://www.ncbi.nlm.nih.gov/pubmed/27322352">doi:10.1111/tpj.13243</a>.</li>
		</ul>

		<p class="user-message note align-center"><span class="icon-book"></span>For further citation help, please refer to the <a href="<?php echo WEB_ROOT.'/meta/citation'; ?>" title="Citing Lotus Base">citation guide</a>.</p>

		<p>The majority of the <em>LORE1</em> lines are released pre-publication, and the Centre for Carbohydrate Recognition and Signalling reserves the right to undertake and publish large-scale analysis of the insertion site data. Large-scale in this context refers to any sequence intervals or combinations thereof that exceed one megabase in length.</p>

		<p>Should you encounter any difficulty in using this site, you can look up the <a href="/meta/faq.php" title="Frequently Asked Questions">end-user documentation</a>.</p>
	</section>

	<section id="latest-posts" class="wrapper">
		<h2>Fresh from the blog</h2>
		<?php include('./blog/recent.html'); ?>
	</section>

	<?php

	// Database
	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		// Prepare query
		$q = $db->prepare("SELECT
				COUNT(DISTINCT ord.Salt) as OrderCount,
				COUNT(DISTINCT ord.Country) as CountryCount,
				COUNT(lin.OrderLineID) as LineCount,
				SUM(lin.SeedQuantity) AS SeedCount
			FROM orders_unique AS ord INNER JOIN orders_lines AS lin ON
				ord.Salt = lin.Salt
			WHERE lin.ProcessDate IS NOT NULL AND ord.Verified = 1 AND ord.ShippingEmail = 1
		");

		// Execute query with array of values
		$q->execute();

		function sp($str) {
			return '<span>'.implode('</span><span>', str_split($str)).'</span>';
		}

		// Fetch row
		$row = $q->fetch();

		echo '<section class="wrapper cols cols-4 stats"><h2><em>LORE1</em> mutants usage statistics</h2>';
		echo '
		<div id="world-map"><ul id="world-map__legend"></ul></div>
		<div class="col-content">
			<div class="odometer" data-target-value="'.$row['CountryCount'].'">0</div>
			countries served
		</div>
		<div class="col-content">
			<div class="odometer" data-target-value="'.$row['OrderCount'].'">0</div>
			orders processed
		</div>
		<div class="col-content">
			<div class="odometer" data-target-value="'.$row['LineCount'].'">0</div>
			<em>LORE1</em> lines shipped
		</div>
		<div class="col-content">
			<div class="odometer" data-target-value="'.$row['SeedCount'].'">0</div>
			seeds sorted
		</div>
		';
		echo '<p>The provision of <em>LORE1</em> insertional mutagenesis lines free-of-charge has allowed us to attract plant researchers across the globe to expand our current understanding and knowledge of the model legume <em>Lotus japonicus</em>. You can order your line(s) of interest now with our online form.</p><div class="stats-buttons cols justify-content__space-around"><a role="secondary" class="button button--big" href="/lore1/search" title="Search LORE1 lines">Search for lines</a><a role="secondary" class="button button--big" href="/lore1/order" title="Order LORE1 lines">Order lines</a></div></section>';

	} catch(PDOException $err) {
		echo '<!--<section class="wrapper"><p class="user-message warning">We have experienced a MySQL error: '.$err->getMessage().'.</p></section>-->';
	}

	?>

	<?php include('footer.php'); ?>

	<!-- For d3.js -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js" integrity="sha384-gOxMGMgqQH8iYyQE8rmgpaokSRE608gSIXXdC2a/yT+OywUqbNmTCQa3qNO4wvyc" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/topojson/1.6.19/topojson.min.js" integrity="sha384-BUz7BfOv7l6jnNmNtX+Wwvp/+c/jxxOJORIxDbG03T0ZuFtcdvM3b95R3t7fygMU" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3-tip/0.6.7/d3-tip.min.js" integrity="sha384-EXdY705BisDV53yySolbxB0gXs2bwh5nVosWEkMH05Gvxsvv2HmJA8l1A6WcLTWU" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/colorbrewer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/odometer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/worldmap.min.js"></script>
	<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/home.min.js"></script>
</body>
</html>
