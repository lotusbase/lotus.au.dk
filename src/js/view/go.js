$(function() {

	var $transcriptTable = $('#view__transcript table').DataTable({
		"pagingType": "full_numbers"
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

	// Ancestor tree
	// Initialize chart
	var width = 960,
		height = 600;

	// Force layout
	var force = d3.layout.force()
		.charge(-2000)
		.linkDistance(20)
		.theta(0.5)
		.gravity(0.5)
		.size([width, height]);
		 
	// Create chart
	var svg = d3.select('#go-ancestor');

	// Tip
	var goTip = d3.tip()
			.attr({
				'class': 'd3-tip tip--bottom',
				'id': 'go-tip'
			})
			.offset([-15,0])
			.direction('n')
			.html(function(d) {
				var parents_markup = '';

				if(d.p.length) {
					$.each(d.p, function(i, v) {
						parents_markup += '<li>'+v[0]+'</li>';
					});
				}

				return ['<ul>',
					'<li><strong>GO identifier</strong>: '+d.id+'</li>',
					(d.p.length ? '<li><strong>'+globalFun.pl(d.p.length, 'Parent')+'</strong>: <ul class="list--floated">'+parents_markup+'</ul></li>' : ''),
				'</ul>'].join('');
			});

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
			var d3_data = go.d3_structure(go_term, go_terms, go_data);
			
			// Draw graph
			go.draw(d3_data);
		},
		get: {
			// Recursively retrieve all ancestors
			ancestors: function(go_term) {
				var go_terms = [];
				go_terms.push(go_term);

				// Check for parents
				if(go_data[go_term]['p'].length > 0) {
					for(var i = 0, count = go_data[go_term]['p'].length; i < count ; i++) {
						var parent_go_term = go_data[go_term]['p'][i][0];
						go_terms = go_terms.concat(go.get.ancestors(parent_go_term));
					}
				}

				return go_terms;
			},

			// Retrieve immediate children
			children: function(go_term) {
				var go_terms = [];

				// Check for children
				if(go_data[go_term]['c'].length > 0) {
					for (var i = 0, count = go_data[go_term]['c'].length; i < count; i++) {
						var child_go_term = go_data[go_term]['c'][i];
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
				nodeIndex = {};

			// Loop through all go terms fed into the function
			for(var i = 0, count = go_terms.length; i < count ; i++) {
				var go_term = go_terms[i];

				// Create node object
				var node = {
					'id': go_term,
					'r': 10,
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
			for(var i = 0, count = go_terms.length; i < count ; i++) {
				var go_term = go_terms[i],
					go_parents = go_data[go_term]['p'];

				if (go_parents.length > 0 ) {
					for (var j = 0; j < go_parents.length; j++) {
						var parent_go_term = go_parents[j][0],
							go_parent_index = nodeIndex[parent_go_term],
							rel_type = go_parents[j][1];

						if (parent_go_term === target) {

							// Set attributes for the GO term of interest
							nodes[i]['is'] = 'child';
							nodes[i]['r'] = 10;
							nodes[i].y = height - 50;

						}

						if(typeof go_parent_index !== typeof undefined) {
							// Create link object
							var link = {
								'source': go_parent_index,
								'target': i,
								'rel': rel_type,
								'value': 5
							};

							nodes[i].p.push([parent_go_term, rel_type]);

							// Push link to links collection
							links.push(link);
						}

					}
				} else {		

					// We have the root		
					nodes[i].fixed = true;
					nodes[i].x = width / 2;
					nodes[i].y = 50;
					nodes[i]['is'] = 'root';
				}
			}

			if(nodes[nodeIndex[target]]['is'] != 'root') {
				nodes[nodeIndex[target]].fixed = true;
				nodes[nodeIndex[target]].x = width / 2;
				nodes[nodeIndex[target]].y = height - 200;
				nodes[nodeIndex[target]]['is'] = 'target';
			} else {
				nodes[nodeIndex[target]].fixed = true;
				nodes[nodeIndex[target]].x = width / 2;
				nodes[nodeIndex[target]].y = height / 2;
			}

			return {'nodes' : nodes, 'links' : links};
		},
		draw: function(s) {

			force
				.nodes(s.nodes)
				.links(s.links);

			// Events, modified from http://bl.ocks.org/norrs/2883411
			var node_drag = force.drag()
					.on('dragstart', dragstart),

				dragstart = function(d) {
					d3.select(this).classed("fixed", d.fixed = true);
				},

				tick = function(e) {
					// Modified from http://mbostock.github.io/d3/talk/20110921/parent-foci.html
					var kx = 0.04 * e.alpha,
						ky = 1.4 * e.alpha;

					s.links.forEach(function(d, i) {
						d.target.x += (d.source.x - d.target.x) * kx;
						d.target.y += (d.source.y + 50 - d.target.y) * ky;
					});

					path.attr('d', function(d) {
						var dx = d.target.x - d.source.x,
						dy = d.target.y - d.source.y,
						dr = Math.sqrt(dx * dx + dy * dy);
						return 'M' + 
						d.source.x + ',' + 
						d.source.y + 'L' + 
						d.target.x + ',' + 
						d.target.y;
					});
			 
					nodeIcon.attr({
						'cx': function(d) { return d.x; },
						'cy': function(d) { return d.y; }
					});

					nodeText.attr({
						'x': function(d) { return d.x; },
						'y': function(d) { return d.y + d.r + 8; },
						'dy': '0.35em'
					})
					.style({
						'font-weight': 'bold',
						'text-anchor': 'middle',
						'stroke': '#fff',
						'stroke-width': 0.25
					});

					nodeTextBG.attr({
						'x': function(d) { return d.x - 0.5*(d.bbox.width + 8); },
						'y': function(d) { return d.y + d.r + 8 - 0.5*(d.bbox.height + 4); }
					});
				},
				makeTextBox = function(d) {
					d.each(function(d) {
						d.bbox = this.getBBox();
					});
				};


			// Link color
			// ['is_a', 'regulates', 'part_of', 'negatively_regulates', 'positively_regulates']
			var strokeColor = d3.scale.ordinal().range([
						'#777',
						'#EFC94C',
						'#467894',
						'#8E2800',
						'#386E52'
					]).domain([0, 1, 2, 3, 4]),
				fillColor = d3.scale.ordinal().range([
						'#332532',
						'#644D52',
						'#F77A52',
						'#A49A87'
					]).domain([
						'root',
						'target',
						'child',
						'node'
					]);

			// Update links
			svg.selectAll('path.link').remove();

			// Update nodes
			svg.selectAll('g.node').remove();

			// Build arrow
			// build the arrow.
			svg.append('svg:defs').selectAll('marker')
				.data(d3.range(0, 9))					// Different link/path types can be defined here
				.enter().append('svg:marker')		// This section adds in the arrows
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
							return strokeColor(d);
						});

			var path = svg.append('g').selectAll('path')
				.data(force.links())
				.enter().append('path')
					.attr({
						'class': 'link',
						'marker-start': function(d) {
							return 'url(#start-'+d.rel+')';
						}
					})
					.style({
						'stroke': function(d) {
							return strokeColor(+d.rel);
						},
						'stroke-opacity': 0.75,
						'stroke-width': 1.5
					});
		 
			var node = svg.selectAll('g.node')
				.data(s.nodes)
				.enter().append('g').attr('class', 'node')
				.on('click', function(d,i) {
					force.stop();
					if(d.id !== go_current) {
						go.update(d.id);
					}
				})
				.call(node_drag)
				.call(goTip)
				.on('mouseover', goTip.show)
				.on('mouseout', goTip.hide);

			var nodeIcon = node.append('circle')
				.attr({
					'r': function(d) {
						return d.r;
					}
				})
				.style({
					'fill': function(d,i) {
						return fillColor(d.is);
					},
					'cursor': 'pointer',
					'stroke': '#fff',
					'stroke-width': 1.5
				});

			var nodeText = node.append('text')
				.attr('class', 'node')
				.style({
					'cursor': 'pointer',
					'font-size': '10px'
				})
				.text(function(d) { return d.id;})
				.call(makeTextBox);

			var nodeTextBG = node.insert('rect', 'text')
			.attr({
				'width': function(d) { return d.bbox.width + 8; },
				'height': function(d) { return d.bbox.height + 4; },
				'rx': 4,
				'ry': 4
			})
			.style({
				'fill': '#fff',
				'fill-opacity': 0.5
			});
		 
			force.on('tick', tick);

			force.start();
			var n = 100;
			for (var i = n * n; i > 0; --i) force.tick();
			force.stop();
		}
	};

	var go_data,
		go_current;
	d3.json('/data/go/go.json', function(data) {
		go_data = data,
		go.update('GO:0000018');
	});
	
});