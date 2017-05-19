<?php
/**
 * Pacifica
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-upload-status module provides an interface to
 *  the ingester status reporting backend, allowing users to view
 *  the current state of any uploads they may have performed, as
 *  well as enabling the download and retrieval of that data.
 *
 *  This file contains a number of common functions related to
 *  file info and handling.
 *
 * PHP version 5.5
 *
 * @package Pacifica-upload-status
 *
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 *
 * @link http://github.com/EMSL-MSC/Pacifica-reporting
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Load JS files as specified
 *
 * @param array $common_collection        collection of JS files shared over the entire site
 * @param array $page_specific_collection JS files just for this page family
 *
 * @return void
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function load_scripts($common_collection, $page_specific_collection = FALSE)
{
    $defaults = $common_collection;
    if($page_specific_collection) {
        $defaults = array_merge($defaults, $page_specific_collection);
    }
    $output_array = array();
    foreach($defaults as $scriptfile){
        $output_array[] = "<script type=\"text/javascript\" src=\"{$scriptfile}\"></script>";
    }
    return implode("\n", $output_array)."\n\n";
}


 /**
 * Load CSS files as specified, folding in theming
 *
 * @param array $common_collection        collection of CSS files shared over the entire site
 * @param array $page_specific_collection CSS files just for this page family
 *
 * @return void
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function load_stylesheets($common_collection, $page_specific_collection = FALSE)
{
    $CI =& get_instance();
    $my_theme = $CI->config->item('theme_name');
    $defaults = $common_collection;
    if($page_specific_collection) {
        $defaults = array_merge($defaults, $page_specific_collection);
    }
    $theme_dir_array = array("resources", "stylesheets", "themes", $my_theme);
    $theme_url_path_array = array("project_resources", "stylesheets", "themes", $my_theme);
    $theme_path = APPPATH . implode(DIRECTORY_SEPARATOR, $theme_dir_array) . DIRECTORY_SEPARATOR;
    if(is_dir($theme_path)) {
        foreach(glob($theme_path . "*.css") as $theme_file){
            $defaults[] = "/". implode("/", $theme_url_path_array) . "/" . basename($theme_file);
        }
    }

    $output_array = array();
    foreach($defaults as $stylesheet){
        $output_array[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$stylesheet}\" />";
    }
    return implode("\n", $output_array)."\n\n";
}
