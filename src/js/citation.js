$(function() {

	// Make tabs
	$('#citation-tabs').tabs();

	// Data for citations
	var refs = [
		{
			'tags': ['lotus-base'],
			'cite': {
				'list': 'Mun et al. (in press). <em>Lotus</em> Base: An integrated information portal for the model legume <em>Lotus japonicus</em>. <em>Sci. Rep.</em>',
				'bibtex': '@article{Mun:2016aa,\n    Author = {Mun, Terry and Bachmann, Asger and Gupta, Vikas and Stougaard, Jens and Andersen, Stig U},\n    Doi = {10.1038/srep39447},\n    Journal = {Sci Rep},\n    Journal-Full = {Scientific reports},\n    Month = {Dec},\n    Pages = {39447},\n    Pmid = {28008948},\n    Pst = {epublish},\n    Title = {\emph{Lotus} Base: An integrated information portal for the model legume \emph{Lotus japonicus}},\n    Volume = {6},\n    Year = {2016},\n    Bdsk-Url-1 = {http://dx.doi.org/10.1038/srep39447}}'
			}
		},
		{
			'tags': ['lore1-mutants'],
			'cite': {
				'list': 'Małolepszy et al. (2016). The <em>LORE1</em> insertion mutant resource. <em>Plant J.</em> <a href="https://www.ncbi.nlm.nih.gov/pubmed/27322352">doi:10.1111/tpj.13243</a>.',
				'bibtex': '@article{Maolepszy:2016aa,\n    Author = {Ma{\l}olepszy, Anna and Mun, Terry and Sandal, Niels and Gupta, Vikas and Dubin, Manu and Urba{\'n}ski, Dorian and Shah, Niraj and Bachmann, Asger and Fukai, Eigo and Hirakawa, Hideki and Tabata, Satoshi and Nadzieja, Marcin and Markmann, Katharina and Su, Junyi and Umehara, Yosuke and Soyano, Takashi and Miyahara, Akira and Sato, Shusei and Hayashi, Makoto and Stougaard, Jens and Andersen, Stig Uggerh{\o}j},\n    Doi = {10.1111/tpj.13243},\n    Journal = {Plant J},\n    Journal-Full = {The Plant journal : for cell and molecular biology},\n    Keywords = {Lotus japonicus; DNA methylation; Long terminal repeat retrotransposon; mutagenesis; palindrome},\n    Month = {Oct},\n    Number = {2},\n    Pages = {306-317},\n    Pmid = {27322352},\n    Pst = {ppublish},\n    Title = {The \emph{\textsc{LORE1}} insertion mutant resource},\n    Volume = {88},\n    Year = {2016},\n    Bdsk-Url-1 = {http://dx.doi.org/10.1111/tpj.13243}}'
			}
		},
		{
			'tags': ['lore1-methods'],
			'cite': {
				'list': 'Urbański et al. (2012). Genome-wide <em>LORE1</em> retrotransposon mutagenesis and high-throughput insertion detection in <em>Lotus japonicus</em>. <em>Plant J.</em>, 69(4). <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014280" title="Genome-wide LORE1 retrotransposon mutagenesis and high-throughput insertion detection in Lotus japonicus.">doi:10.1111/j.1365-313X.2011.04827.x</a>.',
				'bibtex': '@article{Urbanski:2012aa,\n    Author = {Urba{\\\'n}ski, Dorian Fabian and Ma{\\l}olepszy, Anna and Stougaard, Jens and Andersen, Stig Uggerh{\\o}j},\n    Doi = {10.1111/j.1365-313X.2011.04827.x},\n    Journal = {Plant J},\n    Journal-Full = {The Plant journal : for cell and molecular biology},\n    Mesh = {Computational Biology; DNA Primers; Exons; Genome, Plant; Genotyping Techniques; High-Throughput Screening Assays; Lotus; Mutagenesis, Insertional; Mutation; Retroelements; Reverse Genetics; Seeds; Sequence Analysis, DNA; Software; Terminal Repeat Sequences},\n    Month = {Feb},\n    Number = {4},\n    Pages = {731--741},\n    Pmid = {22014280},\n    Pst = {ppublish},\n    Title = {Genome-wide \\emph{\\textsc{LORE1}} retrotransposon mutagenesis and high-throughput insertion detection in \\emph{Lotus japonicus}},\n    Volume = {69},\n    Year = {2012},\n    Bdsk-Url-1 = {http://dx.doi.org/10.1111/j.1365-313X.2011.04827.x}}'
			}
		},
		{
			'tags': ['lore1-methods'],
			'cite': {
				'list': 'Fukai et al. (2012) Establishment of a <em>Lotus japonicus</em> gene tagging population using the exon-targeting endogenous retrotransposon <em>LORE1</em></strong> (2012). <em>Plant J.</em>, 69(4). <a href="http://www.ncbi.nlm.nih.gov/pubmed/22014259" title="Establishment of a Lotus japonicus gene tagging population using the exon-targeting endogenous retrotransposon LORE1.">doi:10.1111/j.1365-313X.2011.04826.x</a>.',
				'bibtex': '@article{Fukai:2012aa,\n    Author = {Fukai, Eigo and Soyano, Takashi and Umehara, Yosuke and Nakayama, Shinobu and Hirakawa, Hideki and Tabata, Satoshi and Sato, Shusei and Hayashi, Makoto},\n    Doi = {10.1111/j.1365-313X.2011.04826.x},\n    Journal = {Plant J},\n    Journal-Full = {The Plant journal : for cell and molecular biology},\n    Mesh = {DNA Primers; Exons; Gene Targeting; Lotus; Mutagenesis, Insertional; Mutation; Retroelements; Sequence Analysis, DNA; Symbiosis; Terminal Repeat Sequences},\n    Month = {Feb},\n    Number = {4},\n    Pages = {720--730},\n    Pmid = {22014259},\n    Pst = {ppublish},\n    Title = {Establishment of a \\emph{Lotus japonicus} gene tagging population using the exon-targeting endogenous retrotransposon \\emph{\\textsc{LORE1}}},\n    Volume = {69},\n    Year = {2012},\n    Bdsk-Url-1 = {http://dx.doi.org/10.1111/j.1365-313X.2011.04826.x}}'
			}
		}
	];

	// Filter references
	$('.citation__filter').on('change', function() {
		// Find all checked filters
		var $checked = $('.citation__filter:checked'),
			tags = [],
			findOne = function (haystack, arr) {
				return arr.some(function (v) {
					return haystack.indexOf(v) >= 0;
				});
			};

		// Reset badge
		$('#citation__header span.badge').removeClass('subset');

		if($checked.length) {
			// Show checked
			// Generate list of tags
			tags = $checked.map(function() {
				return $(this).val();
			}).get();

			// Show filtering is active
			$('#citation__header span.badge').addClass('subset');

		} else {
			// If none is checked, show all
			tags = $('.citation__filter').map(function() {
				return $(this).val();
			}).get();
		}

		// Filter
		var _refs = [];
		$.each(refs, function(i,ref){
			if(findOne(tags, ref.tags)) {
				_refs.push(ref);
			}
		});
		
		// Update badge
		$('#citation__header span.badge').text(_refs.length);

		// Update output
		$('#citation__list ul').html('<li>'+$.map(_refs, function(r) { return r.cite.list; }).join('</li><li>')+'</li>');
		$('#citation__html textarea').html(globalFun.escapeHTML('<ul>\n\t<li>'+$.map(_refs, function(r) { return r.cite.list; }).join('</li>\n\t<li>')+'</li>\n</ul>'));
		$('#citation__bibtex textarea').html($.map(_refs, function(r) { return r.cite.bibtex; }).join('\n\n'));

	});

	// Export function for bibtex
	globalFun.generateDownloadFile = function(opts) {
		var _opts = $.extend({}, {
				string: '',
				mimeType: 'text/plain',
				fileExtension: 'txt',
				fileName: 'lotusbase_output'
			}, opts),
			blob = new Blob([_opts.string], {type: _opts.mimeType+";charset=utf-8"});
		saveAs(blob, _opts.fileName+"."+_opts.fileExtension);
	};
	$('button[data-action="export"]').click(function() {
		globalFun.generateDownloadFile({
			string: $('#citation__bibtex textarea').val(),
			mimeType: $(this).data('mime-type'),
			fileExtension: $(this).data('file-extension'),
			fileName: $(this).data('file-name')
		});
	});
});