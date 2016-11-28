<?php 
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>Citations&mdash;Meta&mdash;Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'How to cite the use of Lotus Base'
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/meta.min.css" type="text/css" media="screen" />
</head>
<body class="meta citation">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();
	?>

	<?php
		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_titles(array('Info', 'Citing <em>Lotus</em> Base'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>Citing <em>Lotus</em> Base</h2>
		<p>We are glad that you have found <em>Lotus</em> Base useful in your research, be it in the field of legume biology or otherwise. As it is difficult to track use of <em>Lotus</em> Base if only the URL is cited in a manuscript (e.g. "<em>Lotus</em> Base, <a href="https://<?php echo $_SERVER['SERVER_NAME']; ?>">https://<?php echo $_SERVER['SERVER_NAME']; ?></a> [Online; accessed 2016-11-28]"), we encourage users to cite the relevant published manuscripts instead.</p>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="has-group">
			<div role="group" class="has-legend">
				<p class="legend">Citation helper</p>
				<p>Select one or more of the following that is relevant to you:</p>
				<label for="citation__lotus-base"><input id="citation__lotus-base" name="citation[]" type="checkbox" class="prettify citation__filter" value="lotus-base" /><span>Use of <strong>any</strong> <em>Lotus</em> Base resources (e.g. <em>LORE1</em> ordering, Expression Atlas)</span></label>
				<label for="citation__lore1-mutants"><input id="citation__lore1-mutants" name="citation[]" type="checkbox" class="prettify citation__filter" value="lore1-mutants" /><span>Use of <em>LORE1</em> mutants</span></label>
				<label for="citation__lore1-methods"><input id="citation__lore1-methods" name="citation[]" type="checkbox" class="prettify citation__filter" value="lore1-methods" /><span>Generation of <em>LORE1</em> mutant population</span></label>
			</div>
		</form>
		<h3 id="citation__header">Reference list <span class="badge" data-original-count="4">4</span></h3>
		<p>This list will be filtered based on the criteria above. If no criteria is set, all references will be displayed.</p>
		<div id="citation-tabs">
			<div id="citation-tabs__nav" class="cols align-items__flex-end ui-tabs-nav__wrapper">
				<ul class="tabbed">
					<li><a href="#citation__list" data-custom-smooth-scroll>List</a></li>
					<li><a href="#citation__html" data-custom-smooth-scroll>HTML</a></li>
					<li><a href="#citation__bibtex" data-custom-smooth-scroll>BibTex</a></li>
				</ul>
			</div>

			<div id="citation__list">
				<ul>
					<li>Mun et al. (in press). <em>Lotus</em> Base: An integrated information portal for the model legume <em>Lotus japonicus</em>. <em>Sci. Rep.</em></li>
					<li>Małolepszy et al. (2016). The <em>LORE1</em> insertion mutant resource. <em>Plant J.</em> <a href="https://www.ncbi.nlm.nih.gov/pubmed/27322352">doi:10.1111/tpj.13243</a>.</li>
					<li>Urbański et al. (2012). Genome-wide <em>LORE1</em> retrotransposon mutagenesis and high-throughput insertion detection in <em>Lotus japonicus</em>. <em>Plant J.</em>, 69(4). <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014280" title="Genome-wide LORE1 retrotransposon mutagenesis and high-throughput insertion detection in Lotus japonicus.">doi:10.1111/j.1365-313X.2011.04827.x</a>.</li>
					<li>Fukai et al. (2012) Establishment of a <em>Lotus japonicus</em> gene tagging population using the exon-targeting endogenous retrotransposon <em>LORE1</em></strong> (2012). <em>Plant J.</em>, 69(4). <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014259" title="Establishment of a Lotus japonicus gene tagging population using the exon-targeting endogenous retrotransposon LORE1.">doi:10.1111/j.1365-313X.2011.04826.x</a>.</li>
				</ul>
			</div>

			<div id="citation__html">
				<textarea readonly>&lt;ul&gt;
	&lt;li&gt;Mun et al. (in press). &lt;em&gt;Lotus&lt;/em&gt; Base: An integrated information portal for the model legume &lt;em&gt;Lotus japonicus&lt;/em&gt;. &lt;em&gt;Sci. Rep.&lt;/em&gt;&lt;/li&gt;
	&lt;li&gt;Małolepszy et al. (2016). The &lt;em&gt;LORE1&lt;/em&gt; insertion mutant resource. &lt;em&gt;Plant J.&lt;/em&gt; &lt;a href="https://www.ncbi.nlm.nih.gov/pubmed/27322352"&gt;doi:10.1111/tpj.13243&lt;/a&gt;.&lt;/li&gt;
	&lt;li&gt;Urbański et al. (2012). Genome-wide &lt;em&gt;LORE1&lt;/em&gt; retrotransposon mutagenesis and high-throughput insertion detection in &lt;em&gt;Lotus japonicus&lt;/em&gt;. &lt;em&gt;Plant J.&lt;/em&gt;, 69(4). &lt;a href="http://www.ncbi.nlm.nih.gov/pubmed/22014280" title="Genome-wide LORE1 retrotransposon mutagenesis and high-throughput insertion detection in Lotus japonicus."&gt;doi:10.1111/j.1365-313X.2011.04827.x&lt;/a&gt;.&lt;/li&gt;
	&lt;li&gt;Fukai et al. (2012) Establishment of a &lt;em&gt;Lotus japonicus&lt;/em&gt; gene tagging population using the exon-targeting endogenous retrotransposon &lt;em&gt;LORE1&lt;/em&gt;&lt;/strong&gt; (2012). &lt;em&gt;Plant J.&lt;/em&gt;, 69(4). &lt;a href="http://www.ncbi.nlm.nih.gov/pubmed/22014259" title="Establishment of a Lotus japonicus gene tagging population using the exon-targeting endogenous retrotransposon LORE1."&gt;doi:10.1111/j.1365-313X.2011.04826.x&lt;/a&gt;.&lt;/li&gt;
&lt;/ul&gt;</textarea>
			</div>

			<div id="citation__bibtex">
				<textarea readonly>@article{Maolepszy:2016aa,
    Author = {Mun, Terry and Bachmann, Asger and Gupta, Vikas and Stougaard, Jens and Andersen, Stig Uggerh{\o}j},
    Journal = {Sci Rep},
    Journal-Full = {Scientific Reports},
    Pst = {aheadofprint},
    Title = {\emph{Lotus} Base, an integrated information portal for the model legume \emph{Lotus japonicus}},
    Year = {in press}}

@article{Maolepszy:2016aa,
    Author = {Ma{\l}olepszy, Anna and Mun, Terry and Sandal, Niels and Gupta, Vikas and Dubin, Manu and Urba{\'n}ski, Dorian and Shah, Niraj and Bachmann, Asger and Fukai, Eigo and Hirakawa, Hideki and Tabata, Satoshi and Nadzieja, Marcin and Markmann, Katharina and Su, Junyi and Umehara, Yosuke and Soyano, Takashi and Miyahara, Akira and Sato, Shusei and Hayashi, Makoto and Stougaard, Jens and Andersen, Stig Uggerh{\o}j},
    Doi = {10.1111/tpj.13243},
    Journal = {Plant J},
    Journal-Full = {The Plant journal : for cell and molecular biology},
    Month = {Jun},
    Pmid = {27322352},
    Pst = {aheadofprint},
    Title = {The \emph{\textsc{LORE1}} insertion mutant resource},
    Year = {2016},
    Bdsk-Url-1 = {http://dx.doi.org/10.1111/tpj.13243}}

@article{Urbanski:2012aa,
    Author = {Urba{\'n}ski, Dorian Fabian and Ma{\l}olepszy, Anna and Stougaard, Jens and Andersen, Stig Uggerh{\o}j},
    Doi = {10.1111/j.1365-313X.2011.04827.x},
    Journal = {Plant J},
    Journal-Full = {The Plant journal : for cell and molecular biology},
    Mesh = {Computational Biology; DNA Primers; Exons; Genome, Plant; Genotyping Techniques; High-Throughput Screening Assays; Lotus; Mutagenesis, Insertional; Mutation; Retroelements; Reverse Genetics; Seeds; Sequence Analysis, DNA; Software; Terminal Repeat Sequences},
    Month = {Feb},
    Number = {4},
    Pages = {731--741},
    Pmid = {22014280},
    Pst = {ppublish},
    Title = {Genome-wide \emph{\textsc{LORE1}} retrotransposon mutagenesis and high-throughput insertion detection in \emph{Lotus japonicus}},
    Volume = {69},
    Year = {2012},
    Bdsk-Url-1 = {http://dx.doi.org/10.1111/j.1365-313X.2011.04827.x}}

@article{Fukai:2012aa,
    Author = {Fukai, Eigo and Soyano, Takashi and Umehara, Yosuke and Nakayama, Shinobu and Hirakawa, Hideki and Tabata, Satoshi and Sato, Shusei and Hayashi, Makoto},
    Doi = {10.1111/j.1365-313X.2011.04826.x},
    Journal = {Plant J},
    Journal-Full = {The Plant journal : for cell and molecular biology},
    Mesh = {DNA Primers; Exons; Gene Targeting; Lotus; Mutagenesis, Insertional; Mutation; Retroelements; Sequence Analysis, DNA; Symbiosis; Terminal Repeat Sequences},
    Month = {Feb},
    Number = {4},
    Pages = {720--730},
    Pmid = {22014259},
    Pst = {ppublish},
    Title = {Establishment of a \emph{Lotus japonicus} gene tagging population using the exon-targeting endogenous retrotransposon \emph{\textsc{LORE1}}},
    Volume = {69},
    Year = {2012},
    Bdsk-Url-1 = {http://dx.doi.org/10.1111/j.1365-313X.2011.04826.x}}</textarea>
			</div>
		</div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/citation.min.js"></script>
</body>
</html>
