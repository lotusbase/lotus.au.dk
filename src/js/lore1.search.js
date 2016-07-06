$(function() {
	//==============================================================//
	// Search form functions										//
	//==============================================================//
	var minAnnoChar = 15,
		isEmpty = function() {
			var isEmpty, inArr = [];
			$('#searchform__lore1 .search-param').each(function() {
				inArr.push($(this).val());
			});
			var filledFields = $('#searchform__lore1 .search-param').filter(function() {
				return $(this).val() !== '';
			});
			if(filledFields.length > 0) {
				isEmpty = false;
			} else {
				isEmpty = true;
			}
			return isEmpty;
		};

	// Use select2 for dropdowns
	$('#searchform__lore1 select, #filetype').select2();

	// Regex validation LORE1 lines
	$('#searchform__lore1 .field__plant-id ul.input-values').on('update', function() {
		$(this).find('li[data-input-value]').each(function() {
			var pid = $(this).attr('data-input-value');
			if(!/^(DK\d{2}-0)?3\d{7}$/gi.test(pid)) {
				$(this).addClass('warning');
			}
		});
	});

	globalVar.lore1 = {};
	globalFun.lore1 = {
		searchForm: {
			init: function() {
				// Validate LORE1 searchform
				globalVar.lore1.searchForm = {};
				globalVar.lore1.searchForm.validator = $('#searchform__lore1').validate({
					rules: {
						v: 'required',
						anno: { required: function(element) { return isEmpty(); }, minlength: minAnnoChar }
					},
					messages: {
						v: 'Please select a database before proceeding.',
						blast: { required: false },
						pid: { required: false },
						chr: { required: false },
						pos1: { required: false },
						pos2: { required: false },
						gene: { required: false },
						anno: {
							minlength: function() {
								return ['Please enter at least '+minAnnoChar+' characters. You have '+(minAnnoChar-parseInt($('#anno').val().length))+' more to go.'];
							},
							required: false
						}
					},
					errorPlacement: function(error, element) {
						var $e = element;
						if($e.attr('id') === 'genome-version') {
							$e.siblings('.select2')
								.addClass('error')
								.after(error);
						} else if($e.hasClass('input-hidden')) {
							$e.parent('div').parent().append(error);
						} else {
							error.insertAfter(element);
						}
					}
				});

				// Listen to change events on BLAST header
				$('#blastheader').on('change', function() {
					if($(this).val()) {
						$('#lore1-search__filtering').css('opacity', 0.5).find(':input').addClass('disabled').prop('disabled', true);
					} else {
						$('#lore1-search__filtering').css('opacity', 1).find(':input').removeClass('disabled').prop('disabled', false);
					}
				});

				// Function: Generate line search summary
				if($b.attr('data-line-search') == 'true') {
					$('.ins-overview').find('div ul li span.count').each(function() {
						if($(this).parent().attr('data-instype')) {
							$(this).text($('#rows').find('td[data-instype='+$(this).parent().attr('data-instype')+']').length);
						}
					});
				}
			}
		}
	};

	globalFun.lore1.searchForm.init();
});