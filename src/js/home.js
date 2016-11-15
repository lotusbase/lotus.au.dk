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
	$('.input-suggestions a').on('click', function(e) {
		e.preventDefault();

		$(this).closest('div').siblings(':input[type="search"]').val($(this).data('value'));
	});

	// Trigger odometer change when scroll position is appropriate
	var worldMapOdometerUpdated = false;
	$w.on('scroll resize load', function() {
		if($w.scrollTop() + 200 > $('#world-map').offset().top + $('#world-map').height() - $w.height()) {
			$('#world-map').siblings('.col-content').find('.odometer').each(function() {
				$(this).html($(this).data('target-value'));
			});
			worldMapOdometerUpdated = true;
		}
	});
});