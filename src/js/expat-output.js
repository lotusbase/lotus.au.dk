$(function() {

	// Global variables
	var expat = {
		d3: {
			vars: {}
		},
		status: {
			searched: false,
			pushState: true
		},
		params: globalFun.parseURLquery(),
		chart: {
			margin: {
				top: 5,
				right: 5,
				bottom: 280,
				left: 90
			},
			outerWidth: 960
		},
		heatmap: {
			cellHeight: 20
		},
		linegraph: {
			targetHeight: 150
		},
		dendrogram: {
			row: {
				currentWidth: 0
			},
			condition: {
				currentHeight: 0,
				targetHeight: 150
			},
			rowClusterCutoff: 0.25,
			colClusterCutoff: 0.25
		}
	};
	expat.chart.innerWidth = expat.chart.outerWidth - expat.chart.margin.left - expat.chart.margin.right;

	// Add message box
	$('#expat-form').prepend('<div id="expat-message" class="user-message"></div>');

	$d.on('submit', '#expat-form', function(e) {
		var $t = $(this),
			startTime = new Date();
		
		e.preventDefault();

		// Update expat.params
		expat.params = globalFun.parseURLquery();

		// Remove all d3 tips
		$('.d3-tip').remove();

		// Remove expat error messages
		if(!expat.params.error) {
			$('#expat-error').fadeOut(250, function() {
				$(this).remove();
			});
		}

		// Validate
		var validator = $(this).validate({
			ignore: [],
			rules: {
				'ids': 'required',
				'dataset': 'required'
			},
			messages: {
				'ids': 'Please enter one or more accession(s).',
				'dataset': 'Please select a dataset.'
			},
			errorPlacement: function(error, element) {
				var $e = element;
				if($e.attr('id') === 'expat-dataset') {
					$e.siblings('.select2')
						.addClass('error')
						.after(error);
				} else if($e.attr('id') === 'expat-row' && $e.hasClass('input-hidden')) {
					$e.parent('div').parent().append(error);
				} else {
					error.insertAfter(element);
				}
			}
		});

		// Error handling for invalid ID
		if (!validator.element('#expat-row')) {
			$('#expat-row').closest('.input-mimic').addClass('error');
		} else {
			$('#expat-row').closest('.input-mimic').removeClass('error');
		}

		if(!$(this).valid()) {
			return false;
		}

		// Perform AJAX call
		var expatAJAX = $.ajax({
				type: 'POST',
				url: root + '/api/v1/expat',
				data: $t.serialize(),
				dataType: 'json'
			}),
			d3varsAJAX = $.ajax({
				type: 'GET',
				url: '/data/d3/vars.json',
				dataType: 'json'
			});

		// Push history state
		if(expat.status.pushState) window.history.pushState({lotusbase: true}, '', '?'+$t.serialize());
		expat.status.pushState = true;

		// Collapse form, only if search has not been triggered before
		if(!expat.status.searched) {
			$t
			.wrap('<div class="toggle hide-first"></div>')
			.before('<h3><a href="#" title="Retrieve other expression data">Retrieve other expression data</a></h3><p>You can repeat your search using a custom sort string, or retrieve expression data for any other accessions/gis.</p>');
			expat.status.searchd = true;
		}

		// Display loading message
		$('#expat-message').removeClass().addClass('loading-message user-message').html('<div class="loader"><svg class="loader"><circle class="path" cx="40" cy="40" r="30" /></svg></div><p class="loading-text">Retrieving column information from database&hellip;</p>').slideDown(250);

		// Success
		//var expat = expat;
		var ajaxChain = $.when(expatAJAX, d3varsAJAX);

		ajaxChain
		.done(function(expatResponse, d3varsResponse) {

			expat = expat;

			// Where is the data coming from
			var d = expatResponse[0],
				data = d.data;

			// Set d3 variables
			expat.d3.vars = $.extend({}, expat.d3.vars, d3varsResponse[0]);

			if(!expat.status.searched) expat.status.searched = true;

			// Parent wrapper
			var $p = $t.parent();

			// Add class
			$b.addClass('wide');

			// Hide stuff
			$p.children().not('h3').hide();
			$p.find('h3 a').removeClass("open").data("toggled", "off");

			// Destroy tabs
			$('#expat-user-custom').tabs('destroy');

			// Evaluate returned data
			if(!d.error) {

				// Add dataset class to body
				$b.addClass('data.dataset');

				// Turn off namespaced events
				$d.off('.expatSuccess');

				// Hide download link in search form
				// Download option now moved to export tab
				$t.find('#download-raw-data').hide();

				// Globals
				var order = {
					'condition': data.Mean.condition,
					'row': data.Mean.row
				};

				// DOM elements
				var $table = $('<table id="rows"></table>'),
					$userMessage,
					$svgDownload,
					$dataDownload,
					$columnSort,
					$userCustom;

				// Table creation
				$table.append('<colgroup></colgroup><thead><tr><td>'+data.rowType+'</td>'+(data.mapped?'<td>'+data.mapped.text+' mapped to*</td>':'')+'</tr></thead><tbody></tbody><tfoot><th class="'+data.dataset+'"><button type="button" class="button--small" disabled><span class="pictogram icon-search">Search selected</span></button></th>'+(data.mapped?'<th class="'+data.mapped.dataset+'"><button type="button" class="button--small" disabled><span class="pictogram icon-search">Search selected</span></button></th>':'')+'<td colspan="999"></td></tfoot>');
				$.each(data.Mean.condition, function(i,conditions) {
					$table.find('thead tr').append('<td data-condition="'+conditions+'"><div><span>'+conditions+'</span></div></td>');
					$table.prepend('<colgroup></colgroup>');
				});
				$.each(data.Mean.row, function(i,rowID) {
					var accessionLinks = [
						'<li><a href="#" title="" class="api-gene-annotation" data-gene="'+rowID+'" data-version="3.0"><span class="pictogram icon-book">Gene annotation</span></a></li>',
						'<li><a href="/tools/trex.php?ids='+rowID+'" title="Get advanced transcript information for this gene: '+rowID+'"><span class="pictogram icon-direction">Send to Transcript Explorer (TREX)</span></a></li>',
						'<li><a href="/lore1/search-exec.php?v=3.0&gene='+rowID+'" title="Search for LORE1 lines with insertion in this gene: '+rowID+'"><span class="pictogram icon-search">Search for <em>LORE1</em> mutants</span></a></li>'
						].join(''),
						tableRow = '<tr data-row="'+rowID+'"><th><input type="checkbox" class="'+data.dataset+'" value="'+rowID+'" /> ';
					if(data.rowType === 'Gene ID') {
						tableRow += '<div class="dropdown button"><span class="dropdown--title">'+rowID+'</span><ul class="dropdown--list">'+accessionLinks+'</ul></div>';
					} else {
						tableRow += rowID;
					}
					tableRow += '</th>';
					if(data.mapped) {
						tableRow += '<th>';
						$.each(data.mappedTo[rowID], function(j,m) {
							// Bold mappedID if it is unique
							tableRow += '<input type="checkbox" class="'+data.mapped.dataset+'" value="'+m+'" /> '+(data.mappedToUnique[rowID]===m?'<strong>':'')+m+(data.mappedToUnique[rowID]===m?'</strong>':'');
							if(j<data.mappedTo[rowID].length-1) tableRow += '<br />';
						});
						tableRow += '</th>';
					}
					tableRow += '</tr>';
					$table.find('tbody').append(tableRow);
				});
				$.each(data.Mean.melted, function(key,row) {
					var index = $.inArray(row.rowID, data.Mean.row);
					$table.find('tbody tr').eq(index).append('<td data-type="numeric" data-condition="'+row.condition+'">'+row.value+'</td>');
				});

				// DOM manipulation
				var userMessage = '<p>We have retrieved the expression data of <strong>'+data.Mean.row.length+'</strong> entries consisting of <strong>'+data.Mean.melted.length+'</strong> data points from our database in <strong>'+(new Date() - startTime)+'ms</strong>.</p>';

				// If we have accessions that are not found in the database
				//if(data.notFound.length > 1 || (data.notFound.length === 1 && data.notFound[0] !== "")) {
				if(data.notFound && data.notFound.length > 0) {
					userMessage += '<p class="user-message warning">We have failed to find expression data for the following: <code>'+data.notFound.join('</code>, <code>')+'</code>.</p>';
				}
				if(data.columnNotFound && data.columnNotFound.length > 0) {
					userMessage += '<p class="user-message reminder">The following columns are unavailable in the probe ID LjGEA database: <code>'+data.columnNotFound.join('</code>, <code>')+'</code></p>';
				}
				$userMessage = $(userMessage);

				$svgDownload = $([
					'<form class="download-form form--reset" id="svg-download-form" action="'+root+'/lib/expat/svg-download.pl" method="post" style="display: none;">',
						'<input type="hidden" id="output_format" name="output_format" value="svg" />',
						'<input type="hidden" id="svg_data" name="svg_data" value="" />',
						'<input type="hidden" id="svg_chartType" name="svg_chartType" value="" />',
					'</form>'
					].join(''));

				$dataDownload = $('<div class="toggle hide-first"></div>');
				$dataDownload.append([
					'<h3><a href="#" title="Export options">Export options</a></h3>',
					'<p>',
						'Download the array data, or export the line graph/heatmap generated. ',
						'Expression data is presented as '+data.rowText.toLowerCase()+' by row and conditions by column. ',
						'Three CSV files are enclosed in the zip file, containing the calculated geometric means, <a href="https://en.wikipedia.org/?title=Standard_deviation#Corrected_sample_standard_deviation">corrected sample standard deviation</a> and underscore-delimited raw values respectively. ',
						'Further manipulation of the data can be done by simply importing the generated text file (formatted in CSV) into R. ',
						'We strongly recommend using <a href="http://ggplot2.org/" title="ggplot2, a plotting system for R">ggplot2</a> for visualization of data, and the <a href="http://www.statmethods.net/management/reshape.html" title="">reshape package</a> for <a href="http://seananderson.ca/2013/10/19/reshape.html">melting/casting the data</a>. ',
						'The scalable vector graphic (SVG) file downloaded can be opened and modified in vector-editing software like Adobe Illustrator. ',
						'You may also use the SVG file as a standalone image file and embed it in any HTML document.',
					'</p>',
					'<form action="expat-download" method="post" class="align-center">',
						'<div class="cols">',
							'<button type="submit" id="array-data-download"><span class="icon-file-archive">Download array data (<code>.zip</code>)</span></button>',
							'<input type="hidden" name="idtype" value="'+$('#expat-dataset option:selected').attr('data-idtype')+'" />',
							'<input type="hidden" name="dataset" value="'+data.dataset+'" />',
							'<input type="hidden" name="url" value="'+window.location.search+'" />',
							'<input type="hidden" id="expat-download-condition" name="conditions" value="'+data.Mean.condition.join(',')+'" />',
							'<input type="hidden" id="expat-download-gene" name="ids" value="'+data.Mean.row.join(',')+'">',
							'<button type="button" class="svg-download" data-target="expat-heatmap" data-chart-type="heatmap">',
								'<span class="icon-file-image">Download heatmap (<code>.svg</code>)</span>',
							'</button>',
						'</div>',
					'</form>'
					].join(''));

				// User customization
				$userCustom = $('<div id="user-custom" class="toggle"></div>');
				var userCustom = '<h3><a href="#" title="User customization" class="open" data-toggled="on">Customization</a></h3><form id="expat-user-custom" action="" method="get">';
				userCustom += [
					'<ul>',
						'<li><a href="#user-custom-tab-1" data-custom-smooth-scroll>Clustering</a></li>',
						'<li><a href="#user-custom-tab-2" data-custom-smooth-scroll>'+data.rowType+' sort</a></li>',
						'<li><a href="#user-custom-tab-3" data-custom-smooth-scroll>Condition sort</a></li>',
						'<li><a href="#user-custom-tab-4" data-custom-smooth-scroll>Line graph</a></li>',
						'<li><a href="#user-custom-tab-5" data-custom-smooth-scroll>Heatmap</a></li>',
					'</ul>'
					].join('');


				// Clustering
				userCustom += '<div id="user-custom-tab-1">';
				if (data.Mean.clustering) {
					userCustom += '<p>Enable <a href="'+data.Mean.clustering.meta.wiki+'" title="Further information on '+data.Mean.clustering.meta.type.toLowerCase()+' clustering">'+data.Mean.clustering.meta.type.toLowerCase()+' clustering</a> along either one, or both, axes, powered by the <a href="http://docs.scipy.org/doc/scipy/reference/cluster.html">scipy clustering library</a>. As clustering are based on heuristics and therefore non-deterministic by nature&mdash;i.e. you will not get the exact same order of clustered data points for each iteration. Should you wish to preserve the current row/column order, please copy the sorting list in the <a href="#" class="tab-nav" data-tab-target="1">'+data.rowType.toLowerCase()+' sort</a> and/or <a href="#" class="tab-nav" data-tab-target="2">condition sort</a> tabs to reproduce the same clustering order.</p>';
					userCustom += '<fieldset id="expat-user-custom-cluster-toggle"><legend>Enable/disable clustering</legend><div class="user-message" style="display: none;"></div><label for="expat-user-custom-cluster-by-condition"><input type="checkbox" class="expat-user-custom-cluster-toggle" id="expat-user-custom-cluster-by-condition" data-variable="condition" data-clustering-type="'+data.Mean.clustering.meta.type.toLowerCase().replace(' ','')+'" '+(data.Mean.clustering.data.condition.clusterData.length > 1 ? '' : 'disabled')+' /> <span>Enable '+data.Mean.clustering.meta.type+' clustering by condition'+(data.Mean.clustering.data.condition.clusterData.length > 1 ? ' (<span class="cluster-count" data-variable="condition">'+data.Mean.clustering.data.condition.clusterData.length+'</span> '+globalFun.pl(data.Mean.clustering.data.condition.clusterData.length,'group','groups')+')' : ' (unavailable because only one condition is queried)')+'</span></label><label for="expat-user-custom-cluster-by-row"><input type="checkbox" class="expat-user-custom-cluster-toggle" id="expat-user-custom-cluster-by-row" data-variable="row" data-clustering-type="'+data.Mean.clustering.meta.type.toLowerCase().replace(' ','')+'" '+(data.Mean.clustering.data.row.clusterData.length > 1 ? '' : 'disabled')+'/> <span>Enable '+data.Mean.clustering.meta.type+' clustering by '+data.rowType+(data.Mean.clustering.data.row.clusterData.length > 1 ? ' (<span class="cluster-count" data-variable="row">'+data.Mean.clustering.data.row.clusterData.length+'</span> '+globalFun.pl(data.Mean.clustering.data.row.clusterData.length,'group','groups')+')' : ' (unavailable because only one '+data.rowText.toLowerCase()+' is queried)')+'</span></label></fieldset>';
					if(data.Mean.clustering.meta.type === 'hierarchical agglomerative') {
						userCustom += '<fieldset id="expat-user-custom-cluster-configs"><legend>Fine tune clustering settings</legend><div class="user-message" style="display: none;"></div><div class="cols"><label class="col-one" for="row-cluster-cutoff">'+data.rowText.charAt(0).toUpperCase()+data.rowText.toLowerCase().replace('(s)', '').slice(1)+' cluster cutoff <a data-modal class="help" title="What is cluster cutoff?" data-modal-content="The cluster cutoff refers to the threshold when forming flat clustering&mdash;it determines the number of clusters formed. The higher the threshold, the less clusters you have and the more dissimilarity you will observe within clusters.">?</a></label><input class="col-two expat-user-custom-cluster-config" id="row-cluster-cutoff" name="rowClusterCutoff" type="number" min="0" max="1" step="0.1" value="0.25" placeholder="Cluster cutoff for genes as a proportion of maximum cluster distance" /><label class="col-one" for="col-cluster-cutoff">Condition cluster cutoff <a data-modal class="help" title="What is cluster cutoff?" data-modal-content="The cluster cutoff refers to the threshold when forming flat clustering&mdash;it determines the number of clusters formed. The higher the threshold, the less clusters you have and the more dissimilarity you will observe within clusters.">?</a></label><input class="col-two expat-user-custom-cluster-config" id="col-cluster-cutoff" name="colClusterCutoff" type="number" min="0" max="1" step="0.1" value="0.25" placeholder="Cluster cutoff for conditions as a proportion of maximum cluster distance" />';
						userCustom += '<label class="col-one" for="linkage-method">Linkage method <a data-modal class="help" title="What are linkage methods?" data-modal-content="&lt;p&gt;The linkage method is used to compute the distance between two clusters, allowing us to perform hierarchical/agglomerative clustering on the condensed distance matrix (which is the table of gene expression values).&lt;/p&gt;&lt;p&gt;For further information, please refer to the official SciPy documentation: &lt;a href=&quot;http://docs.scipy.org/doc/scipy-0.14.0/reference/generated/scipy.cluster.hierarchy.linkage.html&quot;&gt;scipy.cluster.hierarchy.linkage&lt;/a&gt;.&lt;/p&gt;">?</a></label><select class="col-two expat-user-custom-cluster-config" id="linkage-method" name="linkageMethod"><option value="single">Single</option><option value="centroid">Centroid</option><option value="complete" selected>Complete (default)</option><option value="median">Median</option><option value="ward">Ward</option><option value="weighted">Weighted</option></select>';
						userCustom += '<label class="col-one" for="linkage-metric">Linkage metric <a data-modal class="help" title="What metrices are available?" data-modal-content="&lt;p&gt;The linkage method requires a distance matrix to be constructed. There are many ways of computing distance matrices, and SciPy has a complete documentation on them: &lt;a href=&quot;http://docs.scipy.org/doc/scipy-0.14.0/reference/spatial.distance.html&quot;&gt;scipy.spatial.distance&lt;/a&gt;&lt;/p&gt;">?</a></label><select class="col-two expat-user-custom-cluster-config" id="linkage-metric" name="linkageMetric"><option value="braycurtis">Braycurtis</option><option value="canberra">Canberra</option><option value="chebyshev">Chebyshev</option><option value="cityblock">City block / Manhattan</option><option value="correlation">Correlation</option><option value="cosine">Cosine</option><optgroup label="Euclideans"><option value="euclidean" selected>Euclidean (default)</option><option value="seuclidean">Standard Euclidean</option><option value="sqeuclidean">Squared Euclidean</option></optgroup><option value="hamming">Normalized Hamming</option><option value="jaccard">Jaccard</option><option value="minkowski">Minkowski</option></select></div></fieldset>';
					}
				} else {
					userCustom += '<p class="user-message reminder">Clustering functionality unavailable. If you are having a large query, it is possible that an upperlimit of the clustering algorithm has been reached. You can retry with less number of genes and/or columns expat.status.searched.</p>';
					userCustom += '<p>Alternatively, you may download the data and perform clustering on your charting program of choice&mdash;we highly recommend <a href="https://www.r-project.org">R</a> and <a href="http://ggplot2.org$">ggplot</a>.</p>';
				}
				userCustom += '</div>';

				// ID sort
				userCustom += [
					'<div id="user-custom-tab-2">',
						'<p>Click and drag individual boxes to rearrange their order of appearance. This will be reflected in the line chart and heatmap, as well as the data table below. You may redownload your array data with the new sorting order.</p>',
						'<input id="expat-user-custom-sort-by-row" value="'+data.Mean.row.join(',')+'" readonly data-variable="row" />',
						'<ul class="sort-list sort-list--dense expat-sort-list" id="expat-sort-list-row" data-variable="row"></ul>',
					'</div>'
					].join('');

				// Condition sort
				userCustom += [
					'<div id="user-custom-tab-3">',
						'<p>Click and drag individual boxes to rearrange their order of appearance. This will be reflected in the line chart and heatmap, as well as the data table below. You may redownload your array data with the new sorting order. The field below reflects the most current order, which you can copy and reuse for later searches.</p>',
						'<input id="expat-user-custom-sort-by-condition" value="'+data.Mean.condition.join(',')+'" readonly data-variable="condition" />',
						'<ul class="sort-list sort-list--dense expat-sort-list" id="expat-sort-list-condition" data-variable="condition"></ul>',
					'</div>'
					].join('');

				// Linegraph settings
				userCustom += [
					'<div id="user-custom-tab-4">',
						'<div class="cols">',
							'<label class="col-one" for="expat-user-custom-line-scale-y">y-axis scale</label>',
							'<select class="col-two" id="expat-user-custom-line-scale-y">',
								'<option value="linear" selected>Linear (default)</option>',
								(!data.dataTransform || ['normalize', 'standardize'].indexOf(data.dataTransform) >= 0 ? '<option value="square_root">Square root</option>' : ''),
								(!data.dataTransform ? '<option value="log_10">Log10</option>' : ''),
							'</select>',
							'<label class="col-one" for="expat-user-custom-interpolate">Interpolation algorithm <a class="help" title="Which interpolation algorithm should I use?" data-modal data-modal-content="&lt;p&gt;Determine how data points in the line graph should be interpolated to construct a path. For more information on the interpolation algorithim, &lt;a href=&quot;https://github.com/mbostock/d3/wiki/SVG-Shapes#line_interpolate&quot;&gt;refer to the official documentation&lt;/a&gt;.&lt;/p&gt;&lt;p class=&quot;user-message note&quot;&gt;Note: some interpolation will cause points to fall out of the line due to smoothing.&lt;/strong&gt;&lt;/p&gt;">?</a></label>',
							'<select class="col-two" id="expat-user-custom-interpolate">',
								'<option value="basis">Basis</option>',
								'<option value="bundle">Bundle</option>',
								'<option value="cardinal">Cardinal (recommended)</option>',
								'<option value="linear">Linear</option>',
								'<option value="monotone" selected>Monotone (default, recommended)</option>',
							'</select>',
						'</div>',
					'</div>'
					].join('');

				// Heatmap color and scale
				userCustom += [
					'<div id="user-custom-tab-5">',
						'<p>Change the scale of heatmap colours.</p>',
						'<div class="cols">',
							'<label class="col-one" for="expat-user-custom-heatmap-scale-color">Colour scale</label>',
							'<select class="col-two" id="expat-user-custom-heatmap-scale-color">',
								'<option value="linear" selected>Linear (default)</option>',
								(!data.dataTransform || ['normalize', 'standardize'].indexOf(data.dataTransform) >= 0 ? '<option value="square_root">Square root</option>' : ''),
								(!data.dataTransform ? '<option value="log_10">Log10</option>' : ''),
							'</select>',
						'</div>',
						'<p>Chart colours are based on the <a href="http://colorbrewer2.org/" title="ColorBrewer">ColorBrewer v2.0 palette</a>.</p>',
						'<ul id="expat-colorbrewer-list"></ul>',
					'</div>'
					].join('');

				$userCustom.append(userCustom);
				var excludePalette = ['Accent','Dark2','Paired','Pastel1','Pastel2','Set1','Set2','Set3'];
				$.each(colorbrewer, function(name,scale) {
					if(excludePalette.indexOf(name) === -1) {
						var $palette = $('<li id="colorbrewer-'+name+'" class="'+(name==='YlGnBu'?'selected':'')+'" data-palette="'+name+'" data-count="8"></li>').append('<ul></ul>');
						$.each(scale[8], function(i,v) {
							$palette.find('ul').append('<li style="background-color: '+v+';"></li>');
						});
						$palette.appendTo($userCustom.find('#expat-colorbrewer-list'));
					}
				});
				$.each(data.Mean.condition, function(i,v) {
					$userCustom.find('#expat-sort-list-condition').append('<li data-variable-type="condition" data-variable="'+v+'">'+v+'</li>');
				});
				$.each(data.Mean.row, function(i,v) {
					$userCustom.find('#expat-sort-list-row').append('<li data-variable-type="row" data-variable="'+v+'">'+v+'</li>');
				});

				// Append everything
				$('#expat-results')
				.empty()
				.append($dataDownload, $userCustom, $userMessage, '<div class="d3-chart" id="expat-linegraph"></div><div class="d3-chart" id="expat-heatmap"></div>', $svgDownload, $('<div class="table-overflow" />').append($table), '<small><strong>* Bolded '+data.rowType.toLowerCase()+' represents best match based on BLAST results.</strong></small>')
				.find('.toggle.hide-first').children().not('h3').hide();
				$('#expat-results').show();

				// Enable jQuery UI tabs
				$('#expat-user-custom').tabs();

				// Bind event handlers
				$d
				// Allow manual navigation to another tab
				.on('click.expatSuccess', '#expat-user-custom a.tab-nav', function(e) {
					e.preventDefault();
					$('#expat-user-custom').tabs({
						'active': $(this).data('tab-target')
					});
				})
				// Manually modifying scroll behavior
				.on('click.expatSuccess', '#expat-user-custom a.ui-tabs-anchor', function(e) {
					e.preventDefault();
					globalFun.smoothScroll('#user-custom');
				})
				// Download button
				.on('click.expatSuccess', 'button.svg-download', function(e) {
					e.preventDefault();

					// Get the d3.js element
					var target = $(this).data('target'),
						svg_chartType = $(this).data('chart-type'),
						svg_xml = (new XMLSerializer()).serializeToString($('#'+target+' svg')[0]);

					// Manually submit form
					$('#svg_data').val(svg_xml);
					$('#svg_chartType').val(svg_chartType);
					$('#svg-download-form')[0].submit();
				})
				// Update sort
				.on('change.expatSuccess', '#user-sort-custom-field', function() {

					// User sort
					var customSort = $('#user-sort-custom-field').val().split(',');

					// Update field
					$('#expat-sort').val(customSort);

					// Resort jQuery UI sortable
					$.each(customSort, function(i,c) {
						$('#expat-conditions-list li[data-condition="'+c+'"]').appendTo($('#expat-conditions-list'));
					});
				});

				// Cast melted data
				data.casted = [];
				$.each(data.Mean.condition, function(i, c) {
					data.casted.push({'condition': c});
				});
				$.each(data.Mean.melted, function(i,row) {
					var rowIndex = data.Mean.row.indexOf(row.rowID);
					data.casted[(i - rowIndex * data.Mean.condition.length)][row.rowID] = row.value;
				});

				// Pop the last item off clusters
				if(data.Mean.clustering) {
					data.Mean.clustering.data.condition.clusterData.pop();
					data.Mean.clustering.data.row.clusterData.pop();
				}

				// Finalize height
				expat.heatmap.targetHeight = expat.heatmap.cellHeight * data.Mean.row.length;

				// Overwrite left margin for longer probe IDs
				if(data.rowType === 'Probe ID') {
					expat.chart.margin.left = 100;
					expat.chart.innerWidth = expat.chart.outerWidth - expat.chart.margin.left - expat.chart.margin.right;
				}

				// Get depth of dendrograms
				var getDepth = function (obj) {
					var depth = 0;
					if (obj.children) {
						obj.children.forEach(function (d) {
							var tmpDepth = getDepth(d);
							if (tmpDepth > depth) {
								depth = tmpDepth;
							}
						});
					}
					return 1 + depth;
				};

				// LineGraph
				// We expose these variables because they are needed later
				var expatLinegraph, rows, line, linegraphX, linegraphY, linegraphXAxis, linegraphYAxis, cb,
					renderLinegraph = function() {
					// Define tip
					var linegraphTip = d3.tip()
							.attr('class', 'd3-tip linegraph-tip tip--bottom')
							.offset([-15,0])
							.direction('n')
							.html(function(d) {
								return '<ul><li class="value"><strong>Value</strong>: <span>'+d.value+'</span></li><li class="row-id"><strong>'+data.rowType+'</strong>: <span>'+$(this).closest('g').data('row')+'</span></li><li class="condition"><strong>Condition</strong>: <span>'+d.condition+'</span></li></ul>';
							});

					// Create graph
					expatLinegraph = d3.select('#expat-linegraph')
						.append('svg:svg')
							.attr({
								'viewBox': '0 0 ' + expat.chart.outerWidth + ' ' + (expat.linegraph.targetHeight + expat.chart.margin.top + 5),
								'preserveAspectRatio': 'xMidYMid meet'
							})
							.style({
								'font-family': 'Arial'
							})
						.append('g')
							.attr({
								'transform': 'translate(' + expat.chart.margin.left + ',' + expat.chart.margin.top + ')',
								'width': expat.chart.innerWidth - expat.dendrogram.row.currentWidth,
								'height': expat.linegraph.targetHeight
							})
						.call(linegraphTip);

					// Scales
					var color = d3.scale.category10();
					linegraphX = d3.scale.ordinal().rangeBands([0, expat.chart.innerWidth - expat.dendrogram.row.currentWidth]);
					linegraphY = d3.scale.linear().range([expat.linegraph.targetHeight, 0]).nice();

					// Line
					line = d3.svg.line()
						.interpolate('monotone')
						.x(function(d) { return linegraphX(d.condition); })
						.y(function(d) { return linegraphY(d.value); });

					// Transform and coerce values
					data.casted.forEach(function(d) {
						d.condition = d.condition;
					});
					color.domain(d3.keys(data.casted[0]).filter(function(key) {
						return key !== 'condition';
					}));
					rows = color.domain().map(function(name) {
						return {
							name: name,
							values: data.casted.map(function(d) {
								return {
									condition: d.condition,
									value: +d[name]
								};
							})
						};
					});

					// Define domains
					linegraphX.domain(data.casted.map(function(d) { return d.condition; }));
					linegraphY.domain([
						d3.min(rows, function(g) { return d3.min(g.values, function(v) { return v.value; });}),
						d3.max(rows, function(g) { return d3.max(g.values, function(v) { return v.value; });})
					]).nice();

					// Axes
					linegraphXAxis = d3.svg.axis().scale(linegraphX).orient('bottom').innerTickSize(300).outerTickSize(0);
					linegraphYAxis = d3.svg.axis().scale(linegraphY).orient('left').tickFormat(function (d) {
						//var log = Math.log(d) / Math.LN10;
						//return Math.abs(Math.round(log) - log) < 1e-6 ? '10e'+Math.round(log) : '';
						return d;
					}).innerTickSize(-(expat.chart.innerWidth - expat.dendrogram.row.currentWidth)).outerTickSize(0);

					// Append axis
					expatLinegraph.append('svg:g')
					.attr('class', 'y axis')
					.call(linegraphYAxis)
					.selectAll('text')
						.attr('class', 'y axis break')
						.style({
							'text-anchor': 'end',
							'font-size': 8
						});

					var linegraphYAxisLabel = expatLinegraph.append('text')
						.attr('class', 'y axis label')
						.attr({
							'dx': 0,
							'dy': 0.5*expat.linegraph.targetHeight - 6
						})
						.style({
							'font-size': 10,
							'text-anchor': 'end'
						});
					linegraphYAxisLabel.append('tspan').attr({
						'x': -35
					}).text('Expression');
					linegraphYAxisLabel.append('tspan').attr({
						'x': -35,
						'dy': 12
					}).text('level');

					expatLinegraph.append('svg:g')
					.attr('class', 'x axis')
					.call(linegraphXAxis)
					.selectAll('text')
						.text('');

					// Add path
					var cb = 'YlGnBu';
					var row = expatLinegraph.selectAll('.row')
						.data(rows)
						.enter()
						.append('svg:g')
						.attr({
							'class': function(d) { return 'row ' + d.name + ' ' + data.rowType.toLowerCase().replace(' ',''); },
							'data-row': function(d) { return d.name; },
							'id': function(d) { return globalFun.expat.replaceIDClass('expat-linegraph-group-'+d.name); },
							'transform': 'translate(' + (0.5 * (expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / data.Mean.condition.length) + ',0)'
						});

					row.append('svg:path')
					.attr({
						'class': 'line',
						'd': function(d) { return line(d.values); },
						'id': function(d) { return globalFun.expat.replaceIDClass('expat-linegraph-path-'+d.name); }
					})
					.style({
						'stroke': '#333',
						'stroke-width': 3,
						'fill': 'none'
					});

					// Add points
					row.selectAll('.point')
					.data(function(d) { return d.values; })
					.enter()
					.append('svg:circle')
						.attr({
							'class': 'point',
							'data-condition': function(d,i) { return data.Mean.condition.indexOf(d.condition); },
							'cx': function(d) { return linegraphX(d.condition); },
							'cy': function(d) { return linegraphY(d.value); },
							'r': 4
						})
						.style({
							'stroke': '#333',
							'stroke-width': 2,
							'fill': '#fff'
						})
						.on('mouseover', linegraphTip.show)
						.on('mouseout', linegraphTip.hide);

					// Manual styles
					expatLinegraph.selectAll('.x.axis path.domain').style('stroke', 'none');
					expatLinegraph.selectAll('.y.axis path.domain').style('stroke', '#333');
					expatLinegraph.selectAll('.y.axis .tick line').style('stroke', '#ccc');

					// Add cluster separators
					if (data.Mean.clustering) {
						expatLinegraph.selectAll('.cluster-sep.condition')
						.data(globalFun.expat.progressiveArraySum(data.Mean.clustering.data.condition.clusterData))
						.enter()
						.append('line')
							.attr({
								'class': 'cluster-sep condition',
								'x1': function(d) { return d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length); },
								'x2': function(d) { return d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length); },
								'y1': 0,
								'y2': expat.linegraph.targetHeight
							})
							.style({
								'stroke': '#333',
								'stroke-opacity': 0.5,
								'stroke-dasharray': '2,2',
								'opacity': 0
							});
					}
				},
				renderLinegraphButton = function() {
					$('button.svg-download[data-target="expat-heatmap"]').before('<button type="button" class="svg-download" data-target="expat-linegraph" data-chart-type="linegraph"><span class="icon-file-image">Download line graph (<code>.svg</code>)</span></button> ');
				};

				// Only render line graph when there are less than or equal to 25 entries
				if(data.Mean.row.length <= 25) {
					renderLinegraph();
					renderLinegraphButton();
				} else {
					//$('#expat-heatmap').before('<p class="user-message note" id="linegraph-disabled">Out of consideration for browser performance, the line graph of expression data is not displayed. <a href="#" id="show-expat-linegraph" class="button">Click here to render line graph</a>.</p>');
					$('#expat-heatmap').before('<p class="user-message note" id="linegraph-disabled">Out of consideration for browser performance, the line graph of expression data is not displayed.</p>');
					$d.on('click.expatSuccess', '#show-expat-linegraph', function(e) {
//						e.preventDefault();
//
//						// Render graph
//						renderLinegraph();
//						$(this).closest('p').remove();
//						renderLinegraphButton();
//
//						// Update line graph
//						var tweenLinegraph = expatLinegraph.transition().duration(1000).ease('cubic-in-out');
//
//						tweenLinegraph.selectAll('g.row')
//						.attr({
//							'transform': 'translate(' + (0.5 * (expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / data.Mean.condition.length) + ',0)'
//						});
//						tweenLinegraph.selectAll('circle.point')
//						.attr({
//							'cx': function(d) { return order.condition.indexOf(d.condition) * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / data.Mean.condition.length); }
//						});
//						tweenLinegraph.selectAll('.x.axis .tick')
//						.attr({
//							'transform': function(d) { return 'translate(' + ((order.condition.indexOf(d) + 0.5) * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / data.Mean.condition.length)) + ',0)'; }
//						});
//
//						// Reshuffle genes
//						var newRows = [];
//						$.each(rows, function(i,row) {
//							var newRow = new Array(data.Mean.condition.length);
//							$.each(row.values, function(j, value) {
//								newRow[order.condition.indexOf(value.condition)] = {
//									condition: value.condition,
//									value: value.value
//								};
//							});
//							newRows.push({
//								name: row.name,
//								values: newRow
//							});
//						});
//
//						// Redraw paths
//						expatLinegraph.selectAll('path.line').each(function(d,i) {
//							// Do not rely on index (i), double check row against known array
//							var row = $(this).closest('g.row').attr('data-row');
//							d3.select(this)
//							.datum(newRows[data.Mean.row.indexOf(row)])
//							.transition().duration(1000).ease('cubic-in-out')
//							.attr({
//								'd': function(d) { return line(d.values); }
//							});
//						});
					});
				}

				// Heatmap
				// Functions
				globalFun.heatmap = {
					filterLegendData: function(opts) {
						// Parse options
						var d = opts.data,
							i = opts.index,
							scale = opts.scale,
							length = opts.length;

						var n = Number(Math.round(d+'e2')+'e-2');

						if(scale === 'log_10' || scale === 'square_root') {
	 						// Round floating point numbers
							var log_n = Math.round(Math.log(n) / Math.LN10 * 1e6) / 1e6,
								mod_log_n = Math.round((log_n % 1) * 1e6) / 1e6;

							// Breaks at half way point
							// log(5) = 0.69897
							if(mod_log_n === 0 || mod_log_n === 0.69897) {
								return opts.value_if_true;
							} else if(opts.force_outer && (i === 0 || i === length-1)) {
								return opts.value_if_true;
							} else {
								return opts.value_if_false;
							}
						} else if(scale === 'linear') {

							// Round floating point numbers
							var mod_5 = Math.round((n % 5) * 1e6) / 1e6;
							if(mod_5 === 0) {
								return opts.value_if_true;
							} else {
								return opts.value_if_false;
							}

						}
					}
				};

				// Declare tip
				var heatmapTip = d3.tip()
						.attr('class', 'd3-tip heatmap-tip tip--top')
						.offset([15,0])
						.direction('s')
						.html(function(d) {
						return '<ul><li class="value"><strong>Value</strong>: <span>'+d.value+'</span></li><li class="gene-id"><strong>'+data.rowType+'</strong>: <span>'+d.rowID+'</span></li><li class="condition"><strong>Condition</strong>: <span>'+d.condition+'</span></li></ul>';
					});

				// Create graph
				var expatHeatmap = d3.select('#expat-heatmap')
					.append('svg:svg')
						.attr({
							'viewBox': '0 0 ' + expat.chart.outerWidth + ' ' + (expat.heatmap.targetHeight + expat.chart.margin.top + expat.chart.margin.bottom),
							'preserveAspectRatio': 'xMidYMid meet'
						})
						.style('font-family', 'Arial')
					.append('g')
						.attr({
							'id': 'expat-heatmap__wrapper',
							'transform': 'translate(' + expat.chart.margin.left + ',' + expat.chart.margin.top + ')',
							'width': expat.chart.innerWidth - expat.dendrogram.row.currentWidth,
							'height': expat.heatmap.targetHeight
						})
					.call(heatmapTip);

				// Coerce data
				data.Mean.melted.forEach(function(d) {
					d.rowID = d.rowID;
					d.condition = d.condition;
					d.value = +d.value;
				});

				// Scales
				var heatmapX = d3.scale.ordinal().domain(data.Mean.condition).rangeBands([0, expat.chart.innerWidth - expat.dendrogram.row.currentWidth]),
					heatmapY = d3.scale.ordinal().domain(data.Mean.row).rangeBands([0, expat.heatmap.targetHeight]),
					heatmapZ = d3.scale.linear().domain(d3.extent(data.Mean.melted, function(d) { return d.value; })).range([0, 1]).nice();

				// Heatmap fills
				var fills = colorbrewer.YlGnBu[8],
					heatmapFill = d3.scale.linear().domain(d3.range(0, 1, 1.0 / (fills.length - 1))).range(fills);

				// Get data
				expatHeatmap.selectAll('.tile')
					.data(data.Mean.melted)
					.enter()
					.append('rect')
					.attr({
						'class': 'tile',
						'x': function(d) { return heatmapX(d.condition); },
						'y': function(d) { return heatmapY(d.rowID); },
						'fill': function(d) { return heatmapFill(heatmapZ(d.value)); },
						'data-row': function(d) { return d.rowID; },
						'data-condition': function(d) { return data.Mean.condition.indexOf(d.condition); },
						'id': function(d) { return 'expat-heatmap-rect-'+data.Mean.condition.indexOf(d.condition)+'-'+d.rowID; },
						'width': heatmapX.rangeBand(),
						'height': heatmapY.rangeBand()
					})
					.on('mouseover', heatmapTip.show)
					.on('mouseout', heatmapTip.hide);

				// Add a legend for the color values.
				var ticks = heatmapZ.ticks();

				// Heatmap color gradient legend
				var gradient = expatHeatmap.append('defs')
					.append('linearGradient')
					.attr({
						'id': 'heatmap-gradient',
						'x1': '0%',
						'x2': '100%',
						'y1': '0%',
						'y2': '0%'
					});
				gradient.selectAll('stop').data(ticks).enter().append('stop')
					.attr({
						'offset': function(d,i) {
							return (i / ticks.length) * 100 + '%';
						},
						'stop-color': function(d) {
							return heatmapFill(heatmapZ(d));
						}
					});

				var heatmapLegend = expatHeatmap.append('g').attr({
					'id': 'heatmap-legend',
					'transform': 'translate(0,' + (expat.heatmap.targetHeight + expat.chart.margin.bottom - 40) + ')'
				});
				heatmapLegend.append('rect')
					.attr({
						'width': expat.chart.innerWidth - expat.dendrogram.row.currentWidth - 25,
						'height': 20
					}).style({
						'fill': 'url(#heatmap-gradient)',
						'stroke': '#333',
						'stroke-width': 1
					});

				heatmapLegend.selectAll('.tick-mark').data(ticks).enter().append('line')
				.attr({
					'x1': function(d, i) {
						return i * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth - 25) / (ticks.length-1));
					},
					'x2': function(d, i) {
						return i * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth - 25) / (ticks.length-1));
					},
					'y1': 0,
					'y2': 20,
					'class': 'tick-mark'
				})
				.style({
					'stroke': '#333',
					'stroke-width': 1,
					'stroke-dasharray': function(d, i) {
						return globalFun.heatmap.filterLegendData({
							data: d,
							index: i,
							value_if_true: '4,12,4',
							value_if_false: '3,14,3',
							force_outer: true,
							length: ticks.length,
							scale: 'linear'
						});
					},
					'stroke-opacity': function(d, i) {
						return globalFun.heatmap.filterLegendData({
							data: d,
							index: i,
							value_if_true: 1,
							value_if_false: 0.25,
							force_outer: false,
							length: ticks.length,
							scale: 'linear'
						});
					}
				});

				heatmapLegend.selectAll('.tick-label').data(ticks).enter().append('text')
				.attr({
					'class': 'tick-label',
					'x': 0,
					'y': 30,
					'transform': function(d, i) { return 'translate(' + (i * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth - 25) / (ticks.length-1))) + ',0)'; }
				})
				.style({
					'font-size': 10,
					'text-anchor': 'middle',
					'fill-opacity': function(d, i) {
						return globalFun.heatmap.filterLegendData({
							data: d,
							index: i,
							value_if_true: 1,
							value_if_false: 0.5,
							force_outer: true,
							length: ticks.length,
							scale: 'linear'
						});
					},
					'font-weight': function(d, i) {
						return globalFun.heatmap.filterLegendData({
							data: d,
							index: i,
							value_if_true: 600,
							value_if_false: 400,
							force_outer: true,
							length: ticks.length,
							scale: 'linear'
						});
					}
				})
				.text(function(d) { return Number(Math.round(d+'e2')+'e-2'); });

				expatHeatmap.append("text")
				.attr({
					'class': 'scale label',
					'x': 0,
					'y': expat.heatmap.targetHeight + expat.chart.margin.bottom - 45
				})
				.style('font-size', 10)
				.text('Relative expression');

				// Gene ID tip
				var geneIDtip = d3.tip()
					.attr('class', 'd3-tip heatmap-gene-id-tip heatmap-tip tip--left')
					.attr('id', 'heatmap-gene-id-tip')
					.offset([0,15])
					.direction('e')
					.html(function(d) {
						return '<p>Retrieving annotation...</p>';
					});

				// Append axes
				var heatmapXAxis = d3.svg.axis()
						.scale(heatmapX)
						.orient('bottom')
						.tickSize(3,0),
					heatmapYAxis = d3.svg.axis()
						.scale(heatmapY)
						.orient('left')
						.tickSize(0);

				expatHeatmap.append('g')
					.attr({
						'class': 'x axis',
						'transform': 'translate(0,'+expat.heatmap.targetHeight+')'
					})
					.call(heatmapXAxis)
					.selectAll('text')
						.style({
							'font-size': 8,
							'text-anchor': 'start'
						})
						.attr({
							'class': 'x axis break',
							'dx': '8',
							'dy': '-2',
							'transform': 'rotate(90)'
						});

				expatHeatmap.append('g')
					.attr('class', 'y axis')
					.call(heatmapYAxis)
					.selectAll('text')
						.style({
							'font-size': 10,
							'text-anchor': 'end'
						})
						.attr({
							'class': 'y axis break id',
							'data-row': function(d) { return d; }
						});

				// Call tip for gene ID search cases only
				var expatGeneAnnoAJAX,
					expatGeneAnnoAJAXTimer = null;
				if(data.rowType === 'Gene ID') {
					expatHeatmap.selectAll('g.y.axis text')
					.call(geneIDtip)
					.on('mouseover', function(d) {
						// Abort old AJAX call
						if(expatGeneAnnoAJAX && expatGeneAnnoAJAX.readyState != 4){
							expatGeneAnnoAJAX.abort();
						}

						// Reset tooltip and show it
						$('#heatmap-gene-id-tip').html('Retrieving information from database&hellip;');
						geneIDtip.show();

						// Perform AJAX call
						expatGeneAnnoAJAX = $.ajax({
							method: 'get',
							url: root + '/api/v1/gene/annotation/v3.0/'+d,
							dataType: 'json'
						});

						// Evaluate promise
						expatGeneAnnoAJAX
						.done(function(d) {
							if(!data.error) {
								if(d.data[0].annotation) {
									$('#heatmap-gene-id-tip').html(d.data[0].annotation);
								} else {
									$('#heatmap-gene-id-tip').html('Annotation unavailable.');
								}
							} else {
								$('#heatmap-gene-id-tip').html('Annotation unavailable.');
							}
						})
						.fail(function() {
							$('#heatmap-gene-id-tip').html('Error in retrieving annotation.');
						});
					})
					.on('mouseout', geneIDtip.hide);
				}
						
				// Manual styles
				expatHeatmap.selectAll('path.domain').attr('stroke', '#333');
				expatHeatmap.selectAll('.x.axis .tick line').attr('stroke', '#333');

				// Append borders
				expatHeatmap.append('svg:line')
					.attr({
						'class': 'border-top',
						'x1': 0,
						'x2': (expat.chart.innerWidth - expat.dendrogram.row.currentWidth),
						'y1': 0,
						'y2': 0
					})
					.style('stroke', '#333');
				expatHeatmap.append('svg:line')
					.attr({
						'class': 'border-right',
						'x1': (expat.chart.innerWidth - expat.dendrogram.row.currentWidth),
						'x2': (expat.chart.innerWidth - expat.dendrogram.row.currentWidth),
						'y1': expat.heatmap.targetHeight,
						'y2': 0
					})
					.style('stroke', '#333');

				// Append cluster separators
				if (data.Mean.clustering) {
					expatHeatmap.selectAll('.cluster-sep.condition')
						.data(globalFun.expat.progressiveArraySum(data.Mean.clustering.data.condition.clusterData))
						.enter()
						.append('line')
							.attr({
								'class': 'cluster-sep condition',
								'x1': function(d) { return d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length); },
								'x2': function(d) { return d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length); },
								'y1': 0,
								'y2': expat.heatmap.targetHeight
							})
							.style({
								'stroke': '#333',
								'stroke-opacity': 0.5,
								'stroke-dasharray': '2,2',
								'opacity': 0
							});
					expatHeatmap.selectAll('.cluster-sep.row')
						.data(globalFun.expat.progressiveArraySum(data.Mean.clustering.data.row.clusterData))
						.enter()
						.append('line')
							.attr({
								'class': 'cluster-sep row',
								'x1': 0,
								'x2': (expat.chart.innerWidth - expat.dendrogram.row.currentWidth),
								'y1': function(d) { return d * (expat.heatmap.targetHeight / order.row.length); },
								'y2': function(d) { return d * (expat.heatmap.targetHeight / order.row.length); },
							})
							.style({
								'stroke': '#333',
								'stroke-opacity': 0.5,
								'stroke-dasharray': '2,2',
								'opacity': 0
							});
				}

				// Dendrogram
				// Define function to construct dendrograms
				globalFun.expat.dendrogram = {
					init: function(opts) {
						// Parse arguments
						var type = opts.type,
							orientation = opts.orientation;

						// Create SVG
						var svg = d3.select('#expat-heatmap').select('svg')
								.attr('font-family', 'Arial')
								.append('g').attr({
								'id': 'expat-dendrogram__'+type,
								'class': 'expat-dendrogram',
								'transform': function() {
									if (orientation === 'vertical') {
										return 'translate('+expat.chart.margin.left+',0)';
									} else {
										return 'translate('+(expat.chart.outerWidth-expat.dendrogram[type].targetWidth)+', '+expat.chart.margin.top+')';
									}
								}
							});

						// Draw
						svg = globalFun.expat.dendrogram.draw(svg, opts, false);

						// Hide first
						svg.style('opacity', 0);

						// Return object
						return svg;
					},
					elbow: function(d, i, orientation, dist_scale) {
						var path;
						if (d.source.dist) {
							if (orientation === 'vertical') {
								path = "M" + (d.source.x) + "," + (dist_scale(d.source.dist)) + "H" + (d.target.x) + "V" + (dist_scale(d.target.dist));
							} else {
								path = "M" + (dist_scale(d.source.dist)) + "," + (d.source.x) + "V" + (d.target.x) + "H" + (dist_scale(d.target.dist));
							}
						}
						return path;
					},
					distanceScale: function(orientation, dist_min, dist_max, height) {
						if (orientation === 'vertical') {
							return d3.scale.pow().exponent(1/2).domain([dist_min, dist_max]).range([height-5, 5]).nice();
						} else {
							return d3.scale.pow().exponent(1/2).domain([dist_min, dist_max]).range([5, height-5]).nice();
						}
					},
					sizeScale: function(size_min, size_max) {
						return d3.scale.linear().domain([size_min, size_max]).range([3,5]);
					},
					draw: function(svg, opts, updating) {
						// Parse arguments
						var type = opts.type,
							orientation = opts.orientation,
							width = (orientation === 'vertical' ? opts.width : opts.height),
							height = (orientation === 'vertical' ? opts.height : opts.width),

							// Clusters
							cluster = d3.layout.cluster()
								.size([width, height])
								.separation(function(a, b) {
									return 1;
								}),

							// Dist ranges
							dist_max = Number.MIN_VALUE,
							dist_min = Number.MAX_VALUE,

							// Cluster size ranges
							size_max = Number.MIN_VALUE,
							size_min = Number.MAX_VALUE,

							// Dendrogram root
							root = data.Mean.clustering.data.dendrogram[type].children[0],

							// Nodes
							nodes = cluster.nodes(root),
							links = cluster.links(nodes);

						// Define min and max distances
						nodes.forEach(function(d) {
							if (d.dist > dist_max) dist_max = d.dist;
							if (d.dist < dist_min) dist_min = d.dist;

							if (d.count > size_max) size_max = d.count;
							if (d.count < size_min) size_min = d.count;
						});

						// Object scales
						var dist_scale = globalFun.expat.dendrogram.distanceScale(orientation, dist_min, dist_max, height),
							size_scale = globalFun.expat.dendrogram.sizeScale(size_min, size_max);

						// Cutoff
						var rowCutoff = dist_scale(dist_max*expat.dendrogram.rowClusterCutoff),
							colCutoff = dist_scale(dist_max*expat.dendrogram.colClusterCutoff);

						// Color scale for nodes
						// Option 1: use generated lab colors
						// 		Lab colors are generated at http://jnnnnn.github.io/category-colors-constrained.html and http://tools.medialab.sciences-po.fr/iwanthue/experiment.php
						// 		expat.dendrogram.nodeFills = expat.d3.vars.randomLABcolors;
						// Option 2: Use color brewer
						//		expat.dendrogram.nodeFills = colorbrewer.Set3[12];
						//		expat.dendrogram.nodeFills = colorbrewer.Spectral[11];
						expat.dendrogram.nodeFills = colorbrewer.Pastel1[9];
						globalFun.expat.dendrogram.computeFill = function(valueIn, valuesIn) {
//							var nodeFill = d3.scale.linear().domain(d3.range(0, 1, 1.0 / (expat.dendrogram.nodeFills.length - 1))).range(expat.dendrogram.nodeFills),
//								nodeFillmap = d3.scale.ordinal().domain(valuesIn).rangeBands([0,1]);
//							return nodeFill(nodeFillmap(valueIn));
							
							// Allow looping back if there are more clusters than available fills
							var i = valueIn - 1;
							if(i >= expat.dendrogram.nodeFills.length) {
								i = valueIn % expat.dendrogram.nodeFills.length;
							}
							return expat.dendrogram.nodeFills[i];
						};

						// Draw background
						// Parse cluster data
						var clusterData = globalFun.expat.progressiveArraySum(data.Mean.clustering.data[type].clusterData),
							clusterID = globalFun.arrayUnique(data.Mean.clustering.data[type].cluster),
							clusterDataParsed = [];
						$.each(clusterData, function(i,v) {
							if(i === 0) {
								clusterDataParsed.push({
									start: 0,
									end: v,
									size: v,
									cluster: clusterID[i]
								});
							} else {
								clusterDataParsed.push({
									start: clusterData[i-1],
									end: v,
									size: v - clusterData[i-1],
									cluster: clusterID[i]
								});
							}
						});
						var end = clusterDataParsed[clusterDataParsed.length-1].end;
						clusterDataParsed.push({
							start: end,
							end: data.Mean[type].length,
							size: data.Mean[type].length - end,
							cluster: clusterID[clusterID.length-1]
						});
						
						// Update cluster background
						svg.selectAll('.cluster-background').transition().duration(1000)
						.attr({
							'x': function(d) {
								if (orientation === 'vertical') {
									return d.start*width/data.Mean[type].length + 1;
								} else {
									return 0;
								}
							},
							'y': function(d) {
								if (orientation === 'vertical') {
									return colCutoff;
								} else {
									return d.start*width/data.Mean[type].length + 1;
								}
							},
							'width': function(d) {
								if (orientation === 'vertical') {
									return d.size*width/data.Mean[type].length - 2;
								} else {
									return rowCutoff;
								}
							},
							'height': function(d) {
								if (orientation === 'vertical') {
									return height - colCutoff;
								} else {
									return d.size*width/data.Mean[type].length - 2;
								}
							}
						})
						.style({
							'fill': function(d) {
								return globalFun.expat.dendrogram.computeFill(d.cluster, data.Mean.clustering.data[type].cluster);
							},
							'pointer-events': 'none'
						});

						// Add cluster background
						var clusterBackground = svg.selectAll('.cluster-background').data(clusterDataParsed);
						clusterBackground.enter()
							.append('rect')
							.attr({
								'class': 'cluster-background',
								'x': function(d) {
									if (orientation === 'vertical') {
										return d.start*width/data.Mean[type].length + 1;
									} else {
										return 0;
									}
								},
								'y': function(d) {
									if (orientation === 'vertical') {
										return colCutoff;
									} else {
										return d.start*width/data.Mean[type].length + 1;
									}
								},
								'width': function(d) {
									if (orientation === 'vertical') {
										return d.size*width/data.Mean[type].length - 2;
									} else {
										return rowCutoff;
									}
								},
								'height': function(d) {
									if (orientation === 'vertical') {
										return height - colCutoff;
									} else {
										return d.size*width/data.Mean[type].length - 2;
									}
								}
							})
							.style({
								'fill': function(d) {
									return globalFun.expat.dendrogram.computeFill(d.cluster, data.Mean.clustering.data[type].cluster);
								},
								'fill-opacity': 0.15,
								'pointer-events': 'none'
							});

						// Remove cluster background
						clusterBackground.exit().transition().duration(1000).style('opacity', 0).remove();

						// Link clusters
						var link = svg.selectAll('.link').data(links);

						// Update links
						link.transition().duration(1000).attr('d', function(d,i) {
							return globalFun.expat.dendrogram.elbow(d, i, orientation, dist_scale);
						});

						// Add links
						link.enter()
							.append('path')
							.attr({
								'class': 'link',
								'd': function(d,i) {
									return globalFun.expat.dendrogram.elbow(d, i, orientation, dist_scale);
								}
							})
							.style({
								'stroke': '#ccc',
								'stroke-width': 1,
								'fill': 'none'
							});

						link.exit().remove();

						// Add nodes
						var node = svg.selectAll('.node').data(nodes);

						// Update nodes
						var nodeUpdate = node.transition().duration(1000)
							.attr({
								'class': function(d) { return d.children ? 'node parent' : 'node leaf'; },
								'transform': function(d) {
									if (orientation === 'vertical') {
										return 'translate(' + d.x + ',' + (dist_scale(d.dist)) + ')';
									} else {
										return 'translate(' + dist_scale(d.dist) + ',' + d.x + ')';
									}
								},
							});
						
						if(updating) {
							svg.selectAll('circle').data(nodes).transition().duration(1000)
							.attr('r', function(d) { return size_scale(d.count); })
							.style({
								'cursor': 'pointer',
								'fill': function(d) {
									if(d.cluster) {
										return globalFun.expat.dendrogram.computeFill(d.cluster, data.Mean.clustering.data[type].cluster);
									} else {
										return '#bbb';
									}
								},
								'stroke': function(d) { return d.children ? '#aaa' : '#333'; }
							});
						}

						// Enter nodes
						if(!updating) {
							// D3 tip
							var tip = d3.tip()
								.attr('class', 'd3-tip '+type+'-dendrogram-tip dendrogram-tip tip--bottom')
								.offset([-15,0])
								.direction('n')
								.html(function(d) {
									var text = '<p class="align-center">' + (d.cluster ? 'Node is in <span style="background-color: '+globalFun.expat.dendrogram.computeFill(d.cluster, data.Mean.clustering.data[type].cluster)+'; display: inline-block; padding: 0 .5rem; border-radius: 4px; ">cluster &numero; '+d.cluster+'</span><br />' : '');
									if(d.name.indexOf('-') > 0) {
										text += 'Parent node containing <strong>'+d.name.split('-').length+' child nodes</strong>.';
									} else {
										text += '<strong>' + d.name + '</strong>';
									}
									text += '</p>';
									return text;
								});

							var nodeEnter = node.enter().append('g')
								.attr({
									'class': function(d) {
										return d.children ? 'node parent' : 'node leaf';
									},
									'transform': function(d) {
										if (orientation === 'vertical') {
											return 'translate(' + d.x + ',' + (dist_scale(d.dist)) + ')';
										} else {
											return 'translate(' + dist_scale(d.dist) + ',' + d.x + ')';
										}
									},
									'data-row': function(d) {
										return d.name;
									}
								})
								.append('circle')
								.attr('r', function(d) { return size_scale(d.count); })
								.style({
									'cursor': 'pointer',
									'fill': function(d) {
										if(d.cluster) {
											return globalFun.expat.dendrogram.computeFill(d.cluster, data.Mean.clustering.data[type].cluster);
										} else {
											return '#bbb';
										}
									},
									'stroke': function(d) { return d.children ? '#aaa' : '#333'; },
									'stroke-width': 1
								})
								.call(tip)
								.on('mouseover', tip.show)
								.on('mouseout', tip.hide);
						}

						// Remove nodes
						var nodeExit = node.exit().transition().duration(1000).style({
							'opacity': 0
						}).remove();

						// Drag
						var bound = function(n, min, max) {
								return Math.max(Math.min(n, max), min);
							},
							drag = d3.behavior.drag()
							.on('drag', function(d, i) {
								var x = {
										condition: 0,
										row: bound(d3.event.x, 5, expat.dendrogram.row.targetWidth - 5)
									},
									y = {
										condition: bound(d3.event.y, 5, expat.dendrogram.condition.targetHeight - 5),
										row: 0
									};
								//var x = bound(d3.event.x, (orientation === 'vertical' ? 0 : 5), (orientation === 'vertical' ? 0 : expat.dendrogram.row.targetWidth - 5)),
								//	y = bound(d3.event.y, (orientation === 'vertical' ? 5 : 0), (orientation === 'vertical' ? expat.dendrogram.condition.targetHeight - 5 : 0));

								// Update position of dragged element
								d3.select(this).attr('transform', 'translate(' + x[type] + ',' + y[type] + ')');

								// Update position of the other cluster cutoff line
//								var otherType = (type === 'row' ? 'condition' : 'row'),
//									t = d3.transform(d3.select(this).attr('transform')).translate,
//									cutoff = (orientation === 'vertical' ? t[1] : t[0]),
//									dist = dist_scale.invert(cutoff),
//									cutoffRatio = bound(dist/dist_max, 0, 0.99),
//									otherCutoff = expat.dendrogram[otherType].dist_scale(expat.dendrogram[otherType].dist_max*cutoffRatio);
//
//								d3.select('#expat-dendrogram__'+otherType).select('line.cluster-cutoff').attr('transform', 'translate('+(otherType === 'row' ? otherCutoff : 0)+','+(otherType === 'row' ? 0 : otherCutoff)+')');
							})
							.on('dragend', function(d, i) {
								// Accessing translate properties
								// Adapted from http://stackoverflow.com/a/26051816/395910
								var t = d3.transform(d3.select(this).attr('transform')).translate,
									cutoff = (orientation === 'vertical' ? t[1] : t[0]),
									dist = dist_scale.invert(cutoff),
									cutoffRatio = bound(dist/dist_max, 0, 0.99);

								// Disable pointer events on element
								d3.selectAll('line.cluster-cutoff').style('pointer-events', 'none');

								// Update cluster cutoff
								$('#'+(orientation === 'vertical' ? 'col' : 'row')+'-cluster-cutoff').val(cutoffRatio.toFixed(5)).trigger('change');
							});

						if(updating) {
							// Update cutoff line
							svg.selectAll('.cluster-cutoff').transition().duration(1000)
							.attr({
								'x1': 0,
								'x2': (orientation === 'vertical' ? width : 0),
								'y1': 0,
								'y2': (orientation === 'vertical' ? 0 : width),
								'transform': 'translate('+(orientation === 'vertical' ? 0 : rowCutoff)+','+(orientation === 'vertical' ? colCutoff : 0)+')'
							})
							.style('pointer-events', 'auto');
						} else {
							// Add cutoff line
							var clusterCutoff = svg.append('line')
								.attr({
									'class': 'cluster-cutoff',
									'x1': 0,
									'x2': (orientation === 'vertical' ? width : 0),
									'y1': 0,
									'y2': (orientation === 'vertical' ? 0 : width),
									'transform': 'translate('+(orientation === 'vertical' ? 0 : rowCutoff)+','+(orientation === 'vertical' ? colCutoff : 0)+')',
								})
								.style({
									'stroke': '#999',
									'stroke-width': 2,
									'stroke-dasharray': '3,1',
									'stroke-opacity': 0.5,
									'pointer-events': 'auto',
									'cursor': (orientation === 'vertical' ? 'ns-resize' : 'ew-resize')
								})
								.call(drag);

							// Draw distance scale
							var axis_orientation = (orientation === 'vertical' ? 'left' : 'bottom'),
								axis_range = (orientation === 'vertical' ? [height-5, 5] : [5, height-5]),
								dist_axis = d3.svg.axis().scale(d3.scale.sqrt().domain([dist_min, dist_max]).range(axis_range)).orient(axis_orientation).tickFormat(function (d) {
									return d;
								}).ticks(5).outerTickSize(0).innerTickSize(5);
							svg.append('g')
							.attr({
								'class': (orientation === 'vertical' ? 'y axis' : 'x axis'),
								'transform': (orientation === 'horizontal' ? 'translate(0,'+width+')' : '')
							})
							.call(dist_axis)
							.selectAll('text')
								.attr({
									'class': (orientation === 'vertical' ? 'y axis break' : 'x axis break'),
								})
								.style({
									'font-size': 8,
									'text-anchor': (orientation === 'vertical' ? 'end' : 'middle')
								});

							// Append axis labels
							var axis_label = svg.append('text');
							if (orientation === 'vertical') {
								axis_label.attr({
									'class': 'y axis label',
									'dx': 0,
									'dy': 0.5*expat.dendrogram.condition.targetHeight - 6
								})
								.style({
									'font-size': 10,
									'text-anchor': 'end'
								});
								axis_label.append('tspan').attr({
									'x': -25
								}).text('Cluster');
								axis_label.append('tspan').attr({
									'x': -25,
									'dy': 12
								}).text('distance');

								// Custom axis styles
								svg.selectAll('.y.axis path.domain').style('stroke', '#333');
								svg.selectAll('.y.axis .tick line').style('stroke', '#333');

							} else {
								axis_label
								.attr({
									'class': 'x axis label',
									'dx': 0,
									'dy': expat.dendrogram.row.targetHeight + 30,
								})
								.style('font-size', 10);
								axis_label.append('tspan').attr({
									'x': 0.5*expat.dendrogram.row.targetWidth
								}).style('text-anchor', 'middle').text('Cluster');
								axis_label.append('tspan').attr({
									'x': 0.5*expat.dendrogram.row.targetWidth,
									'dy': 12
								}).style('text-anchor', 'middle').text('distance');

								// Custom axis styles
								svg.selectAll('.x.axis path.domain').style('stroke', '#333');
								svg.selectAll('.x.axis .tick line').style('stroke', '#333');
							}
						}

						// Expose internal variables
						expat.dendrogram[type].cluster = cluster;
						expat.dendrogram[type].dist_scale = dist_scale;
						expat.dendrogram[type].size_scale = size_scale;
						expat.dendrogram[type].dist_max = dist_max;
						expat.dendrogram[type].dist_min = dist_min;

						// Return dendrogram
						return svg;
					},
					update: function(opts) {
						// Parse arguments
						var type = opts.type,
							orientation = opts.orientation,
							width = (orientation === 'vertical' ? opts.width : opts.height),
							height = (orientation === 'vertical' ? opts.height : opts.width),

							// Cluster object
							cluster = expat.dendrogram[type].cluster.size([width,height]),

							// Update dendrogram data
							root = data.Mean.clustering.data.dendrogram[type].children[0],

							nodes = cluster.nodes(root),
							links = cluster.links(nodes),

							// Dendrogram
							svg = expat.dendrogram[type].obj;

						var node = svg.selectAll('.node').data(nodes).transition().duration(1000),
							link = svg.selectAll('.link').data(links).transition().duration(1000);

						// Redraw
						svg = globalFun.expat.dendrogram.draw(svg, opts, true);

						return svg;
					}
				};

				// Construct only if hierarchical agglomerative clustering is used, because it is the one that generates cluster trees
				if(data.Mean.clustering.meta.type === 'hierarchical agglomerative') {
					// Make concession of dendrogram
					expat.dendrogram.row.targetHeight = expat.heatmap.targetHeight;
					expat.dendrogram.row.targetWidth = Math.min(getDepth(data.Mean.clustering.data.dendrogram.row)*20, 150);

					// Construct dendrograms
					expat.dendrogram.row.obj = globalFun.expat.dendrogram.init({
						type: 'row',
						orientation: 'horizontal',
						width: expat.dendrogram.row.targetWidth,
						height: 20*(data.Mean.row.length)
					});

					expat.dendrogram.condition.obj = globalFun.expat.dendrogram.init({
						type: 'condition',
						orientation: 'vertical',
						width: expat.chart.innerWidth,
						height: expat.dendrogram.condition.targetHeight
					});
				}

				// Link heatmap and linegraph
				var axis = {
					show: function(condition) {
						if(expatLinegraph) $('#expat-linegraph g.x.axis line').eq(condition)[0].classList.add('hover');
					},
					hide: function(condition) {
						if(expatLinegraph) $('#expat-linegraph g.x.axis line').eq(condition)[0].classList.remove('hover');
					}
				};
				$d
				.on('mouseover.expatSuccess', '#expat-heatmap rect.tile', function() {
					// Condition
					var condition = $(this).data('condition');

					// Highlight line
					$('#expat-linegraph').addClass('active');
					var $g = $('#expat-linegraph-group-'+globalFun.expat.replaceIDClass($(this).data('row')));
					if(expatLinegraph) {
						$g[0].classList.add('active');
						$g.appendTo('#expat-linegraph svg > g')
						.find('circle.point[data-condition="'+condition+'"]')[0].classList.add('active');
					}

					// Add hover class
					$(this)[0].classList.add('hover');

					axis.show(condition);
				})
				.on('mouseout.expatSuccess', '#expat-heatmap rect.tile', function() {
					// Condition
					var condition = $(this).data('condition');

					// Un-highlight line
					$('#expat-linegraph').removeClass('active');
					var $g = $('#expat-linegraph-group-'+globalFun.expat.replaceIDClass($(this).data('row')));
					if(expatLinegraph) {
						$g[0].classList.remove('active');
						$g.find('circle.point[data-condition="'+condition+'"]')[0].classList.remove('active');
					}

					// Remove hover class
					$(this)[0].classList.remove('hover');

					axis.hide(condition);
				})
				.on('mouseover.expatSuccess', '#expat-linegraph circle', function() {
					// Highlight rectangle in heatmap
					var row = $(this).closest('g').data('row'),
						condition = $(this).data('condition'),
						$rect = $('#expat-heatmap-rect-'+condition+'-'+row);
					
					$rect[0].classList.add('hover');

					axis.show(condition);
				})
				.on('mouseout.expatSuccess', '#expat-linegraph circle', function() {
					// Un-highlight rectangle in heatmap
					var row = $(this).closest('g').data('row'),
						condition = $(this).data('condition'),
						$rect = $('#expat-heatmap-rect-'+condition+'-'+row);
					
					$rect[0].classList.remove('hover');

					axis.hide(condition);
				})
				.on('mouseover.expatSuccess', '#expat-heatmap .y.axis text, .expat-dendrogram g.node', function() {
					var row = $(this).data('row');
					$('#expat-linegraph').addClass('active');
					if($('#expat-linegraph-group-'+row).length) $('#expat-linegraph-group-'+row)[0].classList.add('active');
				})
				.on('mouseout.expatSuccess', '#expat-heatmap .y.axis text, .expat-dendrogram g.node', function() {
					var row = $(this).data('row');
					$('#expat-linegraph').removeClass('active');
					if($('#expat-linegraph-group-'+row).length) $('#expat-linegraph-group-'+row)[0].classList.remove('active');
				});


				// Allow gene annotation to be displayed when name on graph is clicked on
				// But only when gene ids are queries
				if(data.rowType === 'Gene ID') {
//					$d.on('click', '#expat-heatmap .y.axis text, #expat-linegraph g.row', function() {
//						$('#rows tbody th a.api-gene-annotation[data-gene="'+$(this).data('row')+'"]').trigger('click');
//					});
				}

				// Custom colour palettes
				$d.on('click.expatSuccess', '#expat-colorbrewer-list > li', function() {
					// Update class
					$(this)
					.addClass('selected')
					.siblings().removeClass('selected');

					// Remove class
//					$('#expatLinegraph g.row').attr('class', function(i, classNames) {
//						return classNames.replace(' '+cb, '');
//					});

					// Update colours
					fills = colorbrewer[$(this).attr('data-palette')][$(this).attr('data-count')];
					cb = $(this).attr('data-palette');
					heatmapFill = d3.scale.linear().domain(d3.range(0, 1, 1.0 / (fills.length - 1))).range(fills);

					// Update mapping
					var scale = $('#expat-user-custom-heatmap-scale-color').val();
					if(scale === 'linear') {
						heatmapZ = d3.scale.linear().domain(d3.extent(data.Mean.melted, function(d) { return d.value; })).range([0, 1]);
					} else if(scale === 'square_root') {
						heatmapZ = d3.scale.pow().exponent(1/2).domain(d3.extent(data.Mean.melted, function(d) { return d.value; })).range([0, 1]);
					} else {
						heatmapZ = d3.scale.log().base(10).domain(d3.extent(data.Mean.melted, function(d) { return d.value; })).range([0, 1]);
					}

					// Update heatmap fills
					expatHeatmap.selectAll('rect.tile').transition().duration(500).style('fill', function(d) { return heatmapFill(heatmapZ(d.value)); });

					// Ticks
					var ticks = heatmapZ.ticks();

					// Update heatmap legend
					var gradientStops = expatHeatmap.select('#heatmap-gradient').selectAll('stop').data(ticks)
						.attr({
							'offset': function(d,i) {
								return (i / ticks.length) * 100 + '%';
							},
							'stop-color': function(d) {
								return heatmapFill(heatmapZ(d));
							}
						});

					// Enter
					gradientStops.enter().append('stop')
					.attr({
						'offset': function(d,i) {
							return (i / ticks.length) * 100 + '%';
						},
						'stop-color': function(d) {
							return heatmapFill(heatmapZ(d));
						}
					});

					// Exit
					gradientStops.exit().remove();

				});

				// Custom interpolation
				$d.on('change.expatSuccess', '#expat-user-custom-interpolate', function(e) {
					// Change interpolation and redraw line
					line.interpolate(this.value);
					expatLinegraph.selectAll('path.line').attr('d', function(d) { return line(d.values); });
				});

				// Custom line y-axis scale
				$d.on('change.expatSuccess', '#expat-user-custom-line-scale-y', function() {

					// Determine how to rescale y-axis
					if(this.value === 'square_root') {

						// Change y axis scale
						linegraphY = d3.scale.pow().exponent(0.5).range([expat.linegraph.targetHeight, 0]).domain([
							d3.min(rows, function(g) { return d3.min(g.values, function(v) { return v.value; });}),
							d3.max(rows, function(g) { return d3.max(g.values, function(v) { return v.value; });})
						]).nice();
						
						// Rescale y axis and modify tick format
						linegraphYAxis.scale(linegraphY).tickFormat(function (d) {
							if((d*10)%2 === 0) {
								return d;
							}
						});

					} else if(this.value === 'log_10') {

						// Change y axis scale
						linegraphY = d3.scale.log().base(10).range([expat.linegraph.targetHeight, 0]).domain([
							d3.min(rows, function(g) { return d3.min(g.values, function(v) { return v.value; });}),
							d3.max(rows, function(g) { return d3.max(g.values, function(v) { return v.value; });})
						]).nice();
						
						// Rescale y axis and modify tick format
						linegraphYAxis.scale(linegraphY).tickFormat(function (d) {
							var log = Math.log(d) / Math.LN10;
							return Math.abs(Math.round(log) - log) < 1e-6 ? '10e'+Math.round(log) : '';
						});

					} else {

						// Default is linear
						linegraphY = d3.scale.linear().range([expat.linegraph.targetHeight, 0]).domain([
							d3.min(rows, function(g) { return d3.min(g.values, function(v) { return v.value; });}),
							d3.max(rows, function(g) { return d3.max(g.values, function(v) { return v.value; });})
						]).nice();
						
						// Rescale y axis and modify tick format
						linegraphYAxis.scale(linegraphY).tickFormat(function (d) {
							return d;
						});

					}

					// Update linegraph components
					var tweenLinegraph = expatLinegraph.transition().duration(1000).ease('cubic-in-out');

					tweenLinegraph.selectAll('g.row')
					.attr('transform', 'translate(' + (0.5 * (expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / data.Mean.condition.length) + ',0)');

					tweenLinegraph.selectAll('circle.point')
					.attr('cy', function(d) { return linegraphY(d.value); });

					tweenLinegraph.select('g.y.axis')
					.call(linegraphYAxis)
					.selectAll('text')
						.attr({
							'class': 'y axis break'
						})
						.style({
							'font-size': 10,
							'text-anchor': 'end'
						});

					tweenLinegraph.selectAll('path.line').attr('d', function(d) { return line(d.values); });
				});

				// Custom heatmap colour scale
				$d.on('change.expatSuccess', '#expat-user-custom-heatmap-scale-color', function() {

					// Update colours
					heatmapFill = d3.scale.linear().domain(d3.range(0, 1, 1.0 / (fills.length - 1))).range(fills);

					// Update scale
					var scale = this.value;
					if(scale === 'linear') {
						heatmapZ = d3.scale.linear().domain(d3.extent(data.Mean.melted, function(d) { return d.value; })).range([0, 1]);
					} else if(scale === 'square_root') {
						heatmapZ = d3.scale.pow().exponent(1/2).domain(d3.extent(data.Mean.melted, function(d) { return d.value; })).range([0, 1]);
					} else {
						heatmapZ = d3.scale.log().base(10).domain(d3.extent(data.Mean.melted, function(d) { return d.value; })).range([0, 1]);
					}
					
					// Change fill in heatmap
					expatHeatmap.selectAll('rect.tile').transition().duration(500).style('fill', function(d, i) {
						return heatmapFill(heatmapZ(d.value));
					});

					var ticks = heatmapZ.ticks(10);
					var heatmapLegend = expatHeatmap.select('#heatmap-legend');

					// Update current ticks
					heatmapLegend.selectAll('.tick-mark').data(ticks).transition().duration(500).attr({
						'x1': function(d, i) {
							return i * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth - 25) / (ticks.length-1));
						},
						'x2': function(d, i) {
							return i * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth - 25) / (ticks.length-1));
						},
						'y1': function(d, i) {
							return globalFun.heatmap.filterLegendData({
								data: d,
								index: i,
								value_if_true: 15,
								value_if_false: 16,
								force_outer: true,
								length: ticks.length,
								scale: scale
							});
						}
					})
					.style({
						'stroke-opacity': function(d, i) {
							return globalFun.heatmap.filterLegendData({
								data: d,
								index: i,
								value_if_true: 1,
								value_if_false: 0.25,
								force_outer: false,
								length: ticks.length,
								scale: scale
							});
						}
					});
					
					// Change ticks in heatmap legend
					var heatmapLegendTicks = heatmapLegend.selectAll('.tick-mark').data(ticks);
					heatmapLegendTicks.enter().append('line')
					.attr({
						'x1': function(d, i) {
							return i * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth - 25) / (ticks.length-1));
						},
						'x2': function(d, i) {
							return i * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth - 25) / (ticks.length-1));
						},
						'y1': 0,
						'y2': 20,
						'class': 'tick-mark'
					})
					.style({
						'stroke': '#333',
						'stroke-width': 1,
						'stroke-dasharray': function(d, i) {
							return globalFun.heatmap.filterLegendData({
								data: d,
								index: i,
								value_if_true: '4,12,4',
								value_if_false: '3,14,3',
								force_outer: true,
								length: ticks.length,
								scale: scale
							});
						},
						'stroke-opacity': function(d, i) {
							return globalFun.heatmap.filterLegendData({
								data: d,
								index: i,
								value_if_true: 1,
								value_if_false: 0.25,
								force_outer: false,
								length: ticks.length,
								scale: scale
							});
						},
					});

					// Remove ticks
					heatmapLegendTicks.exit().transition().duration(500).style('opacity', 0).remove();

					
					// Update current labels
					heatmapLegend.selectAll('.tick-label').data(ticks).transition().duration(500)
					.attr({
						'transform': function(d, i) { return 'translate(' + (i * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth - 25) / (ticks.length-1))) + ',0)'; },
					})
					.style({
						'fill-opacity': function(d, i) {
							return globalFun.heatmap.filterLegendData({
								data: d,
								index: i,
								value_if_true: 1,
								value_if_false: 0.5,
								force_outer: true,
								length: ticks.length,
								scale: scale
							});
						},
						'font-weight': function(d, i) {
							return globalFun.heatmap.filterLegendData({
								data: d,
								index: i,
								value_if_true: 600,
								value_if_false: 400,
								force_outer: true,
								length: ticks.length,
								scale: scale
							});
						}
					})
					.text(function(d) { return d; });
//					.tween('text', function(d) {
//						// Adapted from http://stackoverflow.com/a/13459556/395910
//						var i = d3.interpolate(this.textContent, Number(Math.round(d+'e2')+'e-2')),
//							prec = (d + "").split("."),
//							round = (prec.length > 1) ? Math.pow(10, prec[1].length) : 1;
//
//						return function(t) {
//							this.textContent = Math.round(i(t) * round) / round;
//						};
//					});

					// Add new labels
					var heatmapLegendLabels = heatmapLegend.selectAll('.tick-label').data(ticks);
					heatmapLegendLabels.enter().append('text')
					.attr({
						'class': 'tick-label',
						'font-size': 10,
						'x': 0,
						'y': 30,
						'text-anchor': 'middle',
						'transform': function(d, i) { return 'translate(' + (i * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth - 25) / (ticks.length-1))) + ',0)'; },
					})
					.style({
						'fill-opacity': function(d, i) {
							return globalFun.heatmap.filterLegendData({
								data: d,
								index: i,
								value_if_true: 1,
								value_if_false: 0.5,
								force_outer: true,
								length: ticks.length,
								scale: scale
							});
						},
						'font-weight': function(d, i) {
							return globalFun.heatmap.filterLegendData({
								data: d,
								index: i,
								value_if_true: 600,
								value_if_false: 400,
								force_outer: true,
								length: ticks.length,
								scale: scale
							});
						},
						'opacity': 0
					})
					.text(function(d) { return d; })
					.transition().delay(250).duration(500)
					.style('opacity', 1);
//					.tween(function(d) {
//						// Adapted from http://stackoverflow.com/a/13459556/395910
//						var i = d3.interpolate(this.textContent, Number(Math.round(d+'e2')+'e-2')),
//							prec = (d + "").split("."),
//							round = (prec.length > 1) ? Math.pow(10, prec[1].length) : 1;
//
//						return function(t) {
//							this.textContent = Math.round(i(t) * round) / round;
//						};
//					});
					

					// Remove old labels
					heatmapLegendLabels.exit().transition().duration(500).style('opacity', 0).remove();
					
				});

				// Select all in sorting field
				$d.on('click.expatSuccess', '.expat-user-custom-sort', function(e) { $(this).focus().select(); });

				// Allow sorting
				var selectiveSearchUpdate = function(idType) {
						var checked = $('#rows tbody input.'+idType+'[type="checkbox"]:checked').map(function() {
								return $(this).val();
							}).get(),
							$button = $('#rows tfoot th.'+idType+' button');

						$button.prop('disabled', true).off('click');

						if(checked.length > 0) {
							$('#rows tfoot th.'+idType+' button').prop('disabled', false).on('click', function() {
								window.location.href = './?t=6&dataset='+idType.toLowerCase().replace(' ','')+'&ids='+checked.join(',');
							});
						} else {

						}
					},
				sortupdate = function() {
					var v = $(this).attr('data-variable');
					order[v] = $('#expat-sort-list-'+v+' li').map(function() {
							return $(this).attr('data-variable');
						}).get();
					var orderString = order[v].join(',');

					// Update hidden value (for CSV download) and update custom sorting order (if users want to save it for later)
					$('#expat-download-'+v+', #expat-user-custom-sort-by-'+v).val(orderString);

					// Update search form values
					if(v === 'row') {
						$('#expat-row').val(order[v].join('\n'));
					} else if (v === 'condition') {
						$('#expat-condition').val(orderString);
					}

					// Update table layout
					if(v === 'row') {
						$.each(order[v], function(i,r) {
							$('#rows').find('tbody tr[data-row="'+r+'"]').appendTo('#rows tbody');
						});
					} else if (v === 'condition') {
						$.each(order[v], function(i,c) {
							$('#rows').find('thead tr td[data-condition="'+c+'"]').appendTo('#rows thead tr');
							$('#rows').find('tbody tr').each(function() {
								$(this).find('td[data-condition="'+c+'"]').appendTo($(this));
							});
						});
					}

					// Animate items in heatmap
					var tweenHeatmap = expatHeatmap.transition().duration(1000).ease('cubic-in-out');
					tweenHeatmap.selectAll('rect.tile')
					.attr({
						'x': function(d) { return order.condition.indexOf(d.condition) * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length); },
						'y': function(d) { return order.row.indexOf(d.rowID) * (expat.heatmap.targetHeight / order.row.length); },
						'width': (expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length
					});
					tweenHeatmap.selectAll('.x.axis .tick')
					.attr({
						'transform': function(d) { return 'translate(' + (order.condition.indexOf(d) + 0.5) * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length) + ',0)'; }
					});
					tweenHeatmap.selectAll('.y.axis .tick')
					.attr({
						'transform': function(d) { return 'translate(0,' + (order.row.indexOf(d) + 0.5) * (expat.heatmap.targetHeight / order.row.length)+ ')'; }
					});
					tweenHeatmap.selectAll('.border-top')
					.attr({
						'x2': (expat.chart.innerWidth - expat.dendrogram.row.currentWidth)
					});
					tweenHeatmap.selectAll('.border-right')
					.attr({
						'x1': (expat.chart.innerWidth - expat.dendrogram.row.currentWidth),
						'x2': (expat.chart.innerWidth - expat.dendrogram.row.currentWidth)
					});
					tweenHeatmap.selectAll('.x.axis .domain')
					.attr({
						'd': 'M0,0 V0 H'+(expat.chart.innerWidth - expat.dendrogram.row.currentWidth)+' V0'
					});

					// Animate items in chart
					// 1. Since the order of genes is not important, we trigger this only when conditions are reshuffled
					// 2. Or we trigger when dendrogram is being toggled
					if(expatLinegraph && (v === 'condition' || data.Mean.clustering.meta.type === 'hierarchical agglomerative')) {
						var tweenLinegraph = expatLinegraph.transition().duration(1000).ease('cubic-in-out');

						tweenLinegraph.selectAll('g.row')
						.attr('transform', 'translate(' + (0.5 * (expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / data.Mean.condition.length) + ',0)');

						tweenLinegraph.selectAll('circle.point')
						.attr({
							'cx': function(d) { return order.condition.indexOf(d.condition) * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length); }
						});
						tweenLinegraph.selectAll('.x.axis .tick')
						.attr({
							'transform': function(d) { return 'translate(' + ((order.condition.indexOf(d) + 0.5) * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length)) + ',0)'; }
						});

						// Reshuffle genes
						var newRows = [];
						$.each(rows, function(i,row) {
							var newRow = new Array(data.Mean.condition.length);
							$.each(row.values, function(j, value) {
								newRow[order.condition.indexOf(value.condition)] = {
									condition: value.condition,
									value: value.value
								};
							});
							newRows.push({
								name: row.name,
								values: newRow
							});
						});

						// Line
						// Rescale x-axis
						linegraphX = d3.scale.ordinal().rangeBands([0, expat.chart.innerWidth - expat.dendrogram.row.currentWidth]);

						// Scales
						line = d3.svg.line()
							.interpolate('monotone')
							.x(function(d) { return linegraphX(d.condition); })
							.y(function(d) { return linegraphY(d.value); });
						
						// Domains
						linegraphX.domain(order.condition);
						linegraphY.domain([
							d3.min(newRows, function(g) { return d3.min(g.values, function(v) { return v.value; });}),
							d3.max(newRows, function(g) { return d3.max(g.values, function(v) { return v.value; });})
						]).nice();

						// Redraw paths
						expatLinegraph.selectAll('path.line').each(function(d,i) {
							// Do not rely on index (i), double check gene against known array
							var row = $(this).closest('g.row').attr('data-row');
							d3.select(this)
							.datum(newRows[data.Mean.row.indexOf(row)])
							.transition().duration(1000).ease('cubic-in-out')
							.attr({
								'd': function(d) { return line(d.values); }
							});
						});

						// Rescale y axis and modify tick format
						linegraphYAxis.innerTickSize(-(expat.chart.innerWidth - expat.dendrogram.row.currentWidth));

						// Redraw axis ticks
						tweenLinegraph.select('g.y.axis')
						.call(linegraphYAxis)
						.selectAll('text')
							.attr({
								'class': 'y axis break'
							})
							.style({
								'font-size': 8,
								'text-anchor': 'end'
							});

					}
				};

				// Sortable conditions
				$('#expat-sort-list-condition, #expat-sort-list-row')
				.sortable({
					placeholder: 'ui-state-highlight',
					activate: function() {
						$(this).addClass('ui-state-active');
					},
					deactivate: function() {
						$(this).removeClass('ui-state-active');
					},
					update: function() {
						// Current variable
						var dataVar = $(this).data('variable');

						//expatHeatmap.selectAll('line.cluster-sep.'+dataVar).transition().delay(1000).duration(250).attr('opacity', 0);
						//if(expatLinegraph) expatLinegraph.selectAll('line.cluster-sep.'+dataVar).transition().delay(1000).duration(250).attr('opacity', 0);

						// Update sort
						sortupdate.call(this);

						// Remove clustering
						$('.expat-user-custom-cluster-toggle[data-variable="'+dataVar+'"]').prop('checked', false).trigger('manualchange');
					}
				})
				.on('manualsortupdate.expatSuccess', sortupdate);

				$d
				// Clustering
				.on('change.expatSuccess manualchange.expatSuccess', '.expat-user-custom-cluster-toggle', function(e) {
					var v = $(this).data('variable'),
						newData,
						$row = $('#expat-user-custom-cluster-by-row'),
						$col = $('#expat-user-custom-cluster-by-condition');

					// Update cluster separators in heatmap
					var clusterUpdate = function(v, currentV) {
						var heatmapClusterSep = expatHeatmap.selectAll('.cluster-sep.'+v).data(globalFun.expat.progressiveArraySum(data.Mean.clustering.data[v].clusterData));

						// Update old elements
						heatmapClusterSep.transition().duration(1000)
						.attr({
							'x1': function(d) { return (v === 'condition' ? d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length) : 0); },
							'x2': function(d) { return (v === 'condition' ? d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length) : (expat.chart.innerWidth - expat.dendrogram.row.currentWidth)); },
							'y1': function(d) { return (v === 'row' ? d * (expat.heatmap.targetHeight / order.row.length) : 0); },
							'y2': function(d) { return (v === 'row' ? d * (expat.heatmap.targetHeight / order.row.length) : expat.heatmap.targetHeight); }
						})
						.style({
							'opacity': (currentV.indexOf(v) > -1 ? 1 : 0)
						});

						// Create new elements as needed
						heatmapClusterSep.enter()
						.append('line')
							.attr({
								'class': 'cluster-sep '+v,
								'x1': function(d) { return (v === 'condition' ? d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length) : 0); },
								'x2': function(d) { return (v === 'condition' ? d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length) : (expat.chart.innerWidth - expat.dendrogram.row.currentWidth)); },
								'y1': function(d) { return (v === 'row' ? d * (expat.heatmap.targetHeight / order.row.length) : 0); },
								'y2': function(d) { return (v === 'row' ? d * (expat.heatmap.targetHeight / order.row.length) : expat.heatmap.targetHeight); }
							})
							.style({
								'stroke': '#333',
								'stroke-opacity': 0.5,
								'stroke-dasharray': '2,2',
								'opacity': 0
							})
							.transition().duration(1000)
							.attr({
								'x1': function(d) { return (v === 'condition' ? d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length) : 0); },
								'x2': function(d) { return (v === 'condition' ? d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length) : (expat.chart.innerWidth - expat.dendrogram.row.currentWidth)); },
								'y1': function(d) { return (v === 'row' ? d * (expat.heatmap.targetHeight / order.row.length) : 0); },
								'y2': function(d) { return (v === 'row' ? d * (expat.heatmap.targetHeight / order.row.length) : expat.heatmap.targetHeight); }
							})
							.style({
								'opacity': (currentV.indexOf(v) > -1 ? 1 : 0)
							});

						// Remove old elements as needed
						heatmapClusterSep.exit().transition().duration(1000).style({
							'opacity': 0
						}).remove();

						// Update cluster separators in line graph
						if (expatLinegraph && v === 'condition') {
							var linegraphClusterSep = expatLinegraph.selectAll('.cluster-sep.'+v).data(globalFun.expat.progressiveArraySum(data.Mean.clustering.data[v].clusterData));

							// Update old elements
							linegraphClusterSep.transition().duration(1000)
							.attr({
								'x1': function(d) { return d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length); },
								'x2': function(d) { return d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length); },
								'y1': 0,
								'y2': expat.linegraph.targetHeight
							})
							.style({
								'opacity': (currentV.indexOf(v) > -1 ? 1 : 0)
							});

							// Create new elements as needed
							linegraphClusterSep.enter()
							.append('line')
								.attr({
									'class': 'cluster-sep '+v,
									'x1': function(d) { return d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length); },
									'x2': function(d) { return d * ((expat.chart.innerWidth - expat.dendrogram.row.currentWidth) / order.condition.length); },
									'y1': 0,
									'y2': expat.linegraph.targetHeight
								})
								.style({
									'stroke': '#333',
									'stroke-opacity': 0.5,
									'stroke-dasharray': '2,2',
									'opacity': 0
								})
								.transition().duration(1000).style({
									'opacity': (currentV.indexOf(v) > -1 ? 1 : 0)
								});

							// Remove old elements as needed
							linegraphClusterSep.exit().transition().duration(1000).style({
								'opacity': 0
							}).remove();
						}
					};

					// vOn
					var vOn = $(this).closest('fieldset').find('input.expat-user-custom-cluster-toggle').filter(function() {
						return $(this).is(':checked');
					}).map(function() {
						return $(this).data('variable');
					}).get();

					if($(this).prop('checked') && data.Mean.clustering && data.Mean.clustering.data[v].order !== undefined) {

						// If clustering is turned on and clustering data is available
						newData = data.Mean.clustering.data[v].order;
						order[v] = data.Mean.clustering.data[v].order;

						// Update clusters
						clusterUpdate('row', vOn);

						// Dendrogram functions
						if(data.Mean.clustering.meta.type === 'hierarchical agglomerative') {
							// Adjust dendrogram width
							expat.dendrogram[v].currentWidth = (data.Mean.clustering.meta.type === 'hierarchical agglomerative' ? expat.dendrogram[v].targetWidth : 0);

							// Show dendrograms
							window.setTimeout(function() {
								expat.dendrogram[v].obj.style('opacity', 1);
							}, (v === 'row' ? 1000 : 0));
						}
					} else {

						// If clusteirng is turned off
						newData = data.Mean[v];
						order[v] = data.Mean[v];

						// Hide cluster separators
						expatHeatmap.selectAll('line.cluster-sep.'+v).transition().delay(750).duration(250).style('opacity', 0);
						if(expatLinegraph) expatLinegraph.selectAll('line.cluster-sep.'+v).transition().delay(750).duration(250).style('opacity', 0);

						// Dendrogram functions
						if(data.Mean.clustering.meta.type === 'hierarchical agglomerative') {
							// Adjust dendrogram width
							expat.dendrogram[v].currentWidth = 0;

							// Hide dendrograms
							expat.dendrogram[v].obj.style('opacity', 0);
						}
					}

					// If condition clustering is enabled, make space for dendrogram
					if ($col.prop('checked')) {
						
						// Dendrogram functions
						if(data.Mean.clustering.meta.type === 'hierarchical agglomerative') {
							d3.select('#expat-heatmap').select('svg').attr({
								'viewBox': '0 0 ' + expat.chart.outerWidth + ' ' + (expat.heatmap.targetHeight + expat.chart.margin.top + expat.chart.margin.bottom + expat.dendrogram.condition.targetHeight)
							});

							// Move heatmap down
							d3.select('#expat-heatmap__wrapper').attr({
								'transform': 'translate(' + expat.chart.margin.left + ',' + (expat.chart.margin.top + expat.dendrogram.condition.targetHeight) + ')',
								'width': expat.chart.innerWidth - expat.dendrogram.row.targetWidth
							});

							// Move row dendrogram down
							expat.dendrogram.row.obj.attr({
								'transform': 'translate('+(expat.chart.outerWidth-expat.dendrogram.row.targetWidth)+','+(expat.dendrogram.condition.targetHeight+expat.chart.margin.top)+')'
							});

							// Update dendrogram
							globalFun.expat.dendrogram.update({
								type: 'condition',
								orientation: 'vertical',
								width: expat.chart.innerWidth - expat.dendrogram.row.currentWidth,
								height: expat.dendrogram.condition.targetHeight
							});

							// Mark dendrogrma as active
							if($('#expat-dendrogram__condition').length) $('#expat-dendrogram__condition')[0].classList.add('active');
						}
						
						// Update cluster separators
						clusterUpdate('condition', vOn);

					} else {
						d3.select('#expat-heatmap').select('svg').attr({
							'viewBox': '0 0 ' + expat.chart.outerWidth + ' ' + (expat.heatmap.targetHeight + expat.chart.margin.top + expat.chart.margin.bottom)
						});

						// Move heatmap up
						d3.select('#expat-heatmap__wrapper').attr({
							'transform': 'translate(' + expat.chart.margin.left + ',' + expat.chart.margin.top + ')',
							'width': expat.chart.innerWidth - expat.dendrogram.row.targetWidth
						});

						// Dendrogram functions
						if(data.Mean.clustering.meta.type === 'hierarchical agglomerative') {
							// Move row dendrogram up
							expat.dendrogram.row.obj.attr({
								'transform': 'translate('+(expat.chart.outerWidth-expat.dendrogram.row.targetWidth)+','+expat.chart.margin.top+')'
							});
						}

						// Mark dendrogrma as inactive
						if($('#expat-dendrogram__condition').length) $('#expat-dendrogram__condition')[0].classList.remove('active');
					}

					// If row clustering is enabled
					if ($row.prop('checked') && data.Mean.clustering.meta.type === 'hierarchical agglomerative') {
						// Update dendrogram
						globalFun.expat.dendrogram.update({
							type: 'row',
							orientation: 'horizontal',
							width: expat.dendrogram.row.targetWidth,
							height: expat.dendrogram.row.targetHeight
						});

						// Mark dendrogrma as active
						if($('#expat-dendrogram__row').length) $('#expat-dendrogram__row')[0].classList.add('active');

						// Adjust cluster separator width
						clusterUpdate('row', ['row']);
					} else {
						// Mark dendrogrma as inactive
						if($('#expat-dendrogram__row').length) $('#expat-dendrogram__row')[0].classList.remove('active');
					}

					// Re-sort jQuery UI sortable
					// Prevent circular reference if manualchange is triggered
					if(e.type !== 'manualchange') {
						$.each(newData, function(i,c) {
							$('#expat-sort-list-'+v).find('li[data-variable="'+c+'"]').appendTo($('#expat-sort-list-'+v));
						});

						$('#expat-sort-list-'+v).trigger('manualsortupdate');
					}
				})
				// Clustering fine-tuning
				.on('change.expatSuccess', '.expat-user-custom-cluster-config', function(e) {

					var clusterConfig = {
						'dataTransform': expat.params.data_transform
					};
					$.each($('.expat-user-custom-cluster-config').serializeArray(), function() {
						clusterConfig[this.name] = this.value;
					});

					// Perform sanity check
					if ((
						!isNaN(parseFloat(clusterConfig.rowClusterCutoff)) &&
						isFinite(clusterConfig.rowClusterCutoff) &&
						!isNaN(parseFloat(clusterConfig.colClusterCutoff)) &&
						isFinite(clusterConfig.colClusterCutoff)
						) === false) {
						$('#expat-user-custom-cluster-configs .user-message').removeClass('note').addClass('warning').html('You have used a non-numeric cutoff.').show();
						return false;
					} else if (clusterConfig.rowClusterCutoff < 0 || clusterConfig.rowClusterCutoff > 1 || clusterConfig.colClusterCutoff < 0 || clusterConfig.colClusterCutoff > 1 ) {
						$('#expat-user-custom-cluster-configs .user-message').removeClass('note').addClass('warning').html('Please ensure your cuttoff is between the values of 0 and 1 (inclusive).').show();
						return false;
					}

					// Store cutoffs somewhere
					expat.dendrogram.rowClusterCutoff = clusterConfig.rowClusterCutoff;
					expat.dendrogram.colClusterCutoff = clusterConfig.colClusterCutoff;

					// Perform ajax call
					var updateClustering = $.ajax({
						url: root + '/api/v1/expat/clustering',
						type: 'POST',
						data: {
							melted: JSON.stringify(data.Mean.melted),
							row: data.Mean.row,
							condition: data.Mean.condition,
							config: clusterConfig
						},
						dataType: 'json'
					});

					var clusterStartTime = new Date();

					// Disable further input and wait for promise
					$('html, body').css('cursor', 'wait');
					$('#expat-user-custom-cluster-configs :input').prop('disabled', true);
					$('#expat-user-custom-cluster-configs .user-message').removeClass('warning').addClass('note').html('<div class="loader"><svg viewbox="0 0 80 80"><circle class="path" cx="40" cy="40" r="30"/></svg></div> Performing clustering analysis on the server&hellip;').show();
					updateClustering
					.done(function(newClustering) {
						// Overwrite clustering data is existing data object
						data.Mean.clustering = newClustering.data;

						// Pop the last item off clusters
						if (data.Mean.clustering) {
							data.Mean.clustering.data.condition.clusterData.pop();
							data.Mean.clustering.data.row.clusterData.pop();

							// Update cluster count
							var clusterLengths = [];
							$('#expat-user-custom-cluster-toggle span.cluster-count').each(function() {
								var v = $(this).data('variable'),
									clusterLength = data.Mean.clustering.data[v].clusterData.length + 1;

								clusterLengths.push(clusterLength);

								$(this).text(clusterLength);

								if(clusterLength > 1) {
									$('.expat-user-custom-cluster-toggle[data-variable="'+v+'"]').prop('checked', true).prop('disabled', false).trigger('change');
								} else {
									$('.expat-user-custom-cluster-toggle[data-variable="'+v+'"]').prop('checked', false).prop('disabled', true).trigger('change');
								}
							});

							// Show message
							$('#expat-user-custom-cluster-configs .user-message').removeClass('warning').addClass('note').html('Clustering completed in <strong>'+(new Date() - clusterStartTime)+'ms</strong>.');

							if(Math.min.apply(null, clusterLengths) > 1) {
								// Hide clustering disabled message
								$('#expat-user-custom-cluster-toggle .user-message').hide();
							} else {
								// Show clustering disabled message
								$('#expat-user-custom-cluster-toggle .user-message').removeClass('note warning').addClass('reminder').html('Clustering in one or more axes disabled due to presence of a single group/cluster.').show();
							}
						}
					}).fail(function(jqXHR, textError, status) {
						// Notify that clustering option is not valid
						$('#expat-user-custom-cluster-configs .user-message').removeClass('note').addClass('warning').html('The combination of clustering configuration options are not valid. Please try other combinations.');
					})
					.always(function() {
						// Re-enable
						$('html, body').css('cursor', 'auto');
						$('#expat-user-custom-cluster-configs :input').prop('disabled', false);
					});

				})
				// Update search selected links
				.on('change.expatSuccess', '#rows th input[type="checkbox"]', function() {
					selectiveSearchUpdate($(this).attr('class'));
				})
				// Turn off form submission in user customization
				.on('submit.expatSuccess', '#expat-user-custom', function(e) {
					e.preventDefault();
				});

				// Remove loader
				$('#expat-message').slideUp(250, function() {
					// Remove loading class
					$(this).removeClass('loading-message');

					// Scroll to results
					$('html,body').animate({
						scrollTop: $('#expat-results').offset().top
					}, 500);
				});

			} else {
				expat.status.searched = false;

				// Empty results
				$('#expat-results').empty();

				// Unwrap
				$t.prevAll().remove();
				$t.unwrap().slideDown(250);

				// Remove loader
				$('#expat-message').removeClass().addClass('warning user-message').html(d.message);
			}
		}).fail(function() {
			expat = expat;
			expat.status.searched = false;

			// Empty results
			$('#expat-results').empty();

			// Unwrap
			$t.prevAll().remove();
			$t.unwrap().slideDown(250);

			// Remove loader
			$('#expat-message').removeClass().addClass('warning user-message').html('We have experienced an error with our backend. Should the problem persists, please contact the system administrator.');
		});
	});

	// Listen to back button
	$w.on('popstate', function(e) {
		expat.params = globalFun.parseURLquery();
		if(e.originalEvent.state !== null) {
			expat.status.pushState = false;
			if(expat.params) {
				if(expat.params.ids && expat.params.dataset) {
					// Update inputs
					$('#expat-row').val(expat.params.ids).trigger('manualchange');
					$('#expat-dataset').val(expat.params.dataset).trigger('change');

					// Submit form
					window.setTimeout(function() {
						expat.status.searched = false;
						$('#expat-form').trigger('submit');
					}, 500);
				} else {
					$('#expat-message').removeClass().addClass('warning user-message').html('You have provided incomplete information to perform a search. Please check that you have provided both an a probe/gene ID and a dataset.').slideDown();
				}
			}
		} else {
			if(expat.status.searched) {
				// Empty results
				$('#expat-results').empty();

				// Reset inputs
				$('#expat-row').val('').trigger('manualchange');
				$('#expat-dataset > option').first().prop('selected', true).trigger('change');

				// Unwrap
				$('#expat-form').prevAll().remove();
				$('#expat-form').unwrap().slideDown(250);

				// Remove loader
				$('#expat-message').removeClass().hide();
			}
		}
	});

	// Allow users to download raw data
	$('#expat-form').on('click', '#download-raw-data', function() {
		// Validate first
		var validator = $('#expat-form').validate({
			ignore: [],
			rules: {
				'ids': 'required',
				'dataset': 'required'
			},
			messages: {
				'ids': 'Please enter one or more accession(s).',
				'dataset': 'Please select a dataset.'
			}
		});

		// Error handling for invalid ID
		if (!validator.element('#expat-row')) {
			$('#expat-row').closest('.input-mimic').addClass('error');
		} else {
			$('#expat-row').closest('.input-mimic').removeClass('error');
		}

		// Quit if validation fails
		if(!$('#expat-form').valid()) {
			return false;
		} else {
			// Redirect to download form
			$('#expat-form').attr('action', 'expat-download');
			$('#expat-form')[0].submit();
		}
	});

	if(!$.isEmptyObject(expat.params)) {
		// Update ids
		if(expat.params.ids) {
			$('#expat-row').val(expat.params.ids).trigger('manualchange');
		}

		// Update dataset
		if(expat.params.dataset) {
			$('#expat-dataset option[value="'+expat.params.dataset+'"]').trigger('change');
		}

		// Submit form
		window.setTimeout(function() {
			$('#expat-form').trigger('submit');
		}, 500);
	}
});