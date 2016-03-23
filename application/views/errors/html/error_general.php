<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>MyEMSL - Error Report</title>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<meta  name="description" content="" />
		<meta name="keywords" content="" />

		<link rel="stylesheet" type="text/css" href="/resources/stylesheets/local.css">
		<link rel="stylesheet" href="/resources/stylesheets/emsl_chrome.css">
		<link rel="stylesheet" type="text/css" href="https://dev1.my.emsl.pnl.gov/myemsl/reporting/application/resources/stylesheets/reporting.css">
</head>


<body>
	<div class="page_content">
		<header class="secondary">
			<div class="page_header">
				<div class="logo_container">
					<div class="logo_image">&nbsp;</div>
				</div>
				<div class="site_slogan">Environmental Molecular Sciences Laboratory</div>
			</div>
			<div id="header_container" style="position:relative;">
				<h1 class="underline">Error Reporting</h1>
			</div>
		</header>
		<div id="container">
	    <div id="main">
				<h3><?php echo $heading; ?></h3>
				<?php echo $message; ?>
	    </div>
	  </div>
		<footer class="short">
			<section id="contact_info">
				<a class="email" href="mailto:emsl@pnnl.gov">EMSL, The Environmental Molecular Sciences Laboratory</a>
			</section>
		</footer>
	</div>
</body>
</html>
