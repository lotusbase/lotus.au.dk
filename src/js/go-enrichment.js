jQuery.extend(jQuery.fn.dataTableExt.oSort, {
	"scientific-pre": function ( a ) {
		return parseFloat(a);
	},
	"scientific-asc": function ( a, b ) {
		return ((a < b) ? -1 : ((a > b) ? 1 : 0));
	},
	"scientific-desc": function ( a, b ) {
		return ((a < b) ? 1 : ((a > b) ? -1 : 0));
	}
});
$(function() {
	var $goTable = $('#go-enrichment').DataTable({
		'pagingType': 'full_numbers',
		'columnDefs': [
			{ type: 'scientific', targets: 8 }
		],
		'order': [[8, 'scientific-asc']]
	});

	$('#sample-data').on('click', function() {
		$('#ids-input').val($(this).attr('data-ids')).trigger('blur');
	});
});