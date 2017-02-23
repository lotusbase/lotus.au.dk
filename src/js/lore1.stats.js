$(function() {
	// LORE1 statstics
	globalVar.lore1 = {
		total: {
			insertions: 707573,
			lines: 135716,
			genes: 87230
		},
		subset: {
			genic: {
				insertions: 586321,
				lines: 131375,
				genes: 42166
			},
			exonic: {
				insertions: 321368,
				lines: 117710,
				genes: 41732
			}
		}
	};

	// What metrics are we looking at
	var metrics = ['insertions', 'lines', 'genes'];

	// Draw pie chart
	var pieChart = {
			width: 500,
			height: 500,
			radius: 250
		},
		pieArc = d3.svg.arc()
			.outerRadius(pieChart.radius - 10)
			.innerRadius(pieChart.radius - 120),
		pieColors = {
			total: '#ccc',
			filler: '#ccc',
			genic: '#5ca4a9',
			intronic: '#aaa',
			exonic: '#33658a'
		},
		pie = d3.layout.pie().sort(null).value(function(d) {
			return +d.count;
		});

	// Draw pie chart for each metric
	$.each(metrics, function(i,m) {
		
		var pieSVG = d3.select('#lore1--pie__'+m).attr({
			viewBox: '0 0 '+pieChart.width+' '+pieChart.height
		}).append('g')
			.attr('transform', 'translate('+pieChart.width/2+','+pieChart.height/2+')');

	});

	// Functions
	globalFun.lore1 = {
		toggleCheck: function(duration) {
			if($('#stats-toggle__subset').is(':checked')) {
				// Update statistics
				globalFun.lore1.updateStats('exonic', duration);

				// Update visual cue
				$('#stats-toggle span.subset--genic').addClass('inactive');
				$('#stats-toggle span.subset--exonic').removeClass('inactive');
			} else {
				// Update statistics
				globalFun.lore1.updateStats('genic', duration);

				// Update visual cue
				$('#stats-toggle span.subset--exonic').addClass('inactive');
				$('#stats-toggle span.subset--genic').removeClass('inactive');
			}
		},
		updateStats: function(type, duration) {
			// Iterate through all metrics
			$.each(metrics, function(i,m) {
				$('.lore1__stats--subset')
				.removeClass('lore1__stats--genic lore1__stats--exonic')
				.addClass('lore1__stats--'+type);
				$('.lore1__stats--subset .metric__'+m+' .count').attr('data-target-value', globalVar.lore1.subset[type][m]);
				$('.lore1__stats--subset .subset-text').text(type);
			});

			// Tween counts and pie chart
			globalFun.lore1.tween(type, duration);
		},
		tween: function(type, duration) {
			// Tween counts
			var count = d3.selectAll('.lore1__stats--'+type);
			count.selectAll('div.count').transition().duration(duration || 1500)
				.tween('div.count', function(d) {
					var start = parseInt($(this).text().replace(',','')),
						end = parseInt($(this).attr('data-target-value')),
						i = d3.interpolate(start, end);

					return function(t) {
						d3.select(this).text(globalFun.addCommas(parseInt(i(t))));
					};
				});

			// Tween pie chart
			$.each(metrics, function(i,m) {
				var data = [];
				if(type === 'total') {
					data = data.concat([{
						type: 'filler',
						count: 0
					}, {
						type: 'total',
						count: globalVar.lore1.total[m]
					}]);
				} else if(type === 'genic') {
					data = data.concat([{
						type: 'genic',
						count: globalVar.lore1.subset.genic[m]
					}, {
						type: 'others',
						count: globalVar.lore1.total[m]-globalVar.lore1.subset.genic[m]
					}]);
				} else {
					data = data.concat([{
						type: 'exonic',
						count: globalVar.lore1.subset.exonic[m]
					}, {
						type: 'intronic',
						count: globalVar.lore1.subset.genic[m] - globalVar.lore1.subset.exonic[m]
					}, {
						type: 'others',
						count: globalVar.lore1.total[m]-globalVar.lore1.subset.genic[m]
					}]);
				}

				var pieSVG = d3.select('#lore1--pie__'+m).select('g'),
					pieRatio = pieSVG.append('text').attr({
						'class': 'percentage',
						'dy': '-0.35em'
					}).style({
						'text-anchor': 'middle',
						'font-size': '32px'
					}).text(0).data(0),
					pieG = pieSVG.selectAll('.arc').data(pie(data));

				// arcTween function
				var arcTween = function(a) {
					var i = d3.interpolate(this._current, a);
					this._current = i(0);
					return function(t) {
						return pieArc(i(t));
					};
				};

				// Set
				pieG.enter().append('path')
					.attr({
						'class': 'arc',
						'd': pieArc
					})
					.style('fill', function(d) {
						return pieColors[d.data.type] || '#ccc';
					})
					.each(function(d) {
						this._current = d;
					});

				// Transition
				pieG.transition()
					.duration(duration || 1500)
					.style('fill', function(d) {
						return pieColors[d.data.type] || '#ccc';
					})
					.attrTween('d', arcTween);

				// Exit
				pieG.exit()
					.remove();
			});
		}
	};

	// Dynamically insert statistics, then tween counts
	globalFun.lore1.tween('total');
	
	// Update statistics based on checkbox status
	globalFun.lore1.toggleCheck();
	$('#stats-toggle__subset').on('change', function() {
		globalFun.lore1.toggleCheck(500);
	});

	// Disable all form submissions
	$('form.lore1__stats__form').on('submit', function(e) {
		e.preventDefault();
	});
});