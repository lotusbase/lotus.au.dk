<?php
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>SeqRet &mdash; Tools &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'The Sequence Retrieval (SeqRet) tool extracts sequences through corresponding identification information from BLAST databases hosted with Lotus Base.'
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" integrity="sha384-wCtV4+Y0Qc1RNg341xqADYvciqiG4lgd7Jf6Udp0EQ0PoEv83t+MLRtJyaO5vAEh" crossorigin="anonymous">
	<link rel="stylesheet" href="/dist/css/tools.min.css?bc32f641571e176b" type="text/css" media="screen" />
</head>
<body class="tools seqret">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title('SeqRet');
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>SeqRet</h2>
		<span class="byline">Sequence Retrieval Tool</span>
		<p>This tool allows you to extract sequences by providing the corresponding identification information. This tool is forked from and built upon <a href="https://github.com/aubombarely/wwwfastacmd" target="_new">wwwfastacmd project</a> developed by Aureliano Bombarely. The source code of this project is available on <a href="https://github.com/terrymun/wwwfastacmd" target="_new">GitHub</a>.</p>
		<form action="" method="get" id="seqret-form" class="has-group">
			<div class="cols" role="group">
				<label for="seqret-db" class="col-one">Database <a class="info" data-modal="wide" title="Database Overview" href="<?php echo WEB_ROOT; ?>/lib/docs/blast/db">?</a></label>
				<div class="col-two">
					<select id="seqret-db" name="db">
					<?php
						$dbs = new \LotusBase\BLAST\DBMetadata();
						$db_metadata = $dbs->get_metadata();
						$db_group = array();
						$db_count = 0;

						foreach($db_metadata as $db_basename => $db) {
							if(!in_array($db['category'], $db_group)) {
								$db_group[] = $db['category'];
								echo (count($db_group) > 1 ? '</optgroup>' : '').'<optgroup label="'.strip_tags($db['category']).'">';
							}
							echo '<option
								data-gi-dropdown="'.((isset($db['gi_dropdown']) && $db['gi_dropdown']) ? '1' : '0').'"
								data-gi-dropdown-target="'.substr(str_replace('.', '_', $db_basename), 0, -3).'"
								data-gi-db-category="'.$db['category'].'"
								data-gi-db-type="'.(isset($db['type']) ? $db['type'] : '').'"
								value="'.$db_basename.'"'.((!empty($_GET['db']) && $_GET['db'] === $db_basename) ? 'selected' : '').'
								>'.$db['title'].'</option>';
						}
						echo '</optgroup>';
					?>
					</select>
				</div>

				<label for="seqret-id" class="col-one">Accession / GI <a data-modal class="info" title="How should I enter the IDs?" data-modal-content="&lt;ul&gt;&lt;li&gt;&lt;strong&gt;Genome Assembly&lt;/strong&gt; &mdash; For searching in the &lt;em&gt;Lotus japonicus&lt;/em&gt; genome asssembly, the IDs are the chromosome numbers, e.g. &lt;code&gt;chr1&lt;/code&gt; for the version 3.0 assembly.&lt;/li&gt;&lt;li&gt;&lt;strong&gt;mRNA Illumina Read Assembly&lt;/strong&gt; &mdash; For searching in the Illumina read assemblies, the IDs are unique identifiers of each mRNA sequence. An example of an ID would be &lt;code&gt;NODE_30681_length_2141_cov_27.475946&lt;/code&gt;.&lt;/li&gt;&lt;/ul&gt;&lt;p class=&quot;user-message note&quot;&gt;For entering mulitple IDs, please enter each entry on a new line.&lt;/p&gt;">?</a></label>
				
				<div class="col-two" id="seqret-gi">
					<div>
						<div class="multiple-text-input input-mimic">
							<ul class="input-values">
							<?php
								if(!empty($_GET['id'])) {
									$blast_array = explode(',', $_GET['id']);
									foreach($blast_array as $blast_item) {
										echo '<li data-input-value="'.escapeHTML($blast_item).'">'.escapeHTML($blast_item).'<span class="icon-cancel" data-action="delete"></span></li>';
									}
								}
							?>
								<li class="input-wrapper"><input type="text" id="seqret-id-input" placeholder="Accession number / GI" autocomplete="off" /></li>
							</ul>
							<input class="input-hidden" type="hidden" name="id" id="seqret-id" value="<?php echo (!empty($_GET['id'])) ? escapeHTML($_GET['id']) : ''; ?>" readonly />
						</div>
						<small><strong>Separate each accession number or GI with a comma, space or tab.</strong></small>
					</div>

					<?php
						$gi_dropdowns = array(
							'20180416_Lj_Gifu_v1_1_genome' => array('chr0','chr1','chr2','chr3','chr4','chr5','chr6','chloro','mito'),
							'lj_r30' => array('chr0','chr1','chr2','chr3','chr4','chr5','chr6','chloro','mito'),
							'lj_pr28' => array('Ljchr1_pseudomol_2011025','Ljchr2_pseudomol_2011025','Ljchr3_pseudomol_2011025','Ljchr4_pseudomol_2011025','Ljchr5_pseudomol_2011025','Ljchr6_pseudomol_2011025'),
							'lj_r25' => array('chr0','chr1','chr2','chr3','chr4','chr5','chr6','chr7'),
							'lj_r24' => array('chr0','chr1','chr2','chr3','chr4','chr5','chr6','chr7'),
							'KAW_Scaffolds' => array('scaffold00001','scaffold00002','scaffold00003','scaffold00004','scaffold00005','scaffold00006','scaffold00007')
							);
						foreach($gi_dropdowns as $db => $gi) {
							echo '<select id="gi-'.$db.'" class="gi-dropdown" name="id" disabled>';
							foreach($gi as $opt) {
								echo '<option value="'.$opt.'" '.((!empty($_GET['id']) && $_GET['id'] === $opt) ? 'selected' : '').'>'.$opt.'</option>';
							}
							echo '</select>';
						}
					?>

				</div>

				<input type="hidden" id="seqret-query-id" />

			</div>

			<div class="cols has-legend" role="group">
				<p class="legend">Additional options</p>

				<label for="seqret-from" class="col-one">Set subsequence</label>
				<div class="col-two cols flex-wrap__nowrap field__positions">
					<label for="seqret-from">From</label>
					<input type="number" id="seqret-from" name="from" placeholder="Start Position" min="0" value="<?php echo isset($_GET['from']) && !empty($_GET['from']) ? intval($_GET['from']) : '' ?>" />
					<label for="seqret-to">to</label>
					<input type="number" id="seqret-to" name="to" placeholder="End Position" min="0" value="<?php echo isset($_GET['to']) && !empty($_GET['to']) ? intval($_GET['to']) : '' ?>" />
				</div>
				
				<label for="seqret-strand" class="col-one">Strand <a data-modal title="What strand should I choose?" data-modal-content="Strand on subsequence (nucleotide only): 1 is top, 2 is bottom. When 2 (or minus) strand is chosen, you will obtain the reverse complimentary strand.&lt;/p&gt;&lt;p&gt;If you are extracting a sequence based on your BLAST search result, check the &lt;code&gt;subject&lt;/code&gt; strand if it is &lt;strong&gt;plus&lt;/strong&gt; (1) or &lt;strong&gt;minus&lt;/strong&gt; (2).&lt;ul&gt;&lt;li&gt;&lt;code&gt;Auto&lt;/code&gt; &mdash; Intelligently guesses the polarity of the strand you wish to extract based on the position you have provided.&lt;/li&gt;&lt;li&gt;&lt;code&gt;Manual: Plus&lt;/code&gt; &mdash; Marks polarity of your strand as plus.&lt;/li&gt;&lt;li&gt;&lt;code&gt;Manual: Minus&lt;/code&gt; &mdash; Marks polarity of your strand as minus.&lt;/li&gt;&lt;/ul&gt;&lt;p class='user-message note'&gt;When strand direction is set to auto, the search script can handle inverted start and end positions intelligently. This is the default behavior. You can always override this behavior by manually marking the strand as a plus or a minus (not recommended)." class="help">?</a></label>
				<div class="col-two">
					<select id="seqret-strand" name="st">
						<option value="auto">Auto: Intelligent mode</option>
						<option value="plus">Manual: Plus or (+) or 1</option>
						<option value="minus">Manual: Minus or (-) or 2</option>
					</select>
				</div>
			</div>

			<button type="submit">Retrieve sequences</button>
		</form>
	</section>

	<section class="wrapper hide-first" id="seqret-results"></section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js" integrity="sha384-iViGfLSGR6GiB7RsfWQjsxI2sFHdsBriAK+Ywvt4q8VV14jekjOoElXweWVrLg/m" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/seqret.min.js"></script>
</body>
</html>