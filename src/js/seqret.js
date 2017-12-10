$(function() {
	// Dynamic form
	var dynamicForm = function(dbselect) {
			var $opt = $(dbselect).find('option:selected'),
				giToggle = $opt.attr('data-gi-dropdown'),
				giTarget = $opt.attr('data-gi-dropdown-target'),
				$gi = $('#seqret-gi');

			if(giToggle == 1) {
				$gi.children('div, .gi-dropdown').hide().prop('disabled', true);
				$gi.find('.input-mimic .input-hidden').prop('disabled', true);
				$gi.children('#gi-' + giTarget).show().prop('disabled', false);
			} else {
				$gi.children('div').show();
				$gi.find('.input-mimic .input-hidden').prop('disabled', false);
				$gi.children('.gi-dropdown').hide().prop('disabled', true);
			}
		},
		siteCookie = globalFun.getCookie();

	dynamicForm('#seqret-db');
	$('#seqret-db')
	.change(function() {
		dynamicForm(this);
		$('#seqret-gi select').find('option').prop('selected', false);
		$('#seqret-gi input.input-hidden').val('').trigger('manualchange');
	})
	.css('width', '100%')
	.select2();

	// Generate download URL
	globalFun.seqret = {
		downloadURL: function(id) {
			return '../api/v1/blast/'+$('#seqret-db').val()+'/'+id.join(',')+'?strand='+$('#seqret-strand').val()+'&from='+$('#seqret-from').val()+'&to='+$('#seqret-to').val()+'&download'+'&access_token='+access_token;
		}
	};

	// Store selected databse
	$('#seqret-form').validate({
		ignore: [],
		rules: {
			db: 'required',
			id: {
				required: true,
				regex: /^[^;><\'\"\`:\/\\\*\?!&]+$/
			}
		},
		errorPlacement: function(error, element) {
			var $e = element;
			if($e.attr('id') === 'seqret-db') {
				$e.siblings('.select2')
					.addClass('error')
					.after(error);
			} else if($e.hasClass('input-hidden')) {
				$e.parent('div').addClass('error').parent().append(error);
			} else {
				error.insertAfter(element);
			}
		},
		submitHandler: function(form) {
			var $dbSelected = $('#seqret-db option:selected'),
				$t = $(form);

			if(parseInt($('#seqret-form-download').val()) === 1) {
				form.submit();
				return false;
			}

			if(siteCookie) {
				// Assume that cookie is already made, because user has to consent to cookie use anyway
				if(siteCookie.recentDB && siteCookie.recentDB.constructor === Array) {
					// If recentDB object is found
				} else {
					// If recentDB object is not found, create it
					siteCookie.recentDB = [];
				}

				// Construct unique array of selected databases
				var dbArray = [];
				$.each(siteCookie.recentDB, function() {
					if($.inArray($dbSelected.val(), dbArray)) {
						dbArray.push(this.dbFileName);
					}
				});

				if($.inArray($dbSelected.val(), dbArray) < 0) {
					// If the selected database is not in the database, add it
					siteCookie.recentDB.push({
						dbName:			$dbSelected.text(),
						dbFileName:		$dbSelected.val(),
						dbIndex:		$('#seqret-db')[0].selectedIndex,
						dbAccessDate:	Math.floor((new Date()).getTime() / 1000)
					});
				}
			}

			// Set cookie
			globalVar.cookies.set(
				'lotusbase',
				siteCookie,
				{ path: '/' }
			);

			// Make AJAX call
			var seqretAJAX = $.ajax({
				url: root + '/api/v1/blast/'+$('#seqret-db').val()+'/'+$('#seqret-gi').find(':input[name="id"]:enabled').val(),
				dataType: 'json',
				data: {
					from: $('#seqret-from').val(),
					to: $('#seqret-to').val(),
					strand: $('#seqret-strand').val()
				},
				type: 'GET'
			});

			// Push history state
			window.history.pushState({lotusbase: true}, '', '?'+$t.serialize());

			seqretAJAX
			.done(function(data) {
				var d = data.data;

				// Collapse form
				globalFun.collapseForm.call(form);

				// Empty results
				$('#seqret-results').empty();

				var out,
					ids = $.map(d.fasta, function(fa, i) {
						return fa.id;
					});

				
				// Display data into table
				out = '<h2>Search results</h2>';
				out += '<p>We have retrieved <strong>'+d.fasta.length+' '+globalFun.pl(d.fasta.length, 'entry', 'entries')+'</strong> from the database <strong>'+$('#seqret-db').find('option:selected').text()+'</strong>.</p><p class="align-center"><a href="'+globalFun.seqret.downloadURL(ids)+'" class="button"><span class="icon-download">Download all sequences</span></a></p>';
				out += '<ul class="fasta-rows">';
				$.each(d.fasta, function(i, fa) {
					out += '<li class="fasta-row" id="seqret-'+(i+1)+'">';
					out += '<div class="toggle">';
					out += '<h3><a href="#" class="open" data-toggled="on"><div><span class="fasta-id">'+fa.id+'</span><span class="fasta-header">'+fa.header+'</span></div><div class="fasta-number" title="Result '+(i+1)+' of '+d.fasta.length+'">'+(i+1)+' of '+d.fasta.length+'</div></a></h3>';
					out += (fa.sequence ? '<pre class="fasta-sequence"><code>'+fa.sequence.replace(/(.{10})/g, '$1 ')+'</code></pre>' : '<p class="user-message warning">No sequence returned.</p>')+'<p class="align-center"><a href="'+globalFun.seqret.downloadURL([fa.id])+'" class="button"><span class="icon-download">Download sequence for <strong>'+fa.id+'</strong></span></a></p>';
					out += '</div></li>';
				});
				out += '</ul>';

				$('#seqret-results').append(out).show();
			})
			.fail(function(jqXHR, textError, status) {
				var d = jqXHR.responseJSON;

				// Collapse form
				globalFun.collapseForm.call(form);

				// Empty results
				$('#seqret-results').empty();

				if(d.status === 400) {
					var out,
						ids = $('#seqret-gi :input[name="id"]:enabled').val().split(',');

					// Display download link
					out = '<h2>Search results</h2><p>As you have queried against a genomic database and the expected returned query is too large, you can only download your results.</p><p class="align-center"><a href="'+globalFun.seqret.downloadURL([ids])+'" class="button"><span class="icon-download">Download sequence(s)</span></a></p>';
					$('#seqret-results').append(out).show();

				} else {
					globalFun.modal.open({
						'title': 'Whoops!',
						'content': '<p>We have encountered an error when attemtping to retrieve the sequence.' + (d.message ? ' '+d.message : '') +'</p>',
						'class': 'warning'
					});
				}
			});
		}
	});

	$(document).on('click', '#seqret-download-all', function() {
		// Set to download
		$('#seqret-form-download').val(1);

		// Make AJAX call
		$('#seqret-form').trigger('submit');
	});

	var params = globalFun.parseURLquery();
	if(params.db && params.id) {
		$('#seqret-form').trigger('submit');
	}
});