$(function() {
	var searchTabs = $('#searchform-tabs').tabs();

	// General function to check popstate events
	$w.on('popstate', function(e) {
		if (e.originalEvent.state && e.originalEvent.state.lotusbase) {
			var $tab = $('.ui-tabs ul.ui-tabs-nav li a[href="'+window.location.hash+'"]'),
				index = $tab.parent().index(),
				$parentTab = $tab.closest('.ui-tabs');
			$parentTab.tabs("option", "active", index);
		}
	});

	// Searchform suggestions
	$('.searchform__suggestions a').on('click', function(e) {
		e.preventDefault();

		$(this).closest('.searchform__suggestions').siblings(':input[type="search"]').val($(this).data('value'));
	});
});