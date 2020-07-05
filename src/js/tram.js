$(function() {
	// Dynamic source/target version filtering
	$('#source-version').on('change', function() {
		var ver = $(this).val();

		// Repopulate target version
		$('#target-version').empty();
		var $availableOptions = $('#source-version option').filter(function() {
			if (ver === 'Gifu_1.2' || ver === 'MG20_2.5') {
				return $(this).val() === 'MG20_3.0';
			}
			return $(this).val() !== ver;
		}).clone();
		$('#target-version').append($availableOptions);
	}).trigger('change');
});