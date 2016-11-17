<?php
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>Codon Usage&mdash;Data&mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Codon usage table based on Lotus japonicus v3.0 genome assembly coding sequences.'
			));
		echo $document_header->get_document_header();
	?>
	<link rel="stylesheet" href="/dist/css/tools.min.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="/dist/css/expat.min.css" type="text/css" media="screen" />
</head>
<body class="data codon-usage wide init-scroll--disabled">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<h1>Codon Usage</h1>
		<p>Codon usage is computed based on v3.0 CDS of <em>L. japonicus</em> ecotype MG20. Codon usage is generated from 47,722 coding sequences containing 14,670,605 codons.</p>
		<ul class="tabs">
			<li><a href="#codon-usage--standard" data-smooth-scroll="false"><span class="icon-database">Standard codon usage table</span></a></li>
			<li><a href="#codon-usage--inversed" data-smooth-scroll="false"><span class="icon-shuffle">Inversed codon usage table</span></a></li>
		</ul>
		<p>The raw spreadsheet containing data for both tables can be <a href="'.WEB_ROOT.'/data/downloads/CodonUsageTable_Ljv3.0.xlsx">downloaded here</a>.</p>');
		echo $header->get_header();

		$aminoacid_legend = '<ul class="cols flex-wrap__nowrap amino-acid-legend">
			<li data-aa-prop="nonpolar">Non-polar</li>
			<li data-aa-prop="polar">Polar</li>
			<li data-aa-prop="basic">Basic</li>
			<li data-aa-prop="acidic">Acidic</li>
		</ul>';

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<div class="tabs__target" id="codon-usage--standard">
			<h2>Standard codon usage table</h2>
			<p>Column layout per nucleotide are represented as: <strong>Triplet</strong>, <strong>Amino acid</strong>, <strong>Total observations</strong>, and <strong>Frequency (observations per 1000bp)</strong>. The colour scheme for amino acids are based on their properties:</p>
			<?php echo $aminoacid_legend ;?>
			<div class="table-overflow">
				<table id="table--codon-usage">
					<thead>
						<tr><th rowspan="2">1st base</th><th colspan="16">2nd base</th><th rowspan="2">3rd base</th></tr>
						<tr><th colspan="4" data-type="nucleotide">U</th><th colspan="4" data-type="nucleotide">C</th><th colspan="4" data-type="nucleotide">A</th><th colspan="4" data-type="nucleotide">G</th></tr>
					</thead>
					<tbody>
						<tr><th rowspan="4" data-type="nucleotide">U</th><td>UUU</td><td rowspan="2" data-type="aminoacid" data-aa-prop="nonpolar">Phe</td><td>361367</td><td>24.63</td><td>UCU</td><td rowspan="4" data-type="aminoacid" data-aa-prop="polar">Ser</td><td>354539</td><td>24.17</td><td>UAU</td><td rowspan="2" data-type="aminoacid" data-aa-prop="polar">Tyr</td><td>247097</td><td>16.84</td><td>UGU</td><td rowspan="2" data-type="aminoacid" data-aa-prop="polar">Cys</td><td>155346</td><td>10.59</td><th data-type="nucleotide">U</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>UUC</td><td style="display: none;">&nbsp;</td><td>267950</td><td>18.26</td><td>UCC</td><td style="display: none;">&nbsp;</td><td>191099</td><td>13.03</td><td>UAC</td><td style="display: none;">&nbsp;</td><td>159095</td><td>10.84</td><td>UGC</td><td style="display: none;">&nbsp;</td><td>124208</td><td>8.47</td><th data-type="nucleotide">C</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>UUA</td><td rowspan="6" data-type="aminoacid" data-aa-prop="nonpolar">Leu</td><td>185820</td><td>12.67</td><td>UCA</td><td style="display: none;">&nbsp;</td><td>314643</td><td>21.45</td><td>UAA</td><td colspan="3" data-type="stopcodon">Stop (Ochre)</td><td style="display: none;">&nbsp;</td><td style="display: none;">&nbsp;</td><td>UGA</td><td colspan="3" data-type="stopcodon">Stop (Opal)</td><td style="display: none;">&nbsp;</td><td style="display: none;">&nbsp;</td><th data-type="nucleotide">A</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>UUG</td><td style="display: none;">&nbsp;</td><td>359691</td><td>24.52</td><td>UCG</td><td style="display: none;">&nbsp;</td><td>74656</td><td>5.09</td><td>UAG</td><td colspan="3" data-type="stopcodon">Stop (Amber)</td><td style="display: none;">&nbsp;</td><td style="display: none;">&nbsp;</td><td>UGG</td><td data-type="aminoacid" data-aa-prop="nonpolar">Trp</td><td>190089</td><td>12.96</td><th data-type="nucleotide">G</th></tr>
						<tr><th rowspan="4" data-type="nucleotide">C</th><td>CUU</td><td style="display: none;">&nbsp;</td><td>358233</td><td>24.42</td><td>CCU</td><td rowspan="4" data-type="aminoacid" data-aa-prop="nonpolar">Pro</td><td>281250</td><td>19.17</td><td>CAU</td><td rowspan="2" data-type="aminoacid" data-aa-prop="basic">His</td><td>233616</td><td>15.92</td><td>CGU</td><td rowspan="4" data-type="aminoacid" data-aa-prop="basic">Arg</td><td>93830</td><td>6.4</td><th data-type="nucleotide">U</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>CUC</td><td style="display: none;">&nbsp;</td><td>209298</td><td>14.27</td><td>CCC</td><td style="display: none;">&nbsp;</td><td>113212</td><td>7.72</td><td>CAC</td><td style="display: none;">&nbsp;</td><td>141311</td><td>9.63</td><td>CGC</td><td style="display: none;">&nbsp;</td><td>76200</td><td>5.19</td><th data-type="nucleotide">C</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>CUA</td><td style="display: none;">&nbsp;</td><td>140080</td><td>9.55</td><td>CCA</td><td style="display: none;">&nbsp;</td><td>255046</td><td>17.38</td><td>CAA</td><td rowspan="2" data-type="aminoacid" data-aa-prop="polar">Gln</td><td>302940</td><td>20.65</td><td>CGA</td><td style="display: none;">&nbsp;</td><td>75406</td><td>5.14</td><th data-type="nucleotide">A</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>CUG</td><td style="display: none;">&nbsp;</td><td>189357</td><td>12.91</td><td>CCG</td><td style="display: none;">&nbsp;</td><td>76015</td><td>5.18</td><td>CAG</td><td style="display: none;">&nbsp;</td><td>243399</td><td>16.59</td><td>CGG</td><td style="display: none;">&nbsp;</td><td>64313</td><td>4.38</td><th data-type="nucleotide">G</th></tr>
						<tr><th rowspan="4" data-type="nucleotide">A</th><td>AUU</td><td rowspan="3" data-type="aminoacid" data-aa-prop="nonpolar">Ile</td><td>376167</td><td>25.64</td><td>ACU</td><td rowspan="4" data-type="aminoacid" data-aa-prop="polar">Thr</td><td>256336</td><td>17.47</td><td>AAU</td><td rowspan="2" data-type="aminoacid" data-aa-prop="polar">Asn</td><td>406072</td><td>27.68</td><td>AGU</td><td rowspan="2" data-type="aminoacid" data-aa-prop="polar">Ser</td><td>223030</td><td>15.2</td><th data-type="nucleotide">U</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>AUC</td><td style="display: none;">&nbsp;</td><td>201345</td><td>13.72</td><td>ACC</td><td style="display: none;">&nbsp;</td><td>167225</td><td>11.4</td><td>AAC</td><td style="display: none;">&nbsp;</td><td>258952</td><td>17.65</td><td>AGC</td><td style="display: none;">&nbsp;</td><td>164228</td><td>11.19</td><th data-type="nucleotide">C</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>AUA</td><td style="display: none;">&nbsp;</td><td>199298</td><td>13.58</td><td>ACA</td><td style="display: none;">&nbsp;</td><td>239953</td><td>16.36</td><td>AAA</td><td rowspan="2" data-type="aminoacid" data-aa-prop="basic">Lys</td><td>426451</td><td>29.07</td><td>AGA</td><td rowspan="2" data-type="aminoacid" data-aa-prop="basic">Arg</td><td>242394</td><td>16.52</td><th data-type="nucleotide">A</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>AUG</td><td data-type="aminoacid" data-aa-prop="nonpolar">Met</td><td>371054</td><td>25.29</td><td>ACG</td><td style="display: none;">&nbsp;</td><td>59201</td><td>4.04</td><td>AAG</td><td style="display: none;">&nbsp;</td><td>466896</td><td>31.83</td><td>AGG</td><td style="display: none;">&nbsp;</td><td>207052</td><td>14.11</td><th data-type="nucleotide">G</th></tr>
						<tr><th rowspan="4" data-type="nucleotide">G</th><td>GUU</td><td rowspan="4" data-type="aminoacid" data-aa-prop="nonpolar">Val</td><td>395561</td><td>26.96</td><td>GCU</td><td rowspan="4" data-type="aminoacid" data-aa-prop="nonpolar">Ala</td><td>397703</td><td>27.11</td><td>GAU</td><td rowspan="2" data-type="aminoacid" data-aa-prop="acidic">Asp</td><td>542134</td><td>36.95</td><td>GGU</td><td rowspan="4" data-type="aminoacid" data-aa-prop="nonpolar">Gly</td><td>298975</td><td>20.38</td><th data-type="nucleotide">U</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>GUC</td><td style="display: none;">&nbsp;</td><td>148594</td><td>10.13</td><td>GCC</td><td style="display: none;">&nbsp;</td><td>166084</td><td>11.32</td><td>GAC</td><td style="display: none;">&nbsp;</td><td>226965</td><td>15.47</td><td>GGC</td><td style="display: none;">&nbsp;</td><td>154856</td><td>10.56</td><th data-type="nucleotide">C</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>GUA</td><td style="display: none;">&nbsp;</td><td>140221</td><td>9.56</td><td>GCA</td><td style="display: none;">&nbsp;</td><td>306795</td><td>20.91</td><td>GAA</td><td rowspan="2" data-type="aminoacid" data-aa-prop="acidic">Glu</td><td>502446</td><td>34.25</td><td>GGA</td><td style="display: none;">&nbsp;</td><td>297736</td><td>20.29</td><th data-type="nucleotide">A</th></tr>
						<tr><th style="display: none;">&nbsp;</th><td>GUG</td><td style="display: none;">&nbsp;</td><td>277080</td><td>18.89</td><td>GCG</td><td style="display: none;">&nbsp;</td><td>85856</td><td>5.85</td><td>GAG</td><td style="display: none;">&nbsp;</td><td>434243</td><td>29.6</td><td>GGG</td><td style="display: none;">&nbsp;</td><td>190596</td><td>12.99</td><th data-type="nucleotide">G</th></tr>
					</tbody>	 
				</table>
			</div>
		</div>
		<div class="tabs__target" id="codon-usage--inversed">
			<h2>Inversed codon usage table</h2>
			<p>Column layout per nucleotide are represented as: <strong>Triplet</strong>, <strong>Amino acid</strong>, <strong>Total observations</strong>, and <strong>Frequency (observations per 1000bp)</strong>. The colour scheme for amino acids are based on their properties:</p>
			<?php echo $aminoacid_legend; ?>
			<table id="table--codon-usage-inversed">
				<thead>
					<tr><th>Amino acid</th><th>Triplet</th><th>Observations</th><th>Frequency (observations per 1000bp)</th></tr>
				</thead>
				<tbody>
					<tr><th rowspan="4" data-type="aminoacid" data-aa-prop="nonpolar">Gly</th><td>GGG</td><td>190596</td><td>12.99</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>GGA</td><td>297736</td><td>20.29</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>GGT</td><td>298975</td><td>20.38</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>GGC</td><td>154856</td><td>10.56</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="acidic">Glu</th><td>GAG</td><td>434243</td><td>29.6</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>GAA</td><td>502446</td><td>34.25</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="acidic">Asp</th><td>GAT</td><td>542134</td><td>36.95</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>GAC</td><td>226965</td><td>15.47</td></tr>
					<tr><th rowspan="4" data-type="aminoacid" data-aa-prop="nonpolar">Val</th><td>GTG</td><td>277080</td><td>18.89</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>GTA</td><td>140221</td><td>9.56</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>GTT</td><td>395561</td><td>26.96</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>GTC</td><td>148594</td><td>10.13</td></tr>
					<tr><th rowspan="4" data-type="aminoacid" data-aa-prop="nonpolar">Ala</th><td>GCG</td><td>85856</td><td>5.85</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>GCA</td><td>306795</td><td>20.91</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>GCT</td><td>397703</td><td>27.11</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>GCC</td><td>166084</td><td>11.32</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="basic">Arg</th><td>AGG</td><td>207052</td><td>14.11</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>AGA</td><td>242394</td><td>16.52</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="polar">Ser</th><td>AGT</td><td>223030</td><td>15.2</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>AGC</td><td>164228</td><td>11.19</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="basic">Lys</th><td>AAG</td><td>466896</td><td>31.83</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>AAA</td><td>426451</td><td>29.07</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="polar">Asn</th><td>AAT</td><td>406072</td><td>27.68</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>AAC</td><td>258952</td><td>17.65</td></tr>
					<tr><th data-type="aminoacid" data-aa-prop="nonpolar">Met</td><td>ATG</td><td>371054</td><td>25.29</td></tr>
					<tr><th rowspan="3" data-type="aminoacid" data-aa-prop="nonpolar">Ile</th><td>ATA</td><td>199298</td><td>13.58</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>ATT</td><td>376167</td><td>25.64</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>ATC</td><td>201345</td><td>13.72</td></tr>
					<tr><th rowspan="4" data-type="aminoacid" data-aa-prop="polar">Thr</th><td>ACG</td><td>59201</td><td>4.04</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>ACA</td><td>239953</td><td>16.36</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>ACT</td><td>256336</td><td>17.47</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>ACC</td><td>167225</td><td>11.4</td></tr>
					<tr class="sep--bottom-m"><th data-type="aminoacid" data-aa-prop="nonpolar">Trp</th><td>TGG</td><td>190089</td><td>12.96</td></tr>
					<tr class="sep--bottom-m"><th data-type="stopcodon">STOP</td><td>TGA</td><td>0</td><td>0</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="polar">Cys</th><td>TGT</td><td>155346</td><td>10.59</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>TGC</td><td>124208</td><td>8.47</td></tr>
					<tr class="sep--bottom-m"><th data-type="stopcodon">STOP</td><td>TAG</td><td>0</td><td>0</td></tr>
					<tr class="sep--bottom-m"><th data-type="stopcodon">STOP</td><td>TAA</td><td>0</td><td>0</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="polar">Tyr</th><td>TAT</td><td>247097</td><td>16.84</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>TAC</td><td>159095</td><td>10.84</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="nonpolar">Leu</th><td>TTG</td><td>359691</td><td>24.52</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>TTA</td><td>185820</td><td>12.67</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="nonpolar">Phe</th><td>TTT</td><td>361367</td><td>24.63</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>TTC</td><td>267950</td><td>18.26</td></tr>
					<tr><th rowspan="4" data-type="aminoacid" data-aa-prop="polar">Ser</th><td>TCG</td><td>74656</td><td>5.09</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>TCA</td><td>314643</td><td>21.45</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>TCT</td><td>354539</td><td>24.17</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>TCC</td><td>191099</td><td>13.03</td></tr>
					<tr><th rowspan="4" data-type="aminoacid" data-aa-prop="basic">Arg</th><td>CGG</td><td>64313</td><td>4.38</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>CGA</td><td>75406</td><td>5.14</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>CGT</td><td>93830</td><td>6.4</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>CGC</td><td>76200</td><td>5.19</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="polar">Gln</th><td>CAG</td><td>243399</td><td>16.59</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>CAA</td><td>302940</td><td>20.65</td></tr>
					<tr><th rowspan="2" data-type="aminoacid" data-aa-prop="basic">His</th><td>CAT</td><td>233616</td><td>15.92</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>CAC</td><td>141311</td><td>9.63</td></tr>
					<tr><th rowspan="4" data-type="aminoacid" data-aa-prop="nonpolar">Leu</th><td>CTG</td><td>189357</td><td>12.91</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>CTA</td><td>140080</td><td>9.55</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>CTT</td><td>358233</td><td>24.42</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>CTC</td><td>209298</td><td>14.27</td></tr>
					<tr><th rowspan="4" data-type="aminoacid" data-aa-prop="nonpolar">Pro</th><td>CCG</td><td>76015</td><td>5.18</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>CCA</td><td>255046</td><td>17.38</td></tr>
					<tr><th style="display: none;">&nbsp;</th><td>CCT</td><td>281250</td><td>19.17</td></tr>
					<tr class="sep--bottom-m"><th style="display: none;">&nbsp;</th><td>CCC</td><td>113212</td><td>7.72</td></tr>
				</tbody>
			</table>
		</div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script>
		$(function() {

			// Hash check
			globalFun.hashCheck = function() {
				$('.tabs__target').hide();
				if(window.location.hash) {
					var hash = window.location.hash;
					if ($(hash).length) {
						$('#header ul.tabs a[href="'+hash+'"]').closest('li').addClass('active');
						$(hash).show();
					}
				} else {
					$('#header ul.tabs li').removeClass('active').first().addClass('active');
					$('#codon-usage--standard').show();
				}
			}

			// Manual navigation functionality
			$('#header ul.tabs a').click(function(e) {
				var	hash = $(this).attr('href'),
					$tab = $(hash),
					$li = $(this).closest('li');

				// Display correct tab
				$tab.show().siblings().hide();

				// Update status
				$li.addClass('active').siblings().removeClass('active');

				// Prevent scroll, yet enable hash change to be written to history
				e.preventDefault();
				window.history.pushState({lotusbase: true}, '', hash);
			});

			// Listen to pop state
			$w.on('popstate', function(e) {
				globalFun.hashCheck();
			});

			// Get hash
			globalFun.hashCheck();

		});
	</script>
</body>
</html>