/**
 * Load necessary polyfills.
 */
$.webshims.polyfill('forms');


/**
 * Simple, small jQuery extensions for convenience.
 */
(function ($) {

	$.fn.sticky = function() {
		return this.each(function() {
			var $t = $(this),
				$p = $t.parent(),
				$c = $(this).children('div');

			// Set width
			$c.width($t.width());

			// Refresh width upon resize
			$(window).resize(function() {
				$c.width($t.width());
			});

			// Listen to scroll
			$(window).scroll(function() {
				var st = $(window).scrollTop();
				
				if (st > $p.offset().top - $('#header nav.main').outerHeight()) {
					if (st > $p.offset().top + $p.outerHeight() - $c.outerHeight() - $('#header nav.main').outerHeight()) {
						$c.css({
							'position': 'absolute',
							'top': $p.outerHeight() - $c.outerHeight()
						});
					} else {
						$c.css({
							'position': 'fixed',
							'top': $('#header nav.main').outerHeight()
						}).addClass('affixed');
					}
				} else {
					$c.css({
						'position': 'static',
						'top': 0
					}).removeClass('affixed');
				}
			});
		});
	};

	/**
	 * Disable an element.
	 *
	 * Sets `disabled` property to `true` and adds `disabled` class.
	 */
	$.fn.disable = function () {
		return this.prop('disabled', true).addClass('disabled');
	};

	/**
	 * Enable an element.
	 *
	 * Sets `disabled` property to `false` and removes `disabled` class
	 * if present.
	 */
	$.fn.enable = function () {
		return this.prop('disabled', false).removeClass('disabled');
	};

	/**
	 * Check an element.
	 *
	 * Sets `checked` property to `true`.
	 */
	$.fn.check = function () {
		return this.prop('checked', true);
	};

	/**
	 * Un-check an element.
	 *
	 * Sets `checked` property to `false`.
	 */
	$.fn.uncheck = function () {
		return this.prop('checked', false);
	};


	/**
	 * Initialise Bootstrap tooltip on an element with presets. Takes title.
	 */
	$.fn._tooltip = $.fn.tooltip;
	$.fn.tooltip  = function (options) {
		return this
		._tooltip('destroy')
		._tooltip($.extend({
			container: 'body',
			placement: 'left'
		}, options));
	};

	/**
	 * Returns true / false if any modal is active.
	 */
	$.modalActive = function () {
		var active = false;
		$('.modal').each(function () {
			var modal = $(this).data('bs.modal');
			if (modal) {
				active = modal.isShown;
				return !active;
			}
		});
		return active;
	};


	/**
	 * Wiggle an element.
	 *
	 * Used for wiggling BLAST button.
	 */
	$.fn.wiggle = function () {
		this.finish().effect("bounce", {
			direction: 'left',
			distance: 24,
			times: 4,
		}, 250);
	};


	/**
	 * Check's every 100 ms if an element's value has changed. Triggers
	 * `change` event on the element if it has.
	 */
	$.fn.poll = function () {
		var that = this;
		var val  = null;
		var newval;

		(function ping () {
			newval = that.val();

			if (newval != val){
				val = newval;
				that.change();
			}

			setTimeout(ping, 100);
		}());

		return this;
	};
}(jQuery));

/*
	SS - SequenceServer's JavaScript module

	Define a global SS (acronym for SequenceServer) object containing the
	following methods:

		main():
			Initializes SequenceServer's various modules.
*/

//define global SS object
var SS;
if (!SS) {
	SS = {};
}

//SS module
(function () {

	// Starts with >.
	SS.FASTA_FORMAT = /^>/;

	SS.decorate = function (name) {
	  return name.match(/(.?)(blast)(.?)/).slice(1).map(function (token, _) {
		if (token) {
			if (token !== 'blast'){
				return '<strong>' + token + '</strong>';
			}
			else {
			  return token;
			}
		}
	  }).join('');
	};

	/**
	 * Pre-check the only active database checkbox.
	 */
	SS.onedb = function () {
		var database_checkboxes = $(".databases input:checkbox");
		if (database_checkboxes.length === 1) {
			database_checkboxes.check();
		}
	};


	SS.generateGraphicalOverview = function () {
		$("[data-graphit='overview']").each(function () {
			var $this = $(this);
			var $graphDiv = $('<div/>').addClass('graphical-overview');
			$this.children().eq(1).children().eq(0).before($graphDiv);

			$.graphIt($this, $graphDiv, 0, 20);
		});
	};

	SS.updateDownloadFastaOfAllLink = function () {
		var num_hits = $('.hitn').length;

		var $a = $('.download-fasta-of-all');
		if (num_hits >= 1 && num_hits <= 30) {
			var sequence_ids = $('.hitn :checkbox').map(function() {
				return this.value;
			}).get();
			$a
			.enable()
			.attr('href', SS.generateURI(sequence_ids, $a.data().databases));
			return;
		}

		if (num_hits === 0) {
			$a.attr('title', 'No sequences have been selected for downloading.');
		}

		if (num_hits > 30) {
			$a.attr('title', 'No more than 30 sequences can be fetched at the same time.');
		}

		$a
		.disable()
		.removeAttr('href');
	};

	/* Update the FASTA downloader button's state appropriately.
	 *
	 * When more than 30 hits are obtained, the link is disabled.
	 * When no hits are obtained, the link is not present at all.
	 */
	SS.updateDownloadFastaOfSelectedLink = function () {
		var num_checked  = $('.hitn :checkbox:checked').length;

		var $a = $('.download-fasta-of-selected');
		var $n = $a.find('span');

		if (num_checked >= 1 && num_checked <= 30) {
			var sequence_ids = $('.hitn :checkbox:checked').map(function () {
				return this.value;
			}).get();

			$a
			.enable()
			.attr('href', SS.generateURI(sequence_ids, $a.data().databases))
			.find('span').html(num_checked);
			return;
		}

		if (num_checked === 0) {
			$n.empty();
			$a.attr('title', 'No sequences have been selected for downloading.');
		}

		if (num_checked > 30) {
			$a.attr('title', 'No more than 30 sequences can be fetched at the same time.');
		}

		$a
		.disable()
		.removeAttr('href');
	};

	SS.updateSequenceViewerLinks = function () {
		var MAX_LENGTH = 10000;

		$('.view-sequence:not(.table__button)').each(function () {
			var $this = $(this);
			var $hitn = $this.closest('.hitn');
			if ($hitn.data().hitLen > MAX_LENGTH) {
				$this
				.disable()
				.removeAttr('href');
			}
		});
	};

	SS.setupTooltips = function () {

	};

	SS.setupDownloadLinks = function () {
		$('.download').on('click', function (event) {
			event.preventDefault();
			event.stopPropagation();

			if (event.target.disabled) return;

			var url = this.href;
			$.get(url)
			.done(function (data) {
				window.location.href = url;
			})
			.fail(function (jqXHR, status, error) {
				SS.showErrorModal(jqXHR, function () {});
			});
		});
	};

	SS.generateURI = function (sequence_ids, database_ids) {
		// Encode URIs against strange characters in sequence ids.
		sequence_ids = encodeURIComponent(sequence_ids.join(' '));
		database_ids = encodeURIComponent(database_ids);

		var url = "get_sequence/?sequence_ids=" + sequence_ids +
			"&database_ids=" + database_ids + '&download=fasta';

		return url;
	};

	SS.showErrorModal = function (jqXHR, beforeShow) {
		setTimeout(function () {
			beforeShow();
			if (jqXHR.responseText) {
				$("#error").html(jqXHR.responseText).modal();
			}
			else {
				$("#error-no-response").modal();
			}
		}, 500);
	};

	SS.init = function () {
		this.$sequence = $('#sequence');
		this.$sequenceFile = $('#sequence-file');
		this.$sequenceControls = $('.sequence-controls');

		SS.blast.init();
	};
}()); //end SS module

/**
 * Highlight hit div corresponding to given checkbox and update bulk download
 * link.
 */
SS.selectHit = function (checkbox) {
	if (!checkbox || !checkbox.value) return;

	var $hitn = $($(checkbox).data('target'));

	// Highlight selected hit and sync checkboxes if sequence viewer is open.
	if(checkbox.checked) {
		$hitn
		.addClass('glow')
		.find(":checkbox").not(checkbox).check();
	} else {
		$hitn
		.removeClass('glow')
		.find(":checkbox").not(checkbox).uncheck();
	}

	this.updateDownloadFastaOfSelectedLink();
};

$(document).ready(function(){
	SS.init();

	var notification_timeout;

	// drag-and-drop code
	var tgtMarker = $('.dnd-overlay');
	var $sequence = $('#sequence');

	var dndError = function (id) {
		$('.dnd-error').hide();
		$('#' + id + '-notification').show();
		window.setTimeout(function() {
			$('#' + id + '-notification').fadeOut(2000);
		});
	};

	$(document)
	.on('dragenter', function (evt) {
		// Do not activate DnD if a modal is active.
		if ($.modalActive()) return;

		// Based on http://stackoverflow.com/a/8494918/1205465.
		// Contrary to what the above link says, the snippet below can't
		// distinguish directories from files. We handle that on drop.
		var dt = evt.originalEvent.dataTransfer;
		var isFile = dt.types && ((dt.types.indexOf &&  // Chrome and Safari
								  dt.types.indexOf('Files') != -1) ||
								  (dt.types.contains && // Firefox
								   dt.types.contains('application/x-moz-file')));

		if (!isFile) { return; }

		$('.dnd-error').hide();
		tgtMarker.stop(true, true);
		tgtMarker.show();
		dt.effectAllowed = 'copy';
		if ($sequence.val() === '') {
			$('.dnd-overlay-overwrite').hide();
			$('.dnd-overlay-drop').show('drop', {direction: 'down'}, 'fast');
		}
		else {
			$('.dnd-overlay-drop').hide();
			$('.dnd-overlay-overwrite').show('drop', {direction: 'down'}, 'fast');
		}
	})
	.on('dragleave', '.dnd-overlay', function (evt) {
		tgtMarker.hide();
		$('.dnd-overlay-drop').hide();
		$('.dnd-overlay-overwrite').hide();
	})
	.on('keyup', function(evt) {
		if(evt.which === 27) {
			tgtMarker.hide();
			$('.dnd-overlay-drop').hide();
			$('.dnd-overlay-overwrite').hide();
		}
	})
	.on('click', function() {
		tgtMarker.hide();
		$('.dnd-overlay-drop').hide();
		$('.dnd-overlay-overwrite').hide();
	})
	.on('dragover', '.dnd-overlay', function (evt) {
		evt.originalEvent.dataTransfer.dropEffect = 'copy';
		evt.preventDefault();
	})
	.on('drop', '.dnd-overlay', function (evt) {
		evt.preventDefault();
		evt.stopPropagation();

		var textarea  = $('#sequence');
		var indicator = $('#sequence-file');
		textarea.focus();

		var files = evt.originalEvent.dataTransfer.files;
		if (files.length > 1) {
			dndError('dnd-multi');
			return;
		} else if (files.length === 0) {
			dndError('dnd-no-file');
			return;
		}

		var file = files[0];
		if (file.size > 10 * 1048576) {
			dndError('dnd-large-file');
			return;
		}

		var reader = new FileReader();
		reader.onload = function (e) {
			var content = e.target.result;
			if (SS.FASTA_FORMAT.test(content)) {
				textarea.val(content);
				indicator.text(file.name);
				tgtMarker.hide();
			} else {
				// apparently not FASTA
				dndError('dnd-format');
			}
		};
		reader.onerror = function (e) {
			// Couldn't read. Means dropped stuff wasn't FASTA file.
			dndError('dnd-format');
		};
		reader.readAsText(file);
	});
	// end drag-and-drop

	SS.$sequence.on('change blur', function () {
		if (SS.$sequence.val() && SS.$sequence.val() !== '') {
			// Calculation below is based on -
			// http://chris-spittles.co.uk/jquery-calculate-scrollbar-width/
			var sequenceControlsRight = SS.$sequence[0].offsetWidth -
				SS.$sequence[0].clientWidth;
			SS.$sequenceControls.css('right', sequenceControlsRight + 10);
			SS.$sequenceControls.removeClass('hidden');
		}
		else {
			SS.$sequenceFile.empty();
			SS.$sequenceControls.addClass('hidden');
			SS.$sequence.parent().removeClass('has-error');
		}
	});

	// Handle clearing query sequences(s) when x button is pressed.
	$('#btn-sequence-clear').click(function (e) {
		$('#sequence').val("").focus();
	});

	// Pre-select if only one database available.
	SS.onedb();

	// Handles the form submission when Ctrl+Enter is pressed anywhere on page
	$(document).bind("keydown", function (e) {
		if (e.ctrlKey && e.keyCode === 13 && !$('#method').is(':disabled')) {
			$('#method').trigger('submit');
		}
	});

	$('#sequence').on('sequence_type_changed', function (event, type) {
		clearTimeout(notification_timeout);
		$(this).parent().removeClass('has-error');
		$('.notifications .active').slideUp().removeClass('active');

		if (type) {
			$('#' + type + '-sequence-notification').slideDown().addClass('active');

			notification_timeout = setTimeout(function () {
				$('.notifications .active').slideUp().removeClass('active');
			}, 5000);

			if (type === 'mixed') {
				$(this).parent().addClass('has-error');
			}
		}
	});

	$('body').click(function () {
		$('.notifications .active').hide().removeClass('active');
	});

	$('.databases').on('database_type_changed', function (event, type) {
		switch (type) {
			case 'protein':
				$('.databases.nucleotide input:checkbox').disable();
				$('.databases.nucleotide .checkbox').addClass('disabled');
				$('ul.ui-tabs-nav > li[aria-controls="database-nucleotide"], #database-nucleotide').addClass('disabled');
				break;
			case 'nucleotide':
				$('.databases.protein input:checkbox').disable();
				$('.databases.protein .checkbox').addClass('disabled');
				$('ul.ui-tabs-nav > li[aria-controls="database-protein"], #database-protein').addClass('disabled');
				break;
			default:
				$('.databases input:checkbox').enable();
				$('.databases .checkbox').removeClass('disabled');
				$('ul.ui-tabs-nav > li[aria-controls], div.ui-tabs-panel').removeClass('disabled');
				break;
		}
	});

	$('form').on('blast_method_changed', function (event, methods) {
		// reset
		$('#method')
		.disable().val('').html('blast');

		$('#methods')
		.removeClass('input-group')
		.children().not('#method').remove();

		// set
		if (methods.length > 0) {
			var method = methods.shift();

			$('#method')
			.enable().val(method).html(SS.decorate(method));

			if (methods.length >=1) {
				$('#methods')
				.addClass('input-group')
				.append
				(
					$('<div/>')
					.addClass('input-group-btn')
					.append
					(
						$('<button/>')
						.attr('type', 'button')
						.addClass("btn btn-primary dropdown-toggle")
						.attr('data-toggle', 'dropdown')
						.append
						(
							$('<span/>')
							.addClass('caret')
						),
						$('<ul/>')
						.addClass('dropdown-menu dropdown-menu-right')
						.append
						(
							$.map(methods, function (method) {
								return $('<li/>').html(SS.decorate(method));
							})
						)
					)
				);
			}
		}
	});

	// The list of possible blast methods is dynamically generated.  So we
	// leverage event bubbling and delegation to trap 'click' event on the list items.
	// Please see : http://api.jquery.com/on/#direct-and-delegated-events
	$(document).on("click", "#methods .dropdown-menu li", function(event) {
		var clicked = $(this);
		var mbutton = $('#method');
		var old_method = mbutton.text();
		var new_method = clicked.text();

		// swap
		clicked.html(SS.decorate(old_method));
		mbutton.val(new_method).html(SS.decorate(new_method));
	});

	$('.result').on('click', '.view-sequence', function (event) {
		event.preventDefault();
		event.stopPropagation();
		if (!event.target.disabled) {
			var $clicked = $(event.target);
			var url = $clicked.attr('href');

			globalFun.modal.open({
				'title': 'Retrieving sequence information',
				'content': '<div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div>',
				'class': 'sequence-viewer'
			});

			$.getJSON(url)
			.done(function (response) {
				var content = $.map(response.sequences, function(seq,idx) {
					return '<h3>'+seq.id+' <span>'+seq.title+'</span></h3><div class="sequence-value sequence-value-'+(idx+1)+'" data-seq-val="'+seq.value+'"></div>';
				});
				globalFun.modal.update({
					'title': 'Retrieved sequence information',
					'content': '<ul class="sequence-list"><li>'+content.join('</li><li>')+'</li></ul>'
				});

				// Do sequence viewer
				$('#modal ul.sequence-list li').each(function(i) {
					var seq = new Sequence($(this).find('div.sequence-value').attr('data-seq-val'));
					seq.render('.sequence-value-'+(i+1), {

					});
				})
			})
			.fail(function (jqXHR, status, error) {
				globalFun.modal.update({
					'title': 'Oh blimey!',
					'content': 'We have encountered an error when attempting to retrieve your BLAST query. Should this issue exist, please contact the system administrator.'
				});
			});
		}
	});

	$(document).on('change', '.hitn :checkbox', function (event) {
		event.stopPropagation();
		SS.selectHit(this);
	});

	$('#blast').submit(function(){
		//parse AJAX URL
		var action = '#result';
		var index  = action.indexOf('#');
		var url	= action.slice(0, index);
		var hash   = action.slice(index, action.length);

		// reset hash so we can always _jump_ back to result
		//location.hash = '';

		// Edit: Show activity spinner
		globalFun.modal.open({
			'title': 'BLASTing in progress',
			'content': "<div class='loader'><svg><circle class='path' cx='40' cy='40' r='30' /></svg></div><p>Please wait while we fetch the results from BLAST. Waiting time varies based on the length of your query and the number of database(s) you have selected.</p>",
			'allowClose': false
		});

		// BLAST now
		var data = $(this).serialize() + '&method=' + $('#method').val();
		$.post(url, data).
		  done(function (data) {
			// display the result
			$('#result').html(data).show();

			//jump to the results
			location.hash = hash;

			SS.generateGraphicalOverview();

			SS.updateDownloadFastaOfAllLink();
			SS.updateDownloadFastaOfSelectedLink();
			SS.updateSequenceViewerLinks();
			SS.setupTooltips();
			SS.setupDownloadLinks();

			// Edit: Hide activity spinner
			globalFun.modal.close();

			// Edit: Scroll to results
			$("html, body").animate({
				scrollTop: $('#result').offset().top - ($('#skip.is-sticky').outerHeight() + 24)
			});

			// Edit: Make aside sticky
			$('#result aside.sticky').sticky();

			// BLASTed successfully
			$('#blast').trigger('blastdone.SS');

		}).
		  fail(function (jqXHR, status, error) {
			// Edit: Deal with error
			// Get error HTML
			globalFun.modal.update({
				'title': 'Oh blimey!',
				'content': '<p>We have encountered a '+jqXHR.status+' '+jqXHR.statusText+' when attempting to perform your BLAST request. If this issue persist, please contact the system administrator.</p><pre><code>'+$(jqXHR.responseText).find('pre.pre-scrollable').html()+'</code></pre>',
				'allowClose': true
			})
		});

		return false;
	});

	SS.$sequence.poll();

	// Remove hash in URL
	if (window.location.href.indexOf("#") > -1) {
		window.location.hash = '';
	}

	// jQuery UI tabs for BLAST databases
	$('#database-list').tabs();

	$('#database-list button').on('click', function() {
		$('#database-list input[type="checkbox"]').prop('checked', false).trigger('change');
	});


	// Check if user is logged in
	if(!$.isEmptyObject(globalFun.getJWT())) {
		var jwt = globalFun.getJWT(),
			fname = jwt.data.FirstName ? jwt.data.FirstName : 'user';

		if(window.location.href.split("#")[0] !== 'https://lotus.au.dk/blast-carb/') {
			$('#blast-alt-available').removeClass('hidden');
		}

		$('#header nav ul li[data-group="meta"]').after([
			'<li class="h-user" data-group="user">',
				'<a href="/users/" title="Your dashboard"><span class="icon-user">Hi, '+fname+'</span></a><ul>',
				'<li><a href="/users/" title="Dashboard">Dashboard</a></li>',
				'<li><a href="/users/account" title="View your account details">Account</a></li>',
				'<li><a href="/users/data" title="View data generated from your account">Data</a></li>',
				'<li><a href="/users/logout?redir='+encodeURIComponent(window.location.href)+'" title="Logout">Logout</a></li>',
			'</ul></li>'].join(''));
	} else {

		$('#blast-alt').removeClass('hidden');

		$('#header nav ul li[data-group="meta"]').after([
			'<li class="h-user" data-group="user">',
				'<a href="/users/login?redir='+encodeURIComponent(window.location.href)+'" title="Login for existing Lotus Base users">Login</a><ul>',
				'<li><a href="/users/login?redir='+encodeURIComponent(window.location.href)+'" title="Login for existing Lotus Base users">Login</a></li>',
				'<li><a href="/users/register" title="Register for a user account on Lotus Base">Register</a></li>',
				'<li><a href="/users/reset" title="Reset password">Forgot password?</a></li>',
			'</ul></li>'].join(''));
	}
});
