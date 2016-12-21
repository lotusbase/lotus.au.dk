$(function() {
	// jQuery UI tabs
	var $accountTabs = $('#account-tabs').tabs();
	$d.on('click', '.ui-tabs a.ui-tabs-anchor', function(e) {
		e.preventDefault();
		window.history.pushState({lotusbase: true}, '', $(this).attr('href'));
		$(':input[name="hash"]').val($(this).attr('href').substring(1));
	});
});