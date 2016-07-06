<h2>What am I searching against?</h2>
<p>There are three possible ways that you can search for a gene&mdash;and our algorithm intelligently tries to decide the best way your query can be used to look for entries in our database:</p>
<h3>Gene/Transcript ID</h3>
<p>A query can be any identifier of a <em>Lotus</em> gene: gene ID comes in the format of <code>Lj[chr]g[n]v[i]</code>, where <code>chr</code> is the chromosome identifier, <code>n</code> is the version number and <code>i</code> is the unique ID—e.g. <code>Lj3g3v3639920</code>, a topoisomerase gene.</p><p>A transript ID is a unique identifier of a <em>Lotus</em> transcript, and comes in the format of <code>Lj[chr]g[n]v[i].[j]</code>, where <code>j</code> is the isoform identifier—e.g. <code>Lj3g3v3639920.1</code></p>
<h3>Annotation</h3>
<p>Inferred gene annotation from other organisms are also possible. Searching for <code>topoisomerase</code> will bring back all genes that contain the keyword in their inferred gene names.</p>
<h3><em>Lotus</em> gene name</h3>
<p>As more and more researchers work with <em>Lotus</em> genes, we are able to functionally verify some genes and assigned official <em>Lotus</em>-based naming to them, for example, <code>LjFls2</code> for <code>Lj4g3v0281040.1</code>.</p>
<h2>How to formulate better annotation keyword search</h2>
<p>We are using a MySQL <code>FULLTEXT</code> index that facilitates a performant fulltext search of gene annotations. For advanced users, we are using <a href="http://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html">boolean search mode</a> for searching&mdash;you may use special operators to modify the specificity of your search. For example:</p>
<table>
	<thead>
		<tr>
			<th>Query example</th>
			<th>Layman's description</th>
			<th>Technical explanation</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><code>ATP&nbsp;synthase</code></td>
			<td>Rows returned must contain "ATP" or "synthase", or both.</td>
			<td>When unprefixed with <code>+</code> or <code>-</code>, the word is optional and the rows returned have to satisfy one or more words. However, rows that contain all terms are rated higher.</td>
		</tr>
		<tr>
			<td><code>+ATP&nbsp;synthase</code></td>
			<td>Rows returned must contain "ATP", but the word "synthase" is optional.</td>
			<td>The leading <code>+</code> indicates that the word <strong>must be present</strong> in each row that is returned.</td>
		</tr>
		<tr>
			<td><code>-ATP&nbsp;synthase</code></td>
			<td>Rows returned must <strong><em>not</em></strong> contain "ATP", and the word "synthase" is optional.</td>
			<td>The leading <code>-</code> indicates that the word <strong>must <em>not</em> be present</strong> in each row that is returned.</td>
		</tr>
		<tr>
			<td><code>~ATP&nbsp;synthase</code></td>
			<td>Rows returned must may contain "synthase" (optional), and those that contain "ATP" too are ranked lower than those that do not. This is a <strong>softer</strong> selection criteria than the <code>-</code> sign, which will remove rows that contain the "ATP" keyword.</td>
			<td>The leading <code>~</code> indicates rows containing the word should be ranked lower&mdash;its contribution to the ranking of the row is negative.</td>
		</tr>
		<tr>
			<td><code>&gt;ATP&nbsp;synthase</code></td>
			<td>Returns rows that contain "ATP" or "synthase", but rows that contain "ATP" are ranked higher.</td>
			<td>The leading <code>&gt;</code> increases the contribution of the word to the row's ranking.</td>
		</tr>
		<tr>
			<td><code>&lt;ATP&nbsp;synthase</code></td>
			<td>Returns rows that contain "ATP" or "synthase", but rows that contain "ATP" are ranked lower.</td>
			<td>The leading <code>&lt;</code> decreases the contribution of the word to the row's ranking, but the contribution is still positive (unlike <code>~</code>, which assigns <strong>negative</strong> contribution.</td>
		</tr>
		<tr>
			<td><code>ATP*</code></td>
			<td>Return rows that contain words that matches "ATP&hellip;", such as "ATPase", "ATP-dependent" and "ATP-binding", but not "ADP".</td>
			<td>The <code>*</code> is used as a wildcard.</td>
		</tr>
		<tr>
			<td><code>"ATP&nbsp;synthase"</code></td>
			<td>Searches for the rows that contains the <strong>exact phrase</strong> "ATP synthase". You should wrap your queries in double quotes if they contain special characters, such as <code>-</code>. For example, <code>ATP-binding</code> will not return the result as intended, but <code>"ATP-binding"</code> will.</td>
			<td>The double quotes are used to specify specific phrases that must be present in rows returned.</td>
		</tr>
		<tr>
			<td><code>+ATP +(&gt;helicase &lt;synthase)</code></td>
			<td>Rows must contain "ATP", in conjunction with either "helicase" or "synthase" (but with different contributes to the row's relevance). Rows that contain "ATP" and "helicase" are ranked higher than rows containing "ATP" and "synthase".</td>
			<td>The use of parentheses <code>(&nbsp;)</code> group words into subexpressions. Parenthesized groups can be nested.</td>
		</tr>
	</tbody>
</table>
<p class="user-message note">Since the <em>L. japonicus</em> gene annotations are inferred from other organisms, it might be incomplete. If you have suggestions, rectification or modifications you want to make to the annotation(s), please <a href="'.WEB_ROOT.'/issues">open a ticket</a>.</p>