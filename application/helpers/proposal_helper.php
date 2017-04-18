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
 if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * [get_proposals_by_name description]
 *
 * @param string  $name_fragment search terms for the search
 * @param boolean $active        is the proposal active or not
 *
 * @return void
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_proposals_by_name($name_fragment, $active)
{
    $CI =& get_instance();
    $CI->load->library('PHPRequests');
    $md_url = $CI->metadata_url_base;
}
