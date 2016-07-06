$(function() {
	// AJAX for TrEx
	$d.on('click', '.trx a[data-seqret]', function(e) {
		var $t = $(this);
		e.preventDefault();

		// Store SeqRet information
		var seqret = {
			db:		$t.data('seqret-db'),
			id:		$t.data('seqret-id'),
			from:	parseInt($t.data('seqret-from')),
			to:		parseInt($t.data('seqret-to')),
			strand:	$t.data('seqret-strand')
		};

		// Perform AJAX call
		var seqretAJAX = $.ajax({
			url: '../api/v1/blast/'+seqret.db+'/'+seqret.id,
			dataType: 'json',
			data: {
				from:	seqret.from,
				to:		seqret.to,
				strand:	seqret.strand
			},
			type: 'GET'
		});

		// Load
		globalFun.modal.open({
			'title': "Retrieving sequence&hellip;",
			'content': "<div class='loader'><svg><circle class='path' cx='40' cy='40' r='30' /></svg></div>",
			'allowClose': false
		});

		// When data is returned
		seqretAJAX.done(function(d) {
			var timer = window.setTimeout(function() {
				window.clearTimeout(timer);
				globalFun.modal.update({
					'title': seqret.id + (seqret.from && seqret.to ? ' ('+seqret.from+'&ndash;'+seqret.to+')': ''),
					'content': '<p>'+$t.text()+' for '+seqret.id+(seqret.from && seqret.to ? ' ('+seqret.from+'&ndash;'+seqret.to+')': '')+'&mdash;you can choose to <a href="../api/v1/blast/'+seqret.db+'/'+seqret.id+'?download&from='+seqret.from+'&to='+seqret.to+($t.data('seqret-strand') ? '&strand='+$t.data('seqret-strand') : '')+'&access_token='+access_token+'" title="Download sequence" class="trex-seqret-download">download the sequence</a>.</p><pre class="insertion-flank" readonly>' + d.data.fasta[0].sequence.replace(/(.{10})/g, '$1 ') + '</pre>',
					'allowClose': true
				});
			}, 1000);
		})
		.fail(function(jqXHR) {
			var d = jqXHR.responseJSON,
				modalErrorMessage = '<p>'+d.message+'</p>',
				transcriptRegex = /^(.*)\.\d+$/gi,
				geneID = $t.attr('data-seqret').replace(transcriptRegex, '$1'),
				modalParams = {
					'title': 'Whoops!',
					'content': modalErrorMessage,
					'class': 'warning',
					'allowClose': true
				};

//			if(transcriptRegex.test($t.attr('data-seqret')) && d.status === 400) {
//				modalErrorMessage += '<p>It appears that you are querying for a specific isoform of the gene. You might want to query for all possible isoforoms by searching using the gene ID&mdash;<code>'+geneID+'</code> (without the trailing period and digit)&mdash;and not the transcript ID&mdash<code>'+$t.attr('data-seqret')+'</code>.</p>';
//
//				$.extend(modalParams, {
//					'content': modalErrorMessage,
//					'actionText': 'Search with gene ID <code>'+geneID+'</code>',
//					'actionHref': 'trex?trx=' + geneID
//				});
//			}

			globalFun.modal.update(modalParams);
		});
	});

	// Custom modal for manual gene annotation
	$.validator.addMethod(
		'lj_gene_regex',
		function(value, element, regexp) {
			var check = false;
			return this.optional(element) || regexp.test(value);
		},
		'Please make sure your gene name starts with <code><em>Lj</em></code>, and follows the <a href="">standard <em>Lotus japonicus</em> gene nomenclature</a>.'
	);
	$d.on('click.modal', '.manual-gene-anno', function() {
		var title = $(this).attr('title'),
			gene = $(this).attr('data-gene');

		if(this.href && this.href.indexOf('#') < 0) {
			// Load content of remote file
			var modalLoad = $.ajax(this.href, {
				dataType: 'html'
			});

			modalLoad.done(function(html) {
				globalFun.modal.open({
					'title': title,
					'content': html,
					'class': 'wide',
					'actionButtons': [
						'<a class="button" href="#" data-action="submit" id="manual-gene-anno-submit" data-version="30" data-gene="'+gene+'"><span class="pictogram icon-bookmark">Submit suggestion</span></a>',
						'<a class="button" href="#" data-action="close">Dismiss</a>'
					]
				});

				// Validate form
				globalVar.manualGeneAnnoValidator = $('#manual-gene-anno-form').validate({
					rules: {
						annotation_user_email: 'required',
						annotation_gene: {
							required: true,
							lj_gene_regex: /^Lj[A-Z][a-z]{2,}[0-9]?$/,
							minlength: 5
						}
					}
				});

				$d.on('change keyup blur', '#modal__content :input', function() {
					if($('#manual-gene-anno-form').valid()) {
						$('#manual-gene-anno-submit').prop('disabled',false).removeClass('disabled');
					}
				});
			});
		}

		return false;
	});

	// Listen to submit button on modal
	$d.on('click', '#manual-gene-anno-submit', function(e) {
		var $t = $(this);

		if($('#manual-gene-anno-form').valid()) {

			// Disable submit button
			$t.prop('disabled', true).addClass('disabled');

			// Make AJAX call
			var annoSubmit = $.ajax({
				url: '/api',
				data: {
					t: 14,
					v: $t.data('version'),
					g: $t.data('gene'),
					a: $('#annotation-gene').val(),
					e: $('#annotation-user-email').val(),
					l: $('#annotations-literature').val()
				},
				type: 'POST',
				dataType: 'json'
			}).done(function(data) {
				if(data.success) {
					$('#manual-gene-anno-form').empty().html('<p class="user-message approved">Your submission has been successful. Thank you for your contribution.</p>');
				} else {
					if(data.data.errorType === 'gene_exists' || data.data.errorType === 'gene_under_review') {
						globalVar.manualGeneAnnoValidator.showErrors({
							'annotation_gene': data.message
						});
					}
				}
			});
		}

		return false;
	});
});