<?php
	require_once('../config.php');

	// Google Recaptcha
	$recaptcha = new \ReCaptcha\ReCaptcha(GRECAPTCHA_API_KEY);

	// LORE1 columns
	$lore1_cols = array(
		array('name' => 'PlantID', 'desc' => '<em>LORE1</em> mutant line identifier', 'size' => 5918438),
		array('name' => 'Batch', 'desc' => 'Batch name of the line', 'size' => 3340027),
		array('name' => 'Chromosome', 'desc' => 'Chromosome in which the <em>LORE1</em> insertion is found', 'size' => 3441026),
		array('name' => 'Position', 'desc' => 'Position in which the <em>LORE1</em> insertion is found', 'size' => 6179836),
		array('name' => 'Orientation', 'desc' => 'Orientation of the <em>LORE1</em> insert', 'size' => 1376362),
		array('name' => 'FwPrimer', 'desc' => 'Forward primer designeed using Primer3', 'size' => 17301368),
		array('name' => 'RevPrimer', 'desc' => 'Reverse primer designeed using Primer3', 'size' => 17330936),
		array('name' => 'PCRInsPos', 'desc' => 'Size of PCR product for lines with <em>LORE1</em> insert', 'size' => 2681535),
		array('name' => 'PCRWT', 'desc' => 'Size of PCR product for lines without <em>LORE1</em> insert', 'size' => 2681531),
		array('name' => 'InsFlank', 'desc' => '&pm;1000bp flanking sequences, relative to the insertion orientation', 'size' => 1377038184),
		array('name' => 'ColCoord', 'desc' => 'Column coordinate for sequencing', 'size' => 3372140),
		array('name' => 'RowCoord', 'desc' => 'Row coordinate for sequencing', 'size' => 3371403),
		array('name' => 'CoordList', 'desc' => 'Coordinates for all instances of the same insertion', 'size' => 7464084),
		array('name' => 'CoordCount', 'desc' => 'Absolute counts of each column and row coordinate', 'size' => 5429930),
		array('name' => 'TotalCoverage', 'desc' => 'Total coverage of the sequencing reads', 'size' => 2769986),
		array('name' => 'Version', 'desc' => 'Version of the genome assembly', 'size' => 4129050)
	);
	foreach($lore1_cols as $v) {
		$lore1_colnames[] = $v['name'];
	}

	if($_POST) {
		if(isset($_POST)) {
			// Parse selected columns
			if(empty($_POST['lore1cols'])) {
				$_SESSION['lore1_download_error'] = 'You have not selected any columns to download.';
				header('Location: lore1.php');
				exit();
			} else {
				$lore1_selected_cols = explode(',', escapeHTML($_POST['lore1cols']));
			}

			// Verify captcha, if user is not logged in
			if(!empty($_POST['user_auth_token'])) {
				$user = auth_verify($_POST['user_auth_token']);
			} else {
				$user = false;
			}
			
			if(!$user) {
				if(!empty($_POST['g-recaptcha-response'])) {
					$resp = $recaptcha->verify($_POST['g-recaptcha-response'], get_ip());
					if(!$resp->isSuccess()) {
						$_SESSION['lore1_download_error'] = 'You have provided an incorrect verification token.';
						header('Location: lore1.php');
						exit();
					}
				} else {
					$_SESSION['lore1_download_error'] = 'You have not provided a verification token.';
					header('Location: lore1.php');
					exit();
				}
			}

			// Verify that columns are in the whitelist
			if(count(array_intersect($lore1_selected_cols, $lore1_colnames)) !== count($lore1_selected_cols)) {
				$_SESSION['lore1_download_error'] = 'You have selected columns that do not exist in the table.';
				header('Location: lore1.php');
				exit();
			}

			// Disable downloads for now
			if(1 === 0) {
				$_SESSION['lore1_download_error'] = 'Download currently disabled. Nice try, though.';
				header('Location: lore1.php');
				exit();
			}

			// Columns
			$cols = array_intersect($lore1_selected_cols, $lore1_colnames);

			// Perform query and generate download file
			try {
				$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);		

				// Prepare and execute query
				$q = $db->prepare("SELECT ".implode(',', $cols)." FROM lore1ins WHERE Version = '3.0'");
				$q->execute();

				// Start buffer
				ob_start();

				$fp = fopen('php://output', 'w');
				if($fp && $q) {
					header('Content-Type: text/csv');
					header('Content-Disposition: attachment; filename="lore1-raw-data_'.date("Y-m-d_H-i-s").'.csv"');
					header('Pragma: no-cache');
					header('Expires: 0');
					fputcsv($fp, $cols);
					while($row = $q->fetch(PDO::FETCH_ASSOC)) {
						fputcsv($fp, $row);
					}
					die;
				}

				// Flush buffer
				ob_end_flush();

				header('Location: lore1.php');
				exit();
			} catch(PDOException $e) {
				$_SESSION['lore1_download_error'] = 'We have encountered an error connecting to the database.';
				header('Location: lore1.php');
				exit();
			}
		} else {
			$_SESSION['lore1_download_error'] = 'You have submitted an incomplete form. Please try again.';
			header('Location: lore1.php');
			exit();
		}
	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>LORE1 Raw Data &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
</head>
<body class="data lore1-raw-data">

	<div id="wrap">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<h1><em>LORE1</em> Raw Data</h1>
		<p>The raw data for <em>LORE1</em> insertions mapped against v3.0 of the <em>L. japonicus</em> genome assembly can be downloaded here. Data is provieded on an as-is basis.</p>');
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title('<em>LORE1</em> raw data');
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<?php 
			if(isset($_SESSION['lore1_download_error'])) {
				echo '<p class="user-message warning">'.$_SESSION['lore1_download_error'].'</p>';
				unset($_SESSION['lore1_download_error']);
			}
		?>
		<div id="download__message" class="align-center" style="display: none;"></div>
		<form id="lore1-raw-data-form" class="has-group" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<div>
				<p class="user-message minimal note"><strong>Step 1:</strong> Select one or more columns to be downloaded:</p>
				<table id="rows" class="table--no-borders">
					<thead>
						<tr>
							<th class="chk"><input type="checkbox" class="ca" /></th>
							<th>Column name</th>
							<th>Description</th>
							<th data-type="numeric">Estimated size (MB)</th>
						</tr>
					</thead>
					<tbody>
						<?php
							$input_count = 0;
							foreach ($lore1_cols as $key => $value) {
								$col_name = $value['name'];
								$col_desc = $value['desc'];
								$file_size_mb = number_format($value['size']/(1000*1000), 1, '.', '');
								$file_size_bytes = intval($value['size']);
								echo '<tr><td class="chk"><input tabindex="'.$key.'" type="checkbox" id="column-'.$col_name.'" value="'.$col_name.'" /></td><td>'.$col_name.'</td><td>'.$col_desc.'</td><td data-type="numeric" data-file-size="'.$file_size_bytes.'">'.$file_size_mb.'</td></tr>';
								$input_count++;
							}
						?>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="2"></td>
							<td data-type="numeric">Estimated download size: </td>
							<td data-type="numeric" id="size-estimate"><strong><span>0 MB</span></strong></td>
						</tr>
					</tfoot>
				</table>
			</div>

			<div role="group">
				<p class="user-message minimal note"><strong>Step 2 (optional):</strong> Re-order the columns you have selected, if required.</p>
				<ul class="sort-list hover ui-state-empty" id="cols-list"></ul>
				<input type="hidden" name="lore1cols" id="lore1cols" />
			</div>

			<?php if(!is_logged_in()) { ?>
			<div role="group" class="cols">
				<p class="user-message minimal note full-width"><strong>Step 3:</strong> Verify that you are indeed human.</p>
				<label>Human?</label>
				<div class="col-two" id="google-recaptcha"></div>
			</div>
			<?php } else { ?>
			<input type="hidden" name="user_auth_token" value="<?php echo $_COOKIE['auth_token']; ?>" />
			<?php } ?>

			<button id="button--submit" type="submit" tabindex="<?php $input_count + 2; ?>" class="disabled" disabled><span class="icon-download">Download selected columns (<span class="count">0</span>) as CSV</span></button>
		</form>
	</section>

	</div>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script>
		// Ensure that at least one column is selected
		var columnCheck = function() {
				var error = false;
				<?php if(!is_logged_in()) { ?>
				if(!grecaptcha.getResponse() || grecaptcha.getResponse().length === 0) {
					error = true;
					return !error;
				}
				<?php } ?>

				if($('#rows tbody input:checkbox:checked').length < 1) {
					$('#lore1-raw-data-form :input[type="submit"]')
						.addClass('disabled')
						.prop('disabled', true);
					error = true;
				} else {
					$('#lore1-raw-data-form :input[type="submit"]')
						.removeClass('disabled')
						.prop('disabled', false);
				}

				return !error;
			},
			updateColumnList = function() {
				var cols = $('#cols-list').find('li').map(function() { return $(this).attr('data-value'); }).get();
				$('#lore1cols').val(cols.join(','));
				$('#button--submit span.count').text(cols.length);
			},
			checkSortableSize = function() {
				if($('#cols-list li').length) {
					$('#cols-list').removeClass('ui-state-empty');
				} else {
					$('#cols-list').addClass('ui-state-empty');
				}
			},
			computeFileSize = function() {
				var sizes = $('#rows tbody input:checkbox:checked').map(function() {
						return parseInt($(this).closest('tr').find('td[data-file-size]').attr('data-file-size'));
					}).get(),
					totalSize = function() {
						if (sizes.length) {
							var sizeSum = sizes.reduce(function(a,b) { return a + b; });
							if (sizeSum > 1000000000) {
								return (sizeSum/(1000*1000*1000)).toFixed(3) + ' GB';
							} else {
								return (sizeSum/(1000*1000)).toFixed(1) + ' MB';
							}
						} else {
							return '0 MB';
						}
					};

				$('#size-estimate span').text(totalSize());
			};

		// Check validity before submission
		$('#lore1-raw-data-form').on('submit', function(e) {
			e.preventDefault();
			var valid = columnCheck(),
				$t = $(this);

			if(valid) {
				$('#button--submit').addClass('disabled').prop('disabled', true).html('Download initialized, please wait&hellip;');
				$w.scrollTop(0);
				$t.slideUp();
				$('#download__message').html('<div class="loader"><svg><circle class="path" cx="40" cy="40" r="30" /></svg></div><p class="loading-text">Data download initialized, please wait for a few moments&hellip;</p>').slideDown();

				// Officially start download request
				window.setTimeout(function() {
					$t[0].submit();
				}, 500);

				// Display help after 5 seconds
				window.setTimeout(function() {
					$('#download__message p.loading-text').append('<br /><a href="'+window.location.href+'">Repeat download request if no response is received.</a>');
				}, 5000);
			}
		});

		// Add checked items to sortable list
		$('#rows tbody input:checkbox').on('change', function() {
			var $t = $(this),
				$s = $('#cols-list');

			if($t.prop('checked')) {
				if(!$('#sortable-item__'+$t.val()).length) {
					$s.append('<li id="sortable-item__'+$t.val()+'" data-value="'+$t.val()+'">'+$t.val()+'<span class="icon-cancel icon--no-spacing"></span></li>');
				}
			} else {
				$('#sortable-item__'+$t.val()).remove();
			}

			// Sortable filling check
			checkSortableSize();

			// Update hidden column list
			updateColumnList();

			// Compute file size
			computeFileSize();

			// Validate form
			columnCheck();
		});

		// jQuery sortable
		$('#cols-list')
		.sortable({
			placeholder: 'ui-state-highlight',
			activate: function() {
				$(this).addClass('ui-state-active');
			},
			deactivate: function() {
				$(this).removeClass('ui-state-active');
			},
			update: function() {
				// Sortable filling check
				checkSortableSize();

				// Update hidden column list
				updateColumnList();

				// Compute file size
				computeFileSize();
			}
		})
		.on('click', 'span.icon-cancel', function() {
			$(this).closest('li').fadeOut(250, function() {
				$(this).remove();
				$('#rows').find('#column-'+$(this).attr('data-value')).prop('checked', false).trigger('change');
			});
		});

		<?php if(!is_logged_in()) { ?>
		// Google ReCaptcha
		var onloadCallback = function() {
				grecaptcha.render('google-recaptcha', {
					'sitekey' : '6Ld3VQ8TAAAAAO7VZkD4zFAvqUxUuox5VN7ztwM5',
					'callback': verifyCallback,
					'expired-callback': expiredCallback,
					'tabindex': <?php echo $input_count + 1; ?>
				});
			},
			verifyCallback = function(response) {
				columnCheck();
			},
			expiredCallback = function() {
				grecaptcha.reset();
			};
		<?php } ?>
	</script>
	<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&amp;render=explicit" async defer></script>
</body>
</html>