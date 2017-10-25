<form action="#" method="GET" id="searchform__lore1" class="has-group">
	<div class="cols has-legend" role="group">
		<span class="user-message full-width minimal legend">Genome version</span>
		<p class="full-width">Select a <em>Lotus japonicus</em> genome version to map against.</p>
		<p class="full-width user-message"><span class="icon-attention"></span>It is <strong>not possible to query against multiple genome versions at once</strong>, as the genomic coordinates of <em>LORE1</em> insertions, as well as the genomic positions of genes (if the insertions are genic), vary among versions.</p>
		<label for="genome-version" class="col-one">Genome version <a href="<?php echo WEB_ROOT.'/lib/docs/lj-genome-versions';?>" class="info" data-modal="search-help" title="What does the genome version mean?">?</a></label>
		<div class="col-two field__version">
			<select name="v" id="genome-version">
				<option value="" <?php echo (isset($input['v']) && empty($input['v'])) ? "selected" : ""; ?>>Select genome version</option>
				<?php foreach($lj_genome_versions as $v) {
					echo '<option value="'.$v.'" '.(isset($input['v']) && !empty($input['v']) && strval($input['v']) === $v ? 'selected' : '').'>v'.$v.'</option>';
				} ?>
			</select>
		</div>
	</div>

	<div class="cols has-legend" role="group">
		<span class="user-message full-width minimal legend">Query</span>
		<label for="plantid-input" class="col-one">PlantID <a class="info" data-modal="search-help" data-modal-content="It's easy. Simply type away! Whenever you enter a whitespace character or a commonly-used delimiter, i.e. <kbd>tab</kbd>, <kbd>spacebar</kbd> or <kbd>comma</kbd>, the plant ID will be accepted and you can move on to type the next plant ID. You may also copy and paste space- or comma-delimited plant IDs from a file (text file, csv, Excel) and we will parse the plant IDs for you." title="How should I enter my Plant ID?">?</a></label>
		<div class="col-two field__plant-id">
			<div class="multiple-text-input input-mimic">
				<ul class="input-values">
				<?php
					if(isset($input['pid']) && !empty($input['pid'])) {
						$pid_array = explode(',', $input['pid']);
						foreach($pid_array as $pid_item) {
							$pid_item = preg_replace('/^DK\d{2}\-0(3\d{7})$/', '$1', $pid_item);
							echo '<li data-input-value="'.escapeHTML($pid_item).'">'.escapeHTML($pid_item).'<span class="icon-cancel" data-action="delete"></span></li>';
						}
					}
				?>
					<li class="input-wrapper"><input type="text" id="plantid-input" placeholder="Plant ID (e.g. 30000146)" autocomplete="off" /></li>
				</ul>
				<input class="input-hidden search-param" type="hidden" name="pid" id="plantid" value="<?php echo (!empty($input['pid'])) ? escapeHTML($input['pid']) : ''; ?>" readonly />
			</div>
			<small><strong>Separate each PlantID with a comma, space or tab.</strong></small>
		</div>

		<div class="separator full-width"><span>or</span></div>

		<label for="blastheader-input" class="col-one">BLAST header <a class="info" data-modal="search-help" data-modal-content="It is in the format of &lt;code&gt;[chromosome number]_[position]_[orientation]&lt;/code&gt;:&lt;br /&gt;For example: &lt;code&gt;chr5_3085263_R&lt;/code&gt; or &lt;code&gt;LjSGA_055002_657_R&lt;/code&gt;&lt;/p&gt;&lt;p&gt;If this BLAST header fields are filled in, all other fields below will be ignored because they contain redundant information." title="What is the blast header?">?</a></label>
		<div class="col-two field__blast-header">
			<div class="multiple-text-input input-mimic">
				<ul class="input-values">
				<?php
					if(isset($input['blast']) && !empty($input['blast'])) {
						$blast_array = explode(',', $input['blast']);
						foreach($blast_array as $blast_item) {
							echo '<li data-input-value="'.escapeHTML($blast_item).'">'.escapeHTML($blast_item).'<span class="icon-cancel" data-action="delete"></span></li>';
						}
					}
				?>
					<li class="input-wrapper"><input type="text" name="blast-input" id="blastheader-input" placeholder="BLAST Header (e.g. chr5_3085263_R or LjSGA_055002_657_R)" autocomplete="off" /></li>
				</ul>
				<input class="input-hidden search-param" type="hidden" name="blast" id="blastheader" value="<?php echo (!empty($input['blast'])) ? escapeHTML($input['blast']) : ''; ?>" readonly />
			</div>
			<small><strong>Separate each BLAST header with a comma, space or tab. BLAST search terms will override all other filtering parameters below.</strong></small>
		</div>
	</div>

	<div class="cols has-legend" role="group" id="lore1-search__filtering">
		<span class="user-message full-width minimal legend">Filtering</span>
		<p class="minimal full-width">The following options can be used to fine-tune your query. Note that queries using BLAST headers will override this fieldset.</p>
		<label for="gene" class="col-one">Gene ID <a class="info" data-modal="search-help" data-modal-content="Gene ID search utilizes an exact match&mdash;in other words, you cannot enter a partial Gene ID and expect results to be returned. The gene ID should be in the format of: &lt;codeLj[Chromosome][GenomeVersion][ID Number]&gt;&lt;/code&gt;, e.g. &lt;code&gt;Ljchlorog3v0000610&lt;/code&gt;, &lt;code&gt;Lj1g3v2671450&lt;/code&gt;. There should not be any period in the gene ID, e.g. &lt;code&gt;Ljchlorog3v0000610.1&lt;/code&gt;" title="How should I enter the gene ID?">?</a></label>
		<div class="col-two field__gene-id">
			<input type="text" name="gene" id="gene" class="search-param" placeholder="Gene ID (exact match)" value="<?php echo (!empty($input['gene'])) ? escapeHTML($input['gene']) : ''; ?>" />
		</div>

		<label for="chromosome" class="col-one">Chromosome</label>
		<div class="col-two field__chromosome">
			<select name="chr" id="chromosome" class="search-param">
				<option value="" selected="selected">Select chromosome</option>
				<option value="chr0" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr0') ? 'selected' : ''; ?>>Chromosome 0</option>
				<option value="chr1" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr1') ? 'selected' : ''; ?>>Chromosome 1</option>
				<option value="chr2" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr2') ? 'selected' : ''; ?>>Chromosome 2</option>
				<option value="chr3" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr3') ? 'selected' : ''; ?>>Chromosome 3</option>
				<option value="chr4" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr4') ? 'selected' : ''; ?>>Chromosome 4</option>
				<option value="chr5" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr5') ? 'selected' : ''; ?>>Chromosome 5</option>
				<option value="chr6" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr6') ? 'selected' : ''; ?>>Chromosome 6</option>
				<option value="mito" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'mito') ? 'selected' : ''; ?>>Mitochondrion</option>
				<option value="chloro" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chloro') ? 'selected' : ''; ?>>Chlorophyll</option>
				<option value="chr8" <?php echo (isset($_GET['chr']) && $_GET['chr'] === 'chr8') ? 'selected' : ''; ?>>Plastid</option>
			</select>
		</div>

		<label class="col-one">Position <a class="info" data-modal="search-help" data-modal-content="&lt;em&gt;LORE1&lt;/em&gt; inserts can be searched between two positions (inclusive). However: &lt;ul&gt;&lt;li&gt;if only one position is filled in, the search will only look for an exact position, or...&lt;/li&gt;&lt;li&gt;if nothing is filled in, search will not look for lines based on positions.&lt;/li&gt;&lt;/ul&gt;" title="How to search for the position of &lt;em&gt;LORE1&lt;/em&gt; insert?">?</a></label>
		<div class="col-two cols flex-wrap__nowrap field__positions">
			<label for="pos1">Between</label> <input type="number" name="pos1" id="pos1" class="search-param" placeholder="Start " value="<?php echo (!empty($input['pos1'])) ? escapeHTML($input['pos1']) : ''; ?>" min="0" /> <label for="pos2">and</label> <input type="number" name="pos2" id="pos2" class="search-param" placeholder="End" value="<?php echo (!empty($input['pos2'])) ? escapeHTML($input['pos2']) : ''; ?>" min="0" />
		</div>
	</div>

	<div class="cols has-legend" role="group">
		<span class="user-message full-width minimal legend">Display options</span>

		<label for="rowcount" class="col-one">Rows <a class="info" data-modal="search-help" data-modal-content="You can determine how many results to display per page. This value defaults to &lt;strong&gt;100&lt;/strong&gt;." title="What do the row numbers mean?">?</a></label>
		<div class="col-two">
			<select name="n" id="rowcount">
				<option value="25" <?php echo (isset($input['n']) && intval($input['n']) === 25) ? "selected" : ""; ?>>25</option>
				<option value="50" <?php echo (isset($input['n']) && intval($input['n']) === 50) ? "selected" : ""; ?>>50</option>
				<option value="100" <?php echo (isset($input['n']) && (empty($input['n']) || intval($input['n']) == 100)) ? "selected" : ""; ?>>100</option>
				<option value="200" <?php echo (isset($input['n']) && intval($input['n']) === 200) ? "selected" : ""; ?>>200</option>
			</select>
		</div>

		<label for="orderby" class="col-one">Order by</label>
		<div class="col-two">
			<select name="ord" id="orderby">
				<option value="PlantID" <?php echo (isset($input['ord']) && (empty($input['ord']) || $input['ord'] == "PlantID")) ? "selected" : ""; ?>>Plant ID</option>
				<option value="Position" <?php echo (isset($input['ord']) && $input['ord'] === "Position") ? "selected" : ""; ?>>Position</option>
				<option value="Chromosome" <?php echo (isset($input['ord']) && $input['ord'] === "Chromosome") ? "selected" : ""; ?>>Chromosome</option>
			</select>
		</div>
	</div>

	<button type="submit"><span class="icon-search">Search for <em>LORE1</em> lines</span></button>
</form>