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

	$('#go-ancestor').on('tree.stop', function() {
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
			$('#go-ancestor').goTree('start');
		} else {
			// Play
			$t
			.attr('data-state', 'playing')
			.find('span').removeClass().addClass('icon-pause').text('Pause');
			$('#go-ancestor').goTree('stop');
		}
	});
	
});