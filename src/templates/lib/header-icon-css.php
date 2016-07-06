<?php
	if(extension_loaded('zlib')) {
		ob_start('ob_gzhandler');
	}

	header("Content-type: text/css; charset: UTF-8");

	for ($i=0; $i < 64; $i++) { 
		echo '#header .header-content svg.lotusbase-logo .hex-parts:nth-child('.($i + 1).') {
			transition-delay: '.(0.01 * mt_rand(1,25)).'s
		}';
	}

	if(extension_loaded('zlib')) {
		ob_end_flush();
	}
?>