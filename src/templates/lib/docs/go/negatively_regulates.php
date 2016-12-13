<?php require_once('../../../config.php'); ?>
<p>The <em>regulates</em> relation has two sub-relations, <em>positively regulates</em> and <em>negatively regulates</em>, to represent these more specific forms of regulation. This means that if B <em>negatively regulates</em> X, it is true to say that B <em>regulates</em> X.</p>
<figure>
	<img src="<?php echo WEB_ROOT.'/dist/images/go/positively-negatively-regulates.gif'; ?>" alt="regulates" title="regulates" />
	<figcaption>A <em>positively regulates</em> X, so it also <em>regulates</em> X; B <em>negatively regulates</em> X, so it also <em>regulates</em> X.</figcaption>
</figure>
<p>The Gene Ontology uses generic "regulation of &hellip;" terms to encompass anything that regulates a process or quality; specific examples of regulation&mdash;for example, regulation of skeletal muscle contraction by calcium ion signaling, or activation of innate immune response&mdash;have an <em>is a</em> relationship to these generic regulation terms.</p>