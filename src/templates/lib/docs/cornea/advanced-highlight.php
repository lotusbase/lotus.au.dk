<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
?>
<p>The accepted file format for advanced node highlighting in <code>CSV</code>. Fields have to be separated by commonly used CSV file separators such as <code>,</code>, <code>;</code> or a tab character, and optionally enclosed by double quotes <code>&quot;&quot;</code>. To ensure that your CSV file will be parsed properly, please make sure that:</p>
<ul>
	<li>the first column is the identifier&mdash;it can be a gene, transcript or probe ID. The values in this column must be unique. If you have provided a transcript ID (e.g. <code>Lj4g3v0281040.1</code> but the network is generated based on gene ID (e.g. <code>Lj4g3v0281040</code>), we will automatically perform trimming.</li>
	<li>the second column is the group that you want to place the gene in. For genes in the same group, use the same value&mdash;this value can be completely arbitrary. This value can be used to arbitrarily group the highlighted nodes, e.g. genes involved in nodulation in a group, and genes involves in ethylene signalling in another. This column is optional&mdash;if not filled, we will simply <a href="#cornet-advanced-highlight__single-column">generate a random 8-character hexadecimal string as a group identifier</a>.</li>
	<li><strong>no headers are present in your file</strong></li>
</ul>
<p class="user-message note">Additional columns beyond the first and second ones, as specified above, will be ignored and will not be parsed.</p>
<h3>Example</h3>
<p>A valid example of an uploaded CSV file will look as follow:</p>
<pre><code>GeneA,group1
GeneB,group1
GeneC,group1
GeneD,"another group"
GeneE,"another group"
GeneF,3
GeneG,3</code></pre>
<p>&hellip;which instructs CORNET to highlight <strong>GeneA</strong>, <strong>GeneB</strong>, and <strong>GeneC</strong> with one colour (they are in a group called "group1").</p>
<h3 id="cornet-advanced-highlight__custom-color">Custom group colors</h3>
<p>We use ColorBrewer's Dark2 palette for highlighting nodes that you have submitted. If you wish to use your own palette, simply use a valid hexadecimal or RGB color in the grouping column:</p>
<pre><code>GeneA,#45B29D
GeneB,#45B29D
GeneC,#45B29D
GeneD,"rgb(223,90,73)"
GeneE,"rgb(223,90,73)"
GeneF,"rgb(223,90,73)"
GeneG,"rgb(223,90,73)"</code></pre>
<p>This will have the effect of giving <strong>GeneA</strong>, <strong>GeneB</strong>, and <strong>GeneC</strong> the color <span style="background-color: #45B29D; color: #333; display: inline-block; padding: 2px .25rem; border-radius: 4px;">#45B29D</span>; and <strong>GeneD</strong>, <strong>GeneE</strong>, <strong>GeneF</strong>, and <strong>GeneG</strong> the color <span style="background-color: rgb(223,90,73); color: #333; display: inline-block; padding: 2px .25rem; border-radius: 4px;">rgb(223,90,73)</span></p>
<h3 id="cornet-advanced-highlight__single-column">Single column</h3>
<p>If certain rows are missing the second column, we automatically generate a random 8-character hexademical string as a group identifier. However, note that <strong>all rows without a group specified (empty second column) will be assigned to the same group</strong>. With this in mind, the following CSV file:</p>
<pre><code>GeneA,1
GeneB,group1
GeneC,group1
GeneD
GeneE
GeneF
GeneG</code></pre>
<p>&hellip;will be converted to:</p>
<?php
$randhex = bin2hex(openssl_random_pseudo_bytes(4));
?>
<pre><code>GeneA,1
GeneB,group1
GeneC,group1
GeneD,<?php echo $randhex."\n"; ?>
GeneE,<?php echo $randhex."\n"; ?>
GeneF,<?php echo $randhex."\n"; ?>
GeneG,<?php echo $randhex."\n"; ?></code></pre>
<p>&hellip;before being evaluated and colored in the network chart.</p>