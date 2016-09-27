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
require_once 'Baseline_controller.php';

/**
 *  Ajax is a CI controller class that extends Baseline_controller
 *
 *  The *Ajax* class contains API functionality for getting asynch
 *  data from the backend for behind the scenes info updates
 *
 * @category Page_Controller
 * @package  Pacifica-reporting
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 * @link    http://github.com/EMSL-MSC/Pacifica-reporting

 * @uses   Reporting_model
 * @uses   Group_info_model
 * @uses   Summary_model
 * @uses   EUS               EUS Database access library
 * @see    https://github.com/EMSL-MSC/pacifica-reporting
 * @access public
 */
class Ajax extends Baseline_controller
{
    /**
     * Contains the timestamp when this file was last modified
     */
    public $last_update_time;

    /**
     * [__construct description]
     *
     * @method __construct
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Reporting_model', 'rep');
        $this->load->model('Group_info_model', 'gm');
        $this->load->model('Summary_model', 'summary');
        $this->load->library('EUS', '', 'eus');
        // $this->load->helper(array('network','file_info','inflector','time','item','search_term','cookie'));
        $this->load->helper(array('network','search_term','inflector'));
        $this->accepted_object_types = array('instrument', 'user', 'proposal');
        $this->accepted_time_basis_types = array('submit_time', 'create_time', 'modified_time');
        $this->local_resources_folder = $this->config->item('local_resources_folder');
    }

    /**
     * [make_new_group description]
     *
     * @param [type] $object_type [description]
     *
     * @method make_new_group
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function make_new_group($object_type)
    {
        if ($this->input->post()) {
              $group_name = $this->input->post('group_name');
        }
        elseif ($this->input->is_ajax_request() || $this->input->raw_input_stream) {
            $post_info = json_decode($this->input->raw_input_stream, TRUE);
            // $post_info = $post_info[0];
            $group_name = array_key_exists('group_name', $post_info) ? $post_info['group_name'] : FALSE;
        }
            $group_info = $this->gm->make_new_group($object_type, $this->user_id, $group_name);
        if ($group_info && is_array($group_info)) {
            send_json_array($group_info);
        }
        else {
            $this->output->set_status_header(500, "Could not make a new group called '{$group_name}'");

            return;
        }
    }

    /**
     * [change_group_name description]
     *
     * @param [type] $group_id [description]
     *
     * @method change_group_name
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function change_group_name($group_id)
    {
        $new_group_name = FALSE;
        $group_info = $this->gm->get_group_info($group_id);
        if (!$group_info) {
            $this->output->set_status_header(404, "Group ID {$group_id} was not found");

            return;
        }
        if ($this->input->post()) {
            $new_group_name = $this->input->post('group_name');
        }
        elseif ($this->input->is_ajax_request() || file_get_contents('php://input')) {
            $http_raw_post_data = file_get_contents('php://input');
            $post_info = json_decode($http_raw_post_data, TRUE);
            if (array_key_exists('group_name', $post_info)) {
                $new_group_name = $post_info['group_name'];
            }
        }
        else {
            $this->output->set_status_header(400, 'No update information was sent');

            return;
        }
        if ($new_group_name) {
            //check for authorization
            if ($this->user_id !== $group_info['person_id']) {
                $this->output->set_status_header(401, 'You are not allowed to alter this group');

                return;
            }
            if ($new_group_name === $group_info['group_name']) {
                //no change to name
                $this->output->set_status_header(400, 'Group name is unchanged');

                return;
            }

            $new_group_info = $this->gm->change_group_name($group_id, $new_group_name);
            if ($new_group_info && is_array($new_group_info)) {
                send_json_array($new_group_info);
            }
            else {
                $this->output->set_status_header(500, 'A database error occurred during the update process');

                return;
            }
        }
        else {
            $this->output->set_status_header(400, 'Changed "group_name" attribute was not found');

            return;
        }
    }

    /**
     * [change_group_option description]
     *
     * @param boolean $group_id [description]
     *
     * @method change_group_option
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function change_group_option($group_id = FALSE)
    {
        if (!$group_id) {
            //send a nice error message about why you should include a group_id
        }
        $option_type = FALSE;
        $option_value = FALSE;
        $group_info = $this->gm->get_group_info($group_id);
        if (!$group_info) {
            $this->output->set_status_header(404, "Group ID {$group_id} was not found");

            return;
        }
        if ($this->input->post()) {
            $option_type = $this->input->post('option_type');
            $option_value = $this->input->post('option_value');
        }
        elseif ($this->input->is_ajax_request() || $this->input->raw_input_stream) {
            $http_raw_post_data = file_get_contents('php://input');
            $post_info = json_decode($http_raw_post_data, TRUE);
            // $post_info = $post_info[0];
            $option_type = array_key_exists('option_type', $post_info) ? $post_info['option_type'] : FALSE;
            $option_value = array_key_exists('option_value', $post_info) ? $post_info['option_value'] : FALSE;
        }
        if (!$option_type || !$option_value) {
            $missing_types = array();
            $message = "Group option update information was incomplete (missing '";
            //$message .= !$option_type ? " 'option_type' "
            if (!$option_type) {
                $missing_types[] = 'option_type';
            }
            if (!$option_value) {
                $missing_types[] = 'option_value';
            }
            $message .= implode("' and '", $missing_types);
            $message .= "' entries)";
            $this->output->set_status_header(400, $message);

            return;
        }

        $success = $this->gm->change_group_option($group_id, $option_type, $option_value);
        if ($success && is_array($success)) {
            send_json_array($success);
        }
        else {
            $message = "Could not set options for group ID {$group_id}";
            $this->output->set_status_header('500', $message);

            return;
        }

        return;
    }

    /**
     * [get_group_container description]
     *
     * @param [type]  $object_type [description]
     * @param [type]  $group_id    [description]
     * @param boolean $time_range  [description]
     * @param boolean $start_date  [description]
     * @param boolean $end_date    [description]
     * @param boolean $time_basis  [description]
     *
     * @method get_group_container
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_group_container($object_type, $group_id, $time_range = FALSE, $start_date = FALSE, $end_date = FALSE, $time_basis = FALSE)
    {
        $group_info = $this->gm->get_group_info($group_id);
        $options_list = $group_info['options_list'];
        $item_list = $group_info['item_list'];
        $time_range = !empty($time_range) ? $time_range : $options_list['time_range'];
        $time_basis = $options_list['time_basis'];
        if ((!empty($start_date) && !empty($end_date)) && (strtotime($start_date) && strtotime($end_date))) {
            $time_range = 'custom';
        }
        $object_type = singular($object_type);
        $accepted_object_types = array('instrument', 'proposal', 'user');

        $valid_date_range = $this->gm->earliest_latest_data_for_list($object_type, $group_info['item_list'], $time_basis);
        $my_times = $this->summary->fix_time_range($time_range, $start_date, $end_date, $valid_date_range);
        $latest_available_date = new DateTime($valid_date_range['latest']);
        $earliest_available_date = new DateTime($valid_date_range['earliest']);

        $valid_range = array(
            'earliest' => $earliest_available_date->format('Y-m-d H:i:s'),
            'latest' => $latest_available_date->format('Y-m-d H:i:s'),
            'earliest_available_object' => $earliest_available_date,
            'latest_available_object' => $latest_available_date,
        );
        $my_times = array_merge($my_times, $valid_range);

        $this->page_data['placeholder_info'][$group_id] = array(
            'group_id' => $group_id,
            'object_type' => $object_type,
            'options_list' => $options_list,
            'group_name' => $group_info['group_name'],
            'item_list' => $group_info['item_list'],
            'time_basis' => $time_basis,
            'time_range' => $time_range,
            'times' => $my_times,
        );
        if (!array_key_exists('my_groups', $this->page_data)) {
            $this->page_data['my_groups'] = array($group_id => $group_info);
        }
        else {
            $this->page_data['my_groups'][$group_id] = $group_info;
        }
        $this->page_data['my_object_type'] = $object_type;
        if (empty($item_list)) {
            $this->page_data['examples'] = add_objects_instructions($object_type);
        }
        else {
            $this->page_data['placeholder_info'][$group_id]['times'] = $this->summary->fix_time_range($time_range, $start_date, $end_date);
        }
        $this->load->view('object_types/group.html', $this->page_data);
    }

    /**
     * Allows the user to change information about a given group object
     *
     * @param string  $object_type type of object to update
     * @param boolean $group_id    ID from *reporting_object_groups* table
     *
     * @method update_object_preferences
     * @return none
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function update_object_preferences($object_type, $group_id)
    {
        if ($this->input->post()) {
            $object_list = $this->input->post();
        }
        elseif ($this->input->is_ajax_request() || file_get_contents('php://input')) {
            $http_raw_post_data = file_get_contents('php://input');
            $object_list = json_decode($http_raw_post_data, TRUE);
        }
        else {
            //return a 404 error
        }
        $filter = $object_list[0]['current_search_string'];
        $new_set = array();
        if ($this->gm->update_object_preferences($object_type, $object_list, $group_id)) {
            $this->get_object_group_lookup($object_type, $group_id, $filter);
        }
    }

    /**
     * API call to remove a group collection object from the prefs database
     *
     * @param boolean $group_id the integer ID from the *reporting_object_groups* table
     *
     * @method remove_group
     * @return string json status callback
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function remove_group($group_id = FALSE)
    {
        if (!$group_id) {
            $this->output->set_status_header(400, 'No Group ID specified');

            return;
        }
        $group_info = $this->gm->get_group_info($group_id);
        if (!$group_info) {
            $this->output->set_status_header(404, "Group ID {$group_id} was not found");

            return;
        }
        if ($this->user_id !== $group_info['person_id']) {
            $this->output->set_status_header(401, "User {$this->eus_person_id} is not the owner of Group ID {$group_id}");

            return;
        }
        $results = $this->gm->remove_group_object($group_id, TRUE);

        $this->output->set_status_header(200);

        return;
    }

    /**
     * [get_object_group_lookup description]
     *
     * @param string  $object_type [description]
     * @param integer $group_id    [description]
     * @param string  $filter      [description]
     *
     * @method get_object_group_lookup
     * @return [type] [description]
     * @uses   Group::get_selected_objects
     * @uses   EUS::get_object_list
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_object_group_lookup($object_type, $group_id, $filter = '')
    {
        $my_objects = $this->gm->get_selected_objects($this->user_id, $object_type, $group_id);
        if (!array_key_exists($object_type, $my_objects)) {
            $my_objects[$object_type] = array();
        }
        $filter = parse_search_term($filter);
        $results = $this->eus->get_object_list($object_type, $filter, $my_objects[$object_type]);
        $this->page_data['results'] = $results;
        $this->page_data['object_type'] = $object_type;
        $this->page_data['filter_text'] = $filter;
        $this->page_data['my_objects'] = $my_objects[$object_type];
        $this->page_data['js'] = '$(function(){ setup_search_checkboxes(); })';
        if (!empty($results)) {
            $this->load->view("object_types/search_results/{$object_type}_results.html", $this->page_data);
        }
        else {
            $filter_string = implode("' '", $filter);
            echo "<div class='info_message' style='margin-bottom:1.5em;'>No Results Returned for '{$filter_string}'</div>";
        }
    }

}
