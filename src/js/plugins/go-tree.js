;(function($, window, document, undefined) {

	'use strict';

		// Create the defaults once
		var pluginName = 'goTree',
			defaults = {
				chart: {
					width: 960,
					marginLeft: 200,
					height: 620,
				},
				force: {
					charge: -500,
					linkDistance: 5,
					theta: 0.5
				},
				altClickNavigate: true,
				shiftClickNavigate: false,
				showLegend: true,
				initNode: 'GO:0008150',
				jsonLoaded: function() {}
			},
			go_data = {},
			svg,
			bg,
			stage,
			force,
			colors,
			drag,
			goTip,
			goMenu;

		// The actual plugin constructor
		function Plugin (element, options) {
			this.element = element;
			this.settings = $.extend({}, defaults, options);
			this._defaults = defaults;
			this._name = pluginName;
			this.init();
		}

		// Avoid Plugin.prototype conflicts
		$.extend(Plugin.prototype, {
			init: function() {

				var tree = this,
					$t = $(this.element),
					s = this.settings;

				var width = s.chart.width,
					marginLeft = s.chart.marginLeft,
					height = s.chart.height;

				force = d3.layout.force()
					.charge(s.force.charge)
					.linkDistance(s.force.linkDistance)
					.theta(s.force.theta)
					.gravity(s.force.gravity)
					.size([(width - marginLeft), height]);

				drag = force.drag()
					.on('dragstart', function(d) {
						d3.select(this).classed("fixed", d.fixed = true);
						force.stop();
					})
					.on('dragend', function(d) {
						force.stop();
					});

				svg = d3.select(tree.element)
				.attr({
					'viewBox': '0 0 '+width+' '+height,
					'preserveAspectRatio': 'xMidYMid'
				});
				bg = svg.append('rect')
				.attr({
					'x': 0,
					'y': 0,
					'width': width,
					'height': height,
					'fill': 'transparent'
				});
				stage = svg.append('g').attr({
					'id': 'stage',
					'transform': 'translate('+marginLeft+',0)'
				});

				// Link color
				// ['is_a', 'regulates', 'part_of', 'negatively_regulates', 'positively_regulates']
				colors = {
					relationship: d3.scale.ordinal().range([
							'#777',
							'#614BB8',
							'#E85B0C',
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

				// Tips
				goTip = d3.tip()
					.attr({
						'class': 'd3-tip tip--bottom',
						'id': 'go-tip'
					})
					.offset([-15,0])
					.direction('n')
					.html(function(d) {
						return ['<ul>',
							'<li><strong>GO term</strong>: '+d.id+'</li>',
							(d.Namespace ? '<li><strong>Namespace</strong>: '+d.Namespace+'</li>' : ''),
							(d.Name ? '<li><strong>Description</strong>: '+d.Name+'</li>' : ''),
						'</ul>'].join('');
					});

				goMenu = d3.tip()
					.attr({
						'class': 'd3-menu',
						'id': 'go-menu'
					})
					.offset([0,0])
					.direction('se')
					.html(function(d) {
						return [
							'<ul>',
								'<li><a href="'+root+'/view/go/'+d.id+'"><span class="icon-eye">View GO annotation</span></a></li>',
								'<li><a href="#" id="go-menu__node-update" data-node="'+d.id+'"><span class="icon-map">Expand this node</span></a>',
							'</ul>'
						].join('');
					});

				// Fetch data
				d3.json('/data/go/go.json', function(error, data) {
					if(error) {
						$t.hide().after('<div class="user-message warning"><span class="icon-attention"></span>Unable to load GO relational data. We have encountered a <strong>'+error.status+' '+error.statusText+'</strong> error.</div>');
					} else {
						go_data = data;
						tree.update(tree.settings.initNode);

						if(typeof tree.settings.jsonLoaded === 'function') {
							tree.settings.jsonLoaded();
						}
					}
				});

			},

			// Update chart
			update: function(go_term) {

				// Hide tips
				goTip.hide();
				goMenu.hide();

				var go_parent = this.get.ancestors(go_term),
					go_child = this.get.children(go_term);

				// Combine and convert all terms to unique
				var go_terms = globalFun.arrayUnique(go_parent.concat(go_child));
				
				// Create D3 structure
				var d3_data = this.d3_structure(go_term, go_terms);
				
				// Draw graph
				this.draw(d3_data);
			},

			// Get fucntions
			get: {
				// Recursively retrieve all ancestors
				ancestors: function(go_term) {
					var tree = this,
						go_terms = [];

					go_terms.push(go_term);

					// Check for parents
					if(go_data[go_term].p.length > 0) {
						for(var i = 0, count = go_data[go_term].p.length; i < count ; i++) {
							var parent_go_term = go_data[go_term].p[i][0];
							go_terms = go_terms.concat(this.ancestors(parent_go_term));
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
				var tree = this,
					nodes = [],			// Array of node objects
					links = [],			// Array of link objects
					nodeIndex = {},
					go_term;

				// Dimensions
				var width = this.settings.chart.width,
					height = this.settings.chart.height,
					marginLeft = this.settings.chart.marginLeft;

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
						'c': [],
						'x': (width - marginLeft) * Math.random(),
						'y': height * Math.random()
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

						force
						.charge(tree.settings.force.charge*2)
						.linkDistance(tree.settings.force.linkDistance*5)
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

			// Draw chart
			draw: function(s) {

				var tree = this;

				// Dimensions
				var width = this.settings.chart.width,
					height = this.settings.chart.height,
					marginLeft = this.settings.chart.marginLeft;

				var node_go = $.map(s.nodeIndex, function(k,v) {
					return v;
				});

				// Retrieve metadata for each GO node features in the chart
				d3.json('/api/v1/view/go/'+node_go.join(','), function(error, data) {

					var goAPIdata = null;

					// GO namespaces
					var namespace = {
						'p': 'Biological process',
						'f': 'Molecular function',
						'c': 'Cellular component'
					};

					// Error logging for API call
					if(error) {
						console.error('No metadata retrieved for GO terms found in ancestor tree: '+error.status+' '+error.statusText);
					} else {
						goAPIdata = data.data;

						// Merge API data with nodes
						$.each(s.nodes, function(i,n) {
							n.Name = goAPIdata[n.id] ? goAPIdata[n.id].Name : false;
							n.Namespace = goAPIdata[n.id] ? namespace[goAPIdata[n.id].Namespace] : false;
						});
					}

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

					// Remove elements
					svg.select('#legend').remove();
					stage.selectAll('defs').remove();
					stage.selectAll('g.node').remove();
					stage.selectAll('path.link').remove();

					// Click event on stage
					bg.on('click', function() {
						goMenu.hide();
						goTip.hide();
					});

					// Start drawing proper
					var link = stage.selectAll(".link"),
						node = stage.selectAll(".node");

					force
						.nodes(s.nodes)
						.links(s.links)
						.on('tick', tick)
						.on('end', function() {
							$(tree.element).trigger('tree.stop');
						});

					// Build arrows for chart
					stage.append('svg:defs').selectAll('marker')
						.data(d3.range(0, 5))					// Different link/path types can be defined here
						.enter().append('svg:marker')			// This section adds in the arrows
							.attr({
								'id': function(d) { return 'start-'+d; },
								'viewBox': '0 -5 10 10',
								'refX': -8,
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
								// Clear d3 tip
								goTip.hide(d);

								if (d3.event.shiftKey && tree.settings.shiftClickNavigate === true) {
									d3.select(this).classed("fixed", d.fixed = false);
								} else if(d3.event.altKey && tree.settings.altClickNavigate === true) {
									window.location.href = root + '/view/go/' + d.id;
								} else {
									tree.update(d.id);
								}
							})
							.on('contextmenu', function(d) {
								d3.event.preventDefault();
								goTip.hide(d);
								goMenu.show(d);
							})
							.call(drag)
							.call(goTip)
							.call(goMenu)
							.on('mouseover', goTip.show)
							.on('mouseout', goTip.hide);

					var nodeIcon = node
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

					var nodeText = node.append('text')
						.attr({
							'class': 'node',
							'dy': '0.35em',
							'transform': function(d) {
								return 'translate(0,'+d.r*-2+')';
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

					var nodeTextBG = node.insert('rect', 'text')
						.attr({
							'width': function(d) { return d.bbox.width + 8; },
							'height': function(d) { return d.bbox.height + 4; },
							'transform': function(d) {
								return 'translate('+(d.bbox.width + 8) * -0.5+','+((d.bbox.height + 4) * -0.5 + d.r * -2)+')';
							},
							'rx': 4,
							'ry': 4
						})
						.style({
							'fill': '#fff',
							'fill-opacity': 0.5
						});

					// Start layout
					force.start();
					window.setTimeout(function() {
						force.stop();
					}, 3000);

					// Generate legends
					var legend = svg.append('g').attr('id', 'legend');
					var linkLegend = legend.append('g').attr({
							'id': 'legend__link',
							'transform': 'translate(0,0)'
						});
					linkLegend.append('text').attr('class', 'legend__title').text('RELATIONSHIPS').style({
						'font-size': '14px',
						'font-weight': 'bold',
						'dy': '0.35em',
						'cursor': 'help'
					})
					.on('click', function() {
						globalFun.modal.open({
							'title': 'Ontology relationships',
							'content': [
								'<p>Ontology relationships presented in this force layout graph are extracted from the OBO file format obtained from GOC.</p>',
								'<p>Refer to the Gene Ontology Consortium\'s <a href="http://geneontology.org/page/ontology-relations" target="_blank">ontology relations page</a> for further information.</p>'
							].join('')
						});
					})
					.append('tspan')
						.attr('baseline-shift', 'super')
						.style('font-size', '.75em')
						.text('?');
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
					var linkLegendLabel = ['Is a', 'Regulates', 'Part of', 'Negatively regulates', 'Positively regulates'];
					linkLegendItem.append('text')
						.attr({
							'x': 25,
							'y': 0,
							'dy': '0.35em',
						})
						.style({
							'font-size': '12px',
							'cursor': 'help'
						})
						.text(function(d) {
							return linkLegendLabel[d];
						})
						.on('click', function(d) {
							$.ajax('/data/go/'+linkLegendLabel[d].toLowerCase().replace(' ','_'), {
								dataType: 'html'
							})
							.done(function(data) {
								globalFun.modal.open({
									'title': 'Relationship type: <em>'+linkLegendLabel[d]+'</em>',
									'content': '<p>Definition and content obtained from the Gene Ontology Consortium\'s <a href="http://geneontology.org/page/ontology-relations" target="_blank">ontology relations page</a>.</p><hr />'+data,
								});
							});
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
					legend.attr('transform', 'translate(46,'+(height - lc.height)*0.5+')');

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
			},

			// Alias for force.start
			start: function() {
				force.start();
			},

			// Alias for force.stop
			stop: function() {
				force.stop();
			}
		});

		// A really lightweight plugin wrapper around the constructor,
		// preventing against multiple instantiations
		$.fn[pluginName] = function(options) {

			var args = arguments;

			// Check the options parameter
			// If it is undefined or is an object (plugin configuration),
			// we create a new instance (conditionally, see inside) of the plugin
			if (options === undefined || typeof options === 'object') {

				return this.each(function() {
					// Only if the plugin_fluidbox data is not present,
					// to prevent multiple instances being created
					if (!$.data(this, "plugin_" + pluginName)) {

						$.data(this, "plugin_" + pluginName, new Plugin(this, options));
					}
				});

			// If it is defined, but it is a string, does not start with an underscore and does not call init(),
			// we allow users to make calls to public methods
			} else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
				var returnVal;

				this.each(function() {
					var instance = $.data(this, 'plugin_' + pluginName);
					if (instance instanceof Plugin && typeof instance[options] === 'function') {
						returnVal = instance[options].apply(instance, Array.prototype.slice.call(args, 1));
					} else {
						console.warn('The method "' + options + '" used is not defined. Please make sure you are calling the correct public method.');
					}
				});
				return returnVal !== undefined ? returnVal : this;
			}

			// Return to allow chaining
			return this;
		};

})(jQuery, window, document);