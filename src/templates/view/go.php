<?php
	require_once('../config.php');

	try {
		if(!empty($_GET) && !empty($_GET['id'])) {
			$id = $_GET['id'];
			if(!preg_match('/^(GO:)?\d+$/', $id)) {
				// If ID fails pattern check
				$_SESSION['view_error'] = 'Invalid gene ontology identifier detected. Please ensure that your gene ontology identifier follows the format <code>[GO:]\d+</code>, where the "GO:" suffix is optional, followed by one or more digits.';
				throw new Exception;
			} else if(!preg_match('/^GO:(.*)$/', $id)) {
				$id = 'GO:'.str_pad($id, 7, '0', STR_PAD_LEFT);
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
	<title>Gene Ontology &mdash; View &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/view.min.css" type="text/css" media="screen" />
</head>
<body class="view go init-scroll--disabled">

	<?php
		$header = new \LotusBase\PageHeader();
		echo $header->get_header();
	?>

	<?php echo get_breadcrumbs(array('custom_breadcrumb' => array(
		'View' => WEB_ROOT.'/view',
		'Gene Ontology' => WEB_ROOT.'/view/go',
		$id => WEB_ROOT.'/view/go/'.$id
	))); ?>

	<section class="wrapper">
		<h2><?php echo $id; ?></h2>
		<div id="view__card" class="view__facet">
			<h3>Overview</h3>
			<?php
				try {
					$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

					$q1 = $db->prepare("SELECT
							go.GO_ID AS Term,
							go.Namespace AS Namespace,
							go.Definition AS Definition,
							go.ExtraData AS ExtraData,
							go.SubtermOf AS SubtermOf,
							go.Relationships AS Relationships,
							go.URL AS URL,
							go_lotus.Description AS Description
						FROM gene_ontology AS go
						LEFT JOIN gene_ontology_lotus AS go_lotus ON (
							go.GO_ID = go_lotus.GO_ID
						)
						LEFT JOIN interpro_go_mapping AS ip_go ON (
							go.GO_ID = ip_go.GO_ID
						)
						LEFT JOIN domain_predictions AS dompred ON (
							ip_go.InterPro_ID = dompred.InterProID
						)
						WHERE go.GO_ID = ?
						GROUP BY go.GO_ID");
					$q1->execute(array($id));

					$go_data = $q1->fetch(PDO::FETCH_ASSOC);
					$go_namespace = array(
						'p' => 'Biological process',
						'f' => 'Molecular function',
						'c' => 'Cellular component'
						);
					?>
					<table class="table--dense">
						<thead>
							<tr>
								<th scope="col">Field</th>
								<th scope="col">Value</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<th scope="row">Namespace</th>
								<td><?php echo $go_namespace[$go_data['Namespace']]; ?></td>
							</tr>
							<tr>
								<th scope="row">Short description</th>
								<td><?php echo $go_data['Description']; ?></td>
							</tr>
							<tr>
								<th scope="row">Full defintion</th>
								<td><?php echo $go_data['Definition']; ?></td>
							</tr>
							<tr>
								<th scope="row">Subterm of</th>
								<td><?php
									if($go_data['SubtermOf']) {
										echo '<ul class="list--floated">';
										foreach(json_decode($go_data['SubtermOf'], true) as $go_parent) {
											echo '<li><a href="'.WEB_ROOT.'/view/go/'.$go_parent.'" class="link--reset">'.$go_parent.'</a></li>';
										}
										echo '</ul>';
									} else {
										echo 'Not a subterm/child of another GO identifier.';
									}
								?></td>
							</tr>
						</tbody>
					</table>
					<?php

					$q2 = $db->prepare("SELECT
						anno.Gene AS Transcript,
						anno.Annotation AS Description,
						anno.LjAnnotation AS LotusName
					FROM annotations AS anno
					LEFT JOIN domain_predictions AS dompred ON (
						anno.Gene = dompred.Transcript
					)
					LEFT JOIN interpro_go_mapping AS ip_go ON (
						dompred.InterProID = ip_go.InterPro_ID
					)
					LEFT JOIN gene_ontology AS go ON (
						ip_go.GO_ID = go.GO_ID
					)
					WHERE go.GO_ID = ?
					GROUP BY anno.Gene");
					$q2->execute(array($id));


				} catch(PDOException $e) {
					echo '<p class="user-message warning">We have encountered an error with querying the database: '.$e->getMessage().'.</p>';
				} catch(Exception $e) {
					echo '<p class="user-message warning">'.$e->getMessage().'.</p>';
				}
			?>
		</div>

		<div id="view__transcript" class="view__facet">
			<h3>Associated <em>Lotus</em> transcripts<?php
				if($q2->rowCount()) {
					echo ' <span class="badge">'.$q2->rowCount().'</span>';
				}
			?></h3>
			<?php if($q2->rowCount()) { ?>
			<table class="table--dense">
				<thead>
					<tr>
						<th scope="col">Transcript</th>
						<th scope="col">Name</th>
						<th scope="col">Description</th>
					</tr>
				</thead>
				<tbody><?php while($t = $q2->fetch(PDO::FETCH_ASSOC)) { ?>
					<tr>
						<td><div class="dropdown button">
							<span class="dropdown--title"><a href="<?php echo WEB_ROOT.'/view/transcript/'.$t['Transcript']; ?>"><?php echo $t['Transcript']; ?></a></span>
							<ul class="dropdown--list">
								<li><a href="<?php echo WEB_ROOT.'/view/transcript/'.$t['Transcript']; ?>" title="View transcript"><span class="icon-eye">View transcript</span></a></li>
								<li><a href="<?php echo WEB_ROOT.'/lore1/search-exec?gene='.$t['Transcript'].'&amp;v=3.0'; ?>" title="Search for LORE1 insertions in this transcript"><span class="pictogram icon-leaf"><em>LORE1</em> v3.0</span></a></li>
								<li><a href="<?php echo WEB_ROOT.'/genome?loc='.$t['Transcript']; ?>" title="View in genome browser"><span class="icon-book">Genome browser</span></a></li>
								<li><a href="<?php echo WEB_ROOT.'/expat?ids='.$t['Transcript']; ?>" title="Access expression data from the Expression Atlas"><span class="icon-map">Expression Atlas (ExpAt)</span></a></li>
							</ul>
						</td>
						<td><?php echo $t['LotusName'] ? $t['LotusName'] : '&ndash;'; ?></td>
						<td><?php echo preg_replace('/\[([\w\s]+)\]?/', '[<em>$1</em>]', $t['Description']); ?></td>
					</tr>
				<?php } ?></tbody>
			</table>
			<?php } else {
				echo '<p class="user-message warning">No transcripts are associated with this gene ontology identifier.</p>';
				throw new Exception('');
			} ?>
		</div>

		<?php
			if($go_data['ExtraData'] || $go_data['URL']) {
				$extra_data = json_decode($go_data['ExtraData'], true);
				if(count($extra_data) || $go_data['URL']) {
				?>
				<div id="view__extra-data" class="view__facet">
					<h3>Additional data</h3>
					<p>This table contains additional metadata associated with the <abbr title="Gene Ontology">GO</abbr> entry's definition field.</p>
					<table class="table--dense display compact">
						<thead>
							<tr>
								<th scope="col">Field</th>
								<th scope="col">Value</th>
							</tr>
						</thead>
						<tbody><?php
							foreach($extra_data as $key => $value) {
								echo '<tr><th scope="row">'.$key.'</th><td>'.$value.'</td></tr>';
							}
							if($go_data['URL']) {
								echo '<tr><th scope="row">URL</th><td>'.$go_data['URL'].'</td></tr>';
							}
						?></tbody>
					</table>
				</div>
				<?php }
			}
		?>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/stupidtable/0.0.1/stupidtable.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/view/go.min.js"></script>
</body>
</html>