$(function() {

	// Stupid table
	$('#view__go table').stupidtable();

	// jQuery DataTable
	var $transcriptTable = $('#view__transcript table').DataTable({
		'pagingType': 'full_numbers',
		'dom': 'tiprB',
		'buttons': [
			{
				extend: 'csv',
				exportOptions: {
					columns: [0,1,2,3,4],
					format: {
						body: function(data, row, column, node) {
							var _data;

							// Parse table data
							if (column === 0) {
								_data = $(data).find('span.dropdown--title').text();
							} else if (column === 3) {
								_data = $(data).find('ul.dropdown--list li a span').map(function() {
									return $(this).text();
								}).get().join(', ');
							} else {
								_data = data;
							}

							// Return data
							return (_data === '–') ? '' : _data;
						}
					}
				}
			},
			{
				extend: 'print',
				exportOptions: {
					columns: [0,1],
					format: {
						body: function(data, row, column, node) {
							return (column === 1) ? $(data).find('span.dropdown--title').text() : data;
						}
					}
				}
			}
		]
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
	
});