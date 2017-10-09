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
?>
<?php echo doctype('html5'); ?>
<?php
    $page_header = isset($page_header) ? $page_header : "Untitled Page";
    $title = isset($title) ? $title : $page_header;
    $index_page = $this->config->item('index_page');
?>
<html>
    <head>
        <title><?= ucwords($site_identifier) ?> Reporting - <?php echo $title ?></title>
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
        <meta  name="description" content="" />
        <meta name="keywords" content="" />
        <?php $this->load->view("{$this->template_version}_template/globals"); ?>
        <?= $script_uris ?>
        <?= $css_uris ?>

        <script type="text/javascript">
            var base_url = "<?php echo rtrim(base_url().$index_page, '/').'/' ?>";
        </script>
    </head>
    <body>
        <div class="page_content">
            <header class="secondary">
                <div class="page_header">
                    <div class="graphic_logo">
                        <div class="logo_container" >
                            <div class="logo_image">&nbsp;</div>
                        </div>
                        <div class="site_slogan"><?= $site_slogan ?></div>
                    </div>
                    <div class="text_logo">
                        <?= $site_identifier ?><span class="site_slogan"><?= $this->config->item('site_slogan') ?></span>
                    </div>
                </div>
                <div id="header_container" class="header_container" style="position:relative;">
                    <h1 class="underline"><?php echo $page_header ?></h1>
                    <div id="login_id_container" class="login_id_container">
                        <em><?php echo $this->nav_info['current_page_info']['logged_in_user'] ?></em>
                    </div>
                </div>
            </header>
