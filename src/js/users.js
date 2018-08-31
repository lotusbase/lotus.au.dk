$(function() {

	// Expose global variable
	globalFun.users = {};

	// Registration form
	globalFun.users.registration = {
		validator: $('#registration-form')
			.validate({
				rules: {
					firstname: 'required',
					lastname: 'required',
					username: {
						required: true,
						minlength: 2,
						maxlength: 255,
						remote: {
							url: root + '/api/v1/registration',
							type: 'GET',
							data: {
								field: 'username',
								value: function() {
									return $('#username').val();
								}
							}
						}
					},
					email: {
						required: true,
						email: true,
						maxlength: 255,
						remote: {
							url: root + '/api/v1/registration',
							type: 'GET',
							data: {
								field: 'email',
								value: function() {
									return $('#email').val();
								}
							}
						}
					},
					password: 'required',
					consent: 'required'
				},
				messages: {
					consent: 'Consent is necessary to use <em>Lotus</em> base.',
					username: {
						remote: 'Username is already taken.'
					},
					email: {
						remote: 'Email is already taken.'
					}
				},
				errorElement: 'label',
				errorPlacement: function(error, element) {
					var $e = element;
					if($e.attr('id') === 'consent') {
						$e.closest('label').after(error);
					} else {
						error.insertAfter(element);
					}
				},
				submitHandler: function(form) {
					form.submit();
				},
				onkeyup: false
			}),
		validate: function() {
			if(globalFun.users.registration.validator.checkForm() && grecaptcha.getResponse()) {
				$('#registration-form').find('button[type="submit"]').prop('disabled', false);
			} else {
				$('#registration-form').find('button[type="submit"]').prop('disabled', true);
			}
		}
	};
	$('#registration-form').find(':input').change(function() {
		globalFun.users.registration.validate();
	});


	// New account from OAuth registration
	globalFun.users.oauthRegistration = {
		validator: $('#oauth-registration-form')
			.validate({
				rules: {
					consent: 'required'
				},
				messages: {
					consent: 'Consent is necessary to use <em>Lotus</em> base.',
				},
				errorElement: 'label',
				errorPlacement: function(error, element) {
					var $e = element;
					if($e.attr('id') === 'consent') {
						$e.closest('label').after(error);
					} else {
						error.insertAfter(element);
					}
				},
				submitHandler: function(form) {
					form.submit();
				},
				onkeyup: false
			}),
		validate: function() {
			if(globalFun.users.oauthRegistration.validator.checkForm()) {
				$('#oauth-registration-form').find('button[type="submit"]').prop('disabled', false);
			} else {
				$('#oauth-registration-form').find('button[type="submit"]').prop('disabled', true);
			}
		}
	};

	// Login
	var $loginTabs = $('#login-form--tabs').tabs();

	// Registration
	var $registrationTabs = $('#registration-form--tabs').tabs();

	// General function to check popstate events
	$w.on('popstate', function(e) {
		if (e.originalEvent.state && e.originalEvent.state.lotusbase) {
			var $tab = $('.ui-tabs ul.ui-tabs-nav li a[href="'+window.location.hash+'"]'),
				index = $tab.parent().index(),
				$parentTab = $tab.closest('.ui-tabs');
			$parentTab.tabs("option", "active", index);
		}
	});

	// Login form validation
	globalVar.login.form = {
		validator: $('#login-form').validate({
			ignore: [],
			rules: {
				login: { required: true },
				password: { required: true },
			}
		})
	};
	$('#login-form').on('submit', function(e) {
		// Check if grecaptcha is required
		if($('#login-form').valid() && globalVar.grecaptcha !== void 0) {
			// Reset captcha
			globalVar.grecaptcha.reset();

			// Disable submit button
			$('#expat-form__submit').prop('disabled', true);
		}
	});

	//======//
	// Data //
	//======//
	// jQuery UI tabs
	var $dataTabs = $('#data-tabs').tabs();
});