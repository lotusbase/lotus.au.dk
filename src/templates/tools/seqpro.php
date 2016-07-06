<?php
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>SeqPro &mdash; Tools &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/css/tools.css" type="text/css" media="screen" />
</head>
<body class="tools seqpro">
	<?php
		$header = new \LotusBase\PageHeader();
		echo $header->get_header();
	?>

	<?php echo get_breadcrumbs(array('page_title' => 'SeqPro')); ?>

	<section class="wrapper">
		<h2>Sequence Processor (SeqPro)</h2>
		<p>This tool allows you to processes data into a more compact/readable format. Two formats are currently supported: BLAST output and nucleotide / amino acid sequences. Usually, the tool will have no problem with automatic detecting on the type of data you have entered, so we recommend leaving the "auto/intelligent guess" option untouched. Conversion will be done on-the-fly as you paste the data into the textarea.</p>
		<form>
			<label for="input-type" class="col-one">Select the type of data: <a class="info" data-modal data-modal-content="The script will attempt to detect the type of data you have entered, and process it accordingly. So far the accepted types are:&lt;/p&gt;&lt;ul&gt;&lt;li&gt;&lt;strong&gt;Auto&lt;/strong&gt; &mdash; This is the default option. The script will intelligently guess the type of data you have entered, and process it accordingly. It usually works, so there is no need to override this option.&lt;/li&gt;&lt;li&gt;&lt;strong&gt;Amino Acid / Nucleotide Sequence&lt;/strong&gt; &mdash; Marks the input data as an amino acid or a nucleotide sequence. This will remove all numbers, spaces and line breaks in the string, giving you a clean and uninterrupted sequence. This option also intelligently recognizes sequences that comes with a &lt;code&gt;&amp;gt;lcl|...&lt;/code&gt; header, which it will remove to clean up the sequence.&lt;/li&gt;&lt;li&gt;&lt;strong&gt;BLAST Output&lt;/strong&gt; &mdash; Marks the input data as a NCBI BLAST output. This is useful when you want to extract assession numbers or other identification from a BLAST search result. By default, the algorithm removes all annotations, scores and E-values. However, if you want to retain them, you will be provided with an option after pasting your BLAST output.&lt;/li&gt;&lt;/ul&gt;&lt;p&gt;If the script fails to detect your data correctly, you can override it manually." title="What data should I select?">?</a></label>
			<div class="col-two">
				<select name="input-type" id="input-type">
					<option value="auto" selected="selected">Auto / Intelligent Guess</option>
					<option value="aant">Amino Acid / Nucleotide Sequence</option>
					<option value="blast">Blast Output (chr, node or ctg)</option>
				</select>
			</div>
			<div class="way-wrap">
				<label for="data-input">Enter the data you want to process below:</label>
				<textarea name="data-input" id="data-input" rows="5"></textarea>
			</div>
		</form>

		<form id="output">
			<h3>Processed data:</h3>
			<p></p>
			<fieldset id="data-refine">
				<legend>BLAST filter options</legend>
				<input type="checkbox" id="all-cols" name="all-cols" /><label for="all-cols">Display all columns (description, score and expect) <a class="info" data-modal data-modal-content="Checking this option will display the other columns &mdash; the score and the expect value. This option can be used when you want the entire BLAST output data available. The columns are tab-separated, so you can paste it directly into a spreadsheet program." title="When should I display all columns?">?</a></label>
			</fieldset>

			<!-- For regular output -->
			<pre></pre>

			<!-- For tabulated output -->
			<table></table>
		</form>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>