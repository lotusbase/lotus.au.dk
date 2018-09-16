$(function() {
	// Create globals
	globalVar.corgi = {
		searched: false
	};
	globalVar.cornet = {};

	// Validate form
	globalVar.corgi.form = {
		validator: $('body.corgi #expat-form').validate({
			ignore: [],
			rules: {
				ids: { required: true },
				n: { required: true },
				dataset: { required: true }
			},
			errorPlacement: function(error, element) {
				var $e = element;
				if($e.attr('id') === 'expat-dataset') {
					$e.siblings('.select2')
						.addClass('error')
						.after(error);
				} else {
					error.insertAfter(element);
				}
			}
		})
	};

	// Data parsing function
	globalFun.corgi = {
		init: function() {

			// Expat form
			globalFun.corgi.expatForm();

			// CORGI results
			globalFun.corgi.results();
		},
		expatForm: function() {
			// Validate query
			$d.on('change blur', 'body.corgi #expat-dataset, body.corgi #expat-row', globalFun.corgi.validateQuery);

			// Only allow submission when form is valid
			$('body.corgi #expat-form :input').on('change', function() {
				if($('#expat-form').valid() && globalVar.corgi.form.validator.errorList.length === 0 && globalVar.recaptcha) {
					$('#expat-form__submit').prop('disabled', false);
				} else {
					$('#expat-form__submit').prop('disabled', true);
				}
			});

			// AJAX request
			$('body.corgi #expat-form').on('submit', function(e) {
				e.preventDefault();

				var $t = $(this),
					corgiAJAX = $.ajax({
						url: '../api/v1/corgi/' + $('#expat-row').val(),
						data: $t.serialize(),
						dataType: 'json',
						type: 'POST'
					});

				// Check if grecaptcha is required
				if(globalVar.grecaptcha !== void 0) {
					// Reset captcha
					globalVar.grecaptcha.reset();

					// Disable submit button
					$('#expat-form__submit').prop('disabled', true);
				}

				// Open modal
				globalFun.modal.open({
					'title': 'Processing request',
					'content': '<div class="user-message loading-message"><div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div><p class="loading-text">Constructing correlation matrix&hellip;</p></div></div>',
					'allowClose': false
				});

				corgiAJAX
				.done(function(d) {
					globalFun.modal.update({
						'title': 'Parsing incoming data',
						'content': '<div class="user-message loading-message"><div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div><p class="loading-text">Slicing and dicing incoming data. Please wait&hellip;</p></div></div>'
					});

					var timer = window.setTimeout(function() {
						globalFun.corgi.parseData(d);
						window.clearTimeout(timer);
					}, 1000);
				})
				.fail(function(a, b, c) {
					globalFun.modal.fail(a, b, c);
				});
			});
		},
		results: function() {
			$('#corgi-results')
			// Determine where to send results to
			.on('click', 'tfoot button', function() {
				if($('#rows tbody input:checkbox:checked').length) {
					$(this).closest('form')
					.attr('action', $(this).attr('data-form-action'))
					.trigger('submit');
				} else {
					globalFun.modal.open({
						'title': 'No rows selected',
						'content': '<p>You have not selected any rows to be passed on to another <em>Lotus</em> base tool.</p>',
						'allowClose': true
					});
				}
			})
			// Intercept form submission
			.on('submit', 'form', function(e) {
				e.preventDefault();

				var p = [
					{ key: 'dataset', value: $('#expat-dataset').val() },
					{ key: 'column', value: $('#expat-dataset-subset tbody input:checkbox:checked').map(function() { return $(this).val(); }).get().join(',') },
					{ key: 'conditions', value: $('#expat-condition').val() }
				];

				$.each(p, function(i,v) {
					$('#rows input[name="'+v.key+'"]').val(v.value);
				});

				// Submit
				$(this)[0].submit();
			});
		},
		validateQuery: function() {
			var idType = $('#expat-dataset option:selected').data('idtype'),
				idVal = $('#expat-row').val(),
				idPattern = {
					'transcriptid': /^Lj([0-6]|chloro|mito|1_4_FUCT|XYLT)g\dv\d+\.(mrna)?\d+$/i,
					'geneid': /^Lj([0-6]|chloro|mito|1_4_FUCT|XYLT)g\dv\d+(\.(mrna)?\d+)?$/i,
					'probeid': /^(Ljwgs\_|LjU|Lj\_|chr[0-6]\.|gi|m[a-z]{2}|tc|tm|y4|rngr|cm).+\_at$/i
				},
				idExample = {
					'transcriptid': 'Lj4g3v0281040.1',
					'geneid': 'Lj4g3v0281040',
					'probeid': 'Ljwgs_036669.1_at'
				};

			$('#expat-row').siblings('p.user-message').remove();

			if(idType !== undefined && idType !== '' && idVal.length > 0) {
				if(idPattern[idType].test(idVal)) {
					globalVar.corgi.form.validator.hideErrors();
					globalVar.corgi.form.validator.resetForm();
				} else {
					globalVar.corgi.form.validator.showErrors({
						'ids': 'You have entered a query that does not conform to the expected format. Example: <code>'+idExample[idType]+'</code>.<br />Regex pattern used: <code>'+idPattern[idType]+'</code>.'
					});
				}
			}

			if(globalVar.corgi.form.validator.element('#expat-dataset')) {
				$('#expat-dataset').next('.select2').removeClass('error');
			}
		},
		parseData: function(d) {

			// Wrap form
			if(!globalVar.corgi.searched) {
				$('#expat-form')
				.wrap('<div class="toggle hide-first"></div>')
				.before('<h3><a href="#" title="Repeat search">Retrieve other co-expressed genes</a></h3><p>Repeat your search with other gene/transcript/probe IDs, or with fine-tuned settings.</p>');

				$('.toggle').children().not('h3').hide();

				globalVar.corgi.searched = true;
			}

			// Insert results
			$('#corgi-results').empty().html('<h3>Results</h3>');

			// Generate table
			var $table = $('<table id="rows" data-sticky></table>');
			$table.append('<thead><tr><th class="chk"><input type="checkbox" class="ca"></th><th>Gene</th><th>Annotation</th><th data-type="numeric">R<sup>2</sup></th></tr></thead>');
			$table.append('<tbody></tbody>');
			$.each(d.data, function(i, v) {
				var out = '';
				if(v.Annotation) {
					var anno = v.Annotation.replace(/\[([\w\s]+)\]?/i, '[<em>$1</em>]');

					if(v.LjAnnotation) {
						out += '<span class="anno__manual">'+v.LjAnnotation+'</span>';
					}
					out += '<span class="anno__generated">'+anno+'</span>';
				} else {
					out += '<em>n.a.</em>';
				}

				var gene = v.id.replace(/\.\d+?/gi, ''),
					dropdown = '<div class="dropdown button"><span class="dropdown--title">'+v.id+'</span><ul class="dropdown--list">';

				// Dropdown actions
				dropdown += '<li><a target="_blank" href="../tools/trex?ids='+gene+'&v=MG20_3.0"><span class="icon-direction">Send gene to Transcript Explorer (TREX)</span></a></li>';
				dropdown += '<li><a target="_blank" href="../lore1/search-exec?gene='+gene+'&v=MG20_3.0" title="Search for LORE1 insertions in this gene"><span class="pictogram icon-search">LORE1 v3.0</span></a></li>';
				dropdown += '</ul></div>';

				$table.find('tbody').append('<tr><td class="chk"><input type="checkbox" value="'+v.id+'" name="ids[]" /></td><td>'+dropdown+'</td><td class="anno">'+out+'</td><td data-type="numeric">'+v.score.toFixed(5)+'</td></tr>');
			});
			$table.append('<tfoot><tr><td colspan="999"><div class="cols justify-content--center"><input type="hidden" name="dataset" /><input type="hidden" name="column" /><input type="hidden" name="conditions" /><button data-action="submit" data-form-action="../expat" type="button"><span class="icon-map">Send selected rows to ExpAt</span></button><button data-action="submit" data-form-action="../tools/trex" type="button"><span class="icon-direction">Send selected rows to TREX</span></button></td></div></tfoot>');

			// Generate html
			$('#corgi-results').append($('<form action="#" method="get" target="_blank"></form>').append($table));

			// Create sticky table
			if(typeof($('#rows').data('sticky')) !== undefined) {
				$('#rows').after($('<table />', {
					'id': 'sticky',
					'class': $('#rows').attr('class')
				}));
				$('#rows thead, #rows colgroup').clone().appendTo('#sticky');
				$('#sticky').css({
					'width': $('#rows').width(),
					'left': $('#rows').offset().left
				});

				// Pass computed widths
				$('#sticky thead th').each(function(i) {
					$(this).width($('#rows thead th').eq(i).width());
				});

				// Hide sticky
				$('#sticky').hide();
			}

			// Scroll
			globalFun.smoothScroll('#rows');

			// Close modal
			globalFun.modal.close();
		}
	};

	// Init
	globalFun.corgi.init();
});