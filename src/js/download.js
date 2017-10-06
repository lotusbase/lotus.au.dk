$(function() {

	// Extend global vars
	$.extend(globalVar, {
		download: {},
		pushState: true
	});

	// Setup index for lunr
	globalVar.download.index = lunr(function() {
		this.field('desc');
		this.field('tag');
		this.field('name');
		this.field('data');
		this.ref('id');
	});

	// Add documents to index
	$('#downloads__file-list > li').each(function(i) {
		var $t = $(this);

		globalVar.download.index.add({
			id: i,
			desc: $t.find('.file-meta__file-desc').text(),
			tag: $t.find('.file-meta__tags li').map(function() {
				return $(this).text();
			}).get().join(' '),
			data: $t.find('.file-meta__data li').map(function() {
				return $(this).text();
			}).get().join(' '),
			name: $t.find('.file-meta__file-title').text()
		});
	});

	// Filtering
	$('#downloads-filter').on('submit', function(e) {
		e.preventDefault();
	});
	
	$('#filter').on('change blur keyup search', $.throttle(250, function() {

		var $t = $(this),
			keyword = $t.val(),
			count = 0;

		$('#download__user-message').empty().removeClass('warning approved').addClass('hidden');

		if(keyword.length > 2) {
			$('#downloads__file-list > li').hide();
			globalVar.download.index.search(keyword).map(function(r) {
				var $result = $('#downloads__file-list > li').eq(r.ref);
				$result.show();
				count++;
			});

			if(!count) {
				$('#download__user-message').html('<span class="pictogram icon-attention">Your search term has not matched any questions. Please try again.</span>').removeClass('hidden').addClass('warning');
			} else {
				$('#download__user-message').html('<span class="pictogram icon-ok">Your search term has returned '+count+' '+globalFun.pl(count, 'result', 'results')+'.</span>').removeClass('hidden').addClass('approved');
			}
		} else {
			$('#downloads__file-list > li').show();
		}
	}));

	// Increment download count when clicked on
	$('#downloads__file-list a.downloads__file-list-item').on('click', function(e) {
		var $count = $(this).find('span.file-meta__download-count'),
			count = parseInt($count.data('count'));

		// Increment count
		count++;

		// Update count
		$count.text(globalFun.addCommas(count));
	});

});