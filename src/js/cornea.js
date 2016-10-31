$(function() {
	var sigmaInst,
		searchedForNode,
		highlightedNode,				// Node that is highlighted by click
		customHighlightedNodes = [],	// Array of nodes, containing nodes that are highlighted by user input (text input, or CSV upload)
		pollInterval = 120,
		countdownTimer,
		pollTimer,
		currentStatus,
		previousStatus;

	// Access URL parameters
	var params = globalFun.parseURLquery();

	// If job ID is provided in the form of a subdirectory URL, we try to catch that, too
	var jobID = params.job || window.location.href.split('/').pop().replace(/^(.*?)#.*/gi, '$1').replace(/[\s\-]/gi, ''),
		paramSource = (params.job ? 'request' : (window.location.href.indexOf('/job/') >= 0 ? 'url' : false));

	// jQuery UI tabs
	$( "#cornea-tabs" ).tabs();

	// Add RegEx validation method
	$.validator.addMethod(
		"regex",
		function(value, element, regexp) {
			var re = new RegExp(regexp);
			return this.optional(element) || re.test(/\s+/gi.replace(value));
		},
		"Please check your input."
	);

	// Validate CORNEA form
	$.extend(globalVar, {
		cornea: {
			jobSearchForm: [
				'<form action="#" method="get" id="cornea-form__status">',
					'<div class="cols" role="group">',
						'<label for="cornea-job-id" class="col-one">Job ID <a href="'+(paramSource === 'request' ? '..' : '../../..')+'/lib/docs/cornea/job-id" data-modal class="info" title="What is a job ID?">?</a></label>',
						'<input type="text" class="col-two" id="cornea-job-id" name="job_id" placeholder="Enter the job ID here." required />',
					'</div>',
					'<div class="cols justify-content__center">',
						'<button type="submit" role="primary"><span class="pictogram icon-search">Search again</span></button>',
					'</div>',
				'</form>'
				].join(''),
			validator: {
				submit: $('#cornea-form__submit').validate({
					ignore: [],
					rules: {
						threshold: {
							required: true,
							range: [0.75,0.9999999999]
						},
						clustersize: {
							required: true,
							min: 5
						},
						dataset: { required: true }
					},
					errorPlacement: function(error, element) {
						var $e = element;
						if($e.attr('id') === 'expat-dataset') {
							$e.siblings('.select2')
								.addClass('error')
								.after(error);
						} else {
							error.insertAfter(element);
						}
					}
				}),
				status: $('#cornea-form__status').validate({
					ignore: [],
					rules: {
						job: {
							required: true,
							regex: '^(standard_)?[A-Fa-f0-9]{32}$'
						}
					},
					messages: {
						job: 'Please provide a 32-character hexadecimal hash. It is found in the bookmarked URL as <code>'+window.location.href.split('#')[0]+'/job={hash}</code>'
					}
				})
			}
		},
		keys: {},
		sigma: {
			node: {
				color: {
					default: '#5ca4a9',
					inactive: '#bbb',
					highlighted: '#33658a',
					highlightedNeighbours: '#5ca4a9'
				}
			},
			edge: {
				color: {
					default: '#ccc',
					inactive: '#ccc'
				}
			}

		}
	});

	// Extend global functions
	$.extend(globalFun, {
		cornea: {
			init: function() {
				// Check for FileReader support
				if(typeof window.FileReader === typeof undefined) {
					$('#upload-job').empty().html('<p class="user-message warning"><span class="icon-attention"></span> Your browser does not support the <a href="http://caniuse.com/#feat=filereader"><code>FileReader</code> specification</a>, which means we are unable to parse uploaded file with your browser. We recommending using a modern, standards-compliant browser so that all features of <em>Lotus</em> Base can be accessed.</p>');
				} else {
					globalFun.cornea.dropzone.init();
				}
			},
			dropzone: {
				init: function() {

					// Drop zone for CORNEA jobs
					$d
					.on('dragover', '.dropzone', function(e) {
						e.preventDefault();
						e.stopPropagation();
						e.originalEvent.dataTransfer.dropEffect = 'copy';

						// Hide sigma tooltip
						$('#sigma-tooltip').removeClass().empty();
					})
					.on('dragenter', '.dropzone', function(e) {
						e.preventDefault();
						e.stopPropagation();

						// Hide sigma tooltip
						$('#sigma-tooltip').removeClass().empty();

						// Display overlay, and set progress to 0
						$(this).find('.dropzone__message--normal .dropzone__progress span').css('width', 0);
						globalFun.cornea.dropzone.showMessage.call(this, '.dropzone__message--normal', false);
					})
					.on('drop', '.dropzone', function(e) {
						e.stopPropagation();
						e.preventDefault();

						// Get type
						var type = $(this).attr('data-dropzone-type');

						// Fire file upload handler
						globalFun.cornea.dropzone.fileHandler[type].call(this, e.originalEvent.dataTransfer.files);
					})

					// Fallback for people who use input[type='file']
					.on('change', '.dropzone input[type="file"]', function(e) {
						// Dropzone
						var $dz = $(this).closest('.dropzone');

						// Fire file upload handler
						globalFun.cornea.dropzone.fileHandler[$dz.attr('data-dropzone-type')].call($dz[0], e.originalEvent.target.files);
					});

				},
				fileHandler: {
					// File hanlder for job uploads
					job: function(files) {
						var t = this,
							$t = $(t);

						// Get some data for validation
						var opts = {
							'fileType': $t.find('.dropzone__input-file').attr('accept').replace(/\s+/gi, '').split(',') || [],
							'maxFileSize': 100 * 1024 * 1024
						};

						// Retrieve FileList
						var f;
						if(files.length > 1) {
							globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__message-multi', true);
							return;
						} else if(files.length === 0) {
							globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__message--none', true);
							return;
						} else {
							f = files[0];
							if(f.size > opts.maxFileSize) {
								globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__mesage--large', true);
								return;
							}
						}

						// Check MIME type
						if(opts.fileType.length > 0 && opts.fileType.indexOf(f.type) < 0) {
							console.warn('You have provided an invalid filetype.');
							globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__message--invalid-filetype', true, function() {
								$(this).find('p').first().append('<br /><span class="additional-content">You have provided a file with the MIME type: <code>'+f.type+'</code></span>.');
							});
							return;
						}

						// Recreate DOM required for Sigma
						$('#sigma-output').remove();
						$('#upload-job__drop').after('<div id="sigma-output"><div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div><h2 id="sigma-status--short">Reading uploaded file from disk</h2><span class="byline align-center">Please wait&hellip;</span><div id="sigma-status--long"></div></div>');

						// Read file
						// We try to read in chunks to that large files can be read without issues
						// http://stackoverflow.com/a/12713326/395910
						var Uint8ToString = function(u8a){
								var CHUNK_SZ = 0x8000;
								var c = [];
								for (var i=0; i < u8a.length; i+=CHUNK_SZ) {
									c.push(String.fromCharCode.apply(null, u8a.subarray(i, i+CHUNK_SZ)));
								}
								return c.join("");
							},
							readFile = function(file, chunkSize) {
								var size = file.size,
									offset = 0,
									chunk = file.slice(offset, offset + chunkSize),
									inflator = new pako.Inflate(),
									plainText = '';
								
								var readChunk = function() {
									var reader = new FileReader();

									// When chunk is loaded
									reader.onload = function(e) {

										// Read chunks
										var chunkData;
										if(f.type === 'application/x-gzip') {
											chunkData = new Uint8Array(e.target.result);
										} else {
											plainText += e.target.result;
										}

										// Update offset for next chunk
										offset += chunkSize;

										// Move on to next chunk if available
										if(offset < size) {
											// Splice the next Blob from the File
											chunk = file.slice(offset, offset + chunkSize);
											
											// Push to inflator only if we are dealing with gzip files
											if(f.type === 'application/x-gzip') { inflator.push(chunkData, false); }
										 
											// Recurse to hash next chunk
											readChunk();

										// Done reading
										} else {
											
											// Push to inflator only if we are dealing with gzip files
											if(f.type === 'application/x-gzip') {
												inflator.push(chunkData, true);
												globalFun.cornea.jsonCheck.call(t, f, Uint8ToString(inflator.result));
											} else {
												globalFun.cornea.jsonCheck.call(t, f, plainText);
											}
											// Finish progress
											fileProgress.call(t, size);
										}
									};

									// Progress
									reader.onprogress = function(e) {
										if(e.lengthComputable) {
											fileProgress.call(t, offset + e.loaded);
										}
									};

									// Allow users to manually abort reading
									var aborted = false;
									$d.on('keyup', function(e) {
										if(e.which === 27 && !aborted && $(t).find('.dropzone__message--normal').hasClass('active')) {
											reader.abort();
											aborted = true;
											globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__message--aborted', true);
											return false;
										}
									});

									// Read file differently based on MIME type
									if(f.type === 'application/x-gzip') {
										reader.readAsArrayBuffer(chunk);	
									} else {
										reader.readAsText(chunk);
									}
								},
								fileProgress = function(offset) {
									var $p = $(this).find('.dropzone__message--normal .dropzone__progress span'),
										p = offset/size * 100;
									$p.css('width', p+'%');
								};

								// Start hashing chunks
								readChunk();
							};

						// Initiate file reading
						readFile(f, 65536, null);
					},

					// File handler for CSV uploads (advanced highlight)
					highlight: function(files) {
						var t = this,
							$t = $(t);

						// Get some data for validation
						var opts = {
							'fileType': $t.find('.dropzone__input-file').attr('accept').replace(/\s+/gi, '').split(',') || [],
							'maxFileSize': 100 * 1024 * 1024
						};

						// Retrieve FileList
						var f;
						if(files.length > 1) {
							globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__message-multi', true);
							return;
						} else if(files.length === 0) {
							globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__message--none', true);
							return;
						} else {
							f = files[0];
							if(f.size > opts.maxFileSize) {
								globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__mesage--large', true);
								return;
							}
						}

						// Check MIME type
						if(opts.fileType.length > 0 && opts.fileType.indexOf(f.type) < 0) {
							console.warn('You have provided an invalid filetype.');
							globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__message--invalid-filetype', true, function() {
								$(this).find('p').first().append('<br /><span class="additional-content">You have provided a file with the MIME type: <code>'+f.type+'</code></span>.');
							});
							return;
						}

						// Parse CSV using PapaParse
						// For more information about configuration, see: http://papaparse.com/docs#config
						var percent = 0,
							csvData = [],
							groups = [];

						Papa.parse(f, {
							encoding: 'UTF-8',
							skipEmptyLines: true,
							delimiter: ',',
							step: function(row) {
								var progress = row.meta.cursor,
									newPercent = Math.round(progress / f.size * 100);

								// Update progress
								if (newPercent === percent) return;
								percent = newPercent;
								$t.find('.dropzone__message--normal .dropzone__progress span').css('width',percent+'%');

								// Push each row into final string
								csvData.push(row.data[0]);
							},
							complete: function(results, file) {

								// Update progress
								$t.find('.dropzone__message--normal .dropzone__progress span').css('width','100%');
								window.setTimeout(function() {
									$t.find('.dropzone__message--normal').removeClass('active').find('.dropzone__progress span').css('width',0);
								}, 2000);

								// Send data to nodes highlighting function
								globalFun.sigma.highlightNodes(csvData);

							},
							error: function(error, file) {
								console.warn("Error encountered when attempting to parse:", error, file);
							}
						});

					}
				},
				showMessage: function(target, error, callback) {
					var $t = $(this);

					$t
					.find('.dropzone__message')
						.removeClass('active')
						.find('.additional-content')
							.remove()
							.end()
						.end()
					.find(target)
						.addClass('active');

					if(typeof callback === 'function') {
						callback.call($t.find(target)[0]);
					}
				}
			},
			jsonCheck: function(f, jobRawJSON) {
				var t = this,
					jobJSON;

				// Parse JSON to check
				try {
					jobJSON = JSON.parse(jobRawJSON);
				} catch(e) {
					globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__message--invalid-json', true);
					console.warn('JSON file provided cannot be parsed because it is invalid: '+e);
					return;
				}

				// Tell users we are parsing JSON
				globalFun.cornea.updateStatus({
					loader: true,
					clearTimer: true,
					status: {
						title: '<span class="icon-ok-circled icon--big icon--no-spacing">Parsing file</span>',
						byline: 'Parsing uploaded file. Please wait&hellip;'
					}
				});

				// Check if data structure is intact
				if(!jobJSON.data || !jobJSON.data.edges || !jobJSON.data.nodes) {
					globalFun.cornea.dropzone.showMessage.call(t, '.dropzone__message--incomplete-json', true);
					console.warn('The `data` object, or its compulsory constituents, `edges` and `nodes`, are missing.');
					return;
				}

				// Check if data structure is as expected
				if(!(jobJSON.data.edges instanceof Array) || !(jobJSON.data.nodes instanceof Array)) {
					console.warn('The `nodes` and/or `edges` are not arrays in your data.');
					return;
				}

				// If all checks have passed, do magic
				// Remove drag and drop overlay
				$('.dropzone__message').removeClass('active');

				// Initialize sigma
				globalFun.sigma.init(null, jobJSON, {
					target: '#upload-job',
					fetchData: false
				});

				// Display file metadata
				var jobMeta = jobJSON.metadata;

				var updateStatus = globalFun.cornea.updateStatus({
					loader: false,
					clearTimer: true,
					status: {
						title: '<span class="icon-ok-circled icon--big icon--no-spacing">Parsing completed</span>',
						desc: globalFun.cornea.jobCard.success({
							file: f,
							metadata: jobJSON.metadata,
							static: false
						})
					}
				});
				updateStatus.done(function() {
					globalFun.cornea.vis(jobMeta);
					globalFun.sigma.controls.init(jobMeta);
				});
			},
			pollJobStatus: function(p) {
				var apiURL = (paramSource === 'request' ? '../api' : '../../../api') + '/v1/cornea/job/status/' + jobID.toLowerCase();
				$.ajax({
					url: apiURL,
					type: 'GET',
					dataType: 'text'
				})
				.done(function(stringifiedJSON) {

					var job,
						parser,
						parseComplete = function(job) {
							// Store current status
							currentStatus = parseInt(job.data.status);
						
							// Job complete and available
							if (currentStatus === 3) {

								// Update status
								globalFun.cornea.updateStatus({
									loader: false,
									clearTimer: true,
									status: {
										title: '<span class="icon-ok-circled icon--big icon--no-spacing">'+(jobID.indexOf('standard_') === 0 ? 'Standard job loaded' : 'Job completed')+'</span>',
										pageTitle: 'CORNEA (Job completed) — Tools — Lotus Base'
									}
								});

								// Start sigma
								globalFun.sigma.init(job, null, {
									target: '#check-job',
									fetchData: true
								});

							// Job is in queue
							} else if (currentStatus === 1) {
								var jobs_ahead = function(q) {
									var ahead = parseInt(q) - 1;
									if(ahead < 1) {
										return 'Your job is at the top of the queue and will be processed next.';
									} else {
										return 'There are '+q+' '+globalFun.pl(q, 'job', 'jobs')+' ahead of you.';
									}
								};

								globalFun.cornea.updateStatus({
									loader: true,
									status: {
										title: 'Job currently in queue',
										byline: '<strong>Your job is &numero;'+job.data.queuesize+' in the queue.</strong><br />Last updated: '+moment(new Date()).format('MMM Do YYYY, HH:mm:ss').replace(/(st|nd|rd|th)/gi,'<sup>$1</sup>')+' &middot; updating in <span id="sigma-status__countdown">' + (pollInterval) + 's</span>',
										desc: [
											'<div class="simple-card">',
											'<p>Your job has been submitted to the processing queue. '+jobs_ahead(job.data.queuesize)+' We will automatically poll the status of your job and update this page every '+globalFun.friendlyTime(pollInterval*1000, 0)+'.'+(job.data.owner ? ' As you have provided your email address, you will be notified at <strong>'+job.data.owner+'</strong> when your job is completed.' : '')+'</p>',
											'<p>Your job has been submitted to the server with the following identifier. Make sure that you keep this page open, bookmark this page URL (<a href="'+window.location.href +'">'+window.location.href +'</a>), or save the following identifier for later access:</p>',
											'<span class="job-id__wrapper" data-job-id="'+jobID+'"><span class="job-id">'+jobID+'</span><span class="tooltip">Press <kbd>Ctrl &#8963;</kbd>/<kbd>Cmd &#8984;</kbd> + <kbd>C</kbd> to copy</span></span>',
											'</div>'
											].join(''),
										pageTitle: 'CORNEA (Job in queue) — Tools — Lotus Base'
									}
								});

								globalFun.cornea.updateCountdown();

							// Job is being processed
							} else if (currentStatus === 2) {
								globalFun.cornea.updateStatus({
									loader: true,
									status: {
										title: 'Processing job',
										byline: 'Last updated: '+moment(new Date()).format('MMM Do YYYY, HH:mm:ss').replace(/(st|nd|rd|th)/gi,'<sup>$1</sup>')+' &middot; updating in <span id="sigma-status__countdown">' + (pollInterval) + 's</span>',
										desc: [
											'<div class="simple-card">',
											'<p>Your job is currently being processed. We will automatically poll the status of your job and update every '+globalFun.friendlyTime(pollInterval*1000, 0)+'.'+(job.data.owner ? ' As you have provided your email address, you will be notified at <strong>'+job.data.owner+'</strong> when your job is completed.' : '')+'</p>',
											'<p>Your job has been submitted to the server with the following identifier. Make sure that you keep this page open, bookmark this page URL (<a href="'+window.location.href +'">'+window.location.href +'</a>), or save the following identifier for later access:</p>',
											'<span class="job-id__wrapper" data-job-id="'+jobID+'"><span class="job-id">'+jobID+'</span><span class="tooltip">Press <kbd>Ctrl &#8963;</kbd>/<kbd>Cmd &#8984;</kbd> + <kbd>C</kbd> to copy</span></span>',
											'</div>'
											].join(''),
										pageTitle: 'CORNEA (Processing job) — Tools — Lotus Base'
									}
								});

								globalFun.cornea.updateCountdown();

							// Job has expired
							} else if (currentStatus === 5) {
								globalFun.cornea.updateStatus({
									loader: false,
									status: {
										title: '<span class="icon-attention icon--big icon--no-spacing">Job expired</span>',
										desc: [
											'<p>Your job has been created on the server more than 30 days ago on <strong>'+moment(job.data.end_time).format('LLLL')+' ('+moment(job.data.end_time).fromNow(true)+' ago)</strong>. However, we have loaded your settings and you may use them to create a new network again:</p>',
											'<form action="'+(paramSource === 'request' ? '..' : '../../..')+'/tools/cornea" method="get" class="form--reset"><ul>',
												'<li><strong>Dataset</strong>: '+job.data.dataset+'<input type="hidden" name="dataset" value="'+job.data.dataset+'" /></li>',
												'<li><strong>Threshold</strong>: '+job.data.threshold+'<input type="hidden" name="threshold" value="'+job.data.threshold+'" /></li>',
												'<li><strong>Minimum cluster size</strong>: '+job.data.minimum_cluster_size+'<input type="hidden" name="min_cluster_size" value="'+job.data.minimum_cluster_size+'" /></li>',
												'<li><strong>Columns</strong>: '+(job.data.columns ? job.data.columns.length + '<ul class="dataset-columns"><li>'+job.data.columns.join('</li><li>')+'</li></ul>': 'All')+'<input type="hidden" name="columns" value="'+(job.data.columns ? job.data.columns.join(',') : '')+'" /></li>',
											'</ul>',
											'<button type="submit"><span class="icon-network">Reload settings into new CORNEA job form</span></button>',
											'</form>'
										].join('')
									}
								});
							} else {
								globalFun.cornea.updateStatus({
									loader: false,
									clearTimer: true,
									status: {
										title: '<span class="icon-attention icon--big icon--no-spacing">Job has failed to run</span>',
										desc: [
											'<div class="simple-card">',
											'<p>We have encountered an error: <code>'+job.data.status_reason+'</code> If you wish to contact us about an issue, please attach the following job ID whenver relevant:</p>',
											'<span class="job-id__wrapper" data-job-id="'+jobID+'"><span class="job-id">'+jobID+'</span><span class="tooltip">Press <kbd>Ctrl &#8963;</kbd>/<kbd>Cmd &#8984;</kbd> + <kbd>C</kbd> to copy</span></span>',
											'</div>'
											].join(''), 
										pageTitle: 'CORNEA (Job failure) — Tools — Lotus Base'
									}
								});
							}
						};

					// Pass stringified JSON to web worker for parsing
					if(window.Worker) {

						// Define parser
						parser = new Worker('/dist/js//workers/json-parser.min.js');
						parser.addEventListener('message', function(e) {
							parseComplete(e.data);
						});

						// Send stringified JSON for parsing
						parser.postMessage(stringifiedJSON);

					} else {
						console.warn('Browser does not support Web Worker, using UI thread of JSON parsing.');
						job = JSON.parse(stringifiedJSON);
						parseComplete(job);
					}

				});
			},
			updateCountdown: function() {
				// Countdown from pre-set interval
				var	ticks = pollInterval - 1;
				if (countdownTimer) window.clearInterval(countdownTimer);
				countdownTimer = window.setInterval(function() {
					$('#sigma-status__countdown').text(ticks + 's');
					if(ticks === 0) {
						window.clearInterval(countdownTimer);
					} else {
						ticks--;
					}
				}, 1000);
			},
			updateStatus: function(settings) {

				// Create deferred object
				var dfd = new $.Deferred();

				// Options
				var	opts = $.extend({
						loader: true,
						clearTimer: false,
						status: {
							'title': false,
							'byline': false,
							'desc': false,
							'pageTitle': false
						}
					}, settings);

				// Clear timer
				if(opts.clearTimer) {
					if (pollTimer) window.clearInterval(pollTimer);
					if (countdownTimer) window.clearInterval(countdownTimer);
				}

				// Loader
				if(!opts.loader) $('#sigma-output').find('div.loader').remove();

				// Title
				$('#sigma-status--short').html((opts.status.title ? opts.status.title : ''));

				// Byline
				var $byline = $('#sigma-status--short').next('.byline');
				if(opts.status.byline) {
					$byline.html(opts.status.byline).show();
				} else {
					$byline.hide();
				}

				// Desc
				$('#sigma-status--long').html((opts.status.desc ? opts.status.desc : ''));

				// Page title
				if(opts.status.pageTitle) document.title = opts.status.pageTitle;

				// When all is done, resolve deferred
				dfd.resolve();
				return dfd.promise();
			},
			jobCard: {
				success: function(opts) {
					var f = opts.file,
						jobMeta = opts.metadata,
						staticJob = opts.static;

					// Load variables
					var jobSettings = [
							'<strong>Dataset</strong>: '+jobMeta.settings.dataset,
							'<strong>Identifier type</strong>: '+jobMeta.settings.id_type,
							'<strong>Minimum cluster size</strong>: '+jobMeta.settings.minimum_cluster_size,
							'<strong>Threshold</strong>: '+jobMeta.settings.threshold,
							'<strong>Columns</strong>: '+(jobMeta.settings.columns.length)+'<ul class="dataset-columns"><li>'+jobMeta.settings.columns.join('</li><li>')+'</li></ul>'
						],
						runData = [
							'<strong>ID</strong>: '+(staticJob ? 'standard_' : '')+jobMeta.job.id,
							'<strong>Owner</strong>: '+('<a href="mailto:'+jobMeta.job.owner+'">'+jobMeta.job.owner+'</a>' || 'n.a.'),
							(jobMeta.job.start_time ? '<strong>Start time</strong>: '+moment(jobMeta.job.start_time).format('MMMM Do YYYY, h:mm:ss a') : ''),
							(jobMeta.job.end_time ? '<strong>End time</strong>: '+moment(jobMeta.job.end_time).format('MMMM Do YYYY, h:mm:ss a') : ''),
							'<strong>Runtime</strong>: '+(!isNaN(jobMeta.job.total_time_elapsed) ? globalFun.friendlyTime(jobMeta.job.total_time_elapsed * 1000) : 'n.a.')
						],
						sigmaLayout = [
							'<strong>&numero; of edges</strong>: '+jobMeta.layout.edge_count,
							'<strong>&numero; of nodes</strong>: '+jobMeta.layout.node_count,
							'<strong>&numero; of clusters</strong> :'+jobMeta.layout.cluster_count
						];

					// Store important metadata in #sigma-data
					$('#sigma-data').data('job-metadata', jobMeta);

					// File meta
					var fileMeta = '',
						jobIDMessage = '';
					if(f) {
						jobIDMessage = [
							'<p>We have successfully parsed the file you have uploaded. This file is no longer available on the server.</p>'
						].join('');
						fileMeta = [
							'<div class="card__panel" id="card__file-details"><h4>File details</h4><ul><li>',
							[
								'<strong>MIME type</strong>: '+f.type,
								'<strong>File size</strong>: '+f.size+' bytes',
								'<strong>Last modified date:</strong>: '+(f.lastModifiedDate ? moment(f.lastModifiedDate).format('MMMM Do YYYY, h:mm:ss a') : 'n.a.')
							].join('</li><li>'),
							'</li></ul></div>'
						].join('');
					} else if(staticJob) {
						jobIDMessage = [
							'<p>You are currently viewing a standard CORNEA job.</p>',
							'<span class="job-id__wrapper" data-job-id="standard_'+jobMeta.job.id+'"><span class="job-id">standard_'+jobMeta.job.id+'</span><span class="tooltip">Press <kbd>Ctrl &#8963;</kbd>/<kbd>Cmd &#8984;</kbd> + <kbd>C</kbd> to copy</span></span>',
							'<p class="align-left">',
								'Downloading the compressed JSON will enable you to revisualize this network with CORNEA. ',
								'This job is generated on '+moment(jobMeta.job.end_time).format('MMMM Do YYYY, h:mm:ss a')+'. It is a standard job generated by us, and will remain accessible perpetually.',
							'</p>'
						].join('');
					} else {
						jobIDMessage = [
							'<p>We strongly recommend storing your job ID so that you can access your job in the next 30 days.:</p>',
							'<span class="job-id__wrapper" data-job-id="'+jobMeta.job.id+'"><span class="job-id">'+jobMeta.job.id+'</span><span class="tooltip">Press <kbd>Ctrl &#8963;</kbd>/<kbd>Cmd &#8984;</kbd> + <kbd>C</kbd> to copy</span></span>',
							'<p class="align-left">',
								'For long-term storage, we strongly encourage exporting your job using the available export options, as networks are generated in a non-deterministic manner and user jobs are deleted after 30 days on our servers. ',
								'Downloading the compressed JSON will enable you to revisualize your network with CORNEA. ',
								'This job is generated on '+moment(jobMeta.job.end_time).format('MMMM Do YYYY, h:mm:ss a')+' and will be deleted from the server in <strong>'+moment(jobMeta.job.end_time).add(30, 'days').startOf('day').fromNow(true)+'</strong>.',
							'</p>'
						].join('');
					}

					// Return card
					return [
						'<div class="simple-card align-center">',
							jobIDMessage,
							'<form id="sigma-action" class="cols justify-content__center form--reset" method="post" action="'+(paramSource === 'request' ? '../api' : '../../../api')+'/v1/cornea/job/data/'+(staticJob ? 'standard_' : '')+jobMeta.job.id+'">',
								'<input type="hidden" name="resourceType" id="cornea-download__resourceType" value="" />',
								'<input type="hidden" name="fileData" id="cornea-download__data" value="" />',
								'<input type="hidden" name="job" value="'+(staticJob ? 'standard_' : '')+jobMeta.job.id+'" />',
								'<button type="button" id="sigma-info"><span class="icon-network">View network information</span></button>',
								'<div class="dropdown button">',
									'<span class="dropdown--title"><span class="icon-download">Export</span></span><ul class="dropdown--list">',
										(jobMeta.layout.edge_count <= 100000 && jobMeta.layout.node_count <= 3500 ? '<li class="align-left"><a id="cornea-download__canvas-svg" class="disabled" data-resource-type="svg"><span class="icon-file-image">Drawing canvas&hellip;</span></a></li>' : ''),
										'<li class="align-left"><a id="cornea-download__canvas-png" class="disabled" data-resource-type="png"><span class="icon-file-image">Drawing canvas&hellip;</span></a></li>',
										(!f ? '<li><a id="cornea-download__json" class="disabled" data-resource-type="file" data-file-path="data/cornea/jobs/'+(staticJob ? 'standard_' : '')+jobMeta.job.id+'.json.gz"><span class="icon-file-archive">Loading data file&hellip;</span></a></li>' : ''),
									'</ul>',
								'</div>',
							'</form>',
						'</div>',
							'<div class="card" id="sigma-card">',
							'<article class="card__content"><header><h3>Your network at a glance</h3></header><section>',
								fileMeta,
								'<div class="card__panel" id="card__job-settings"><h4>Run data</h4><div class="cols flex-wrap__nowrap"><ul><li>'+runData.join('</li><li>')+'</li></ul><div class="cornea-vis" id="cornea-vis__runtime"></div></div></div>',
								'<div class="card__panel" id="card__job-settings"><h4>CORNEA parameters</h4><ul><li>'+jobSettings.join('</li><li>')+'</li></ul></div>',
								'<div class="card__panel" id="card__job-settings"><h4>Sigma layout</h4><ul><li>'+sigmaLayout.join('</li><li>')+'</li></ul></div>',
							'</section></article>',
							'<a href="#" title="Close card" role="close" id="sigma-card__close">&times;</a>',
						'</div>'
					].join('');
				}
			},

			// D3 visualization functions for job cards, triggered by a resolved deferred object
			vis: function(jobMeta) {
				// Create runtime visualization
				var jobTimeElapsed = jobMeta.job.time_elapsed,
					colors = colorbrewer.Set2[8];

				var runtime_svg_opts = {
					width: 750,
					height: 250,
					radius: 250/2
				};

				var runtime_svg = d3.select('#cornea-vis__runtime').append('svg:svg').data([jobTimeElapsed]).attr({
						'viewBox': '0 0 '+runtime_svg_opts.width+' '+runtime_svg_opts.height
					}).append('svg:g').attr('transform', 'translate(' + (runtime_svg_opts.radius) + ',' + (runtime_svg_opts.radius) + ')'),

					runtime_pie = d3.layout.pie().value(function(d) {
						return d.time_elapsed;
					}).sort(null),

					runtime_arc = d3.svg.arc().outerRadius(runtime_svg_opts.radius).innerRadius(runtime_svg_opts.radius/2),
					runtime_arcs = runtime_svg.selectAll('g.slice').data(runtime_pie).enter().append('g').attr('class', 'slice'),
					runtime_arc_colors = d3.scale.ordinal().range(colors);

				// Define paths in arcs
				runtime_arcs.append('svg:path')
					.attr({
						'fill': function(d, i) { return runtime_arc_colors(d.data.label); },
						'd': function(d) { return runtime_arc(d); }
					});
					
				// Draw labels
				var label = runtime_arcs.append('text')
					.attr({
						'class': 'label',
						'font-family': 'Helvetica Neue, Helvetica, Arial, sans-serif',
						'transform': function(d) {
							return 'translate(' + runtime_arc.centroid(d) + ')';
						},
						'text-anchor': 'middle',
						'font-size': 12,
						'dy': '0.35em'
					})
					.text(function(d) {
						if(Math.abs(d.startAngle - d.endAngle) > 0.125) {
							return (Math.abs(d.startAngle - d.endAngle)/(2*Math.PI)*100).toFixed(1) + '%';
						}
					});
					
				// Draw legend
				var legend = runtime_svg.selectAll('.legend')
					.data(runtime_arc_colors.domain())
					.enter()
					.append('g')
					.attr('class', 'legend')
					.attr('transform', function(d, i) {
						return 'translate(' + (runtime_svg_opts.radius*1.5) + ',' + (((runtime_svg_opts.height-32)/(runtime_arc_colors.domain().length-1)) * i - (runtime_svg_opts.height/2) + 16) + ')';
					});
				legend.append('circle').attr({
					'class': 'legend__color',
					'r': 12,
					'cx': 4,
					'cy': 4,
					'stroke': 'none',
					'fill': runtime_arc_colors
				});
				legend.append('text').attr({
					'class': 'legend__step-counter',
					'font-family': 'Helvetica Neue, Helvetica, Arial, sans-serif',
					'fill': '#333',
					'x': 4,
					'y': 8,
					'font-size': 12,
					'text-anchor': 'middle'
				}).text(function(d,i) { return (i+1); });
				legend.append('text').attr({
					'class': 'legend__label',
					'font-family': 'Helvetica Neue, Helvetica, Arial, sans-serif',
					'fill': '#333',
					'x': 24,
					'y': 8,
					'font-size': 16
				}).text(function(d,i) {return d+' ('+globalFun.friendlyTime(jobTimeElapsed[i].time_elapsed * 1000)+')'; });
			},

			// Cluster display functions
			clusters: function(job) {
				var clustered_nodes;

				if(job.layout.clustered_nodes) {
					clustered_nodes = job.layout.clustered_nodes;

					$.each(clustered_nodes, function(i,v) {
						$('#sigma-cluster').append('<option value="'+(i+1)+'">Cluster #'+(i+1)+' ('+clustered_nodes[i].length+' nodes)</option>');
					});

					// Add rows
					$.each(clustered_nodes, function(i0,v0) {
						$.each(v0, function(i1,v1) {
							$('#sigma-clusters__table tbody').append([
								'<tr>',
									'<td data-order="'+i0+'">Cluster #'+(i0+1)+'</td>',
									'<td><div class="dropdown button"><span class="dropdown--title">'+v1+'</span><ul class="dropdown--list">',
										(job.settings.id_type !== 'GeneID' ? '<li><a href="'+root+'/view/gene/'+v1.replace(/\.\d+$/gi, '')+'"><span class="icon-eye">View gene data</span></a></li>': ''),
										'<li><a href="'+root+'/view/'+(job.settings.id_type === 'GeneID' ? 'gene' : 'transcript')+'/'+v1+'"><span class="icon-eye">View '+(job.settings.id_type === 'GeneID' ? 'gene' : 'transcript')+' data</span></a></li>',
									'</ul></div></td>',
								'</tr>'].join(''));
						});
					});

					// Do jQuery data tables
					var rows_selected = [],
						nodeTable = $('#sigma-clusters__table').DataTable({
							'dom': 'tipr',
							'language': {
								'emptyTable': 'No clusters selected yet. Please select a cluster from the dropdown above.'
							},
							'order': [0, 'asc'],
							'rowCallback': function(raw, data, dataIndex) {
								// Get row ID
								var rowID = data[0];

								// If row ID is in list of selected row IDs
								if($.inArray(rowID, rows_selected) !== -1){
									$(row).find('input[type="checkbox"]').prop('checked', true);
									$(row).addClass('selected');
								}
							}
						});
					$(nodeTable.table().container()).addClass('full-width');

					// Filter by cluster
					$d.on('change', '#sigma-cluster', function() {

						var c = this.value;
						nodeTable.column(0).search('Cluster #'+c.toString(), false, false, true).draw();

						// Get all nodes
						var rows = $.map(clustered_nodes[parseInt(c) - 1], function(v) {
							return [[v]];
						});

						// Update node highlight field
						$('#sigma__highlight-id').val(clustered_nodes[parseInt(c) - 1].join('\n'));

						// Highlight cluster on map
						globalFun.sigma.highlightNodes(rows);
					});

					// Change counts per page
					$d.on('change', '#sigma-cluster-count', function() {
						nodeTable.page.len(parseInt(this.value)).draw();
					});
					
				}
			}
		},
		sigma: {
			init: function(job, jobJSON, opts) {

				// Check options
				if(typeof opts === typeof undefined) {
					console.warn('Sigma initialization options not specified.');
					return;
				}
				if(typeof opts.target === typeof undefined) {
					console.warn('Tab target of Sigma rendering not specified.');
					return;
				}

				// Job data
				var jobData = {};

				// Check options
				if(typeof opts === typeof undefined) {
					console.warn('Sigma initialization options not specified.');
					return;
				}
				if(typeof opts.target === typeof undefined) {
					console.warn('Tab target of Sigma rendering not specified.');
					return;
				}

				if(job) {
					jobData.filesize = job.data.filesize;
					jobData.id = jobID;
					jobData.dataset = job.data.dataset;
				} else if(jobJSON) {
					jobData.filesize = 0;
					jobData.id = jobJSON.metadata.job.id;
					jobData.dataset = jobJSON.metadata.settings.dataset;
				}

				// Create the necessary DOM elements
				$('#sigma-status--long')
				.after(
					$('<div />', { 'id': 'sigma-data' })
					.append(
						$('<div />', { 'id': 'sigma-parent' })
						.append('<div id="sigma"></div>')
						.append('<div id="sigma-loader" class="align-center"><div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg><p>Retrieving data (<strong>'+jobData.filesize+'</strong>)&hellip;</p></div>')
						)
					.append([
						'<form action="#" method="get" class="has-group"><div class="has-legend" role="group" id="sigma-searchform">',
							'<p class="legend">Node highlight</p>',
							'<div class="sigma-searchform__inputs">',
								'<div class="sigma-searchform__input" id="sigma-searchform__highlight">',
									'<textarea name="highlight-id" id="sigma__highlight-id" placeholder="Enter candidates on a new line"/>',
									'<div class="dropzone hidden" data-dropzone-type="highlight">',
										'<label for="upload-highlight__input-file"><span class="icon-upload icon--big icon--no-spacing">Choose, or drag and drop, a CSV file here</span></label>',
										'<input type="file" id="upload-highlight__input-file" name="sigmaHighlightFile" class="dropzone__input-file" accept="application/vnd.ms-excel, text/csv, applicaton/csv, applicaton/excel, application/msexcel, text/plain"/>',
										'<a class="align-center format-info" data-modal="wide" href="'+(paramSource === 'request' ? '..' : '../../..')+'/lib/docs/cornea/advanced-highlight" title="File format for advanced node highlighting"><span class="icon-info-circled">Need help with formatting your file?</span></a>',
										'<div class="dropzone__message dropzone__message--normal">',
											'<div class="dropzone__progress"><span></span></div>',
											'<h3>Drop CSV file here</h3>',
											'<p>Release your job file here for processing. Press <kbd>Esc</kbd> to close this dialog</p>',
										'</div>',
										'<div class="dropzone__message error dropzone__message--multi">',
											'<h3><span class="icon-attention"></span> Multiple files detected</h3>',
											'<p>You have attempted to upload multiple files. Please only upload a single file: we accept both the original <code>.json.gz</code> file, and the unzipped/uncompressed <code>json</code> file.</p>',
											'<p>Press <kbd>Esc</kbd> to dismiss this message.</p>',
										'</div>',
										'<div class="dropzone__message error dropzone__message--none">',
											'<h3><span class="icon-attention"></span> No files dropped</h3>',
											'<p>No files have been dropped. Please try again.</p>',
											'<p>Press <kbd>Esc</kbd> to dismiss this message.</p>',
										'</div>',
										'<div class="dropzone__message error dropzone__message--large">',
											'<h3><span class="icon-attention"></span> File too big</h3>',
											'<p>The file you have attempted to upload is too big.</p>',
											'<p>Press <kbd>Esc</kbd> to dismiss this message.</p>',
										'</div>',
										'<div class="dropzone__message error dropzone__message--invalid-filetype">',
											'<h3><span class="icon-attention"></span> Invalid file type</h3>',
											'<p>The file you have attempted to upload is not a CSV file.</p>',
											'<p>Press <kbd>Esc</kbd> to dismiss this message.</p>',
										'</div>',
									'</div>',
									'<small><strong>Enter each candidate on a new line, with an optional second column separated by a comma.</strong><br /><a class="format-info" data-modal="wide" href="'+(paramSource === 'request' ? '..' : '../../..')+'/lib/docs/cornea/advanced-highlight" title="File format for advanced node highlighting">How should I format my data?</a></small>',
									'<div id="sigma-searchform__highlight--not-found" class="user-message warning"></div>',
									'<a href="#" id="sigma__highlight-id__mode-switch" class="floating-controls position--right" data-mode="manual">',
										'<span data-mode="upload" class="icon-doc-text icon--no-spacing" title="Upload CSV file with candidate(s)"></span>',
										'<span data-mode="manual" class="icon-keyboard icon--no-spacing" title="Manually enter candidate(s)"></span>',
									'</a>',
								'</div>',
							'</div>',
							'<div id="sigma-searchform__controls" class="cols justify-content__center">',
								'<button id="sigma-searchform__control__highlight" type="button"><span class="icon-filter">Apply filter</span></button>',
								'<button id="sigma-searchform__control__remove-highlights" type="button" disabled><span class="icon-cancel">Remove filter</span></button>',
							'</div>',
						'</div></form>'
						].join('')
						)
//					.append(
//						$('<div />', { 'class': 'toggle hide-first'})
//						.append('<h3><a href="#">Data export</a></h3>')
//						.append(
//							$('<form />', { 'id': 'cornea-download', 'class': 'cols justify-content__center form--reset', 'method': 'POST', 'action': (paramSource === 'request' ? '../api' : '../../../api') })
//							.css('display', 'none')
//							.append('<p>We highly recommend exporting your data due to the non-deterministic nature of generating a network map. No two network maps will be the same regardless of settings. We only store user-generated network files for 30 days on our servers&mdash;if you want to visualize your network in any point in the future, you can re-upload your compressed JSON file to do so.</p>')
//							.append('<input type="hidden" name="t" value="16" />')
//							.append('<input type="hidden" name="resourceType" id="cornea-download__resourceType" value="" />')
//							.append('<input type="hidden" name="fileData" id="cornea-download__data" value="" />')
//							.append('<input type="hidden" name="job" value="'+jobData.id+'" />')
//							.append('<button type="button" id="cornea-download__canvas" class="button" data-resource-type="svg" disabled>Drawing canvas&hellip;</a>')
//							.append((job ? '<button type="button" id="cornea-download__json" class="button" data-resource-type="file" data-file-path="data/cornea/jobs/'+jobData.id+'.json.gz" disabled>Loading data file&hellip;</a>' : ''))
//							)
//						)
					.append('<div id="sigma-search-message"></div>')
					.append([
						'<div id="sigma-clusters">',
							'<form action="#" method="get" class="has-group">',
								'<div class="cols has-legend" role="group">',
									'<p class="legend full-width">Cluster data</p>',
									'<label for="sigma-cluster" class="col-one">Select a cluster</label>',
									'<select id="sigma-cluster" class="col-two"><option>Select a cluster</option></select>',
									'<div class="separator full-width"></div>',
									'<label for="sigma-cluster-count" class="col-one">Rows per page</label>',
									'<select id="sigma-cluster-count" class="col-two">',
										'<option value="10">10</option>',
										'<option value="25">25</option>',
									'</select>',
									'<table class="table--dense full-width" id="sigma-clusters__table">',
										'<thead>',
											'<tr>',
												'<th>Cluster</th>',
												'<th>Identifier</th>',
											'</tr>',
										'</thead>',
										'<tbody></tbody>',
									'</table>',
								'</div>',
							'</form>',
						'</div>'
						].join(''))
					.append($('<div id="sigma-node-card" />').hide())
					.append('<div id="sigma-gene-neighbours"><form action="#" method="get" target="_blank" class="form--reset"><input type="hidden" name="dataset" value="'+jobData.dataset+'" /></form></div>')
					);

				// Create sigma tooltip
				$('#sigma-tooltip').remove();
				$('#sigma-parent').append('<div id="sigma-tooltip" class="tooltip"></div>');
				
				// Zoom
				sigma.prototype.zoomToNode = function(node, ratio, camera){
					if(typeof camera === typeof undefined){
						camera = this.camera;
					}
					camera.ratio = ratio;
					camera.x = node[camera.readPrefix+"x"];
					camera.y = node[camera.readPrefix+"y"]; 
					this.refresh({ skipIndexation: true });
				};

				// Listen to escape key
				$d.on('keypress', function(e) {
					if(e.which === 27) {
						var s = sigmaInst;
						if (highlightedNode) {
							s.renderers[0].dispatchEvent('outNode', { node: highlightedNode });
							s.render();
						}

						// First, clear everything.
						s.graph.nodes().forEach(function(n) {
							n.color = n.restingColor;
							n.size = 2;

							if(customHighlightedNodes.indexOf(n.label) > -1) {
								n.zIndex = 2;
							} else {
								n.zIndex = 0;
							}
						});
						s.graph.edges().forEach(function(e) { e.color = e.restingColor; });

						// Empty node and its neighbours' info
						$('#sigma-node-card, #sigma-gene-neighbours form').empty();
						$('#sigma-node-card').hide();

						// Update
						s.settings.sortNodesByZIndex = true;
						s.settings.sortEdgesByZIndex = true;
						s.refresh({ skipIndexation: true });
					}
				});

				// Do drawing, bind events and etc.
				if(opts.fetchData) {
					window.setTimeout(function() {
						$.ajax({
							url: (paramSource === 'request' ? '..' : '../../..')+'/api/v1/cornea/job/data/'+jobID,
							dataType: 'json',
							type: 'POST',
							data: {
								resourceType: 'stream',
								fileData: '/data/cornea/jobs/' + jobID + '.json.gz'
							}
						})
						.done(function (d) {

							if(job) {
								var jobMeta = d.metadata;

								var updateStatus = globalFun.cornea.updateStatus({
									loader: false,
									clearTimer: true,
									status: {
										title: '<span class="icon-ok-circled icon--big icon--no-spacing">'+(jobID.indexOf('standard_') === 0 ? 'Standard job loaded' : 'Job completed')+'</span>',
										desc: globalFun.cornea.jobCard.success({
											file: null,
											metadata: d.metadata, 
											static: (jobID.indexOf('standard_') === 0 ? true : false)
										})
									}
								});
								updateStatus.done(function() {
									globalFun.cornea.vis(jobMeta);
									globalFun.cornea.clusters(jobMeta);
									globalFun.sigma.controls.init(jobMeta);
								});
							}

							globalFun.sigma.parseData(d);
						})
						.fail(function(jqXHR, textStatus, errorThrown) {
							var reason = '';
							if(jqXHR.status === 404) {
								reason = 'we have failed to locate the file.';
							} else if(textStatus === 'parsererror') {
								reason = 'we have encountered an error when attempting to parse the server output due to an improperly formatted JSON file.';
							}
							$('#sigma-data').after('<div class="user-message warning"><span class="icon-attention"></span> Your job was successfully completed, but ' + reason + ' This is a server side issue that requires our attention&mdash;please <a href="/issue" title="Open a new issue">open an issue</a> and we will have a look as soon as possible.</div>').remove();
						});
					}, 500);
				} else {
					globalFun.sigma.parseData(jobJSON);
				}

				// Nodes highlight/filtering feature
				$d
				.on('click', '#sigma-searchform__control__highlight', function() {

					// Highlight nodes
					globalFun.sigma.highlightNodesClicked();

				});

				// Click events on dropdown menu
				$d
				.on('click', '.input-group .dropdown-menu li', function() {
					var $t = $(this);
					$t.closest('.input-group-btn').siblings('button[data-action]').first()
					.html($t.html())
					.attr('data-action', $t.attr('data-action'));
					$('.sigma-searchform__input, .sigma-searchform__control').removeClass('active');
					$('#sigma-searchform__'+$t.attr('data-action')+', #sigma-searchform__control-'+$t.attr('data-action')).addClass('active');
				})
				// Click events on buttons
				.on('click', '#sigma-button', function() {
					var $t = $(this);
					if ($t.attr('data-action') === 'search') {
						globalFun.sigma.searchNode();
					} else if ($t.attr('data-action') === 'highlight') {
						// Parse multi input first
						$('#sigma-searchform__highlight .input-mimic input').trigger('blur');

						globalFun.sigma.highlightNodesClicked();
					}
				});

				// Sigma searchform events
				$('#sigma__search-id').on('change blur input', function() {
					if(!$(this).val()) {
						$(this).removeClass('error');
						$('#sigma-search-message').removeClass().empty();
					}
				});

				$('#sigma-data')
				// Determine where to send results to
				.on('click', 'tfoot button', function() {
					if($('#rows tbody input:checkbox:checked').length) {
						$(this).closest('form')
						.attr('action', $(this).attr('data-form-action'))
						.trigger('submit');
					} else {
						globalFun.modal.open({
							'title': 'No rows selected',
							'content': '<p>You have not selected any rows to be passed on to another <em>Lotus</em> base tool.</p>',
							'allowClose': true
						});
					}
				})
				// Intercept form submission
				.on('submit', 'form', function(e) {
					e.preventDefault();

					var p = [
						{ key: 'dataset', value: $('#expat-dataset').val() },
						{ key: 'column', value: $('#expat-dataset-subset tbody input:checkbox:checked').map(function() { return $(this).val(); }).get().join(',') },
						{ key: 'conditions', value: $('#expat-condition').val() }
					];

					$.each(p, function(i,v) {
						$('#rows input[name="'+v.key+'"]').val(v.value);
					});

					// Submit
					$(this)[0].submit();
				});

				// Draging cursor
				var isDragging = false;
				$d
				.on('mousedown', '.sigma-mouse', function() {
					if(!isDragging) {
						isDragging = true;
						$(this).addClass('grabbing');
						$('#sigma-tooltip').removeClass().empty();
					}
				})
				.on('mouseup', '.sigma-mouse', function() {
					if(isDragging) {
						isDragging = false;
						$(this).removeClass('grabbing');
					}
				});
				$('.sigma-mouse')
				.unbind('mousewheel DOMMouseScroll')
				.bind('mousewheel DOMMouseScroll', $.throttle(500, function(e) {
					$('#sigma-tooltip').removeClass().empty();
				}));

				// Window resize
				 $w.on('resize', function() {
				 	// Rescale
				 	$('#sigma-tooltip').removeClass().empty();
					if(sigmaInst.renderers[0]) sigmaInst.renderers[0].resize();
				 });
			},
			parseData: function(d) {

				// Loader
				var $loader = $('#sigma-loader');

				// Update loader
				$loader.find('p').html('Data loaded. Parsing data&hellip;');

				var expandedData = {nodes: [], edges: []},
					step = 5000;

				// Loop functions
				var map = {
					nodes: function(idx, n) {
						expandedData.nodes.push({
							id: n.i,
							label: n.l,
							x: n.x,
							y: n.y,
							size: 1.5,
							restingColor: globalVar.sigma.node.color.default,
							highlighted: false
						});
					},
					edges: function(idx, e) {
						expandedData.edges.push({
							id: e.i,
							source: e.s,
							target: e.t,
							size: 0.5,
							restingColor: globalVar.sigma.edge.color.default
						});
					}
				};

				for (var i = 0, j = d.data.nodes.length; i < j; i += step) {
					$.each(d.data.nodes.slice(i,i+step), map.nodes);
				}

				for (var k = 0, l = d.data.edges.length; k < l; k += step) {
					$.each(d.data.edges.slice(k,k+step), map.edges);
				}

				// Update loader
				$loader.find('p').html('Data parsed. Drawing onto canvas&hellip;');

				// Enable data download
				$('#cornea-download__json').removeClass('disabled').html('<span class="icon-file-archive">Download compressed JSON</span>');

				globalFun.sigma.draw(expandedData);
			},
			draw: function(data) {
				var options = {
					container: 'sigma',
					settings: {
						// General apperance
						defaultNodeColor: globalVar.sigma.node.color.default,
						defaultLabelColor: '#333',
						defaultLabelSize: 16,
						defaultEdgeColor: globalVar.sigma.edge.color.default,
						edgeColor: globalVar.sigma.edge.color.default,

						// Hovered nodes
						borderSize: 1,
						defaultNodeBorderColor: 'rgba(51,51,51,1)',
						defaultNodeHoverColor: '#fff',
						singleHover: true,

						// Hoevered edges
						defaultEdgeHoverColor: '#b13131',

						// Rescale settings
						minEdgeSize: 0,
						maxEdgeSize: 0,
						minNodeSize: 0,
						maxNodeSize: 0,
						scalingMode: 'inside',
						sideMargin: 1000,
						hideEdgesOnMove: true,
						labelThreshold: Number.MAX_VALUE,

						// Captor settings
						zoomingRatio: 1.25
					},
					graph: data
				};

				var s = new sigma(options);
				sigmaInst = s;
				
				// Events API
				s.bind('clickNode', function(e) {
					// Get node coordinates
					var clientX = e.data.captor.clientX - $('#sigma-parent').offset().left,
						clientY = e.data.captor.clientY + $w.scrollTop() - $('#sigma-parent').offset().top;

					// Fire custom clicKNode function
					s.graph.clickNode(e.data.node, { x: clientX, y: clientY });

				});
				s.bind('clickStage', function(e) {
					// If a node is highlighted, make sure it stays highlighted.
					if (highlightedNode) {
						sigmaInst.renderers[0].dispatchEvent('overNode', { node: highlightedNode });
					}
				});
				s.bind('overNode', function(e) {
					$('#sigma .sigma-mouse').addClass('node-hover');
				});
				s.bind('outNode', function(e) {
					$('#sigma .sigma-mouse').removeClass('node-hover');
				});

				// Camera API
//				s.camera.bind('coordinatesUpdated', $.throttle(500, function() {
//					$('#sigma-tooltip').removeClass('active').empty();
//				}));

				// Display final message
				$('#sigma-loader').find('p').html('Drawing successful. Cleaning up&hellip;');
				var timer = window.setTimeout(function() {
					// Remove loader
					$('#sigma-loader').remove();

					// Enable canvas download
					$('#cornea-download__canvas-svg').removeClass('disabled').html('<span class="icon-file-image">Export as SVG (vector, larger file)</span>');
					$('#cornea-download__canvas-png').removeClass('disabled').html('<span class="icon-file-image">Export as PNG (bitmap, smaller file)</span>');
				}, 1000);

				// Show draggable controls
				$('.sigma-mouse').addClass('grab');
			},
			controls: {
				init: function(jobMeta) {
					$('#sigma')
					.prepend([
						'<div id="sigma-controls">',
							'<ul class="floating-controls position--left">',
								'<li><a id="sigma-controls__fullscreen" href="#" class="icon-resize-full icon--no-spacing" title="Toggle fullscreen"></a></li>',
								'<li><a href="#" id="sigma-controls__export-image" class="icon-camera icon--no-spacing" title="Image export options"></a><ul>',
									'<li><a href="#" data-image-type="png" title="Export current view as a PNG file" class="sigma-controls__export-image-option">PNG (bitmap)</a></li>',
									(jobMeta.layout.edge_count <= 100000 && jobMeta.layout.node_count <= 3500 ? '<li><a href="#" data-image-type="svg" title="Export current view as an SVG file" class="sigma-controls__export-image-option">SVG (vector)</a></li>' : ''),
								'</ul></li>',
							'</ul>',
						'</div>'
						].join(''));
				}
			},
			searchNode: function() {
				var s = sigmaInst,
					str = $('#sigma__search-id').val();

				// If we re-do a search, simply zoom to searchedForNode.
				if (searchedForNode && searchedForNode.label === str) {
					s.zoomToNode(searchedForNode, 0.75);
					// Sometimes multiple labels are shown. Counter this by calling render().
					s.render();
					return;
				}

				// Filter through the nodes in the graph and find the first node with label equal to str.
				var nodes = s.graph.nodes(),
					foundGene = false;
				for (var nodeIdx in nodes) {
					var node = nodes[nodeIdx];
					if (node.label === str) {
						foundGene = true;
						// Call both Sigma's overNode event to display the label and also our own clickNode event.
						s.renderers[0].dispatchEvent('overNode', { node: s.graph.nodes(node.id) });
						s.graph.clickNode(node, null);
						s.zoomToNode(node, 0.75);
						searchedForNode = node;
						break;
					}
				}

				var $sm = $('#sigma-search-message'),
					$st = $('#sigma-id');
				if(foundGene) {
					$sm.removeClass().addClass('user-message approved');
					$st.removeClass('error');
				} else {
					$sm.removeClass().addClass('user-message warning');
					$st.addClass('error');
				}
				$sm.html(foundGene ? "Found gene!" : "Did not find gene. :(");
			},

			// Node highlighting based on user input in textarea
			highlightNodesClicked: function() {
				Papa.parse($('#sigma__highlight-id').val(), {
					encoding: 'UTF-8',
					skipEmptyLines: true,
					complete: function(rows) {

						// Highlight nodes
						globalFun.sigma.highlightNodes(rows.data);

						// Filter cluster data
						var filter = $.map(rows.data, function(v) {
							return v[0];
						});

						// Reset filter
						$('#sigma-cluster').find('option').first().prop('selected', true);

						// Search
						$('#sigma-clusters__table').DataTable().column(1).search(filter.join('|'), true, false, true).draw();
					}
				});
			},

			// Node highlighting main function
			highlightNodes: function(nodes) {
				var s = sigmaInst;

				// Loop through nodes and assign colors whenever appropriate
				var highlight_node_objs = [],											// Create array to store all processed nodes
					nodes_to_highlight_map = {},										// Create a hashmap (using a JS object) to allow easy label-to-color-lookup.
					hash = Math.random().toString(36).substring(2,10),					// Create hash for rows without grouping data
					idType = $('#sigma-data').data('job-metadata').settings.id_type,	// Get idType so we know how to replace/coerce labels
					nodeGroups = [],													// An array to store unique node groups
					nodeGroupColors = colorbrewer.Dark2[8],								// An array of colours for node groups

					// Allow label coercing
					coerceLabel = function(label) {
						if(idType === 'GeneID') {
							return label.replace(/\.\d+$/gi, '');
						} else {
							return label;
						}
					},

					// ColorCheck adapted from http://stackoverflow.com/a/33184805/395910
					colorCheck = function(c) {
						var ele = document.createElement("div");
						ele.style.color = c;
						var browserColor = ele.style.color.split(/\s+/).join('').toLowerCase();
						if(browserColor && /^(rgb\(\d{1,3},\d{1,3},\d{1,3}\)|#(\d{3}|\d{6}))$/gi.test(browserColor)) {
							return browserColor;
						} else {
							return false;
						}
					},

					// Map colors to groups
					mapNodeGroupColors = function(valueIn, valuesIn) {

						// Interpolate colors
						var nodeFill = d3.scale.linear().domain(d3.range(0, 1, 1.0 / (nodeGroupColors.length - 1))).range(nodeGroupColors),
							nodeFillmap = d3.scale.ordinal().domain(valuesIn).rangeBands([0,1]);
						return nodeFill(nodeFillmap(valueIn));

					};

				// First loop: Assign groups and get unique groups
				nodes.forEach(function(n) {

					var nodeLabel = coerceLabel(n[0]),
						nodeGroup = $.trim(n[1] || hash),
						nodeColor = null;

					// Check of nodeGroup is a color
					if(colorCheck(nodeGroup)) {
						nodeColor = colorCheck(nodeGroup);
					} else {
						// Store unique node groups only if it is not a valid color (so we can assign a custom color to them)
						if(nodeGroups.indexOf(nodeGroup) < 0) nodeGroups.push(nodeGroup);
					}
					
					// Push node settings into array
					highlight_node_objs.push({
						'label': nodeLabel,
						'group': nodeGroup,
						'color': nodeColor
					});
				});

				// Second loop: Assign colors
				highlight_node_objs.forEach(function(n) {

					// Assign colors
					if(typeof n.color === typeof null) {
						n.color = mapNodeGroupColors(n.group, nodeGroups);
					}

					// Create hash map
					nodes_to_highlight_map[n.label] = {
						color: n.color,
						restingColor: n.color
					};

				});

				// Third loop: Go through all nodes in chart and highlight them when a match is found
				s.graph.nodes().forEach(function(n) {
					if (n.label in nodes_to_highlight_map) {
						n.color = nodes_to_highlight_map[n.label].color;
						n.restingColor = nodes_to_highlight_map[n.label].restingColor;
						n.highlighted = true;
						// Setting the z index (and sortNodesByZIndex below) allows us to draw this node in front of non-highlighted nodes.
						n.zIndex = 1;
						n.size = 3;

						// Show position
						$('#sigma').append($('<div />').css({
							'top': n['cam0:y'] - 5,
							'left': n['cam0:x'] - 5,
							'background-color': nodes_to_highlight_map[n.label].color,
							'width': 10,
							'height': 10,
						}).addClass('node-show'));

						// Since we found the node, we remove it from the map
						delete nodes_to_highlight_map[n.label];

						// Store labels
						customHighlightedNodes.push(n.label);

					} else {
						n.color = globalVar.sigma.node.color.inactive;
						n.restingColor = globalVar.sigma.node.color.inactive;
						n.zIndex = 0;
						n.size = 1.5;
					}
				});

				// Toggle highlight status
				$('#sigma').data('highlight', true);
				$('#sigma-searchform__control__i').prop('disabled', false);

				// For nodes that are not found in the network graph
				$('#sigma-searchform__highlight--not-found').empty().removeClass('active');
				if(!$.isEmptyObject(nodes_to_highlight_map)) {
					$('#sigma-searchform__highlight--not-found')
					.addClass('active')
					.append('<p><span class="icon-attention"></span> The following IDs were not found in the network map:</p><ul />');
					for (var n in nodes_to_highlight_map) {
						// Use .text() to escape dangerous HTML
						$('#sigma-searchform__highlight--not-found ul').append($('<li />').text(n));
					}
				}

				// Unhighlight edges
				s.graph.edges().forEach(function(e) {
					e.color = e.restingColor;
					e.size = 0.5;
					e.zIndex = 0;
				});

				// sortNodesByZIndex is an extension we have made to sigma.js. See https://bitbucket.org/asger/sigma.js.
				// Note: This is only for the WebGL renderer. We can extend it to the canvas renderer if needed but all modern browsers support WebGL.
				// Sorting by z index is turned on the first time the user uses any highlight function (this one or searching for a node or clicking a node).
				// If the user continues the session, sorting is still enabled. This might impact performance but we should be fine as long as we limit the number of s.refresh() calls.
				s.settings.sortNodesByZIndex = true;
				s.settings.sortEdgesByZIndex = true;
				s.refresh({ skipIndexation: true });
			},
			updateGeneInfo: function(node, neighbours) {
				// Return if node is unavailable
				if(!node || !node.label) return;

				// Perform AJAX call for selected gene
				var nodeAjax = $.ajax({
					url: (paramSource === 'request' ? '../api' : '../../../api') + '/v1/gene/annotation/v3.0/' + node.label
				});

				// Display neighbours
				var $neighbours = $('#sigma-gene-neighbours form'),
					n = [];

				$neighbours.addClass('form--reset').find('table').remove();

				if (neighbours) {

					// Construct neighbours list
					$.each(neighbours, function (i,v) {
						if (v.label !== node.label) {
							n.push(v.label);
						}
					});

					// Perform AJAX call for neighbours
					var neighboursAjax = $.ajax({
						url: (paramSource === 'request' ? '../api' : '../../../api') + '/v1/gene/annotation/v3.0/' + n
					});

					$.when.apply($, [nodeAjax, neighboursAjax]).done(function(r1, r2) {

						var hasAnno = false,
							coercedID = false,
							targetAnno;

						// If gene annotation is returned
						if(r1[0].success && r1[0].data[0].annotation) {
							hasAnno = true;
							targetAnno = r1[0].data[0].annotation.replace(/\[([\w\s]+)\]?/i, '[<em>$1</em>]');

							// If the return ID has been coerced (i.e. transcript <> gene)
							if(node.label !== r1[0].data[0].gene) {
								coercedID = true;
							}
						}
						
						// Update tooltip
						$('#sigma-tooltip').html([
							'<h3>'+node.label+'</h3>',
							(hasAnno ? (coercedID ? '<p>Annotation for the transcript (converted from gene ID) <strong>'+r1[0].data[0].gene+'</strong>:</p>' : '')+'<pre><code>'+targetAnno+'</code></pre>' : ''),
							'<p>This node is linked to '+n.length+' other '+globalFun.pl(n.length, 'neighbour', 'neighbours')
						].join(''));

						// Display card
						$('#sigma-node-card').html(function() {
							var out = '';
							if(node) {
								out += '<h3>'+node.label+'</h3><span class="annotation">'+(hasAnno ? targetAnno : 'No annotations available.')+'</span><p>Your selected node is highlighted as the first gene in the table below, along with its '+n.length+' other '+globalFun.pl(n.length, 'neighbour', 'neighbours')+'</p>';
							}
							return out;
						}).show();

						// Row output
						var row_out = function(o, h) {
							return [
								'<tr>',
									'<td class="chk"><input type="checkbox" name="ids[]" value="'+o.gene+'" /></td>',
									'<td>',
										'<div class="dropdown button"><span class="dropdown--title">'+(h ? '<strong>'+o.gene+'</strong>' : o.gene)+'</span><ul class="dropdown--list">',
											'<li><a target="_blank" href="../tools/trex?ids='+o.gene+'"><span class="icon-direction">Send gene to Transcript Explorer (TREX)</span></a></li>',
											'<li><a target="_blank" href="../lore1/search-exec?gene='+o.gene+'&amp;v=30" title="Search for LORE1 insertions in this gene"><span class="pictogram icon-search">LORE1 v3.0</span></a></li>',
										'</ul></div>',
									'</td>',
									'<td>'+(o.annotation ? o.annotation.replace(/\[([\w\s]+)\]?/i, '[<em>$1</em>]') : 'n.a.')+'</td>',
								'</tr>'
							].join('');
						};

						// Create table
						var $table = $('<table id="rows" class="table--dense" />');
						$table
						.append('<thead><tr><th class="chk"><input type="checkbox" class="ca" /></th><th>Gene</th><th>Annotation</th></thead>')
						.append(function() {
							var $tbody = $('<tbody />');
							$tbody.append(row_out(r1[0].data[0], true));
							$.each(r2[0].data, function(i, n) {
								$tbody.append(row_out(n, false));
							});
							return $tbody;
						})
						.append([
							'<tfoot><tr>',
								'<td colspan="999">',
									'<div class="cols justify-content--center">',
										'<button data-action="submit" data-form-action="'+(paramSource === 'request' ? '..' : '../../..')+'/expat" type="button"><span class="icon-map">Send selected rows to ExpAt</span></button>',
										'<button data-action="submit" data-form-action="'+(paramSource === 'request' ? '..' : '../../..')+'/tools/trex" type="button"><span class="icon-direction">Send selected rows to TREX</span></button>',
									'</div>',
								'</td>',
							'</tr></tfoot>'
							].join(''));
						$neighbours.append($table);

						// Enable sticky table
						globalFun.stickyTable();

						if($('#rows').length) {
							// Resize sticky table
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
				}
			}
		},
		getKeys: function() {
			var _keys = [];
			for (var i in globalVar.keys) {
				if (!globalVar.keys.hasOwnProperty(i)) continue;
				_keys.push(i);
			}
			if(_keys.indexOf('67') > -1 && (_keys.indexOf('91') > -1 || _keys.indexOf('17') > -1)) {
				$('#sigma-status--long span.job-id__wrapper').removeClass('selected');
			}
		}
	});
	globalFun.cornea.init();

	if(paramSource) {
		if(jobID) {

			// Programatically select tab
			$( "#cornea-tabs" ).tabs('option', 'active', 3);

			if(/(standard_)?[A-Fa-f0-9]{32}/gi.test(jobID)) {

				// Poll status every 'pollInterval' seconds.
				globalFun.cornea.pollJobStatus(params);
				pollTimer = window.setInterval(function() {
					globalFun.cornea.pollJobStatus(params);
				}, pollInterval * 1000);

			} else {
				globalFun.cornea.updateStatus({
					loader: false,
					clearTimer: true,
					status: {
						'title': '<span class="icon-attention icon--big icon--no-spacing">Invalid job ID</span>',
						'byline': false,
						'desc': '<div class="user-message warning">You have provided an invalid job ID. Please make sure that it is a 32-character hexadecimal string. Once you have retrieved the correct job ID, you may search for it with the form below:</div>' + globalVar.cornea.jobSearchForm,
						'pageTitle': 'CORNEA (Invalid job ID) — Tools — Lotus Base'
					}
				});
			}
		} else {
			globalFun.cornea.updateStatus({
				loader: false,
				clearTimer: true,
				status: {
					'title': '<span class="icon-attention icon--big icon--no-spacing">Job identifier not provided</span>',
					'byline': false,
					'desc': '<div class="user-message warning">You have not provided a job ID in your URL. Please check that you have followed the correct link. If you have your job identifier, you can search for it using the form below:</div>' + globalVar.cornea.jobSearchForm,
					'pageTitle': 'CORNEA (Missing job ID) — Tools — Lotus Base'
				}
			});
		}
	}

	// Flipper for job card
	$d.on('click', '.card__flipper-button', function() {
		$(this).closest('.card__action').siblings('.card__flipper').toggleClass('flipped');
		if($(this).closest('.card__action').siblings('.card__flipper').hasClass('flipped')) {
			$(this).html($(this).attr('data-card-back-text'));
		} else {
			$(this).html($(this).attr('data-card-front-text'));
		}
	});

	// Job status form
	$d.on('submit', '#cornea-form__status', function(e) {
		if($('#cornea-job-id').val()) {
			window.location = (paramSource === 'request' ? '..' : '../../..') + '/tools/cornea/job/' + $('#cornea-job-id').val();
		}
		e.preventDefault();
	});

	// Copy job ID
	var TrelloClipboard = function() {
		var me = this;

		var utils = {
			nodeName: function (node, name) {
				return node.nodeName.toLowerCase() === name;
			}
		};

		var textareaId = 'simulate-trello-clipboard',
			containerId = textareaId + '-container',
			container, textarea;

		var createTextarea = function () {
			container = document.querySelector('#' + containerId);
			if (!container) {
				container = document.createElement('div');
				container.id = containerId;
				container.setAttribute('style', ['position: fixed;', 'left: 0px;', 'top: 0px;', 'width: 0px;', 'height: 0px;', 'z-index: 100;', 'opacity: 0;'].join(''));
				document.body.appendChild(container);
			}
			container.style.display = 'block';
			textarea = document.createElement('textarea');
			textarea.setAttribute('style', ['width: 1px;', 'height: 1px;', 'padding: 0px;'].join(''));
			textarea.id = textareaId;
			container.innerHTML = '';
			container.appendChild(textarea);

			if(me.el) {
				textarea.appendChild(document.createTextNode(me.el.innerText || me.el.textContent));
				textarea.focus();
				textarea.select();
			}
		};

		var keyDownMonitor = function (e) {
			var code = e.keyCode || e.which;
			if (!(e.ctrlKey || e.metaKey)) {
				return;
			}
			var target = e.target;
			if (utils.nodeName(target, 'textarea') || utils.nodeName(target, 'input')) {
				return;
			}
			if (window.getSelection && window.getSelection() && window.getSelection().toString()) {
				return;
			}
			if (document.selection && document.selection.createRange().text) {
				return;
			}
			setTimeout(createTextarea, 0);

			$(me.el).closest('.job-id__wrapper').removeClass('selected');
		};

		var keyUpMonitor = function (e) {
			var code = e.keyCode || e.which;
			if (e.target.id !== textareaId) {
				return;
			}
			container.style.display = 'none';
		};

		document.addEventListener('keydown', keyDownMonitor);
		document.addEventListener('keyup', keyUpMonitor);
	};

	TrelloClipboard.prototype.setValue = function (el) {
		this.el = el;
	};

	var clip = new TrelloClipboard();
	$d.on('click', '.simple-card span.job-id__wrapper', function(e) {
		var el = $(this).children('.job-id').first()[0];
		clip.setValue(el);

		$(this).addClass('selected');

		e.stopPropagation();
	})
	.on('click', '.simple-card span.job-id__wrapper span.tooltip', function(e) {
		$(this).closest('span.job-id__wrapper').removeClass('selected');
		e.stopPropagation();
	})
	.on('click', function(e) {
		$('.simple-card span.job-id__wrapper').removeClass('selected');
	})
	.on('keydown', function(e) {
		globalVar.keys[e.which] = true;
		globalFun.getKeys();
	})
	.on('keyup', function(e) {
		delete globalVar.keys[e.which];
		globalFun.getKeys();
	});

	// CORNEA download
	var corneaDownloadCanvasTimer;
	$d
	.on('click','#cornea-download__canvas-svg', function() {
		// Generate data URL
		// Check for dataURL support
		var scene = $('#sigma').children('.sigma-scene').first()[0],
			canvasData,
			$t = $(this),
			$sigmaControl = $('.sigma-controls__export-image-option[data-image-type="svg"]');

		// Update button status
		$t.prop('disabled', true).html('Creating file, please wait&hellip;');
		$sigmaControl.addClass('disabled').html('Creating file&hellip;');

		// Get data and set resource type
		$('#cornea-download__data').val(btoa(sigmaInst.toSVG()));
		$('#cornea-download__resourceType').val($t.attr('data-resource-type'));

		// Submit form
		$t.closest('form')[0].submit();
		$('#cornea-download__data').val('');

		// Update button status
		$t.html('Download in progress&hellip;');
		$sigmaControl.html('Downloading&hellip;');

		// Set timer
		window.clearTimeout(corneaDownloadCanvasTimer);
		corneaDownloadCanvasTimer = window.setTimeout(function() {
			$t.prop('disabled', false).html('<span class="icon-file-image">Export as SVG (vector, larger file)</span>');
			$sigmaControl.removeClass('disabled').text('SVG (vector)');
		}, 2000);
		
	})
	.on('click', '#cornea-download__canvas-png', function() {
		var scene = $('#sigma').children('.sigma-scene').first()[0],
			canvasData,
			$t = $(this),
			$sigmaControl = $('.sigma-controls__export-image-option[data-image-type="png"]');

		if(typeof scene.toDataURL === 'function') {
			window.clearTimeout(corneaDownloadCanvasTimer);

			// Update button status 
			$t.prop('disabled', true).html('Creating file, please wait&hellip;');
			$sigmaControl.addClass('disabled').html('Creating file&hellip;');

			// Get data
			canvasData = (typeof scene.toDataURLHD === 'function' ? scene.toDataURLHD() : scene.toDataURL());
			$('#cornea-download__data').val(canvasData);

			// Set resource type
			$('#cornea-download__resourceType').val($t.attr('data-resource-type'));

			// Submit form
			$t.closest('form')[0].submit();
			$('#cornea-download__data').val('');

			// Update button status
			$t.html('Download in progress&hellip;');
			$sigmaControl.html('Downloading&hellip;');

			// Set timer
			corneaDownloadCanvasTimer = window.setTimeout(function() {
				$t.prop('disabled', false).html('<span class="icon-file-image">Export as PNG (bitmap, smaller file)</span>');
				$sigmaControl.removeClass('disabled').text('PNG (bitmap)');
			}, 2000);
		} else {
			// Remove button as canvas API is not supported
			$t.remove();
		}
	})
	.on('click', '#cornea-download__json', function() {
		// Retrieve file path
		$('#cornea-download__data').val($(this).attr('data-file-path'));

		// Set resource type
		$('#cornea-download__resourceType').val($(this).attr('data-resource-type'));

		// Submit form
		$(this).closest('form')[0].submit();
	});

	// Add a method to the graph model that returns an
	// object with every neighbors of a node inside:
	sigma.classes.graph.addMethod('neighbors', function(nodeId) {
		var neighbors = {},
			index = this.allNeighborsIndex[nodeId] || {};
		
		for (var k in index)
			neighbors[k] = this.nodesIndex[k];
		
		return neighbors;
	});
	
	// When a node is clicked, we check for each node if it is a neighbor of the clicked one. If not, we set its color as grey, and else, it takes its original color.
	// We do the same for the edges, and we only keep edges that have both extremities colored.
	sigma.classes.graph.addMethod('clickNode', function(node, nodeCoords) {
		var s = sigmaInst;

		// If we have searched for a node, it is highlighted. Make sure to un-highlight it.
		if (searchedForNode) {
			s.renderers[0].dispatchEvent('outNode', { node: searchedForNode });
			// Sometimes, the label won't go away. Counter this by calling render().
			s.render();
			searchedForNode = null;

			// Hide tooltip
			$('#sigma-tooltip').removeClass().empty();
			$('#sigma-status--long').removeClass('tooltip--open');

		} else {
			// Create tooltip
			if(nodeCoords !== null && 'x' in nodeCoords && 'y' in nodeCoords) {
				// Determine how to position tooltip relative to node
				var posClass;
				if(nodeCoords.y < $('#sigma-parent').height() * 0.5) {
					posClass = 'position--bottom';
				} else {
					posClass = 'position--top';
				}

				// Display and position tooltip
				$('#sigma-tooltip').addClass('tooltip active '+posClass).css({
					top: nodeCoords.y,
					left: nodeCoords.x
				}).html('<div class="loader"><svg viewbox="0 0 80 80"><circle class="path" cx="40" cy="40" r="30"/></svg></div>');
				$('#sigma-status--long').addClass('tooltip--open');
			} else {
				// Destroy tip
				$('#sigma-tooltip').removeClass().empty();
				$('#sigma-status--long').removeClass('tooltip--open');
			}
		}

		// First, clear everything.
		s.graph.nodes().forEach(function(n) {
			n.color = n.restingColor;
			n.size = 1.5;
		});
		
		s.graph.edges().forEach(function(e) {
			e.color = e.restingColor;
			e.size = 0.5;
		});

		// Make sure to un-unhighlight whatever node is highlighted.
		if (highlightedNode) {
			s.renderers[0].dispatchEvent('outNode', { node: highlightedNode });
			// Sometimes, the label won't go away. Counter this by calling render().
			s.render();
			// If we have re-clicked the previously highlighted node, don't re-highlight its neighbours.
			if (highlightedNode.id === node.id) {
				highlightedNode = null;
				s.refresh({ skipIndexation: true });
				globalFun.sigma.updateGeneInfo(null, null);

				// Destroy tip
				$('#sigma-tooltip').removeClass().empty();
				$('#sigma-status--long').removeClass('tooltip--open');

				return;
			}
		}
		
		highlightedNode = node;
		
		var nodeId = node.id,
			toKeep = this.neighbors(nodeId);
		toKeep[nodeId] = node;
		
		this.nodes().forEach(function(n) {
			if (n.id === node.id) {
				n.color = (n.highlighted ? n.restingColor : globalVar.sigma.node.color.highlighted);
				n.size = 3;
				n.zIndex = 2;
			}
			else if (toKeep[n.id]) {
				n.color = globalVar.sigma.node.color.highlightedNeighbours;
				n.size = 2;
				n.zIndex = 1;
			} else {
				n.color = globalVar.sigma.node.color.inactive;
				n.size = 1.5;
				n.zIndex = 0;
			}
		});
		
		this.edges().forEach(function(e) {
			if (e.source === node.id || e.target === node.id) {
				e.color = 'rgba(92, 164, 169, 0.5)';
				e.size = 1;
				e.zIndex = 1;
			}
			else if (toKeep[e.source] && toKeep[e.target]) {
				e.color = e.restingColor;
				e.size = 0.5;
				e.zIndex = 0;
			} else {
				e.color = globalVar.sigma.edge.color.inactive;
				e.size = 0.5;
				e.zIndex = 0;
			}
		});
		
		// Since the data has been modified, we need to call the refresh method to make the colors update effective.
		s.settings.sortNodesByZIndex = true;
		s.settings.sortEdgesByZIndex = true;
		s.refresh({ skipIndexation: true });

		globalFun.sigma.updateGeneInfo(node, toKeep);
	});

	// Sigma metadata viewer
	$d
	.on('click', '#sigma-info', function(e) {
		e.preventDefault();

		// Close other interfering popups
		globalFun.modal.close();
		$('#sigma-tooltip').removeClass().empty();
		$('#sigma-status--long').removeClass('tooltip--open');

		// Open
		$('#sigma-card').addClass('active');
		$('#sigma-status--long').addClass('card--open');
		$b.addClass('modal--open');
	})
	.on('click', '#sigma-card, #sigma-card__close', function(e) {
		e.preventDefault();
		$('#sigma-card').removeClass('active');
		$('#sigma-status--long').removeClass('card--open');
		$b.removeClass('modal--open');
	})
	.on('click', '#sigma-card .card__content', function(e) {
		e.stopPropagation();
	});

	// Toggle between manual keyboard input and file upload for node highlighting
	var modeSwitch = function() {
		if($(this).attr('data-mode') === 'manual') {
			// Switch mode
			$(this).attr('data-mode', 'upload');

			// Show dropzone
			$(this).siblings('.dropzone').removeClass('hidden');
		} else {
			// Switch mode
			$(this).attr('data-mode', 'manual');

			// Hide dropzone
			$(this).siblings('.dropzone').addClass('hidden');
		}
	};
	modeSwitch.call($('#sigma__highlight-id__mode-switch')[0]);
	$d.on('click', '#sigma__highlight-id__mode-switch', function(e) {
		e.preventDefault();
		modeSwitch.call(this);
	});

	// Remove node pings
	$d
	.on('mouseover', '#sigma', function() {
		$(this).find('.node-show').fadeOut(1000, function() { $(this).remove(); });
	});

	// Sigma controls
	$d
	.on('click', '#sigma-controls__fullscreen', function(e) {
		e.preventDefault();

		var $t = $(this),
			element = $('#sigma-parent')[0],
			fullscreenCallback = function() {
				// Toggle control status
				$('#sigma-controls__fullscreen').addClass('active').data('fullscreen', true);

				// Resize sigma
				sigmaInst.renderers[0].resize();
			};


		if(!$t.data('fullscreen') || $t.data('fullscreen') === false) {
			if(element.requestFullscreen) {
				element.requestFullscreen();
				fullscreenCallback();
			} else if(element.mozRequestFullScreen) {
				element.mozRequestFullScreen();
				fullscreenCallback();
			} else if(element.webkitRequestFullscreen) {
				element.webkitRequestFullscreen();
				fullscreenCallback();
			} else if(element.msRequestFullscreen) {
				element.msRequestFullscreen();
				fullscreenCallback();
			}
		} else {
			if(document.exitFullscreen) {
				document.exitFullscreen();
			} else if(document.mozCancelFullScreen) {
				document.mozCancelFullScreen();
			} else if(document.webkitExitFullscreen) {
				document.webkitExitFullscreen();
			}
			$t.removeClass('active').data('fullscreen', false);
		}
	})
	.on('click', '#sigma-controls__export-image', function(e) {
		e.preventDefault();
	})
	.on('click', '.sigma-controls__export-image-option', function(e) {
		e.preventDefault();
		$('#cornea-download__canvas-'+$(this).data('image-type')).trigger('click');
	});

	// Sigma searchform controls
	$d
	.on('click', '#sigma-searchform__control__remove-highlights', function() {
		$('#sigma').data('highlighted', false);
		$(this).prop('disabled', true);

		// Reset custom highlighted nodes
		var customHighlightedNodes = [];

		// Reset sigma node colors
		var s = sigmaInst;
		s.graph.nodes().forEach(function(n) {
			n.color = globalVar.sigma.node.color.default;
			n.restingColor = globalVar.sigma.node.color.default;
			n.size = 1.5;
			n.zIndex = 0;
		});
		s.graph.edges().forEach(function(e) {
			e.color = globalVar.sigma.edge.color.default;
			e.restingColor = globalVar.sigma.edge.color.default;
			e.size = 0.5;
			e.zIndex = 0;
		});
		s.refresh({ skipIndexation: true });
	});

	// Escape keypress
	$d
	.on('keyup', function(e) {
		if(e.which === 27) {
			$('.dropzone__message.active').removeClass('active');
			$('#sigma-tooltip').removeClass().empty();
			$('#sigma-card').removeClass('active');
			$('#sigma-status--long').removeClass('card--open').removeClass('tooltip--open');
			$b.removeClass('modal--open');
		}
	});

});