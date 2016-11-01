;(function($, window, document, undefined) {

	'use strict';

		// Create the defaults once
		var pluginName = 'dropzone',
			defaults = {
				files: {
					maxSize: 100 * 1024 * 1024,
					maxCount: 1
				},
				reader: {
					chunkSize: 65536
				}
			};

		// The actual plugin constructor
		function Plugin (element, options) {
			this.element = element;
			this.settings = $.extend({}, defaults, options);
			this._defaults = defaults;
			this._name = pluginName;
			this.init();
		}

		// Avoid Plugin.prototype conflicts
		$.extend(Plugin.prototype, {
			init: function() {
				// Bind events
				this.bindEvents();
			},
			bindEvents: function() {
				var t = this,
					$t = $(this.element),
					opts = this.settings;

				$t
				.on('dragover', function(e) {
					e.preventDefault();
					e.stopPropagation();
					e.originalEvent.dataTransfer.dropEffect = 'copy';

					$(this)
						.find('.dropzone__input').removeClass('hidden').end()
						.find('.dropzone__message').addClass('hidden').empty();
				})
				.on('dragexit', function(e) {
					$(this).find('.dropzone__input').addClass('hidden');
				})
				.on('dragenter', function(e) {
					e.preventDefault();
					e.stopPropagation();
				})
				.on('drop', function(e) {

					e.stopPropagation();
					e.preventDefault();

					// Trigger event
					$t.trigger('drop.dropzone');

					// Preprocess
					t.preprocess(e.originalEvent.dataTransfer.files);
				});

				$d.on('keyup', function(e) {
					if(e.which === 27) {
						$t.find('.dropzone__message, .dropzone__input').addClass('hidden');
					}
				});

				// Fallback for people who use input[type='file']
				$t.find('input[type="file"]')
				.on('change', function(e) {

					// Trigger event
					$t.trigger('drop.dropzone');

					// Preprocess
					t.preprocess(e.originalEvent.dataTransfer.files);
				});
			},
			preprocess: function(files) {
				var $t = $(this.element),
					opts = this.settings;

				// Retrieve FileList
				var f;
				if(files.length > opts.files.maxCount) {
					$t.trigger('fail.dropzone', [{
						'message': 'Multiple files detected. Please upload only a single file.'
					}]);
					return;
				} else if(files.length === 0) {
					$t.trigger('fail.dropzone', [{
						'message': 'No file detected. Please upload a single file.'
					}]);
					return;
				} else {
					f = files[0];
					if(f.size > opts.files.maxSize) {
						$t.trigger('fail.dropzone', [{
							'message': 'File size is too big.'
						}]);
						return;
					}
				}

				// Trigger event
				$t.trigger('valid.dropzone')

				// Read file
				this.readFile(f);
			},
			readFile: function(f) {
				var t = this,
					$t = $(this.element),
					opts = this.settings;

				// Read file
				// We try to read in chunks to that large files can be read without issues
				// http://stackoverflow.com/a/12713326/395910
				var Uint8ToString = function(u8a){
						var CHUNK_SZ = 0x8000;
						var c = [];
						for (var i=0; i < u8a.length; i+=CHUNK_SZ) {
							c.push(String.fromCharCode.apply(null, u8a.subarray(i, i+CHUNK_SZ)));
						}
						return c.join('');
					},
					fileProgress = function(offset) {
						$t.trigger('progress.dropzone', [{
							progress: offset/size
						}]);
					};
					
				var size = f.size,
					offset = 0,
					chunk = f.slice(offset, offset + opts.reader.chunkSize),
					plainText = '';
				
				var readChunk = function() {
					var reader = new FileReader();

					// When chunk is loaded
					reader.onload = function(e) {

						// Read chunks
						var chunkData;
						plainText += e.target.result;

						// Update offset for next chunk
						offset += opts.reader.chunkSize;

						// Move on to next chunk if available
						if(offset < size) {
							// Splice the next Blob from the File
							chunk = f.slice(offset, offset + opts.reader.chunkSize);
						 
							// Recurse to hash next chunk
							readChunk();

						// Done reading
						} else {

							// Finish progress
							fileProgress(size);

							// Hand file off to processing
							t.fileComplete(plainText);
						}
					};

					// Progress
					reader.onprogress = function(e) {
						if(e.lengthComputable) {
							fileProgress(offset + e.loaded);
						}
					};

					// Allow users to manually abort reading
					var aborted = false;
					$d.on('keyup', function(e) {
						if(e.which === 27 && !aborted) {
							reader.abort();
							aborted = true;
							$t.trigger('fail.dropzone', [{
								message: 'File upload aborted.'
							}]);
							return false;
						}
					});

					
					reader.readAsText(chunk);
				};
				

				// Start hashing chunks
				readChunk();

			},
			fileComplete: function(fileContent) {
				var $t = $(this.element);

				// Reset progress
				$t.find('.dropzone__input').addClass('hidden').find('span.progress').css('width', 0);

				// Trigger event
				$t.trigger('done.dropzone', [{
					data: fileContent
				}]);
			}
		});

		// A really lightweight plugin wrapper around the constructor,
		// preventing against multiple instantiations
		$.fn[pluginName] = function(options) {

			var args = arguments;

			// Check the options parameter
			// If it is undefined or is an object (plugin configuration),
			// we create a new instance (conditionally, see inside) of the plugin
			if (options === undefined || typeof options === 'object') {

				return this.each(function() {
					// Only if the plugin_fluidbox data is not present,
					// to prevent multiple instances being created
					if (!$.data(this, "plugin_" + pluginName)) {

						$.data(this, "plugin_" + pluginName, new Plugin(this, options));
					}
				});

			// If it is defined, but it is a string, does not start with an underscore and does not call init(),
			// we allow users to make calls to public methods
			} else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
				var returnVal;

				this.each(function() {
					var instance = $.data(this, 'plugin_' + pluginName);
					if (instance instanceof Plugin && typeof instance[options] === 'function') {
						returnVal = instance[options].apply(instance, Array.prototype.slice.call(args, 1));
					} else {
						console.warn('The method "' + options + '" used is not defined. Please make sure you are calling the correct public method.');
					}
				});
				return returnVal !== undefined ? returnVal : this;
			}

			// Return to allow chaining
			return this;
		};

})(jQuery, window, document);