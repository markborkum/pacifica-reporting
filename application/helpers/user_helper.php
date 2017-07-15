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

if(!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 *  Properly formats the user returned in the ['REMOTE_USER']
 *  variable from Apache
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user()
{
    $user = '(unknown)';
    $CI =& get_instance();
    $CI->load->library('PHPRequests');
    $md_url = $CI->metadata_url_base;
    if(isset($_SERVER["REMOTE_USER"])) {
        $user = str_replace('@PNL.GOV', '', $_SERVER["REMOTE_USER"]);
    } else if (isset($_SERVER["PHP_AUTH_USER"])) {
        $user = str_replace('@PNL.GOV', '', $_SERVER["PHP_AUTH_USER"]);
    }
    $user = strtolower($user);
    $url_args_array = array(
       'network_id' => $user
    );
    $query_url = "{$md_url}/users?";
    $query_url .= http_build_query($url_args_array, '', '&');
    $query = Requests::get($query_url, array('Accept' => 'application/json'));
    $results_body = $query->body;
    $results_json = json_decode($results_body, TRUE);
    if($query->status_code == 200) {
        return strtolower($results_json[0]['_id']);
    }else{
        return FALSE;
    }
}
