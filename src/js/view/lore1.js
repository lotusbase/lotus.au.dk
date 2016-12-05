$(function() {

	// Tabs
	var insertionStatsTabs = $('#view__insertion-stats__tabs').tabs();

	// Button functions
	var buttons = {
		insertion: function(data, row, column, node) {
			var _data;

			// Parse table data
			_data = data;

			// Return data
			return (_data === '–') ? '' : _data.replace(/<(?:.|\n)*?>/gm, '');
		}
	};

	// DataTable for insertion data
	var $insertionTable = $('#view__lore1ins table').DataTable({
		'pagingType': 'full_numbers',
		'dom': 'lftiprB',
		'scrollX': true,
		'buttons': [
			{
				extend: 'csv',
				exportOptions: {
					columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13],
					format: {
						body: function(data, row, column, node) {
							return buttons.insertion(data, row, column, node);
						}
					}
				}
			},
			{
				extend: 'print',
				exportOptions: {
					columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13],
					format: {
						body: function(data, row, column, node) {
							return buttons.insertion(data, row, column, node);
						}
					}
				}
			}
		]
	});
	$insertionTable.on('search.dt', function() {
		var info = $insertionTable.page.info(),
			totalRows = info.recordsTotal,
			filteredRows = info.recordsDisplay,
			$badge = $('#view__lore1ins h3 span.badge');

		// Update counts
		$badge.text(filteredRows);

		if(filteredRows < totalRows) {
			$badge.addClass('subset');
		} else {
			$badge.removeClass('subset');
		}
	});

	// Chromosome map
	var chromosomeData = [
			{'chromosome': 'chr1', 'length': 62285374 },
			{'chromosome': 'chr2', 'length': 43247325 },
			{'chromosome': 'chr3', 'length': 45610869 },
			{'chromosome': 'chr4', 'length': 42341900 },
			{'chromosome': 'chr5', 'length': 34192293 },
			{'chromosome': 'chr6', 'length': 26985540 }
		],
		map = {
			chart: {
				width: 960,
				height: 400,
				margin: {
					top: 36,
					bottom: 12,
					left: 48,
					right: 128
				},
				trackWidth: 10
			}
		};

	// Draw SVG
	var svg = d3.select('#lore1-by-chromosome')
		.attr({
			'viewBox': '0 0 ' + map.chart.width + ' ' + (map.chart.margin.top + map.chart.margin.bottom + map.chart.height),
			'preserveAspectRatio': 'xMidYMid meet'
		})
		.style('font-family', 'Arial');

	// Scale chromosome length
	var scale_chrY = d3.scale.linear().range([0, map.chart.height - map.chart.margin.top - map.chart.margin.bottom]).domain([0, d3.max(chromosomeData, function(d) {
			return +d.length;
		})]),
		scale_chrX = d3.scale.linear().range([0, map.chart.width - map.chart.margin.left - map.chart.margin.right]).domain([0, 5]);

	// Draw chromosomes
	svg.selectAll('g.chromosome').data(chromosomeData).enter()
		.append('g')
		.attr({
			'class': 'chromosome',
			'id': function(d) {
				return 'chromosome__' + d.chromosome;
			},
			'transform': function(d, i) {
				return 'translate('+(map.chart.margin.left+scale_chrX(i))+','+map.chart.margin.top+')';
			}
		});
	var chr = svg.selectAll('g.chromosome');

	// Append label
	chr.append('text')
		.attr({
			'transform': 'translate(0,-12)'
		})
		.style({
			'font-size': '16px',
			'text-anchor': 'middle'
		})
		.text(function(d) { return d.chromosome; });

	// Append chromosome track
	chr.append('line')
		.attr({
			'class': 'chromosome__track',
			'x1': 0,
			'x2': 0,
			'y1': 0,
			'y2': function(d) {
				return scale_chrY(+d.length);
			}
		})
		.style({
			'stroke': '#999',
			'stroke-width': map.chart.trackWidth + 'px',
			'stroke-linecap': 'round'
		});

	// Create new layer for labels
	var insertionLabelGroup = svg.append('g')
		.attr({
			'class': 'chromosome__labels',
			'transform': 'translate('+(map.chart.margin.left - 0.5*map.chart.trackWidth)+','+map.chart.margin.top+')'
		});

	// Load insertion data from ajax
	$.ajax({
		'url': root + '/api/v1/lore1/' + $('#lore1-by-chromosome').attr('data-lore1'),
		'dataType': 'json'
	})
	.done(function(d) {
		// Remove insertion data from chromosome 0, chloroplast and mitochondria
		var data = d.data.filter(function(ins) {
				return ['chr0','chloro','mito'].indexOf(ins.Chromosome) < 0; 
			}),
			labels = [],
			chrIns = [0,0,0,0,0,0];

		// Append marker
		$.each(data, function(i,ins) {
			var chrIndex = parseInt(ins.Chromosome.replace('chr','')) - 1;

			labels.push({
				x: scale_chrX(chrIndex) + map.chart.trackWidth*2.5 + 4,
				y: scale_chrY(ins.Position),
				orientation: ins.Orientation,
				position: ins.Position,
				chromosome: chrIndex
			});
			
			console.log(chrIndex);
			chrIns[chrIndex] += 1;
		});
		console.log(JSON.stringify(chrIns));

		// Highlight chromosomes with inserts
		svg.selectAll('line.chromosome__track').each(function(d,i) {
			if(chrIns[i] > 0) {
				d3.select(this).style({
					'stroke': 'rgba(51, 138, 132, .65)'
				});
			}
		});

		// Single axis force layout to prevent collision
		var force = d3.layout.force()
			.nodes(labels)
			.charge(-1)
			.gravity(0)
			.size([map.chart.width, map.chart.height]);

		// Insertion labels
		var insertionLabels = insertionLabelGroup.selectAll('.insertion-label')
			.data(labels).enter()
				.append('text')
				.attr({
					'class': 'insertion-label',
					'transform': function(d) {
						return 'translate('+d.x+','+d.y+')';
					},
					'dy': '0.35em'
				})
				.style({
					'font-size': '14px'
				})
				.text(function(d) {
					return globalFun.addCommas(d.position) + ' ('+d.orientation+')';
				});

		// Append pointer
		var insertionPointers = insertionLabelGroup.selectAll('path.insertion-pointer')
			.data(labels).enter()
				.append('path')
				.attr({
					'class': 'insertion-pointer',
					'd': function(d) {
						return 'M'+scale_chrX(d.chromosome)+' '+scale_chrY(d.position)+' h '+(map.chart.trackWidth*1.5)+' l '+map.chart.trackWidth+' 0';
					}
				})
				.style({
					'fill': 'none',
					'stroke': '#333',
					'stroke-width': 1
				});

		// Tick
		force.on('tick', function() {
			insertionLabelGroup.selectAll('text.insertion-label').attr({
				'transform': function(d,i) {
					d.x = scale_chrX(d.chromosome) + map.chart.trackWidth*2.5 + 4;
					if(d.y < 5) d.y = 5;
					if(d.y > map.chart.height - map.chart.margin.top) d.y = map.chart.height - map.chart.margin.top;
					return 'translate('+d.x+','+d.y+')';
				}
			});

			insertionLabelGroup.selectAll('path.insertion-pointer').attr({
				'd': function(d) {
					return 'M'+scale_chrX(d.chromosome)+' '+scale_chrY(d.position)+' h '+(map.chart.trackWidth*1.5)+' L '+(map.chart.trackWidth*2.5+scale_chrX(d.chromosome))+' '+d.y;
				}
			});
		});

		force.start();

	})
	.fail(function() {

	});

});