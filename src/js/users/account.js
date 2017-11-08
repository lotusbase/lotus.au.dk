$(function() {
	// Expose global variable
	globalFun.users = {};
	
	// jQuery UI tabs
	var $accountTabs = $('#account-tabs').tabs();

	//----------//
	// OAuth2.0 //
	//----------//
	$('#oauth-list input.oauth__toggle').on('change', function() {
		var $t = $(this),
			$f = $t.closest('form');

		// Disable all inputs first
		$f.find('input.oauth__toggle').prop('disabled', true);

		// Are we connecting or disconnecting?
		if($t.prop('checked')) {
			window.location = $t.attr('data-oauth-url');
		} else {
			$.ajax({
				url: root + '/api/v1/users/' + $f.find(':input[name="salt"]').val() + '/oauth',
				type: 'PUT',
				dataType: 'json',
				data: {
					provider: $t.val()
				}
			})
			.done(function(r) {
				// Re-enable inputs
				$f.find('input.oauth__toggle').prop('disabled', false);
			})
			.fail(function(jqXHR) {
				// Re-enable inputs
				$f.find('input.oauth__toggle').prop('disabled', false).prop('checked', true);
	
				// Display error message
				var r = jqXHR.responseJSON;
				globalFun.modal.open({
					'title': 'Whoops!',
					'content': '<p>'+(r.message ? r.message : 'We have encountered an unspecified error when attempting to disconnect your account.')+'</p>',
					'class': 'warning'
				});
			});
		}
	});

	//------------------------//
	// Newsletter subscripton //
	//------------------------//
	$('#newsletter-subscription input.subscription__toggle').on('change', function() {
		var $t = $(this),
			$f = $t.closest('form');

		// Disable all inputs first
		$f.find('input.subscription__toggle').prop('disabled', true);

		// Are we subscribing or unsubscribing
		var subscriptionAJAX;
		if($t.prop('checked')) {
			subscriptionAJAX = $.ajax({
				url: root + '/api/v1/users/' + $f.find(':input[name="salt"]').val() + '/subscription',
				type: 'POST',
				dataType: 'json',
				data: {
					provider: $t.attr('data-provider'),
					list: {
						id: $t.val(),
						name: $t.attr('data-list-name')
					},
					email: $t.attr('data-email')
				}
			});
		} else {
			subscriptionAJAX = $.ajax({
				url: root + '/api/v1/users/' + $f.find(':input[name="salt"]').val() + '/subscription',
				type: 'DELETE',
				dataType: 'json',
				data: {
					provider: $t.attr('data-provider'),
					list: {
						id: $t.val(),
						name: $t.attr('data-list-name')
					},
					email: $t.attr('data-email')
				}
			});
		}
		
		subscriptionAJAX.done(function(r) {
			// Re-enable inputs
			$f.find('input.subscription__toggle').prop('disabled', false);
		})
		.fail(function(jqXHR) {
			// Re-enable inputs
			$f.find('input.subscription__toggle').prop('disabled', false).prop('checked', !$t.prop('checked'));

			// Display error message
			var r = jqXHR.responseJSON;
			globalFun.modal.open({
				'title': 'Whoops!',
				'content': '<p>'+(r.message ? r.message : 'We have encountered an unspecified error when attempting to disconnect your account.')+'</p>',
				'class': 'warning'
			});
		});
	});

	//----------------//
	// Update profile //
	//----------------//
	globalFun.users.profile = {
		validator: $('#update-profile')
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
							beforeSend: function(xhr) {
								xhr.setRequestHeader('X-API-KEY', access_token);
								if(globalVar.cookies.get('auth_token')) {
									xhr.setRequestHeader('Authorization', 'Bearer '+globalVar.cookies.get('auth_token'));
								}
							},
							data: {
								field: 'username',
								value: function() {
									return $('#username').val();
								},
								ignoreCurrent: true
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
							beforeSend: function(xhr) {
								xhr.setRequestHeader('X-API-KEY', access_token);
								if(globalVar.cookies.get('auth_token')) {
									xhr.setRequestHeader('Authorization', 'Bearer '+globalVar.cookies.get('auth_token'));
								}
							},
							data: {
								field: 'email',
								value: function() {
									return $('#email').val();
								},
								ignoreCurrent: true
							}
						}
					}
				},
				messages: {
					username: {
						remote: 'Username is already taken.'
					},
					email: {
						remote: 'Email is already taken.'
					}
				},
				submitHandler: function(form) {
					var $t = $(form);

					// Open modal
					globalFun.modal.open({
						'title': 'Updating your profile&hellip;',
						'content': '<div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div>',
						'allowClose': false
					});

					$.ajax({
						url: root + '/api/v1/users/' + $(form).find(':input[name="salt"]').val() + '/profile',
						type: 'PUT',
						dataType: 'json',
						data: $t.serialize()
					})
					.done(function(r) {
						globalFun.modal.open({
							'title': '<span class="icon-ok-circled icon--big">'+(r && r.data ? r.title : 'Profile unmodified')+'</span>',
							'content': '<p>'+(r && r.data ? r.message : 'The data you have provided matches exactly with what we have on record, and no further action has taken place.')+'</p>',
						});

						// Update profile fields
						if(r && r.data) {
							$.each(r.data, function(k,v) {
								$(form).find(':input[name="'+k+'"]').val(v);
							});
						}
					})
					.fail(function(jqXHR) {
						var r = jqXHR.responseJSON;
						globalFun.modal.open({
							'title': 'Whoops!',
							'content': '<p>'+(r.message ? r.message : 'We have encountered an unspecified error when attempting to update your profile.')+'</p>',
							'class': 'warning'
						});
					});
				},
				onkeyup: false
			})
	};
	// Trigger validation before, to prevent the plugin attempting to validate two fields too quickly at the same time
	if($('#update-profile').length) {
		globalFun.users.profile.validator.element('[name="username"]');
		window.setTimeout(function() {
			globalFun.users.profile.validator.element('[name="email"]');
		},500);
	}

	//------------------//
	// Account deletion //
	//------------------//
	globalFun.users.deletion = {
		validator: $('#account-deletion')
			.validate({
				rules: {
					ad_pass: 'required',
					ad_consent: 'required'
				},
				messages: {
					ad_pass: 'Please authenticate with your password.',
					ad_consent: 'Please explicitly indicate your intention to delete your user account.'
				},
				errorPlacement: function(error, element) {
					var $e = element;
					if($e.attr('id') === 'ad_consent') {
						$e.closest('label').after(error);
					} else {
						error.insertAfter(element);
					}
				},
				submitHandler: function(form) {
					var $t = $(form);

					// Make AJAX call to delete user
					$.ajax({
						url: root + '/api/v1/users/' + $(form).find(':input[name="salt"]').val(),
						type: 'DELETE',
						dataType: 'json',
						data: $t.serialize()
					})
					.done(function(r) {
						globalFun.modal.open({
							'title': r.title,
							'content': '<p>'+r.message+'</p>',
							'allowClose': false
						});
						$(form)[0].reset();
						window.setTimeout(function() {
							window.location = 'logout.php';
						}, 3000);
					})
					.fail(function(jqXHR) {
						var r = jqXHR.responseJSON;
						globalFun.modal.open({
							'title': 'Whoops!',
							'content': '<p>'+(r.message ? r.message : 'We have encountered an unspecified error when attempting to delete your user account.')+'</p>',
							'class': 'warning'
						});
						$(form)[0].reset();
					});
				}
			})
	};

	//-----------------//
	// Update password //
	//-----------------//
	globalFun.users.security = {
		validator: $('#update-password')
			.validate({
				rules: {
					oldpass: 'required',
					newpass: 'required',
					newpass_rep: {
						required: true,
						equalTo : '#newpass'
					}
				},
				messages: {
					oldpass: 'Please authenticate with your current password.',
					newpass: 'Please enter a password.',
					newpass_r: 'Please enter a password that is identical to the one you have just entered.'
				},
				submitHandler: function(form) {
					var $t = $(form);

					// Open modal
					globalFun.modal.open({
						'title': 'Updating your password&hellip;',
						'content': '<div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div>'
					});

					$.ajax({
						url: root + '/api/v1/users/' + $(form).find(':input[name="salt"]').val() + '/password',
						type: 'PUT',
						dataType: 'json',
						data: $t.serialize()
					})
					.done(function(r) {
						globalFun.modal.update({
							'title': '<span class="icon-ok-circled icon--big">Password change successful</span>',
							'content': '<p>You have successfully updated your password. You will remain logged in until you choose to log off, or when your session expires.</p>',
						});
						$(form)[0].reset();
					})
					.fail(function(jqXHR) {
						var r = jqXHR.responseJSON;
						globalFun.modal.update({
							'title': 'Whoops!',
							'content': '<p>'+(r.message ? r.message : 'We have encountered an unspecified error when attempting to update your password.')+'</p>',
							'class': 'warning'
						});
						$(form)[0].reset();
					});
				}
			})
	};

	//--------//
	// Tokens //
	//--------//
	// Select all
	$d.on('focus', '.access-token', function() {
		$(this)[0].select();
	});

	// API access token: revoking
	$d.on('click', '.api-key__revoke', function(e) {
		e.preventDefault();
		
		var $t = $(this);
		$.ajax({
			url: root + '/api/v1/users/access_token/'+$t.data('token'),
			type: 'DELETE',
			dataType: 'json'
		})
		.done(function(r) {
			var d = r.data;
			$('#token-'+d.token).fadeOut(1000, function() {
				$(this).remove();
			});
		})
		.fail(function(jqXHR) {
			var r = jqXHR.responseJSON;
			globalFun.modal.open({
				'title': 'Whoops!',
				'content': '<p>'+(r.message ? r.message : 'We have encountered an unspecified error when attempting to update your profile.')+'</p>',
				'class': 'warning'
			});
		});
	});

	// API access token: generating
	$('#api-key__create').on('submit', function(e) {
		e.preventDefault();

		var auth_token = globalVar.cookies.get('auth_token'),
			$t = $(this);

		$.ajax({
			url: root + '/api/v1/users/access_token',
			type: 'POST',
			data: $t.serialize(),
			dataType: 'json'
		})
		.done(function(r) {
			var d = r.data;
			$('#api-keys tbody').append([
				'<tr id="token-'+d.token+'">',
				'<td><code class="access_token">'+d.access_token+'</code></td>',
				'<td>'+d.created+'</td>',
				'<td class="align-center">&ndash;</td>',
				'<td '+(d.comment ? '' : 'class="align-center"')+'>'+(d.comment ? d.comment : '&ndash;')+'</td>',
				'<td><a href="#" class="api-key__revoke button button--small warning" data-token="'+d.token+'"><span class="icon-cancel">Revoke</span></a></td>',
				'</tr>'
				].join(''));
		})
		.fail(function(jqXHR) {
			var r = jqXHR.responseJSON;
			globalFun.modal.open({
				'title': 'Whoops!',
				'content': '<p>'+(r.message ? r.message : 'We have encountered an unspecified error when attempting to update your profile.')+'</p>',
				'class': 'warning'
			});
		});
	});

	// DataTable for transcripts
	var $userGroupMembersTable = $('#user-group__members').DataTable({
		'pagingType': 'full_numbers',
		'dom': 'lftipr'
	});
	$userGroupMembersTable.on('search.dt', function() {
		var info = $transcriptTable.page.info(),
			totalRows = info.recordsTotal,
			filteredRows = info.recordsDisplay,
			$badge = $('#user-group__members').prev('h3').find('span.badge');

		// Update counts
		$badge.text(filteredRows);

		if(filteredRows < totalRows) {
			$badge.addClass('subset');
		} else {
			$badge.removeClass('subset');
		}
	});
});