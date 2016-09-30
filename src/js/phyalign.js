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
					.html('Job data retreived')
					.after([
						'<div class="simple-card align-center">',
						'<p>We have retrieved run data from the Clustal Omega job from the EMBL-EBI server:</p>',
						'<span class="job-id__wrapper" data-job-id="'+job.id+'"><span class="job-id">'+job.id+'</span><span class="tooltip">Press <kbd>Ctrl &#8963;</kbd>/<kbd>Cmd &#8984;</kbd> + <kbd>C</kbd> to copy</span></span>',
						'</div>'
						].join(''));

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
		$('#tree-input').val(globalVar.phyalign.data.data.filter(function(o) {
			return o.type.identifier === 'phylotree';
		})[0].content);
		$('#phyalign-form__tree').trigger('submit');

		// Handoff to third tab
		$('#phyalign-tabs').tabs('option', 'active', 2);
	});

	// Load premade trees
	$d.on('change', '#tree-load', function(e) {
		var $inputs = $('#phyalign-form__tree :input[type="submit"], #tree-input');

		// Disable textarea and submit button until submission
		$inputs.prop('disabled', true);

		// Perform AJAX
		$.ajax({
			url: $(this).val(),
			dataType: 'text'
		})
		.done(function(tree) {
			$('#tree-input').val(tree);
			$('#phyalign-form__tree').trigger('submit');
		})
		.always(function() {
			$inputs.prop('disabled', false);
		});
	});

	// Expose global d3 variables
	globalVar.phyalign.d3 = {
		canvas: {
			width: 960,
			height: 960
		},
		tree: {
			radial: {
				scaleX: 1,
				scaleY: 1,
				outerRadius: 400,
				innerRadius: 400 - 150
			},
			dendrogram: {
				rowHeight: 14
			},
			types: ['radial','dendrogram']
		},
		opts: 	{
			scale: false,
			nodes: {
				bootstrap: false,
				show: false
			},
			leaves: {
				bootstrap: false,
				show: false
			},
			links: {
				bootstrap: false,
				root: true
			},
			linkExtensions: {
				bootstrap: false
			}
		}
	};

	// Expose global d3 functions
	globalFun.phyalign = {
		d3: {
			setScale: {
				radial: function(d, y0, k) {
					d.scale = (y0 += d.length) * k;
					if (d.children) d.children.forEach(function(d) { globalFun.phyalign.d3.setScale.radial(d, y0, k); });
				},
				dendrogram: function(d, y0, k) {
					d.scale = (y0 += d.length) * k;
					if (d.children) d.children.forEach(function(d) { globalFun.phyalign.d3.setScale.dendrogram(d, y0, k); });
				}
			},
			maxLength: function(d) {
				return d.length + (d.children ? d3.max(d.children, globalFun.phyalign.d3.maxLength) : 0);
			},
			step: {
				radial: function(startAngle, startRadius, endAngle, endRadius) {
					var c0 = Math.cos(startAngle = (startAngle - 90) / 180 * Math.PI),
						s0 = Math.sin(startAngle),
						c1 = Math.cos(endAngle = (endAngle - 90) / 180 * Math.PI),
						s1 = Math.sin(endAngle);

					return "M" + startRadius * c0 + "," + startRadius * s0 + (endAngle === startAngle ? "" : "A" + startRadius + "," + startRadius + " 0 0 " + (endAngle > startAngle ? 1 : 0) + " " + startRadius * c1 + "," + startRadius * s1) + "L" + endRadius * c1 + "," + endRadius * s1;
				},
				dendrogram:  function(sX, sY, tX, tY) {
					return 'M' + (sY * globalVar.phyalign.d3.tree.dendrogram.scaleX) + ',' + (sX * globalVar.phyalign.d3.tree.dendrogram.scaleY) + 'V' + (tX * globalVar.phyalign.d3.tree.dendrogram.scaleY) + 'H' + (tY * globalVar.phyalign.d3.tree.dendrogram.scaleX);
				}
			},
			label: {
				radial: {
					transform: function(d) {
						return "rotate(" + (d.x - 90) + ")translate(" + (globalVar.phyalign.d3.tree.radial.innerRadius + 8) + ",0)" + (d.x < 180 ? "" : "rotate(180)");
					},
					textAnchor: function(d) {
						return d.x < 180 ? 'start' : 'end';
					}
				},
				dendrogram: {
					transform: function(d) {
						return 'translate(' + (d.y * globalVar.phyalign.d3.tree.dendrogram.scaleX) + ',' + (d.x * globalVar.phyalign.d3.tree.dendrogram.scaleY) + ')';
					},
					textAnchor: function(d) {
						return 'start';
					}
				}
			},
			branchNode: {
				radial: function(startAngle, startRadius) {
					var c0 = Math.cos(startAngle = (startAngle - 90) / 180 * Math.PI),
						s0 = Math.sin(startAngle);

					return 'translate(' + startRadius 	* c0 + ','+ startRadius * s0 +')';
				},
				dendrogram: function(x,y) {
					return 'translate(' + (y * globalVar.phyalign.d3.tree.dendrogram.scaleX) + ',' + (x * globalVar.phyalign.d3.tree.dendrogram.scaleY) + ')';
				}
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
			tree: {
				init: function() {
					// Zoom
					var zoomListener = d3.behavior.zoom().scaleExtent([0.2,5]).on('zoom', function() {
						d3.select('#stage').attr("transform", "translate(" + d3.event.translate + ") scale(" + d3.event.scale + ")");
					});

					var svg = d3.select('#phyalign-tree__svg').append('svg')
							.attr({
								//'viewBox': '0 0 ' + globalVar.phyalign.d3.canvas.width + ' ' + globalVar.phyalign.d3.canvas.height,
								'preserveAspectRatio': 'xMidYMid meet'
							})
							.style({
								'font-family': 'Arial'
							})
							.call(zoomListener),

						// Capture
						capture = svg.append('rect')
							.attr({
								'id': 'capture',
								'width': Math.max($w.width(), $w.height()),
								'height': Math.max($w.width(), $w.height()),
								'transform': 'translate(0,0)'
							})
							.style('fill', 'red')
							.style('pointer-events', 'all'),

						// Stage
						stage = svg.append('g')
								.attr('id', 'stage');

					// Create tree
					globalFun.phyalign.d3.tree.create();

					// Timeout to show controls
					window.setTimeout(function() {
						$('#phyalign-tree').addClass('controls--visible');
					}, 500);

					// Expose
					globalVar.phyalign.d3.tree.stage = stage;

				},
				create: function(forceTreeType) {

					// Cluster layout
					var cluster = d3.layout.cluster()
							.size([360, globalVar.phyalign.d3.tree.radial.innerRadius])
							.children(function(d) { return d.branchset; })
							.value(function(d) { return 1; })
							.sort(function(a,b) { return (a.value - b.value) || d3.ascending(a.length, b.length); })
							.separation(function(a,b) { return 1; });

					// Parse Newick
					var newick = Newick.parse('('+$('#tree-input').val().replace(/;$/i,'')+':0.01)'),
						nodes = cluster.nodes(newick),
						links = cluster.links(nodes),
						leaves = nodes.filter(function(d) { return !d.children; }).length;

					// Color scale for bootstrap values
					var fills = ["#a6bddb","#74a9cf","#3690c0","#0570b0","#045a8d","#023858"],
						bootstrapColor = d3.scale.linear().domain(d3.range(0, 1, 1.0 / (fills.length - 1))).range(fills),
						bootstrapColorMap = d3.scale.linear().domain([0, d3.max(nodes, function(d) { return +d.bootstrap; })]).range([0, 1]).nice();

					// Determine tree type before drawing
					if(forceTreeType) {
						globalVar.phyalign.d3.tree.type = forceTreeType;
					} else {
						globalVar.phyalign.d3.tree.type = leaves > 20 ? 'radial' : 'dendrogram';
					}

					var treeType = globalVar.phyalign.d3.tree.type;

					// Update tree type options
					$('#tc__layout').val(treeType);

					// Toggle tree controls
					globalFun.phyalign.d3.tree.controls();

					// Set y-axis scale for dendrogram
					var scaleY_calc = leaves * globalVar.phyalign.d3.tree.dendrogram.rowHeight / globalVar.phyalign.d3.tree.radial.innerRadius;
					globalVar.phyalign.d3.tree.dendrogram.scaleX = $('#phyalign-tree__svg svg').width() / 360;
					globalVar.phyalign.d3.tree.dendrogram.scaleY = scaleY_calc < 1.5 ? 1.5 : scaleY_calc;

					// Chart
					var chart = d3.select('#stage').append('g').attr('id', 'tree');

					// Create groups
					chart.append('g').attr('class', 'links');
					chart.append('g').attr('class', 'link-extensions');
					chart.append('g').attr('class', 'nodes');
					chart.append('g').attr('class', 'labels');

					// Tooltips
					var tips = {
						leaves: d3.tip()
							.attr('class', 'd3-tip tip--bottom')
							.attr('id', 'phyalign-d3__leaves-tip')
							.offset([-15,0])
							.direction('n')
							.html(function(d) {
								return ['<ul>',
									'<li class="node-label"><strong>Label</strong>: <span>'+d.name+'</span></li>',
									'<li class="node-branch-length"><strong>Branch length</strong>: <span>'+d.length+'</span></li>',
								'</ul>'].join('');
							})
					};

					// Store tree data
					globalVar.phyalign.d3.tree.data = {
						newick:		newick,
						chart:		chart,
						links:		links,
						nodes:		nodes,
						leaves:		leaves,
						tips:		tips
					};

					// Draw tree
					globalFun.phyalign.d3.tree.draw[globalVar.phyalign.d3.tree.type]();

					// Expose
					globalVar.phyalign.d3.tree.bootstrap = {
						color: bootstrapColor,
						colorMap: bootstrapColorMap
					};
				},
				draw: {
					dendrogram: function() {
						// Variables
						var d = globalVar.phyalign.d3.tree.data;
						var newick		= d.newick,
							chart		= d.chart,
							links		= d.links,
							nodes		= d.nodes,
							leaves		= d.leaves,
							tips		= d.tips;

						// Position nicely
						chart.attr({
							'transform': 'translate(100,20)',
							'data-transform': 'translate(100,20)'
						});

						// Set scale
						globalFun.phyalign.d3.setScale[globalVar.phyalign.d3.tree.type](
							newick,
							newick.length = 0,
							globalVar.phyalign.d3.tree.radial.innerRadius / globalFun.phyalign.d3.maxLength(newick)
							);

						// Return link, linkExtension, and node because we need them
						var link			= globalFun.phyalign.d3.tree.update.link(d),
							linkExtension	= globalFun.phyalign.d3.tree.update.linkExtension(d),
							node			= globalFun.phyalign.d3.tree.update.node(d);

						// Update labels
						globalFun.phyalign.d3.tree.update.label(d);

						// Expose
						globalVar.phyalign.d3.tree.linkExtension	= linkExtension;
						globalVar.phyalign.d3.tree.link				= link;
						globalVar.phyalign.d3.tree.branchNode		= node;
					},
					radial: function() {
						// Variables
						var d = globalVar.phyalign.d3.tree.data;
						var newick	= d.newick,
							chart	= d.chart,
							links	= d.links,
							nodes	= d.nodes,
							leaves	= d.leaves,
							tips	= d.tips;

						// Position nicely
						chart.attr({
							'transform': 'translate('+($('#phyalign-tree__svg svg').width()/2)+','+($('#phyalign-tree__svg svg').height()/2)+')',
							'data-transform': 'translate('+($('#phyalign-tree__svg svg').width()/2)+','+($('#phyalign-tree__svg svg').height()/2)+')'
						});

						// Set scale
						globalFun.phyalign.d3.setScale[globalVar.phyalign.d3.tree.type](
							newick,
							newick.length = 0,
							globalVar.phyalign.d3.tree.radial.innerRadius / globalFun.phyalign.d3.maxLength(newick)
							);

						// Return link, linkExtension, and node because we need them
						var link			= globalFun.phyalign.d3.tree.update.link(d),
							linkExtension	= globalFun.phyalign.d3.tree.update.linkExtension(d),
							node			= globalFun.phyalign.d3.tree.update.node(d);

						// Update labels
						globalFun.phyalign.d3.tree.update.label(d);

						// Expose
						globalVar.phyalign.d3.tree.linkExtension	= linkExtension;
						globalVar.phyalign.d3.tree.link				= link;
						globalVar.phyalign.d3.tree.branchNode		= node;
					}
				},
				update: {
					link: function(d) {
						// Variables
						var chart = d.chart,
							links = d.links,
							treeType = globalVar.phyalign.d3.tree.type,
							chartLinks = chart.select('g.links');

						// Update links
						chartLinks.selectAll('path.link')
						.attr({
							'd': function(d) {
								return globalFun.phyalign.d3.step[treeType](
									d.source.x,
									globalVar.phyalign.d3.opts.scale ? d.source.scale : d.source.y,
									d.target.x,
									globalVar.phyalign.d3.opts.scale ? d.target.scale : d.target.y);
							}
						});

						// Append links
						return chartLinks
							.selectAll('path.link')
								.data(links)
								.enter().append('path')
									.each(function(d, i) {
										d.target.linkNode = this;
										if(i === 0) d.root = true;
									})
									.attr({
										'class': 'link',
										'data-bootstrap-value': function(d) {
											return d.source.bootstrap ? d.source.bootstrap : '';
										},
										'd': function(d) {
											return globalFun.phyalign.d3.step[treeType](
												d.source.x,
												globalVar.phyalign.d3.opts.scale ? d.source.scale : d.source.y,
												d.target.x,
												globalVar.phyalign.d3.opts.scale ? d.target.scale : d.target.y);
										}
									})
									.style({
										'stroke': '#555',
										'stroke-width': 1.5,
										'fill': 'none'
									});
					},
					linkExtension: function(d) {
						// Variables
						var chart		= d.chart,
							links		= d.links,
							treeType	= globalVar.phyalign.d3.tree.type,
							chartLinkExtensions = chart.select('g.link-extensions');

						// Update link extensions
						chartLinkExtensions.selectAll('path.link-extension')
						.attr({
							'd': function(d) {
								return globalFun.phyalign.d3.step[treeType](
									d.target.x,
									globalVar.phyalign.d3.opts.scale ? d.target.scale : d.target.y,
									d.target.x,
									globalVar.phyalign.d3.tree.radial.innerRadius);
							}
						});

						// Add new link extensions
						return chartLinkExtensions
							.selectAll('path.link-extension')
								.data(links.filter(function(d) { return !d.target.children; }))
								.enter().append('path')
									.each(function(d) {
										d.target.linkExtensionNode = this;
									})
									.attr({
										'd': function(d) {
											return globalFun.phyalign.d3.step[treeType](
												d.target.x,
												globalVar.phyalign.d3.opts.scale ? d.target.scale : d.target.y,
												d.target.x,
												globalVar.phyalign.d3.tree.radial.innerRadius);
										},
										'class': 'link-extension'
									})
									.style({
										'fill': 'none',
										'stroke': '#000000',
										'stroke-width': 1.5,
										'stroke-opacity': 0.25,
										'stroke-dasharray': '2, 2',
									});
					},
					node: function(d) {
						// Variables
						var chart 		= d.chart,
							nodes 		= d.nodes,
							treeType	= globalVar.phyalign.d3.tree.type,
							chartNodes	= chart.select('g.nodes');

						// Update nodes
						chartNodes.selectAll('circle.node')
						.attr({
							'transform': function(d) {
								return globalFun.phyalign.d3.branchNode[treeType](d.x, d.y);
							}
						});

						// Add new nodes
						return chartNodes
							.selectAll('circle.node')
								.data(nodes.filter(function(d,i){ return i > 0; }))
								.enter().append('circle')
									.each(function(d, i) {
										if(!d.children) d.bootstrap = d.parent.bootstrap;	
									})
									.attr({
										'r': 3,
										'class': function(d) {
											return 'node ' + (d.children ? 'node--inter' : 'node--leaf');
										},
										'transform': function(d) {
											return globalFun.phyalign.d3.branchNode[treeType](d.x, d.y);
										}
									})
									.style({
										'fill': '#555',
										'fill-opacity': 0
									});
					},
					label: function(d) {
						// Variables
						var chart		= d.chart,
							nodes		= d.nodes,
							tips		= d.tips,
							leaves		= d.leaves,
							treeType	= globalVar.phyalign.d3.tree.type,
							chartLabels = chart.select('g.labels');

						// Update labels
						chartLabels.selectAll('text.label')
						.attr({
							'transform': globalFun.phyalign.d3.label[treeType].transform,
							'dx': function() {
								if(treeType === 'dendrogram') {
									return 8;
								} else {
									return 0;
								}
							}
						}).
						style({
							'text-anchor': globalFun.phyalign.d3.label[treeType].textAnchor,
							'font-size': function() {
								if(treeType === 'radial') {
									return Math.max(5, Math.min(14, Math.round(2 * Math.PI * globalVar.phyalign.d3.tree.radial.outerRadius / leaves) - 5));
								} else {
									return 14;
								}
							}
						});

						// Add labels to charts
						chartLabels
							.selectAll('text.label')
							.data(nodes.filter(function(d) { return !d.children; }))
							.enter().append('text')
								.attr({
									'class': 'label',
									'dy': '.31em',
									'dx': function() {
										if(treeType === 'dendrogram') {
											return 8;
										} else {
											return 0;
										}
									},
									'transform': globalFun.phyalign.d3.label[treeType].transform,
									'data-name': function(d) {
										return d.name.replace(/['"]/gi, '');
									}
								})
								.style({
									'font-size': function() {
										if(treeType === 'radial') {
											return Math.max(5, Math.min(14, Math.round(2 * Math.PI * globalVar.phyalign.d3.tree.radial.outerRadius / leaves) - 5));
										} else {
											return 14;
										}
									},
									'text-anchor': globalFun.phyalign.d3.label[treeType].textAnchor
								})
								.text(function(d) {
									return d.name;
								});


						chartLabels
							.selectAll('text.label')
							.on('mousemove', function(e) {
									tips.leaves.style({
										'top': (d3.event.pageY - $('#phyalign-d3__leaves-tip').outerHeight() - 20) + 'px',
										'left': (d3.event.pageX - 0.5 * $('#phyalign-d3__leaves-tip').outerWidth()) + 'px'
									});
								})
								.on('mouseover', function(d) {
									tips.leaves.show(d, this);
									globalFun.phyalign.d3.mouseover(d, true, this);
								})
								.on('mouseout', function(d) {
									tips.leaves.hide(d, this);
									globalFun.phyalign.d3.mouseover(d, false, this);
								})
								.call(tips.leaves);
					}
				},
				controls: function() {
					var treeType = globalVar.phyalign.d3.tree.type;
					$('.tc__treeType').hide().find(':input').prop('disabled', true);
					$('#tc__'+treeType).show().find(':input').prop('disabled', false);
				}
			},
			drawTree: function() {

				// Draw scale bar
//				var scaleAxis = d3.svg.axis().scale(d3.scale.linear().domain([0,d3.max(nodes, function(d) { return +d.bootstrap; })]).range([0, d3.max(nodes, function(d) { return +d.radius; })])).orient('horizontal').tickFormat(function (d) {
//						return d;
//					}).ticks(5).outerTickSize(0).innerTickSize(5);
//
//				svg.append('g')
//				.attr({
//					'class': 'x axis',
//				})
//				.call(scaleAxis)
//				.selectAll('text')
//					.attr({
//						'class': 'x axis break',
//					})
//					.style({
//						'font-size': 10,
//						'text-anchor': 'middle'
//					});

			},
			update: {
				link: function(_opts) {
					var link				= d3.selectAll('path.link'),
						bootstrapColor		= globalVar.phyalign.d3.tree.bootstrap.color,
						bootstrapColorMap	= globalVar.phyalign.d3.tree.bootstrap.colorMap;
					
					// Update options
					$.extend(true, globalVar.phyalign.d3.opts, _opts);

					// Do the work
					link.transition().duration(750)
					.style({
						'stroke': function(d) {
							return (globalVar.phyalign.d3.opts.links.bootstrap && d.source.bootstrap && !isNaN(+d.source.bootstrap)) ? bootstrapColor(bootstrapColorMap(+d.source.bootstrap)) : '#555';
						},
						'stroke-opacity': function(d) {
							return d.root ? (globalVar.phyalign.d3.opts.links.root ? 1 : 0) : 1;
						}
					})
					.attr('d', function(d) {
						return globalFun.phyalign.d3.step[globalVar.phyalign.d3.tree.type](
							d.source.x,
							globalVar.phyalign.d3.opts.scale ? d.source.scale : d.source.y,
							d.target.x,
							globalVar.phyalign.d3.opts.scale ? d.target.scale : d.target.y
							);
					});
				},
				linkExtension: function(_opts) {
					var linkExtension = d3.selectAll('path.link-extension');

					// Update options
					$.extend(true, globalVar.phyalign.d3.opts, _opts);

					// Do the work
					linkExtension.transition().duration(750).attr('d', function(d) {
						return globalFun.phyalign.d3.step[globalVar.phyalign.d3.tree.type](
							d.target.x,
							globalVar.phyalign.d3.opts.scale ? d.target.scale : d.target.y,
							d.target.x,
							globalVar.phyalign.d3.tree.radial.innerRadius
							);
					});
				},
				branchNode: function(_opts) {
					var branchNode			= d3.selectAll('circle.node'),
						bootstrapColor		= globalVar.phyalign.d3.tree.bootstrap.color,
						bootstrapColorMap	= globalVar.phyalign.d3.tree.bootstrap.colorMap;

					// Update options
					$.extend(true, globalVar.phyalign.d3.opts, _opts);

					// Do the work
					branchNode.transition().duration(750)
					.style({
						'fill': function(d) {
							return (globalVar.phyalign.d3.opts.nodes.bootstrap && d.bootstrap && !isNaN(+d.bootstrap)) ? bootstrapColor(bootstrapColorMap(+d.bootstrap)) : '#555';
						},
						'fill-opacity': function(d) {
							if(d.children) {
								return globalVar.phyalign.d3.opts.nodes.show ? 1 : 0;
							} else {
								return globalVar.phyalign.d3.opts.leaves.show ? 1 : 0;
							}
						}
					})
					.attr({
						'transform': function(d) {
							return globalFun.phyalign.d3.branchNode[globalVar.phyalign.d3.tree.type](d.x, globalVar.phyalign.d3.opts.scale ? d.scale : d.y);
						}
					});
				}
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
			$('#phyalign-tree__svg').empty();

			// Destroy tips
			$('.d3-tip').remove();

			// Draw tree
			globalFun.phyalign.d3.tree.init();
		}
	});

	// Tree controls
	$('#tc__scale').on('change', function() {
		var checked = this.checked;
		globalFun.phyalign.d3.update.linkExtension({
			scale: checked
		});
		globalFun.phyalign.d3.update.link({
			scale: checked
		});
		globalFun.phyalign.d3.update.branchNode({
			scale: checked
		});
	});
	$('#tc__bootstrap-nodes').on('change', function() {
		var checked = this.checked,
			opts = {
				nodes: {
					bootstrap: checked,
				}
			};

		if(checked) {
			$('#tc__internodes').prop('checked', true);
			opts.nodes.show = true;
		}

		globalFun.phyalign.d3.update.branchNode(opts);
	});
	$('#tc__bootstrap-links').on('change', function() {
		var checked = this.checked;
		globalFun.phyalign.d3.update.link({
			links: {
				bootstrap: checked
			}
		});
	});
	$('#tc__root').on('change', function() {
		var checked = this.checked;
		globalFun.phyalign.d3.update.link({
			links: {
				root: checked
			}
		});
	});
	$('#tc__internodes').on('change', function() {
		var checked = this.checked;
		globalFun.phyalign.d3.update.branchNode({
			nodes: {
				show: checked
			}
		});
	});
	$('#tc__leaves').on('change', function() {
		var checked = this.checked;
		globalFun.phyalign.d3.update.branchNode({
			leaves: {
				show: checked
			}
		});
	});
	$('#tc__layout').on('change', function() {
		if(globalVar.phyalign.d3.tree.types.indexOf($(this).val()) >= 0) {
			// Render tree again
			globalVar.phyalign.d3.tree.type = $(this).val();
			globalFun.phyalign.d3.tree.draw[globalVar.phyalign.d3.tree.type]();

			// Toggle tree controls
			globalFun.phyalign.d3.tree.controls();
		} else {
			console.log('Invalid tree type!');
		}
	});

	// Radial tree
	$('#tc__radial__rotation').on('input', $.throttle(50, function() {
		var t = d3.transform(d3.select('#tree').attr('transform')),
			r = +$(this).val();

		d3.select('#tree').attr({
			'transform': 'translate('+t.translate[0]+','+t.translate[1]+') rotate('+r+')'
		});
		d3.selectAll('text.label')
			.style({
				'text-anchor': function(d) {
					return (d.x + r) % 360 < 180 ? 'start' : 'end';
				}
			})
			.attr({
				'transform': function(d) {
					return "rotate(" + (d.x - 90) + ")translate(" + (globalVar.phyalign.d3.tree.radial.innerRadius + 8) + ",0)" + ((d.x + r) % 360 < 180 ? "" : "rotate(180)");
				}
			});
	}));

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