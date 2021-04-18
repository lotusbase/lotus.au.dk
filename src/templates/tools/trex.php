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

	// If it is a search query
	$searched = false;
	if($_GET) {
		// Perform search when $_GET variable is found

		// Declare variables to be executed
		$exec_vars = array();

		// What version are we filtering for?
		$lj_genome_versions_class = new \LotusBase\LjGenomeVersion();
		if(!isset($_GET['v']) || empty($_GET['v'])) {
			$version = $lj_genome_versions_class->get_genome_ids();
		} else {
			$version = (is_array($_GET['v']) ? $_GET['v'] : explode(',', escapeHTML($_GET['v'])));
		}

		$ecotypes = array();
		$versions = array();
		foreach($version as $v) {
			$version_pieces = explode('_', $v);
			$ecotypes[] = $version_pieces[0];
			$versions[] = $version_pieces[1];
		}
		$exec_vars = array_merge($exec_vars, $ecotypes, $versions);

		// If an ID is used in a search
		if (!count($versions)) {
			$error = array(
				'error' => true,
				'message' => 'No valid genome assembly version was provided. This could happen if you have used an outdated link, or when you have attempt to access a genome assembly that you do not have permission to.'
			);
		}
		else if(isset($_GET['ids']) && !empty($_GET['ids'])) {
			// Construct query
			if(is_array($_GET['ids'])) {
				$trx = $_GET['ids'];
				$vars = array_filter($trx);
			} else {
				$trx = escapeHTML($_GET['ids']);
				$trx_rep = preg_match_all('/([\.\w\+\-\:<>]+|[\"\'][\.\w\s\+\-\:<>]*[\"\'])+/' , $trx , $trx_matched);
				$vars = $trx_matched[0];
			}

			// Construct query, loop through all transcript IDs
			$dbq = "
				FROM annotations AS anno
				LEFT JOIN transcriptcoord AS tc ON (
					anno.Gene = tc.Transcript AND
					anno.Ecotype = tc.Ecotype AND
					anno.Version = tc.Version
				)
				LEFT JOIN domain_predictions AS dompred ON (
					anno.Gene = dompred.Transcript
				)
				LEFT JOIN interpro_go_mapping AS ip_go ON (
					dompred.InterProID = ip_go.Interpro_ID
				)
				WHERE
					anno.Ecotype IN (".str_repeat("?,", count($ecotypes)-1)."?".") AND
					anno.Version IN (".str_repeat("?,", count($versions)-1)."?".") AND
					(";
			foreach ($vars as $trx_item) {
				if(preg_match('/^chr\d+/', $trx_item)) {
					$dbq .= "anno.Gene LIKE ? OR ";
					$exec_vars = array_merge($exec_vars, array($trx_item.'%'));
				} else if(preg_match('/^(Lj|Lotja)/i', $trx_item)) {
					$dbq .= "MATCH(anno.Gene) AGAINST (? IN BOOLEAN MODE) OR
						MATCH(anno.Annotation) AGAINST (? IN BOOLEAN MODE) OR
						MATCH(anno.LjAnnotation) AGAINST (? IN BOOLEAN MODE) OR ";
						$exec_vars = array_merge($exec_vars, array($trx_item, $trx_item, (preg_match('/^Lj/i', $trx_item) ? $trx_item : 'Lj'.$trx_item)));
				} else if(preg_match('/^IPR\d+/i', $trx_item)) {
					$dbq .= "dompred.InterProID = ? OR ";
					$exec_vars = array_merge($exec_vars, array($trx_item));
				} else if(preg_match('/^(cd\d+|PD\d+|PF\d+|PR\d+|PS\d+|SM\d+|TIGR\d+|G3DSA:.+|SSF\d+|PTHR\d+:SF\d+|PTHR\d+)$/i', $trx_item)) {
					$dbq .= "dompred.SourceID = ? OR ";
					$exec_vars = array_merge($exec_vars, array($trx_item));
				} else if(preg_match('/^GO:/i', $trx_item)) {
					$dbq .= "ip_go.GO_ID = ? OR ";
					$exec_vars = array_merge($exec_vars, array($trx_item));
				}
				
				// If no pattern matches, then we probably have to search through everything
				else {
					$dbq .= "MATCH(anno.Gene) AGAINST (? IN BOOLEAN MODE) OR
						MATCH(anno.Annotation) AGAINST (? IN BOOLEAN MODE) OR
						MATCH(anno.LjAnnotation) AGAINST (? IN BOOLEAN MODE) OR
						dompred.InterProID = ? OR 
						dompred.SourceID = ? OR
						ip_go.GO_ID = ? OR ";
					$exec_vars = array_merge($exec_vars, array($trx_item, $trx_item, (preg_match('/^Lj/i', $trx_item) ? $trx_item : 'Lj'.$trx_item), $trx_item, $trx_item, $trx_item));
				}
				
			}
			$dbq = substr($dbq, 0 ,-4);
			$dbq .= ") GROUP BY tc.Transcript";
			$searched = true;
			
		}

		// If chromosome and at least one position is provided
		else if(!empty($_GET['chr']) && !empty($_GET['pos1'])) {

			// Construct database query
			$dbq = "
				FROM
					transcriptcoord AS tc
				LEFT JOIN annotations AS anno ON (
					tc.Transcript = anno.Gene AND
					tc.Ecotype = anno.Ecotype AND
					tc.Version = anno.Version
				)
				LEFT JOIN domain_predictions AS dompred ON (
					anno.Gene = dompred.Transcript
				)
				LEFT JOIN interpro_go_mapping AS ip_go ON (
					dompred.InterProID = ip_go.Interpro_ID
				)
				WHERE
					anno.Ecotype IN (".str_repeat("?,", count($ecotypes)-1)."?".") AND
					anno.Version IN (".str_repeat("?,", count($versions)-1)."?".")
			";

			// Assign variables
			$pos1 = $_GET['pos1'];
			$pos2 = $_GET['pos2'];

			// Process positions
			if($pos1 > $pos2) {
				if($pos2 == '') {
					$dbq .= "AND (tc.StartPos = ? OR tc.EndPos = ?)";
					$exec_vars[] = $pos1;
				} else {
					$lower = $pos2;
					$upper = $pos1;
					$dbq .= " AND (tc.StartPos BETWEEN ? AND ?)";
					$exec_vars[] = $pos2;
					$exec_vars[] = $pos1;
				}
			} elseif($pos1 < $pos2) {
				if($pos1 == '') {
					$dbq .= "AND (tc.StartPos = ?)";
					$exec_vars[] = $pos1;
				} else {
					$lower = $pos1;
					$upper = $pos2;
					$dbq .= " AND (tc.StartPos BETWEEN ? AND ?)";
					$exec_vars[] = $pos1;
					$exec_vars[] = $pos2;
				}
			}

			// Add chromosome
			$dbq .= " AND tc.Chromosome = ?";
			$exec_vars[] = $_GET['chr'];

			$dbq .= " GROUP BY tc.Transcript";
			$searched = true;
		}
		else {
			$error = array(
				'error' => true,
				'message' => 'Invalid TREX query used.'
			);
		}

		try {
			if ($error) {
				throw new Exception($error['message']);
			}

			// Prequery
			$q1 = $db->prepare("SELECT
				anno.ID AS ID,
				anno.Gene AS Transcript,
				anno.Ecotype AS Ecotype,
				anno.Version AS Version
				".$dbq." ORDER BY tc.Transcript ASC");
			$q1->execute($exec_vars);

			$rows = $q1->rowCount();
			if($rows < 1) {
				if(isset($_GET['anno']) && strlen($_GET['anno'] < 3)) {
					throw new Exception('No rows have been returned. It seems that you are attemtping to search using a non-specfic keyword. Please refine your query.');
				} else {
					throw new Exception('No rows have been returned. Please repeat your search.');
				}
			} else {
				while($r = $q1->fetch(PDO::FETCH_ASSOC)) {
					$q1_rows['ID'][] = $r['ID'];
					$q1_rows['Transcript'][] = $r['Transcript'];
					$q1_rows['Ecotype'][] = $r['Ecotype'];
					$q1_rows['Version'][] = $r['Version'];

					if (
						(floatVal($r['Version']) >= 3 && $r['Ecotype'] === 'MG20') ||
						($r['Version'] === '1.2' && $r['Ecotype'] === 'Gifu')
					) {
						$q1_filtered_rows['ID'][] = $r['ID'];
						$q1_filtered_rows['Transcript'][] = $r['Transcript'];
					}
				}
			}

			// Default pagination variables
			$num	= (isset($_GET['n']) && !empty($_GET['n'])) ? intval($_GET['n']) : 50;
			$page	= (isset($_GET['p']) && !empty($_GET['p'])) ? intval($_GET['p']) : 1;

			// Get pagination variables
			$last = intval(ceil($rows/$num));
			if($page <= 1) {
				$page = 1;
			} elseif($page > $last) {
				$page = $last;
			}

			// Perform actual query
			$q2 = $db->prepare("SELECT
				anno.Gene AS Transcript,
				anno.Ecotype AS Ecotype,
				anno.Version AS Version,
				tc.StartPos AS Start,
				tc.EndPos AS End,
				tc.Strand AS Strand,
				tc.Chromosome AS Chromosome,
				anno.Annotation AS Annotation,
				anno.LjAnnotation AS LjAnnotation,
				GROUP_CONCAT(DISTINCT dompred.InterproID) AS Interpro_ID,
				GROUP_CONCAT(DISTINCT dompred.SourceID) AS DomPred_ID,
				GROUP_CONCAT(DISTINCT ip_go.GO_ID) AS GO_ID,
				CASE WHEN anno.LjAnnotation IS NOT NULL THEN 1 ELSE 0 END AS CustomRank
				".$dbq."
				ORDER BY CustomRank DESC, tc.Transcript ASC
				LIMIT ".($page-1)*$num.", ".$num);
			$q2->execute($exec_vars);

		} catch(PDOException $err) {
			$e = $db->errorInfo();
			$error = array(
				'error' => true,
				'message' => 'MySQL Error '.$e[1].': '.$e[2].'<br />'.$err->getMessage()
			);
		} catch(Exception $err) {
			$error = array(
				'error' => true,
				'message' => $err->getMessage()
			);
		}
	} else if(!empty($_GET) && empty($_GET['trx']) && empty($_GET['anno'])) {
		$error = array(
			'error' => true,
			'message' => 'You have not provided a transcript ID or a gene annotation.'
		);
	}

	// If it is a download request
	else if($_POST && !empty($_POST['ids'])) {
		try {
			$ids = explode(',', $_POST['ids']);
			$q3 = $db->prepare("SELECT
				anno.Gene AS Transcript,
				CONCAT(anno.Ecotype, ' v', anno.Version) AS GenomeAssembly,
				tc.StartPos AS Start,
				tc.EndPos AS End,
				tc.Strand AS Strand,
				tc.Chromosome AS Chromosome,
				anno.Annotation AS Annotation,
				anno.LjAnnotation AS LjAnnotation,
				GROUP_CONCAT(DISTINCT gi.PlantID) AS LORE1_Insertions,
				GROUP_CONCAT(DISTINCT dompred.InterproID) AS Interpro_ID,
				GROUP_CONCAT(DISTINCT ip_go.GO_ID) AS GO_ID
				FROM annotations AS anno
				LEFT JOIN transcriptcoord AS tc ON (
					anno.Gene = tc.Transcript AND
					anno.Ecotype = tc.Ecotype AND
					anno.Version = tc.Version
				)
				LEFT JOIN domain_predictions AS dompred ON (
					anno.Gene = dompred.Transcript
				)
				LEFT JOIN interpro_go_mapping AS ip_go ON (
					dompred.InterProID = ip_go.Interpro_ID
				)
				LEFT JOIN geneins AS gi ON (
					tc.Gene = gi.Gene
				)
				WHERE
					anno.ID IN (".str_repeat("?,", count($ids)-1)."?".")
				GROUP BY tc.Transcript ORDER BY tc.Transcript ASC");
			$q3->execute($ids);

			// Generate download file
			$header = array(
				"Transcript",
				"Genome assembly",
				"Start",
				"End",
				"Strand",
				"Chromosome",
				"Annotation",
				"Gene name",
				"LORE1 genic insertions",
				"Domain predictions",
				"GO terms");
			$out = implode("\t", $header)."\n";
			while($r = $q3->fetch(PDO::FETCH_ASSOC)) {
				$out .= implode("\t", $r)."\n";
			}

			$file = "lore1_trex_" . date("Y-m-d_H-i-s") . ".tsv";
			header("Content-disposition: csv; filename=\"".$file."\"");
			header("Content-type: application/vnd.ms-excel");
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			print $out;
			exit();

		} catch(PDOException $e) {
			if(is_valid_request_uri($_POST['redir'])) {
				$url = $_POST['redir'];
			} else {
				$url = './';
			}
			$_SESSION['trex_error'] = $e->getMessage();
			header('Location: '.$url);
			exit();
		}
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>TrEx &mdash; Tools &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'The Transcript Explorer (TREX) tool searches for candidate Lotus genes using user-defined keywords, or combs for a specific genomic interval, for genes/transcripts of interest.'
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/tools.min.css" type="text/css" media="screen" />
</head>
<body class="tools trex <?php echo ($searched) ? 'results' : ''; ?>">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title('TREX');
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2>TREX</h2>
		<span class="byline"><strong>Transcript Explorer</strong><br />for <em>L. japonicus</em> reference genomes</span>
		<p>The <strong>Transcript Explorer</strong> tool can be used to search for candidate <em>Lotus</em> genes using user-defined keywords, or to comb a specific genomic interval&mdash;a combination of chromosome and position range&mdash;for genes/transcripts of interest.</p>

		<?php 
			// Display error if any
			if(!empty($error)) {
				echo '<div class="user-message warning align-center"><h3>Houston, we have a problem!</h3>'.$error['message'].'</div>';
			}
			else if(isset($_SESSION['trex_error'])) {
				echo '<div class="user-message warning align-center"><h3>Houston, we have a problem!</h3>'.$_SESSION['trex_error'].'</div>';
				unset($_SESSION['trex_error']);
			}

			// Allow collapsible form when search is initiated
			if($searched) {
				echo '<div class="toggle'.(empty($error) ? ' hide-first' : '').'"><h3><a href="#" title="Repeat Search">Repeat Search</a></h3>';
			}

		?>
			<form action="./trex" method="get" id="trex-form" class="has-group">
				<div class="cols" role="group">
					<label for="ids-input" class="col-one">Query <a href="<?php echo WEB_ROOT; ?>/lib/docs/trex-query" class="info" title="How should I look for my gene of interest?" data-modal="wide">?</a></label>
					<div class="col-two">
						<div class="multiple-text-input input-mimic">
							<ul class="input-values">
							<?php
								if(!empty($_GET['ids'])) {
									if(is_array($_GET['ids'])) {
										$trx_array = $_GET['ids'];
									} else {
										$trx_array = explode(",", $_GET['ids']);
									}
									foreach($trx_array as $trx_item) {
										echo '<li data-input-value="'.escapeHTML($trx_item).'">'.escapeHTML($trx_item).'<span class="icon-cancel" data-action="delete"></span></li>';
									}
								}
							?>
								<li class="input-wrapper"><input type="text" id="ids-input" placeholder="Keyword, or gene/transcript ID" autocomplete="off" autocorrect="off"  autocapitalize="off" spellcheck="false" data-boolean-mode="true" /></li>
							</ul>
							<input class="input-hidden" type="hidden" name="ids" id="ids" value="<?php echo (!empty($_GET['ids'])) ? (is_array($_GET['ids']) ? implode(',', preg_replace('/\"/', '&quot;', escapeHTML($trx_array))) : preg_replace('/\"/', '&quot;', escapeHTML($_GET['ids']))) : ''; ?>" readonly />
						</div>
						<small><strong>Separate each keyword, or gene/transcript ID, with a comma or tab. Spaces are not accepted as delimiters due to their potential use in <a href="<?php echo WEB_ROOT; ?>/lib/docs/trex-query" title="How should I look for my gene of interest?" data-modal="wide">boolean searches</a>.</strong></small>
					</div>

					<div class="separator full-width"><span>or</span></div>

					<label for="chromosome" class="col-one">Chromosome</label>
					<div class="col-two field__chromosome">
						<select name="chr" id="chromosome" class="search-param">
							<option value="" selected="selected">Select chromosome</option>
							<option value="chr0" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr0') ? 'selected' : ''; ?>>Chromosome 0</option>
							<option value="chr1" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr1') ? 'selected' : ''; ?>>Chromosome 1</option>
							<option value="chr2" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr2') ? 'selected' : ''; ?>>Chromosome 2</option>
							<option value="chr3" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr3') ? 'selected' : ''; ?>>Chromosome 3</option>
							<option value="chr4" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr4') ? 'selected' : ''; ?>>Chromosome 4</option>
							<option value="chr5" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr5') ? 'selected' : ''; ?>>Chromosome 5</option>
							<option value="chr6" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr6') ? 'selected' : ''; ?>>Chromosome 6</option>
							<option value="mito" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'mito') ? 'selected' : ''; ?>>Mitochondrion</option>
							<option value="chloro" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chloro') ? 'selected' : ''; ?>>Chlorophyll</option>
							<option value="chr8" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr8') ? 'selected' : ''; ?>>Plastid</option>
						</select>
					</div>

					<label class="col-one">Position <a class="info" data-modal="search-help" data-modal-content="Genes can be searched between two positions (inclusive). However: &lt;ul&gt;&lt;li&gt;if only one position is filled in, the search will only look for an exact position, or&hellip;&lt;/li&gt;&lt;li&gt;if nothing is filled in, search will not look for genes based on positions.&lt;/li&gt;&lt;/ul&gt;" title="How to search for the position of gene insert?">?</a></label>
					<div class="col-two cols flex-wrap__nowrap field__positions">
						<label for="pos1">Between</label> <input type="number" name="pos1" id="pos1" class="search-param" placeholder="Start " value="<?php echo (!empty($_GET['pos1'])) ? intval($_GET['pos1']) : ''; ?>" min="0" /> <label for="pos2">and</label> <input type="number" name="pos2" id="pos2" class="search-param" placeholder="End" value="<?php echo (!empty($_GET['pos2'])) ? intval($_GET['pos2']) : ''; ?>" min="0" />
					</div>
				</div>

				<div class="cols" role="group">
					<div class="col-one"><label>Genome version(s) <a href="<?php echo WEB_ROOT; ?>/lib/docs/version-filtering" class="info" title="Filtering for versions" data-modal>?</a></label></div>
					<div class="col-two cols justify-content__flex-start versions">
						<?php
							foreach($lj_genome_versions as $label => $lj_genome) {
								$lj_genome_id = implode('_', [$lj_genome['ecotype'], $lj_genome['version']]);
								$lj_genome_version = $lj_genome['version'];
								$checked = (isset($version) && (in_array($lj_genome_id, $version))) || ((!isset($version) || $error) && version_compare($lj_genome_version, '3.0') >= 0);
								echo '<input type="checkbox" value="'.$lj_genome_id.'" class="prettify" name="v[]" id="'.$lj_genome_id.'" '.($checked ? 'checked' : '').'/><label for="version-'.$lj_genome_id.'">'.$label.'</label>';
							}
						?>
					</div>
				</div>

				<input type="hidden" name="n" value="25" />
				<input type="hidden" name="p" value="1" />

				<button type="submit"><span class="pictogram icon-search">Search for gene of interest</span></button>
			</form>
			<?php if (empty($error) && $searched) { ?>
			</div><div class="toggle">
					<h3><a href="#" data-toggled="on" class="open">Export options</a></h3>
					<p>Download the entire search result as a CSV file.</p>
					<div class="cols justify-content__space-around">
						<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="form--no-spacing">
							<button type="submit">Download tabular results</button>
							<input type="hidden" name="ids" value="<?php echo implode(',', $q1_rows['ID']); ?>" />
							<input type="hidden" name="redir" value="<?php echo $_SERVER['REQUEST_URI']; ?>" />
						</form>
						<?php
							// Only allow bulk download if genome assembly is monolithic
							if (count($q1_filtered_rows) && count($versions) === 1 && count($ecotypes) === 1) {
								$genome_ecotype = $ecotypes[0];
								$genome_version = $versions[0];
						?>
							<?php if ($genome_ecotype === 'MG20' && $genome_version === '3.0') { ?>
								<div class="dropdown button">
									<span class="dropdown--title">Download all sequences</span>
									<ul class="dropdown--list">
										<li><a href="../api/v1/blast/20130521_Lj30_CDS.fa/<?php echo implode(',', $q1_filtered_rows['Transcript']); ?>?download&access_token=<?php echo LOTUSBASE_API_KEY; ?>"><span class="pictogram icon-switch">Download all coding sequences</span></a></li>
										<li><a href="../api/v1/blast/20130521_Lj30_cDNA.fa/<?php echo implode(',', $q1_filtered_rows['Transcript']); ?>?download&access_token=<?php echo LOTUSBASE_API_KEY; ?>"><span class="pictogram icon-switch">Download all mRNA sequences</span></a></li>
										<li><a href="../api/v1/blast/20130521_Lj30_proteins.fa/<?php echo implode(',', $q1_filtered_rows['Transcript']); ?>?download&access_token=<?php echo LOTUSBASE_API_KEY; ?>"><span class="pictogram icon-switch">Download all amino acid sequences</span></a></li>
									</ul>
								</div>
							<?php } else if ($genome_ecotype === 'Gifu' && $genome_version === '1.2') {?>
								<div class="dropdown button">
									<span class="dropdown--title">Download all sequences</span>
									<ul class="dropdown--list">
										<li><a href="../api/v1/blast/20190809_Lj_Gifu_v1.2_CDS.fa/<?php echo implode(',', $q1_filtered_rows['Transcript']); ?>?download&access_token=<?php echo LOTUSBASE_API_KEY; ?>"><span class="pictogram icon-switch">Download all coding sequences</span></a></li>
										<li><a href="../api/v1/blast/20190809_Lj_Gifu_v1.2_proteins.fa/<?php echo implode(',', $q1_filtered_rows['Transcript']); ?>?download&access_token=<?php echo LOTUSBASE_API_KEY; ?>"><span class="pictogram icon-switch">Download all amino acid sequences</span></a></li>
									</ul>
								</div>
							<?php } ?>
						<?php } ?>
					</div>
				</div>
			<?php	
				}
				
				// Display search results
				if(empty($error) && $searched && $q2->rowCount() > 0) {
			?>
			<p>We have found <?php echo "<strong>".$rows."</strong> ".pl($rows,'result','results'); ?>. Now displaying <?php echo $page." of ".$last." with ".$num." ".pl($num,'row','rows');?> per page. This search has taken <strong><?php echo number_format((microtime(true) - $start_time), 3); ?>s</strong> to perform.</p>

			<?php
				if($last > 1) {
					$paginate = new \LotusBase\Component\Paginate();
					$paginate->set_current_page($page);
					$paginate->set_last_page($last);
					$paginate->set_rows_per_page($num);
					$paginate->set_query_string($_GET);
					$paginate->set_version($version); 
					$pagination = $paginate->get_pagination();
					echo $pagination;
				}
			?>

			<table id="rows" data-sticky>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<colgroup></colgroup>
				<thead>
					<tr>
						<th scope="col">Transcript</th>
						<th scope="col">Name</th>
						<th scope="col">Description</th>
						<th scope="col"><abbr title="Chromosome">Chr</abbr></th>
						<th scope="col" data-type="numeric"><abbr title="Start Position">Start</abbr></th>
						<th scope="col" data-type="numeric"><abbr title="End Position">End</abbr></th>
						<th scope="col">Strand</th>
						<th scope="col">Domain predictions</th>
						<th scope="col"><abbr title="Gene Ontology">GO</abbr> predictions</th>
						<?php //echo ($search_type === 'anno' ? '<th scope="col"><a href="#" data-modal data-modal-content="The score for each row is computed based on how relevant they are to the search query you have provided. The algorithm behind how this score is computed is &lt;a href=&quot;http://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html#idm140019561440400&quot;&gt;available in the official MySQL documentation&lt;/a&gt;." title="What does the score of my result row indicate?">Score</a></th>' : ''); ?>
					</tr>
				</thead>
				<tbody>
		<?php
			$vlt3 = 0;
			while($row = $q2->fetch(PDO::FETCH_ASSOC)) {
				// Get version
				$v = floatval($row['Version']);
				$ecotype = $row['Ecotype'];

				// Determine start and end positions
				if($row['Strand'] == '+') {
					$start = $row['Start'];
					$end = $row['End'];
				} elseif($row['Strand'] == '-') {
					$start = $row['End'];
					$end = $row['Start'];
				}
		?>
					<tr
						data-chromosome="<?php echo $row['Chromosome']; ?>"
						data-transcript="<?php echo $row['Transcript']; ?>"
						data-from="<?php echo $start; ?>"
						data-to="<?php echo $end; ?>"
					>
						<td class="trx">
							<?php if($v >= 3.0 && $ecotype === 'MG20') { ?>
							<div class="dropdown button"><span class="dropdown--title"><a href="<?php echo WEB_ROOT.'/view/transcript/'.$row['Transcript']; ?>"><?php echo $row['Transcript']; ?></a></span><ul class="dropdown--list">
								<li><a href="<?php echo WEB_ROOT.'/view/gene/'.preg_replace('/\.\d+?$/', '', $row['Transcript']); ?>" title="View gene"><span class="icon-eye">View gene</span></a></li>
								<li><a href="<?php echo WEB_ROOT.'/view/transcript/'.$row['Transcript']; ?>" title="View transcript"><span class="icon-eye">View transcript</span></a></li>
								<li><a href="../lore1/search?gene=<?php echo preg_replace('/\.\d+?$/', '', $row['Transcript']); ?>&amp;v=<?php echo $row['Version']; ?>" title="Search for LORE1 insertions in this gene"><span class="icon-leaf"><em>LORE1</em> v<?php echo $row['Version']; ?></span></a></li>
								<li>
									<a
										href="../api/v1/blast/<?php echo 'lj_r30.fa/'.$row['Chromosome'].'?from='.$start.'&to='.$end.'&access_token='.LOTUSBASE_API_KEY; ?>"
										data-seqret
										data-seqret-id="<?php echo $row['Chromosome']; ?>"
										data-seqret-data-type="genomic"
										data-seqret-db="lj_r30.fa"
										data-seqret-from="<?php echo $start; ?>"
										data-seqret-to="<?php echo $end; ?>"
										title="Retrieve genomic sequence"
										>
										<span class="icon-switch">Genomic sequence</span>
									</a>
								</li>
								<li>
									<a
										href="../api/v1/blast/<?php echo '20130521_Lj30_CDS.fa/'.$row['Transcript'].'&access_token='.LOTUSBASE_API_KEY; ?>"
										data-seqret
										data-seqret-id="<?php echo $row['Transcript']; ?>"
										data-seqret-data-type="coding sequence"
										data-seqret-db="20130521_Lj30_CDS.fa"
										title="Retrieve coding sequence"
										>
										<span class="icon-switch">Coding sequence</span>
									</a>
								</li>
								<li>
									<a
										href="../api/v1/blast/<?php echo '20130521_Lj30_cDNA.fa/'.$row['Transcript'].'&access_token='.LOTUSBASE_API_KEY; ?>"
										data-seqret
										data-seqret-id="<?php echo $row['Transcript']; ?>"
										data-seqret-data-type="mRNA"
										data-seqret-db="20130521_Lj30_cDNA.fa"
										title="Retrieve mRNA sequence"
										>
										<span class="icon-switch">mRNA sequence</span>
									</a>
								</li>
								<li>
									<a
										href="../api/v1/blast/<?php echo '20130521_Lj30_proteins.fa/'.$row['Transcript'].'&access_token='.LOTUSBASE_API_KEY; ?>"
										data-seqret
										data-seqret-id="<?php echo $row['Transcript']; ?>"
										data-seqret-data-type="amino acid"
										data-seqret-db="20130521_Lj30_proteins.fa"
										title="Retrieve amino acid sequence"
										>
										<span class="icon-switch">Protein sequence</span>
									</a>
								</li>
								<li><a href="../genome?data=genomes%2Flotus-japonicus%2Fmg20%2Fv3.0&loc=<?php echo $row['Transcript']; ?>" title="View in genome browser"><span class="icon-book">Genome browser</span></a></li>
								<li><a href="../expat/?ids=<?php echo $row['Transcript']; ?>&amp;t=6" title="Access expression data from the Expression Atlas"><span class="icon-map">Expression Atlas (ExpAt)</span></a></li>
								<?php if(is_logged_in()) { ?>
									<li><a class="manual-gene-anno" href="<?php echo WEB_ROOT; ?>/lib/docs/gene-annotation" title="Manual gene name suggestion for <?php echo $row['Transcript']; ?>" data-gene="<?php echo $row['Transcript']; ?>"><span class="pictogram icon-bookmark">Suggest <?php echo ((isset($row['LjAnnotation']) && !empty($row['LjAnnotation'])) ? 'another ' : ''); ?><em>Lj</em> gene name</span></a></li>
								<?php } ?>
							</ul>
							</div>
							<?php } else if($v === 1.2 && $ecotype === 'Gifu') { ?>
								<div class="dropdown button"><span class="dropdown--title"><a href="<?php echo WEB_ROOT.'/view/transcript/'.$row['Transcript']; ?>"><?php echo $row['Transcript']; ?></a></span><ul class="dropdown--list">
								<li><a href="<?php echo WEB_ROOT.'/view/gene/'.preg_replace('/\.\d+?$/', '', $row['Transcript']); ?>" title="View gene"><span class="icon-eye">View gene</span></a></li>
								<li><a href="<?php echo WEB_ROOT.'/view/transcript/'.$row['Transcript']; ?>" title="View transcript"><span class="icon-eye">View transcript</span></a></li>
								<li>
									<a
										href="../api/v1/blast/<?php echo '20190809_Lj_Gifu_v1.2_genome.fa/'.$row['Chromosome'].'?from='.$start.'&to='.$end.'&access_token='.LOTUSBASE_API_KEY; ?>"
										data-seqret
										data-seqret-id="<?php echo $row['Chromosome']; ?>"
										data-seqret-data-type="genomic"
										data-seqret-db="20190809_Lj_Gifu_v1.2_genome.fa"
										data-seqret-from="<?php echo $start; ?>"
										data-seqret-to="<?php echo $end; ?>"
										title="Retrieve genomic sequence"
										>
										<span class="icon-switch">Genomic sequence</span>
									</a>
								</li>
								<li>
									<a
										href="../api/v1/blast/<?php echo '20190809_Lj_Gifu_v1.2_CDS.fa/'.$row['Transcript'].'&access_token='.LOTUSBASE_API_KEY; ?>"
										data-seqret
										data-seqret-id="<?php echo $row['Transcript']; ?>"
										data-seqret-data-type="coding sequence"
										data-seqret-db="20190809_Lj_Gifu_v1.2_CDS.fa"
										title="Retrieve coding sequence (ORFs only)"
										>
										<span class="icon-switch">Coding sequence</span>
									</a>
								</li>
								<li>
									<a
										href="../api/v1/blast/<?php echo '20190809_Lj_Gifu_v1.2_proteins.fa/'.$row['Transcript'].'&access_token='.LOTUSBASE_API_KEY; ?>"
										data-seqret
										data-seqret-id="<?php echo $row['Transcript']; ?>"
										data-seqret-data-type="amino acid"
										data-seqret-db="20190809_Lj_Gifu_v1.2_proteins.fa"
										title="Retrieve amino acid sequence"
										>
										<span class="icon-switch">Protein sequence</span>
									</a>
								</li>
								<li><a href="../genome?data=genomes%2Flotus-japonicus%2Fgifu%2Fv1.1&loc=<?php echo $row['Transcript']; ?>" title="View in genome browser"><span class="icon-book">Genome browser</span></a></li>
							</ul>
							</div>
							<?php } else {
								echo $row['Transcript'].' <sup><a href="#" data-modal data-modal-content="Data from &le;v2.5 cannot be mapped to our entire dataset&mdash;we encourage users to map these accessions to v3.0 or above." title="Incomplete dataset warning" class="icon-attention icon--no-spacing"></a></sup>';
								$vlt3++;
							} ?>
						</td>
						<td class="name">
						<?php
							if (isset($row['LjAnnotation']) && !empty($row['LjAnnotation'])) { ?>
							<span class="name__manual"><em><?php echo $row['LjAnnotation']; ?></em><?php if(version_compare($v, '3.0', '<') && is_logged_in()) { ?>
								<a href="<?php echo WEB_ROOT; ?>/lib/docs/gene-annotation" class="button manual-gene-anno" title="Manual gene name suggestion for <?php echo $row['Transcript']; ?>" data-gene="<?php echo $row['Transcript']; ?>"><span class="pictogram icon-bookmark">Suggest alternatives</span></a>
							<?php } ?></span>
							<?php } else {
								echo 'n.a.';
							}
						?>
						</td>
						<td class="desc">
							<?php
							if(!empty($row['Annotation'])) {
								$desc = preg_replace('/\[([\w\s]+)\]?/', '[<em>$1</em>]', $row['Annotation']);
								echo $desc;
							} else {
								echo 'n.a.';
							} ?>
						</td>
						<td class="chr"><?php echo $row['Chromosome']; ?></td>
						<td class="pos" data-type="numeric"><?php echo $row['Start']; ?></td>
						<td class="pos" data-type="numeric"><?php echo $row['End']; ?></td>
						<td class="str"><?php echo $row['Strand']; ?></td>
						<td>
							<?php
								$interpro_ids = array_filter(explode(',', $row['Interpro_ID']));
								$dompred_ids = array_filter(explode(',', $row['DomPred_ID']));
								$go_ids = array_filter(explode(',', $row['GO_ID']));

								// Domains
								$domains_grouped = array('interpro' => $interpro_ids);
								foreach($dompred_ids as $d) {
									switch (true) {
										case (strpos($d, 'PD') === 0 ? true : false):
											$domains['BlastProDom'][] = $d;
											break;
										case (strpos($d, 'coil') === 0 ? true : false):
											$domains['Coil'][] = $d;
											break;
										case (strpos($d, 'PR') === 0 ? true : false):
											$domains['FPrintScan'][] = $d;
											break;
										case (strpos($d, 'G3DSA') === 0 ? true : false):
											$domains['Gene3D'][] = $d;
											break;
										case (strpos($d, 'MF_') === 0 ? true : false):
											$domains['HAMAP'][] = $d;
											break;
										case (strpos($d, 'PTHR') === 0 ? true : false):
											$domains['HMMPanther'][] = $d;
											break;
										case (strpos($d, 'PF') === 0 ? true : false):
											$domains['HMMPfam'][] = $d;
											break;
										case (strpos($d, 'PIRSF') === 0 ? true : false):
											$domains['HMMPIR'][] = $d;
											break;
										case (strpos($d, 'SM') === 0 ? true : false):
											$domains['HMMSmart'][] = $d;
											break;
										case (strpos($d, 'TIGR') === 0 ? true : false):
											$domains['HMMTigr'][] = $d;
											break;
										case (strpos($d, 'PS') === 0 ? true : false):
											$domains['PatternProfileScan'][] = $d;
											break;
										case (strpos($d, 'SSF') === 0 ? true : false):
											$domains['Superfamily'][] = $d;
											break;
										default:
											break;
									}
								}

								// Print domain predictions
								$domains = array_filter(array_unique(array_merge($interpro_ids, $dompred_ids)));
								asort($domains);
								if(!empty($domains)) {
									$out = '<div class="dropdown button"><span class="dropdown--title">'.count($domains).' domain '.pl(count($domains), 'prediction').'</span><ul class="dropdown--list">';
									foreach($domains as $d) {
										$out .= '<li><a href="'.WEB_ROOT.'/view/domain/'.$d.'">'.$d.'</a></li>';
									}
									$out .= '</ul></div>';
								} else {
									$out = '&ndash;';
								}
								echo $out;

							?>
						</td>
						<td><?php
							if(!empty($go_ids)) {
								$out = '<div class="dropdown button"><span class="dropdown--title">'.count($go_ids).' GO '.pl(count($go_ids), 'term').'</span><ul class="dropdown--list">';
									foreach($go_ids as $go_id) {
										$out .= '<li><a href="'.WEB_ROOT.'/view/go/'.$go_id.'">'.$go_id.'</a></li>';
									}
									$out .= '</ul></div>';
							} else {
								$out = '&ndash;';
							}
							echo $out;
						?></ul></td>
					</tr>
		<?php } ?>
			</tbody>
			</table>
		<?php
				// Only display pagination if on last page and have enough rows
				if($last > 1 && $q2->rowCount() > 5) {
					echo $pagination;
				}
			}
		?>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/trex.min.js"></script>
</body>
</html>