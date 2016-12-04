$(function() {

	// Stupid table
	$('#view__go table').stupidtable();

	// Button functions
	var buttons = {
		transcript: function(data, row, column, node) {
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
			return (_data === '–') ? '' : _data.replace(/<(?:.|\n)*?>/gm, '');
		},
		cooccurring: function(data, row, column, node) {
			var _data;

			// Parse table data
			if (column === 0) {
				if($(data).find('span.dropdown--title').length) {
					_data = $(data).find('span.dropdown--title').text();
				} else {
					_data = data;
				}
			} else {
				_data = data;
			}

			// Return data
			return (_data === '–') ? '' : _data.replace(/<(?:.|\n)*?>/gm, '');
		}
	};

	// DataTable for transcripts
	var $transcriptTable = $('#view__transcript table').DataTable({
		'pagingType': 'full_numbers',
		'dom': 'lftiprB',
		'buttons': [
			{
				extend: 'csv',
				exportOptions: {
					columns: [0,1,2,3,4],
					format: {
						body: function(data, row, column, node) {
							return buttons.transcript(data, row, column, node);
						}
					}
				}
			},
			{
				extend: 'print',
				exportOptions: {
					columns: [0,1,2,3,4],
					format: {
						body: function(data, row, column, node) {
							return buttons.transcript(data, row, column, node);
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
		'dom': 'lftiprB',
		'order': [[3, 'desc']],
		'buttons': [
			{
				extend: 'csv',
				exportOptions: {
					columns: [0,1,2,3],
					format: {
						body: function(data, row, column, node) {
							return buttons.cooccurring(data, row, column, node);
						}
					}
				}
			},
			{
				extend: 'print',
				exportOptions: {
					columns: [0,1,2,3],
					format: {
						body: function(data, row, column, node) {
							return buttons.cooccurring(data, row, column, node);
						}
					}
				}
			}
		]
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