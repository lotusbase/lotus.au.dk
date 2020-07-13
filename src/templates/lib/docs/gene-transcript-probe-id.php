<?php require_once('../../config.php'); ?>
<p>It depends on what kind of data do you have in your hands:</p>
<table>
	<thead>
		<tr><th>Query ID type</th><th>Description</th><th>Example</th><th>Regex pattern</th></tr>
	</thead>
	<tbody>
		<tr>
			<th>Gene</th>
			<td>It should be the assigned gene ID with respect to the <strong>MG20 v3.0</strong> or <strong>Gifu v1.1</strong> of the <em>Lotus japonicus</em> protein database.</td>
			<td>
				<code>Lj4g3v0281040</code>
				<br /><em>or</em><br />
				<code>LotjaGi4g1v0024900</code>
			</td>
			<td>
				<code class="regex-colorize">/^Lj([0-6]|chloro|mito|1_4_FUCT|XYLT)g\dv\d+$/i</code>
				<br /><em>or</em><br />
				<code class="regex-colorize">/^LotjaGi\dg\dv\d+?(_LC)?$/i</code>
			</td>
		</tr>
		<tr>
			<th>Transcript</th>
			<td>It should be the assigned gene ID with respect to the <strong>MG20 v3.0</strong> or <strong>Gifu v1.1</strong> of the <em>Lotus japonicus</em> protein database. Note the trailing period and digit, which indicates the specific isoform, i.e. <code>.1</code>, <code>.2</code> and etc.</td>
			<td>
				<code>Lj4g3v0281040.1</code>
				<br /><em>or</em><br />
				<code>LotjaGi4g1v0024900.1</code>
			</td>
			<td>
				<code class="regex-colorize">/^Lj([0-6]|chloro|mito|1_4_FUCT|XYLT)g\dv\d+(\.(mrna)?\d+)?$/i</code>
				<br /><em>or</em><br />
				<code class="regex-colorize">/^LotjaGi\dg\dv\d+?(_LC)?\.\d+$/i</code>
			</td>
		</tr>
		<tr>
			<th>Probe</th>
			<td>It should be in the format used by the <a href="http://ljgea.noble.org/v2/">Samuel Roberts Noble Foundation.</a></td>
			<td>
				<code>Ljwgs_028612.1.1_at</code>
				<br /><em>or</em><br />
				<code>chr6.CM0836.50_at</code>
			</td>
			<td><code class="regex-colorize">/^(Ljwgs\_|LjU|Lj\_|chr[0-6]\.|gi|m[a-z]{2}|tc|tm|y4|rngr|cm).+\_at$/i</code></td>
		</tr>
	</tbody>
</table>
<p class="user-message note">If you want to map your probe ID to gene ID (as different datasets uses different identifiers), you can use the <a href="<?php echo WEB_ROOT; ?>/expat/mapping">mapping tool</a>).</p>