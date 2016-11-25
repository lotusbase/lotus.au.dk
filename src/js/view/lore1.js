$(function() {

	var $insertionTable = $('#view__lore1ins table').DataTable({
		'pagingType': 'full_numbers'
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

});