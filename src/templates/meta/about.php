<?php 
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>About&mdash;Meta&mdash;Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Project description and information, and the efforts behind Lotus Base'
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/meta.min.css" type="text/css" media="screen" />
</head>
<body class="meta about">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->add_header_class('alt');
		$header->set_header_content('<div class="align-center"><h1>'.file_get_contents(DOC_ROOT."/dist/images/branding/logo.svg").'About <em>Lotus</em> Base</h1></div>');
		echo $header->get_header();
	?>

	<?php
		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_titles(array('Info', 'About <em>Lotus</em> Base'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>What is <em>Lotus</em> Base</h2>
		<p><em>Lotus</em> Base is an integrated information portal for the model legume <em>Lotus japonicus</em>. Similar to the creation of <a href="https://www.araport.org" title="Araport">Araport</a> and <a href="http://soybase.org" title="SoyBase">SoyBase</a>, <em>Lotus</em> Base is motivated by the fragmented landscape of <em>Lotus</em> data, and strives to provide comprehensive data and a unified workflow to legume researchers.</p>

		<h3>Design principles</h3>
		<h4>OS-agnoticism &amp; standards compliance</h4>
		<p>We are aware of how the use of modern, standards-compliant web technology helps to improve user experience and ensure efficient use of computing resources in browsers. <em>Lotus</em> Base is powered by a suite of browser technology, leveraging on the efficiency of JS engines and HTML5 API.</p>

		<h4>Open source, community-powered</h4>
		<p>Many features and toolkits are facilitated, built on, or running off open source projects&mdash;<a href="https://github.com/d3/d3" title="d3">d3</a>, <a href="https://github.com/gruntjs/grunt" title="Grunt">Grunt</a>, <a href="https://github.com/GMOD/jbrowse" title="JBrowse">JBrowse</a>, <a href="https://github.com/drewm/mailchimp-api" title="MailChimp API">MailChimp API</a>, <a href="https://github.com/wurmlab/sequenceserver" title="SequenceServer">SequenceServer</a>, <a href="https://github.com/jacomyal/sigma.js" title="SigmaJS">SigmaJS</a>, etc. On a similar vein, <em>Lotus</em> Base is an open source project whose code base is freely accessible to the public and <a href="https://github.com/lotusbase/lotus.au.dk" title="Lotus Base on GitHub">hosted on GitHub</a>. Moreover, we offer a <a href="https://lotus.au.dk/docs/api/v1/" title="Lotus Base API Reference">REST service through an API endpoint</a>.</p>

		<h4>Modular construction</h4>
		<p><em>Lotus</em> Base has a modular construction, meaning that additional toolkits and services may be built onto it easily. We adopt object-oriented codes for easy integration of new functionalities and features.</p>

		<h4>Ethical design</h4> 
		<p>We adopt a user-centric and ethical design, which prioritises user privacy and ownership over their own data. You can <a href="<?php echo WEB_ROOT; ?>/meta/legal" title="Terms of use of Lotus Base">read our terms of use and privacy policy here</a>.</p>

		<h3>How can I cite <em>Lotus</em> Base?</h3>
		<p>The manuscript for <em>Lotus</em> Base is currently in press, and should be citated as the following:</p>
		<pre>Mun et al. (2016). Lotus Base: An integrated information portal for the model legume <em>Lotus japonicus</em>. <em>Sci. Rep.</em> doi:10.1038/srep39447</pre>
		<p>For additional guidelines towards citing resources hosted on <em>Lotus</em> Base, please refer to the <a href="<?php echo WEB_ROOT; ?>/meta/citation" title="Citation guide">citation guide</a>.</p>

		<h3>Meet the team</h3>
		<p><em>Lotus</em> Base is powered by a small group of researchers from the Centre for Carbohydrate Recognition and Signalling, Aarhus University, Denmark. You can <a href="<?php echo WEB_ROOT; ?>/meta/team" tite="Meet the team">learn more about us here</a>.</p>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>
