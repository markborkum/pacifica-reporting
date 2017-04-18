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

if(!defined('BASEPATH')) { exit('No direct script access allowed');
}

/**
 *  Directly retrieves user info from the MyEMSL EUS
 *  database clone
 *
 *  @param integer $eus_id user id of the person in question
 *
 *  @return array
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_details_myemsl($eus_id)
{
    $CI =& get_instance();
    $results = FALSE;
    $users_table = "users";
    $DB_eus = $CI->load->database('eus_for_myemsl', TRUE);

    $select_array = array('person_id', 'first_name','last_name', 'email_address', 'network_id', 'emsl_employee');

    $query = $DB_eus->select($select_array)->get_where($users_table, array('person_id' => $eus_id), 1);

    if($query && $query->num_rows() > 0) {
        $results = $query->row_array();
    }
    return $results;
}

/**
 *  Read and parse the '*general.ini*' file to retrieve things
 *  like the database connection strings, etc.
 *
 *  @param string $file_specifier the name of the file to be read
 *                                from the default folder location
 *
 *  @return array an array of ini file items
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function read_myemsl_config_file($file_specifier = 'general')
{
    $CI =& get_instance();
    $ini_path = $CI->config->item('application_config_file_path');
    $ini_items = parse_ini_file("{$ini_path}{$file_specifier}.ini", TRUE);
    return $ini_items;
}

/**
 *  Construct an appropriate token for retrieving the items
 *  from a given cart object. This was needed to overcome the
 *  'single call for each cart item' limitation
 *
 *  @param array   $item_list     the item identifiers for the cart items to be processed cart items to be processed
 *                                cart items to be processed
 *  @param integer $eus_person_id cart owner user id
 *
 *  @return string Base64 encoded token to use for the submission
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function generate_cart_token($item_list,$eus_person_id)
{
    $uuid = "huYNwptYEeGzDAAmucepzw";
    $duration = 3600;

    //grab private key file
    $fp = fopen('/etc/myemsl/keys/item/local.key', 'r');
    $priv_key = fread($fp, 8192);
    fclose($fp);
    $pkey_id = openssl_get_privatekey($priv_key);

    $s_time = new DateTime();
    $time = $s_time->format(DATE_ATOM);
    // $time = '2015-05-08T16:07:06-07:00';

    $token_object = array(
    'd' => $duration, 'i' => $item_list, 'p' => intval($eus_person_id),
    's' => $time, 'u' => $uuid
    );

    $token_json = json_encode($token_object);

    $trimmed_token = trim($token_json, '{}');

    openssl_sign($trimmed_token, $signature, $pkey_id, 'sha256');
    openssl_free_key($pkey_id);

    $cart_token = strlen($trimmed_token).$trimmed_token.$signature;

    $cart_token_b64 = base64_encode($cart_token);

    return $cart_token_b64;

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
