$(function() {
	// Dynamic source/target version filtering
	$('#source-version').on('change', function() {
		var ver = $(this).val();

		// Repopulate target version
		$('#target-version').empty();
		var $availableOptions = $('#source-version option').filter(function() {
			return $(this).val() !== ver;
		}).clone();
		$('#target-version').append($availableOptions);
	}).trigger('change');
});