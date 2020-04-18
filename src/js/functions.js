var $w = $(window),
	$d = $(document),
	$b = $('body'),
	root = '';

// Fix console error in IE8
if(console === void 0) {
	var console = {
		log: function (logMsg) { }
	};
}

// Global function: Pluralize
var globalVar = {
	errorMessage: 'Should this error persist, please contact the system administrator',
	cookies: Cookies.noConflict()
};
var globalFun = {
	init: function() {

		// Set custom headers for all API calls
		$.ajaxPrefilter(function(options) {
			if (!options.beforeSend) {
				options.beforeSend = function (xhr) { 
					xhr.setRequestHeader('X-API-KEY', access_token);
					if(globalVar.cookies.get('auth_token')) {
						xhr.setRequestHeader('Authorization', 'Bearer '+globalVar.cookies.get('auth_token'));
					}
				};
			}
		});

		// Create modal window elements on the fly
		$b.append('<div id="modal" class="modal--closed"><article id="modal__wrapper"><header id="modal__title"></header><section id="modal__content"></section><footer id="modal__action"></footer></article><a id="bg-close" href="#"></a></div>');

		// Function: Smooth scrolling
		$d.on('click', 'a[href*="#"]:not([href="#"]):not([data-smooth-scroll="false"]):not([data-custom-smooth-scroll])', function() {
			if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') || location.hostname == this.hostname) {
				globalFun.smoothScroll(this.hash);
			}
		});

		$w.on('hashchange', function(event, opts) {
			if(!opts) opts = { smoothScroll: null };
			if(window.location.hash && !$b.hasClass('init-scroll--disabled') && $b.not('[data-custom-smooth-scroll]') && opts.smoothScroll) {
				globalFun.smoothScroll(window.location.hash);
			}
		});

		// Offset scroll for sticky header
		if(window.location.hash && !$b.hasClass('init-scroll--disabled') && $b.not('[data-custom-smooth-scroll]')) {
			globalFun.smoothScroll(window.location.hash);
		}

		// Terminate scrolling animation when user scrolls
		$w.on('DOMMouseScroll mousewheel', function() {
			$('html, body').stop(true,true);
		});

		// jQuery UI tabs
		$d.on('click', '.ui-tabs a.ui-tabs-anchor', function(e) {
			e.preventDefault();
			window.history.pushState({lotusbase: true}, '', $(this).attr('href'));
			$(':input[name="hash"]').val($(this).attr('href').substring(1));
		});

		// General function to check popstate events
		$w.on('popstate', function(e) {
			if (e.originalEvent.state && e.originalEvent.state.lotusbase) {
				var $tab = $('.ui-tabs ul.ui-tabs-nav li a[href="'+window.location.hash+'"]'),
					index = $tab.parent().index(),
					$parentTab = $tab.closest('.ui-tabs');

				if($tab.length) {
					$parentTab.tabs("option", "active", index);
				} else {
					$('.ui-tabs').tabs("option", "active", 0);
				}
			} else {
				$('.ui-tabs').tabs("option", "active", 0);
			}
		});
	},
	smoothScroll: function(hash) {
		var target = $(hash);
		target = target.length ? target : $('[name=' + hash.slice(1) +']');
		if (target.length) {
			var distance = Math.abs($w.scrollTop() - 24 - (target.offset().top - $('#header nav.main').outerHeight())),
				speed = Math.min(250 + distance/$w.height()*750, 1000);
			$('html,body').animate({
				scrollTop: target.offset().top - 24 - $('#header nav.main').outerHeight()
			}, speed);
			$w.trigger('scroll');
			window.history.pushState({lotusbase: true}, '', hash);
			return false;
		}
	},
	pl: function(number, singular, plural) {
		if(plural === void 0) {
			plural = singular + 's';
		}
		if(number <= 1) {
			return singular;
		} else {
			return plural;
		}
	},
	scrollbarWidth: function() {
		var css = {
			'border':  'none',
			'height':  '200px',
			'margin':  '0',
			'padding': '0',
			'width':   '200px'
		};

		var inner = $('<div>').css($.extend({}, css));
		var outer = $('<div>').css($.extend({
			'left':	   '-1000px',
			'overflow':   'scroll',
			'position':   'absolute',
			'top':		'-1000px'
		}, css)).append(inner).appendTo('body')
		.scrollLeft(1000)
		.scrollTop(1000);

		var scrollSize = {
			'height': (outer.offset().top - inner.offset().top) || 0,
			'width': (outer.offset().left - inner.offset().left) || 0
		};

		outer.remove();
		return scrollSize;
	},
	modal: {
		open: function(opts) {

			var defaults = {
					'title': '',
					'content': '',
					'class': '',
					'allowClose': true,
					'actionButtons': [
						'<a class="button" href="#" data-action="close">Dismiss</a>'
					]
				},
				options = $.extend({}, defaults, opts);

			// Store options
			$('#modal').data('modal-options', JSON.stringify(options));

			// Append buttons
			$('#modal__action').empty();
			$.each(options.actionButtons, function(i,btn) {
				$('#modal__action').append(btn);
			});

			// Open modal box
			$('#modal').removeClass('modal--closed').addClass('modal--open');

			// Check if closing is allowed
			$('#modal__action, a#bg-close').hide().off('click');
			if(options.allowClose === true) globalFun.modal.allowClose();

			// Get position from top
			bodyTop = $w.scrollTop();

			// Write content into modal box
			$('#modal__title').html('<h1>'+options.title+'</h1>');
			$('#modal__content').html(options.content);
			$('#modal__wrapper').addClass(options.class);
			$b.addClass('modal--open');

			// Preload images if any
			if($('#modal__content img').length > 0) {
				var img = new Image();
				$(img).load(function() {
					globalFun.modal.position();
				});
				$(img).attr('src', $('#modal__content img').attr('src'));				
			} else {
				globalFun.modal.position();
			}

			// Run additional functions
			RegexColorizer.colorizeAll('regex-colorize');

			// Ignore default event
			return false;	
		},
		allowClose: function() {
			$('#modal__action, #bg-close').show();
			$d
			.off('click.modal', '#modal__action a.button[data-action="close"], #bg-close')
			.on('click.modal', '#modal__action a.button[data-action="close"], #bg-close', function(e){
				if($(this).attr('href') === '#') {
					e.preventDefault();
					globalFun.modal.close();
				}
			});
		},
		close: function() {
			$('#modal').removeClass('modal--open').addClass('modal--closed');
			$('#modal__title, #modal__content').empty();
			$('#modal__wrapper').removeClass();
			$b.removeClass('modal--open');
			return false;
		},
		update: function(opts) {
			var options = $.extend({}, JSON.parse($('#modal').data('modal-options')), opts);

			var $m = $('#modal');
			if($m.is(':visible')) {
				// Replace title
				if(opts.title !== '') { $('#modal__title').empty().html('<h1>'+opts.title+'</h1>'); }

				// Replace content
				if(opts.content !== '') { $('#modal__content').empty().html(opts.content); }
			}

			// Add class
			if(opts['class'])
				$('#modal__wrapper').addClass(opts['class']);

			// Append updated buttons
			$('#modal__action').empty();
			$.each(options.actionButtons, function(i,btn) {
				$('#modal__action').append(btn);
			});

			// Allow close?
			if(opts.allowClose & opts.allowClose === true) globalFun.modal.allowClose();

			// Reposition
			globalFun.modal.position();
		},
		position: function() {
			$mw = $('#modal__wrapper');
			$mc = $('#modal__content');
			$w.resize($.throttle(250, function(){
				if($mc[0].scrollHeight > $mc[0].offsetHeight) {
					$mc.addClass('overflowing');
				} else {
					$mc.removeClass('overflowing');
				}
			})).resize();
		},
		fail: function(jqXHR, textStatus, errorThrown) {
			var d = jqXHR.responseJSON;
			globalFun.modal.update({
				'title': errorThrown,
				'content': '<p>We have encountered an error '+(d.status ? '(error code: <code>'+d.status+'</code>) ' : '')+'while processing your request.</p>'+(d.message ? '<p>'+d.message+'</p>' : ''),
				'class': 'warning'
			});
			globalFun.modal.allowClose();
		}
	},
	loadingIndicator: function(opts) {
		var $hide = opts.toHide;

		if($hide.valid()) {
			$hide
				.slideUp(opts.hideSpeed)
				.after("<div class='user-message loading-message'><div class='loader'><svg><circle class='path' cx='40' cy='40' r='30' /></svg></div><p class='loading-text'>"+opts.message+"</p></div></div>")
				.next()
				.hide()
				.slideDown(opts.showSpeed);

			if(opts.scrollTo) {
				$('html, body').animate({
					scrollTop: $hide.next().offset().top
				});
			}
		}
	},
	seqPro: {
		replaceTag: function(tag) {
			return tagsToReplace[tag] || tag;
		},
		parseInput: function(parseOpts) {
			if(parseOpts.dataType) {
				var type = parseOpts.dataType,
					out = $('#data-input').val(),
					$msg = $('#output h3 + p'),
					pat,
					rep;

				$msg.empty();
				if(out) {
					// Escape HTML
					out = out.replace(/[&<>]/g, globalFun.seqPro.replaceTag);
					
					// If input is not empty
					if(type == 'aant') {
						// Amino acid or nucleotide sequence
						out = out.replace(/>lcl.*(\r\n|\n|\r)/gim,'');
						out = out.replace(/([0-9\s\t]|(\r\n|\n|\r))/g,'');
						$msg.text('You have entered an amino acid / nucleotide sequence.');
					} else if(type == 'blast') {
						// BLAST output
						// Display option to refine BLAST output
						$('#data-refine').fadeIn();

						// Refine option (for columns)
						if(parseOptsallCols) {
							// If serialized options exist that specifies all columns to be displayed

							if(out.match(/^(node|chr|ctg)/gim)) {
								rep = '<tr><td>$1</td><td>$3</td><td>$4</td></tr>';
							} else if(out.match(/^(lj(\d|chloro|mito|XYLT|1_4_FUCT)g\dv)/gim)) {
								rep = '<tr><td>$1</td><td>$2</td><td>$3</td><td>$4</td></tr>';
							} else {
								rep = '<tr><td>$1</td><td>$2</td><td>$3</td><td>$4</td></tr>';
							}
						} else {
							// If not, fall back to default behaviour (display accessions only)
							rep = '$1';
						}

						// Pattern matching for accessions
						if(out.match(/^(node|chr|ctg)/gim)) {
							// For LORE1 and genomic databases
							pat = /((node|chr|ctg)[\w\.]*) {2,}(\d{2,4}) {2,}([\d|e|\-|\.]+)/gim;
						} else if(out.match(/^(lj(\d|chloro|mito|XYLT|1_4_FUCT)g\dv)/gim)) {
							// For Lj protein database
							pat = /(lj\dg[\d\.]+) {2,}([\w\d\=\.\-\(\)\[\]\{\}, ]+?) {2,}(\d{2,4}) {2,}([\d|e|\-|\.]+)/gim;
						} else {
							// For general blast
							pat = /([0-9a-z\.]+) {2,}([\w\s=\.]+) {2,}(\d{2,4}) {2,}([\d|e|\-|\.]+)/gim;
						}

						// Get output
						out = out.replace(pat, rep);

						$msg.text("You have provided a BLAST output. Further options of refining the processed output are available, please see below.");
					}

					// Write output
					if(parseOpts.allCols) {
						$('.tools.seqpro #output')
							.show()
							.find('table')
								.show()
								.html(out)
								.siblings('pre')
									.hide();

						// Adjust textarea height
						// $("form #output textarea").height($("form #output textarea")[0].scrollHeight + 5);

					} else {
						$('.tools.seqpro #output')
							.show()
							.find('pre')
								.show()
								.text(out)
								.siblings('table')
									.hide();
					}
					
				} else {
					// If input is empty
					$('.tools.seqpro #output').hide();
				}
			} else {
				globalFun.modal.open({
					'title': 'Whoops!',
					'content': '<p>You have not selected a data type. Please try again.</p>',
					'class': 'warning'
				});
			}
		}
	},
	arrayUnique: function(a) {
		var seen = {},
			out = [],
			len = a.length,
			j = 0;
		for(var i = 0; i < len; i++) {
			var item = a[i];
			if(seen[item] !== 1) {
				seen[item] = 1;
				out[j++] = item;
			}
		}
		return out;
	},
	escapeHTML: function(str) {
		 var entityMap = {
			"&": "&amp;",
			"<": "&lt;",
			">": "&gt;",
			'"': '&quot;',
			"'": '&#39;',
			"/": '&#x2F;'
		};

		return String(str).replace(/[&<>"'\/]/g, function (s) {
			return entityMap[s];
		});
	},
	addCommas: function(nStr) {
		nStr += '';
		var x = nStr.split('.');
		var x1 = x[0];
		var x2 = x.length > 1 ? '.' + x[1] : '';
		var rgx = /(\d+)(\d{3})/;
		while (rgx.test(x1)) {
			x1 = x1.replace(rgx, '$1' + ',' + '$2');
		}
		return x1 + x2;
	},
	multipleTextInput: {
		parse: function(booleanMode) {
			var $t = $(this),
				string = $t.val(),
				b = booleanMode || false;

			if(string && string !== '' && string !== ' ' && string !== ',') {
				if(!b) {
					// Explode into array with delimiters: space, tab, newline, comma
					// And then return string
					return string.replace(/([\s\,;\|#]+)/gi, ' ').split(' ').filter(function(n) { return n !== void 0; });
				} else {
					// If we are using boolean mode:
					return string.match(/([\.\w\+\-\:<>]+|[\"\'][\.\w\s\+\-\:<>]*[\"\'])+/gi);
				}
			} else {
				return false;
			}
	
		},
		updateInputList: function(booleanMode, strings) {
			if(strings) {
				var $t = $(this),
					existingStrings = $t.closest('ul.input-values').find('li').map(function() {
						return $(this).attr('data-input-value');
					}).get();

				// Loop through all strings, check if they are present
				$.each(strings, function(idx, str) {
					if(existingStrings.indexOf(str) === -1) {
						str = str.replace(/\"/gi, '&quot;');
						$t.closest('li').before('<li data-input-value="'+str+'">'+str+'<span class="icon-cancel" data-action="delete"></span></li>');
					}
				});

				// Empty input value
				$t.val('');

				// Trigger change on list
				$t.closest('ul.input-values').trigger('update');

				globalFun.multipleTextInput.updateHidden.call($t[0], booleanMode);
			}
		},
		updateHidden: function(booleanMode) {
			var $m = $(this).closest('.multiple-text-input'),
				vals = $m.find('ul.input-values li').map(function() {
					return $(this).data('input-value');
				}).get();

			if(vals.length) {
				$(this).closest('.input-mimic').next('button.input-mimic__clear').removeClass('hidden');
			} else {
				$(this).closest('.input-mimic').next('button.input-mimic__clear').addClass('hidden');
			}

			$m.find('input.input-hidden').val(vals).trigger('change');
		}
	},
	collapseForm: function() {
		if($(this).data('wrapped') !== 1) {
			$(this)
			.data('wrapped', 1)
			.wrap('<div class="search-again hide-first toggle"></div>')
			.closest('.toggle')
			.prepend('<h3><a href="#">Repeat search</a></h3>');
		}
		$(this).slideUp(250);
	},
	parseURLquery: function() {
		return $.deparam(window.location.search.substring(1), true);
	},
	getCookie: function() {
		var c = globalVar.cookies.getJSON('lotusbase');
		if(!c || typeof c === undefined) {
			c = {};
		}
		return c;
	},
	getJWT: function() {
		var c = globalVar.cookies.get('auth_token');

		if(!c || typeof c === undefined) {
			c = {};
		} else {
			c = globalFun.parseJWT(c);
		}

		return c;
	},
	parseJWT: function(token) {
		var base64Url = token.split('.')[1],
			base64 = base64Url.replace('-', '+').replace('_', '/');
		return JSON.parse(window.atob(base64));
	},
	color: {
		// Adapted from https://css-tricks.com/snippets/javascript/lighten-darken-color/
		brightness: function(color, amount) {
			var usePound = false;

			if (color[0] == "#") {
				color = color.slice(1);
				usePound = true;
			}

			var num = parseInt(color,16);

			var r = (num >> 16) + amount;
			if		(r > 255) r = 255;
			else if	(r < 0) r = 0;

			var b = ((num >> 8) & 0x00FF) + amount;
			if		(b > 255) b = 255;
			else if	(b < 0) b = 0;

			var g = (num & 0x0000FF) + amount;
			if		(g > 255) g = 255;
			else if	(g < 0) g = 0;

			return (usePound?"#":"") + (g | (b << 8) | (r << 16)).toString(16);
		},
		lighten: function(color, amount) {
			return globalFun.color.brightness(color, Math.abs(amount));
		},
		darken: function(color, amount) {
			return globalFun.color.brightness(color, Math.abs(amount)*-1);
		}
	},
	stickyTable: function() {
		if(typeof($('#rows').data('sticky')) !== undefined) {
			$('#rows').after($('<table />', {
				'id': 'sticky',
				'class': $('#rows').attr('class')
			}));
			$('#rows thead, #rows colgroup').clone().appendTo('#sticky');
			$('#sticky').hide();
		}
	},
	friendlyTime: function(t, p) {
		// Note: time (t) must be provided in milliseconds

		if (p === void 0) {
			p = 2;
		}

		var s = parseFloat(t)/1000,
			unit;
		if (s < 60) {
			unit = globalFun.pl(s, 'second');
		} else if (s < 60 * 60) {
			s = s / 60;
			unit = globalFun.pl(s, 'minute');
		} else {
			s = s/(60*60);
			unit = globalFun.pl(s, 'hour');
		}

		return s.toFixed(p) + ' ' + unit;
	},
	secondsToHMS: function(d) {
		d = Number(d);
		var h = Math.floor(d / 3600),
			m = Math.floor(d % 3600 / 60),
			s = Math.floor(d % 3600 % 60);
		return ((h > 0 ? h + ":" + (m < 10 ? "0" : "") : "") + m + ":" + (s < 10 ? "0" : "") + s);
	},
	// JSON string validation from http://stackoverflow.com/a/20392392/395910
	jsonCheck: function(jsonString) {
		try {
			var o = JSON.parse(jsonString);

			// Handle non-exception-throwing cases:
			// Neither JSON.parse(false) or JSON.parse(1234) throw errors, hence the type-checking,
			// but... JSON.parse(null) returns 'null', and typeof null === "object", 
			// so we must check for that, too.
			if (o && typeof o === "object" && o !== null) {
				return o;
			}
		}
		catch (e) { }
		return false;
	},
	isRetina: function() {
		if (window.matchMedia) {
			var mq = window.matchMedia("only screen and (min--moz-device-pixel-ratio: 1.3), only screen and (-o-min-device-pixel-ratio: 2.6/2), only screen and (-webkit-min-device-pixel-ratio: 1.3), only screen  and (min-device-pixel-ratio: 1.3), only screen and (min-resolution: 1.3dppx)");
			return (mq && mq.matches || (window.devicePixelRatio > 1)); 
		}
	}
};



$(function() {

	// Turn off global caching
	$.ajaxSetup ({
		cache: false
	});

	// Initialize global options
	var parseOpts = {},
		bodyTop;

	// Fetch cookies
	globalVar.siteCookie = globalFun.getCookie();
	globalVar.userAuth = globalFun.getJWT();

	// Initialize global actions
	globalFun.init();


	//==============================================================//
	// Modal box functions											//
	//==============================================================//

	// Empty model box content upon closing
	$d.keyup(function(e) { 
		if (e.keyCode == 27) { globalFun.modal.close(); } 
	});

	// Model box: Display search help
	$d.on("click.modal", "a[data-modal]", function() {
		var title = $(this).attr('title'),
			modalClass = $(this).attr('data-modal');

		//if(this.href && /\.(txt|php|html?)/gi.test(this.href)) {
		if(this.href && this.href.indexOf('#') < 0 && !/(jpe?g|gif|png|svg|bmp)/gi.test(this.href)) {
			// Load content of remote file
			$.ajax(this.href, {
				dataType: 'html'
			})
			.done(function(data) {
				globalFun.modal.open({
					'title': title,
					'content': data,
					'class': modalClass
				});
			});
		} else {
			globalFun.modal.open({
				'title': $(this).attr('title'),
				'content': "<p>"+$(this).attr('data-modal-content')+"</p>",
				'class': modalClass
			});
		}

		return false;
	});





	//==============================================================//
	// Header navigation											//
	//==============================================================//

	// Mark header navigation items with children
	$("header nav > ul > li").each(function() {
		if($(this).children("ul").length > 0) {
			$(this).addClass("has-child");
			$("<span>").addClass("icon--has-child icon-down-open").appendTo($(this).children("a"));
		}
	});
	$("header nav > ul > li li").each(function() {
		if($(this).children("ul").length > 0) {
			$(this).addClass("has-child");
			$("<span>").addClass("icon--has-child icon-right-open").appendTo($(this).children("a"));
		}
	});

	// Click event
	$('#header nav.main > ul > li > a').on('click', function(e) {
		if($w.width() < 767) {
			var $li = $(this).closest('li');
			if(!$li.hasClass('h-home')) {
				e.preventDefault();
				$li.toggleClass('hover').siblings().removeClass('hover');
			}
		}
	});
	$('#header nav.main > ul > li > ul').on('click', function(e) {
		if($w.width() < 767) {
			$(this).closest('li').removeClass('hover');
		}
	});

	// Scroll event	
	$w.scroll(function() {
		// Hides side navigation when user is at top
		var $nav = $("#header nav.main"),
			st = $(window).scrollTop();

		if (st > 0) {
			$nav.addClass('is-sticky has-background');
		} else {
			$nav.removeClass('is-sticky has-background');
		}
	});




	//==============================================================//
	// Top notifications											//
	//==============================================================//

	// Check cookie for settings
	var topNotifications = $('#top-notifications').children().map(function() { return $(this).attr('id'); }).get();
	$.each(topNotifications, function(i,cookieObject) {
		if(globalVar.siteCookie[cookieObject] && globalVar.siteCookie[cookieObject] === 1) {
			$b.addClass(cookieObject + '__dismissed');
		} else {
			$b.addClass(cookieObject + '__active');
			$('#'+cookieObject).addClass('notification__visible');
		}
	});

	// Allow users to dismiss warnings
	$('#top-notifications a.button[data-action="dismiss"], #top-notifications a.button[data-action="accept"]').click(function(e) {
		e.preventDefault();

		// Hide parent
		$(this).closest('.site-notification').slideUp(250);

		// Check if cookie and its object exists
		var cookieObject = $(this).closest('.site-notification').attr('id');
		globalVar.siteCookie[cookieObject] = 1;

		// Set cookie
		globalVar.cookies.set(
			'lotusbase',
			globalVar.siteCookie,
			{ path: '/' }
			);

	});

	// Countdown timer
	$('.countdown[data-duration]').each(function() {
		var $t = $(this),
			updateCountdown = function() {
				$t.data('duration', $t.data('duration') - 1);
				var diff = $t.data('duration');
				$t.text(moment.duration(diff, 'seconds').humanize(true));		
			};

		$t.data('duration', $(this).attr('data-duration'));

		updateCountdown();
		window.setInterval(updateCountdown, 1000);
	});



	//==============================================================//
	// Flexbox image replacements									//
	//==============================================================//
	$('.cols .bg-fill').each(function() {
		var $img = $(this).find('img').first();
		$(this).css('background-image', 'url('+$img.attr('src')+')');
	});



	//==============================================================//
	// Masonry grid													//
	//==============================================================//
	var $masonry = $('.masonry').imagesLoaded(function() {
		$('.masonry')
		.isotope({
			itemSelector: '.masonry-item',
			percentPosition: true,
			isResizable: false,
			masonry: {
				columnWidth: '.masonry-sizer',
				gutter: '.masonry-gutter'
			},
			getSortData: {
				order: '[data-order] parseInt'
			},
			sortBy: 'order',
			transitionDuration: '.5s'
		});	
	});
	$w.on('resize', $.debounce(500, function() {
		$('.masonry').isotope('layout');
	}));



	//==============================================================//
	// Site search form												//
	//==============================================================//
	$('form.search-form')
	.find('select.qtype')
		.on('change manualchange', function(e) {
			var $o		= $(this).find('option:selected'),
				$f		= $(this).closest('form'),
				params	= $o.data('form-params');

			// Remove hidden inputs
			$f.find('.search-form__param').remove();

			// Add new inputs
			if(params) {
				$.each(params, function(i,v) {
					$f.append('<input type="hidden" class="search-form__param" value="'+v+'" name="'+i+'" />');
				});
			}

			$f
			// Update form action
			.attr('action', $o.data('form-action'))
			// Change input name and placeholder
			.find('input[type="search"]')
				.attr({
					'name': $o.data('form-query-var'),
					'placeholder': $o.data('input-placeholder')
				});

			// If search field is already filled, submit the form
			if(e.type !== 'manualchange' && $(this).prev(':input[type="search"]').val()) {
				$f[0].submit();
			}

		})
		.on('keydown', function(e) {
			if (e.which === 13) {
				$(this).closest('form').trigger('submit');
			}
		})
		.end()
	.find('input[type="search"]')
		.on('keydown paste', $.throttle(250, function() {
			if(/^(Lj|chr\d\.CM\d{4}|PGSB_(mRNA|gene)_\d+|LotjaGi\dg\dv\d{7})/gi.test($(this).val())) {
				$(this).siblings('select.qtype').find('option[value="gene"]').prop('selected', true).trigger('manualchange');
			} else if (/^(3|DK\d{2}\-03)\d{7}$/gi.test($(this).val())) {
				$(this).siblings('select.qtype').find('option[value="lore1"]').prop('selected', true).trigger('manualchange');
			}
		}));


	// Input mimic for tag-like inputs
	$('.input-mimic').each(function() {
		$(this).after('<button class="input-mimic__clear button--plain '+($(this).find('.input-hidden').val() ? '' : 'hidden')+'" type="button" title="Clear all inputs"><span class="icon-cancel icon--no-spacing"></span></button>');
	});
	$d.on('click', '.input-mimic__clear', function() {
		var $input = $(this).prev('.input-mimic').find('ul.input-values li.input-wrapper input'),
			$tags = $(this).prev('.input-mimic').find('ul.input-values li[data-input-value]');

		$input.focus();
		$tags.remove();
		globalFun.multipleTextInput.updateHidden.call($input[0]);
	});
	$('.input-mimic')
	.on('keydown', 'input', function(e) {

		var $t = $(this),
			booleanMode = $(this).attr('data-boolean-mode') || false,
			keyCodes = [188,9,13];

		if(!booleanMode) keyCodes.push(32);

		// What key is pressed?
		// ",", "enter", tab or spacebar
		if (keyCodes.indexOf(e.which) > -1 && $t.val()) {
			e.preventDefault();
			e.stopPropagation();

			// Parse input value
			// Sometimes if the user types really fast, we missed capturing separator keys
			// ... so parsing is still required
			var strings = globalFun.multipleTextInput.parse.call($t[0], booleanMode);

			// Update list
			globalFun.multipleTextInput.updateInputList.call($t[0], booleanMode, strings);

			// Submit form if enter key is pressed
			if(e.which == 13) $t.closest('form')[0].submit();

		} else if (e.which === 8 && !$t.val()) {
			$t.closest('li').siblings().not('.input-wrapper').last().remove();
			globalFun.multipleTextInput.updateHidden.call($t[0]);
		}
	})
	.on('focus', 'input', function() {
		$(this).closest('.input-mimic').addClass('focus');
	})
	.on('blur', 'input', function() {
		var $t = $(this),
			booleanMode = $(this).attr('data-boolean-mode') || false,
			strings = globalFun.multipleTextInput.parse.call($t[0], booleanMode);

		// Remove focus
		$t.closest('.input-mimic').removeClass('focus');

		// Update list
		globalFun.multipleTextInput.updateInputList.call($t[0], booleanMode, strings);

	}).on('paste', 'input', function() {
		var $t = $(this);
	});

	$('.input-mimic')
	.on('click', 'li span[data-action="delete"]', function(e) {
		e.stopPropagation();
		var $p = $(this).parent('li'),
			$input = $p.siblings('li.input-wrapper').find('input');

		$input.focus();
		$p.fadeOut(250, function() {
			$p.remove();
			globalFun.multipleTextInput.updateHidden.call($input[0]);
		});
	})
	.on('click', '.input-values', function(e) {
		e.stopPropagation();
	})
	.on('click', function() {
		$(this).find('ul li.input-wrapper input').focus();
	})
	.find('input.input-hidden')
		.on('manualchange', function() {

			// Remove previously entered values
			$(this).prev('.input-values').find('li[data-input-value]').remove();

			// Set new value
			var $input = $(this).prev('.input-values').find('.input-wrapper input');
			$input.val($(this).val()).trigger('blur');

		});

	// Search toggle
	$(".toggle.hide-first > *:not(h3)").hide();
	$d.on('click', '.toggle h3 a', function(e) {

		// Prevent default
		e.preventDefault();

		// Toggle status
		if(!$(this).data('toggled') || $(this).data('toggled') === 'off') {
			$(this)
				.addClass('open')
				.data('toggled', 'on')
				.parent().siblings().show();		
		} else if($(this).data('toggled') === 'on') {
			$(this)
				.removeClass('open')
				.data('toggled', 'off')
				.parent().siblings().hide();	
		}

		// Update position of sticky table header
		if($('#sticky').length > 0) {
			$('#sticky').css({
				'top': $('#rows thead').offset().top
			});
		}
	});

	// AJAX to fetch gene annotation
	$d.on('click', '.api-gene-annotation', function(e) {
		var q = $(this).data('gene');
		var genomeEcotype = $(this).data('genome-ecotype');
		var genomeVersion = $(this).data('genome-version');
		var strict = 0;

		$.get(
			root + '/api/v1/gene/annotation/' + encodeURIComponent(genomeEcotype) + '/' + encodeURIComponent(genomeVersion) + '/' + encodeURIComponent(q),
			'strict=' + encodeURIComponent(strict),
			function(data) {
				if(!data.error) {
					globalFun.modal.open({
						'title': 'Gene annotation for ' + q,
						'content': '<pre class="gene-annotation">' + data.data[0].annotation + '</pre>'
					});
				} else {
					msg = 'The annotation of the gene <code>' + q + '</code> is unavailable, i.e. it is yet to be annotated.';
					if(parseFloat(genomeVersion) < parseFloat(3.0) && genomeEcotype === 'MG20') {
						msg += ' You are searching using an older version of the <em>LORE1</em> database &mdash; you might want to repeat your search with the latest database.';
					}
					globalFun.modal.open({
						'title': 'Gene annotation unavailable',
						'content': msg,
						'class': 'warning'
					});
				}
			},
			'json'
		).fail(function(xhr, status, thrownError) {
			globalFun.modal.open({
				'title': 'Whoops!',
				'content': 'Error ' + xhr.status + ': ' + thrownError,
				'class': 'warning'
			});
		});
		e.preventDefault();
		e.stopImmediatePropagation();
	});

	// AJAX to fetch insertion flank
	$('.results .api-insertion-flank').click(function(e) {
		var q = $(this).data('key');
		var genomeEcotype = $(this).data('genome-ecotype');
		var genomeVersion = $(this).data('genome-version');

		globalFun.modal.open({
			'title': 'Fetching flanking sequence&hellip;',
			'content': '<div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div>'
		});

		$.get(
			root + '/api/v1/lore1/flanking-sequence/' + encodeURIComponent(genomeEcotype) + '/' + encodeURIComponent(genomeVersion) + '/' + encodeURIComponent(q),
			'',
			function(data) {
				var d = data.data;
				globalFun.modal.update({
					'title': 'Flanking sequence (&#177;1000bp) for the insertion <a href="/genome/?data=genomes%2Flotus-japonicus%2Fmg20%2Fv3.0&loc='+d.chromosome+'%3A'+(parseInt(d.position)-5000)+'..'+(parseInt(d.position)+5000)+'">'+d.chromosome+'_'+d.position+'_'+d.orientation+'</a>',
					'content': '<pre class="insertion-flank">' + d.insFlank + '</pre>'
				});
			},
			"json"
		).fail(function(xhr, status, thrownError) {
			globalFun.modal.update({
				'title': 'Whoops! We have encountered an error!',
				'content': 'Error ' + xhr.status + ': ' + thrownError,
				'class': 'warning'
			});
		});
		e.preventDefault();
	});

	// Download type check
	$('#dlc').click(function() {
		$('#dlt').val('checked');
		if($('#dl tbody input:checkbox:checked').length > 0) {
			$('#dl').submit();
		} else {
			// Interrupt form submission when there is nothing checked
			globalFun.modal.open({
				'title': 'Whoops!',
				'content': "<p>You have not selected any rows to be downloaded. Please check that you have selected at least one row of search results.</p>",
				'class': 'warning'
			});	
		}
	});
	$('#dla').click(function() {
		$('#dlt').val('all');
		$('#dl').submit();
	});

	// Sticky table header
	// Clone original table header and then hide it
	globalFun.stickyTable();
	$w
	.scroll(function() {
		// Checks if element exists, so that an error isn't thrown
		if($('#rows thead').length > 0) {
			var topPadding = $('#header nav.main').outerHeight();
			if ($w.scrollTop() > $('#rows thead').offset().top - topPadding) {
				if($w.scrollTop() > $('#rows').offset().top + $('#rows').outerHeight() - $('#rows tbody tr').last().outerHeight() - $('#sticky').outerHeight() - topPadding) {
					$('#sticky').fadeOut();
				} else {
					$('#sticky').fadeIn().addClass('is-sticky').css({
						top: topPadding
					});
				}
			} else {
				$('#sticky').fadeOut().removeClass('is-sticky').css({
					top: $('#rows thead').offset().top
				});
			}
		}
	})
	.resize($.throttle(125, function() {
		$('#sticky thead th').each(function(i) {
			$(this).width($('#rows thead th').eq(i).width());
		});

		// Update sticky width and position
		if($('#sticky').length > 0) {
			$('#sticky').css({
				'width': $('#rows').width(),
				'left': $('#rows').offset().left
			});
		}
	}));

	// Table column highlight
	$b.not('.loreview').on('mouseover mouseleave', '#rows td, #sticky td', function(e){
		if(e.type == 'mouseover') {
			$('#rows colgroup, #sticky colgroup').eq($(this).index()).addClass('hover');
			$('#rows thead td').eq($(this).index()).addClass('hover');
			$('#sticky thead td').eq($(this).index()).addClass('hover');
		} else {
			$('#rows colgroup, #sticky colgroup').eq($(this).index()).removeClass('hover');
			$('#rows thead td').eq($(this).index()).removeClass('hover');
			$('#sticky thead td').eq($(this).index()).removeClass('hover');
		}
	});

	// Check all / Uncheck all toggle
	$d.on('change', 'thead .ca', function() {
		if($(this).prop('checked')) {
			$('#rows tbody input:checkbox').prop('checked', true).trigger('change').closest('tr').addClass('checked');
			$('.ca').prop('checked', true);
		} else {
			$('#rows tbody input:checkbox').prop('checked', false).trigger('change').closest('tr').removeClass('checked');
			$('.ca').prop('checked', false);
		}

		countChecked();
	});

	// Highlight checked rows
	$d
	.on('change', '#rows tbody input:checkbox', function() {
		if($(this).prop('checked')) {
			$(this).closest('tr').addClass('checked');
		} else {
			$(this).closest('tr').removeClass('checked');
		}

		// Toggle checkall status
		var checked = countChecked(),
			all = $('#rows tbody input:checkbox').length;
		if (checked === all) {
			$('#rows, #sticky').find('.ca').prop({
				'checked': true,
				'indeterminate': false
			});
		} else if (checked === 0) {
			$('#rows, #sticky').find('.ca').prop({
				'checked': false,
				'indeterminate': false
			});
		} else {
			$('#rows, #sticky').find('.ca').prop({
				'checked': false,
				'indeterminate': true
			});
		}
	})
	.on('click', '#rows tbody input:checkbox', function(e) {
		e.stopPropagation();
	});

	// Clicking on row leads to checking
	$d
	.on('click', '#rows tbody tr', function(e) {
		if($(e.target).closest('.button').length === 0) {
			var $checkbox = $(this).find('td.chk input:checkbox');
			if($checkbox.prop('checked')) {
				$checkbox.prop('checked', false);
			} else {
				$checkbox.prop('checked', true);
			}
			$checkbox.trigger('change');
		}
	});

	// Count checked
	var countChecked = function() {
		var cCount = $('#rows tbody input:checkbox:checked').length;
		$('#dlc span').text(cCount);
		return cCount;
	};

	// Form validation
	// 1. Add validation by regex
	$.validator.addMethod(
		'regex',
		function(value, element, regexp) {
			var re = new RegExp(regexp);
			return this.optional(element) || re.test(value);
		},
		'Input contains invalid characters, <kbd>;</kbd><kbd>&gt;</kbd><kbd>&lt;</kbd><kbd>\'</kbd><kbd>&quot;</kbd><kbd>\`</kbd><kbd>:</kbd><kbd>/</kbd><kbd>*</kbd><kbd>?</kbd><kbd>!</kbd><kbd>&amp;</kbd> . Please revise your input.'
	);

	// 2. Rules
	$('#primers-form').validate({
		rules: {
			version: 'required',
			blast: 'required',
			ref: 'required'
		}
	});
	$('#trex-form').validate({
		rules: {
			trx: function(el) {
				return $('#annotation').is(':empty');
			},
			anno: function(el) {
				return $('#transcript').is(':empty');
			}
		}
	});





	//==============================================================//
	// Downloads													//
	//==============================================================//
	// Download count live update
	$('.downloads section li a').click(function() {
		var filecount = $(this).parent().siblings('.file-count').find('.file-count-wrap');
		var downloadcount = parseInt(filecount.attr('data-count')) + 1;
		filecount.attr('data-count', downloadcount).html(globalFun.addCommas(downloadcount));
	});

	



	//==============================================================//
	// SeqPro														//
	//==============================================================//
	var tagsToReplace = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;'
	};

	// BLAST output refine
	$('#all-cols').click(function(){
		// parseOpts.allCols: Determines if all columns in the BLAST output should be displayed
		if($('#all-cols').prop('checked')) {
			parseOpts.allCols = true;
		} else {
			parseOpts.allCols = false;
		}

		// Parse on-the-fly (remember to pass parseOpts)
		globalFun.seqPro.parseInput(parseOpts);
	});

	// SeqPro
	$('.tools.seqpro #output, .tools.seqpro #data-refine, .tools.seqpro #output table').hide();
	$('.tools.seqpro form #data-input').keyup(function() {
		// If automatic detection is selected
		if($('#input-type option[value="auto"]').is(':selected')) {
			var input = $('#data-input').val();
			if(input.match(/^(node|chr|ctg|lj)/i)) {
				$('#input-type option[value="blast"]').attr('selected','selected');
			} else if(input.match(/(\s{2,}|[0-9]|>lcl)/gi)){
				$('#input-type option[value="aant"]').attr('selected','selected');
			}
		}
		// parseOpts.dataType: the type of data submitted for parsing
		parseOpts.dataType = $('#input-type').val();

		// Parse on-the-fly (remember to pass parseOpts)
		globalFun.seqPro.parseInput(parseOpts);
	});
	$(".tools.seqpro form #input-type").change(function() {
		// parseOpts.dataType: the type of data submitted for parsing
		parseOpts.dataType = $(this).val();

		// Parse on-the-fly (remember to pass parseOpts)
		globalFun.seqPro.parseInput(parseOpts);
	});


	// Get recently selected database
	if(globalVar.siteCookie.recentDB && globalVar.siteCookie.recentDB.length > 0) {
		var $recentDBList = $('<div id="recent-db" class="input-suggestion"><h4>Recently selected databases:</h4><ul></ul></div>');
		$.each(globalVar.siteCookie.recentDB, function(i) {
			if(i <=5 ) {
				$('<li><a class="button" role="secondary" href="#" data-db-index="'+this.dbIndex+'" data-db-file-name="'+this.dbFileName+'" data-db-access-date="'+this.dbAccessDate+'">'+this.dbName+'</a></li>').appendTo($recentDBList.find("ul"));			
			}
		});
		$recentDBList.insertAfter("#seqret-db");
		$(".input-suggestion").show();
	}
	$d.on("click", "#recent-db li a", function(e) {
		var db = $(this).data("db-file-name");
		$("#seqret-db option").filter(function() {
			return $(this).val() === db;
		}).prop("selected", true).trigger('change');
		$("#seqret-db").focus();
		e.preventDefault();
	});

	// On search results page, go through all annotation links
	var exonArray = [];
	$(".results .api-gene-annotation").each(function() {
		if($.inArray($(this).data("gene"), exonArray) < 0) {
			exonArray.push($(this).data("gene"));
		}
	});
	if(exonArray.length > 0) {
		// Retrieve annotations from server by AJAX
		var exons = exonArray.join(',');
		var genomeId = $("#dl").find("input[name='genome']").val();
		var genomeParts = genomeId.split('_');

		$.get(
			root + '/api/v1/gene/annotation/' + genomeParts[0] + '/' + genomeParts[1] + '/' + exons,
			'strict=0',
			function(data) {
				// Log error if any
				if(data.error) {
					$('.results .api-gene-annotation').each(function() {
						$(this)
							.addClass('warning')
							.html('<span class="pictogram icon-attention">'+data.message+'</span>')
							.off("click")
							.on("click", function(e) {
								e.preventDefault();
							})
							.attr("title", "Whoops!")
							.animate({
								"opacity": 0.33333
							},1000);
					});
				} else {
				// Go through each api link
					var	d = data.data,
					returnedExons = $.map(d, function(v) {
						return v.gene;
					});

					$(".results .api-gene-annotation").each(function() {

						// Change icons
						if($.inArray($(this).data("gene"), returnedExons) >= 0) {
							$(this)
								.html("<span class='pictogram icon-book'>Get gene annotation</span>")
								.attr("title", "Get gene annotation.");
						} else {
							$(this)
								.html("<span class='pictogram icon-cancel'>No gene annotation available</span>")
								.off("click")
								.on("click", function(e) {
									e.stopPropagation();
									e.preventDefault();
								})
								.attr("title", "No annotations were found for this gene.")
								.animate({
									"opacity": 0.33333
								},1000);
						}
					});					
				}

			},
			"json"
		).fail(function(xhr, status, thrownError) {
			$("#dl").after("<p class='user-message warning'>We have encountered a problem when trying to fetch annotations for the genes displayed in the table above. If this message persists, please contact the system administrator.<br />"+thrownError+"</p>");
		});
	}


	// Manipulate ID strings for probeset submission
	$('#probes-result button').click(function(e) {
		var idType = $(this).data('idtype');
			ids = $('#probes-result td.chk input[type="checkbox"]'+($('#probes-result td.chk input[type="checkbox"]:checked').length === 0 ? '' : ':checked')).map(function() {
				return $(this).data(idType);
			}).get();
		window.location.href = $('#probes-result form').attr('action') + '?ids=' + globalFun.arrayUnique(ids).join(',');
	});
	$('#probes-result input[type="checkbox"].ca').on('change', function() {
		if($(this).prop('checked')) $('#probes-result tbody td.chk input[type="checkbox"]').prop('checked', true);
	});
	$('#probes-result td.chk input[type="checkbox"]').on('change', function() {
		var t = $('#probes-result td.chk input[type="checkbox"]').length,
			c = $('#probes-result td.chk input[type="checkbox"]:checked').length,
			$ca = $('#probes-result input[type="checkbox"].ca');

		if(c === 0) {
			$ca.prop({
				'indeterminate': false,
				'checked': false
			});
		} else if (t > c) {
			$ca.prop('indeterminate', true);
		} else {
			$ca.prop({
				'indeterminate': false,
				'checked': true
			});
		}
	});
});

// Adjust sticky header cell sizes after window is done loading all assets, as font-face takes awhile to load and will affect cell width!
$(window).on('load', function() {
	// Stretch sticky's total width
	if($('#rows').length) {
		$('#sticky').css({
			'width': $('#rows').width(),
			'left': $('#rows').offset().left
		});

		// Pass computed widths
		$('#sticky thead th').each(function(i) {
			$(this).width($('#rows thead th').eq(i).width());
		});
	}
});