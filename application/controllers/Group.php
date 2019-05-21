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

defined('BASEPATH') or exit('No direct script access allowed');
require_once 'Baseline_api_controller.php';

/**
 *  Group is a CI controller class that extends Baseline_controller
 *
 *  The *Group* class contains user-interaction logic for a set of CI pages.
 *  It interfaces with several different models to retrieve information about
 *  groups of instruments/projects/users so that they can be displayed on
 *  a reporting page with pie charts and line graphs that represent the amount
 *  and distribution of data upload to Pacifica by its users.
 *
 * @category Page_Controller
 * @package  Pacifica-reporting
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 * @link    http://github.com/EMSL-MSC/Pacifica-reporting

 * @uses   Group_info_model
 * @uses   Summary_model
 * @see    https://github.com/EMSL-MSC/pacifica-reporting
 * @access public
 */
class Group extends Baseline_api_controller
{
    /**
     * The timestamp when this file was last modified
     *
     * @var $last_update_time
     */
    public $last_update_time;


    /**
     *  [__construct description]
     *
     * @method __construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Group_info_model', 'gm');
        $this->load->model('Summary_api_model', 'summary');
        $this->load->helper(
            array(
             'network',
             'file_info',
             'inflector',
             'time',
             'item',
             'search_term',
             'cookie',
             'project',
             'myemsl_api',
             'theme'
            )
        );
        $this->last_update_time = get_last_update(APPPATH);
        $this->accepted_object_types = array(
                                        'instrument',
                                        'user',
                                        'project'
                                       );
        $this->accepted_time_basis_types = array(
                                            'submit_time',
                                            'create_time',
                                            'modified_time',
                                           );
        $this->local_resources_folder = $this->config->item('local_resources_folder');
        $this->debug = $this->config->item('debug_enabled');
        $this->page_data['site_identifier'] = $this->config->item('site_identifier');
        $this->page_data['site_slogan'] = $this->config->item('site_slogan');
        $this->status_site_base_url = $this->config->item('status_server_base_url');
        $this->site_identifier = $this->config->item('site_identifier');
        $this->site_slogan = $this->config->item('site_slogan');
    }

    /**
     *  Grabs the base-level call to this controller and redirects
     *  it to Group::view
     *
     * @method index
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     *
     * @return none
     */
    public function index()
    {
        redirect('group/view');
    }

    /**
     *  Constructs the main report viewing page
     *
     * @param string $object_type classification of the object of interest [project/instrument/user]
     * @param string $time_range  parsable string to specify how far back to look from the current date [e.g. 3-months, 1-week, custom, etc]
     * @param string $start_date  beginning date for data collection in YYYY-MM-DD format
     * @param string $end_date    end date for data collection in YYYY-MM-DD format
     * @param string $time_basis  classification of date type to use [created/modified/submission]
     *
     * @method view
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     *
     * @return none  sends output to *reporting_view.html*
     */
    public function view(
        $object_type = false,
        $time_range = false,
        $start_date = false,
        $end_date = false,
        $time_basis = false
    ) {

        $this->benchmark->mark('controller_view_start');
        $object_type           = singular($object_type);
        $time_basis            = ! empty($time_basis) ? $time_basis : 'modification_time';
        $accepted_object_types = array(
                                  'instrument',
                                  'project',
                                  'user',
                                 );
        if (!in_array($object_type, $accepted_object_types)) {
            redirect('group/view/instrument');
        }

        $this->page_data['page_header'] = 'Aggregated Uploads by '.ucwords($object_type).' Grouping';
        $this->page_data['my_object_type'] = $object_type;
        $this->page_data['css_uris']
            = load_stylesheets(
                array(
                '/resources/scripts/select2/select2.css',
                '/resources/scripts/bootstrap/css/bootstrap.css',
                '/resources/scripts/bootstrap-daterangepicker/daterangepicker.css',
                '/project_resources/stylesheets/reporting.css',
                '/project_resources/stylesheets/combined.css'
                )
            );
        $this->page_data['script_uris']
            = load_scripts(
                array(
                '/resources/scripts/spinner/spin.min.js',
                '/resources/scripts/spinner/jquery.spin.js',
                '/resources/scripts/moment.min.js',
                '/resources/scripts/select2/select2.min.js',
                '/resources/scripts/bootstrap-daterangepicker/daterangepicker.js',
                '/resources/scripts/jquery-typewatch/jquery.typewatch.js',
                '/resources/scripts/highcharts/js/highcharts.js',
                '/project_resources/scripts/reporting.js',
                )
            );
        $my_groups = $this->gm->get_selected_groups($this->user_id, $object_type);

        if (empty($my_groups)) {
            $this->page_data['content_view'] = 'object_types/select_some_groups_insert.html';
        } else {
            $this->page_data['my_groups'] = '';
            foreach ($my_groups as $group_id => $group_info) {
                $my_start_date = false;
                $my_end_date = false;
                $options_list = $group_info['options_list'];
                $my_start_date = strtotime($start_date) ? $start_date : $options_list['start_time'];
                $my_start_date = $my_start_date !== 0 ? $my_start_date : false;
                $my_end_date = strtotime($end_date) ? $end_date : $options_list['end_time'];
                $my_end_date = $my_end_date !== 0 ? $my_end_date : false;
                $time_basis = $options_list['time_basis'];
                $time_range = $time_range ? $time_range : $options_list['time_range'];

                if ($time_range && $time_range !== $options_list['time_range'] && $time_range !== 'custom') {
                    $this->gm->change_group_option($group_id, 'time_range', $time_range);
                }

                $update_start = !$my_start_date ? true : false;
                $update_end = !$my_end_date ? true : false;
                $valid_date_range = $this->gm->earliest_latest_data_for_list(
                    $object_type,
                    $group_info['item_list'],
                    $time_basis
                );
                $my_times = fix_time_range(
                    $time_range,
                    $my_start_date,
                    $my_end_date,
                    $valid_date_range
                );

                if ($update_start) {
                    $this->gm->change_group_option(
                        $group_id,
                        'start_time',
                        $my_times['start_time_object']->format('Y-m-d')
                    );
                }

                if ($update_end) {
                    $this->gm->change_group_option(
                        $group_id,
                        'end_time',
                        $my_times['end_time_object']->format('Y-m-d')
                    );
                }

                $latest_available_date = new DateTime($valid_date_range['latest']);
                $earliest_available_date = new DateTime($valid_date_range['earliest']);
                // var_dump($my_times);
                $valid_range = array(
                    'earliest' => $earliest_available_date->format('Y-m-d H:i:s'),
                    'latest' => $latest_available_date->format('Y-m-d H:i:s'),
                    'earliest_available_object' => $earliest_available_date,
                    'latest_available_object' => $latest_available_date,
                );
                if ($my_times['start_time_object']->getTimestamp() < $valid_range['earliest_available_object']->getTimestamp()) {
                    $my_times['start_time_object'] = clone $valid_range['earliest_available_object'];
                }

                if ($my_times['end_time_object']->getTimestamp() > $valid_range['latest_available_object']->getTimestamp()) {
                    $my_times['end_time_object'] = clone $valid_range['latest_available_object'];
                }

                $my_times = array_merge($my_times, $valid_range);
                if (empty($group_info['item_list'])) {
                    $this->page_data['examples'] = add_objects_instructions($object_type);
                }

                $this->page_data['placeholder_info'][$group_id] = array(
                                                                   'group_id'     => $group_id,
                                                                   'object_type'  => $object_type,
                                                                   'options_list' => $options_list,
                                                                   'group_name'   => $group_info['group_name'],
                                                                   'item_list'    => $group_info['item_list'],
                                                                   'time_basis'   => $time_basis,
                                                                   'time_range'   => $time_range,
                                                                   'times'        => $my_times,
                                                                  );
            }//end foreach

            $time_range = str_replace(array('-', '_', '+'), ' ', $time_range);
            $this->page_data['my_groups']    = $my_groups;
            $this->page_data['content_view'] = 'object_types/group.html';
        }//end if
        $this->page_data['js'] = "var object_type = '{$object_type}'; var time_range = '{$time_range}';";
        $this->load->view('reporting_view.html', $this->page_data);
        $this->benchmark->mark('controller_view_end');
    }

    /**
     *  Returns an HTML block containing a table with details for each transaction
     *  represented in the current reporting view for the group
     *
     * @method get_transaction_list_details
     *
     * @return none pushes HTML to *object_types/transaction_details_insert.html*
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_transaction_list_details()
    {
        if ($this->input->post()) {
            $transaction_list = $this->input->post();
        } else if ($this->input->is_ajax_request() || file_get_contents('php://input')) {
            $http_raw_post_data = file_get_contents('php://input');
            $transaction_list   = json_decode($http_raw_post_data, true);
        }
        if ($transaction_list) {
            $results = $this->summary->detailed_transaction_list($transaction_list);
            $this->page_data['transaction_info'] = $results;
            $this->page_data['status_site_base_url'] = $this->status_site_base_url;

            $this->load->view('object_types/transaction_details_insert.html', $this->page_data);
        } else {
            echo "";
        }
    }

    /**
     *  [get_reporting_info_list description]
     *
     * @param string  $object_type   [description]
     * @param integer $group_id      [description]
     * @param boolean $time_basis    [description]
     * @param boolean $time_range    [description]
     * @param boolean $start_date    [description]
     * @param boolean $end_date      [description]
     * @param boolean $with_timeline [description]
     *
     * @return none   pushes to viewfile
     *
     * @method get_reporting_info_list
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_reporting_info_list(
        $object_type,
        $group_id,
        $time_basis = false,
        $time_range = false,
        $start_date = false,
        $end_date = false,
        $with_timeline = true
    ) {
        $this->_get_reporting_info_list_base(
            $object_type,
            $group_id,
            $time_basis,
            $time_range,
            $start_date,
            $end_date,
            true,
            false
        );
    }

    /**
     * [get_reporting_info_list_no_timeline description]
     *
     * @param string  $object_type [description]
     * @param integer $group_id    [description]
     * @param boolean $time_basis  [description]
     * @param boolean $time_range  [description]
     * @param boolean $start_date  [description]
     * @param boolean $end_date    [description]

     * @method get_reporting_info_list_no_timeline
     * @return none  pushes to viewfile
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_reporting_info_list_no_timeline(
        $object_type,
        $group_id,
        $time_basis = false,
        $time_range = false,
        $start_date = false,
        $end_date = false
    ) {
        $this->_get_reporting_info_list_base(
            $object_type,
            $group_id,
            $time_basis,
            $time_range,
            $start_date,
            $end_date,
            false,
            false
        );
    }//end get_reporting_info_list_no_timeline()

    /**
     * [_get_reporting_info_list_base description]
     *
     * @param [type]  $object_type   [description]
     * @param [type]  $group_id      [description]
     * @param [type]  $time_basis    [description]
     * @param [type]  $time_range    [description]
     * @param boolean $start_date    [description]
     * @param boolean $end_date      [description]
     * @param boolean $with_timeline [description]
     * @param boolean $full_object   [description]

     * @return [type] [description]
     * @method _get_reporting_info_list_base
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _get_reporting_info_list_base(
        $object_type,
        $group_id,
        $time_basis,
        $time_range,
        $start_date = false,
        $end_date = false,
        $with_timeline = true,
        $full_object = false
    ) {
        $this->benchmark->mark('get_group_info_start');
        $group_info = $this->gm->get_group_info($group_id);
        $this->benchmark->mark('get_group_info_end');

        if (!empty($group_info) || !$group_info['time_list']['earliest'] || !$group_info['time_list']['latest']) {
            $item_list    = $group_info['item_list'];
            $options_list = $group_info['options_list'];
            $available_time_range = $group_info['time_list'];
            if ($time_range && $time_range !== $options_list['time_range']) {
                $this->gm->change_group_option($group_id, 'time_range', $time_range);
            } else {
                $time_range = $options_list['time_range'];
            }
            $time_basis = !$time_basis ? $options_list['time_basis'] : $time_basis;
            $start_date = !$start_date || !strtotime($options_list['start_time']) ? $options_list['start_time'] : $start_date;
            $end_date   = !$end_date || !strtotime($options_list['end_time']) ? $options_list['end_time'] : $end_date;

            $object_id_list = array_values($item_list);
            $this->page_data['object_id_list']         = $object_id_list;
            $this->page_data["{$object_type}_id_list"] = $object_id_list;

            $latest_data = is_array($available_time_range) && array_key_exists('latest', $available_time_range) ? $available_time_range['latest'] : false;
        }

        $this->page_data['object_type'] = $object_type;
        $this->page_data['group_id'] = $group_id;
        if (!$latest_data || empty($group_info)) {
            $this->page_data['results_message'] = 'No Data Available for this group of '.plural(ucwords($object_type));
            $this->page_data['object_id_list'] = [];
            $this->load->view('object_types/group_body_insert.html', $this->page_data);
            return;
        }

        $latest_data_object = new DateTime($latest_data);
        $time_range         = str_replace(array('-', '_', '+'), ' ', $time_range);
        $this->page_data['results_message'] = '&nbsp;';
        $valid_tr = strtotime($time_range);
        $valid_st = strtotime($start_date);
        $valid_et = strtotime($end_date);

        $this->benchmark->mark('time_range_verify_start');
        if (!$valid_tr || ($valid_st && $valid_et)) {
            if ($time_range === 'custom' || ($valid_st && $valid_et)) {
                $earliest_available_object = new DateTime($available_time_range['earliest']);
                $latest_available_object   = new DateTime($available_time_range['latest']);
                $start_date_object         = new DateTime($start_date);
                $end_date_object           = new DateTime($end_date);
                if ($start_date_object->getTimestamp() < $earliest_available_object->getTimestamp()) {
                    $start_date_object = clone $earliest_available_object;
                    $start_date        = $start_date_object->format('Y-m-d');
                }

                if ($end_date_object->getTimestamp() > $latest_available_object->getTimestamp()) {
                    $end_date_object = clone $latest_available_object;
                    $end_date        = $end_date_object->format('Y-m-d');
                }

                $times = array(
                          'start_date'                => $start_date_object->format('Y-m-d H:i:s'),
                          'end_date'                  => $end_date_object->format('Y-m-d H:i:s'),
                          'earliest'                  => $earliest_available_object->format('Y-m-d H:i:s'),
                          'latest'                    => $latest_available_object->format('Y-m-d H:i:s'),
                          'start_date_object'         => $start_date_object,
                          'end_date_object'           => $end_date_object,
                          'time_range'                => $time_range,
                          'earliest_available_object' => $earliest_available_object,
                          'latest_available_object'   => $latest_available_object,
                          'message'                   => '<p>Using '.$end_date_object->format('Y-m-d').' as the new origin time</p>',
                         );
            } else {
                $time_range = '1 week';
                $times      = time_range_to_date_pair($time_range, $available_time_range);
            }//end if
        } else {
            if (($valid_st || $valid_et) && !($valid_st && $valid_et)) {
                $times = time_range_to_date_pair($time_range, $available_time_range, $start_date, $end_date);
            } else {
                $times = time_range_to_date_pair($time_range, $available_time_range);
            }
        }//end if
        $this->benchmark->mark('time_range_verify_end');

        extract($times);

        // $transaction_retrieval_func = "summarize_uploads_by_{$object_type}_list";
        // $transaction_info           = array();
        // $this->benchmark->mark("{$transaction_retrieval_func}_start");
        // $transaction_info = $this->summary->$transaction_retrieval_func(
        //     $object_id_list, $start_date, $end_date, $with_timeline, $time_basis
        // );
        $transaction_info = $this->summary->summarize_uploads(
            $object_type,
            $object_id_list,
            $start_date,
            $end_date,
            $with_timeline,
            $time_basis
        );
        // unset($transaction_info['transaction_list']);
        $this->page_data['transaction_info'] = $transaction_info;
        $this->page_data['times']            = $times;
        $this->page_data['include_timeline'] = $with_timeline;
        // $this->benchmark->mark("{$transaction_retrieval_func}_end");

        if ($with_timeline) {
            $this->load->view('object_types/group_body_insert.html', $this->page_data);
        } else {
            $this->load->view('object_types/group_pie_scripts_insert.html', $this->page_data);
        }
    }//end _get_reporting_info_list_base()


    /**
     * [get_group_timeline_data description]
     *
     * @param string $object_type [description]
     * @param int    $group_id    [description]
     * @param string $start_date  [description]
     * @param string $end_date    [description]
     *
     * @return [type] [description]
     */
    public function get_group_timeline_data($object_type, $group_id, $start_date, $end_date)
    {
        if (!in_array($object_type, $this->accepted_object_types)) {
            return false;
        }

        $group_info = $this->gm->get_group_info($group_id);

        $object_list    = $group_info['item_list'];
        $results        = $this->summary->summarize_uploads(
            $object_type,
            $object_list,
            $start_date,
            $end_date,
            true,
            $group_info['options_list']['time_basis']
        );
        $downselect     = $results['day_graph']['by_date'];
        $return_array   = array(
                           'file_volumes'       => array_values($downselect['file_volume_array']),
                           'transaction_counts' => array_values($downselect['transaction_count_array']),
                          );
        $start_date_obj = new DateTime($start_date);
        $end_date_obj   = new DateTime($end_date);
        $this->gm->change_group_option($group_id, 'start_time', $start_date_obj->format('Y-m-d'));
        $this->gm->change_group_option($group_id, 'end_time', $end_date_obj->format('Y-m-d'));
        transmit_array_with_json_header($return_array);
    }//end get_group_timeline_data()


    /**
     * Retrieves a set of projects and their accompanying info to be used in a dropdown menu
     * in the UI. Doesn't actually return anything, but hands the array off to a helper function
     * that formats it into a json object for return through AJAX
     *
     * @param string $project_name_fragment the search term to use
     * @param string $active                active/inactive project switch (active/inactive)
     *
     * @uses EUS::get_projects_by_name to get project list from the EUS Database clone
     *
     * @return none
     */
    public function get_projects($project_name_fragment, $active = 'active')
    {
        $results = $this->eus->get_projects_by_name($project_name_fragment, $active);
        transmit_array_with_json_header($results);
    }//end get_projects()
}//end class
