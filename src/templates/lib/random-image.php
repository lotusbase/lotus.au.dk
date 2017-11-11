<?php
	// Whitelist of folders
	$dir_list = array('header/hero', 'hero');

	// Check if type is declared
	if(isset($_GET) && isset($_GET['type']) && in_array($_GET['type'], $dir_list)) {
		$dir = $_GET['type'];
	} else {
		$dir = 'header';
	}
	$imagesDir = '../dist/images/'.$dir.'/';
	$images = glob($imagesDir . '*.{jpg,jpeg}', GLOB_BRACE);
	$randomImage = $images[array_rand($images)];
	header("Content-type: image/jpeg");
	echo file_get_contents($randomImage);
	exit();
?>