$(function() {
	$('.masonry').on('layoutComplete', function() {

		var v = [];

		$(this).find('.masonry-item').each(function() {
			var $t = $(this);

			// Add general class
			$t.removeClass('masonry-col-offset masonry-col-left masonry-col-right').addClass('masonry-col');

			// If item belongs to the left column
			if(parseInt($t[0].style.left) === 0) {
				$t.addClass('masonry-col-left');
			} else {
				$t.addClass('masonry-col-right');
			}

			for (var i = 0; i < v.length; i++) {
				if(Math.abs(parseInt($t[0].style.top) - v[i]) < 15) {
					$t.addClass('masonry-col-offset');
					break;
				}
			}

			// Push vertical position to array
			v.push(parseInt($t[0].style.top));
		});
	});
});