<?php
/**
 * Pacifica
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-Reporting module provides an interface for
 *  concerned and interested parties to view the current
 *  contribution status of any and all instruments in the
 *  system. The reporting interface can be customized and
 *  filtered streamline the report to fit any level of user,
 *  from managers through instrument operators.
 *
 *  This file contains a number of common functions related to
 *  file info and handling.
 *
 * PHP version 5.5
 *
 * @package Pacifica-reporting
 *
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 *
 * @link http://github.com/EMSL-MSC/Pacifica-reporting
 */
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>MyEMSL - Error Report</title>
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
        <meta  name="description" content="" />
        <meta name="keywords" content="" />

        <link rel="stylesheet" type="text/css" href="/resources/stylesheets/local.css">
        <link rel="stylesheet" href="/resources/stylesheets/emsl_chrome.css">
        <link rel="stylesheet" type="text/css" href="/project_resources/stylesheets/reporting.css">
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
            <div id="header_container" class="header_container" style="position:relative;">
                <h1 class="underline">Error Reporting</h1>
            </div>
        </header>
        <div id="container">
        <div id="main" class="main">
                <h3><?php echo $heading; ?></h3>
                <?php echo $message; ?>
        </div>
      </div>
        <footer class="short">
            <section id="contact_info" class="contact_info">
                <a class="email" href="mailto:emsl@pnnl.gov">EMSL, The Environmental Molecular Sciences Laboratory</a>
            </section>
        </footer>
    </div>
</body>
</html>
