var d3 = require('d3'),
	fs = require('fs');

// Attempt to read file
fs.readFile(process.argv.slice(2)[0], 'utf8', function(err, data) {

	if(err) {
		console.log(JSON.stringify({
			status: '404',
			message: err
		}));
	} else {
		// Parse JSON file
		obj = JSON.parse(data);

		// Extract configuration
		var config = obj['config'],
			values = obj['values'];

		var min = +config['min'],
			max = +config['max'],
			fills = config['fills'],
			scaleColumn = +config['scaleColumn'];

		// Define scales
		var scale1 = d3.scale.linear()
				.domain([min,max])
				.range([0, 1])
				.nice(),
			scale2 = d3.scale.linear()
				.domain(d3.range(0, 1, 1.0 / (fills.length - 1)))
				.interpolate(d3.interpolateHcl)
				.range(fills);

		// Scale data
		var scaledData = values.map(function(x) {
			var scale = function(value) {
				var _c;
				if(value < min) {
					_c = fills[0];
				} else if(value > max) {
					_c = fills[fills.length-1];
				} else {
					_c = scale2(scale1(value));
				}
				return _c;
			};

			x[scaleColumn] = scale(+x[scaleColumn]);

			return x;
		});

		console.log(JSON.stringify({
			status: '200',
			data: scaledData,
			config: config
		}));
	}
});