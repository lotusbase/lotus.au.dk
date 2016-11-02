$(function() {
	var goTree = $('#go-explorer').goTree({
		shiftClickNavigate: true,
		dblClickUpdate: true,
		allowUpdate: true,
		initNode: $('#go-node').val(),
		jsonLoaded: function() {
			$('#go-root, #go-node').prop('disabled', false);
		}
	});

	$('#go-root').on('change', function() {
		$('#go-node').val($(this).val()).trigger('change');
	});

	$('#go-node').on('change blur', function() {
		$('#go-explorer').goTree('update', $('#go-root').val());
	});

	$d.on('click', '#go-menu__node-update', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#go-explorer').goTree('update', $(this).attr('data-node'));
	});

	// GO ancestor tree
	$('.controls__toggle').click(function(e) {
		e.preventDefault();
		$(this).closest('.facet').toggleClass('controls--visible');
	});

	// Facet controls
	$('#go-explorer__reset').on('click', function(e) {
		e.preventDefault();
		$('#go-explorer').goTree('update', $('#go-node').val());
	});
	$('#go-explorer__play-pause').on('click', function() {
		var $t = $(this);
		if(!$t.attr('data-state') || $t.attr('data-state') === 'playing') {
			// Pause
			$t
			.attr('data-state', 'paused')
			.find('span').removeClass().addClass('icon-play').text('Play');
			$('#go-explorer').goTree('stop');
		} else {
			// Play
			$t
			.attr('data-state', 'playing')
			.find('span').removeClass().addClass('icon-pause').text('Pause');
			$('#go-explorer').goTree('start');
		}
	});
	$('#go-explorer__controls .force').on('input', $.throttle(250, function() {
		$('#go-explorer').goTree($(this).data('tree-function'), $(this).val());
		$(this).next('output').text($(this).val());
	}));
	$('#force-bound').on('change', function() {
		$('#go-explorer').goTree('bound', this.checked);
	});

	// Listen to events bubbling up
	$('#go-explorer')
		// Get force layout configuration when tree is started
		.on('start.goTree', function(event, d) {

			// Update play/pause state
			$('#go-explorer__play-pause')
				.attr('data-state', 'playing')
				.find('span').removeClass().addClass('icon-pause').text('Pause');
				$('#go-explorer').goTree('start');

			// Update displayed force options
			$.each(d.force, function(k,f) {
				$('#force-'+k).val(f).next('output').text(f);
			});
		})

		// When force layout is stopped
		.on('stop.goTree', function() {
			$('#go-explorer__play-pause')
				.attr('data-state', 'ended')
				.find('span').removeClass().addClass('icon-play').text('Play');
		})

		// When tree is updated
		.on('updated.goTree', function(event, d) {
			$('#go-node').val(d.go);
			if($('#go-root option[value="'+d.go+'"]').length) {
				$('#go-root').val(d.go);
			}
		});


	// Image export
	$('a.image-export').on('click', function(e) {
		e.preventDefault();

		// Get SVG
		var source = $(this).data('source');

		// Update SVG dimensions
		var $svg = $('#'+source);
		$svg.attr({
			'width': $svg.width(),
			'height': $svg.height()
		});

		// Get data
		var svg_xml = (new XMLSerializer()).serializeToString($svg[0]),
			output_format = $(this).data('image-type'),
			$form = $('#' + $(this).data('form'));

		// Update form fields
		$form
			.find(':input.svg-data').val(svg_xml).end()
			.find(':input.output-format').val(output_format).end();

		// Submit form
		$form[0].submit();
	});
});