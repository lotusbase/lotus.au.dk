$(function() {

	// Global variables
	globalVar.view = {};
	globalVar.tables = {};

	// ExpAt functions
	globalFun.expat = {
		replaceIDClass: function(string) {
			return string.replace(/[\#\.]/gi,'');
		},
		heatmap: {
			filterLegendData: function(opts) {
				// Parse options
				var d = opts.data,
					i = opts.index,
					scale = opts.scale,
					length = opts.length;

				var n = Number(Math.round(d+'e2')+'e-2');

				if(scale === 'log_10' || scale === 'square_root') {
						// Round floating point numbers
					var log_n = Math.round(Math.log(n) / Math.LN10 * 1e6) / 1e6,
						mod_log_n = Math.round((log_n % 1) * 1e6) / 1e6;

					// Breaks at half way point
					// log(5) = 0.69897
					if(mod_log_n === 0 || mod_log_n === 0.69897) {
						return opts.value_if_true;
					} else if(opts.force_outer && (i === 0 || i === length-1)) {
						return opts.value_if_true;
					} else {
						return opts.value_if_false;
					}
				} else if(scale === 'linear') {

					// Round floating point numbers
					var mod_5 = Math.round((n % 5) * 1e6) / 1e6;
					if(mod_5 === 0) {
						return opts.value_if_true;
					} else {
						return opts.value_if_false;
					}

				}
			}
		}
	};
			
	// Gene functions
	globalFun.view = {
		init: function() {
			globalFun.view.corgi();
			globalFun.view.expat();
			globalFun.view.domain();

			// Initialize sequence tabs
			$sequenceTabs = $('#view__sequence').tabs();

			// General function to check popstate events
			$w.on('popstate', function(e) {
				if (e.originalEvent.state && e.originalEvent.state.lotusbase) {
					var $tab = $('.ui-tabs ul.ui-tabs-nav li a[href="'+window.location.hash+'"]'),
						index = $tab.parent().index(),
						$parentTab = $tab.closest('.ui-tabs');
					$parentTab.tabs("option", "active", index);
				}
			});
			$d.on('click', '.ui-tabs a.ui-tabs-anchor', function(e) {
				e.preventDefault();
				window.history.pushState({lotusbase: true}, '', $(this).attr('href'));
			});

			// Modal box for domain prediction links
			$d.on('click', 'a[data-desc-id][data-desc-source]', function(e) {
				e.preventDefault();

				var $t = $(this),
					domainAJAX = $.ajax({
						url: '/api/v1/view/domain/'+$t.attr('data-desc-source')+'/'+$t.attr('data-desc-id'),
						dataType: 'json'
					});

				// Open modal
				globalFun.modal.open({
					'title': 'Predicted domain '+$t.attr('data-desc-id'),
					'content': '<div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div><p class="align-center">Loading data&hellip;</p>'
				});

				// AJAX promise
				domainAJAX
				.done(function(d) {

					var p = d.data,
						linklist = function(a, s) {
							var o = '',
								_s = {
									'go': 'http://www.ebi.ac.uk/interpro/search?q=',
									'pubmed': 'https://www.ncbi.nlm.nih.gov/pubmed/',
									'pfam': 'http://pfam.xfam.org/family/'
								};
							for (var i = 0; i < a.length; i++) {
								o += '<li><a href="'+_s[s]+a[i]+'" class="link--reset" target="_blank">'+a[i]+'</a></li>';
							}
							return o;
						};

					// Show modal
					globalFun.modal.update((function(domain){
						var o;
						if(domain === 'interpro') {
							o = {
									'title': 'Predicted domain '+$t.attr('data-desc-id'),
									'content': [
										'<p class="align-center"><strong>'+p.id+'</strong> is a '+p.fields.name.join(', ')+'.</p>',
										'<p>'+p.fields.description.join('. ')+'</p>',
										'<table class="table--dense">',
											'<thead>',
												'<tr>',
													'<th scope="col" style="width: 25%">Field</th>',
													'<th scope="col">Value</th>',
												'</tr>',
											'</thead>',
											'<tbody>',
												(p.id 								? '<tr><th scope="row">ID</th><td>'+p.id+'</td></tr>' : ''),
												(p.fields.INTERPRO_PARENT.length	? '<tr><th scope="row">Associated parent</th><td>'+p.fields.INTERPRO_PARENT+'</td></tr>' : ''),
												(p.fields.domain_source.length		? '<tr><th scope="row">Domain source</th><td>'+p.fields.domain_source.join(', ')+'</td></tr>' : ''),
												(p.fields.type.length				? '<tr><th scope="row">Type</th><td>'+p.fields.type.join(', ')+'</td></tr>' : ''),
												(p.fields.GO.length					? '<tr><th scope="row"><abbr title="Gene Ontology">GO</abbr> '+globalFun.pl(p.fields.GO.length, 'prediction')+'</th><td><ul class="list--floated">'+linklist(p.fields.GO, 'go')+'</ul></td></tr>' : ''),
												(p.fields.PUBMED.length 			? '<tr><th scope="row">PubMed references</th><td><ul class="list--floated">'+linklist(p.fields.PUBMED, 'pubmed')+'</ul></td></tr>' : ''),
												(p.fields.PFAM.length				? '<tr><th scope="row">Related Pfam entries</th><td><ul class="list--floated">'+linklist(p.fields.PFAM, 'pfam')+'</ul></td></tr>' : ''),
											'</tbody>',
										'</table>'
									].join(''),
									'class': 'wide'
								};
							} else if(domain === 'pfam') {
								o = {
									'title': 'Predicted domain '+$t.attr('data-desc-id'),
									'content': [
										'<p class="align-center"><strong>'+p.id+'</strong> is a '+p.fields.description.join(', ')+'.</p>',
										'<table class="table--dense">',
											'<thead>',
												'<tr>',
													'<th scope="col" style="width: 25%">Field</th>',
													'<th scope="col">Value</th>',
												'</tr>',
											'</thead>',
											'<tbody>',
												(p.id 								? '<tr><th scope="row">ID</th><td>'+p.id+'</td></tr>' : ''),
												(p.short_name 						? '<tr><th scope="row">Accession</th><td>'+p.short_name+'</td></tr>' : ''),
												(p.fields.domain_source.length		? '<tr><th scope="row">Domain source</th><td>'+p.fields.domain_source.join(', ')+'</td></tr>' : ''),
												(p.fields.type.length				? '<tr><th scope="row">Type</th><td>'+p.fields.type.join(', ')+'</td></tr>' : ''),
												(p.fields.PUBMED.length 			? '<tr><th scope="row">PubMed references</th><td><ul class="list--floated">'+linklist(p.fields.PUBMED, 'pubmed')+'</ul></td></tr>' : ''),
											'</tbody>',
										'</table>'
									].join('')
								};
							}
							return o;
					})($t.attr('data-desc-source')));
				})
				.fail(function(jqXHR, textStatus, errorThrown) {
					var d = jqXHR.responseJSON;
					globalFun.modal.update({
						'title': errorThrown,
						'content': '<p>We have encountered an error '+(d.status ? '(error code: <code>'+d.status+'</code>) ' : '')+'while processing your request.</p>'+(d.message ? '<p>'+d.message+'</p>' : ''),
						'class': 'warning'
					});
				});
			});

			// Update badge
			var updateBadge = function(subset, selector) {
				var $badge = $('#view__lore1-inserts h3 span.badge');

				$badge.text($('#lore1-list').find(selector).length);

				if(subset) {
					$badge.addClass('subset');
				} else {
					$badge.removeClass('subset');
				}
			};

			// Bind change event to LORE1 filtering
			$('#lore1-type').on('change', function() {
				var $t = $(this),
					$l = $('#lore1-list');

				// Show intronic lines
				if($t.val() === 'intronic') {
					$l.find('li.lore1--intronic').show();
					$l.find('li').not('.lore1--intronic').hide();

					updateBadge(true, 'li.lore1--intronic');
				}
				// Show exonic lines
				else if($t.val() === 'exonic') {
					$l.find('li.lore1--exonic').show();
					$l.find('li').not('.lore1--exonic').hide();

					updateBadge(true, 'li.lore1--exonic');
				}
				// Default fallback: show all LORE1 lines
				else {
					$l.find('li').show();
					updateBadge(false, 'li');
				}

			});

			// Bind change event to dataset
			$('#expat-dataset').on('change', function() {
				globalFun.view.corgi(
					$(this).val(),
					$(this).find('option:selected').attr('data-idtype')
					);

				globalFun.view.expat(
					$(this).val(),
					$(this).find('option:selected').attr('data-idtype')
					);
			});

			// Buttons
			var buttons = {
				dompred: function(data, row, column, node) {
					var _data;

					// Parse table data
					if (column === 1 || column === 6) {
						_data = $(data).find('span.dropdown--title').text();
					} else {
						_data = data;
					}

					// Return data
					return (_data === '–') ? '' : _data.replace(/<(?:.|\n)*?>/gm, '');
				},
				corgi: function(data, row, column, node) {
					var _data;

					// Parse table data
					if (column === 0) {
						_data = $(data).find('span.dropdown--title').text();
					} else {
						_data = data;
					}

					// Return data
					return (_data === '–') ? '' : _data.replace(/<(?:.|\n)*?>/gm, '');
				}
			};

			// jQuery data tables
			var $domPredTable = $('#view__domain-prediction table').DataTable({
				'pagingType': 'full_numbers',
				'dom': 'lftiprB',
				'buttons': [
					{
						extend: 'csv',
						exportOptions: {
							columns: [0,1,2,3,4,5,6],
							format: {
								body: function(data, row, column, node) {
									return buttons.dompred(data, row, column, node);
								}
							}
						}
					},
					{
						extend: 'print',
						exportOptions: {
							columns: [0,1,2,3,45,6],
							format: {
								body: function(data, row, column, node) {
									return buttons.dompred(data, row, column, node);
								}
							}
						}
					}
				]
			});

			globalVar.tables.corgi = $('#coexpression__table').DataTable({
				'pagingType': 'full_numbers',
				'dom': 'lftiprB',
				'order': [[1, 'desc']],
				'buttons': [
					{
						extend: 'csv',
						exportOptions: {
							columns: [0,1,2],
							format: {
								body: function(data, row, column, node) {
									return buttons.corgi(data, row, column, node);
								}
							}
						}
					},
					{
						extend: 'print',
						exportOptions: {
							columns: [0,1,2],
							format: {
								body: function(data, row, column, node) {
									return buttons.corgi(data, row, column, node);
								}
							}
						}
					}
				]
			});

			// Stupid tables
			$('#view__function table').stupidtable();
		}
	};

	// Get domain data
	globalFun.view.domain = function() {
		// Variables for d3 charts
		var domain = {
				chart: {
					width: 960,
					minHeight: 150,
					rowHeight: 10,
					domainHeight: 6,
					margin: {
						top: 10,
						bottom: 20,
						left: 10,
						right: 20
					}
				}
			};

		// Colors
		var colors = {
				'interpro_type': {
					label: 'InterPro type',
					dataType: 'discrete',
					domain: ['domain','repeat','family','active_site'],
					range: function() { return ['#44c83a','#ff7f2b','#ef211f','#bc53d0']; },
					value: function(s) {
						return s.toLowerCase().replace(' ','_');
					},
					key: 'InterProType'
				},
				'prediction_algorithm': {
					label: 'Prediction algorithm',
					dataType: 'discrete',
					domain: [
						'CDD',
						'Gene3D',
						'PANTHER',
						'PRINTS',
						'Pfam',
						'Phobius',
						'ProSitePatterns',
						'ProSiteProfiles',
						'SMART',
						'SUPERFAMILY',
						'SignalP_EUK',
						'SignalP_GRAM_NEG',
						'SignalP_GRAM_POS',
						'TMHMM'
					],
					range: function(d) {
						var c = [];
						for (var i = 0; i < d.length; i++) {
							c.push(d3.hcl((i*(360/d.length))%360, 40, 70).toString());
						}
						return c;
					},
					value: function(s) { return s; },
					key: 'Source'
				}
			},
			colorType = 'interpro_type',	// Default: highlight by InterPro type
			p;								// Store API data

		// Functions
		var computeColors = function(type, valueIn, valuesIn) {

				// Coerce type
				if(!type) type = colorType;

				var scale = d3.scale.ordinal().range(colors[type].range(colors[type].domain)).domain(colors[type].domain);

				if(!valueIn) {
					return '#999999';
				} else {
					return scale(colors[type].value(valueIn));
				}
			};

		// Perform AJAX call
		var domainsAJAX = $.ajax({
			type: 'GET',
			url: root + '/api/v1/view/domains/'+$('#domain-prediction').attr('data-protein'),
			dataType: 'json'
		});
		domainsAJAX
		.done(function(d) {

			// Hide loader
			$('#domain-prediction__loader').slideUp(500);

			// Data
			p = d.data;

			// Chart height
			domain.chart.height = (domain.chart.rowHeight * p.length < domain.chart.minHeight) ? domain.chart.minHeight : domain.chart.rowHeight * p.length;

			var svg = d3.select('#domain-prediction')
				.insert('svg', ':first-child')
				.attr({
					'viewBox': '0 0 ' + domain.chart.width + ' ' + (domain.chart.margin.top + domain.chart.margin.bottom + domain.chart.height),
					'preserveAspectRatio': 'xMidYMid meet'
				})
				.style('font-family', 'Arial');

			svg.append('rect')
				.attr({
					'transform': 'translate('+(domain.chart.margin.left)+','+(domain.chart.margin.top*0.5)+')',
					'width': domain.chart.width - (domain.chart.margin.left + domain.chart.margin.right),
					'height': domain.chart.height + domain.chart.margin.top
				})
				.style({
					'fill': '#ddd'
				});
			svg.append('line')
				.attr({
					'x1': domain.chart.margin.left,
					'y1': domain.chart.margin.top*0.5,
					'x2': domain.chart.width - domain.chart.margin.right,
					'y2': domain.chart.margin.top*0.5
				})
				.style({
					stroke: '#999'
				});

			var stage = svg.append('g')
				.attr({
					'id': 'stage',
					'transform': 'translate('+domain.chart.margin.left+','+domain.chart.margin.top+')'
				});

			// Scale width
			var scaleX = d3.scale.linear().range([0, domain.chart.width - domain.chart.margin.left - domain.chart.margin.right]).domain([1, d3.max(p, function(g) { return +g.DomainEnd; })]),
				ticksX = scaleX.ticks();

			// Scale height
			var scaleY = function(valueIn, length) {
				return d3.scale.linear().domain([0, length - 1]).range([0, domain.chart.rowHeight * p.length - (domain.chart.rowHeight - domain.chart.domainHeight)])(valueIn);
			};

			// Add custom tick at position 1 and max value, but ensure that max value is only added when there is sufficient clearest with the computed last tick
			ticksX.push(1);
			var maxX = d3.max(p, function(g) { return +g.DomainEnd; }),
				lastTickDistance = scaleX(maxX - Math.max.apply(null, ticksX));
			if(lastTickDistance > 20) {
				ticksX.push(maxX);
			}

			// Axes
			var domainXAxis = d3.svg.axis().scale(scaleX).orient('bottom').innerTickSize(-1 * (domain.chart.height + domain.chart.margin.top)).outerTickSize(0).tickValues(ticksX);

			// Draw x axis
			var xAxis = stage.append('g')
			.attr({
				'class': 'x axis',
				'transform': 'translate(0,'+(domain.chart.height + domain.chart.margin.top*0.5)+')'
			})
			.call(domainXAxis);

			xAxis.selectAll('text')
				.style({
					'text-anchor': 'middle',
					'font-size': '12px',
					'dy': '0.35em'
				});
			xAxis.selectAll('line')
				.style({
					'stroke': '#999',
					'stroke-dasharray': '2,2'
				});
			xAxis.select('path.domain')
				.style({
					stroke: '#999'
				});

			// Tips
			var domTip = d3.tip()
			.attr({
				'class': 'd3-tip tip--bottom',
				'id': 'domain-tip'
			})
			.offset([-15,0])
			.direction('n')
			.html(function(d) {
				var type = d.InterProType;
				return ['<ul>',
					'<li><strong>Prediction algorithm (source)</strong>: '+d.Source+'</li>',
					'<li><strong>Domain identifier</strong>: '+d.SourceID+'</li>',
					(d.SourceDescription ? '<li><strong>Description</strong>: '+d.SourceDescription+'</li>' : ''),
					(d.InterProID ? '<li><strong>InterPro mapping</strong>: '+d.InterProID+(type ? ' <span class="pill" style="background-color: '+computeColors('interpro_type', type)+'"> '+type+'</span>' : '')+'</li>' : ''),
					'<li><strong>Span</strong>: '+d.DomainStart+'&ndash;'+d.DomainEnd+' (length: '+(d.DomainEnd - d.DomainStart + 1)+')</li>',
					'<li><strong>E-value</strong>: '+d.Evalue+'</li>',
				'</ul>'].join('');
			});

			// Append domains
			var doms = stage.append('g').attr('class', 'domains');
			doms.selectAll('rect.domain')
			.data(p)
				.enter().append('rect')
					.attr({
						'class': 'domain',
						'x': function(d) {
							return scaleX(+d.DomainStart);
						},
						'y': function(d, i) {
							//return domain.chart.rowHeight * i + 0.5 * (domain.chart.rowHeight - domain.chart.domainHeight);
							return scaleY(i, p.length);
						},
						'width': function(d) {
							return scaleX(d.DomainEnd - d.DomainStart);
						},
						'height': domain.chart.domainHeight,
						'rx': 0.5 * domain.chart.domainHeight,
						'ry': 0.5 * domain.chart.domainHeight
					})
					.style({
						'fill': function(d) {
							return computeColors(colorType, d[colors[colorType].key]);
						},
						'stroke': '#333',
						'stroke-opacity': 0.15
					})
					.call(domTip)
					.on('mouseover', domTip.show)
					.on('mouseout', domTip.hide)
					.on('mousemove', function(e) {
						domTip.style({
							'left': (d3.event.pageX - 0.5 * $('#domain-tip').outerWidth()) + 'px'
						});
					});

			// Draw domains
			var legend = svg.append('g').attr('class', 'legends');

			// Expose scale
			$.extend(globalVar.view, {
				'd3': {
					'domains': {
						'scaleX': scaleX,
						'scaleY': scaleY,
						'tip': domTip
					}
				}
			});
		})
		.fail(function() {
			// Disable all the controls
			$('#domain__controls :input').prop('disabled', true);

		});

		// Domain controls
		$('.controls__toggle').click(function(e) {
			e.preventDefault();
			$(this).closest('.facet').toggleClass('controls--visible');
		});

		// Update colors
		$('#dc__fill').on('change', function() {
			var $t = $(this),
				colorType = $t.val(),
				dataKey = $t.find('option:selected').attr('data-key');

			var stage = d3.select('#stage'),
				doms = stage.selectAll('rect.domain');

			doms
			.transition()
			.duration(500)
			.style({
				'fill': function(d) {
					return computeColors(colorType, d[colors[colorType].key]);
				},
				'stroke': function(d) {
					return globalFun.color.darken(computeColors(colorType, d[colors[colorType].key]),20);
				}
			});
		});

		// Update filters
		$('.dc__filter').on('change', function() {
			var checked = this.checked,
				pred = this.value,
				stage = d3.select('#stage'),
				colorType = $('#dc__fill').val(),
				scaleX = globalVar.view.d3.domains.scaleX,
				scaleY = globalVar.view.d3.domains.scaleY,
				domTip = globalVar.view.d3.domains.tip;

			// Collect all checked prediction algorithms
			var c = $('.dc__filter').map(function() {
				if(this.checked) {
					return this.value;
				}
			}).get();

			// Filter data
			var _p = p.filter(function(d) {
				if(c.indexOf(d.Source) >= 0) {
					return d;
				}
			});

			// Transition
			var doms = stage.selectAll('rect.domain').data(_p);
			doms
			.attr({
				'x': function(d) {
					return scaleX(+d.DomainStart);
				},
				'width': function(d) {
					return scaleX(d.DomainEnd - d.DomainStart);
				}
			})
			.style({
				'fill': function(d) {
					return computeColors(colorType, d[colors[colorType].key]);
				},
				'stroke': function(d) {
					return globalFun.color.darken(computeColors(colorType, d[colors[colorType].key]),20);
				}
			});

			doms.enter().append('rect')
			.attr({
				'class': 'domain',
				'x': function(d) {
					return scaleX(+d.DomainStart);
				},
				'width': function(d) {
					return scaleX(d.DomainEnd - d.DomainStart);
				},
				'height': domain.chart.domainHeight,
				'rx': 0.5 * domain.chart.domainHeight,
				'ry': 0.5 * domain.chart.domainHeight
			})
			.style({
				'fill': function(d) {
					return computeColors(colorType, d[colors[colorType].key]);
				},
				'stroke': function(d) {
					return globalFun.color.darken(computeColors(colorType, d[colors[colorType].key]),20);
				}
			});

			// Finalise y position
			stage.selectAll('rect.domain').attr({
				'y': function(d, i) {
					return scaleY(i, _p.length);
				}
			})
			.call(domTip)
			.on('mouseover', domTip.show)
			.on('mouseout', domTip.hide)
			.on('mousemove', function(e) {
				domTip.style({
					'left': (d3.event.pageX - 0.5 * $('#domain-tip').outerWidth()) + 'px'
				});
			});

			doms.exit().remove();

		});

		// Update sorting order
		$('#dc__sort').on('change', function() {
			var $t = $(this),
				sortBy = $t.val(),
				mapping = {
					'start': {
						'key': 'DomainStart',
						'dataType': 'numeric'
					},
					'end': {
						'key': 'DomainEnd',
						'dataType': 'numeric'
					},
					'length': {
						'key': 'DomainLength',
						'dataType': 'numeric'
					},
					'prediction_algorithm': {
						'key': 'Source',
						'dataType': 'string'
					},
					'interpro_id': {
						'key': 'InterProID',
						'dataType': 'string'
					},
					'interpro_namespace': {
						'key': 'InterProType',
						'dataType': 'string'
					}
				},
				stage = d3.select('#stage'),
				doms = stage.selectAll('rect.domain');

			doms.sort(function(a,b) {
				if(mapping[sortBy].dataType === 'numeric') {
					return a[mapping[sortBy].key] - b[mapping[sortBy].key];
				} else {
					var _a = (a[mapping[sortBy].key] === null) ? "" : "" + a[mapping[sortBy].key],
						_b = (b[mapping[sortBy].key] === null) ? "" : "" + b[mapping[sortBy].key];
					return d3.ascending(_a, _b);
				}
			});
			doms.transition().duration(500)
				.attr({
					'y': function(d, i) {
						return domain.chart.rowHeight * i + 0.5 * (domain.chart.rowHeight - domain.chart.domainHeight);
					}
				});

		});
	};

	// Get Expression Atlas data
	globalFun.view.expat = function(dataset, idtype) {
		var _dataset 	= dataset || 'ljgea-geneid',
			_idtype		= idtype || 'geneid';

		// Update gene link
		$('#view__expat__link').attr('href', function() {
			return $(this).data('root') + '/expat?ids=' + $('#view__expression').data('gene') + '&dataset=' + _dataset + '&idtype=' + _idtype;
		});

		// Show loader
		$('#expat__loader').slideDown(500);

		// Hide chart
		$('#view__expat')
		.slideUp(500)
		.empty();

		// Update dataset in loader
		$('#expat__loader__dataset').text(dataset);

		// Perform AJAX call
		var expatAJAX = $.ajax({
				type: 'POST',
				url: root + '/api/v1/expat',
				data: {
					ids: $('#view__expression').attr('data-gene'),
					dataset: _dataset,
					idtype: _idtype
				},
				dataType: 'json'
			}),
			d3varsAJAX = $.ajax({
				type: 'GET',
				url: '/data/d3/vars.json',
				dataType: 'json'
			});

		// Wait for data
		$.when(expatAJAX, d3varsAJAX)
		.done(function(expatResponse, d3varsResponse) {
			// Hide loader
			$('#expat__loader').slideUp(500);

			// Data
			var d = expatResponse[0],
				data = d.data;

			// ExpAt chart dimensions
			var expat = {
				d3: {
					vars: {}
				},
				status: {
					searched: false,
					pushState: true
				},
				chart: {
					margin: {
						top: 5,
						right: 5,
						bottom: 280,
						left: 90
					},
					outerWidth: 720
				},
				heatmap: {
					cellHeight: 15
				},
				linegraph: {
					targetHeight: 150
				}
			};
			expat.chart.innerWidth = expat.chart.outerWidth - expat.chart.margin.left - expat.chart.margin.right;

			// Create SVG element
			$('#view__expat')
			.removeClass('hidden')
			.append('<div class="d3-chart" id="expat-chart"></div>');

			// Globals
			var order = {
				'condition': data.Mean.condition,
				'row': data.Mean.row
			};

			// Cast melted data
			data.casted = [];
			$.each(data.Mean.condition, function(i, c) {
				data.casted.push({'condition': c});
			});
			$.each(data.Mean.melted, function(i,row) {
				var rowIndex = data.Mean.row.indexOf(row.rowID);
				data.casted[(i - rowIndex * data.Mean.condition.length)][row.rowID] = row.value;
			});

			// Pop the last item off clusters
			if(data.Mean.clustering) {
				data.Mean.clustering.data.condition.clusterData.pop();
				data.Mean.clustering.data.row.clusterData.pop();
			}

			// Finalize height
			expat.heatmap.targetHeight = expat.heatmap.cellHeight * data.Mean.row.length;

			// Overwrite left margin for longer probe IDs
			if(data.rowType === 'Probe ID') {
				expat.chart.margin.left = 100;
				expat.chart.innerWidth = expat.chart.outerWidth - expat.chart.margin.left - expat.chart.margin.right;
			}

			// Create graph
			var expatChart = d3.select('#expat-chart')
				.append('svg:svg')
					.attr({
						'viewBox': '0 0 ' + expat.chart.outerWidth + ' ' + (expat.linegraph.targetHeight + expat.heatmap.targetHeight + expat.chart.margin.top + expat.chart.margin.bottom),
						'preserveAspectRatio': 'xMidYMid meet'
					})
					.style('font-family', 'Arial');

			// Linegraph
			expatChart.append('g')
				.attr({
					'id': 'expat-linegraph',
					'transform': 'translate(' + expat.chart.margin.left + ',' + expat.chart.margin.top + ')',
					'width': expat.chart.innerWidth,
					'height': expat.heatmap.targetHeight
				});

			// Heatmap
			expatChart.append('g')
				.attr({
					'id': 'expat-heatmap',
					'transform': 'translate(' + expat.chart.margin.left + ',' + (expat.chart.margin.top + expat.linegraph.targetHeight + 16) + ')',
					'width': expat.chart.innerWidth,
					'height': expat.heatmap.targetHeight
				});


			// Initialize linegraph
			// Define tip
			var linegraphTip = d3.tip()
					.attr('class', 'd3-tip linegraph-tip tip--bottom')
					.offset([-15,0])
					.direction('n')
					.html(function(d) {
						return '<ul><li class="value"><strong>Value</strong>: <span>'+d.value+'</span></li><li class="row-id"><strong>'+data.rowType+'</strong>: <span>'+$(this).closest('g').data('row')+'</span></li><li class="condition"><strong>Condition</strong>: <span>'+d.condition+'</span></li></ul>';
					}),
				expatLinegraph = d3.select('#expat-linegraph').call(linegraphTip);

			// Scales
			var color = d3.scale.category10(),
				linegraphX = d3.scale.ordinal().rangeBands([0, expat.chart.innerWidth]),
				linegraphY = d3.scale.linear().range([expat.linegraph.targetHeight, 0]).nice();

			// Line
			line = d3.svg.line()
				.interpolate('monotone')
				.x(function(d) { return linegraphX(d.condition); })
				.y(function(d) { return linegraphY(d.value); });

			// Transform and coerce values
			data.casted.forEach(function(d) {
				d.condition = d.condition;
			});
			color.domain(d3.keys(data.casted[0]).filter(function(key) {
				return key !== 'condition';
			}));
			rows = color.domain().map(function(name) {
				return {
					name: name,
					values: data.casted.map(function(d) {
						return {
							condition: d.condition,
							value: +d[name]
						};
					})
				};
			});

			// Define domains
			linegraphX.domain(data.casted.map(function(d) { return d.condition; }));
			linegraphY.domain([
				d3.min(rows, function(g) { return d3.min(g.values, function(v) { return v.value; });}),
				d3.max(rows, function(g) { return d3.max(g.values, function(v) { return v.value; });})
			]).nice();

			// Axes
			linegraphXAxis = d3.svg.axis().scale(linegraphX).orient('bottom').innerTickSize(expat.linegraph.targetHeight).outerTickSize(0);
			linegraphYAxis = d3.svg.axis().scale(linegraphY).orient('left').tickFormat(function (d) {
				return d;
			}).innerTickSize(-expat.chart.innerWidth).outerTickSize(0);

			// Append axis
			expatLinegraph.append('svg:g')
			.attr('class', 'y axis')
			.call(linegraphYAxis)
			.selectAll('text')
				.style({
					'text-anchor': 'end',
					'font-size': '8px'
				});

			var linegraphYAxisLabel = expatLinegraph.append('text')
				.attr({
					'dx': 0,
					'dy': 0.5*expat.linegraph.targetHeight - 6
				})
				.style({
					'font-size': '10px',
					'text-anchor': 'end'
				});
			linegraphYAxisLabel.append('tspan').attr({
				'x': -35
			}).text('Expression');
			linegraphYAxisLabel.append('tspan').attr({
				'x': -35,
				'dy': 12
			}).text('level');

			expatLinegraph.append('svg:g')
			.attr('class', 'x axis')
			.call(linegraphXAxis)
			.selectAll('text')
				.text('');

			// Add path
			var cb = 'YlGnBu';
			var row = expatLinegraph.selectAll('.row')
				.data(rows)
				.enter()
				.append('svg:g')
				.attr({
					'class': function(d) { return 'row ' + d.name + ' ' + data.rowType.toLowerCase().replace(' ',''); },
					'data-row': function(d) { return d.name; },
					'id': function(d) { return globalFun.expat.replaceIDClass('expat-linegraph-group-'+d.name); },
					'transform': 'translate(' + (0.5 * expat.chart.innerWidth / data.Mean.condition.length) + ',0)'
				});

			row.append('svg:path')
			.attr({
				'class': 'line',
				'd': function(d) { return line(d.values); },
				'id': function(d) { return globalFun.expat.replaceIDClass('expat-linegraph-path-'+d.name); }
			})
			.style({
				'stroke': '#333',
				'stroke-width': 3,
				'fill': 'none'
			});

			// Add points
			row.selectAll('.point')
			.data(function(d) { return d.values; })
			.enter()
			.append('svg:circle')
				.attr({
					'class': 'point',
					'data-condition': function(d,i) { return data.Mean.condition.indexOf(d.condition); },
					'cx': function(d) { return linegraphX(d.condition); },
					'cy': function(d) { return linegraphY(d.value); },
					'r': 4
				})
				.style({
					'stroke': '#333',
					'stroke-width': 2,
					'fill': '#fff'
				})
				.on('mouseover', linegraphTip.show)
				.on('mouseout', linegraphTip.hide);

			// Manual styles
			expatLinegraph.selectAll('.x.axis path.domain').style('stroke', 'none');
			expatLinegraph.selectAll('.y.axis path.domain').style('stroke', '#333');
			expatLinegraph.selectAll('.y.axis .tick line').style('stroke', '#ccc');



			// Initialize heatmap
			// Define tip
			var heatmapTip = d3.tip()
					.attr('class', 'd3-tip heatmap-tip tip--top')
					.offset([15,0])
					.direction('s')
					.html(function(d) {
					return '<ul><li class="value"><strong>Value</strong>: <span>'+d.value+'</span></li><li class="gene-id"><strong>'+data.rowType+'</strong>: <span>'+d.rowID+'</span></li><li class="condition"><strong>Condition</strong>: <span>'+d.condition+'</span></li></ul>';
				}),
				expatHeatmap = d3.select('#expat-heatmap').call(heatmapTip);

			// Coerce data
				data.Mean.melted.forEach(function(d) {
					d.rowID = d.rowID;
					d.condition = d.condition;
					d.value = +d.value;
				});

				// Scales
				var heatmapX = d3.scale.ordinal().domain(data.Mean.condition).rangeBands([0, expat.chart.innerWidth]),
					heatmapY = d3.scale.ordinal().domain(data.Mean.row).rangeBands([0, expat.heatmap.targetHeight]),
					heatmapZ = d3.scale.linear().domain(d3.extent(data.Mean.melted, function(d) { return d.value; })).range([0, 1]).nice();

				// Heatmap fills
				var fills = colorbrewer.YlGnBu[8],
					heatmapFill = d3.scale.linear().domain(d3.range(0, 1, 1.0 / (fills.length - 1))).range(fills);

				// Get data
				expatHeatmap.selectAll('.tile')
					.data(data.Mean.melted)
					.enter()
					.append('rect')
					.attr({
						'class': 'tile',
						'x': function(d) { return heatmapX(d.condition); },
						'y': function(d) { return heatmapY(d.rowID); },
						'fill': function(d) { return heatmapFill(heatmapZ(d.value)); },
						'data-row': function(d) { return d.rowID; },
						'data-condition': function(d) { return data.Mean.condition.indexOf(d.condition); },
						'id': function(d) { return 'expat-heatmap-rect-'+data.Mean.condition.indexOf(d.condition)+'-'+d.rowID; },
						'width': heatmapX.rangeBand(),
						'height': heatmapY.rangeBand()
					})
					.on('mouseover', heatmapTip.show)
					.on('mouseout', heatmapTip.hide);

				// Add a legend for the color values.
				var ticks = heatmapZ.ticks();

				// Heatmap color gradient legend
				var gradient = expatHeatmap.append('defs')
					.append('linearGradient')
					.attr({
						'id': 'heatmap-gradient',
						'x1': '0%',
						'x2': '100%',
						'y1': '0%',
						'y2': '0%'
					});
				gradient.selectAll('stop').data(ticks).enter().append('stop')
					.attr({
						'offset': function(d,i) {
							return (i / ticks.length) * 100 + '%';
						},
						'stop-color': function(d) {
							return heatmapFill(heatmapZ(d));
						}
					});

				var heatmapLegend = expatHeatmap.append('g').attr({
					'id': 'heatmap-legend',
					'transform': 'translate(0,' + (expat.heatmap.targetHeight + expat.chart.margin.bottom - 95) + ')'
				});
				heatmapLegend.append('rect')
					.attr({
						'width': expat.chart.innerWidth - 25,
						'height': 20
					}).style({
						'fill': 'url(#heatmap-gradient)',
						'stroke': '#333',
						'stroke-width': 1
					});

				heatmapLegend.selectAll('.tick-mark').data(ticks).enter().append('line')
				.attr({
					'x1': function(d, i) {
						return i * ((expat.chart.innerWidth - 25) / (ticks.length-1));
					},
					'x2': function(d, i) {
						return i * ((expat.chart.innerWidth - 25) / (ticks.length-1));
					},
					'y1': 0,
					'y2': 20,
					'class': 'tick-mark'
				})
				.style({
					'stroke': '#333',
					'stroke-width': 1,
					'stroke-dasharray': function(d, i) {
						return globalFun.expat.heatmap.filterLegendData({
							data: d,
							index: i,
							value_if_true: '4,12,4',
							value_if_false: '3,14,3',
							force_outer: true,
							length: ticks.length,
							scale: 'linear'
						});
					},
					'stroke-opacity': function(d, i) {
						return globalFun.expat.heatmap.filterLegendData({
							data: d,
							index: i,
							value_if_true: 1,
							value_if_false: 0.25,
							force_outer: false,
							length: ticks.length,
							scale: 'linear'
						});
					}
				});

				heatmapLegend.selectAll('.tick-label').data(ticks).enter().append('text')
				.attr({
					'class': 'tick-label',
					'x': 0,
					'y': 30,
					'transform': function(d, i) { return 'translate(' + (i * ((expat.chart.innerWidth - 25) / (ticks.length-1))) + ',0)'; }
				})
				.style({
					'font-size': '7.5px',
					'text-anchor': 'middle',
					'fill-opacity': function(d, i) {
						return globalFun.expat.heatmap.filterLegendData({
							data: d,
							index: i,
							value_if_true: 1,
							value_if_false: 0.5,
							force_outer: true,
							length: ticks.length,
							scale: 'linear'
						});
					},
					'font-weight': function(d, i) {
						return globalFun.expat.heatmap.filterLegendData({
							data: d,
							index: i,
							value_if_true: 600,
							value_if_false: 400,
							force_outer: true,
							length: ticks.length,
							scale: 'linear'
						});
					}
				})
				.text(function(d) { return Number(Math.round(d+'e2')+'e-2'); });

				expatHeatmap.append("text")
				.attr({
					'class': 'label',
					'x': 0,
					'y': expat.heatmap.targetHeight + expat.chart.margin.bottom - 100
				})
				.style('font-size', '10px')
				.text('Relative expression');

				// Gene ID tip
				var geneIDtip = d3.tip()
					.attr('class', 'd3-tip heatmap-gene-id-tip heatmap-tip tip--left')
					.attr('id', 'heatmap-gene-id-tip')
					.offset([0,15])
					.direction('e')
					.html(function(d) {
						return '<p>Retrieving annotation...</p>';
					});

				// Append axes
				var heatmapXAxis = d3.svg.axis()
						.scale(heatmapX)
						.orient('bottom')
						.tickSize(3,0),
					heatmapYAxis = d3.svg.axis()
						.scale(heatmapY)
						.orient('left')
						.tickSize(0);

				expatHeatmap.append('g')
					.attr({
						'class': 'x axis',
						'transform': 'translate(0,'+expat.heatmap.targetHeight+')'
					})
					.call(heatmapXAxis)
					.selectAll('text')
						.style({
							'font-size': '7.5px',
							'text-anchor': 'start'
						})
						.attr({
							'dx': '8',
							'dy': '-2',
							'transform': 'rotate(90)'
						});

				expatHeatmap.append('g')
					.attr('class', 'y axis')
					.call(heatmapYAxis)
					.selectAll('text')
						.style({
							'font-size': '8.5px',
							'text-anchor': 'end'
						})
						.attr({
							'data-row': function(d) { return d; }
						});

				// Call tip for gene ID search cases only
				var expatGeneAnnoAJAX,
					expatGeneAnnoAJAXTimer = null;
				if(data.rowType === 'Gene ID') {
					expatHeatmap.selectAll('g.y.axis text')
					.call(geneIDtip)
					.on('mouseover', function(d) {
						// Abort old AJAX call
						if(expatGeneAnnoAJAX && expatGeneAnnoAJAX.readyState != 4){
							expatGeneAnnoAJAX.abort();
						}

						// Reset tooltip and show it
						$('#heatmap-gene-id-tip').html('Retrieving information from database&hellip;');
						geneIDtip.show();

						// Perform AJAX call
						expatGeneAnnoAJAX = $.ajax({
							method: 'get',
							url: root + '/api/v1/gene/annotation/MG20/3.0/'+d,
							dataType: 'json'
						});

						// Evaluate promise
						expatGeneAnnoAJAX
						.done(function(d) {
							if(!data.error) {
								if(d.data[0].annotation) {
									$('#heatmap-gene-id-tip').html(d.data[0].annotation);
								} else {
									$('#heatmap-gene-id-tip').html('Annotation unavailable.');
								}
							} else {
								$('#heatmap-gene-id-tip').html('Annotation unavailable.');
							}
						})
						.fail(function() {
							$('#heatmap-gene-id-tip').html('Error in retrieving annotation.');
						});
					})
					.on('mouseout', geneIDtip.hide);
				}
						
				// Manual styles
				expatHeatmap.selectAll('path.domain').attr('stroke', '#333');
				expatHeatmap.selectAll('.x.axis .tick line').attr('stroke', '#333');

				// Append borders
				expatHeatmap.append('svg:line')
					.attr({
						'class': 'border-top',
						'x1': 0,
						'x2': expat.chart.innerWidth,
						'y1': 0,
						'y2': 0
					})
					.style('stroke', '#333');
				expatHeatmap.append('svg:line')
					.attr({
						'class': 'border-right',
						'x1': expat.chart.innerWidth,
						'x2': expat.chart.innerWidth,
						'y1': expat.heatmap.targetHeight,
						'y2': 0
					})
					.style('stroke', '#333');


			// Link heatmap and linegraph
			var axis = {
				show: function(condition) {
					if(expatLinegraph) $('#expat-linegraph g.x.axis line').eq(condition)[0].classList.add('hover');
				},
				hide: function(condition) {
					if(expatLinegraph) $('#expat-linegraph g.x.axis line').eq(condition)[0].classList.remove('hover');
				}
			};
			$d
			.on('mouseover.expatSuccess', '#expat-heatmap rect.tile', function() {
				// Condition
				var condition = $(this).data('condition');

				// Highlight line
				$('#expat-linegraph').addClass('active');
				var $g = $('#expat-linegraph-group-'+globalFun.expat.replaceIDClass($(this).data('row')));
				if(expatLinegraph) {
					$g[0].classList.add('active');
					$g.find('circle.point[data-condition="'+condition+'"]')[0].classList.add('active');
				}

				// Add hover class
				$(this)[0].classList.add('hover');

				axis.show(condition);
			})
			.on('mouseout.expatSuccess', '#expat-heatmap rect.tile', function() {
				// Condition
				var condition = $(this).data('condition');

				// Un-highlight line
				$('#expat-linegraph').removeClass('active');
				var $g = $('#expat-linegraph-group-'+globalFun.expat.replaceIDClass($(this).data('row')));
				if(expatLinegraph) {
					$g[0].classList.remove('active');
					$g.find('circle.point[data-condition="'+condition+'"]')[0].classList.remove('active');
				}

				// Remove hover class
				$(this)[0].classList.remove('hover');

				axis.hide(condition);
			})
			.on('mouseover.expatSuccess', '#expat-linegraph circle', function() {
				// Highlight rectangle in heatmap
				var row = $(this).closest('g').data('row'),
					condition = $(this).data('condition'),
					$rect = $('#expat-heatmap-rect-'+condition+'-'+row);
				
				$rect[0].classList.add('hover');

				axis.show(condition);
			})
			.on('mouseout.expatSuccess', '#expat-linegraph circle', function() {
				// Un-highlight rectangle in heatmap
				var row = $(this).closest('g').data('row'),
					condition = $(this).data('condition'),
					$rect = $('#expat-heatmap-rect-'+condition+'-'+row);
				
				$rect[0].classList.remove('hover');

				axis.hide(condition);
			})
			.on('mouseover.expatSuccess', '#expat-heatmap .y.axis text, .expat-dendrogram g.node', function() {
				var row = $(this).data('row');
				$('#expat-linegraph').addClass('active');
				if($('#expat-linegraph-group-'+row).length) $('#expat-linegraph-group-'+row)[0].classList.add('active');
			})
			.on('mouseout.expatSuccess', '#expat-heatmap .y.axis text, .expat-dendrogram g.node', function() {
				var row = $(this).data('row');
				$('#expat-linegraph').removeClass('active');
				if($('#expat-linegraph-group-'+row).length) $('#expat-linegraph-group-'+row)[0].classList.remove('active');
			});

			// Show expression data
			$('#view__expat').slideDown(500);

		})
		.fail(function() {

		});
	};

	// Get CORGI data
	globalFun.view.corgi = function(dataset, idtype) {
		var _dataset 	= dataset || 'ljgea-geneid',
			_idtype		= idtype || 'geneid';

		// Show loader
		$('#coexpression__loader').slideDown(500);

		// Update dataset in loader
		$('#coexpression__loader__dataset').text(dataset);

		// Hide table
		$('#coexpression__table')
		.slideUp(500)
		.find('tbody')
			.empty();

		$.ajax({
			url: root + '/api/v1/corgi/' + $('#view__expression').attr('data-gene'),
			data: {
				dataset: _dataset,
				idtype: _idtype,
				n: 25,
				b: true
			},
			dataType: 'json',
			type: 'POST'
		})
		.done(function(d) {
			// Hide loader
			$('#coexpression__loader').slideUp(500);

			// Update table
			var $ct = $('#coexpression__table');
			$ct.slideDown(500);
			$.each(d.data, function(i, g) {
				var gene = g.id.replace(/\.\d+?/gi, '');

				globalVar.tables.corgi.row.add([
					[
						'<div class="dropdown button">',
								'<span class="dropdown--title">'+g.id+'</span>',
								'<ul class="dropdown--list">',
									'<li><a href="'+root+'/view/gene/'+gene+'" title="View gene"><span class="icon-eye">View gene</span></a></li>',
									'<li><a href="'+root+'/tools/trex?ids='+gene+'&amp;v=MG20_3.0"><span class="icon-search">Send gene to Transcript Explorer (TREX)</span></a></li>',
									'<li><a href="'+root+'/lore1/search-exec?gene='+gene+'&amp;v=MG20_3.0" title="Search for LORE1 insertions in this gene"><span class="pictogram icon-leaf"><em>LORE1</em> v3.0</span></a></li>',
								'</ul>',
							'</div>'
					].join(''),
					'<span title="Pearson\'s correlation value: '+g.score+'">'+g.score.toFixed(3)+'</span>',
					(g.Annotation ? g.Annotation.replace(/\[([\w\s]+)\]?/i, '[<em>$1</em>]') : 'n.a.')
				]);
			});

			// Show table
			$ct.removeClass('hidden');

			// Update jQuery datatable
			globalVar.tables.corgi.draw();

		})
		.fail(function(a, b, c) {
			// Display failure message
			$('#coexpression__table .user-message')
			.addClass('warning')
			.html('<span class="icon-attention"></span>We have encountered an issue when attempting to retrieve co-expressed genes. '+(a.responseJSON.message ? a.responseJSON.message : ''))
			.slideDown(500);
		});
	};

	// Initialize
	globalFun.view.init();
});