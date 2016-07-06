<?php
	require_once('../../config.php');
?>
<form class="modal__form" id="manual-gene-anno-form">
	<p><em>Lotus</em> Base is a community driven site&mdash;you may submit suggestions of gene names. Submissions are manually checked and curated by the <em>Lotus</em> Base team, and therefore may take up to a week to appear in the database. Fields marked with an asterisk are compulsory.</p>
	<div class="cols">
		<label for="annotation-user-email" class="col-one">Email <span class="asterisk" title="Required Field">*</span></label>
		<input id="annotation-user-email" name="annotation_user_email" type="email" class="col-two" placeholder="Your email" required />

		<label for="annotation-gene" class="col-one">Gene name <span class="asterisk" title="Required Field">*</span></label>
		<input id="annotation-gene" name="annotation_gene" type="text" class="col-two" placeholder="Suggested name for gene" required />

		<label for="annotation-literature" class="col-one">Literature</label>
		<div class="col-two">
			<textarea id="annotation-literature" name="annotation_literature" placeholder="Literature references to support the naming of this gene" maxlength="1024"></textarea>
		</div>
	</div>
</form>