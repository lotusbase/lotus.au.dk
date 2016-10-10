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
	
});