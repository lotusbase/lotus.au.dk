<?php require_once('../../config.php'); ?>
<p>The relation <em>part of</em> is used to represent part-whole relationships in the Gene Ontology. <em>part of</em> has a specific meaning in GO, and a <em>part of</em> relation would only be added between A and B if B is <em><strong>necessarily</strong> part of</em> A: wherever B exists, it is as part of A, and the presence of the B implies the presence of A. However, given the occurrence of A, we cannot say for certain that B exists.</p>
<figure>
	<img src="<?php echo WEB_ROOT.'/dist/images/go/part_of.gif'; ?>" alt="part_of" title="part_of" />
	<figcaption>i.e. <em>all</em> B are part of A; <em>some</em> A <em>have part</em> B.</figcaption>
</figure>
<figure>
	<img src="<?php echo WEB_ROOT.'/dist/images/go/part_of-eg.gif'; ?>" alt="part_of" title="part_of" />
	<figcaption>For example: Replication fork is <em>necessarily part of</em> chromosome: <em>all</em> replication forks are <em>part of</em> some chromosome, but only <em>some</em> chromosomes <em>have part</em> replication fork.</figcaption>
</figure>