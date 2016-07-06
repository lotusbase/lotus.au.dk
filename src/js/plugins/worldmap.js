$(function() {
	// Adapted from:
	// http://bl.ocks.org/rgdonohue/9280446
	
	var	map, projection, svg, path, g,
		tip = d3.tip()
			.attr('class', 'd3-tip d3-tip__worldmap tip-position--bottom')
			.offset([-10,0])
			.html(function(d) {
				var orderCount = d.properties.orderCount,
					out;

				out = '<p>';

				if(orderCount > 0) {
					out += orderCount + (orderCount > 1 ? ' orders ' : ' order ') + 'shipped to ';
				} else {
					out += 'No order shipped to ';
				}

				out += d.properties.countryName + '</p>';

				return out;
			}),
		mapFun = {
			init: function() {
				map = {
					width: 1000,
					height: 400
				};

				projection = d3.geo.equirectangular()
					.center([0, 15])
					.scale(150)
					.rotate([0, 0])
					.translate([map.width / 2, map.height / 2]);

				svg = d3.select('#world-map').append('svg')
					.attr({
						'viewBox': '0 0 ' + map.width + ' ' + map.height
					});

				path = d3.geo.path()
					.projection(projection);

				mapFun.loadData();
			},
			loadData: function() {
				// Do not use d3 queue, as it fails to send the correct AJAX header (X-Requested-With)
				// This causes d3 to fail the header check in the first few lines of the API file
				//queue()
				//	.defer(d3.json, '/data/d3/world-topo.json').header("X-Requested-With", "XMLHttpRequest")
				//	.defer(d3.json, '/api?t=2').header("X-Requested-With", "XMLHttpRequest")
				//	.await(mapFun.processData);
				var topo = $.ajax({
						url: '/data/d3/world-topo.json',
						dataType: 'json'
					}),
					data = $.ajax({
						url: '/api/v1/lore1/orders/by-country',
						dataType: 'json'
					});

				$.when(topo, data).then(function(t, d) {
					mapFun.processData(null, t[0], d[0]);
				});
			},
			processData: function(error, world, countryDataReturn) {
				var countries = world.objects.countries.geometries,
					attributeArray = [],
					countryData = countryDataReturn.data;

				for (var i in countries) {
					for (var j in countryData) {
						if (countries[i].properties.id == countryData[j].countryCode) {
							for (var k in countryData[j]) {
								if (k !== 'countryCode') {
									if (attributeArray.indexOf(k) == -1) {
										attributeArray.push(k);
									}
									countries[i].properties[k] = countryData[j][k];
								}
							}
							break;
						}
					}
				}

				mapFun.drawMap(world);
			},
			drawMap: function(world) {
				svg.call(tip);
				svg.selectAll('.country')
					.data(topojson.feature(world, world.objects.countries).features)
					.enter()
						.append('path')
						.attr({
							'class': 'country',
							'id': function(d) {
								return 'code_' + d.properties.id; 
							},
							'd': path
						})
						.on('mouseover', tip.show)
						.on('mouseout', tip.hide);

				var dataRange = mapFun.getDataRange();

				// Colors
				var fills = ["#c6dbef","#9ecae1","#6baed6","#4292c6","#2171b5","#08519c","#08306b"],
					color1 = d3.scale.pow().exponent(0.5).domain([dataRange[0], dataRange[1]]).range([0, 1]).nice(),
					color2 = d3.scale.linear().domain(d3.range(0, 1, 1.0/(fills.length - 1))).range(fills);

				// Make fills
				svg.selectAll('.country')
					.attr('fill', function(d) {
						if(d.properties.orderCount === 0 || d.properties.orderCount === undefined) {
							return '#ddd';
						} else {
							return color2(color1(d.properties.orderCount));
						}
					});

				// Make legend
				var ticks = color1.ticks(10),
					gradient = svg.append('defs')
					.append('linearGradient')
					.attr('id', 'worldmap-gradient')
					.attr('x1', '0%')
					.attr('x2', '0%')
					.attr('y1', '0%')
					.attr('y2', '100%');

				gradient.selectAll('stop').data(ticks).enter().append('stop')
				.attr({
					'offset': function(d,i) {
						return (i / ticks.length) * 100 + '%';
					},
					'stop-color': function(d) {
						return color2(color1(d));
					}
				});

				var worldmapLegend = svg.append('g').attr({
					'id': 'worldmap-legend',
					'transform': 'translate(0, '+(0.5 * map.height - 25)+')'
				});
				worldmapLegend.append('rect')
				.attr({
					'width': 20,
					'height': 0.5 * map.height,
					'fill': 'url(#worldmap-gradient)',
					'stroke': '#333',
					'stroke-width': 1
				});

				var worldmapLegendTicks = worldmapLegend.selectAll('.tick-mark').data(ticks);
					worldmapLegendTicks.enter().append('line')
					.attr({
						'y1': function(d, i) {
							return i * ((0.5 * map.height) / (ticks.length - 1));
						},
						'y2': function(d, i) {
							return i * ((0.5 * map.height) / (ticks.length - 1));
						},
						'x1': 0,
						'x2': 20,
						'stroke': function(d, i) {
							return (i === 0 || i === ticks.length-1 ? '#333' : '#fff');
						},
						'stroke-width': 1,
						'stroke-dasharray': '4,12,4',
						'stroke-opacity': 1,
						'class': 'tick-mark'
					});

				var worldmapLegendLabels = worldmapLegend.selectAll('.tick-label').data(ticks);
				worldmapLegendLabels.enter().append('text')
				.attr({
					'class': 'tick-label',
					'font-size': 12,
					'x': 0,
					'y': 0,
					'dy': 4,
					'text-anchor': 'middle',
					'transform': function(d, i) { return 'translate(35, '+(i * ((0.5 * map.height) / (ticks.length - 1)))+')'; }
				})
				.text(function(d) { return Number(Math.round(d+'e2')+'e-2'); });

//				$.each(color1.ticks(10), function(i,v) {
//					var legendItem = $('<li />')
//						.append($('<span />', { 'class': 'legend__color' }).css({ 'background-color': color2(color1(v)) }))
//						.append($('<span />', { 'class': 'legend__title' }).html(v));
//
//					$('#world-map__legend').append(legendItem);
//				});
			},
			getDataRange: function() {
				var min = Infinity,
					max = -Infinity;

				d3.selectAll('.country')
					.each(function(d, i) {
						var currentValue = d.properties.orderCount;
						if (currentValue <= min && typeof currentValue !== typeof undefined) {
							min = currentValue;
						} else if (currentValue >= max && typeof currentValue !== typeof undefined) {
							max = currentValue;
						}
					});

				return [1, max];
			}
		};
	
	mapFun.init();
	
});