<?php
	require_once('../config.php');
	require_once(DOC_ROOT.'/lib/classes.php');

	$dbs = new \LotusBase\BLAST\DBMetadata();
	$db_metadata = $dbs->get_metadata();

	echo '<p>The databases that are available for searching are organized into several groups for ease of use:</p>
	<table>
		<thead>
			<tr>
				<th>Name</th>
				<th>Category</th>
				<th>Type</th>
				<th>Description</th>
				<th>Last updated</th>
				<th>Base Count</th>
				<th>Sequence Count</th>
			</tr>
		</thead>
		<tbody>';

	foreach($db_metadata as $db_name => $db) {
		echo '<tr>';
		echo '<td>'.$db['title'].'</td>';
		echo '<td>'.$db['category'].'</td>';
		echo '<td>'.$db['molecular_type'].'</td>';
		echo '<td>'.$db['description'].'</td>';
		echo '<td>'.$db['last_updated'].'</td>';
		echo '<td>'.number_format($db['base_count']).'</td>';
		echo '<td>'.number_format($db['sequence_count']).'</td>';
		echo '</tr>';
	}

	echo '</tbody><table>';
?>