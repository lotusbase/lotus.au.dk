<?php require_once('../../config.php'); ?>
<table>
	<thead>
		<tr>
			<th>Database description</th>
			<th>ID type</th>
			<th>Description</th>
			<th>Reference</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>LjGEA</td>
			<td>Gene ID</td>
			<td>The LjGEA dataset contains all gene expression data from the <a href="http://ljgea.noble.org/v2/" title="LjGEA Project">LjGEA project</a> with transcripts mapped against <em>Lotus japonicus</em> v3.0 predicted proteins. Grouping and classification are based on the <a href="http://ljgea.noble.org/v2/slides.php#a_genotype_17">information available via LjGEA</a>. There may be instances of one/many-to-many relatioships between mapped proteins and probes.</td>
			<td><a href="http://www.ncbi.nlm.nih.gov/pubmed/23452239" title="Establishment of the Lotus japonicus Gene Expression Atlas (LjGEA) and its use to explore legume seed maturation.">Verdier <em>et al.</em>, 2013</a></td>
		</tr>
		<tr>
			<td>LjGEA</td>
			<td>Probe ID</td>
			<td>The LjGEA dataset contains all gene expression data from the <a href="http://ljgea.noble.org/v2/" title="LjGEA Project">LjGEA project</a> with probe IDs preserved. Grouping and classification are based on the <a href="http://ljgea.noble.org/v2/slides.php#a_genotype_17">information available via LjGEA</a>. There may be instances of one/many-to-many relatioships between mapped proteins and probes.</td>
			<td><a href="http://www.ncbi.nlm.nih.gov/pubmed/23452239" title="Establishment of the Lotus japonicus Gene Expression Atlas (LjGEA) and its use to explore legume seed maturation.">Verdier <em>et al.</em>, 2013</a></td>
		</tr>

		<?php if(is_intranet_client()) { ?>
		<tr>
			<td>Simon Kelly's RNAseq data with bacteria treatment</td>
			<td>Transcript ID</td>
			<td>RNAseq data from <em>Lotus japonicus</em> roots from various mutants, after inoculation with various rhizobial strains.</td>
			<td>Kelly, S., unpublished data.</td>
		</tr>
		<tr>
			<td>Simon Kelly's RNAseq data with purified compounds treatment</td>
			<td>Transcript ID</td>
			<td>RNAseq data from <em>Lotus japonicus</em> roots from various mutants, after treatment with purified compounds from rhizobia.</td>
			<td>Kelly, S., unpublished data.</td>
		</tr>
		<tr>
			<td>Eiichi Murakami's RNAseq data with purified compounds treatment</td>
			<td>Transcript ID</td>
			<td>RNAseq data from <em>Lotus japonicus</em> samples from Gifu wildtype, <em>nfr1</em>, and <em>lys1</em> mutants, after treatment with <em>M. loti</em> R7A Nod factor (1e-10M).</td>
			<td>Murakami, E., unpublished data.</td>
		</tr>
		<?php } ?>

		<tr>
			<td><em>Lotus japonicus</em> early root responses to fungal exudates</td>
			<td>Probe ID</td>
			<td>
				<p>RNAseq data from <em>Lotus japonicus</em> roots after exposure to <em>C. trifolii</em> germinating spore exudates.</p>
				<p class="user-message warning">Due to insufficient number of conditions, this dataset is not available for the CORX toolkit.</p>
			</td>
			<td><a href="http://www.ncbi.nlm.nih.gov/pubmed/26175746" title="Early Lotus japonicus root transcriptomic responses to symbiotic and pathogenic fungal exudates.">Giovanetti <em>et al.</em>, 2015</a></td>
		</tr>
	</tbody>
</table>