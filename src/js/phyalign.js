$(function() {

	// jQuery UI tabs
	var $phyalign_tabs = $('#phyalign-tabs').tabs();

	// Initialize db
	$('#phyalign-db')
	.css('width', '100%')
	.select2();

	// Timers
	var pollTimer,
		pollInterval = 30,
		polled = false,
		countdownTimer;

	// Global storage
	globalVar.phyalign = {};

	// Define form
	var form = $('#phyalign-form__submit');

	// Store objects of all of user's retrieved sequences
	var seqs = {};

	// Listen to on change events to fetch sequences
	$d.on('change', '#ids', function() {

		// Make AJAX call
		var phyalignAJAX = $.ajax({
			url: root + '/api/v1/blast/'+$('#phyalign-db').val()+'/'+$('#ids').val(),
			dataType: 'json',
			type: 'GET'
		});

		phyalignAJAX
		.done(function(data) {
			var d = data.data,
				fasta = '';
			
			// Iterate through the returned sequences and store non-redundant versions in the seqs object
			$.each(d.fasta, function(i, entry) {
				if(!(entry.id in seqs)) {
					seqs[entry.id] = entry.sequence;
				}
			});

			// Update textarea
			$.each(seqs, function(id, sequence) {
				fasta += '>' + id + '\n' + sequence + '\n\n';
			});
			$('#seqs-input').val(fasta.replace(/\n+$/, '')).trigger('change');
		})
		.fail(function(jqXHR, textError, status) {
			var d = jqXHR.responseJSON;

			// Collapse form
			globalFun.collapseForm.call(form);

			// Empty results
			$('#seqret-results').empty();

			if(d.status === 400) {
				var out,
					ids = $('#seqret-gi :input[name="id"]:enabled').val().split(',');

				// Display download link
				out = '<h2>Search results</h2><p>As you have queried against a genomic database and the expected returned query is too large, you can only download your results.</p><p class="align-center"><a href="'+globalFun.seqret.downloadURL([ids])+'" class="button"><span class="icon-download">Download sequence(s)</span></a></p>';
				$('#seqret-results').append(out).show();

			} else {
				globalFun.modal.open({
					'title': 'Whoops!',
					'content': '<p>We have encountered an error when attemtping to retrieve the sequence.' + (d.message ? ' '+d.message : '') +'</p>',
					'class': 'warning'
				});
			}
		});
	});

	// Simple FASTA parser
	var parse_fasta = function(fasta) {
		// Remove extraenous linebreaks
		var _fasta = fasta.replace(/\n{2,}/, '\n').split('\n'),
			fasta_headers = [],
			fasta_seqs = [],
			_f = [];

		// Go through line by line
		$.each(_fasta, function(i, l) {
			if(l.match(/>/)) {
				fasta_headers.push(l.replace(/^>\s?/, ''));
			} else {
				if(typeof fasta_seqs[fasta_headers.length - 1] === typeof undefined) {
					fasta_seqs[fasta_headers.length - 1] = '';
				}
				fasta_seqs[fasta_headers.length - 1] += l.replace(/[\d\s]/, '');
			}
		});

		// Parse type and construct object
		var types = [];
		$.each(fasta_seqs, function(i, s) {
			_f.push({
				'header': fasta_headers[i],
				'sequence': s,
				'type': (function(s) {
					// If contains E, F, I, J, L, P, Q and Z: protein/amino acid
					if(s.match(/[efijlpqz]/i)) {
						types.push('protein');
						return 'protein';
					}
					// If contains U: RNA
					else if(s.match(/u/i)) {
						types.push('rna');
						return 'rna';
					}
					// If contains the rest of the common alphabets
					// A, B, C, D, G, H, K, M, N, R, S, T, V, W, X, Y, .
					// DNA
					else if(s.match(/[abcdghkmnrstvwxy\.]/i)) {
						types.push('dna');
						return 'dna';
					}
					else {
						return undefined;
					}
				})(s)
			});
		});

		types = globalFun.arrayUnique(types);

		return {
			'data': _f,
			'types': types,
			'status': (function(types) {
				if(types.length !== 0 && types.length === 1) {
					return 0;
				} else {
					return 1;
				}
			})(types)
		};
	};

	// Determine sequence type
	$('#seqs-input').on('change', function() {
		var parsed_fasta = parse_fasta($(this).val());
		$('#seq-type').val(parsed_fasta.types[0]);
	});


	// Enforce option requirement compliance
	$('#guide-tree, #distance-matrix').on('change', function() {
		if($(this).is(':checked')) {
			$('#mbed-guide-tree, #mbed-iteration').prop('checked', false);
		}
	});
	$('#mbed-guide-tree, #mbed-iteration').on('change', function() {
		if($(this).is(':checked')) {
			$('#guide-tree, #distance-matrix').prop('checked', false);
		}
	});


	// Create custom validators
	// FASTA validator
	$.validator.addMethod("fasta", function(value, element) {
		var parsed_fasta = parse_fasta(value);
		if(parsed_fasta.data.length < 2) {
			return false;
		} else {
			return true;
		}
	}, "Please provide more than one FASTA sequence");

	// Sequence type validator
	$.validator.addMethod("sequenceType", function(value, element) {
		if(['protein','rna','dna'].indexOf(value) > -1) {
			return true;
		} else {
			return false;
		}
	}, "Please provide a valid sequence type: DNA, RNA, or protein.");

	// Output format validator
	$.validator.addMethod("outputFormat", function(value, element) {
		if(['clustal','clustal_num','fa','msf','nexus','phylip','selex','stockholm','vienna'].indexOf(value) > -1) {
			return true;
		} else {
			return false;
		}
	}, "Please provide a valid output format: clustal, clustal_num, fa, msf, nexus, phylip, selex, stockholm, or vienna.");



	// Validation of submission form
	$('#phyalign-form__submit').validate({
		rules: {
			sequence: {
				fasta: true
			},
			stype: {
				required: true,
				sequenceType: true
			},
			outfmt: {
				required: true,
				outputFormat: true
			},
			iterations: {
				required: true,
				range: [0,5]
			},
			gtiterations: {
				required: true,
				range: [-1,5]
			},
			hmmiterations: {
				required: true,
				range: [-1,5]
			}
		},
		submitHandler: function(form) {
			// Make AJAX call
			var clustalo_submit = $.ajax({
				url: root + '/api/v1/phyalign',
				dataType: 'json',
				data: {
					email: $('#email').val(),
					title: $('#job-title').val(),
					stype: $('#seq-type').val(),
					guidetreeout: ($('#guide-tree').is(':checked') ? true : false),
					dismatout: ($('#distance-matrix').is(':checked') ? true : false),
					dealign: $('#dealign').val(),
					mbed: ($('#mbed-guide-tree').is(':checked') ? true : false),
					mbediteration: ($('#mbed-iteration').is(':checked') ? true : false),
					iterations: $('#iteration-count').val(),
					gtiterations: $('#max-guide-tree-iterations').val(),
					hmmiterations: $('#max-hmm-iterations').val(),
					outfmt: $('#output-format').val(),
					sequence: $('#seqs-input').val()
				},
				type: 'POST'
			});

			// Deal with AJAX response
			clustalo_submit
			.done(function(d) {
				var jobID = d.data.jobID;
				console.log('Assigned job identifier: ' + jobID);

				// Set up form
				$('#clustalo-jobid').val(jobID);
				$('#phyalign-form__get').trigger('submit');

				// Handoff to second tab
				$('#phyalign-tabs').tabs('option', 'active', 1);
				
			})
			.fail(function(jqXHR, textError, status) {
				console.log(jqXHR, textError, status);
				globalFun.modal.open({
					'title': 'Whoops!',
					'content': '<p>We have encountered an error.</p>',
					'class': 'warning'
				});
			});
		}
	});

	// Validation of status/data form
	$('#phyalign-form__get').validate({
		rules: {
			clustalo_jobid: {
				required: true
			}
		},
		submitHandler: function(form) {
			// Hide form and show loader
			globalFun.collapseForm.call(form);
			$('#phyalign__job-status')
			.html('<div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div><h2 id="phyalign-status--short">Please wait&hellip;</h2><span class="byline align-center">Contacting the EMBL-EBI Clustal Omega server</span>')
			.slideDown(250);

			// Start polling job
			pollJobStatus();
			pollTimer = window.setInterval(function() {
				pollJobStatus();
			}, pollInterval * 1000);
		}
	});

	var pollJobStatus = function() {

		console.log('Polling job status…');

		// Perform AJAX to check for job status
		var clustalo_status = $.ajax({
			url: root + '/api/v1/phyalign/data',
			data: {
				jobID: $('#clustalo-jobid').val()
			},	
			dataType: 'json',
			type: 'GET'
		});

		// Change status update to timer, but only for the first instance
		if(!polled) {
			$('#phyalign-status__short').text('Job is still running&hellip;');
			$('#phyalign-status span.byline').html('Last updated: '+moment(new Date()).format('MMM Do YYYY, HH:mm:ss').replace(/(st|nd|rd|th)/gi,'<sup>$1</sup>')+' &middot; updating in <span id="phyalign-status__countdown">' + (pollInterval) + 's</span>');
		}

		// Retrieve status
		clustalo_status
		.done(function(d) {
			console.log('Receiving job status (and data, if any)…');
			var job = d.data;

			// Store job data
			globalVar.phyalign.job = job;

			// Update polled flag
			polled = true;

			// Countdown from pre-set interval
			var	ticks = pollInterval - 1;
			if (countdownTimer) window.clearInterval(countdownTimer);
			countdownTimer = window.setInterval(function() {
				$('#phyalign-status__countdown').text(ticks + 's');
				if(ticks === 0) {
					window.clearInterval(countdownTimer);
				} else {
					ticks--;
				}
			}, 1000);

			// Job has been completed
			console.log(job);
			if(job.status === 0) {
				$('#phyalign__job-status').slideUp(250);
				if (countdownTimer) window.clearInterval(countdownTimer);
				if (pollTimer) window.clearInterval(pollTimer);
				console.log('Job is completed, now parsing data…');

				// Parse incoming data
				var $clustalo_tabs__nav = $('#phyalign__job-data__nav');
				$.each(job.data, function(i, clustalo) {
					// Append tab navigation
					$clustalo_tabs__nav.append('<li><a href="#phyalign__job-data__'+clustalo.type.identifier+'" title="'+clustalo.type.description+'" data-custom-smooth-scroll>'+clustalo.type.label+'</a></li>');

					// Append tab content
					$clustalo_tabs__nav.closest('div').after([
						'<div id="phyalign__job-data__'+clustalo.type.identifier+'">',
							'<p>'+clustalo.type.description+', available from <a href="'+clustalo.url+'">here</a>.</p>',
							'<pre><code>'+clustalo.content+'</code></pre>',
							(clustalo.type.identifier === 'phylotree' ? '<p class="align-center"><a href="#make-tree" title="Make phylogenetic tree" class="button" id="make-tree__button"><span class="icon-fork">Make phylogenetic tree</span></a></p>' : ''),
						'</div>'
						].join(''));
				});

				// Show and implement tabs
				$('#phyalign__job-data')
				.show()
				.tabs();
			}
		})
		.fail(function(jqXHR, textError, status) {
			console.log(jqXHR, textError, status);
		});
	};

	// Attach action to make phylogenetic tree button
	$d.on('click', '#make-tree__button', function() {

		// Setup form properly
		$('#tree-input').val();
		$('#phyalign-form__tree').trigger('submit');

		// Handoff to third tab
		$('#phyalign-tabs').tabs('option', 'active', 2);
	});

});