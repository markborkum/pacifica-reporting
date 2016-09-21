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
 *  Recursively construct the proper HTML
 *  for representing a folder full of items
 *
 *  @param array $dirs       array of directory objects to process
 *  @param array $path_array path components in array form
 *  @param array $item_info  metadata about each item
 *
 *  @return void
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function build_folder_structure(&$dirs, $path_array, $item_info)
{
    if (count($path_array) > 1) {
        if (!isset($dirs['folders'][$path_array[0]])) {
            $dirs['folders'][$path_array[0]] = array();
        }

        build_folder_structure($dirs['folders'][$path_array[0]], array_splice($path_array, 1), $item_info);
    } else {
        $size_string = format_bytes($item_info['size']);
        $item_id = $item_info['item_id'];
        $url = base_url()."myemsl/itemauth/{$item_id}";
        $item_info['url'] = $url;
        $item_info_json = json_encode($item_info);
        $dirs['files'][$item_id] = "<a class='item_link' id='item_{$item_id}' href='#'>{$path_array[0]}</a> <span class='fineprint'>[size: {$size_string}]</span><span class='item_data_json' id='item_id_{$item_id}' style='display:none;'>{$item_info_json}</span>";
    }
}

/**
 *  Construct an array of folders that can be translated to
 *  a JSON object
 *
 *  @param array  $folder_obj  container for folders
 *  @param string $folder_name display name for the folder object
 *
 *  @return array
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function format_folder_object_json($folder_obj, $folder_name)
{
    $output = array();

    if (array_key_exists('folders', $folder_obj)) {
        foreach ($folder_obj['folders'] as $folder_entry => $folder_tree) {
            $folder_output = array('title' => $folder_entry, 'folder' => TRUE);
            $children = format_folder_object_json($folder_tree, $folder_entry);
            if (!empty($children)) {
                foreach ($children as $child) {
                    $folder_output['children'][] = $child;
                }
            }
            $output[] = $folder_output;
        }
    }
    if (array_key_exists('files', $folder_obj)) {
        foreach ($folder_obj['files'] as $item_id => $file_entry) {
            $output[] = array('title' => $file_entry, 'key' => "ft_item_{$item_id}");
        }
    }

    return $output;
}

/**
 *  Similar to format_folder_object_json, but outputs HTML
 *
 *  @param array  $folder_obj       container for folders
 *  @param string $output_structure complete HTML structure, passed by ref
 *
 *  @return string
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function format_folder_object_html($folder_obj, &$output_structure)
{
    foreach (array_keys($folder_obj) as $folder_entry) {
        $output_structure .= "<li class='folder'>{$folder_entry}<ul>";
        if (array_key_exists('folders', $folder_obj[$folder_entry])) {
            $f_obj = $folder_obj[$folder_entry]['folders'];
            format_folder_object_html($f_obj, $output_structure);
        }
        if (array_key_exists('files', $folder_obj[$folder_entry])) {
            $file_obj = $folder_obj[$folder_entry]['files'];
            format_file_object_html($file_obj, $output_structure);
        }
        $output_structure .= '</ul></li>';
    }
}

/**
 *  Constructs the list item for each individual object
 *
 *  @param array $file_obj         the file item object to format
 *  @param array $output_structure complete HTML structure, passed by ref
 *
 *  @return void
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function format_file_object_html($file_obj, &$output_structure)
{
    foreach ($file_obj as $file_entry) {
        $output_structure .= "<li>{$file_entry}</li>";
    }
}

/**
 *  Converts byte-wise file sizes to human-readable strings
 *
 *  @param integer $bytes file size in bytes to convert
 *
 *  @return string
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function format_bytes($bytes)
{
    if ($bytes < 1024) {
        return $bytes.' B';
    } elseif ($bytes < 1048576) {
        return round($bytes / 1024, 0).' KB';
    } elseif ($bytes < 1073741824) {
        return round($bytes / 1048576, 1).' MB';
    } elseif ($bytes < 1099511627776) {
        return round($bytes / 1073741824, 2).' GB';
    } else {
        return round($bytes / 1099511627776, 2).' TB';
    }
}
