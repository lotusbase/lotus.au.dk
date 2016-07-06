<?php
	require_once('config.php');

	$search_form = new \LotusBase\SiteSearchForm();
?>
<!doctype html>
<html lang="en">
<head>
	<title>Site Search &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
</head>
<body class="site-search">
	<?php $header = new \LotusBase\PageHeader(); echo $header->get_header(); ?>

	<section class="wrapper">
		<?php if(isset($_GET['q']) && !empty($_GET['q'])) { ?>
			<h2>Search results for '<?php echo escapeHTML($_GET['q']); ?>'</h2>
			<div class="toggle hide-first">
				<h3><a href="#">Search again</a></h3>
				<?php echo $search_form->get_form(); ?>
			</div>
			<script>
				(function() {
					var cx = '015094331014386003101:vgzeqxc0y-0';
					var gcse = document.createElement('script');
					gcse.type = 'text/javascript';
					gcse.async = true;
					gcse.src = (document.location.protocol == 'https:' ? 'https:' : 'http:') +
							'//cse.google.com/cse.js?cx=' + cx;
					var s = document.getElementsByTagName('script')[0];
					s.parentNode.insertBefore(gcse, s);
				})();
			</script>
			<gcse:searchresults-only></gcse:searchresults-only>
		<?php } else { ?>
			<h2>Site Search</h2>
			<p><?php echo (empty($_GET['q']) ? 'You have not specified a search term. Please repeat your search with a keyword:' : 'You can search our site with the form below:'); ?></p>
			<?php echo $search_form->get_form(); ?>
		<?php } ?>

	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>
