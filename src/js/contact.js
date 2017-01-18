$(function() {
	// Contact page
	// Select2
	$('#contact-form select').select2();
	$('#topic')
	.on('change', function() {
		var $o		= $(this).find('option:selected'),
			target	= $o.attr('data-target'),
			message	= parseInt($o.attr('data-message'));

		$('#contact-form__fields .contact-form__field')
		.hide()
		.find('[data-field]')
		.hide();

		if(message) {
			$('#contact-form__fields .contact-form__field[data-message="1"]').show();
		} else {
			$('#contact-form__fields .contact-form__field[data-message="0"]').show();
		}

		if(target) {
			$('#contact-form__fields .contact-form__field *[data-field]').each(function() {
				var $t = $(this),
					f = $t.attr('data-field').split(' ');

				if(f.indexOf(target) > -1) {
					$t.show();
				}
			});
		}

		if(message === 0 || isNaN(message)) {
			$('#contact-form').find(':input[type="submit"]').hide();
		} else {
			$('#contact-form').find(':input[type="submit"]').show();
		}
	})
	.trigger('change');

	// Form validation
	$('#contact-form')
	.validate({
		rules: {
			fname: 'required',
			lname: 'required',
			email: {
				required: true,
				email: true
			},
			emailver: {
				required: true,
				equalTo: '#email'
			},
			message: 'required'
		},
		errorElement: 'label',
		submitHandler: function(form) {
			
			var $t = $(form);

			var contact = $.ajax({
				url: '/api/v1/contact',
				data: $t.serialize(),
				dataType: 'json',
				type: 'POST'
			});

			contact
			.done(function(data) {
				globalFun.modal.open({
					'title': 'Sending mail&hellip;',
					'content': '<div class="user-message loading-message"><div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div><p class="loading-text">Waiting for your mail to be dispatched. Hang on a second before we receive confirmation&hellip;</p></div></div>',
					'allowClose': false
				});
	
				var timer = window.setTimeout(function() {
					window.clearTimeout(timer);
					if(data.error) {
						var message;
						if($.isArray(data.message)) {
							message = '<p>Something is not too right with the submission. Please review the error '+(data.message.length > 1 ? 'messages' : 'message')+' returned:</p><ul><li>'+data.message.join('</li><li>')+'</li></ul>';
						} else {
							message = data.message;
						}
						globalFun.modal.update({
							'title': 'Whoops!',
							'content': message,
							'allowClose': true,
							'class': 'warning',
							'actionButtons': [
								'<a class="button" href="#" data-action="close">Dismiss</a>'
							]
						});
					} else {
						globalFun.modal.update({
							'title': 'Mail sent!',
							'content': '<p>Your mail has been successfully dispatched. We promise we will get back to you as soon as possible. You will be redirected to the homepage in 3 seconds.</p>',
							'allowClose': false,
							'class': 'approved',
							'actionButtons': [
								'<a class="button" href="/">Back to site</a>'
							]
						});

						window.setTimeout(function() {
							window.location.href = '/';
						}, 3000);
					}
				}, 1000);
			})
			.fail(function() {
				globalFun.modal.open({
					'title': 'Whoops!',
					'content': '<p>Our API has experienced an error, and is unfortunately unable to send your message. Please forward your email to <a href="mailto:terry@mbg.au.dk">terry@mbg.au.dk</a>.</p>',
					'class': 'warning'
				});

				// Reset captcha
				grecaptcha.reset();
			});
		}
	});
});