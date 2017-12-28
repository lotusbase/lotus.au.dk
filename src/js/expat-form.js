$(function() {
	// Cache custom sort
	var expatCondition = $('#expat-condition').val(),
		columnShare = $('#expat-dataset option:selected').data('column-share'),
		customSort = $('#expat-sort-conditions').length,
		params = globalFun.parseURLquery();

	// Global variables
	// Data subsets
	var datasetSubset = {
		'ljgea': [
			{value: 'PlantSpecies', text: 'Plant species', sort: 'string'},
			{value: 'PlantEcotype', text: 'Plant ecotype', sort: 'string'},
			{value: 'PlantGenotype', text: 'Plant genotype', sort: 'string'},
			{value: 'Standard', text: 'Standard', sort: 'string'},
			{value: 'ExperimentalFactor', text: 'Experimental factor', sort: 'string'},
			{value: 'Age', text: 'Age (days)', sort: 'int'},
			{value: 'Inoculation', text: 'Inoculation (<abbr title="days post-inoculation">dpi</abbr>)', sort: 'int'},
			{value: 'Inocula', text: 'Inocula', sort: 'string'},
			{value: 'InoculaStrain', text: 'Inocula strain', sort: 'string-int'},
			{value: 'CultureSystem', text: 'Culture system', sort: 'string'},
			{value: 'Organ', text: 'Organ', sort: 'string'},
			{value: 'TissueType', text: 'Tissue type', sort: 'string'},
			{value: 'Comments', text: 'Comments', sort: 'string'},
			{value: 'Reference', text: 'Reference', sort: 'string'}
		],
		'rnaseq-kellys-2015': [
			{value: 'ExperimentalFactor', text: 'Experiment factor', sort: 'string'},
			{value: 'Treatment', text: 'Treatment', sort: 'string'},
			{value: 'PlantSpecies', text: 'Plant species', sort: 'string'},
			{value: 'PlantEcotype', text: 'Plant ecotype', sort: 'string'},
			{value: 'PlantGenotype', text: 'Plant genotype', sort: 'string'},
			{value: 'Age', text: 'Age at harvest (days)', sort: 'string'},
			{value: 'Inoculation', text: 'Inoculation (<abbr title="hours post-inoculation">hpi</abbr>)', sort: 'int'},
			{value: 'Inocula', text: 'Inocula', sort: 'string'},
			{value: 'Reference', text: 'Reference', sort: 'string'}
		],
		'rnaseq-giovanettim-2015': [
			{value: 'ExperimentalFactor', text: 'Experimental factor', sort: 'string'},
			{value: 'Treatment', text: 'Treatment', sort: 'string'},
			{value: 'PlantSpecies', text: 'Plant species', sort: 'string'},
			{value: 'PlantEcotype', text: 'Plant ecotype', sort: 'string'},
			{value: 'Inoculation', text: 'Inoculation (<abbr title="hours post-inoculation">hpi</abbr>)', sort: 'int'},
			{value: 'Inocula', text: 'Inocula', sort: 'string'},
			{value: 'Reference', text: 'Reference', sort: 'string'},
			{value: 'Reference', text: 'Reference', sort: 'string'}
		],
		'rnaseq-murakamie-2016': [
			{value: 'ExperimentalFactor', text: 'Experimental factor', sort: 'string'},
			{value: 'Treatment', text: 'Treatment', sort: 'string'},
			{value: 'PlantSpecies', text: 'Plant species', sort: 'string'},
			{value: 'PlantEcotype', text: 'Plant ecotype', sort: 'string'},
			{value: 'PlantGenotype', text: 'Plant genotype', sort: 'string'},
			{value: 'Age', text: 'Age (days)', sort: 'int'},
			{value: 'Inoculation', text: 'Inoculation (<abbr title="days post-inoculation">dpi</abbr>)', sort: 'int'},
			{value: 'Inocula', text: 'Inocula', sort: 'string'},
			{value: 'Reference', text: 'Reference', sort: 'string'}
		],
		'rnaseq-handay-2015': [
			{value: 'Treatment', text: 'Treatment', sort: 'string'},
			{value: 'Inocula', text: 'Inocula', sort: 'string'},
			{value: 'Strain', text: 'Strain', sort: 'string'},
			{value: 'InoculationPressure', text: 'Inoculation pressure', sort: 'string'},
			{value: 'SoilNutrientStatus', text: 'Soiul nutrient status', sort: 'string'},
			{value: 'TimeUnit', text: 'Time unit', sort: 'string'},
			{value: 'TimeDuration', text: 'Duration', sort: 'int'},
			{value: 'PlantSpecies', text: 'Plant species', sort: 'string'},
			{value: 'PlantEcotype', text: 'Plant ecotype', sort: 'string'},
			{value: 'PlantGenotype', text: 'Plant genotype', sort: 'string'},
			{value: 'GrowthMedium', text: 'Growth medium', sort: 'string'},
			{value: 'GrowthTemperature', text: 'Growth temperature', sort: 'int'},
			{value: 'DayNightRegime', text: 'Day/night regime', sort: 'string'},
			{value: 'Reference', text: 'Reference', sort: 'string'}
		],
		'rnaseq-sasakit-2014': [
			{value: 'Treatment', text: 'Treatment', sort: 'string'},
			{value: 'Inocula', text: 'Inocula', sort: 'string'},
			{value: 'Strain', text: 'Strain', sort: 'string'},
			{value: 'TimeUnit', text: 'Time unit', sort: 'string'},
			{value: 'TimeDuration', text: 'Duration', sort: 'int'},
			{value: 'PlantSpecies', text: 'Plant species', sort: 'string'},
			{value: 'PlantEcotype', text: 'Plant ecotype', sort: 'string'},
			{value: 'PlantGenotype', text: 'Plant genotype', sort: 'string'},
			{value: 'Tissue', text: 'Tissue', sort: 'string'},
			{value: 'Reference', text: 'Reference', sort: 'string'}
		],
		'rnaseq-suzakit-2014': [
			{value: 'Treatment', text: 'Treatment', sort: 'string'},
			{value: 'Inocula', text: 'Inocula', sort: 'string'},
			{value: 'Strain', text: 'Strain', sort: 'string'},
			{value: 'TimeUnit', text: 'Time unit', sort: 'string'},
			{value: 'TimeDuration', text: 'Duration', sort: 'int'},
			{value: 'PlantSpecies', text: 'Plant species', sort: 'string'},
			{value: 'PlantEcotype', text: 'Plant ecotype', sort: 'string'},
			{value: 'PlantGenotype', text: 'Plant genotype', sort: 'string'},
			{value: 'Tissue', text: 'Tissue', sort: 'string'},
			{value: 'Reference', text: 'Reference', sort: 'string'}
		],
		'rnaseq-davidm-2017': [
			{value: 'Treatment', text: 'Treatment', sort: 'string'},
			{value: 'Inocula', text: 'Inocula', sort: 'string'},
			{value: 'Strain', text: 'Strain', sort: 'string'},
			{value: 'TimeUnit', text: 'Time unit', sort: 'string'},
			{value: 'TimeDuration', text: 'Duration', sort: 'int'},
			{value: 'PlantSpecies', text: 'Plant species', sort: 'string'},
			{value: 'PlantEcotype', text: 'Plant ecotype', sort: 'string'},
			{value: 'Tissue', text: 'Tissue', sort: 'string'},
			{value: 'Age', text: 'Age (days)', sort: 'int'},
			{value: 'Reference', text: 'Reference', sort: 'string'}
		],
		'rnaseq-kellys-2017': [
			{value: 'Treatment', text: 'Treatment', sort: 'string'},
			{value: 'Inocula', text: 'Inocula', sort: 'string'},
			{value: 'Strain', text: 'Strain', sort: 'string'},
			{value: 'TimeUnit', text: 'Time unit', sort: 'string'},
			{value: 'TimeDuration', text: 'Duration', sort: 'int'},
			{value: 'PlantSpecies', text: 'Plant species', sort: 'string'},
			{value: 'PlantEcotype', text: 'Plant ecotype', sort: 'string'},
			{value: 'Tissue', text: 'Tissue', sort: 'string'},
			{value: 'Age', text: 'Age (days)', sort: 'int'},
			{value: 'Reference', text: 'Reference', sort: 'string'}
		],
	};

	// Display subset options
	var listedConditions = [];

	// Use select2
	$('#expat-dataset').css('width', '100%').select2();

	// Global function
	var globalFunExpat = {
		expat: {
			replaceIDClass: function(string) {
				return string.replace(/[\#\.]/gi,'');
			},
			progressiveArraySum: function(arr) {
//				var newArr = [], vPrev = 0;
//				$.each(arr, function(i,v) {
//					vPrev += v;
//					newArr.push(vPrev);
//				});
//				return newArr;
				var newArr = arr.reduce(function(r, a) {
					if (r.length > 0) a += r[r.length - 1];
					r.push(a);
					return r;
				}, []);
				return newArr;
			},
			checkInput: function() {
				var idType = $('#expat-dataset option:selected').data('idtype');

				if($('#expat-row').length) {
					var idVals = $('#expat-row').val(),
						idPattern = {
							'transcriptid': /^Lj([0-6]|chloro|mito|1_4_FUCT|XYLT)g\dv\d+(\.(mrna)?\d+)?$/i,
							'geneid': /^Lj([0-6]|chloro|mito|1_4_FUCT|XYLT)g\dv\d+(\.(mrna)?\d+)?$/i,
							'probeid': /^(Ljwgs\_|LjU|Lj\_|chr[0-6]\.|gi|m[a-z]{2}|tc|tm|y4|rngr|cm).+\_at$/i
						},
						idExample = {
							'transcriptid': 'Lj4g3v0281040.1',
							'geneid': 'Lj4g3v0281040',
							'probeid': 'Ljwgs_036669.1_at'
						},
						$ids = $('#expat-row').prev('ul.input-values').find('li').not('.input-wrapper');

					$('#expat-row').closest('.multiple-text-input').siblings('p.user-message').remove();

					// Clear classes
					$ids.removeClass('user-message warning');

					// Highlight problematic ids
					if(idType !== undefined && idType !== '' && idVals.length > 0) {
						$ids.each(function() {
							var str = $(this).data('input-value');
							if(idPattern[idType].test(str)) {

							} else {
								$(this).addClass('warning');
							}
						});

						var vals = idVals.split(','),
							uniqueVals = [];

						$.each(vals, function(i,v) {
							if($.inArray(v, uniqueVals) == -1) {
								uniqueVals.push(v);
							}
						});

						var errorIDs = [];
						$.each(uniqueVals, function(i,v) {
							var valsError;
							if(idPattern[idType].test(v)) {

							} else {
								errorIDs.push(v);
							}
						});

						if(errorIDs.length > 0) {
							$('#expat-row').closest('.multiple-text-input').next('small').after('<p class="user-message warning">You have provided IDs that do not match the expected format for the selected dataset. Example: <code>'+idExample[idType]+'</code>.<br />Regex pattern used: <code>'+idPattern[idType]+'</code>. </p>');
						}
					}
				}

				// Update idtype
				$('#expat-idtype').val(idType);
			},
			grouping: {
				update: function() {
					// Fetch current choice
					var dataset = $('#expat-dataset').val(),
						experiment = $('#expat-dataset option:selected').data('experiment'),
						columnShareCurrent = $('#expat-dataset option:selected').data('column-share'),
						expatCondition = $('#expat-condition').val(),
						groupingFun = globalFun.expat.grouping;

					// Exit if dataset or experiment is undefined or null
					if(dataset === null || experiment === undefined) {
						$('#expat-dataset-subset').hide(250);
						$('#expat-sort-conditions').addClass('hidden').empty();
						$('#expat-condition').val('');
						return false;
					}

					// Check if columns are interchangable
					if(columnShare !== undefined && columnShare !== null && columnShare === columnShareCurrent) {
						$('#expat-condition').val(expatCondition);
					} else {
						$('#expat-sort-conditions').addClass('hidden').empty();
						$('#expat-condition').val('');
					}

					// Empty table
					$('#expat-dataset-subset table thead, #expat-dataset-subset table tbody').empty();

					// Make AJAX call
					var getColumns = $.ajax({
						url: root + '/api/v1/expat/' + experiment + '/' + dataset,
						dataType: 'json',
						type: 'GET'
					});

					// Show
					groupingFun.show();

					// When AJAX is done
					getColumns.done(function(d) {

						var data = d.data;

						// Create index
						globalVar.expat = {};
						globalVar.expat.index = lunr(function() {
							this.field('content');
							this.ref('id');
						});

						// Check if user has supplied the correct ID type
						globalFun.expat.checkInput();

						// Housekeeping functions
						groupingFun.ajaxDone(data, experiment);

						// Empty table
						$('#expat-dataset-subset table tbody').empty();

						// Empty sort conditions
						$('#expat-sort-conditions').empty();
						$('#expat-condition').val('');
						var datasetColumns = (params.columns ? params.columns.split(',') : []),
							columns = [];

						// Fill table rows depending on dataset selected
						if(experiment === 'ljgea') {
							$.each(data, function(i,r) {

								// Construct row for output
								var row = '<tr>',
									content, raw;

								$.each(r, function(k,c) {
									if(k.indexOf('Reference') === -1) {
										// Note: Cast c to string if you are using .replace(). The method doesn't want to work with integers
										row += (k==='ConditionName'?'<td class="chk"><input type="checkbox" data-condition="'+c+'" name="column[]" value="'+c+'" id="dataset-condition__'+c+'" '+(datasetColumns.indexOf(c) > -1 ? 'checked': '')+'/></td>':'')+'<td class="'+k+'">'+(k==='PlantGenotype'&&c!=='Wildtype'?'<em>':'')+(k==='Standard'?(c==='1'?'Standard':'&ndash;'):(c===null?'&ndash;':String(c).replace(/((Gigaspora margarita)|(Mesorhizobium loti))/gi,'<em>$1</em>')))+(k==='PlantGenotype'&&c!=='Wildtype'?'</em>':'')+'</td>';
									} else if(k==='Reference') {
										// Note: Cast c to string if you are using .replace(). The method doesn't want to work with integers
										row += '<td class="'+k+'"><a href="'+r.ReferenceURL+'" title="'+r.ReferenceTitle+'">'+c.replace(/(et al\.)/gi,'<em>$1</em>')+'</a></td>';
									}

									// Append to content, which we will add to lunr.js index
									content += ' ' + (c ? $('<span>'+c+'</span>').text() : '');
								});
								row += '</tr>';

								// Append to condition sort
								if(datasetColumns.indexOf(r.ConditionName) > -1) {
									columns.push(r.ConditionName);
									$('#expat-sort-conditions').append('<li data-condition="'+r.ConditionName+'">'+r.ConditionName+'<span class="icon-cancel icon--no-spacing"></span></li>');
								}

								// Append to row
								$('#expat-dataset-subset table tbody').append(row);
								
								// Add to index
								globalVar.expat.index.add({
									'content': content,
									'id': i
								});
							});
						} else {
							$.each(data, function(i,r) {
								var row = '<tr>';
								$.each(r, function(k,c) {
									if(k.indexOf('Reference') === -1) {
										// Note: Cast c to string if you are using .replace(). The method doesn't want to work with integers
										row += (k==='ConditionName'?'<td class="chk"><input type="checkbox" data-condition="'+c+'" /></td>':'')+'<td class="'+k+'">'+(c===null?'&ndash;':c)+'</td>';
									} else if(k==='Reference') {
										if (!r.ReferenceURL) {
											row += '<td class="'+k+'">'+c.replace(/(et al\.)/gi,'<em>$1</em>')+'</td>';
										} else {
											row += '<td class="'+k+'"><a href="'+r.ReferenceURL+'" title="'+r.ReferenceTitle+'">'+c.replace(/(et al\.)/gi,'<em>$1</em>')+'</a></td>';
										}
									}
								});
								row += '</tr>';

								// Append to condition sort
								if(datasetColumns.indexOf(r.ConditionName) > -1) {
									columns.push(r.ConditionName);
									$('#expat-sort-conditions').append('<li data-condition="'+r.ConditionName+'">'+r.ConditionName+'<span class="icon-cancel icon--no-spacing"></span></li>');
								}

								// Append to row
								$('#expat-dataset-subset table tbody').append(row);
							});
						}

						// Update condiitons
						$('#expat-condition').val(columns.join(','));

						// Enable sorting
						groupingFun.sortTable();

						// Update checkboxes
						groupingFun.updateCheckboxes();
					})

					.error(function() {

					});	
				},
				show: function() {
					$('#expat-dataset-subset .col-two .table-overflow').before('<div class="loading-message user-message"><div class="loader"><svg class="loader"><circle class="path" cx="40" cy="40" r="30" /></svg></div><p class="loading-text">Retrieving column information from database&hellip;</p></div>');
					$('#expat-dataset-subset').slideDown(250);
				},
				ajaxDone: function(data, experiment) {

					// Remove loading message
					$('#expat-dataset-subset .col-two .loading-message').slideUp(125, function() {
						$(this).remove();
					});

					// Enable filtering
					$('#expat-dataset-subset input[type="text"]').prop('disabled', false);

					// Create table header
					$('#expat-dataset-subset table thead').html('<tr><th class="chk"><input type="checkbox" id="expat-dataset-subset-ca" class="ca" /></th><th data-sort="string-int">Column</th></tr>');

					// Fill table header
					$.each(datasetSubset[experiment], function(i,c) {
						$('#expat-dataset-subset table thead tr').append('<th data-sort="'+c.sort+'">'+c.text+'</th>');
					});
				},
				sortTable: function() {
					// Enable sorting
					$('#expat-dataset-subset table')
					.stupidtable()
					.on('beforetablesort', function(event, data) {
						$(this).addClass('disabled');
					})
					.on('aftertablesort', function(event, data) {
						$(this).removeClass('disabled');
						// Re-sort sortable list
						listedConditions = [];
						$('#expat-sort-conditions').empty();
						$('#expat-dataset-subset table tbody tr:visible input[type="checkbox"]:checked').each(function() {
							var thisCondition = $(this).data('condition');
							listedConditions.push(thisCondition);
							$('#expat-sort-conditions').append('<li data-condition="'+thisCondition+'">'+thisCondition+'<span class="icon-cancel icon--no-spacing"></span></li>').trigger('manualsortupdate');
						});
					});
				},
				updateCheckboxes: function() {
					// Check appropriate checkboxes
					if($('#expat-condition').length) {
						var conditionsList = $('#expat-condition').val().split(',');
						$.each(conditionsList, function(i,v) {
							$('#expat-dataset-subset table tbody input[data-condition="'+v+'"]').prop('checked', true);
						});
					}
				}
			}
		}
	};
	$.extend(globalFun, globalFunExpat);

	$d.on('change', '#expat-row', function() {
		globalFun.expat.checkInput();
	});

	// Store original starting choice, and check
	//globalFun.expat.grouping.update();

	// Bind change event
	$d.on('change', '#expat-dataset', globalFun.expat.grouping.update);

	// Filter
	var filterFun = {
			updateCa: function() {
				// Count number of checked
				var checkedCount = $('#expat-dataset-subset tbody td.chk input[type="checkbox"]:checked').length,
					visibleRowCount = $('#expat-dataset-subset tbody tr:visible').length,
					$ca = $('#expat-dataset-subset-ca');

				if(checkedCount === 0) {
					$ca.prop({'indeterminate': false, 'checked': false});
				} else if(visibleRowCount > checkedCount) {
					$ca.prop({'indeterminate': true, 'checked': false});
				} else {
					$ca.prop({'indeterminate': false, 'checked': true});
				}

				// Update sortable class
				if(checkedCount === 0) {
					$('#expat-sort-conditions').addClass('ui-state-empty');
				} else {
					$('#expat-sort-conditions').removeClass('ui-state-empty');
				}
			}
		};
	$d
	.on('keyup', '#expat-dataset-subset input[type="text"]', $.throttle(500, function() {
		var keywords = $(this).val(),
			datasetSubsetRowMatches = 0;

		$('#subset-none').remove();

		if(keywords.length > 2) {

			$('#expat-dataset-subset tbody tr').hide();

			globalVar.expat.index.search(keywords).map(function(r) {
				var $result = $('#expat-dataset-subset tbody tr').eq(r.ref);
				$result.show();
				datasetSubsetRowMatches++;
			});

			// Are there any rows that match?
			if(datasetSubsetRowMatches === 0) {
				$('#expat-dataset-subset tbody').append('<tr id="subset-none"><td colspan="99" class="user-message warning">No conditions matched your search criteria. Please try again.</td></tr>');
			}
		} else {
			$('#expat-dataset-subset tbody tr').show();
		}

		// Check ca (check-all) checkbox
		filterFun.updateCa();	
	}));

	// Checkbox magic for grouping table
	$d
	.on('click', '#expat-dataset-subset tbody tr', function() {
		var $checkbox = $(this).find('td.chk input[type="checkbox"]');
		if($checkbox.is(':checked')) {
			$checkbox.prop('checked', false).trigger('change');
		} else {
			$checkbox.prop('checked', true).trigger('change');
		}
	})
	.on('click', '#expat-dataset-subset tbody td.chk', function(e) {
		e.stopPropagation();
	})
	.on('change', '#expat-dataset-subset tbody td.chk input[type="checkbox"]', function(e) {
		// Toggle state
		var thisCondition = $(this).data('condition');
		if($(this).is(':checked')) {
			// Update classes
			$(this).closest('tr').addClass('checked');

			// Modify sortable list
			if($.inArray(thisCondition, listedConditions) === -1 && customSort) {
				// Doesn't exist in array
				listedConditions.push(thisCondition);
				$('#expat-sort-conditions').append('<li data-condition="'+thisCondition+'">'+thisCondition+'<span class="icon-cancel icon--no-spacing"></span></li>').trigger('manualsortupdate');
			}

		} else {
			// Update classes
			$(this).closest('tr').removeClass('checked');

			// Modify sortable list
			if($.inArray(thisCondition, listedConditions) > -1 && customSort) {
				// Exists in array
				listedConditions.splice($.inArray(thisCondition, listedConditions), 1);
				$('#expat-sort-conditions').find('li[data-condition="'+thisCondition+'"]').fadeOut(250, function() {
					$(this).remove();
					$('#expat-sort-conditions').trigger('manualsortupdate');
				});
			}
		}

		// Update check all status
		filterFun.updateCa();

		// Show sortable list
		if(customSort) $('#expat-sort-conditions').removeClass('hidden');
	})
	.on('change', '#expat-dataset-subset-ca', function(e) {
		var $checkbox = $('#expat-dataset-subset tbody tr:visible td.chk input[type="checkbox"]');
		if($(this).is(':checked')) {
			$checkbox.prop('checked', true).trigger('change');
		} else {
			$checkbox.prop('checked', false).trigger('change');
		}
		$(this).prop('indeterminate', false);
	});

	// Sortable list for pre-search columns
	if(customSort) {
		var expatSortConditionsUpdate = function() {
			// Update custom sort
			var sortedConditions = $('#expat-sort-conditions li').map(function() {
				return $(this).data('condition');
			}).get();
			if(sortedConditions.length > 0) {
				$('#expat-condition').val(sortedConditions.join(','));
				expatCondition = sortedConditions.join(',');
			} else {
				$('#expat-condition').val('');
				expatCondition = '';
			}
		};
		$('#expat-sort-conditions')
		.sortable({
			placeholder: 'ui-state-highlight',
			activate: function() {
				$(this).addClass('ui-state-active');
			},
			deactivate: function() {
				$(this).removeClass('ui-state-active');
			},
			update: expatSortConditionsUpdate
		})
		.on('manualsortupdate', expatSortConditionsUpdate);
		$d.on('click', '#expat-sort-conditions li span.icon-cancel', function() {
			// Update checkbox status
			$('#expat-dataset-subset table input[type="checkbox"][data-condition="'+$(this).parent('li').data('condition')+'"]').prop('checked', false).closest('tr').removeClass('checked');

			// Remove draggable handler
			$(this).closest('li').fadeOut(125, function() {
				$(this).remove();
				$('#expat-sort-conditions').trigger('manualsortupdate');
			});
		});
	}
});