$(function() {
	// Dynamic source/target version filtering
	$('#source-version').on('change', function() {
		var _ver = $(this).val(),
			ver = ['2.5', '3.0'];

		// Repopulate target version
		$('#target-version').empty();
		$.each(ver, function(i,v) {
			if(v !== _ver) {
				$('#target-version').append('<option value="'+v+'">'+v+'</option>');
			}
		});
	});
});