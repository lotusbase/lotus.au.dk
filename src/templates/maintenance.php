<?php
	function getFriendlyTime($dateTimeObject) {
		return $dateTimeObject->format("H:i");
	}

	function getHtmlTime($dateTimeObject, $use24hour = true) {
		$html5DateTime = $dateTimeObject->format("Y-m-d\TH:i:s");
		$friendlyDateTime = getFriendlyTime($dateTimeObject);

		if (!$use24hour) {
			$friendlyDateTime = $dateTimeObject->format("h.ia");
		}

		return "<time datetime=\"$html5DateTime\" title=\"$html5DateTime\">{$friendlyDateTime}</time>";
	}

	if (preg_match('/\/api\/.*/', $_SERVER['REQUEST_URI'])) {
		header('Content-Type: application/json');
		echo json_encode(array(
			'status' => 503,
			'message' => 'Server currently under maintenance between CET (GMT +1) '.getFriendlyTime($maintenanceStart).' and '.getFriendlyTime($maintenanceEnd)
		));
		exit();
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Lotus Base is under maintenance</title>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<!-- Stylesheets -->
		<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/ggs.min.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/styles.min.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/lib/header-icon-css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/print.min.css" type="text/css" media="print" />
		<link rel="shortcut icon" type="image/png" href="<?php echo WEB_ROOT; ?>/dist/images/branding/favicon-32x32.png" />

		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,300,400,600|Open+Sans+Condensed:300|Lora:400,400italic,700,700italic" rel="stylesheet prefetch" type="text/css">
		<style>
			html {
				overflow: hidden;
    			height: 100%;
			}
			body {
				background-image:
					linear-gradient(180deg, rgba(0, 0, 0, .125), rgba(0, 0, 0, .5)),
					linear-gradient(135deg, rgba(158, 115, 50, .5), rgba(60, 123, 115, .5)),
					url(/dist/images/background/trianglify.svg);
				background-repeat: no-repeat;
				color: #fff;
				height: 100vh;
			}

			section {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				height: 100vh;
			}

			#logo {
				display: block;
				margin: 0 auto 1.5rem;
				width: 8rem;
				height: 8rem;
			}

			h1 {
				font-weight: 600;
				text-align: center;
			}

			h1 + span.byline {
				margin-bottom: 3rem;
			}

			p {
				font-size: 1.2em;
				line-height: 2rem;
			}
		</style>
	</head>
	<body>
		<section>
			<header>
				<h1>
					<img id="logo" src="<?php echo WEB_ROOT; ?>/dist/images/branding/logo.svg" alt="Lotus Base" title="Lotus Base" /><span>
					Scheduled maintenance
				</h1>
				<span class="byline">
					Expected downtime:
					<strong><abbr title="Central European Time; GMT+1">CET (GMT+1)</abbr>
					<?php echo getHtmlTime($maintenanceStart); ?> to <?php echo getHtmlTime($maintenanceEnd); ?></strong>
				</span>
				<p>
					We are currently performing our weekly database maintenance between
					<strong><abbr title="Central European Time; GMT+1">CET (GMT+1)</abbr>
					<?php echo getHtmlTime($maintenanceStart); ?> and <?php echo getHtmlTime($maintenanceEnd); ?></strong>
					(<?php echo getHtmlTime($maintenanceStart, false); ?>&ndash;<?php echo getHtmlTime($maintenanceEnd, false); ?>)
					every <?php echo $maintenanceStart->format("l"); ?>.
					We apologize for the inconvenience.
					Please check back again later after the stipulated maintenance end time.
				</p>
			</header>
		</section>
	</body>
</html>