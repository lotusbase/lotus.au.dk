$(function() {

	// Global variables
	globalVar.stepForm = {};
	globalVar.map = {};

	// Extend
	globalFun.stepForm = {
		init: function() {
			// Initiate navigation
			globalFun.stepForm.navigation.init();
			
			// Declare clearance count
			$('form.has-steps .form-step').data('step-cleared-count', 0);

			// Fetch data from local storage
			if(typeof window.localStorage !== typeof undefined && window.localStorage.length) {
				globalFun.stepForm.loadData();
				globalFun.stepForm.update.overview();
			} else {
				globalFun.stepForm.step.activate(0);
			}

			// Prev/next navigation
			$('#form-step__nav-bottom').on('click', 'a.button', function(e) {
				e.preventDefault();

				// Storage current facet data in HTML5 webstorage
				var currentIndex = globalVar.stepForm.currentIndex;
				globalFun.stepForm.step.storeData(currentIndex);

				if($(this).data('button-status') !== 'disabled') {
					if($(this).data('direction') === 'prev') {
						// Navigate to previous step
						globalFun.stepForm.step.navigate(currentIndex - 1);

					} else if($(this).data('direction') === 'next') {

						// Activate next step
						globalFun.stepForm.step.navigate(currentIndex + 1);

						// Clear current step
						globalFun.stepForm.step.clear(currentIndex);

						// Reset next button
						$(this).addClass('disabled').data('button-status', 'disabled');
					}
				}

				// Scroll to top
				globalFun.smoothScroll('#order-form');
			});

			// User wants to delete local storage
			$d.on('click', '#reset-local-storage', function() {
				window.localStorage.clear();
				document.location.reload(false);
			});

			// Listen to click event on form navigation
			$('#form-step__nav').on('click', 'li.form-step-nav a', function(e) {
				e.preventDefault();

				// Storage current facet data in HTML5 webstorage
				var currentIndex = globalVar.stepForm.currentIndex;
				globalFun.stepForm.step.storeData(currentIndex);

				// Navigate
				if(['enabled', 'valid', 'invalid'].indexOf($(this).closest('li').data('step-status')) > -1) {
					globalFun.stepForm.step.navigate(parseInt($(this).data('target-step')) - 1);
				}
			});

			// Basic validation
			$('form.has-steps .form-step :input')
			.not('.validate--ignore')
			.on('keyup change input manualValidation', $.throttle(500, function() {
				globalFun.stepForm.step.validateFields.call(this);
			}))
			.on('blur manualOverviewUpdate', globalFun.stepForm.update.overview);

			// Trigger validation on input mimic
			$d.on('change manualValidation', '.input-mimic .input-hidden', function() {
				globalFun.stepForm.step.validateFields.call(this);
			});

			// Create map
			globalFun.stepForm.update.map();
		},
		loadData: function() {
			// Insert values
			for (var i = 0; i < window.localStorage.length; i++) {
				var id = window.localStorage.key(i),
					value = window.localStorage.getItem(id);
				
				if(value) {
					if(/^[^_]/g.test(id)) {
						$('#' + id).val(value);
					} else if(/^_si_/g.test(id)) {

					} else if(/^_scc_/g.test(id)) {
						$('#'+id.substr(5)).data('step-cleared-count', parseInt(value));
					}
				}
			}

			// Trigger input mimic change
			$('#lines').trigger('manualchange');

			// Navigate to last active step
			var currentStep = parseInt(window.localStorage.getItem('_si_currentStepIndex'));
			globalVar.stepForm.currentIndex = currentStep;
			globalFun.stepForm.step.navigate(currentStep);

			// Validate known steps
			var validStep = Math.max(parseInt(window.localStorage.getItem('_si_validStepIndex')),2);
			globalVar.stepForm.validStepIndex = validStep;

			$('form.has-steps .form-step')
			.slice(0, validStep+1)
				.removeClass('disabled form-step--disabled')
				.addClass('form-step--enabled form-step--valid')
				.data('step-status', 'valid');

			$('#form-step__nav ul li')
			.slice(0, validStep+1)
				.removeClass('disabled form-step-nav--disabled')
				.addClass('form-step-nav--enabled')
				.each(function(i) {
					globalFun.stepForm.navigation.validate(i);
				});

			// Enable next button
			if(validStep <= currentStep && validStep <= 2) {
				$('#form-step__next').removeClass('disabled').data('button-status', 'enabled');
			}

			// Indicate to user that form is currently using stored data
			$('#form-step__nav').before('<div class="user-message note">We have saved the progress of your incomplete LORE1 order. <button id="reset-local-storage" type="button" class="button"><span class="icon-cancel">Remove stored data and repeat</span></button></div>');
		},
		step: {
			activate: function(i) {
				var $step = $('form.has-steps .form-step').eq(i);

				// Set classes
				$step
				.removeClass('form-step--disabled')
				.addClass('form-step--active')
				.data('step-status', 'active');

				// Update global variable
				globalVar.stepForm.currentIndex = i;

				// Update navigation
				globalFun.stepForm.navigation.activate(i);

				// Update display
				$('#form-step__title').text($step.data('form-step-title'));

				// Update prev/next buttons
				globalFun.stepForm.prevnext.update(i);
			},
			clear: function(i) {
				var $step = $('form.has-steps .form-step').eq(i);

				// Set classes
				$step.removeClass('form-step--active').data({
					'step-status': 'valid',
					'step-cleared-count': parseInt($step.data('step-cleared-count')) + 1
				});

				// Update cleared count
				if(typeof window.localStorage !== typeof undefined) {
					window.localStorage.setItem('_scc_' + $step.attr('id'), $step.data('step-cleared-count'));
				}

				// Clear navigation
				globalFun.stepForm.navigation.clear(i);
			},
			navigate: function(i) {
				// Blur all other steps
				$('form.has-steps .form-step, #form-step__nav ul li').each(function() {
					if(/form-step(-nav)?--active/gi.test($(this).attr('class'))) {
						$(this).removeClass(function(i,v) {
							return (v.match (/(^|\s)form-step(-nav)?--active+/g) || []).join(' ');
						}).data('step-status', 'enabled');
					}
				});

				// Activate step of choice and then check it
				globalFun.stepForm.step.activate(i);

				// Perform validation
				if($('form.has-steps .form-step').eq(i).data('step-cleared-count') > 0) {
					$('form.has-steps .form-step').eq(i).find(':input').not('.validate--ignore').trigger('manualValidation');
				}

				// Store current index
				if(typeof window.localStorage !== typeof undefined) {
					// Set current step
					window.localStorage.setItem('_si_currentStepIndex', i);
				}

			},
			validateFields: function() {
				var $t = $(this),
					$currentStep = $(this).closest('.form-step'),
					$allInputs = $currentStep.find(':input[required]').not('.validate--ignore'),
					allInputs = $allInputs.length,
					validInputs = 0;

				$('#form-step__next').addClass('disabled').data('button-status', 'disabled');

				// Validate all fields in current step
				if(allInputs) {
					$allInputs.map(function() {
						if(_validator.check(':input[name="'+$(this).attr('name')+'"]') === true) {
							validInputs += 1;
						}
					});

					if(allInputs === validInputs) {
						// Validate this step
						globalFun.stepForm.step.validate($currentStep);
					} else {
						// Invalidate this step
						globalFun.stepForm.step.invalidate($currentStep);
					}
				}
			},
			validate: function($currentStep) {

				// Allow user to navigate forward
				$('#form-step__next').removeClass('disabled').data('button-status', 'enabled');

				// Mark current step as valid
				$currentStep.removeClass('form-step--invalid').addClass('form-step--valid').data('step-status', 'valid');

				// Mark navigation as valid
				globalFun.stepForm.navigation.validate(globalVar.stepForm.currentIndex);

				// Update valid step index
				globalVar.stepForm.validStepIndex = globalVar.stepForm.currentIndex;

			},
			invalidate: function($currentStep) {
				// If user is filling in the first time, don't pester them
				if($currentStep.data('step-cleared-count') > 0) {

					// Mark current step as invalid
					$currentStep.removeClass('form-step--valid').addClass('form-step--invalid').data('step-status', 'invalid');

					// Invalidate navigation
					globalFun.stepForm.navigation.invalidate(globalVar.stepForm.currentIndex);
				}

				// Disable navigation
				$('#form-step__next').addClass('disabled').data('button-status', 'disabled');
			},
			storeData: function(i) {
				if(typeof window.localStorage !== typeof undefined) {
					$('form.has-steps .form-step').each(function() {
						if($(this).data('step-status') === 'valid') {
							$(this).find(':input[name]').each(function() {
								var $t = $(this);
								window.localStorage.setItem($t.attr('id'), $t.val());
							});
						}
					});

					// Set last valid step
					window.localStorage.setItem('_si_validStepIndex', globalVar.stepForm.validStepIndex);
				}
			}
		},
		navigation: {
			init: function() {
				var $nav = $('#form-step__nav ul');
				$('form.has-steps .form-step').each(function(i) {
					var formStepTitle = $(this).data('form-step-title'),
						formStepTitleShort = $(this).data('form-step-title-short'),
						content = $('<li class="form-step-nav form-step-nav--disabled disabled"><a href="#form-step__step-'+(i+1)+'" data-target-step="'+(i+1)+'"><span class="step-label step-status">Step '+(i+1)+'</span><span class="step-desc">'+(typeof formStepTitleShort === typeof undefined ? formStepTitle : formStepTitleShort)+'</span></a></li>');
					$nav.append(content);
				});
			},
			activate: function(i) {
				var $nav = $('#form-step__nav ul');
				$nav.find('li').eq(i)
				.removeClass('form-step-nav--disabled disabled')
				.addClass('form-step-nav--active form-step-nav--enabled')
				.data('step-status', 'enabled');
			},
			clear: function(i) {
				$('#form-step__nav ul li').eq(i)
				.removeClass('form-step-nav--active')
				.data('step-status', 'valid')
					.find('span.step-status')
						.removeClass(function(i,v) {
							return (v.match (/(^|\s)icon-\S+/g) || []).join(' ');
						})
						.addClass('icon-ok-circled');
			},
			invalidate: function(i) {
				// Invaliate a step in navigation
				$('#form-step__nav ul li').eq(i)
					.removeClass('form-step-nav--valid')
					.addClass('form-step-nav--invalid')
					.data('step-status', 'valid')
					.find('span.step-status')
						.removeClass(function(i,v) {
							return (v.match (/(^|\s)icon-\S+/g) || []).join(' ');
						})
						.addClass('icon-attention-1');
			},
			validate: function(i) {
				$('#form-step__nav ul li').eq(i)
				.removeClass('form-step-nav--invalid')
				.addClass('form-step-nav--valid')
				.data('step-status', 'valid')
					.find('span.step-status')
						.removeClass(function(i,v) {
							return (v.match (/(^|\s)icon-\S+/g) || []).join(' ');
						})
						.addClass('icon-ok-circled');
			}
		},
		prevnext: {
			update: function(i) {
				// If is on first step, hide the previous button
				if(i === 0) {
					$('#form-step__prev').addClass('disabled').data('button-status', 'disabled').hide();
				} else {
					$('#form-step__prev').removeClass('disabled').data('button-status', 'enabled').show();
				}

				// Deactivate next button by default
				$('#form-step__next').addClass('disabled').data('button-status', 'disabled').show();

				// If is on last step, hide the next button
				if(i === $('form.has-steps .form-step').length - 1) {
					$('#form-step__next').hide();
				}

				
			}
		},
		update: {
			lore1lines: function() {
				// LORE1 lines
				// Show lines
				var lines = $('#lines').val().split(',');
				$('#order-overview__lore1-lines-count').html('<strong>' + lines.length + '</strong> LORE1 ' + globalFun.pl(lines.length, 'line', 'lines'));
				$('#order-overview__lore1-lines-list').empty();
				$.each(lines, function(i,v) {
					$('#order-overview__lore1-lines-list').append($('<li />').text(v));
				});

				// Compute costs
				var linesCost = lines.length*100,
					totalCost = linesCost + 100;
				$('#order-overview__lore1-lines-cost').html(lines.length+ ' &times; 100 = DKK '+linesCost.toFixed(2));
				$('#order-overview__lore1-lines-total').html('DKK '+totalCost.toFixed(2));
			},
			map: function() {
				// Map
				var accessToken = 'pk.eyJ1IjoibG90dXNiYXNlIiwiYSI6ImNpaGZjaXR3cDBsc2t0dGx6ZjV4NjdiamEifQ.tZYfphcXXFL17KLWHMppQQ';

				// Forward geocoding
				var city = $('#shipping-city').val(),
					state = $('#shipping-state').val(),
					country_a2 = $('#shipping-country option:selected').attr('data-country-alpha2'),
					query = [city, state];

				// Hide marker
				$('#order-overview__map .tooltip').removeClass('show');

				if (city && country_a2) {
					var places = $.get('https://api.mapbox.com/geocoding/v5/mapbox.places/'+encodeURIComponent(query.join(' ').replace(/\s{2,}/gi, '').trim())+'.json?country='+country_a2.toLowerCase()+'&access_token='+accessToken);
					places.done(function(d) {
						if (d.features.length) {
							var f = d.features[0];

							// Load map
							$('#order-overview__map').css({
								'background-image': 'url("https://api.mapbox.com/v4/lotusbase.o9e761mh/'+f.geometry.coordinates[0]+','+f.geometry.coordinates[1]+',6/1280x1280.png?access_token='+accessToken+'")'
								//'background-image': 'url("https://api.mapbox.com/v4/lotusbase.o9e761mh/pin-l-circle+4a7298('+f.geometry.coordinates[0]+','+f.geometry.coordinates[1]+',6)/'+f.geometry.coordinates[0]+','+f.geometry.coordinates[1]+',6/1280x1280.png?access_token='+accessToken+'")'
							}).find('.tooltip').addClass('show');
						}
					});
				}
			},
			overview: function() {
				// Shipping and contact details
				var shippingDetails = '<p class="fn"><span>' + $('#fname').val() + ' ' + $('#lname').val() + '</span></p><p class="adr"><span class="street-address">' + $('#shipping-address').val() + '</span><br /><span class="city">' + $('#shipping-city').val() + '</span>, ' + ($('#shipping-state').val() ? '<span class="region">' + $('#shipping-state').val() + '</span><br />' : '') + '<span class="postal-code">' + $('#shipping-postalcode').val() + '</span><br /><span class="country-name">' + $('#shipping-country option:selected').attr('data-country-name') + '</span></p>';
				$('#order-overview__shipping .vcard').html(shippingDetails);

				globalFun.stepForm.update.lore1lines();

				// Create map
				globalFun.stepForm.update.map();
			}
		}
	};

	// Step form
	globalFun.stepForm.init();

	// Order validation of entered Plant ID
	$('#id-check').hide();
	$('#lines').on('change manualchange', function() {
		var $t = $(this),
			$currentStep = $(this).closest('.form-step');

		// Remove error class if any
		$t.closest('.input-mimic').removeClass('error');

		// Execute AJAX call
		if($t.val()) {
			var query = 'q=' + encodeURIComponent($t.val()) + '&t=1',
				linesCheck = $.ajax({
					url: root + '/api',
					type: 'GET',
					data: query,
					dataType: 'json'
				});

			linesCheck.done(function(data) {
				var pidList; 
				if(data.error && data.status === 404) {
					// Incorrect Plant ID is found
					$('#id-check').removeClass().addClass('warning').slideDown(125);
					var pids = data.data.pid;
					if(pids) {
						var line_s = (pids.length)==1 ? 'line' : 'lines';
						pidlist = "<p>" + pids.length + " invalid LORE1 " + line_s + " found&mdash;lines are invalid or have depleted seed stock. See highlighted.</p>";

						$.each(pids, function(idx,pid){
							$('#lines').prev('ul.input-values').find('li').filter(function() {
								return $(this).data('input-value') == pid;
							}).addClass('user-message warning')
							.closest('.input-mimic')
							.addClass('error');
						});	
					} else {
						pidlist = '<p><span class="pictogram icon-cancel"></span>None of your lines are valid.</p>';
					}
					$('#id-check').html(pidlist);

					// Mark step as invalid
					globalFun.stepForm.step.invalidate($currentStep);

				} else if(data.status === 200) {
					// Everything is okay
					$('#id-check')
					.removeClass()
					.addClass('approved')
					.html('<p><span class="pictogram icon-check"></span>Your LORE1 '+globalFun.pl(data.data.pid.length, 'line is', 'lines are')+' available and valid.</p>')
					.slideDown(125);

					globalFun.stepForm.update.lore1lines();

					// Write to storage
					if(typeof window.localStorage !== typeof undefined) {
						window.localStorage.setItem('lines', data.data.pid.join(','));
					}

					// Mark step as valid
					globalFun.stepForm.step.validate($currentStep);
				
				} else {
					//
					$('#id-check')
					.removeClass()
					.addClass('warning')
					.html('<p><span class="pictogram icon-cancel"></span>We have a problem contacting the database. Please contact system administrator should this problem persists.</p>')
					.slideDown(125);
				}
			});
		} else {
			$('#id-check').slideUp(125);

			// Invalidate step
			globalFun.stepForm.navigation.invalidate($t.closest('.form-step').index() - 1);
		}
	});


	// Payment
//	var handler = StripeCheckout.configure({
//		key: 'pk_test_joxAG926qmdjVR7LchnCvq6V',
//		image: '/dist/images/branding/logo-256x256.png',
//		locale: 'auto',
//		token: function(token) {
//			// When token is successfully returned
//			// Store token object
//			$('#token').val(JSON.stringify(token));
//
//			// Disable payment button
//			$('#payment')
//			.prop('disabled', true)
//			.data({
//				'button-status': 'disabled',
//				'payment-status': 1
//			})
//			.addClass('token-success')
//			.html('Payment successful');
//
//			// Submit form
//			$('#order-form').submit();
//
//			// Allow user to proceed to next step
//			var currentIndex = parseInt($('#payment').closest('.form-step.form-step--active').data('form-step')) - 1;
//			$('#form-step__next').removeClass('disabled');
//		}
//	});

//	$('#payment').on('click', function(e) {
//		e.preventDefault();
//
//		var $t = $(this);
//
//		if(!$t.data('payment-status') || parseInt($t.data('payment-status')) !== 1) {
//
//			// Set up varialbes
//			var quantity = parseInt($t.data('quantity'));
//			if(isNaN(quantity)) {
//				return false;
//			}
//
//			// Open Checkout with further options
//			handler.open({
//				name: 'LORE1 order payment',
//				description: 'Payment for '+quantity+' '+globalFun.pl(quantity, 'line', 'lines'),
//				currency: "dkk",
//				amount: quantity * globalVar.linePrice,
//				email: $('#email').val()
//			});
//		}
//	});

//	// Close Checkout on page navigation
//	$w.on('popstate', function() {
//		handler.close();
//	});

	// Form validation
	var _validator = $('#order-form').validate({
		rules: {
			fname: 'required',
			lname: 'required',
			email: {
				required: true,
				email: true
			},
			lines: 'required',
			billing_name: 'required',
			billing_address: 'required',
			billing_city: 'required',
			billing_postalcode: 'required',
			billing_country: 'required',
			shipping_institution: 'required',
			shipping_address: 'required',
			shipping_city: 'required',
			shipping_postalcode: 'required',
			shipping_country: 'required',
			consent_disclaimer: 'required',
		},
		ignore: '.validate--ignore',
		errorElement: 'label',
		errorPlacement: function(error, element) {
			if(element.attr('type') === 'checkbox') {
			} else {
				error.insertAfter(element);
			}
		},
		submitHandler: function(form) {
			
			var $t = $(form);

			var order = $.ajax({
				url: '/api',
				data: $t.serialize(),
				dataType: 'json',
				type: 'POST'
			});

			order
			.done(function(data) {
				globalFun.modal.open({
					'title': 'Placing your order&hellip;',
					'content': '<div class="user-message loading-message"><div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div><p class="loading-text">Submitting your order to the system. Hang on a second before we receive confirmation&hellip;</p></div></div>',
					'allowClose': false
				});
	
				var timer = window.setTimeout(function() {
					window.clearTimeout(timer);
					if(data.error) {
						if(data.status === 555) {
							globalFun.modal.update({
								'title': 'Whoops!',
								'content': '<p>'+data.message+'</p>',
								'allowClose': true,
								'class': 'warning'
							});
						} else {
							globalFun.modal.update({
								'title': 'Whoops!',
								'content': '<p>Something is not too right with the submission. Please review the error '+(data.message.length > 1 ? 'messages' : 'message')+' returned:</p><ul><li>'+data.message.join('</li><li>')+'</li></ul>',
								'allowClose': true,
								'class': 'warning'
							});
						}
					} else {
						globalFun.modal.update({
							'title': 'You\'ve got mail!',
							'content': '<p>You have successfully placed an order. We have just sent an email to you&mdash;<strong>please verify your order using the verification link provided in the email you have received.</strong></p><p>Orders that remain unverified for more than one month will be deleted from our database.</p>',
							'allowClose': true,
							'class': 'note',
							'actionText': 'Back to site',
							'actionHref': '/'
						});
					}
				}, 1000);
			})
			.error(function() {
				globalFun.modal.open({
					'title': 'Whoops!',
					'content': '<p>Our API has experienced an error, and is unfortunately unable to process your order. '+globalVar.errorMessage+'</p>',
					'class': 'warning'
				});
			});
		}
	});
});