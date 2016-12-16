<?php

namespace LotusBase\Component;

/* Component\PageHeader */
class PageHeader {

	private $page_header = array();
	private $search_form;

	// Construct
	public function __construct() {

		// Default internal values
		$this->page_header['content'] = '';
		$this->page_header['css'] = array();

		// Default header class
		$this->page_header['class'] = array();

		// Default header theme
		$this->page_header['theme'] = 'default';
		$this->page_header['themes'] = array(
			'default' => array(
					'radial-gradient' => 'radial-gradient(ellipse at 50% 100%, rgba(238, 238, 238, 0.25) 0%, rgba(238, 238, 238, 0) 50%)',
					'linear-gradient' => 'linear-gradient(90deg, rgba(51, 101, 138, 0.85) 0%, rgba(51, 138, 132, 0.85) 100%)'
				),
			'white' => array(
					'radial-gradient' => 'radial-gradient(ellipse at 50% 100%, rgba(238, 238, 238, 0.25) 0%, rgba(238, 238, 238, 0) 50%)',
					'linear-gradient' => 'linear-gradient(90deg, rgba(204, 204, 204, 0.95) 0%, rgba(221, 221, 221, 0.95) 100%)'
				)
			);

		// Construct new form
		$this->search_form = new \LotusBase\Component\SiteSearchForm();
		$this->search_form->set_id('nav-search-form');
		$this->search_form->set_content_before('<button class="pictogram icon-search icon--no-spacing"></button>');
		$this->search_form->update_options(array(
			'general' => array('placeholder' => 'Search term'),
			'lore1' => array('placeholder' => 'Search term'),
			'gene' => array('placeholder' => 'Search term', 'text' => 'Gene')
			));
	}

	// Set header content
	public function set_header_content($header_content) {
		$this->page_header['content'] = $header_content;
	}

	// Set header theme
	public function set_header_theme($header_theme) {
		$this->page_header['theme'] = $header_theme;
	}

	// Set header class
	public function add_header_class($header_class) {
		$header_class_list = split('/\s+/', $header_class);
		$header_class_merged = array_merge($this->page_header['class'], $header_class_list);
		$this->page_header['class'] = array_unique($header_class_merged);
	}

	// Set header background image
	public function set_header_background_image($opts) {
		// Default settings
		$header_default_background_image = $this->page_header['themes'][$this->page_header['theme']];

		// Merge options
		if(is_array($opts)) {
			$header_background_image = array_merge($header_default_background_image, $opts);
			if(!preg_match("/^url(.*)$/", $header_background_image['image-url'])) {
				$header_background_image['image-url'] = 'url('.$header_background_image['image-url'].')';
			}
			$this->page_header['css']['background-image'] = $header_background_image;
		} else {
			if(!empty($opts)) {
				$this->page_header['css']['background-image'] = array_merge($header_default_background_image, array('image-url' => 'url('.$opts.')'));
			}
		}
	}

	// Private function: generate styles
	private function set_header_styles() {
		$css = $this->page_header['css'];
		$inline_css = '';
		foreach ($css as $prop => $values) {
			$inline_css .= $prop . ': ' . implode(',', $values) . ';';
		}
		return $inline_css;
	}

	// Echo form
	public function get_header() {

		// Get user data if logged in
		$user = is_logged_in();

		return '<div id="lotusbase__wrap">
	<div id="lotusbase__container">
		<header id="header" class="'.(count($this->page_header['class']) ? implode(' ', $this->page_header['class']) : '').'" style="'.$this->set_header_styles().'">
			'.(!empty($this->page_header['content']) ? '<div class="header-content">'.$this->page_header['content'].'</div>' : '').'
			<nav class="cf main">
				<ul>
					<li class="h-home"><a href="'.WEB_ROOT.'/" title="Home"><img class="logo" src="'.WEB_ROOT.'/dist/images/branding/logo.svg" alt="Lotus Base" title="Lotus Base" /><span><em>Lotus</em> Base</span></a></li>
					<li class="h-lore1" data-group="lore1"><a href="'.WEB_ROOT.'/lore1/" title="LORE1 Resources"><em>LORE1</em></a>
						<ul>
							<li><a href="'.WEB_ROOT.'/lore1/search" title="Search for LORE1 lines"><em>LORE1</em> lines search</a></li>
							<li><a href="'.WEB_ROOT.'/lore1/order" title="Order LORE1 lines">Order lines</a></li>
							<li><a href="'.WEB_ROOT.'/lore1/order-search" title="Search for ordered LORE1 lines">Order history</a></li>
							<li><a href="'.WEB_ROOT.'/lore1/order-status" title="Check the status of a LORE1 order">Order status</a></li>
							'.(is_allowed_access('/tools/primers') ? '<li><a href="'.WEB_ROOT.'/tools/primers" title="Genotyping Primer Order Sheet">Genotyping Primers</a></li>' : '').'
						</ul>
					</li>
					<li class="h-tools" data-group="tools"><a href="'.WEB_ROOT.'/tools" title="Tools">Tools</a>
						<ul>
							'.(is_allowed_access('/blast/') ? '<li><a href="'.WEB_ROOT.'/blast-carb/" title="BLAST">BLAST</a></li>' : '<li><a href="'.WEB_ROOT.'/blast/" title="BLAST">BLAST</a></li>').'
							<li>
								<a>Co-expression Toolkit  (<strong>CORx</strong>)</a>
								<ul>
									<li><a href="'.WEB_ROOT.'/tools/corgi" title="Correlated Genes Identifier (CORGI)">Correlated Genes Identifier (<strong>CORGI</strong>)</a></li>
									<li><a href="'.WEB_ROOT.'/tools/cornea" title="Coexpression Network Analysis (CORNEA)">Coexpression Network Analysis (<strong>CORNEA</strong>)</a></li>
								</ul>
							</li>
							<li>
								<a>Expression Analysis &amp; Visualisation</a>
								<ul>
									<li><a href="'.WEB_ROOT.'/expat/" title="Expression Atlas (ExpAt)">Expression Atlas (<strong>ExpAt</strong>)</a></li>
									<li><a href="'.WEB_ROOT.'/expat/mapping" title="Gene/Probe mapping for ExpAt">Gene/Probe Mapping for ExpAt</a></li>
								</ul>
							</li>
							<li><a href="'.WEB_ROOT.'/genome" title="Genome Browser">Genome Browser</a></li>
							'.(is_allowed_access('/go/') ? '
							<li>
								<a>Gene Ontology</a>
								<ul>
									<li><a href="'.WEB_ROOT.'/go/enrichment" title="GO Enrichment Analysis">GO Enrichment Analysis <span class="badge">BETA</span></a></li>
									<li><a href="'.WEB_ROOT.'/go/explorer" title="GO Explorer">GO Explorer <span class="badge">BETA</span></a></li>
								</ul>
							</li>
							' : '').'
							'.(is_allowed_access('/tools/phyalign') ? '<li><a href="'.WEB_ROOT.'/tools/phyalign" title="PhyAlign">PhyAlign (<abbr title="Multiple Sequence Alignment">MSA</abbr> + Phylogeny) <span class="badge">BETA</span></a></li>' : '').'
							<li>
								<a>Sequence Toolkit</a>
								<ul>
									<li><a href="'.WEB_ROOT.'/tools/seqpro" title="Sequence Processor (SeqPro)">Sequence Processor (<strong>SeqPro</strong>)</a></li>
									<li><a href="'.WEB_ROOT.'/tools/seqret" title="Sequence Retrieval (SeqRet)">Sequence Retrieval (<strong>SeqRet</strong>)</a></li>
								</ul>
							</li>
							<li>
								<a>Transcript Toolkit (<strong>TRx</strong>)</a>
								<ul>
									<li><a href="'.WEB_ROOT.'/tools/tram" title="Transcript Mapper (TRAM)">Transcript Mapper (<strong>TRAM</strong>)</a></li>
									<li><a href="'.WEB_ROOT.'/tools/trex" title="Transcript Explorer (TREX>)">Transcript Explorer (<strong>TREX</strong>)</a></li>
								</ul>
							</li>
							<li><a href="'.WEB_ROOT.'/view/" title="View">View</a></li>
						</ul>
					</li>
					<li class="h-data" data-group="data"><a href="'.WEB_ROOT.'/data/" title="Data">Data</a>
						<ul>
							<li><a href="'.WEB_ROOT.'/data/codon-usage" title="Codon Usage Table">Codon Usage</a></li>
							<li><a href="'.WEB_ROOT.'/data/download" title="Downloads">Downloads</a></li>
							<li><a href="'.WEB_ROOT.'/data/lore1" title="LORE1 Raw Data"><em>LORE1</em> Raw Data</a></li>
						</ul>
					</li>
					<li class="h-meta" data-group="meta"><a href="'.WEB_ROOT.'/meta/" title="Info">Info</a>
						<ul>
							<li><a href="'.WEB_ROOT.'/meta/about" title="About Lotus Base">About the Project</a></li>
							<li><a href="'.WEB_ROOT.'/meta/citation" title="Citing Lotus Base">Citation Guide</a></li>
							<li><a href="'.WEB_ROOT.'/meta/team" title="Lotus Base Team">Meet the Team</a></li>
							<li><a href="'.WEB_ROOT.'/blog" title="Blog">Blog</a></li>
							<li><a href="'.WEB_ROOT.'/meta/faq" title="Frequently Asked Questions">FAQ</a></li>
							<li><a href="'.WEB_ROOT.'/meta/legal" title="Privacy &amp; Terms of Use">Privacy &amp; Terms</a></li>
							<li><a href="'.WEB_ROOT.'/meta/contact" title="Contact Us">Contact</a></li>
							<li><a href="'.WEB_ROOT.'/issue" title="Open issue or propose enhancements">Open Issue</a></li>
						</ul>
					</li>
					'.($user ? '<li class="h-user" data-group="user"><a href="'.WEB_ROOT.'/users/" title="Your dashboard"><span class="icon-user">Hi, '.($user ? '<strong>'.$user['FirstName'].'</strong>' : 'user').'</span></a><ul>
							'.($user['Authority'] <= 3 ? '<li><a href="'.WEB_ROOT.'/admin" title="Admin">Admin</a></li>' : '').'
							<li><a href="'.WEB_ROOT.'/users/" title="Dashboard">Dashboard</a></li>
							<li><a href="'.WEB_ROOT.'/users/account" title="View your account details">Account</a></li>
							<li><a href="'.WEB_ROOT.'/users/data" title="View data generated from your account">Data</a></li>
							<li><a href="'.WEB_ROOT.'/users/logout?redir='.urlencode($_SERVER['PHP_SELF']).'" title="Logout">Logout</a></li>
						</ul></li>' : '<li class="h-user" data-group="user"><a href="'.WEB_ROOT.'/users/login?redir='.urlencode($_SERVER['PHP_SELF']).'" title="Login for existing Lotus Base users">Login</a><ul>
							<li><a href="'.WEB_ROOT.'/users/login?redir='.urlencode($_SERVER['PHP_SELF']).'" title="Login for existing Lotus Base users">Login</a></li>
							<li><a href="'.WEB_ROOT.'/users/register" title="Register for a user account on Lotus Base">Register</a></li>
							<li><a href="'.WEB_ROOT.'/users/reset" title="Reset password">Forgot password?</a></li>
						</ul></li>').'
					<li class="h-search">
						<a href="'.WEB_ROOT.'/search" title="Search site" class="search-link">Search</a>
						'.$this->search_form->get_form().'
					</li>
				</ul>
			</nav>

			<div id="top-notifications">
				<div id="cookie-consent" class="site-notification">
					<span class="pictogram icon-attention"></span>
					<p><em>Lotus</em> Base uses cookies to allow user preferences to be stored. By continuing to browse the site you are agreeing to our use of cookies.<br /><a href="'.WEB_ROOT.'/meta/legal" class="icon button button--small" data-action="read">Read more</a> <a href="#" class="icon button" data-action="accept">Accept cookie use and dismiss message</a></p>
				</div>

				<div id="browser-features-warning" class="site-notification">
					<span class="pictogram icon-attention"></span>
					<p>Your browser is unable to support new features implemented in HTML5 and CSS3 to render this site as intended. Your experience may suffer from functionality degradation but the site should remain usable. We strongly recommend the latest version of <a href="http://google.com/chrome/" title="Google Chrome">Google Chrome</a>, <a href="https://support.apple.com/en-us/HT204416" title="OS X Safari">OS X Safari</a> or <a href="http://mozilla.org/firefox/" title="Mozilla Firefox">Mozilla Firefox</a>. As Safari is bundled with OS X, if you are unable to upgrade to a newer version of OS X, we recommend using an open source browser. <a href="#" class="icon button button--small" title="I understand" data-action="dismiss">Dismiss message</a></p>
				</div>

				'.(is_intranet_client() && !is_logged_in() ? '
				<div id="intranet-client" class="site-notification">
					<p>You appear to be visiting from '.is_intranet_client()['HostName'].(get_ip() ? ' with the IP address of <strong>'.get_ip().'</strong>' : '').'. We are currently migrating away from IP verification, and using user logins as a means to fine tune user access to selected data. <a href="'.WEB_ROOT.'/users/login" class="icon-user button button--small" data-action="read">Login</a> <a href="'.WEB_ROOT.'/users/register" class="icon-user-plus button button--small" data-action="read">Register as new user</a> <a href="#" class="icon button button--small" title="I understand" data-action="dismiss">Dismiss message</a></p>
				</div>
				' : '').'

				'.($user && intval($user['Verified']) === 0 && strtotime($user['VerificationKeyTimestamp']) >= strtotime('-24 hours') ? '
				<div id="user-verification" class="site-notification">
					<p>Thank you for registering with <em>Lotus</em> Base. We noticed that you have not verified your account&mdash;please do so <span class="countdown" data-duration="'.(strtotime($user['VerificationKeyTimestamp'])+24*60*60-strtotime('now')).'"></span>, or the account will be inactivated and the email recycled. A link has been sent to your registered email, at <strong>'.$user['Email'].'</strong>.</p>
				</div>
				' : '').'

			</div>

		</header>';
	}
}
?>