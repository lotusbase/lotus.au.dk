<?php
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>FAQ &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
</head>
<body class="faq">
	<?php
		$header = new \LotusBase\PageHeader();
		$header->set_header_content('
			<h1>Documentation</h1>
			<p>We have compiled a list of questions, categorized under different topics, that we hope will help clear any doubts you have. If you are unable to find the answer to your question, feel free to <a href="'.WEB_ROOT.'/meta/contact">contact us</a>.</p>
			<h2>Select a topic</h2>
			<ul class="tabs">
				<li id="tabs-lore1"><a '.((isset($_GET) && !empty($_GET['q']) && $_GET['q'] === 'lore1') ? 'class="current"' : '').' href="#" data-header="LORE1 resource" data-keyword="lore1"><span class="pictogram icon-leaf">LORE1</span></a></li>
				<li id="tabs-jbrowse"><a '.((isset($_GET) && !empty($_GET['q']) && $_GET['q'] === 'jbrowse') ? 'class="current"' : '').' href="#" data-header="<em>Lotus</em> genome browser" data-keyword="jbrowse"><span class="pictogram icon-book">JBrowse</span></a></li>
				<li id="tabs-blast"><a '.((isset($_GET) && !empty($_GET['q']) && $_GET['q'] === 'blast') ? 'class="current"' : '').' href="#" data-header="<em>Lotus</em> BLAST" data-keyword="blast"><span class="pictogram icon-search">BLAST</span></a></li>
				<li id="tabs-tools"><a '.((isset($_GET) && !empty($_GET['q']) && $_GET['q'] === 'tools') ? 'class="current"' : '').' href="#" data-header="other tools" data-keyword="tools"><span class="pictogram icon-wrench">Other Tools</span></a></li>
			</ul>
			<p>&hellip;alternatively, you can filter based on a keyword:</p>
			<form id="faq-form" class="search-form">
				<input type="search" id="filter" name="q" value="'.((isset($_GET) && !empty($_GET['q'])) ? $_GET['q'] : '').'" placeholder="Start typing to initiate keyword search" autocomplete="off" />
				<button type="submit"><span class="pictogram icon-search">Filter</span></button>
			</form>
		');
		echo $header->get_header();
	?>

	<?php echo get_breadcrumbs(array('custom_titles' => array('Info', 'Frequently Asked Questions'))); ?>

	<section id="faq-wrapper" class="wrapper">
		<p id="faq__user-message" class="user-message"></p>
		<div class="faq-action">
			<a class="button" id="showall" role="secondary">Expand all</a> <a class="button" id="hideall" role="secondary">Collapse all</a>
		</div>
		<div id="faq-content">
			<ul>
				<li data-tag="help information db tips blast">
					<h4>Availability of databases through BLAST</h4>
					<p>If you click on the question mark beside the database option, a database overview will be available through a modal window. Should you like to request additional databases to be added to BLAST, please <a href="<?php echo WEB_ROOT; ?>issues/new">open an enhancement request or a proposal</a> at the <em>Lotus</em> Base repository.</p>
				</li>

				<li data-tag="adjustment ncbiblast">
					<h4>Querying for short sequences</h4>
					<p>An option is available to adjust the algorithm if you are querying based on short sequences, and it can be found directly under the textarea where you enter the search query. Should you check this option (it is unchecked by default), the following changes to the algorithm will be made:</p>
					<ul>
						<li>Word size: 7</li>
						<li>Filter: Off</li>
						<li>Expect value: 1000</li>
					</ul>
					<p>More information regarding this option is <a href="http://www.ncbi.nlm.nih.gov/blast/Why.shtml">available here</a>.</p>
				</li>

				<li data-tag="blast">
					<h4>Extracting sequence identifiers from BLAST search results</h4>
					<p>Some users would like to keep a list of sequence identifiers from the BLAST result. If so desired, copy the block of text from the BLAST output and use <a href="<?php echo WEB_ROOT; ?>/tools/seqpro" title="Sequence Processor (SeqPro)">SeqPro</a> to isolate sequence identifiers from the copied text.</p>
				</li>

				<li data-tag="blast">
					<h4>Extracting nucleotide or amino acid sequences from BLAST databases</h4>
					<p>If you have the accession numbers (i.e sequence identifiers) of the sequences you want, you can use <a href="<?php echo WEB_ROOT; ?>/blast?p=seqret" title="Sequence Retrieval Tool (SeqRet)">SeqRet</a>.</p>
				</li>

				<li data-tag="jbrowse">
					<h4>What is JBrowse?</h4>
					<p>JBrowse is a fast, modern genome browser. Unlike conventional genome browser, JBrowse is solely accessible through a web interface. Users are not required to load data files into the browser&mdash;instead, files are remotely loaded from a server. If you want to add additional tracks to JBrowse, you can either upload it yourself (which will only be visible by you), or <a href="<?php echo WEB_ROOT; ?>issues/new">send in a request</a> (which will make the database locally, or externally, available (depending on your affiliation with the group).</p>
				</li>

				<li data-tag="jbrowse">
					<h4>How can I access JBrowse?</h4>
					<p>JBrowse can be accessed via any web browser such as Chrome and Mozilla on the following two address: <a href="http://10.14.65.61/lotus0.1/">http://10.14.65.61/lotus0.1/</a> or <a href="http://zombie.bioxray.au.dk/lotus0.1/">http://zombie.bioxray.au.dk/lotus0.1/</a>. Do note that the site is only accessible internally (i.e. you have to be connected to the department's VPN if you are working remotely).</p>
				</li>

				<li data-tag="jbrowse">
					<h4>What are the different tracks in JBrowse?</h4>
					<p>JBrowse consists of a set of tracks representing various forms of genomic location-based information. More information on individual track can be found by clicking on "Select tracks" and then by looking up information stored in the columns such as category, factor and name.</p>
				</li>

				<li data-tag="jbrowse">
					<h4>What are the various colors in JBrowser tracks?</h4>
					<p>Gene structure such as exons, <abbr title="untranslated regions">UTRs</abbr> and <abbr title="coding sequences">CDSs</abbr> are colored differently.</p>
				</li>

				<li data-tag="jbrowse">
					<h4>How can I retreive DNA/protein sequences of a gene models?</h4>
					<p>Users can click on the gene model and copy the region sequences.</p>
				</li>

				<li data-tag="jbrowse">
					<h4>How can  I get the flanking (intergenic) region around the genes?</h4>
					<p>Users can copy the co-ordinates of the region and use the sequence retrieval tool (<a href="<?php echo WEB_ROOT; ?>/tools/seqret.php" title="SeqRet">SeqRet</a>) to do so.</p>
				</li>

				<li data-tag="jbrowse">
					<h4>How can I share my results?</h4>
					<p>Users can click on the top-right option "share" and use the link shown.</p>
				</li>

				<li data-tag="jbrowse">
					<h4>How can I export a publication quality image?</h4>
					<p>You can not &mdash; however, you can capture screenshots off the genome browser, preferably in full screen mode.</p>
				</li>

				<li data-tag="jbrowse">
					<h4>How to navigate in JBrowse?</h4>
					<p>Functions such as zooming, dragging and clicking are fairly easy and straightforward. More details can be found by clicking on "Help" button on the top-right of the screen.</p>
				</li>

				<li data-tag="lore1">
					<h4>What is the LORE1 activity pattern?</h4>
					<p>LORE1 is de-repressed during tissue culture. However, new copies occur only in the following generations. The activity of LORE1 was pinpointed to the germline, with highest activity in the male gametophyte. So far no somatic insertions were observed (<a href="#ref1">Fukai, Umehara <em>et al.</em> 2010</a>).</p>
				</li>

				<li data-tag="lore1">
					<h4>Which generation do we get seeds from?</h4>
					<p>You will receive the R3 generation seeds (3<sup>rd</sup> generation of tissue culture regenerated plants). Analysis of insertion sites was done by a high-throughput method in the R2 generation.</p>
				</li>

				<li data-tag="lore1">
					<h4>Are the plants homozygous for the identified insertions?</h4>
					<p>R3 is a segregating population. We observe Mendelian segregation of insertions - 1:2:1. In case of recessive mutations the phenotype, if any, should be visible for 25% of the individuals.</p>
				</li>

				<li data-tag="lore1 genotype" id="lore1-primer-design">
					<h4>How can we design primers for LORE1 genotyping?</h4>
					<figure>
						<a href="<?php echo WEB_ROOT; ?>/dist/images/content/genotyping.png"  title="Designing genotyping primers for LORE1 lines" data-modal="wide" data-modal-content="&lt;img src=&quot;<?php echo WEB_ROOT; ?>/dist/images/content/genotyping.png&quot; title=&quot;Designing genotyping primers for LORE1 lines&quot; alt=&quot;Designing genotyping primers for LORE1 lines&quot; /&gt;"><img src="<?php echo WEB_ROOT; ?>/dist/images/content/genotyping.png" title="Designing genotyping primers for LORE1 lines" alt="Designing genotyping primers for LORE1 lines" /></a>
						<figcaption><span class="legend">Figure 1</span> Designing genotyping primers for LORE1 lines. The forward and reverse primers are designed based on the &#177;1000bp flanking region with predefined criteria &mdash; the former at least 100bp away and the latter at least 200bp away from the insertion site (position 1000). The P2 primer is 260bp away from the insertion site and is found in all LORE1 insertions, if present.</figaption>
					</figure>
					<p>The genotyping primers for LORE1 lines are generated using <a href="http://frodo.wi.mit.edu/" title="Primer 3">Primer3</a> with a set of predefined parameters. <strong>The PCR product <em>must</em> span the 1000<sup>th</sup> position</strong>, and the PCR product size should ideally fall between the range of 500-700 base pairs. As follows are the settings we have used for primer design:</p>
					<div class="table-overflow">
						<table>
							<thead><tr><th>Parameter</th><th>Minimum</th><th>Optimal</th><th>Maximum</th></tr></thead>
							<tbody>
								<tr><td>Primer size</td><td>18</td><td>24</td><td>27</td></tr>
								<tr><td>Primer T<sub>m</sub></td><td>65</td><td>68</td><td>71</td></tr>
								<tr><td>Primer GC%</td><td>20</td><td>50</td><td>80</td></tr>
						</table>
					</div>
					<p>Predesigned primers are available for most insertions.  We were able to identify at least 95% of the test insertions using abovementioned primers. If you are using predesigned primers, always use the forward and P2 primers for insertion detection. The &#177;1000bp flanking sequence is reverse complemented when the LORE1 insertion is in the reverse orientation.</p>
					<p>The primers can be <a href="<?php echo WEB_ROOT; ?>/tools/primers.php" title="Genotyping Primer Order Sheet">downloaded from our database</a> when you submit a list of BLAST headers.</p>
				</li>

				<li data-tag="lore1 genotype">
					<h4>How can we identify the hetero/homozygotes?</h4>
					<p>The PCR program used for LORE1 insertion line genotyping is known as "touchdown".</p>
					<div class="table-overflow">
						<table id="pcr">
							<caption><span class="legend">Table 1</span> PCR program used for LORE1 genotyping.</strong></caption>
							<thead>
								<tr>
									<th scope="col">Step</td>
									<th scope="col">Temperature</td>
									<th scope="col">Duration</td>
									<th scope="col">Repeat</td>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>1</td>
									<td>95&deg;C</td>
									<td>3min</td>
									<td>n.a.</td>
								</tr>
								<tr>
									<td>2</td>
									<td>95&deg;C</td>
									<td>30sec</td>
									<td rowspan="2">5x</td>
								</tr>
								<tr>
									<td>3</td>
									<td>72&deg;C</td>
									<td>1min 15sec</td>
								</tr>
								<tr>
									<td>4</td>
									<td>95&deg;C</td>
									<td>30sec</td>
									<td rowspan="3">10&times;</td>
								</tr>
								<tr>
									<td>5</td>
									<td>72&deg;C to 68&deg;C<br /><small>(-0.5&deg;C per round)</small></td>
									<td>30sec</td>
								</tr>
								<tr>
									<td>6</td>
									<td>72&deg;C</td>
									<td>45sec</td>
								</tr>
								<tr>
									<td>7</td>
									<td>95&deg;C</td>
									<td>30sec</td>
									<td rowspan="3">20&times;</td>
								</tr>
								<tr>
									<td>8</td>
									<td>68&deg;C</td>
									<td>30sec</td>
								</tr>
								<tr>
									<td>9</td>
									<td>72&deg;C</td>
									<td>45sec</td>
								</tr>
								<tr>
									<td>10</td>
									<td>72&deg;C</td>
									<td>10min</td>
									<td>n.a.</td>
								</tr>
							</tbody>
						</table>
					</div>
					<p>To interpret your PCR result, do take note of the following:</p>
					<ul>
						<li><strong>homozygous wild type</strong> plants will have gene segments amplified by the forward + reverse primers,</li>
						<li><strong>homozygous LORE1 insertional mutant</strong> plants will have gene segments amplified by the forward + P2 primers&mdash;the 5kb LORE1 insertion will cause the forward + reverse primers to fail to amplify,</li>
						<li><strong>heterozygous</strong> plants will have gene segments amplified by the forward and reverse primers, and by the forward + P2 primers.</li>
					</ul>
					<p>The PCR product sizes indicated in the LORE1 download data refers to the expected sizes of amplified gene fragments by the forward + reverse primers ('PCR Product Size in Wild Type') or the forward + P2 primers ('PCR Product Size with Insertion').</p>
					<p>Detailed instructions about genotyping by PCR can be found in our publication (<a href="#ref2">Urbanski <em>et al.</em>, 2011</a>).
				</li>

				<li data-tag="lore1 ">
					<h4>What is the mutagenic background of the lines?</h4>
					<p>LORE1 has a low frequency of insertions. The R1 generation acquired 3 insertions (two in unknown genes) that do not cause any phenotype. This generation (line G329-3) is used now as a founder line for the whole mutagenized population. The R2 generation has on average 2.9 new LORE1 insertions per plant. The R3 generation comes with approximately 1.9 additional new insertions per plant. Those last insertions will be different among the siblings that are shipped to you.</p>
				</li>

				<li data-tag="lore1 ">
					<h4>Can LORE1 be repressed again?</h4>
					<p>One case is known when LORE1 was repressed and is not accumulating during the generative propagation (<a href="#ref3">Madsen, Fukai <em>et al.</em> 2005</a>). We did not examine the frequency of new insertion accumulation in the R4 generation. </p>
				</li>

				<li data-tag="lore1 " id="non-unique-blast-header">
					<h4>How should I deal with non-unique LORE1 insertions?</h4>
					<p>Non-unique LORE1 insertions occur where multiple plant IDs have been mapped to the same insertional position in the genome. In other words, these plants share identical BLAST headers (in the format of <code>[Chromosome Number]_[Position]_[Orientation]</code>. Usually this is a result of contamination during material collection or genotyping. However, by downloading your LORE1 search results, it is possible to identify the line with the true insertion.</p>
					<p>The search results that you have downloaded can be opened in any common spreadsheet program (<a href="#example-search-result">Table 2a</a>). It contains more information than is displayed on the search results page itself, due to spatial limitations in the latter.</p>
					<div class="table-overflow">
						<table id="example-search-result" class="table--dense">
							<caption><span class="legend">Table 2a</span> An example of the downloaded search results from LORE1 search. Some columns have been removed due to spatial restrictions. Tabulated data are not true (trivial) and should not be used as reference.</caption>
							<thead>
								<tr>
									<th scope="col"><abbr title="Plant ID">PID</abbr></th>
									<th scope="col">Batch</th>
									<th scope="col"><abbr title="Chromosome">Chr</abbr></th>
									<th scope="col">Position</th>
									<th scope="col">Orientation</th>
									<th scope="col">...</th>
									<th scope="col"><abbr title="Column Coordinate">Col. Coord.</abbr></th>
									<th scope="col"><abbr title="Row Coordinate">Row Coord.</abbr></th>
									<th scope="col"><abbr title="Column Coordinate Details">Col. Coord. Details</abbr></th>
									<th scope="col"><abbr title="Row Coordinate Details">Row Coord. Details</abbr></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>30000001</td>
									<td>DK01</td>
									<td>chr1</td>
									<td>4599382</td>
									<td>F</td>
									<td>...</td>
									<td>C_12</td>
									<td>R_31</td>
									<td>C_12#R_31#R_47#R_51#R_80</td>
									<td>252#8#265#8#8</td>
								</tr>
								<tr>
									<td>30000012</td>
									<td>DK01</td>
									<td>chr1</td>
									<td>4599382</td>
									<td>F</td>
									<td>...</td>
									<td>C_12</td>
									<td>R_47</td>
									<td>C_12#R_31#R_47#R_51#R_80</td>
									<td>252#8#265#8#8</td>
								</tr>
								<tr>
									<td>30000123</td>
									<td>DK01</td>
									<td>chr1</td>
									<td>4599382</td>
									<td>F</td>
									<td>...</td>
									<td>C_12</td>
									<td>R_51</td>
									<td>C_12#R_31#R_47#R_51#R_80</td>
									<td>252#8#265#8#8</td>
								</tr>
								<tr>
									<td>30001234</td>
									<td>DK01</td>
									<td>chr1</td>
									<td>4599382</td>
									<td>F</td>
									<td>...</td>
									<td>C_12</td>
									<td>R_60</td>
									<td>C_12#R_31#R_47#R_51#R_80</td>
									<td>252#8#265#8#8</td>
								</tr>
							</tbody>
						</table>
					</div>
					<p>You can see that these lines have LORE1 insertions in the same chromosome, position and orientation. Despite being identical, each plant ID has a unique row coordinate. With that in mind, we look at the hash(#)-separated values in the last two columns, <strong>Col Coord Details</strong> and <strong>Row Coord Details</strong> (<a href="#hash-separated-coord-details">Table 2b</a>).</p>
					<div class="table-overflow">
						<table id="hash-separated-coord-details">
							<caption><span class="legend">Table 2b</span> Hash-separated value pairs in column <strong>Col Coord Details</strong> and <strong>Row Coord Details</strong></caption>
							<thead>
								<th>Value Pair</th>
								<th>Col Coord Details</th>
								<th>Row Coord Details</th>
							</thead>
							<tbody>
								<tr>
									<td>1</td>
									<td>C_12</td>
									<td>252</td>
								</tr>
								<tr>
									<td>2</td>
									<td>R_31</td>
									<td>8</td>
								</tr>
								<tr>
									<td>3</td>
									<td>R_47</td>
									<td>265</td>
								</tr>
								<tr>
									<td>4</td>
									<td>R_51</td>
									<td>8</td>
								</tr>
								<tr>
									<td>5</td>
									<td>R_80</td>
									<td>8</td>
								</tr>
							</tbody>
						</table>
					</div>
					<p>We can see that <strong>R_47</strong> has the highest count among rows, and therefore we are most confident that the line found at <strong>C_12 R_47</strong> has the highest chance of being the line with the true LORE1 insertion at the position <strong>chr1_4599382_F</strong>. This would be line <strong>30000012</strong> because it has the aforementioned column and row coordinate (<a href="#example-search-result">Table 2a</a>).</p>
				</li>

				<li data-tag="lore1 ">
					<h4>Are there any problems with the seed germination? </h4>
					<p>We have not observed any problem with the seed germination, unless a line with a mutation in a known housekeeping gene was examined. We conclude that, due to the  low mutational background, the germination and fertility has not deteriorated in the lines.</p>
					<p>There might be however, problems with fungal infection. Please examine the seeds carefully before germination and if possible do not germinate all of the seeds at once.</p>
					<p>We have recently established a new facility for growing the LORE1 lines. For the following batches of LORE1 lines we are expecting an increase in seed quality and yield.</p>
				</li>

				<li data-tag="lore1 ">
					<h4>Are the mutations stable?</h4>
					<p>Yes. LORE1 is a retrotransposon (RNA transposon) that amplifies in genome by a copy-and-paste mechanism. All the insertions will be present in the future generations unless removed by back-crossing.</p>
				</li>

				<li data-tag="lore1 ">
					<h4>Can we clean the mutational background by back-crossing?</h4>
					<p>Yes. Although LORE1 is active in the gametophyte, far less insertions are generated in the female gametophyte. It is than possible to use Gifu as a male partner for crossing with your line to remove accumulated insertions, and have a low chance to generating new ones.</p>
				</li>

				<li data-tag="lore1 ">
					<h4>What are the <code>chr0</code> and <code>chr7</code> on the insertion list?</h4>
					<p><code>chr0</code> corresponds to a pseudomolecule made out of contigs that were not assembled into chromosome 1 to 6 pseudomolecules of the Lotus genome release 2.5. This genome release is available on Kazusa institute servers. The sequence of the <code>chr0</code> as well as <code>.gff</code> files with the gene models for those contigs can be downloaded from the <a href="ftp://ftp.kazusa.or.jp/pub/lotus/lotus_r2.5/">Kazusa FTP server</a> (check pseudomolecules).</p>
					<p><code>chr7</code> is used for simplicity and describes Lotus chloroplast DNA in legacy versions. In newer versions (&ge;3.0), chloroplastic DNA are found in the <code>Ljchloro</code> chromosome.</p>
				</li>

				<li data-tag="lore1 ">
					<h4>Do we need to sign any material transfer agreement (MTA)?</h4>
					<p>No, so far there is no MTA.</p>
				</li>

				<li data-tag="lore1 ">
					<h4>Will there be more lines available?</h4>
					<p>Yes. We are planning to sequence more lines and share the results when they are available.</p>
				</li>

				<li data-tag="lore1 ">
					<h4>How can we examine the number of additional LORE1 insertions in our line?</h4>
					<p>The routine way is to use the Southern blotting. Sequence-specific amplification polymorphism (SSAP) is however, simpler and faster technique that will additionally allow cloning and sequencing different insertions.</p>
				</li>

				<li data-tag="lore1 ">
					<h4>How should the LORE1 resource be cited?</h4>
					<p>If you find the LORE1 lines useful for your research, we ask that you cite the two LORE1 manscripts published back-to-back in the Plant Journal: Urbanski <em>et al.</em>, 2011 and Fukai <em>et al.</em>, 2011.</p>
				</li>

				<li data-tag="tools">
					<h4>What kind of data is accepted in SeqPro?</h4>
					<p>The default behavior of SeqPro is that it will attempt to detect the type of data you have entered automatically without user intervention, and process it accordingly. So far the accepted types are:</p>
					<ul>
						<li><strong>Amino acid / Nucleotide sequence</strong> &mdash; Marks the input data as an amino acid or a nucleotide sequence. This will remove all numbers, spaces and line breaks in the string, giving you a clean and uninterrupted sequence.</li>
						<li><strong>BLAST output</strong> &mdash; Marks the input data as a NCBI BLAST output. This is useful when you want to extract assession numbers or other identification from a BLAST search result. By default, the algorithm removes all scores and E-values. However, if you want to retain them, you will be provided with an option after pasting your BLAST output.</li>
					</ul>
				</li>

				<li data-tag="tools">
					<h4>How does SeqPro work?</h4>
					<p>SeqPro is powered by a simple but powerful feature found in many programming languages known as <a href="http://en.wikipedia.org/wiki/Regular_expression" title="Regular Expression">regular expression</a> (RegEx). Basically, the tool attempts to identify useful bits of data in your input such that they are kept, and discards bits of data that are irrelevant. In the example of a BLAST output, the extra spaces between columns are removed.</p>
				</li>


				<li data-tag="tools">
					<h4>How does SeqRet work?</h4>
					<p>The Sequence Retrieval Tool (SeqRet) works by fetching bits of information (e.g. amino acid, nucleotide sequences) from a BLAST-formatted database based on a unique identifier you have provided. An example of an identifier in the LORE1 database would be the BLAST header in the format of <code>[chromosome number]_[coordinate]_[orientation]</code>, such as <code>chr3_25342606_F</code>.</p>
				</li>

				<li data-tag="tools">
					<h4>How are these tools useful to me?</h4>
					<p>SeqPro and SeqRet work hand-in-hand, and they are designed to provide a seamless workflow for users who are going through large amount of data. For example, when a user receives a BLAST output with multiple promising candidates, he can copy the BLAST output and extract the accession numbers from the output with SeqPro. After that is done, he may extract the nucleotide sequences of those accessions with the help of SeqRet.</p>
					<p>The output generated by SeqPro can be easily pasted into a spreadsheet program, but it is only available in the BLAST output filtering.</p>
					<p>It is noteworthy to mention that both SeqPro and SeqRet are able to process <strong>multiple lines of data</strong>, meaning no tedious copying and pasting between different applications.</p>
				</li>
			</ul>
		</div>	</section>

	<section class="wrapper ref">
		<h3>References</h3>
		<ol class="ref">
			<li id="ref1">Fukai, E., Y. Umehara, <em>et al.</em> (2010). Derepression of the Plant Chromovirus LORE1 Induces Germline Transposition in Regenerated Plants. <em>PLoS Genet</em>, 6(3): e1000868</li>
			<li id="ref4">Fukai E, Soyano T, <em>et al.</em>, (2011) Establishment of a Lotus japonicus gene tagging population using the exon-targeting endogenous retrotransposon LORE1. <em>Plant J</em>, 69(4): 720-30. <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014259">Available online</a>.</li>
			<li id="ref3">Madsen, L. H., E. Fukai, <em>et al.</em> (2005). LORE1, an active low-copy-number TY3-gypsy retrotransposon family in the model legume Lotus japonicus. <em>Plant J</em>, 44(3): 372-381.</li>
			<li id="ref2">Urbanski, D., F., Malolepszy, A., (2011) Genome-wide LORE1 retrotransposon mutagenesis and high-throughput insertion detection in Lotus japonicus. <em>Plant J</em>, 69(4): 731-41. <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014280">Available online</a>.</li>
		</ol>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/lunr.js/0.6.0/lunr.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/faq.min.js"></script>
</body>
</html>
