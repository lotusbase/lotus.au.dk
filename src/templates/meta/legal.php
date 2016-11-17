<?php 
	require_once('../config.php');
?>
<!doctype html>
<html lang="en">
<head>
	<title>Terms of Use &mdash; Lotus Base</title>
	<?php
		$document_header = new \LotusBase\Component\DocumentHeader();
		$document_header->set_meta_tags(array(
			'description' => 'Privacy policy and terms of use of Lotus Base'
			));
		echo $document_header->get_document_header();
	?>
</head>
<body>
	<?php
		$header = new \LotusBase\Component\PageHeader();
		echo $header->get_header();

		$breadcrumbs = new \LotusBase\Component\Breadcrumbs();
		$breadcrumbs->set_page_title(array('Info', 'Privacy Policy &amp; Terms of Use'));
		echo $breadcrumbs->get_breadcrumbs();
	?>

	<section class="wrapper">
		<article>
			<h2>Privacy Policy &amp; Terms of Use</h2>
			
			<h3>What information do we collect?</h3> 
			<p>We collect information from you when you register on our site or place an order.</p>
			<p>When ordering or registering on our site, as appropriate, you may be asked to enter your: name, e-mail address, mailing address or institution. You may, however, visit our site anonymously.</p>

			<h3>What do we use your information for?</h3> 
			<p>Any of the information we collect from you may be used in one of the following ways:</p>
			<ul>
				<li><strong>To improve customer service</strong><br />Your information helps us to more effectively respond to your customer service requests and support needs.</li>
				<li><strong>To process transactions</strong><br />Your information, whether public or private, will not be sold, exchanged, transferred, or given to any other company for any reason whatsoever, without your consent, other than for the express purpose of delivering the purchased product or service requested.</li>
				<li><strong>To send periodic emails</strong><br />The email address you provide for order processing, will only be used to send you information and updates pertaining to your order.</li>
			</ul>

			<h3>How do we protect your information?</h3> 
			<p>We implement a variety of security measures to maintain the safety of your personal information when you place an order or enter, submit, or access your personal information.</p>
			<p>Your orders are uniquely identified with a randomly generated 32-character hexadecimcal key, and your personal information are only viewable to administrative staff of the LORE1 group.</p>

			<h3>Do we use cookies?</h3> 
			<p>Yes (Cookies are small files that a site or its service provider transfers to your computers hard drive through your Web browser (if you allow) that enables the sites or service providers systems to recognize your browser and capture and remember certain information.</p>
			<p>We use cookies to help us remember and process the items in your shopping cart and compile aggregate data about site traffic and site interaction so that we can offer better site experiences and tools in the future.</p>

			<h3>Do we disclose any information to outside parties?</h3> 
			<p>We do not sell, trade, or otherwise transfer to outside parties your personally identifiable information. This does not include trusted third parties who assist us in operating our website, conducting our business, or servicing you, so long as those parties agree to keep this information confidential. We may also release your information when we believe release is appropriate to comply with the law, enforce our site policies, or protect ours or others rights, property, or safety. However, non-personally identifiable visitor information may be provided to other parties for marketing, advertising, or other uses.</p>

			<h3>Third party links</h3>
			<p>Occasionally, at our discretion, we may include or offer third party products or services on our website. These third party sites have separate and independent privacy policies. We therefore have no responsibility or liability for the content and activities of these linked sites. Nonetheless, we seek to protect the integrity of our site and welcome any feedback about these sites.</p>

			<h3>Your Consent</h3>
			<p>By using our site, you consent to our web site privacy policy.</p>
		</article>
	</section>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>
