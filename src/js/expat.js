$(function() {
	// Sortable list for pre-search columns
	var exportSortConditionsUpdate = function() {
		// Update custom sort
		var sortedConditions = $('#expat-sort-conditions li').map(function() {
			return $(this).data('condition');
		}).get();
		if(sortedConditions.length > 0) {
			$('#expat-condition').val(sortedConditions.join(','));
			expatCondition = sortedConditions.join(',');
		} else {
			$('#expat-condition').val('');
			expatCondition = '';
		}
	};
	$('#expat-sort-conditions')
	.sortable({
		placeholder: 'ui-state-highlight',
		activate: function() {
			$(this).addClass('ui-state-active');
		},
		deactivate: function() {
			$(this).removeClass('ui-state-active');
		},
		update: exportSortConditionsUpdate
	})
	.on('manualsortupdate', exportSortConditionsUpdate);
	$d.on('click', '#expat-sort-conditions li span.icon-cancel', function() {
		// Update checkbox status
		$('#expat-dataset-subset table input[type="checkbox"][data-condition="'+$(this).parent('li').data('condition')+'"]').prop('checked', false).closest('tr').removeClass('checked');

		// Remove draggable handler
		$(this).closest('li').fadeOut(125, function() {
			$(this).remove();
			$('#expat-sort-conditions').trigger('manualsortupdate');
		});
	});
});