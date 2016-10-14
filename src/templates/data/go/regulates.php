<?php require_once('../../config.php'); ?>
<p>Another common relationship in the Gene Ontology is that where one process directly affects the manifestation of another process or quality, i.e. the former <em>regulates</em> the latter. The target of the regulation may be another process&mdash;for example, regulation of a pathway or an enzymatic reaction&mdash;or it may be a quality, such as cell size or pH. Analogously to part of, this relation is used specifically to mean <em><strong>necessarily</strong> regulates</em>: if both A and B are present, B <em>always regulates</em> A, but A may not always be regulated by B.</p>
<figure>
	<img src="<?php echo WEB_ROOT.'/dist/images/go/regulates.gif'; ?>" alt="regulates" title="regulates" />
	<figcaption>i.e. <em>all</em> B <em>regulate</em> A; <em>some</em> A <em>regulated</em> by B.</figcaption>
</figure>
<figure>
	<img src="<?php echo WEB_ROOT.'/dist/images/go/regulates-eg.gif'; ?>" alt="regulates" title="regulates" />
	<figcaption>For example: Whenever a cell cycle checkpoint occurs, it always <em>regulates</em> the cell cycle. However, the cell cycle is <strong>not solely <em>regulated by</em></strong> cell cycle checkpoints; there are also other processes that regulate it.</figcaption>
</figure>