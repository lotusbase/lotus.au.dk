<?php
	// Get important files
	require_once('../config.php');

	// Initiate time count
	$start_time = microtime(true);

	// Error
	$error = array();

	// Establish connection
	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);		
	} catch(PDOException $e) {
		$error = array(
			'error' => true,
			'message' => 'We have experienced a problem trying to establish a database connection. Please contact the system administrator as soon as possible'
		);
	}

	// Declare global user object
	if(isset($_COOKIE['auth_token']) && auth_verify($_COOKIE['auth_token'])) {
		$user = auth_verify($_COOKIE['auth_token']);
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>PhyAlign &mdash; Tools &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/tools.min.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/phyalign.min.css" type="text/css" media="screen" />
</head>
<body class="tools phyalign init-scroll--disabled">
	<?php
		$header = new \LotusBase\PageHeader();
		echo $header->get_header();
	?>

	<?php echo get_breadcrumbs(array('page_title' => 'PhyAlign')); ?>

	<section class="wrapper">
		<h2>PhyAlign</h2>
		<span class="byline">Phylogeny + Alignment using Clustal Omega</span>
		<p>The <strong>PhyAlign</strong> tool interfaces with the <a href="http://www.ebi.ac.uk/Tools/msa/clustalo/" title="EMBL-EBI Clustal Omega"><abbr title="European Molecular Biology Lab">EMBL</abbr>-<abbr title="European Bioinformatics Institute">EBI</abbr> Clustal Omega</a><sup><a href="#ref1">1</a>, <a href="#ref2">2</a></sup> tool to enable multiple sequence alignment and phylogenetic tree construction. Should there be any discrepancies between the information provided on this page and the official EMBL-EBI provisioned one, please refer to the <a href="http://www.ebi.ac.uk/Tools/msa/clustalo/help/index.html">official EMBL-EBI Clustal Omega documentation</a>. Required fields are marked with an asterisk (*).</p>
		<?php 
			// Display error if any
			if(!empty($error)) {
				echo '<div class="user-message warning align-center"><h3>Houston, we have a problem!</h3>'.$error['message'].'</div>';
			}
			else if(isset($_SESSION['trex_error'])) {
				echo '<div class="user-message warning align-center"><h3>Houston, we have a problem!</h3>'.$_SESSION['trex_error'].'</div>';
				unset($_SESSION['trex_error']);
			}

			// Define whitelist
			$phyalign_db_whitelist = array('type' => array('protein','transcript'));

		?>
		<div id="phyalign-tabs">
			<ul>
				<li><a href="#create-alignment" title="Create new Clustal Omega alignment" data-custom-smooth-scroll><span class="icon-switch">Create new ClustalO alignment</span></a></li>
				<li><a href="#get-alignment" title="Retrieve Clustal Omega alignment" data-custom-smooth-scroll><span class="icon-download">Retrieve ClustalO alignment</span></a></li>
				<li><a href="#make-tree" title="Make phylogenetic tree" data-custom-smooth-scroll><span class="icon-fork">Make phylogenetic tree</span></a></li>
			</ul>

			<div id="create-alignment">
				<form action="https://www.ebi.ac.uk/Tools/services/rest/clustalo/run/" method="post" id="phyalign-form__submit" class="has-group">
					<div class="cols has-legend" role="group">
						<p class="legend">Sequence information</p>
						<p class="full-width">Retrieve sequences from our BLAST databases using the form below, or&hellip;</p>
						<label for="phyalign-db" class="col-one">Database <a class="info" data-modal="wide" title="Database Overview" href="<?php echo WEB_ROOT; ?>/data/blast-db?<?php echo http_build_query(array('whitelist' => $phyalign_db_whitelist)); ?>">?</a></label>
						<div class="col-two">
							<select id="phyalign-db" name="db">
							<?php
								$dbs = new \LotusBase\BLAST\DBMetadata();
								//$dbs->set_db_whitelist($phyalign_db_whitelist);
								$db_metadata = $dbs->get_metadata();
								$db_count = 0;

								foreach($db_metadata as $db_basename => $db) {
									if(!in_array($db['category'], $db_group)) {
										$db_group[] = $db['category'];
										echo (count($db_group) > 1 ? '</optgroup>' : '').'<optgroup label="'.strip_tags($db['category']).'">';
									}
									echo '<option
										data-gi-dropdown="'.(isset($db['gi_dropdown']) ? '1' : '0').'"
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

						<label for="ids_input" class="col-one">Accession / GI <a data-modal class="info" title="How should I enter the IDs?" data-modal-content="&lt;ul&gt;&lt;li&gt;&lt;strong&gt;Genome Assembly&lt;/strong&gt; &mdash; For searching in the &lt;em&gt;Lotus japonicus&lt;/em&gt; genome asssembly, the IDs are the chromosome numbers, e.g. &lt;code&gt;chr1&lt;/code&gt; for the version 3.0 assembly.&lt;/li&gt;&lt;li&gt;&lt;strong&gt;mRNA Illumina Read Assembly&lt;/strong&gt; &mdash; For searching in the Illumina read assemblies, the IDs are unique identifiers of each mRNA sequence. An example of an ID would be &lt;code&gt;NODE_30681_length_2141_cov_27.475946&lt;/code&gt;.&lt;/li&gt;&lt;/ul&gt;&lt;p class=&quot;user-message note&quot;&gt;For entering mulitple IDs, please enter each entry on a new line.&lt;/p&gt;">?</a></label>
						<div class="col-two">
							<div class="multiple-text-input input-mimic">
								<ul class="input-values">
								<?php
									if(!empty($_GET['ids'])) {
										if(is_array($_GET['ids'])) {
											$phyalign_array = $_GET['ids'];
										} else {
											$phyalign_array = explode(",", $_GET['ids']);
										}
										foreach($phyalign_array as $phyalign_item) {
											echo '<li data-input-value="'.$phyalign_item.'">'.$phyalign_item.'<span class="icon-cancel" data-action="delete"></span></li>';
										}
									}
								?>
									<li class="input-wrapper"><input type="text" id="ids-input" placeholder="Transcript ID, e.g. Lj4g3v0281040.1" autocomplete="off" autocorrect="off"  autocapitalize="off" spellcheck="false" /></li>
								</ul>
								<input class="input-hidden" type="hidden" name="ids" id="ids" value="<?php echo (!empty($_GET['ids'])) ? (is_array($_GET['ids']) ? implode(',', preg_replace('/\"/', '&quot;', $phyalign_array)) : preg_replace('/\"/', '&quot;', $_GET['ids'])) : ''; ?>" readonly />
							</div>
							<small><strong>Separate each transcript ID with a comma, space, or tab. This list will automatically propagate the sequences field below.</strong></small>
						</div>

						<div class="separator full-width"><span>or</span></div>

						<p class="full-width">&hellip;provide sequences formatted as FASTA:</p>

						<label for="seqs-input" class="col-one">Sequences (FASTA) <span class="asterisk" title="Required Field">*</span></label>
						<div class="col-two">
							<textarea name="sequence" id="seqs-input" rows="5" class="resize__vertical font-family_monospace" placeholder="Enter sequences in FASTA format" required ></textarea>
						</div>

						<label for="seq-type" class="col-one">Sequence type <a class="info" data-modal title="Sequence type" href="<?php echo WEB_ROOT; ?>/data/phyalign/seqtype">?</a><span class="asterisk" title="Required Field">*</span></label>
						<div class="col-two">
							<select name="stype" id="seq-type">
								<option value="dna">DNA</option>
								<option value="rna">RNA</option>
								<option value="protein" selected>Protein</option>
							</select>
						</div>
					</div>

					<div class="cols has-legend" role="group">
						<p class="legend">Job options</p>

						<label for="output-format" class="col-one">Output format <a class="info" data-modal title="Output alignment format" href="<?php echo WEB_ROOT; ?>/data/phyalign/outfmt">?</a><span class="asterisk" title="Required Field">*</span></label>
						<div class="col-two">
							<select name="outfmt" id="output-format" class="col-two">
								<option value="clustal" selected>Clustal without numbers</option>
								<option value="clustal_num">Clustal with numbers</option>
								<option value="fa">Pearson/FASTA</option>
								<option value="msf">Multiple Sequence File (MSF) alignment format</option>
								<option value="nexus">NEXUS alignment format</option>
								<option value="phylip">PHYLIP interleaved alignment format</option>
								<option value="selex">SELEX alignment format</option>
								<option value="stockholm">STOCKHOLM alignment format</option>
								<option value="vienna">VIENNA alignment format</option>
							</select>
						</div>

						<label for="email" class="col-one">Email <a class="info" data-modal data-modal-content="EMBL-EBI mandates a valid email to be provided when submitting a Clustal Omega job through their RESTful services. As per the &lt;a href=&quot;http://www.ebi.ac.uk/about/terms-of-use&quot;&gt;terms of use&lt;/a&gt; of EMBL-EBI tools, your email will not be disclosed to anyone else, and will be exclusively used to contact you for the following events:&lt;/p&gt;&lt;ul&gt;&lt;li&gt;Problems with the service which affect your jobs.&lt;/li&gt;&lt;li&gt;Scheduled maintenance which affects services you are using.&lt;/li&gt;&lt;li&gt;Deprecation and retirement of a service you are using.&lt;/li&gt;&lt;/ul&gt;&lt;p class=&quot;user-message warning&quot;&gt;Providing a false email address will lead to job being killed and your IP, organisation, or entire domain being black-listed.&lt;/p&gt;&lt;p&gt;Please refer to &lt;a href=&quot;http://www.ebi.ac.uk/Tools/webservices/help/faq#why_do_you_need_my_e-mail_address&quot;&gt;their documentation for further information&lt;/a&gt;." title="Why do I have to provide my email?">?</a><span class="asterisk" title="Required Field">*</span></label>
						<div class="col-two"><input type="email" id="email" name="email" placeholder="Email (required)" required value="<?php echo (!empty($user['Email'])) ? $user['Email'] : ''; ?>" /><?php if(!empty($user['Email'])) { ?><small><strong>Hi <?php echo $user['FirstName']; ?>! We have retrieved your email because you are logged in as a user.</strong><br />You may use a different email address if you choose to.</small><?php } ?></div>

						<label for="job-title" class="col-one">Job title</label>
						<input type="text" class="col-two" id="job-title" name="title" placeholder="Job title (optional)" />
					</div>

					<div class="cols has-legend" role="group">
						<p class="legend">Additional options</p>

						<div class="full-width">
							<label for="dealign"><input type="checkbox" id="dealign" name="dealign" class="prettify"><span>Remove gap for input sequences (dealignment)</span></label>
						</div>

						<label for="iteration-count" class="col-one">Number of iterations <a class="info" data-modal title="Number of combined iterations" data-modal-content="The number of combined guide-tree or HMM iterations. Adapted from &lt;a href=&quot;http://www.ebi.ac.uk/Tools/msa/clustalo/help/index.html#iterations&quot;&gt;EMBL-EBI documentation&lt;/a&gt;.">?</a></label>
						<input type="number" id="iteration-count" name="iterations" min="0" max="5" step="1" value="0" placeholder="0–5" class="col-two" />

						<label for="max-guide-tree-iterations" class="col-one">Max guide tree iterations <a class="info" data-modal title="Maximum number of guide tree iterations" data-modal-content="Having set the number of combined iterations, this parameter can be changed to limit the number of guide tree iterations within the combined iterations. Default value is &lt;code&gt;-1&lt;/code&gt;, meaning that there is no limit imposed. Adapted from &lt;a href=&quot;http://www.ebi.ac.uk/Tools/msa/clustalo/help/index.html#gtiterations&quot;&gt;EMBL-EBI documentation&lt;/a&gt;.">?</a></label>
						<input type="number" id="max-guide-tree-iterations" name="gtiterations" min="-1" max="5" step="1" value="-1" placeholder="-1 (n.a.) or 0–5" class="col-two" />

						<label for="max-hmm-iterations" class="col-one">Max HMM interations <a class="info" data-modal title="Maximum number of HMM iterations" data-modal-content="Having set the number of combined iterations, this parameter can be changed to limit the number of HMM iterations within the combined iterations. Default value is &lt;code&gt;-1&lt;/code&gt;, meaning that there is no limit imposed. Adapted from &lt;a href=&quot;http://www.ebi.ac.uk/Tools/msa/clustalo/help/index.html#hmmiterations&quot;&gt;EMBL-EBI documentation&lt;/a&gt;.">?</a></label>
						<input type="number" id="max-hmm-iterations" name="hmmiterations" min="-1" max="5" step="1" value="-1" placeholder="-1 (n.a.) or 0–5" class="col-two" />

					</div>

					<div class="cols has-legend" role="group">
						<p class="legend">Guide-tree and distance matrix output options</p>
						<p class="full-width">Any of these options, once checked, will uncheck all the mBed options. These two groups of options are mutually exclusive.</p>
						<div class="full-width">
							<label for="guide-tree"><input type="checkbox" id="guide-tree" name="guidetreeout" class="prettify"><span>Guide-tree output</span></label>
							<label for="distance-matrix"><input type="checkbox" id="distance-matrix" name="dismatout" class="prettify"><span>Distance matrix output</span></label>
						</div>
					</div>

					<div class="cols has-legend" role="group">
						<p class="legend">mBed clustering options</p>
						<p class="full-width">Any of these options, once checked, will uncheck all the guide-tree and distance matrix output options. These two groups of options are mutually exclusive.</p>
						<div class="full-width">
							<label for="mbed-guide-tree"><input type="checkbox" id="mbed-guide-tree" name="mbed" class="prettify" checked><span>mBed-like clustering guide-tree</span></label>
							<label for="mbed-iteration"><input type="checkbox" id="mbed-iteration" name="mbediteration" class="prettify" checked><span>mBed-like clustering iteration</span></label>
						</div>
					</div>

					<button type="submit"><span class="pictogram icon-upload">Submit job to EMBL-EBI Clustal Omega</span></button>
				</form>
			</div>

			<div id="get-alignment">
				<p>Retrieve data from a multiple sequence alignment you have already submitted to the EMBL-EBI Clustal Omega tool. The job does not necessarily have to be created from <em>Lotus</em> Base.</p>
				<form action="<?php echo WEB_ROOT; ?>/api/v1/phyalign/data" method="get" id="phyalign-form__get" class="has-group">
					<div class="cols" role="group">
						<label for="clustalo-jobid" class="col-one">ClustalO Job ID <span class="asterisk" title="Required Field">*</span></label>
						<div class="col-two">
							<input type="text" id="clustalo-jobid" name="clustalo_jobid" placeholder="EMBL-EBI Clustal Omega job identifier" />
						</div>
					</div>

					<button type="submit"><span class="pictogram icon-search">Retrieve job data</span></button>
				</form>
				<div id="phyalign__job-status" class="hidden"></div>
				<div id="phyalign__job-data" class="hidden">
					<div class="cols align-items__flex-end ui-tabs-nav__wrapper">
						<ul id="phyalign__job-data__nav" class="minimal"></ul>
					</div>
				</div>
			</div>

			<div id="make-tree">
				<p>Submit a Newick-format tree file to draw phylogenetic tree.</p>
				<form action="#" method="post" id="phyalign-form__tree" class="has-group">
					<div class="cols has-legend" role="group">
						<p class="legend">Newick tree data</p>
						<p class="full-width">New to the tool? Try loading a sample tree:</p>
						<label for="tree-load" class="col-one">Select a demo</label>
						<select name="tree-load" id="tree-load" class="col-two">
							<option>Select one example tree to load</option>
							<option value="<?php echo WEB_ROOT; ?>/data/sample/phyalign/tol.tree">Tree of Life</option>
							<option value="<?php echo WEB_ROOT; ?>/data/sample/phyalign/vertebrates.tree">Selected vertebrates</option>
						</select>
						<div class="separator full-width"><span>or</span></div>
						<p class="full-width">&hellip;paste a Newick-format tree in the textarea below, or drag and drop a valid file in this box.</p>
						<label for="tree-input" class="col-one">Newick tree <span class="asterisk" title="Required Field">*</span></label>
						<div class="col-two">
							<textarea id="tree-input" name="tree" placeholder="Paste Newick-format tree file here" rows="10" class="resize__vertical"></textarea>
						</div>
					</div>

					<button type="submit"><span class="pictogram icon-fork">Draw tree</span></button>
				</form>
				<div id="phyalign-tree">
					<div id="phyalign-tree__svg"></div>
					<form id="phyalign-tree__controls" action="#" method="get" class="has-group">
						<div class="has-legend" role="group">
							<p class="legend">Display options</p>
							<label for="tc__bootstrap-nodes"><input type="checkbox" name="tc__bootstrap-nodes" id="tc__bootstrap-nodes" class="prettify" /><span>Color nodes by bootstrap values</span></label>
							<label for="tc__bootstrap-links"><input type="checkbox" name="tc__bootstrap-links" id="tc__bootstrap-links" class="prettify" /><span>Color links by bootstrap values</span></label>
							<label for="tc__internodes"><input type="checkbox" name="tc__internodes" id="tc__internodes" class="prettify" /><span>Show internodes</span></label>
							<label for="tc__leaves"><input type="checkbox" name="tc__leaves" id="tc__leaves" class="prettify" /><span>Show leaf nodes</span></label>
							<label for="tc__root"><input type="checkbox" name="tc__root" id="tc__root" class="prettify" checked /><span>Show root</span></label>
						</div>

						<div class="has-legend" role="group">
							<p class="legend">Scale options</p>
							<label for="tc__scale"><input type="checkbox" name="tc__scale" id="tc__scale" class="prettify" /><span>Draw to scale</span></label>
							<label for="tc__scale-bar"><input type="checkbox" name="tc__scale" id="tc__scale-bar" class="prettify" /><span>Show scale bar</span></label>
							<label for="tc__grid"><input type="checkbox" name="tc__grid" id="tc__grid" class="prettify" /><span>Show grid</span></label>
						</div>

						<div class="cols has-legend" role="group">
							<p class="legend">Layout options</p>
							<label for="tc__layout" class="col_one">Tree type</label>
							<select name="tc__layout" id="tc__layout" class="col-two">
								<option value="radial">Radial</option>
								<option value="dendrogram">Dendrogram</option>
							</select>

							<button type="button" id="tc__fit">Zoom to fit</button>
						</div>

						<div class="cols has-legend tc__treeType" role="group" id="tc__radial">
							<p class="legend">Radial tree options</p>
							<label for="tc__radial__rotation" class="col-one">Rotation</label>
							<div class="col-two">
								<input type="range" name="tc__radial__rotation" id="tc__radial__rotation" min="0" max="360" value="0" disabled /><output id="tc__radial__rotation--out"-></output>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>

		<h3>References</h3>
		<ol class="references">
			<li id="ref1">Fast, scalable generation of high-quality protein multiple sequence alignments using Clustal Omega. (2011). Sievers F, Wilm A, Dineen DG, Gibson TJ, Karplus K, Li W, Lopez R, McWilliam H, Remmert M, Söding J, Thompson JD, Higgins D. <em>Molecular Systems Biology</em>, 7(539). <a href="http://dx.doi.org/10.1038/msb.2011.75">doi:10.1038/msb.2011.75</a></li>
			<li id="ref2">Analysis Tool Web Services from the EMBL-EBI. (2013). McWilliam H, Li W, Uludag M, Squizzato S, Park YM, Buso N, Cowley AP, Lopez R. <em>Nucleic Acids Research</em> 41(Web server issue):W597&ndash;600. <a href="http://dx.doi.org/10.1093/nar/gkt376">doi:10.1093/nar/gkt376</a></li>
		</ol>

	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/colorbrewer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/d3-tip.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/newick.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/phyalign.min.js"></script>
</body>
</html>