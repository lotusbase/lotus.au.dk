<?php
	// Important global variables
	// Lotus genome versions
	$lj_genome_versions = array(
		'MG20 v2.5' => array(
			'ecotype' => 'MG20',
			'version' => '2.5'
		),
		'MG20 v3.0' => array(
			'ecotype' => 'MG20',
			'version' => '3.0'
		),
		'Gifu v1.1' => array(
			'ecotype' => 'Gifu',
			'version' => '1.1'
		)
	);

	// LORE1 database columns that are used for sorting
	$lore1_order_columns = array('PlantID', 'Position', 'Chromosome');

	// Export/download file types
	$export_file_types = array('csv', 'tsv');
?>