$(function() {

	// jQuery UI tabs
	var $phyalign_tabs = $('#phyalign-tabs').tabs();
	$d.on('click', '.ui-tabs a.ui-tabs-anchor', function(e) {
		e.preventDefault();
		window.history.pushState({lotusbase: true}, '', $(this).attr('href'));
		$(':input[name="hash"]').val($(this).attr('href').substring(1));
	});

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
				url: root + '/api/v1/phyalign/submit',
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
			.removeClass('user-message warning')
			.html('<div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div><h2 id="phyalign-status--short">Please wait&hellip;</h2><span class="byline align-center">Contacting the EMBL-EBI Clustal Omega server</span>')
			.slideDown(250);

			// Start polling job
			polled = false;
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

		// Retrieve status
		clustalo_status
		.done(function(d) {
			console.log('Receiving job status (and data, if any)…');
			var job = d.data;

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
			if(typeof job.status !== typeof undefined && job.status === 0) {

				// Create data
				globalVar.phyalign.data = job;

				if (countdownTimer) window.clearInterval(countdownTimer);
				if (pollTimer) window.clearInterval(pollTimer);
				console.log('Job is completed, now parsing data…');

				// Update status
				$('#phyalign__job-status').find('div.loader').remove();
				$('#phyalign-status--short')
				.html('<span class="icon-ok-circled icon--big icon--no-spacing">Job completed</span>')
				.next('span.byline')
					.html('<span class="job-id__wrapper" data-job-id="'+job.id+'"><span class="job-id">'+job.id+'</span><span class="tooltip">Press <kbd>Ctrl &#8963;</kbd>/<kbd>Cmd &#8984;</kbd> + <kbd>C</kbd> to copy</span></span>');

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
			} else {
				// Change status update to timer, but only for the first instance
				if(!polled) {
					$('#phyalign-status--short')
					.html('Job is still running&hellip;')
					.next('span.byline')
						.html('Last updated: '+moment(new Date()).format('MMM Do YYYY, HH:mm:ss').replace(/(st|nd|rd|th)/gi,'<sup>$1</sup>')+' &middot; updating in <span id="phyalign-status__countdown">' + (pollInterval) + 's</span>');
				}

				// Update polled flag
				polled = true;
			}
		})
		.fail(function(jqXHR, textError, status) {
			console.log(jqXHR, textError, status);
			if (countdownTimer) window.clearInterval(countdownTimer);
			if (pollTimer) window.clearInterval(pollTimer);

			$('#phyalign__job-status').html('<p><span class="icon-attention"></span>We have encountered a '+jqXHR.status+' "'+status+'" error when attempting to retrieve your ClustalO job status and/or data.</p>').addClass('user-message warning');
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

	// Expose global d3 variables
	globalVar.phyalign.d3 = {
		outerRadius: 960/2
	};
	globalVar.phyalign.d3.innerRadius = globalVar.phyalign.d3.outerRadius - 170;

	// Expose global d3 functions
	globalFun.phyalign = {
		d3: {
			setRadius: function(d, y0, k) {
				d.radius = (y0 += d.length) * k;
				if (d.children) d.children.forEach(function(d) { globalFun.phyalign.d3.setRadius(d, y0, k); });
			},
			maxLength: function(d) {
				return d.length + (d.children ? d3.max(d.children, globalFun.phyalign.d3.maxLength) : 0);
			},
			step: function(startAngle, startRadius, endAngle, endRadius) {
				var c0 = Math.cos(startAngle = (startAngle - 90) / 180 * Math.PI),
					s0 = Math.sin(startAngle),
					c1 = Math.cos(endAngle = (endAngle - 90) / 180 * Math.PI),
					s1 = Math.sin(endAngle);

				return "M" + startRadius * c0 + "," + startRadius * s0 + (endAngle === startAngle ? "" : "A" + startRadius + "," + startRadius + " 0 0 " + (endAngle > startAngle ? 1 : 0) + " " + startRadius * c1 + "," + startRadius * s1) + "L" + endRadius * c1 + "," + endRadius * s1;
			},
			mouseover: function(d, active, that) {
				d3.select(that).classed("label--active", active);
				d3.select(d.linkExtensionNode).classed("link-extension--active", active).each(globalFun.phyalign.d3.moveToFront);
				do {
					d3.select(d.linkNode).classed("link--active", active).each(globalFun.phyalign.d3.moveToFront);
				} while(!!(d = d.parent));
			},
			moveToFront: function() {
				this.parentNode.appendChild(this);
			},
			toScale: function() {
				d3.transition().duration(750).each(function() {
					linkExtension.transition().attr("d", function(d) { return step(d.target.x, checked ? d.target.radius : d.target.y, d.target.x, innerRadius); });
					link.transition().attr("d", function(d) { return step(d.source.x, checked ? d.source.radius : d.source.y, d.target.x, checked ? d.target.radius : d.target.y); });
				});
			}
		}
	};

	// Validation of submission form
	$('#phyalign-form__tree').validate({
		rules: {
			tree: {
				required: true
			}
		},
		submitHandler: function(form) {

			// Empty results
			$('#phyalign-tree').empty();

			// Destroy tips
			$('.d3-tip').remove();

			// Cluster layout
			var cluster = d3.layout.cluster()
					.size([360, globalVar.phyalign.d3.innerRadius])
					.children(function(d) { return d.branchset; })
					.value(function(d) { return 1; })
					.sort(function(a,b) { return (a.value - b.value) || d3.ascending(a.length, b.length); })
					.separation(function(a,b) { return 1; }),

				// SVG canvas
				svg = d3.select('#phyalign-tree').append('svg')
					.attr({
						'viewBox': '0 0 ' + globalVar.phyalign.d3.outerRadius * 2 + ' ' + globalVar.phyalign.d3.outerRadius * 2,
						'preserveAspectRatio': 'xMidYMid meet'
					})
					.style({
						'font-family': 'Arial'
					});

				// Chart
				chart = svg.append('g')
					.attr('transform', 'translate('+globalVar.phyalign.d3.outerRadius+','+globalVar.phyalign.d3.outerRadius+')');

			// Parse Newick
			var newick = Newick.parse('('+$('#tree-input').val().replace(/;$/i,'')+')'),
				nodes = cluster.nodes(newick),
				links = cluster.links(nodes),
				leaves = nodes.filter(function(d) { return !d.children; }).length;

			console.log(newick);
			console.log(leaves);

			// Set radius
			globalFun.phyalign.d3.setRadius(
				newick,
				newick.length = 0,
				globalVar.phyalign.d3.innerRadius / globalFun.phyalign.d3.maxLength(newick)
				);

			var linkExtension = chart.append('g')
				.attr('class','link-extensions')
				.selectAll('path')
					.data(links.filter(function(d) { return !d.target.children; }))
					.enter().append('path')
						.each(function(d) {
							d.target.linkExtensionNode = this;
						})
						.attr('d', function(d) {
							return globalFun.phyalign.d3.step(d.source.x, d.source.y, d.target.x, globalVar.phyalign.d3.innerRadius);
						})
						.style('fill', 'none');

			var link = chart.append('g')
				.attr('class','links')
				.selectAll('path')
					.data(links)
					.enter().append('path')
						.each(function(d) {
							d.target.linkNode = this;
						})
						.attr('d', function(d) {
							return globalFun.phyalign.d3.step(d.source.x, d.source.y, d.target.x, d.target.y);
						})
						.style({
							'stroke': function(d) { return '#000'; },
							'fill': 'none'
						});

			// Tooltip
			var tip = d3.tip()
				.attr('class', 'd3-tip tip--bottom')
				.attr('id', 'phyalign-d3-tip')
				.offset([-15,0])
				.direction('n')
				.html(function(d) {
					return ['<ul>',
						'<li class="node-label"><strong>Label</strong>: <span>'+d.name+'</span></li>',
						'<li class="node-branch-length"><strong>Branch length</strong>: <span>'+d.length+'</span></li>',
					'</ul>'].join('');
				});

			// Append labels to charts
			chart.append('g')
				.attr('class', 'labels')
				.selectAll('text')
				.data(nodes.filter(function(d) { return !d.children; }))
				.enter().append('text')
					.attr({
						'dy': '.31em',
						'transform': function(d) {
							return 'rotate(' + (d.x - 90) + ')translate(' + (globalVar.phyalign.d3.innerRadius + 4) + ',0)' + (d.x < 180 ? '' : 'rotate(180)');
						},
						'data-name': function(d) {
							return d.name.replace(/['"]/gi, '');
						}
					})
					.style({
						'font-size': Math.max(5, Math.min(16, Math.round(2 * Math.PI * globalVar.phyalign.d3.outerRadius / leaves) - 5)),
						'text-anchor': function(d) {
							return d.x < 180 ? 'start' : 'end';
						}
					})
					.text(function(d) {
						var n = d.name.replace(/['"]/gi, ''),
							l = Math.max(5, Math.min(16, Math.round(2 * Math.PI * globalVar.phyalign.d3.outerRadius / leaves) - 5)) * 3.5/1.55;

						return (n.length > l) ? n.substr(0,l-1)+'…' : n;
					})
					.on('mousemove', function(e) {
						tip.style({
							'top': (d3.event.pageY - $('#phyalign-d3-tip').outerHeight() - 20) + 'px',
							'left': (d3.event.pageX - 0.5 * $('#phyalign-d3-tip').outerWidth()) + 'px'
						});
					})
					.on('mouseover', function(d) {
						tip.show(d, this);
						globalFun.phyalign.d3.mouseover(d, true, this);
					})
					.on('mouseout', function(d) {
						tip.hide(d, this);
						globalFun.phyalign.d3.mouseover(d, false, this);
					})
					.call(tip);

			// Expose internal variables
			globalFun.phyalign.d3.cluster		= cluster;
			globalFun.phyalign.d3.linkExtension	= linkExtension;
			globalFun.phyalign.d3.link			= link;
			globalFun.phyalign.d3.tip			= tip;
		}
	});

	// General function to check popstate events
	$w.on('popstate', function(e) {
		if (e.originalEvent.state && e.originalEvent.state.lotusbase) {
			var $tab = $('.ui-tabs ul.ui-tabs-nav li a[href="'+window.location.hash+'"]'),
				index = $tab.parent().index(),
				$parentTab = $tab.closest('.ui-tabs');
			$parentTab.tabs("option", "active", index);
		}
	});

});