<?php
	require_once('../config.php');

	try {
		if(!empty($_GET) && !empty($_GET['id'])) {
			$id = escapeHTML($_GET['id']);

			if(
				// MG20 v3.0
				preg_match('/^Lj(\d|chloro}mito)g3v(\d+)\.\d+$/', $id) ||

				// Gifu v1.2
				preg_match('/^LotjaGi\dg\dv\d+?\.\d+?$/', $id)
			) {
				// If gene pattern is match, redirect to gene page
				header('Location:'.WEB_ROOT.'/view/transcript/'.$id);
				exit();
			}

			// Doesn't match any known gene patterns
			else if(
				// MG20 v3.0
				!preg_match('/^Lj(\d|chloro}mito)g3v(\d+)$/', $id) &&

				// Gifu v1.2
				!preg_match('/^LotjaGi\dg\dv\d+?$/', $id)
			) {
				// If ID fails pattern check
				$_SESSION['view_error'] = 'Invalid gene ID format detected. Please ensure that your gene ID follows the formats:
					<ul>
						<li><strong>MG20 v3.0</strong>: <code>Lj{chr}g{version}v{id}</code>, e.g. Lj4g3v0281040</li>
						<li><strong>Gifu v1.2</strong>: <code>LotjaGi{chr}g{version}v{id}</code>, e.g. LotjaGi4g1v0024900</li>
					</ul>';
				throw new Exception;
			}

			else if(preg_match('/^Lj(\d|chloro}mito)g3v(\d+)$/', $id)) {
				$genome = 'MG20_3.0';
			}

			else if(preg_match('/^LotjaGi\dg\dv\d+?$/', $id)) {
				$genome = 'Gifu_1.2';
			}
		} else {
			// If ID is not available
			throw new Exception;
		}
	} catch (Exception $e) {
		header('Location:'.WEB_ROOT.'/view');
		exit();
	}
	
?>
<!doctype html>
<html lang="en">
<head>
	<title>Gene &mdash; View &mdash; Lotus Base</title>
	<script>
		window.lotusbase = {
			genome: '<?php echo $genome; ?>'
		};
	</script>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Consolidated gene view: '.escapeHTML($_GET['id'])
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/view.min.css" type="text/css" media="screen" />
</head>
<body class="viewer gene init-scroll--disabled">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<h2><?php echo escapeHTML($id); ?></h2>

		<div id="view__card" class="view__facet">
			<h3>Isoforms</h3>
			<?php
				try {
					$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

					$q1 = $db->prepare("SELECT
							tc.StartPos AS StartPos,
							tc.EndPos AS EndPos,
							tc.Strand AS Strand,
							tc.Transcript AS Transcript,
							anno.Annotation AS Description
						FROM transcriptcoord AS tc
						LEFT JOIN annotations AS anno ON
							tc.Transcript = anno.Gene
						WHERE tc.Gene = ?");
					$q1->execute(array($id));

					$q2 = $db->prepare("SELECT
							GROUP_CONCAT(DISTINCT geniclore1.PlantID ORDER BY geniclore1.PlantID ASC) AS GenicPlantID,
							GROUP_CONCAT(DISTINCT exoniclore1.PlantID ORDER BY exoniclore1.PlantID ASC) AS ExonicPlantID
						FROM transcriptcoord AS tc
						LEFT JOIN exonins AS exon ON (
							tc.Transcript = exon.Gene AND
							tc.Ecotype = exon.Ecotype AND
							tc.Version = exon.Version
						)
						LEFT JOIN geneins AS genic ON (
							tc.Gene = genic.Gene AND
							tc.Ecotype = genic.Ecotype AND
							tc.Version = genic.Version
						)
						LEFT JOIN lore1ins AS geniclore1 ON (
							genic.Chromosome = geniclore1.Chromosome AND
							genic.Position = geniclore1.Position AND
							genic.Orientation = geniclore1.Orientation AND
							genic.Ecotype = geniclore1.Ecotype AND
							genic.Version = geniclore1.Version
						)
						LEFT JOIN lore1ins AS exoniclore1 ON (
							exon.Chromosome = exoniclore1.Chromosome AND
							exon.Position = exoniclore1.Position AND
							exon.Orientation = exoniclore1.Orientation AND
							exon.Ecotype = exoniclore1.Ecotype AND
							exon.Version = exoniclore1.Version
						)
						WHERE tc.Gene = ?
						GROUP BY tc.Gene
						LIMIT 1");
					$q2->execute(array($id));

					if($q1->rowCount()) { ?>
					<p>Each gene may have one or more predicted transcripts/proteins (known as isoforms) mapped to it. Navigate to individual isoforms for further details, such as domain prediction and GO term mappings.</p>
					<table class="table--dense">
						<thead>
							<tr>
								<th>Transcript</th>
								<th>Description</th>
								<th>Start</th>
								<th>End</th>
								<th>Strand</th>
							</tr>
						</thead>
						<tbody>
						<?php
							while($g = $q1->fetch(PDO::FETCH_ASSOC)) {
								echo '<tr>
									<td><a href="'.WEB_ROOT.'/view/transcript/'.$g['Transcript'].'">'.$g['Transcript'].'</a></td>
									<td>'.$g['Description'].'</td>
									<td>'.$g['StartPos'].'</td>
									<td>'.$g['EndPos'].'</td>
									<td>'.$g['Strand'].'</td>
								</tr>';
							}
						?>
						</tbody>
					</table>
					<?php }

				} catch(PDOException $e) { ?>
					<p class="user-message warning">We have encountered an error with querying the database: <?php echo $e->getMessage(); ?></p>
				<?php } catch(Exception $e) { ?>
					<p class="user-message warning">We have encountered a general error: <?php echo $e->getMessage(); ?></p>
				<?php }

			?>
		</div>

		<div id="view__jbrowse" class="view__facet">
			<h3>Genome browser</h3>
			<?php
				$jbrowse = array(
					'MG20_3.0' => array(
						'id' => $id,
						'data' => 'genomes%2Flotus-japonicus%2Fmg20%2Fv3.0'
					),
					'Gifu_1.2' => array(
						'id' => preg_replace('/^(.*?)\.\d+$/', '$1', $id),
						'data' => 'genomes%2Flotus-japonicus%2Fgifu%2Fv1.2'
					)
				);
			?>
			<iframe name="jbrowse-embed" class="jbrowse-embed" src="<?php echo WEB_ROOT.'/genome/?data='.$jbrowse[$genome]['data'].'&loc='.$jbrowse[$genome]['id'].'&amp;embed=true'; ?>"></iframe>
			<ul class="list--reset cols flex-wrap__nowrap justify-content__flex-start jbrowse__action">
				<li><a href="<?php echo WEB_ROOT.'/genome/?data='.$jbrowse[$genome]['data'].'&loc='.$jbrowse[$genome]['id'].'&amp;embed=true'; ?>" target="jbrowse-embed"><span class="icon-eye">Center view on <strong><?php echo $id; ?></strong></span></a></li>
				<li><a href="<?php echo WEB_ROOT.'/genome/?data='.$jbrowse[$genome]['data'].'&loc='.$jbrowse[$genome]['id']; ?>"><span class="icon-resize-full">View larger version</span></a></li>
				<li><a href="https://jbrowse.org" title="JBrowse">Powered by JBrowse <span class="icon-link-ext-alt icon--no-spacing"></span></a></li>
			</ul>
		</div>

		<?php if ($genome === 'MG20_3.0') { ?>
			<?php if($q2->rowCount()) {
				$g = $q2->fetch(PDO::FETCH_ASSOC);

				// Generate LORE1 list
				$genic_lore1 = array_filter(explode(',', $g['GenicPlantID']));
				$exonic_lore1 = array_filter(explode(',', $g['ExonicPlantID']));
				$intronic_lore1 = array_diff($genic_lore1, $exonic_lore1);

			?>
			<div id="view__lore1-inserts">
				<h3>LORE1 insertions <?php

					// Display count
					if(count($genic_lore1)) {
						echo '<span class="badge">'.count($genic_lore1).'</span>';
					}
				?></h3>
				<?php if(count($genic_lore1)) {?>
					<form id="lore1-filter__form" action="#" method="get" class="has-group">
						<div class="cols" role="group">
							<label class="col-one" for="lore1-type">Insertion filter</label>
							<div class="col-two">
								<select id="lore1-type" name="lore1_type">
									<option value="genic" selected>Genic (all)</option>
									<option value="intronic">Intronic (only in introns)</option>
									<option value="exonic">Exonic (only in exons)</option>
								</select>
							</div>
						</div>
					</form>
					<ul class="list--floated" id="lore1-list">
					<?php
						foreach($genic_lore1 as $pid) {
							echo '<li class="'.(in_array($pid, $exonic_lore1) ? 'lore1--exonic' : 'lore1--intronic').'"><a class="link--reset" href="'.WEB_ROOT.'/lore1/search?v=MG20_3.0&pid='.$pid.'" title="View details for this line">'.$pid.'</a></li>';
						}
					?>
					</ul>
				<?php } else { ?>
				<p class="user-message">No <em>LORE1</em> insertions have been found in the genomic region that overlaps with this gene.</p>
				<?php } ?>
			</div>
			<?php } ?>
		<?php } ?>

		<div id="view__expression" data-gene="<?php echo $id; ?>" class="view__facet">
			<h3>Expression data</h3>
			<form id="corgi__form" class="has-group">
				<div class="cols" role="group">
					<label for="expat-dataset" class="col-one">Dataset <a data-modal="wide" class="info" title="What are the available datasets?" href="<?php echo WEB_ROOT; ?>/lib/docs/expat-datasets">?</a></label>
					<div class="col-two">
						<?php
							$expat_dataset = new \Lotusbase\ExpAt\Dataset();
							if(!empty($_GET['dataset'])) {
								$expat_dataset->set_dataset($_GET['dataset']);
							}
							$expat_dataset->set_idType(array(
								'geneid'
								));

							if ($genome === 'MG20_3.0') {
								$selected_dataset = 'ljgea-geneid';
							} else if ($genome === 'Gifu_1.2') {
								$selected_dataset = 'reidd-2020-gifuatlas';
							}

							if ($selected_dataset) {
								$expat_dataset->set_selected_dataset($selected_dataset);
							}
							
							$expat_dataset->set_species('Lotus');
							$expat_dataset->set_genome($genome);
							echo $expat_dataset->render();
						?>
					</div>
				</div>
			</form>

			<h4>Expression pattern</h4>
			<p>Expression pattern of <strong><?php echo $id; ?></strong>, powered by <a href="<?php echo WEB_ROOT; ?>/expat" title="Expression Atlas">ExpAt</a>. For advanced configuration, data transformation and export options, <a id="view__expat__link" href="<?php echo WEB_ROOT; ?>/expat?ids=<?php echo $id; ?>&amp;dataset=ljgea-geneid&amp;idtype=geneid" title="" data-root="<?php echo WEB_ROOT; ?>">view expression data in the ExpAt application</a>.</p>
			<div id="expat__loader" class="align-center loader__wrapper">
				<div class="loader"><svg class="loader"><circle class="path" cx="40" cy="40" r="30" /></svg></div>
				<p>Loading expression data from <span id="expat__loader__dataset">ljgea-geneid</span>. Please wait&hellip;</p>
			</div>
			<div id="view__expat" class="hidden"></div>
			
			<?php if ($genome === 'MG20_3.0') { ?>
				<h4>Co-expressed genes</h4>
				<p>A list of the top 25 highly co-expressed genes of <strong><?php echo $id; ?></strong>, powered by <a href="<?php echo WEB_ROOT; ?>/tools/corgi" title="Co-expressed Genes Identifier (CORGI)">CORGI</a>.</p>
				<div id="coexpression__loader" class="align-center loader__wrapper">
					<div class="loader"><svg class="loader"><circle class="path" cx="40" cy="40" r="30" /></svg></div>
					<p>Loading co-expressed genes from the dataset <span id="coexpression__loader__dataset">ljgea-geneid</span>. This will take 20&ndash;30 seconds to construct.</p>
				</div>
				<table id="coexpression__table" class="table--dense hidden">
					<thead>
						<tr>
							<th scope="col">ID</th>
							<th scope="col">Score</th>
							<th scope="col">Description</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			<?php } ?>
		</div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js" integrity="sha384-gOxMGMgqQH8iYyQE8rmgpaokSRE608gSIXXdC2a/yT+OywUqbNmTCQa3qNO4wvyc" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/colorbrewer.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/plugins/d3-tip.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/stupidtable/0.0.1/stupidtable.min.js" integrity="sha384-i1hs1xV885ynDTpLx248kPSNT7iUxg+8qxUH4P5Sm/5G8WDsvIHTh05JlnYudqPl" crossorigin="anonymous"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/view/gene.min.js"></script>
</body>
</html>