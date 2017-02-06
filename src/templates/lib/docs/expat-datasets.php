<?php
	require_once('../../config.php');

	try {

		// Database connection
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		// Query 1: Collect all PMID
		$q1 = $db->prepare('SELECT PMID, IntranetOnly FROM expat_datasets GROUP BY PMID');
		$q1->execute();

		if(!$q1->rowCount()) {
			throw new Exception('No rows returned.');
		} else {

			$pmids = array();
			while($row = $q1->fetch(PDO::FETCH_ASSOC)) {
				if(!!$row['IntranetOnly'] === true && !is_allowed_access('/expat/')) {
					continue;
				}
				$pmids[] = $row['PMID'];
			}

			$refHandler = new \LotusBase\Getter\PMID;
			$refHandler->set_pmid($pmids);
			$refs = $refHandler->get_data();
		}
		

		// Query 2: Get actual data
		$q2 = $db->prepare('SELECT `Text`, IDtype, Description, CORNEA, PMID, IntranetOnly, Curators FROM expat_datasets');
		$q2->execute();

		// Check results
		if($q2->rowCount()) {

			echo '<table>
	<thead>
		<tr>
			<th>Dataset</th>
			<th>ID type</th>
			<th>Description</th>
			<th>Curation</th>
			<th>Reference</th>
		</tr>
	</thead>
	<tbody>';

			// Iterate through each row
			while($row = $q2->fetch(PDO::FETCH_ASSOC)) {

				// For rows that are marked for intranet only, check if user is allowed to view it
				if(!!$row['IntranetOnly'] === true && !is_allowed_access('/expat/')) {
					continue;
				}

				// Curators
				$curators = array_filter(explode(',', $row['Curators']));

				// Construct reference
				if(!empty($row['PMID'])) {
					$ref = $refs[$row['PMID']];

					// Reference link
					$_articleids = $ref['articleids'];
					$doi = false;
					foreach ($_articleids as $ai) {
						if($ai['idtype'] === 'doi') {
							$doi = $ai['value'];
						}
					}

					// Reference authors
					$_authors = $ref['authors'];
					if(count($_authors) !== 2) {
						$authors = $_authors[0]['name'].' et al.';
					} else {
						$authors = implode(' and ', array_map(function($a) {
							return $a['name'];
						}, $_authors));
					}

					// Publication year
					$year = DateTime::createFromFormat('Y/m/d G:i', $ref['sortpubdate'])->format('Y');
				}

				echo '<tr>
					<th>'.$row['Text'].'</th>
					<td>'.preg_replace(
						array(
							'/geneid/',
							'/probeid/',
							'/transcriptid/'
							),
						array(
							'Gene ID',
							'Probe ID',
							'Transcript ID'
							),
						$row['IDtype']).'</td>
					<td><p>'.$row['Description'].'</p>'.(!!$row['CORNEA'] !== true ? '<p class="user-message warning"><span class="icon-attention"></span>This dataset is not available for CORNEA because of the low number of conditions available.</p>' : '').'</td>
					<td>'.(count($curators) ? '<ul class="list--reset"><li>'.implode('</li><li>', $curators).'</li></ul>' : '').'</td>
					<td>'.(!empty($row['PMID']) ? '<a href="'.(!empty($doi) ? 'https://doi.org/'.$doi : 'https://www.ncbi.nlm.nih.gov/pubmed/'.$row['PMID']).'" title="'.$ref['title'].'">'.$authors.', '.$year.'</a>' : 'Unpublished').'</td>
				</tr>';
			}

			echo '</tbody></table>';

		} else {
			throw new Exception('No rows returned.');
		}

	} catch(PDOexception $e) {
		echo '<p class="user-message">We have encountered an error: </p>'.$e->getMessage();
	} catch(Exception $e) {
		echo '<p class="user-message">We have encountered an error: </p>'.$e->getMessage();
	}

?>