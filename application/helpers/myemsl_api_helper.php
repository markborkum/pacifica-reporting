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
 *  This file contains a number of common functions for retrieving
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

if(!defined('BASEPATH')) { exit('No direct script access allowed');
}

/**
 *  Directly retrieves user info from the MyEMSL EUS
 *  database clone
 *
 * @param integer $eus_id user id of the person in question
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_details($eus_id)
{
    return get_details('user', $eus_id);
}

/**
 *  Directly retrieves instrument info from md server
 *
 * @param integer $instrument_id id of the instrument in question
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_instrument_details($instrument_id)
{
    return get_details('instrument', $instrument_id);
}

/**
 *  Directly retrieves proposal info from md server
 *
 * @param integer $proposal_id proposal id of the item in question
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_proposal_details($proposal_id)
{
    return get_details('proposal', $proposal_id);
}

/**
 *  Worker function for talking to md server
 *
 * @param string $object_type type of object to query
 * @param string $object_id   id of object to query
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_details($object_type, $object_id)
{
    $object_map = array(
        'instrument' => array('url' => 'instrumentinfo/by_instrument_id'),
        'proposal' => array('url' => 'proposalinfo/by_proposal_id'),
        'user' => array('url' => 'userinfo/by_id')
    );
    $url = $object_map[$object_type]['url'];
    $CI =& get_instance();
    $CI->load->library('PHPRequests');
    // $md_url = $CI->config->item('metadata_url');
    $md_url = $CI->metadata_url_base;
    $query_url = "{$md_url}/{$url}/{$object_id}";
    $query = Requests::get($query_url, array('Accept' => 'application/json'));
    $results_body = $query->body;

    return json_decode($results_body, TRUE);

}

/**
 * Set up hints that show what types of
 * things make for acceptable search criteria
 *
 * @param string $object_type type of object to be hinted
 *                            (instrument/proposal/user)
 *
 * @return array simple array with search criteria
 *               descriptions for display
 */
function add_objects_instructions($object_type)
{
    $object_examples = array(
                        'instrument' => array(),
                        'proposal'   => array(),
                        'user'       => array(),
                       );
    $object_examples['instrument'] = array(
                                      "'nmr' returns a list of all instruments with 'nmr' somewhere in the name or description",
                                      "'34075' returns the instrument having an ID of '34075' in the EUS database",
                                      "'nmr nittany' returns anything with 'nmr' and 'nittany' somewhere in the name or description",
                                     );
    $object_examples['proposal']   = array(
                                      "'phos' returns a list of all proposals having the term 'phos' somewhere in the title or description",
                                      "'49164' returns a proposal having an ID of '49164' in the EUS database",
                                     );
    $object_examples['user']       = array(
                                      "'jones' returns a list of EUS users having 'jones' somewhere in their first name, last name or email",
                                      "'36846' returns a user having the ID of '36846' in the EUS database",
                                     );

    return $object_examples[$object_type];

}//end add_objects_instructions()
