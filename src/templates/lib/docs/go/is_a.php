<?php require_once('../../../config.php'); ?>
<p>The <em>is a</em> relation forms the basic structure of GO. If we say A <em>is a</em> B, we mean that node A <em>is a subtype of</em> node B. For example, mitotic cell cycle <em>is a</em> cell cycle, or lyase activity <em>is a</em> catalytic activity.</p>
<p>It should be noted that <em>is a</em> does not mean ‘is an instance of’. An ‘instance’, ontologically speaking, is a specific example of something; e.g. a cat <em>is a</em> mammal, but Garfield is an <em>instance</em> of a cat, rather than a subtype of cat. GO, like most ontologies, does not use instances, and the terms in GO represent a class of entities or phenomena, rather than specific manifestations thereof. However, if we know that cat <em>is a</em> mammal, we can say that every instance of cat <em>is a</em> mammal.</p>
<p>The is a relation is transitive, which means that if A <em>is a</em> B, and B <em>is a</em> C, we can infer that A is a C.</p>
<figure>
	<img src="<?php echo WEB_ROOT.'/dist/images/go/is_a-eg.gif'; ?>" alt="is_a" title="is_a" />
	<figcaption>For example: mitochondrion is an intracellular organelle and intracellular organelle is an organelle therefore mitochondrion is an organelle.</figcaption>
</figure>