$(function() {
	// Extend global vars
	$.extend(globalVar, {
		faq: {},
		pushState: true
	});

	// Extend global functions
	$.extend(globalFun, {
		faq: {
			// Index
			init: function() {
				// Setup index for lunr
				globalVar.faq.index = lunr(function() {
					this.field('title');
					this.field('tag');
					this.field('content');
					this.ref('id');
				});

				// Add documents to index
				$('#faq-content > ul > li').each(function(i) {
					var $t = $(this);

					globalVar.faq.index.add({
						id: i,
						title: $t.find('h4'),
						tag: $t.attr('data-tag'),
						content: $t.find('p').text()
					});
				});
			},

			// Filtering function
			filter: function() {
				$('#faq__user-message').empty().hide().removeClass('warning approved');
				var keyword = $(this).val(),
				    count = 0;

				$('#faq-content > ul > li').hide();

				if(keyword.length > 2) {
					globalVar.faq.index.search(keyword).map(function(r) {
						var $result = $('#faq-content > ul > li').eq(r.ref);
						$result.show();
						count++;
					});

					if(count < 1) {
						$('#faq__user-message').html('<span class="pictogram icon-cancel"></span><p>Your search term has not matched any questions. Please try again.</p>').show().addClass("warning");
						$('.faq-action').fadeOut();
					} else {
						$('#faq__user-message').html('<span class="pictogram icon-ok"></span><p>Your search term has returned '+count+' '+globalFun.pl(count, 'result', 'results')+'.</p>').show().addClass("approved");
						$('.faq-action').fadeIn();
					}
				} else {
					$('#faq-content > ul > li').show();
				}
			}
		}
	});

	//==============================================================//
	// FAQ functionalities											//
	//==============================================================//

	globalFun.faq.init();

	// Normal tabs for FAQ
	$(".faq table").each(function(){
			if($(this).width() > $(this).parent().innerWidth()) {
				$(this).show().wrap('<div class="overflow-wrap"></div>');
			}
		});
	$(".faq .tabs li a").click(function(e) {
		var $li = $(this).closest('li');

		if(!$li.hasClass('active')) {
			// Highlight current tab, and remove highlight from others
			$li.addClass('active').siblings().removeClass('active');

			// Update filter
			$("#filter").val($(this).attr('data-keyword')).trigger('keyup');
		} else {
			// Remove active tab
			$li.removeClass('active');

			// Update filter
			$("#filter").val('').trigger('keyup');
		}

		// Push history state
		if(globalVar.pushState) window.history.pushState({lotusbase: true}, '', '?q='+$(this).attr('data-keyword'));

		// Remove click event
		e.preventDefault();
	});

	// Redirect to correct FAQ question
	// Only perform redirection when the FAQ page is displayed and the URL query string is not empty
	if($b.hasClass('faq') && window.location.hash) {
		var key = window.location.hash.substring(1), timer;
		$('#filter').val(key);
		timer = window.setTimeout(function() {
			window.clearTimeout(timer);
			$('#filter').trigger('keyup');
			$('#'+key).find('h4').trigger('click');
			globalFun.smoothScroll('#faq-content');
		},500);
	}

	// Evaluate params if present
	var params = globalFun.parseURLquery();
	if(params && params.q) {
		globalFun.faq.filter.call($('#filter')[0]);
	}

	$w.on('popstate', function(e) {
		var params = globalFun.parseURLquery();
		if(e.originalEvent.state !== null && params.q) {
			// Update tabs
			$('.tabs > li > a').removeClass('current').filter('[data-keyword="'+params.q+'"]').addClass('current');

			// Update field
			$('#filter').val(params.q);

			// Filter
			globalFun.faq.filter.call($('#filter')[0]);
		}
	});

	// Hide all answers
	$('#faq-content > ul > li > *:not(h4)').hide();
	$('#faq-wrapper').on('click', '#faq-content h4', function() {
		var $parent = $(this).parent();
		if (!$parent.data('toggled') || $parent.data('toggled') == 'off'){
			$parent.data('toggled', 'on').addClass('open');
			$(this).siblings().show();
		}
		else if ($parent.data('toggled') == 'on'){
			$parent.data('toggled', 'off').removeClass('open');
			$(this).siblings().hide();
		}
	});
	$('.faq #showall').click(function() {
		$('#faq-content > ul > li > *:not(h4)').show();
		$('#faq-content > ul > li').data('toggled', 'on').addClass('open');
	});
	$('.faq #hideall').click(function() {
		$('#faq-content > ul > li > *:not(h4)').hide();
		$('#faq-content > ul > li').data('toggled', 'off').removeClass('open');
	});

	// Live search
	$('#filter')
	.on('keyup', $.throttle(250, function() {
		globalFun.faq.filter.apply(this);
	}))
	.on('keypress', function(e) {
		return e.which !== 13;
	});
});