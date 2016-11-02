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

	var $cooccurringTable = $('#view__co-occurring table').DataTable({
		'pagingType': 'full_numbers',
		'order': [[3, 'desc']]
	});
	$cooccurringTable.on('search.dt', function() {
		var info = $cooccurringTable.page.info(),
			totalRows = info.recordsTotal,
			filteredRows = info.recordsDisplay,
			$badge = $('#view__co-occurring h3 span.badge');

		// Update counts
		$badge.text(filteredRows);

		if(filteredRows < totalRows) {
			$badge.addClass('subset');
		} else {
			$badge.removeClass('subset');
		}
	});

	// GO ancestor tree
	$('.controls__toggle').click(function(e) {
		e.preventDefault();
		$(this).closest('.facet').toggleClass('controls--visible');
	});
	var goTree = $('#go-ancestor').goTree({
		initNode: $('#go-ancestor').attr('data-go')
	});

	$('#go-ancestor').on('stop.goTree', function() {
		$('#go-ancestor__play-pause')
			.attr('data-state', 'ended')
			.find('span').removeClass().addClass('icon-play').text('Play');
	});

	// Facet controls
	$('#go-ancestor__reset').on('click', function(e) {
		e.preventDefault();
		$('#go-ancestor').goTree('update', $('#go-ancestor').attr('data-go'));
	});
	$('#go-ancestor__play-pause').on('click', function() {
		var $t = $(this);
		if(!$t.attr('data-state') || $t.attr('data-state') === 'playing') {
			// Pause
			$t
			.attr('data-state', 'paused')
			.find('span').removeClass().addClass('icon-play').text('Play');
			$('#go-ancestor').goTree('stop');
		} else {
			// Play
			$t
			.attr('data-state', 'playing')
			.find('span').removeClass().addClass('icon-pause').text('Pause');
			$('#go-ancestor').goTree('start');
		}
	});
	$('#go-ancestor__controls .force').on('input', $.throttle(250, function() {
		$('#go-ancestor').goTree($(this).data('tree-function'), $(this).val());
		$(this).next('output').text($(this).val());
	}));

	// Get force layout configuration when tree is started
	$('#go-ancestor').on('start.goTree', function(event, d) {

		// Update play/pause state
		$('#go-ancestor__play-pause')
			.attr('data-state', 'playing')
			.find('span').removeClass().addClass('icon-pause').text('Pause');
			$('#go-ancestor').goTree('start');

		// Update displayed force options
		$.each(d.force, function(k,f) {
			$('#force-'+k).val(f).next('output').text(f);
		});
	});
	$('#force-bound').on('change', function() {
		$('#go-ancestor').goTree('bound', this.checked);
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