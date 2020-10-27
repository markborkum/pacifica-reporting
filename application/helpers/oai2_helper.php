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

// $xml_entity_refs = array(
//     '<' => '&lt;',
//     '>' => '&gt;',
//     '&' => '&amp;',
//     '\'' => '&apos;',
//     '"' => '&quot;',
// );
//
// private function safe_escape($str, $replacements = $xml_entity_refs)
// {
//     if (empty($str)) {
//         return $str;
//     }
//
//     foreach ($replacements as $key => $value) {
//         $str = str_replace($key, $value, $str);
//     }
//
//     return $str;
// }

// private function safe_quote($str, $delimeter = '"')
// {
//     if (empty($str)) {
//         return $str;
//     }
//
//     return $delimeter . str_replace($delimeter, '\\' . $delimeter, $str) . $delimeter;
// }

private function safe_trim($str)
{
    if (empty($str)) {
        return $str;
    }

    return trim($str);
}

private function test_all_not_empty($args = array(), $keys = array())
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $args)) {
            return False;
        }

        $value = $args[$key];

        if (empty($value)) {
            return False;
        }
    }

    return True;
}

private function test_any_not_empty($args = array(), $keys = array())
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $args)) {
            $value = $args;

            if (!empty($value)) {
                return True;
            }
        }
    }

    return False;
}
