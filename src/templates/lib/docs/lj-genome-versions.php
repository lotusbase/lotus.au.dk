<?php require_once('../../config.php'); ?>
<p>There are currently <?php echo count($lj_genome_versions).' '.pl($lj_genome_versions, 'version'); ?> of the <em>Lotus japonicus</em> reference genome:</p>
<ul>
<?php
	foreach($lj_genome_versions as $v) {
		echo '<li>v'.$v.'</li>';
	}
?>
</ul>