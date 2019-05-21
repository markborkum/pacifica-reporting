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
if (!defined('BASEPATH')) {
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
    $CI =& get_instance();
    $CI->load->library('PHPRequests');
    $md_url = $CI->metadata_url_base;
    $remote_user = array_key_exists("REMOTE_USER", $_SERVER) ? $_SERVER["REMOTE_USER"] : false;
    $remote_user = !$remote_user && array_key_exists("PHP_AUTH_USER", $_SERVER) ? $_SERVER["PHP_AUTH_USER"] : $remote_user;
    $results = false;
    $cookie_results = false;
    if ($CI->config->item('enable_cookie_redirect')) {
        $cookie_results = get_user_from_cookie();
        if ($cookie_results && is_array($cookie_results) && array_key_exists('eus_id', $cookie_results)) {
            $cookie_results['id'] = $cookie_results['eus_id'];
            $url_args_array = [
                "_id" => $cookie_results['eus_id']
            ];
        } else {
            return $results;
        }
    } elseif ($remote_user) {
        //check for email address as username
        $selector = filter_var($remote_user, FILTER_VALIDATE_EMAIL) ? 'email_address' : 'network_id';
        $url_args_array = [
            $selector => strtolower($remote_user)
        ];
    } else {
        return $results;
    }
    if (empty($url_args_array)) {
        return $results;
    }

    $query_url = "{$md_url}/users?";
    $query_url .= http_build_query($url_args_array, '', '&');
    $query = Requests::get($query_url, array('Accept' => 'application/json'));
    $results_body = $query->body;
    $results_json = json_decode($results_body, true);
    if ($cookie_results) {
        array_merge($results_json, $cookie_results);
    }
    if ($query->status_code == 200 && !empty($results_json)) {
        $results = strtolower($results_json[0]['_id']);
    }
    return $results;
}

/**
 *  Properly formats the user returned in the ['REMOTE_USER']
 *  variable from Apache
 *
 * @param integer $user_id The user_id to format
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_details_server_vars($user_id)
{
    $user_info = array(
    'user_id' => strtolower($_SERVER['REMOTE_USER']),
    'first_name' => $_SERVER['LDAP_GIVENNAME'],
    'middle_initial' => $_SERVER['LDAP_INITIALS'],
    'last_name' => $_SERVER['LDAP_SN'],
    'email' => strtolower($_SERVER['LDAP_MAIL'])
    );
    return $user_info;
}

/**
 * Utility function to get user from cookie
 *
 * @return string EUS ID of the user from EUS cookie
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_from_cookie()
{
    $CI =& get_instance();
    $cookie_name = $CI->config->item('cookie_name');
    $eus_id_string = $CI->input->cookie($cookie_name) ? eus_decrypt($CI->input->cookie($cookie_name)) : false;
    $eus_user_info = $eus_id_string ? json_decode($eus_id_string, true) : false;
    return $eus_user_info;
}

/**
 * Entry function to encrypt string text
 *
 * @param string $src source text to encipher
 *
 * @return string encrypted text
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function eus_encrypt($src)
{
    $key = _getkey();
    return base64_encode(openssl_encrypt($src, "aes-128-ecb", $key, OPENSSL_RAW_DATA));
}

/**
 * Entry function to decrypt string text
 *
 * @param string $src encrypted text to decipher
 *
 * @return string decrypted text
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function eus_decrypt($src)
{
    $key = _getkey();
    $src = str_replace(" ", "+", $src);
    return openssl_decrypt(base64_decode($src), "aes-128-ecb", $key, OPENSSL_RAW_DATA);
}

/**
 * Get the shared key string from configuration
 *
 * @return string key value retrieved from configuration
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function _getkey()
{
    $CI =& get_instance();
    return $CI->config->item('cookie_encryption_key');
}
