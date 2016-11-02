<?php
	require_once('config.php');
?>
<!doctype html>
<html lang="en">
<head itemscope itemtype="http://schema.org/WebSite">
	<title itemprop="name">Lotus Base</title>
	<?php include('head.php'); ?>
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

		$header = new \LotusBase\PageHeader();
		$header->add_header_class('alt');
		$header->set_header_content('<div class="align-center"><h1>'.file_get_contents(DOC_ROOT."/dist/images/branding/logo.svg").'<em>Lotus</em> Base</h1><span class="byline">Genomic, proteomic &amp; expression resources for <em>Lotus japonicus</em>.</span></div>');
		echo $header->get_header();
	?>

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
					<div class="full-width searchform__suggestions">
						<ul class="list--floated">
							<li>Example:</li>
							<li><a href="#" data-value="LjFls2"><em>LjFls2</em></a></li>
							<li><a href="#" data-value="Lj4g3v0281040">Lj4g3v0281040</a></li>
							<li><a href="#" data-value="Lj4g3v0281040.1">Lj4g3v0281040.1</a></li>
						</ul>
					</div>
					</form>
				</div>

				<div id="searchform__prediction">
					<form action="<?php echo WEB_ROOT; ?>/tools/trex" class="search-form flex-wrap__wrap" method="get">
					<p class="full-width">Search for predictions based on <abbr>GO</abbr> terms and prediction domains (InterPro, PFam, Superfamily, etc.).</p>
					<input type="search" name="ids" placeholder="GO term (GO:0004672)" />
					<button type="submit"><span class="icon-search icon--no-spacing"></span></button>
					<div class="full-width searchform__suggestions">
						<ul class="list--floated">
							<li>Example:</li>
							<li><a href="#" data-value="GO:0004672">GO:0004672</a></li>
						</ul>
					</div>
					</form>
				</div>

				<div id="searchform__lore1">
					<form action="<?php echo WEB_ROOT; ?>/lore1/search" class="search-form flex-wrap__wrap" method="get">
					<p class="full-width">Search for <em>LORE1</em> lines of interest using an internal ID.</p>
					<input type="search" name="pid" placeholder="Line ID (e.g. 30010101)" />
					<button type="submit"><span class="icon-search icon--no-spacing"></span></button>
					<div class="full-width searchform__suggestions">
						<ul class="list--floated">
							<li>Example:</li>
							<li><a href="#" data-value="30010101">30010101</a></li>
						</ul>
					</div>
					</form>
				</div>
			</div>
		</div>
		<div id="twitter-timeline" class="col align-center">
			<a class="twitter-timeline" data-lang="en" data-theme="light" data-link-color="#5ca4a9" href="https://twitter.com/lotusbase" data-dnt="true" data-height="300"><span class="icon-twitter">Find us on Twitter at @LotusBase</span></a>
		</div>
	</section>

	<section id="using-lore1-lines" class="wrapper">
		<h2>Using <em>LORE1</em> lines</h2>
		<p>The <em>LORE1</em> lines are currently shipped free of charge and MTAs are not required. If you find the lines useful in your research and have obtained them through <em>Lotus</em> Base, we ask that you cite the following <em>LORE1</em> papers:</p>

		<ul>
			<li><strong>Genome-wide <em>LORE1</em> retrotransposon mutagenesis and high-throughput insertion detection in <em>Lotus japonicus</em></strong> (<a href="http://www.ncbi.nlm.nih.gov/pubmed/22014280" title="Genome-wide LORE1 retrotransposon mutagenesis and high-throughput insertion detection in Lotus japonicus.">Urbanski et al., 2012</a>),</li>
			<li><strong>Establishment of a <em>Lotus japonicus</em> gene tagging population using the exon-targeting endogenous retrotransposon <em>LORE1</em></strong> (<a href="http://www.ncbi.nlm.nih.gov/pubmed/22014259" title="Establishment of a Lotus japonicus gene tagging population using the exon-targeting endogenous retrotransposon LORE1.">Fukai et al., 2012</a>),</li>
			<li><strong>The <em>LORE1</em> insertion mutant resource</strong> (<a href="http://www.ncbi.nlm.nih.gov/pubmed/27322352" title="The LORE1 insertion mutant resource">Małolepszy et al., 2016</a>), and</li>
			<li><strong><em>Lotus</em> Base: An integrated information portal for the model legume <em>Lotus japonicus</em></strong> (Mun et al., submitted)</li>
		</ul>

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
			<span class="count">'.sp($row['CountryCount']).'</span>
			countries served
		</div>
		<div class="col-content">
			<span class="count">'.sp($row['OrderCount']).'</span>
			orders processed
		</div>
		<div class="col-content">
			<span class="count">'.sp($row['LineCount']).'</span>
			<em>LORE1</em> lines shipped
		</div>
		<div class="col-content">
			<span class="count">'.sp($row['SeedCount']).'</span>
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
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/topojson/1.6.19/topojson.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3-tip/0.6.7/d3-tip.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/worldmap.min.js"></script>
	<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/home.min.js"></script>
</body>
</html>
