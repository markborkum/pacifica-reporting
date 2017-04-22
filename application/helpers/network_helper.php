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
 *  Takes a given array object and formats it as
 *  standard JSON with appropriate status headers
 *  and X-JSON messages
 *
 *  @param array   $response            the array to be transmitted
 *  @param string  $statusMessage       optional status message
 *  @param boolean $operationSuccessful Was the calling
 *                                      operation successful?
 *
 *  @return void (sends directly to browser)
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function transmit_array_with_json_header($response, $statusMessage = '', $operationSuccessful = TRUE)
{
    header("Content-type: text/json");
    $headerArray = array();
    $headerArray['status'] = $operationSuccessful ? "ok" : "fail";
    $headerArray['message'] = !empty($statusMessage) ? $statusMessage : "";
    header("X-JSON: (".json_encode($headerArray).")");

    $response = !is_array($response) ? array('results' => $response) : $response;

    if(is_array($response) && sizeof($response) > 0) {
        print(json_encode($response));
    }else{
        print("0");
    }
}

/**
 *  Formats an array object into the proper format
 *  to be parsed by the Select2 Jquery library for
 *  generating dropdown menu objects
 *
 *  @param array $response array to be formatted
 *
 *  @return void (sends directly to browser)
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function format_array_for_select2($response)
{
    header("Content-type: text/json");

    $results = array();

    foreach($response['items'] as $id => $text){
        $results[] = array('id' => $id, 'text' => $text);
    }

    $ret_object = array(
    'total_count' => sizeof($results),
    'incomplete_results' => FALSE,
    'items' => $results
    );

    print(json_encode($ret_object));

}

/**
 *  Takes a string that is too long and chops
 *  some content out of the middle to provide
 *  better context
 *
 *  @param string  $text     string to be shortened
 *  @param integer $maxchars maximum string length allowed
 *
 *  @return string shortened string
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function shorten_string($text, $maxchars)
{
    if(strlen($text) > $maxchars) {
        $text = substr_replace($text, '...', $maxchars/2, strlen($text)-$maxchars);
    }
    return $text;
}
