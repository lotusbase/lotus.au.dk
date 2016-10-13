$(function() {

	var $transcriptTable = $('#view__transcript table').DataTable({
		'pagingType': 'full_numbers'
	});
	$transcriptTable.on('search.dt', function() {
		var info = $transcriptTable.page.info(),
			totalRows = info.recordsTotal,
			filteredRows = info.recordsDisplay,
			$badge = $('#view__transcript h3 span.badge');

		// Update counts
		$badge.text(filteredRows);

		if(filteredRows < totalRows) {
			$badge.addClass('subset');
		} else {
			$badge.removeClass('subset');
		}
	});

	// GO ancestor tree
	var width = 960,
		marginLeft = 360,
		height = 620;

	var force = d3.layout.force()
		.charge(-2000)
		.linkDistance(20)
		.theta(0.5)
		.gravity(0.5)
		.size([(width - marginLeft), height]);

	var drag = force.drag()
		.on("dragstart", function(d) {
			console.log(d.is);
			d3.select(this).classed("fixed", d.fixed = true);
		});

	var svg = d3.select("#go-ancestor")
		.attr({
			'viewBox': '0 0 '+width+' '+height,
			'preserveAspectRatio': 'xMidYMid'
		}),
		stage = svg.append('g').attr({
			'id': 'stage',
			'transform': 'translate('+marginLeft+',0)'
		});

	// Link color
	// ['is_a', 'regulates', 'part_of', 'negatively_regulates', 'positively_regulates']
	var colors = {
		relationship: d3.scale.ordinal().range([
				'#777',
				'#EFC94C',
				'#467894',
				'#8E2800',
				'#386E52'
			]).domain(d3.range(0, 5)),
		node: d3.scale.ordinal().range([
				'#332532',
				'#644D52',
				'#F77A52',
				'#A49A87'
			]).domain([
				'root',
				'target',
				'child',
				'node'
			])
		};

	// Chart functions
	var go = {
		update: function(go_term) {

			// Update current term
			go_current = go_term;

			var go_parent = go.get.ancestors(go_term),
				go_child = go.get.children(go_term);

			// Combine and convert all terms to unique
			var go_terms = globalFun.arrayUnique(go_parent.concat(go_child));
			
			// Create D3 structure
			var d3_data = go.d3_structure(go_term, go_terms);
			
			// Draw graph
			go.draw(d3_data);
		},
		get: {
			// Recursively retrieve all ancestors
			ancestors: function(go_term) {
				var go_terms = [];
				go_terms.push(go_term);

				// Check for parents
				if(go_data[go_term].p.length > 0) {
					for(var i = 0, count = go_data[go_term].p.length; i < count ; i++) {
						var parent_go_term = go_data[go_term].p[i][0];
						go_terms = go_terms.concat(go.get.ancestors(parent_go_term));
					}
				}

				return go_terms;
			},

			// Retrieve immediate children
			children: function(go_term) {
				var go_terms = [];

				// Check for children
				if(go_data[go_term].c.length > 0) {
					for (var i = 0, count = go_data[go_term].c.length; i < count; i++) {
						var child_go_term = go_data[go_term].c[i];
						go_terms.push(child_go_term);
					}
				}

				return go_terms;
			}
		},

		// Construct D3 structure
		d3_structure: function(target, go_terms) {
			var nodes = [],			// Array of node objects
				links = [],			// Array of link objects
				nodeIndex = {},
				go_term;

			// Loop through all go terms fed into the function
			for (var i = 0; i < go_terms.length; i++) {

				// Update go_term reference
				go_term = go_terms[i];

				// Create node object
				var node = {
					'id': go_term,
					'r': 7.5,
					'is': 'node',
					'p': [],
					'c': []
				};

				// Push node to index
				nodeIndex[go_term] = i;

				// Push node to nodes collection
				nodes.push(node);
			}

			// Loop through all terms again, but this time determine relationships
			for (var j = 0; j < go_terms.length; j++) {

				// Update go_term reference
				go_term = go_terms[j];

				var go_parents = go_data[go_term].p;

				if (go_parents.length > 0 ) {
					for (var k = 0; k < go_parents.length; k++) {
						var parent_go_term = go_parents[k][0],
							go_parent_index = nodeIndex[parent_go_term],
							rel_type = go_parents[k][1];

						if (parent_go_term === target) {

							// Set attributes for the GO term of interest
							nodes[j].is = 'child';
							nodes[j].r = 7.5;
							nodes[j].y = height - 50;

						}

						if(typeof go_parent_index !== typeof undefined) {
							// Create link object
							var link = {
								'source': go_parent_index,
								'target': j,
								'rel': rel_type,
								'value': 5
							};

							nodes[j].p.push([parent_go_term, rel_type]);

							// Push link to links collection
							links.push(link);
						}

					}
				} else {		
					// We have the root		
					nodes[j].fixed = true;
					nodes[j].x = (width - marginLeft) / 2;
					nodes[j].y = 50;
					nodes[j].is = 'root';
					nodes[j].r = 10;
				}
			}

			if(nodes[nodeIndex[target]].is != 'root') {
				nodes[nodeIndex[target]].fixed = true;
				nodes[nodeIndex[target]].x = (width - marginLeft) / 2;
				nodes[nodeIndex[target]].y = height - 100;
				nodes[nodeIndex[target]].is = 'target';
			} else {
				nodes[nodeIndex[target]].fixed = true;
				nodes[nodeIndex[target]].x = (width - marginLeft) / 2;
				nodes[nodeIndex[target]].y = height / 2;
			}
			nodes[nodeIndex[target]].r = 10;

			return {'nodes' : nodes, 'links' : links, 'nodeIndex': nodeIndex};
		},
		draw: function(s) {

			var node_go = $.map(s.nodeIndex, function(k,v) {
				return v;
			});

			// Retrieve metadata for each GO node features in the chart
			d3.json('/api/v1/view/go/'+node_go.join(','), function(error, data) {

				// Error logging for API call
				if(error) {
					console.error('No metadata retrieved for GO terms found in ancestor tree: '+error.status+' '+error.statusText);
				} else {
					goAPIdata = data.data;
				}

				// GO namespaces
				var namespace = {
					'p': 'Biological process',
					'f': 'Molecular function',
					'c': 'Cellular component'
				};

				// Start drawing proper
				var link = stage.selectAll(".link"),
					node = stage.selectAll(".node");

				var tick = function(e) {
					var kx = 0.04 * e.alpha,
							ky = 1.4 * e.alpha;

					s.links.forEach(function(d, i) {
						d.target.x += (d.source.x - d.target.x) * kx;
						d.target.y += (d.source.y + 50 - d.target.y) * ky;
					});

					link.attr('d', function(d) {
						var dx = d.target.x - d.source.x,
						dy = d.target.y - d.source.y,
						dr = Math.sqrt(dx * dx + dy * dy);
						return 'M' + 
						d.source.x + ',' + 
						d.source.y + 'L' + 
						d.target.x + ',' + 
						d.target.y;
					});

					node.attr('transform', function (d) {
						return 'translate(' + d.x + ',' + d.y + ')';
					});
				};

				var goTip = d3.tip()
					.attr({
						'class': 'd3-tip tip--bottom',
						'id': 'go-tip'
					})
					.offset([-15,0])
					.direction('n')
					.html(function(d) {
						return ['<ul>',
							'<li><strong>GO term</strong>: '+d.id+'</li>',
							(!error && goAPIdata[d.id] && goAPIdata[d.id].Namespace ? '<li><strong>Namespace</strong>: '+namespace[goAPIdata[d.id].Namespace]+'</li>' : ''),
							(!error && goAPIdata[d.id] && goAPIdata[d.id].Name ? '<li><strong>Description</strong>: '+goAPIdata[d.id].Name+'</li>' : ''),
							(!error && goAPIdata[d.id] && goAPIdata[d.id].Definition ? '<li><strong>Definition</strong>: '+goAPIdata[d.id].Definition+'</li>' : ''),
						'</ul>'].join('');
					});

				force
					.nodes(s.nodes)
					.links(s.links)
					.start()
					.on('tick', tick);

				// Update links
				stage.selectAll('path.link').remove();

				// Update nodes
				stage.selectAll('g.node').remove();

				// Build arrows for chart
				stage.append('svg:defs').selectAll('marker')
					.data(d3.range(0, 5))					// Different link/path types can be defined here
					.enter().append('svg:marker')			// This section adds in the arrows
						.attr({
							'id': function(d) { return 'start-'+d; },
							'viewBox': '0 -5 10 10',
							'refX': -10,
							'refY': 0,
							'markerWidth': 6,
							'markerHeight': 6,
							'orient': 'auto'
						})
						.append('svg:path')
							.attr('d', 'M0,0L10,5L10,-5')
							.style('fill', function(d) {
								return colors.relationship(+d);
							});

				// Generate link
				link = link.data(force.links())
					.enter().append('path')
						.attr({
							'class': 'link',
							'marker-start': function(d) {
								return 'url(#start-'+d.rel+')';
							}
						})
						.style({
							'stroke': function(d) {
								return colors.relationship(+d.rel);
							},
							'stroke-opacity': 0.75,
							'stroke-width': 1.5
						});

				// Generate node
				node = node.data(s.nodes)
					.enter().append('g')
						.attr({
							'class': 'node',
							'id': function(d) {
								if(d.is === 'target' || d.is === 'root') {
									return 'node-'+d.is;
								}
							}
						})
						.on('dblclick', function(d) {
							d3.select(this).classed("fixed", d.fixed = false);
						})
						.call(drag)
						.call(goTip)
						.on('mouseover', goTip.show)
						.on('mouseout', goTip.hide);

				nodeIcon = node
					.append('circle')
					.attr({
						'cx': 0,
						'cy': 0,
						'r': function(d) {
							return d.r;
						}
					})
					.style({
						'fill': function(d,i) {
							return colors.node(d.is);
						},
						'cursor': 'move',
						'stroke': '#fff',
						'stroke-width': 1.5
					});

				nodeText = node.append('text')
					.attr({
						'class': 'node',
						'dy': '0.35em',
						'transform': function(d) {
							return 'translate(0,'+d.r*2+')';
						}
					})
					.style({
						'cursor': 'pointer',
						'font-size': '10px',
						'font-weight': 'bold',
						'text-anchor': 'middle',
						'stroke': '#fff',
						'stroke-width': 0.25
					})
					.text(function(d) { return d.id; })
					.each(function(d) {
						d.bbox = this.getBBox();
					});

				nodeTextBG = node.insert('rect', 'text')
					.attr({
						'width': function(d) { return d.bbox.width + 8; },
						'height': function(d) { return d.bbox.height + 4; },
						'transform': function(d) {
							return 'translate('+(d.bbox.width + 8) * -0.5+','+((d.bbox.height + 4) * -0.5 + d.r * 2)+')';
						},
						'rx': 4,
						'ry': 4
					})
					.style({
						'fill': '#fff',
						'fill-opacity': 0.5
					});

				// Generate legends
				var legend = svg.append('g').attr('id', 'legend');
				var linkLegend = legend.append('g').attr({
						'id': 'legend__link',
						'transform': 'translate(0,0)'
					});
				linkLegend.append('text').attr('class', 'legend__title').text('RELATIONSHIPS').style({
					'font-size': '14px',
					'font-weight': 'bold',
					'dy': '0.35em'
				});
				var linkLegendItem = linkLegend.selectAll('g.legend__item').data(colors.relationship.domain())
					.enter().append('g')
						.attr({
							'class': 'legend__item',
							'transform': function(d,i) {
								return 'translate(0,'+(i*24 + 18)+')';
							}
						});
				linkLegendItem.append('path')
					.attr({
						'd': 'M0,0 L15,0',
					})
					.style({
						'stroke': function(d) {
							return colors.relationship(+d);
						},
						'stroke-opacity': 0.75,
						'stroke-width': 1.5
					});
				linkLegendItem.append('polygon')
					.attr({
						'points': '0,-5 10,0 0,5',
						'transform': 'translate(10,0)'
					})
					.style({
						'fill': function(d) {
							return colors.relationship(+d);
						}
					});
				var linkLegendDesc = ['Is a', 'Regulates', 'Part of', 'Negatively regulates', 'Positively regulates'];
				linkLegendItem.append('text')
					.attr({
						'x': 25,
						'y': 0,
						'dy': '0.35em',
					})
					.style({
						'font-size': '12px'
					})
					.text(function(d) {
						return linkLegendDesc[d];
					});

				var nodeLegend = legend.append('g').attr({
						'id': 'legend__node',
						'transform': 'translate(0,'+(colors.relationship.domain().length*24 + 50)+')'
					});
				nodeLegend.append('text').attr('class', 'legend__title').text('NODES').style({
					'font-size': '14px',
					'font-weight': 'bold',
					'dy': '0.35em'
				});
				var nodeLegendItem = nodeLegend.selectAll('g.legend__item').data(colors.node.domain())
					.enter().append('g')
						.attr({
							'class': 'legend__item',
							'transform': function(d,i) {
								return 'translate(0,'+(i*24 + 18)+')';
							}
						});
				nodeLegendItem.append('circle')
					.attr({
						'cx': 12,
						'cy': 0,
						'r': 7.5
					})
					.style({
						'fill': function(d) {
							return colors.node(d);
						},
						'stroke': '#fff',
						'stroke-width': 1.5
					});
				nodeLegendItem.append('text')
					.attr({
						'x': 25,
						'y': 0,
						'dy': '0.35em',
					})
					.style({
						'font-size': '12px'
					})
					.text(function(d,i) {
						var label = colors.node.domain()[i];
						return label[0].toUpperCase() + label.slice(1);
					});

				// Center legend box
				var lc = legend.node().getBBox();
				legend.attr('transform', 'translate(21,'+(height - lc.height)*0.5+')');

				// Insert background
				legend.insert('rect', 'g')
					.attr({
						'width': lc.width + 40,
						'height': lc.height + 40,
						'x': -20,
						'y': -30
					})
					.style({
						'fill': '#fff',
						'fill-opacity': 0.5,
						'stroke': '#333',
						'stroke-opacity': 0.5
					});

				// Style legend texts
				legend.selectAll('text').style({
					'fill': '#333'
				});
			});
		}
	};

	var go_data = null,
		go_current = null;
	d3.json('/data/go/go.json', function(error, data) {
		if(error) {
			$('#go-ancestor').hide().after('<div class="user-message warning"><span class="icon-attention"></span>Unable to load GO relational data. We have encountered a <strong>'+error.status+' '+error.statusText+'</strong> error.</div>');
		} else {
			go_data = data;
			go.update($('#go-ancestor').attr('data-go'));
		}
	});
	
});