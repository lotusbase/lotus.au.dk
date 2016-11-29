<?php
	require_once('../config.php');

	// Initiate time count
	$start_time = microtime(true);

	// Search status
	$searched = false;

	// Initialize error variables
	$error_message = '';
	$error_flag = false;

	// Get user input
	$input = $_GET;

	if($_GET && !empty($_GET['v'])) {

		// Fetch all search parameters. Declare empty if not found, and clean them
		if(!isset($_GET['blast'])) {	$blast = '';		} else { 	$blast =	trim($_GET['blast']);	}	// Sanitized later
		if(!isset($_GET['pid'])) {		$pid = '';			} else {	$pid =		$_GET['pid'];		}
		if(!isset($_GET['pos1'])) {		$pos1 = '';			} else {	$pos1 =		$_GET['pos1'];	}
		if(!isset($_GET['pos2'])) {		$pos2 = '';			} else {	$pos2 =		$_GET['pos2'];	}	
		if(!isset($_GET['chr'])) {		$chr = '';			} else {	$chr =		$_GET['chr'];		}
		if(!isset($_GET['gene'])) { 	$gene = '';			} else {	$gene =		$_GET['gene'];	}
		if(!isset($_GET['anno'])) { 	$anno = '';			} else {	$anno =		$_GET['anno'];	}
		if(!isset($_GET['ord'])) { 		$ord = 'Position'; 	} else { 	$ord =		$_GET['ord'];		}
		if(!isset($_GET['n'])) {		$num = '100';		} else {	$num =		$_GET['n'];		}			// Default to 100 rows per page
		if(!isset($_GET['p'])) {		$page = '1';		} else {	$page =		$_GET['p'];				}	// Redirect to page 1
		if(!isset($_GET['v'])) {		$version = '';		} else {	$version =	$_GET['v'];			}

		// Convert to integers
		$page = intval($page);
		$num = intval($num);
		$version = strval($version);

		// Parse plant IDs of alternative format
		$pid = preg_replace('/^DK\d{2}\-0(3\d{7})$/', '$1', $pid);

		// Strip transcript variants from gene name
		$gene = preg_replace('/^(Lj.*)\.\d+$/', '$1', $gene);

		// Check if database version is specified
		if(empty($version) || !in_array($version, $lj_genome_versions)) {
			$ea = array(
				"title" => "Database version not specified",
				"message" => "You have not specified a <em>LORE1</em> database version to search against."
			);
			$error_flag = true;
			
		}

		// Coerce order values
		if(!in_array($ord, array('PlantID','Position','Chromosome'))) {
			$ord = 'PlantID';
		}

		// Force user to either fill up:
		// (1) The BLAST header OR
		// (2) Plant ID, Chromosome, Position, GeneID OR Annotation
		$params = array($blast, $pid, $pos1, $pos2, $chr, $gene, $anno);
		if(count(array_filter($params)) === 0) {
			$ea = array(
				"title" => "No search parameters provided",
				"message" => "You have not provided any search parameter, resulting in a overly broad search scope. Please provide either the BLAST header(s), or plant ID, chromosome, position, gene or annotation (or a combination thereof)."
			);
			$error_flag = true;
		}

		// Force minimum length of annotation
		if(!empty($anno)) {
			if(strlen($anno) < 15) {
				$ea = array(
					"title" => "Annotation length is too short",
					"message" => "The keyword you have netered for your annotation is too short &mdash; there is a risk of a very long query time, which may prevent other users from accessing the database."
				);
				$error_flag = true;
			}
		}

		// Determine search type:
		// 1: BLAST headers
		// 2: Parameter-based
		// 3: MySQL command
		if(!empty($blast)) {
			$search_type = 1;
		} else {
			$search_type = 2;
		}

		if(!$error_flag) {

			// Construct general MySQL query based on seach type
			$dbq = "FROM lore1ins AS lore LEFT JOIN geneins AS gene ON (lore.Chromosome = gene.Chromosome AND lore.Position = gene.Position AND lore.Orientation = gene.Orientation AND lore.version = gene.Version) LEFT JOIN exonins AS exon ON (lore.Chromosome = exon.Chromosome AND lore.Position = exon.Position AND lore.Orientation = exon.Orientation AND lore.Version = exon.Version) LEFT JOIN annotations AS anno ON (exon.Gene = anno.Gene AND exon.Version = anno.Version) LEFT JOIN lore1seeds AS seeds ON lore.PlantID = seeds.PlantID";
			if($search_type == 1 || $search_type == 2) {
				// Only allow normal users to search for lines with seed stock available
				$dbq .= " WHERE lore.Version = ? AND seeds.SeedStock = 1";
			}

			// Construct placeholder array
			$placeholder_array = array($version);

			// Perform specific validation and advanced query construction based on search types
			if($search_type == 1) {

				// Format input such that each value is separated by ", "
				$blast_pattern = array(
					'/ *[\r\n]+/',		// Checks for one or more line breaks
					'/(\w)\s+(\w)/',	// Checks for words separated by one or more spaces
					'/,\s*/',			// Checks for words separated by comma, but with variable spaces
					'/,\s*,/'			// Checks for empty strings (i.e. no words between two commas)
					);
				$blast_replace = array(
					',',
					'$1, $2',
					',',
					','
					);
				$blast_rep = (preg_replace($blast_pattern, $blast_replace, $blast));

				// Explode into array
				$blast_arr = array_filter(explode(",", $blast_rep));

				// Check the number of BLAST headers
				$dbq .= " AND (";

				// Evaluate individual BLAST headers
				$blast_error = array();
				foreach($blast_arr as &$blast_item) {
					$blast_item = explode("_", $blast_item);
					$blast_count = count($blast_item);
					if($blast_count == 3) {
						// If the user enters a mapped insert e.g. chr5_3085263_R
						$dbq .= "(lore.Chromosome = ? AND lore.Position = ? AND lore.Orientation = ?) OR ";
						$placeholder_array = array_merge($placeholder_array, $blast_item);
					} elseif($blast_count == 4) {
						// If the user enters an unmapped insert, e.g. LjSGA_055002_657_R
						$dbq .= "(lore.Chromosome = ? AND lore.Position = ? AND lore.Orientation = ?) OR ";
						$placeholder_array = array_merge($placeholder_array, $blast_item);
					} else {
						$blast_error[] = implode("_", $blast_item);
					}
				}
				if(count($blast_error) > 0) {
					$ea = array(
						'title' => 'Invalid BLAST header',
						'message' => 'I\'m sorry, but the following BLAST '.pl(count($blast_error), 'header', 'headers').' are of an invalid format. '.pl(count($blast_error), 'It', 'They').' should be in the format of <code>[chromosome]_[position]_[orientation]</code>, i.e. <code>chr5_3085263_R</code>.',
						'list' => $blast_error
					);
					$error_flag = true;
					
				}

				// Remove the last ' OR ' from the concatenated query
				$dbq = substr($dbq, 0 ,-4).")";
				$ord = 'Chromosome';

			} elseif($search_type == 2) {
				// Search type: General search
				// "\n\t" are for formatting purpose when displaying the query later
				if($pos1 > $pos2) {
					if(empty($pos2)) {
						$dbq .= " AND (lore.Position = ?)";
						$placeholder_array[] = $pos1;
					} else {
						$lower = $pos2;
						$upper = $pos1;
						$dbq .= " AND (lore.Position BETWEEN ? AND ?)";
						$placeholder_array[] = $pos2;
						$placeholder_array[] = $pos1;
					}
					$ord = "Position";
				} elseif($pos1 < $pos2) {
					if(empty($pos1)) {
						$dbq .= " AND (lore.Position = ?)";
						$placeholder_array[] = $pos1;
					} else {
						$lower = $pos1;
						$upper = $pos2;
						$dbq .= " AND (lore.Position BETWEEN ? AND ?)";
						$placeholder_array[] = $pos1;
						$placeholder_array[] = $pos2;
					}
					$ord = "Position";
				}

				if($chr!=='') {
					$dbq .= " AND lore.Chromosome = ?";
					$placeholder_array[] = $chr;
				}
				if($gene!=='') {
					// Determine if it's gene or gene model being entered
					if(stripos($gene, ".") !== false) {
						$dbq .= " AND gene.Gene = ?";
						$placeholder_array[] = preg_replace('/^(Lj\dg(\d)+)\.(\d)+$/', '$1', $gene);
					} else {
						$dbq .= " AND gene.Gene = ?";
						$placeholder_array[] = $gene;
					}
				}
				if($pid!=='') {
					// Format input such that each value is separated by ", "
					$pid_pattern = array(
						'/ *[\r\n]+/',		// Checks for one or more line breaks
						'/(\w)\s+(\w)/',	// Checks for words separated by one or more spaces
						'/,\s*/',			// Checks for words separated by comma, but with variable spaces
						'/,\s*,/'			// Checks for empty strings (i.e. no words between two commas)
						);
					$pid_replace = array(
						',',
						'$1, $2',
						',',
						','
						);
					$pid_rep = (preg_replace($pid_pattern, $pid_replace, $pid));

					// Explode into array
					$pid_arr = array_filter(explode(",", $pid_rep));
					$pid_arr_placeholder = str_repeat('?,', count($pid_arr)-1).'?';

					// Evaluate individual plant IDs
					$dbq .= " AND lore.PlantID IN ($pid_arr_placeholder)";
					$placeholder_array = array_merge($placeholder_array, $pid_arr);

				}
				if($anno!=='') {
					$dbq .= " AND MATCH(anno.Annotation) AGAINST(?)";
					$placeholder_array[] = $anno;
				}
			}

			// Now we are ready to rock and roll
			try {
				$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			} catch(PDOException $e) {
				$ea = array(
					'title' => 'Error establishing database connection',
					'message' => 'We have encountered an issue when attempting to connect to the database. Should this issue persist, please contact the system administrator.'
				);
				$error_flag = true;
				
			}

			// Perform pre-query
			$q1 = $db->prepare("SELECT lore.IDKey AS IDKey, HEX(lore.Salt) AS Salt ".$dbq." GROUP BY lore.IDKey");
			$q1->execute($placeholder_array);

			// Fetch keys
			$ak = array();
			while($r1 = $q1->fetch(PDO::FETCH_ASSOC)) {
				$ak[] = $r1['Salt'];
			}
			$ak = serialize($ak);

			// Quit if no rows are returned
			$rows = $q1->rowCount();
			if($rows <= 0) {
				$ea = array(
					'title' => 'No <em>LORE1</em> lines found',
					'message' => 'Your search criteria has returned no matching <em>LORE1</em> lines. Please refine/modify your search criteria.'
				);
				$error_flag = true;
			}

			// Get pagination variables
			$last = intval(ceil($rows/$num));
			if($page <= 1) {
				$page = 1;
			} elseif($page > $last) {
				$page = $last;
			}

			// Perform actual query
			$q2 = $db->prepare("SELECT lore.Batch AS Batch,
				lore.PlantID As PlantID,
				lore.Chromosome AS Chromosome,
				lore.Position AS Position,
				lore.Orientation AS Orientation,
				lore.FwPrimer AS FwPrimer,
				lore.RevPrimer AS RevPrimer,
				lore.PCRInsPos AS PCRInsPos,
				lore.PCRWT AS PCRWT,
				lore.IDKey AS IDKey,
				HEX(lore.Salt) AS Salt,

				GROUP_CONCAT(DISTINCT gene.Gene ORDER BY gene.Gene) AS Gene,
				GROUP_CONCAT(DISTINCT exon.Gene ORDER BY exon.Gene) AS Exon,
				CASE
					WHEN GROUP_CONCAT(DISTINCT gene.Gene ORDER BY gene.Gene) IS NOT NULL AND GROUP_CONCAT(DISTINCT exon.Gene ORDER BY exon.Gene) IS NULL THEN 'intronic'
					WHEN GROUP_CONCAT(DISTINCT exon.Gene ORDER BY exon.Gene) IS NOT NULL THEN 'exonic'
					WHEN GROUP_CONCAT(DISTINCT gene.Gene ORDER BY gene.Gene) IS NULL AND GROUP_CONCAT(DISTINCT exon.Gene ORDER BY exon.Gene) IS NULL THEN 'intergenic'
					ELSE 'Other'
				END AS Type
				".$dbq."
				GROUP BY lore.IDKey
				ORDER BY `$ord`
				LIMIT ".($page-1)*$num.", ".$num);
			$q2->execute($placeholder_array);

			// Set search states
			$searched = ($error_flag ? false : true);

		}
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>LORE1 Search &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Search for LORE1 mutants of interest.'
			));
		echo $document_header->get_document_header();
	?>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
</head>
<body class="lore1-search <?php echo ($searched ? 'results' : '');?>" data-line-search="<?php echo ($searched && !empty($pid) ? 'true' : 'false');?>">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<div class="align-center">
			'.($searched ? '<h1><em>LORE1</em> line search results</h1><span class="byline">Using <em>L. japonicus</em> reference genome <strong>v'.$version.'</strong></span>' : '<h1><em>LORE1</em> line search</h1><span class="byline">Search for <em>LORE1</em> lines of interest and their accompanying metadata,<br />from a collection of 108,133 orderable lines.</span>').'
		</div>');
		$header->set_header_background_image(WEB_ROOT.'/dist/images/header/lore1/lore1_01.jpg');
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_titles(array('<em>LORE1</em>', 'Search'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper search">
		<?php
			$input = $_GET;
			if($error_flag) {
		?>
		<div class="user-message warning">
			<h3><span class="pictogram icon-attention"></span><?php echo $ea['title']; ?></h3>
			<p><?php echo $ea['message']; ?></p>
			<?php
				if(isset($ea['list'])) {
					echo '<ul>';
					foreach($ea['list'] as $item) {
						echo '<li><code>'.$item.'</code></li>';
					}
					echo '</ul>';
				}
			?>
		</div>
		<?php } ?>

		<?php
			if(isset($_SESSION['download_error']) && $_SESSION['download_error'] == true) {
				echo "<p class='user-message warning'>".$_SESSION['download_error']."</p>";
				unset($_SESSION['download_error']);
			}
		?>

		<?php if($searched) { ?>
		<p>We have found <?php echo "<strong>".$rows."</strong> ".pl($rows,'result','results'); ?>. Now displaying <?php echo $page." of ".$last." with ".$num." ".pl($num,'row','rows');?> per page. This search has taken <strong><?php echo number_format(microtime(true) - $start_time, 3); ?>s</strong> to perform.</p>
		<?php }

			// Display searchform
			if($searched) echo '<div class="search-again hide-first toggle"><h3><a href="#" title="View Options">Search again</a></h3>';
			include('searchform.php');
			if($searched) echo '</div>';

			// Do results
			if($searched) {
		?>

		<form action="search-download.php" method="post" id="dl" class="has-group">
			<div class="download toggle">
				<h3><a href="#" class="open" data-toggled="on" title="Download Options">Download options</a></h3>
				<div class="cols justify-content__flex-start">
					<p class="full-width"><strong>Due to space constraints, not all columns are displayed in the table below.</strong> To view the full table, you can download the entire search result using the form below.</p>
					<label for="filetype" class="col-one">File format: <a class="info" data-modal="search-help" title="What kind of file formats do you support for export?" data-modal-content="We support two different file formats: &lt;ul&gt;&lt;li&gt;&lt;code&gt;.csv&lt;/code&gt; file formats have comma-delimited columns, and rows are separated by line breaks.&lt;/li&gt;&lt;li&gt;&lt;code&gt;.tsv&lt;/code&gt; file formats have tab-delimited columns, and rows are separated by line breaks.&lt;/li&gt;&lt;/ul&gt;">?</a></label>
					<select name="t" id="filetype">
						<option value="csv" selected>CSV file (.csv)</option>
						<option value="tsv">TSV file (.tsv)</option>
					</select>
					<button type="button" id="dlc">Download Selected Rows (<span>0</span>)</button>
					<button type="button" id="dla">Download Entire Search (<span><?php echo $rows; ?></span>)</button>
					<input type="hidden" value="" name="d" id="dlt" />
					<input type="hidden" value='<?php echo $ak; ?>' name="ak" />
					<input type="hidden" value="<?php echo $version; ?>" name="v" />
					<input type="hidden" value="<?php echo $_SERVER['REQUEST_URI']; ?>" name="origin" />
				</div>				
			</div>

			<?php if(!empty($pid) && count($pid_arr) === 1) { ?>
			<div class="ins-overview toggle">
				<h3><a href="#" class="open" data-toggled="on" title="Overview for the line <?php echo $pid; ?>">Overview for the line <?php echo $pid; ?></a></h3>
				<div>
					<p>The <em>LORE1</em> insertional mutant line <strong><?php echo $pid; ?></strong> contains a total of <?php echo "<strong>".$rows."</strong> ".pl($rows,'insertion'); ?>, summarized as follow:</p>
					<ul class="cols flex-wrap__nowrap">
						<li data-instype="exonic"><span class="count"></span><span class="ins-type">Exonic</span></li>
						<li data-instype="intronic"><span class="count"></span><span class="ins-type">Intronic</span></li>
						<li data-instype="intergenic"><span class="count"></span><span class="ins-type">Intergenic</span></li>
						<li><span class="count"><?php echo $rows; ?></span><span class="ins-type">Total</span></li>
					</ul>
				</div>
			</div>
			<?php } ?>

			<?php
				// Echo warning if multiple plants are returned per BLAST header
				if(!empty($blast) && count($blast_arr) != $rows) {
					echo '<p class="warning user-message">We have detected that there are multiple lines associated with one or more of your BLAST headers. As it is almost statistically impossible to have &gt;1 <em>LORE1</em> insertions in the exact same coordinate and orientation, we recommend that you download your search results and <a href="/meta/faq#non-unique-blast-header" title="">follow the instructions here</a>.</p>';
				}
				// Print navigation if there is more than one page of search result
				// Construct query string
				$query_string = [];
				foreach($_GET as $key => $value) {
					$query_string[$key] = $value;
				}

				$paginate = new \LotusBase\Component\Paginate();
				$paginate->set_current_page($page);
				$paginate->set_last_page($last);
				$paginate->set_rows_per_page($num);
				$paginate->set_query_string($query_string);
				$pagination = $paginate->get_pagination();

				if($last > 1) {
					echo $pagination;
				}

			?>

			<table id="rows" class="table--dense table--no-borders" data-sticky>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<thead>
					<tr>
						<th scope="col" class="chk"><input type="checkbox" class="ca" /></td>
						<td scope="col"><a data-modal title="Batch" data-modal-content="The batch name of the &lt;em&gt;LORE1&lt;/em&gt; plant line." class="pictogram icon-database"></a></td>
						<th scope="col" data-type="numeric">Plant ID</td>
						<th scope="col"><a data-modal title="What is a BLAST header?" data-modal-content="BLAST header is derived from the concatenation of chromosome, position and orientation of the insert.">BLAST Header</a></td>
						<th scope="col">Gene</td>
						<th scope="col"><a title="Exonic insertions with annotations" data-modal data-modal-content="When this field is not empty, it indicates that the &lt;em&gt;LORE1&lt;/em&gt; insertion occurs in the exonic region of a gene. Since a single gene may have several variants, exon names are appended with a period and a unique ID: &lt;code&gt;.[n]&lt;/code&gt;. Annotations may not be available for some exons.">Exonic + Anno</a></td>
						<th scope="col"><a title="Type of insertion" data-modal data-modal-content="Inferred from the previous two columns, this column indicates whether the &lt;em&gt;LORE1&lt;/em&gt; insertion occurs in an intron, an exon, or in the intergenic region.">Ins<br />Type</a></td>
						<th scope="col" class="naseq"><a title="Forward Primer" data-modal data-modal-content="The sequence of the forward primer, designed by Primer3 &lt;a href=&quot;/meta/faq#lore1-primer-design&quot; title=&quot;LORE1 genotyping primer design&quot;&gt;using a set of pre-established parameters&lt;/a&gt;, that is used for genotyping purposes. This primer can either be couplde with the designed reverse primer, or the general &lt;em&gt;LORE1&lt;/em&gt; reverse primer.">Fw<br />Primer</a></td>
						<th scope="col" class="naseq"><a title="Reverse Primer" data-modal data-modal-content="The sequence of the reverse primer, designed by Primer3 &lt;a href=&quot;/meta/faq#lore1-primer-design&quot; title=&quot;LORE1 genotyping primer design&quot;&gt;using a set of pre-established parameters&lt;/a&gt;, that is used for genotyping purposes.">Rev<br />Primer</a></td>
						<!--<td scope="col" data-type="numeric"><a title="PCR product size after insertion">Ins Size</a></td>-->
						<!--<td scope="col" data-type="numeric"><a title="PCR product size in wild-type">WT Size</a></td>-->
						<th scope="col"><a title="&#177;1000bp insertion flanking sequence" data-modal data-modal-content="Contains the &#177;1000bp base pairs surrounding the insertion site. The insertion site has an insertion position of 1000 in this sequence.">Flanking seq.</a></td>
					</tr>
				</thead>
				<tbody>
					<?php
						while($results = $q2->fetch(PDO::FETCH_ASSOC)) {
					?>
					<tr>
						<td class="chk"><input type="checkbox" name="k[]" value="<?php echo $results['Salt']; ?>"></td>
						<td class="bch"><?php echo $results['Batch']; ?></td>
						<td class="pid" data-type="numeric">
							<?php
								if(!empty($pid)) {
									echo $results['PlantID'];
								} else {
									echo '<div class="dropdown button"><span class="dropdown--title">'.$results['PlantID'].'</span><ul class="dropdown--list"><li><a href="'.$_SERVER['PHP_SELF'].'?v='.$version.'&pid='.$results['PlantID'].'" title="Search database for this LORE1 line: '.$results['PlantID'].'"><span class="pictogram icon-search">Get all entries for this line</span></a></li></ul></div>';							
								}
							?>
						</td>
						<td class="blh">
							<?php
								$blast_header = $results['Chromosome']."_".$results['Position']."_".$results['Orientation'];
								if(version_compare($version, '3.0') >= 0) {
									echo '<div class="dropdown button"><span class="dropdown--title">'.$blast_header.'</span><ul class="dropdown--list"><li><a href="/genome/?loc='.$results['Chromosome'].'%3A'.($results['Position']-5000).'..'.($results['Position']+5000).'" title="View in genome browser"><span class="pictogram icon-map">View in genome browser</span></a></li></ul></div>';
								} else {
									echo $blast_header;
								}

							?>
						</td>
						<td class="gene">
							<?php
								if(!empty($results['Gene'])) {
									// If insertion occurs in a gene
									$genes = explode(",", $results['Gene']);
									foreach($genes as $gene) {
										echo '<div class="dropdown button"><span class="dropdown--title"><a href="'.WEB_ROOT.'/view/gene/'.$gene.'" title="View gene details for '.$gene.'">'.$gene.'</a></span><ul class="dropdown--list">';

										echo '<li><a href="'.$_SERVER['PHP_SELF'].'?v='.$version.'&gene='.$gene.'" title="Search database for this gene: '.$gene.'"><span class="pictogram icon-search"><em>LORE1</em> search</span></a></li>';

										if(version_compare($version, '3.0') >= 0) {
											echo '<li><a href="'.WEB_ROOT.'/view/gene/'.$gene.'" title="View gene details for '.$gene.'"><span class="icon-eye">View gene info</span></a></li>';
											echo '<li><a href="/tools/trex.php?ids='.$gene.'&v='.$version.'" title="Get advanced transcript information for this gene: '.$gene.'"><span class="pictogram icon-search">Send to Transcript Explorer</span></a></li>';
											echo '<li><a href="/expat/?ids='.$gene.'&v='.$version.'&t=6&dataset=ljgea-geneid" title="Access expression data from the Lotus japonicus expression atlas tool"><span class="pictogram icon-map">Send to Expression Atlas</span></a></li>';
											echo '<li><a href="/genome/?loc='.$gene.'" title="View in genome browser"><span class="pictogram icon-map">View in genome browser</span></a></li>';
										}

										echo '</ul></div>';
									}													
								} else {
									// If insertion is not in a gene
									echo "<span class='na'>&ndash;</span>";
								}
							?>
						</td>
							<td class="exon">
								<?php
									if(!empty($results['Exon'])) {
										// Explode
										$exons = explode(",", $results['Exon']);

										foreach($exons as $exon) {
											echo '<div class="dropdown button"><span class="dropdown--title"><a href="'.WEB_ROOT.'/view/transcript/'.$exon.'" title="View transcript details for '.$exon.'">'.$exon.'</a></span><ul class="dropdown--list">';

											echo '<li><a data-gene="'.$exon.'" data-version="'.$version.'" class="api-gene-annotation" title="Fetching gene annotation&hellip;"><span class="pictogram icon-cog">Fetching gene annotation&hellip;</a></span></li>';

											if(version_compare($version, '3.0') >= 0) {
												echo '<li><a href="'.WEB_ROOT.'/view/transcript/'.$exon.'" title="View transcript details for '.$exon.'"><span class="icon-eye">View transcript info</span></a></li>';
											}

											echo '</ul></div>';
										}
									} else {
										echo "<span class='na'>&ndash;</span>";

									}
								?>
							</td>
							<td class="instype" data-instype="<?php echo $results['Type']; ?>"><?php echo $results['Type']; ?></td>
							<td class="naseq"><?php echo $results['FwPrimer']; ?></td>
							<td class="naseq"><?php echo $results['RevPrimer']; ?></td>
							<!--<td class="pcr" data-type="numeric"><?php echo $results['PCRInsPos']; ?></td>-->
							<!--<td class="pcr" data-type="numeric"><?php echo $results['PCRWT']; ?></td>-->
							<td class="flk">
								<a class="api-insertion-flank button" data-key="<?php echo $results['Salt']; ?>" data-version="<?php echo $version; ?>"><span class="pictogram icon-reply"></span>View</a>
							</td>
					</tr>
					<?php
						}
					?>
				</tbody>
			</table>
			<?php
				// Only display pagination if on last page and have enough rows
				if($last > 1 && $q2->rowCount() > 5) {
					echo $pagination;
				}
			?>
		</form>

		<?php } ?>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/lore1.min.js"></script>
</body>
</html>