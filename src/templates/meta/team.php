<?php 
	require_once('../config.php');
	require_once(DOC_ROOT.'/lib/team.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>Team&mdash;Meta&mdash;Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Meet the team behind Lotus Base'
			));
		echo $document_header->get_document_header();
	?>
	<link href="<?php echo WEB_ROOT; ?>/dist/css/meta.min.css" rel="stylesheet" />

	<!-- Load Mapbox.js -->
	<script src='https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.js'></script>
	<link href='https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css' rel='stylesheet' />

	<!-- Load Mapbox-gl-js -->
	<script src='https://api.tiles.mapbox.com/mapbox-gl-js/v0.11.3/mapbox-gl.js'></script>
	<link href='https://api.tiles.mapbox.com/mapbox-gl-js/v0.11.3/mapbox-gl.css' rel='stylesheet' />

</head>
<body class="meta team wide">
	<?php
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content('<h1>Meet the Team</h1><p><strong><em>Lotus</em> Base</strong> is a project funded by the Centre for Carbohydrate Recognition and Signalling, Aarhus University, to make genomics, proteomics and other <em>Lotus</em>-related processes easily accessible to the research community.</p>');
		$header->set_header_background_image(WEB_ROOT.'/dist/images/team/carb.jpg');
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title(array('Info', '<em>Lotus</em> Base Team'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
	<?php
		echo '<ul class="team-list masonry"><li class="masonry-sizer"></li><li class="masonry-gutter"></li>';

		// Fetch team bg images from directory
		$team_bgs = glob(DOC_ROOT.'/dist/images/team_bg/' . '*.jpg', GLOB_BRACE);
		shuffle($team_bgs);

		// Loop through each team member
		$member_count = 0;
		foreach($team as $i => $member) {
			if(isset($member['mail']['au'])) {
				$member_link = 'http://pure.au.dk/portal/en/'.$member['mail']['au'];
			} else {
				$member_link = '';
			}

			$bg_index = $member_count % count($team_bgs);
			$name = preg_replace(array('/\s+/', '/\./'), array('_',''), strtolower($member['name']));
			?>
			<li itemscope itemtype="http://schema.org/Person" id="<?php echo $name; ?>" class="team-card masonry-item" data-order="<?php echo $member_count + 1;?>" data-order-original="<?php echo $member_count + 1; ?>">
				<div class="team-card__avatar" style="background-image: url('<?php echo(WEB_ROOT.'/dist/images/team_bg/'.basename($team_bgs[$bg_index])); ?>');"><img itemprop="image" src="<?php echo WEB_ROOT; ?>/dist/images/team/<?php echo $member['avatar'];?>" alt="<?php echo $member['name'];?>" title="<?php echo $member['name'];?>" /></div>
				<div class="team-card__meta">
					<span class="team-card__name"><a href="<?php echo $member_link; ?>"><span itemprop="name"><?php echo $member['name'];?></span></a></span>
					<span class="team-card__role" itemprop="jobTitle"><?php echo $member['role'];?></span>
					<?php if(isset($member['social'])) { ?>
					<ul class="team-card__social hidden cols flex-wrap__nowrap justify-content__center">
						<?php
							foreach ($member['social'] as $network => $n) {
								echo '<li><a href="'.$n['url'].'" title=""><span class="icon-'.$network.' icon--no-spacing"></span></a></li>';
							}
						?>
					</ul>
					<?php } ?>
					<div class="team-card__description" itemprop="description"><?php echo (isset($member['description']) ? $member['description'] : ''); ?></div>
				</div>
			</li>
			<?php
			$member_count++;
		}
		echo '</ul>';
	?>
	</section>

	<section class="wrapper">
		<div id="map"></div>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/masonry/3.3.2/masonry.pkgd.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/3.2.0/imagesloaded.pkgd.min.js"></script>
	<script src="<?php echo WEB_ROOT; ?>/dist/js/team.min.js"></script>
</body>
</html>
