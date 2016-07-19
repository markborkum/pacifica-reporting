<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once 'Baseline_controller.php';

class Group extends Baseline_controller
{
    public $last_update_time;
    public $accepted_object_types;
    public $accepted_time_basis_types;
    public $local_resources_folder;
    private $debug;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Reporting_model', 'rep');
        $this->load->model('Group_info_model', 'gm');
        $this->load->model('Summary_model','summary');
        $this->load->library('EUS', '', 'eus');
        $this->load->helper(array('network', 'file_info', 'inflector', 'time', 'item', 'search_term', 'cookie'));
        $this->last_update_time = get_last_update(APPPATH);
        $this->accepted_object_types = array('instrument', 'user', 'proposal');
        $this->accepted_time_basis_types = array('submit_time', 'create_time', 'modified_time');
        $this->local_resources_folder = $this->config->item('local_resources_folder');
        $this->debug = $this->config->item('debug_enabled');
    }

    public function index(){
      redirect('group/view');
    }

    public function view($object_type, $time_range = false, $start_date = false, $end_date = false, $time_basis = false)
    {
        // $this->output->enable_profiler(TRUE);
        $this->benchmark->mark('controller_view_start');
        $object_type = singular($object_type);
        $time_basis = !empty($time_basis) ? $time_basis : 'modification_time';
        $accepted_object_types = array('instrument', 'proposal', 'user');
        if (!in_array($object_type, $accepted_object_types)) {
            redirect('group/view/instrument');
        }
        $this->page_data['page_header'] = 'Aggregated MyEMSL Uploads by '.ucwords($object_type).' Grouping';
        $this->page_data['my_object_type'] = $object_type;
        $this->page_data['css_uris'] = array(
            '/resources/stylesheets/status_style.css',
            '/resources/scripts/select2/select2.css',
            '/resources/scripts/bootstrap/css/bootstrap.css',
            '/resources/scripts/bootstrap-daterangepicker/daterangepicker.css',
            base_url().'application/resources/stylesheets/reporting.css',
        );
        $this->page_data['script_uris'] = array(
            '/resources/scripts/spinner/spin.min.js',
            '/resources/scripts/spinner/jquery.spin.js',
            '/resources/scripts/moment.min.js',
            '/resources/scripts/select2/select2.min.js',
            '/resources/scripts/bootstrap-daterangepicker/daterangepicker.js',
            '/resources/scripts/jquery-typewatch/jquery.typewatch.js',
            '/resources/scripts/highcharts/js/highcharts.js',
            base_url().'application/resources/scripts/reporting.js',
        );
        $my_groups = $this->gm->get_selected_groups($this->user_id, $object_type);
        // $object_list = array();
        if (empty($my_groups)) {
            $this->page_data['content_view'] = 'object_types/select_some_groups_insert.html';
        } else {
            $this->page_data['my_groups'] = '';
            foreach ($my_groups as $group_id => $group_info) {
                $my_start_date = false;
                $my_end_date = false;
                $options_list = $group_info['options_list'];
                $my_start_date = strtotime($start_date) ? $start_date : $options_list['start_time'];
                $my_start_date = $my_start_date != 0 ? $my_start_date : false;
                $my_end_date = strtotime($end_date) ? $end_date : $options_list['end_time'];
                $my_end_date = $my_end_date != 0 ? $my_end_date : false;
                $time_basis = $options_list['time_basis'];
                $time_range = $time_range ? $time_range : $options_list['time_range'];

                if ($time_range && $time_range != $options_list['time_range'] && $time_range != 'custom') {
                    $this->gm->change_group_option($group_id, 'time_range', $time_range);
                }

                $update_start = !$my_start_date ? true:false;
                $update_end = !$my_end_date ? true:false;
                // $object_list = array_merge($object_list, $group_info['item_list']);
                // echo "group_info<br />";
                // var_dump($group_info);
                $valid_date_range = $this->gm->earliest_latest_data_for_list($object_type, $group_info['item_list'], $time_basis);
                // echo "date range<br />";
                // var_dump($valid_date_range);
                $my_times = $this->fix_time_range($time_range, $my_start_date, $my_end_date, $valid_date_range);

                if($update_start) $this->gm->change_group_option($group_id,'start_time',$my_times['start_time_object']->format('Y-m-d'));
                if($update_end) $this->gm->change_group_option($group_id,'end_time',$my_times['end_time_object']->format('Y-m-d'));

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
                    'group_id' => $group_id,
                    'object_type' => $object_type,
                    'options_list' => $options_list,
                    'group_name' => $group_info['group_name'],
                    'item_list' => $group_info['item_list'],
                    'time_basis' => $time_basis,
                    'time_range' => $time_range,
                    'times' => $my_times,
                );
            }
            // $object_info = $this->eus->get_object_info($object_list, $object_type);
            $time_range = str_replace(array('-', '_', '+'), ' ', $time_range);
            // $this->page_data['my_objects'] = $object_info;
            $this->page_data['my_groups'] = $my_groups;
            $this->page_data['content_view'] = 'object_types/group.html';
        }
        $this->page_data['js'] = "var object_type = '{$object_type}'; var time_range = '{$time_range}';";
        $this->load->view('reporting_view.html', $this->page_data);
        $this->benchmark->mark('controller_view_end');
    }

    private function fix_time_range($time_range, $start_date, $end_date, $valid_date_range = false)
    {
        if (!empty($start_date) && !empty($end_date)) {
            $times = $this->summary->canonicalize_date_range($start_date, $end_date);

            return $times;
        }
        $time_range = str_replace(array('-', '_', '+'), ' ', $time_range);
        if (!strtotime($time_range)) {
            if ($time_range == 'custom' && strtotime($start_date) && strtotime($end_date)) {
                //custom date_range, just leave them. Canonicalize will fix them
            } else {
                //looks like the time range is borked, pick the default
                $time_range = '1 week';
                $times = time_range_to_date_pair($time_range, $valid_date_range);
                extract($times);
            }
        } else {
            $times = time_range_to_date_pair($time_range, $valid_date_range);
            extract($times);
        }

        $times = $this->summary->canonicalize_date_range($start_date, $end_date);

        return $times;
    }

    public function get_transaction_list_details()
    {
        if ($this->input->post()) {
            $transaction_list = $this->input->post();
        } elseif ($this->input->is_ajax_request() || file_get_contents('php://input')) {
            $HTTP_RAW_POST_DATA = file_get_contents('php://input');
            $transaction_list = json_decode($HTTP_RAW_POST_DATA, true);
        }
        // extract($times_list); //yields $start_date, $end_date, $group_id
        // $group_info = $this->gm->get_group_info($group_id);
        $results = $this->rep->detailed_transaction_list($transaction_list);
        $this->page_data['transaction_info'] = $results;

        $this->load->view('object_types/transaction_details_insert.html', $this->page_data);
    }

    public function get_reporting_info_list($object_type, $group_id, $time_basis = false, $time_range = false, $start_date = false, $end_date = false, $with_timeline = true)
    {
        $this->get_reporting_info_list_base($object_type, $group_id, $time_basis, $time_range, $start_date, $end_date, true, false);
    }

    public function get_reporting_info_list_no_timeline($object_type, $group_id, $time_basis = false, $time_range = false, $start_date = false, $end_date = false)
    {
        $this->get_reporting_info_list_base($object_type, $group_id, $time_basis, $time_range, $start_date, $end_date, false, false);
    }

    private function get_reporting_info_list_base($object_type, $group_id, $time_basis, $time_range, $start_date = false, $end_date = false, $with_timeline = true, $full_object = false)
    {
        // $this->output->enable_profiler(TRUE);
        $this->benchmark->mark('get_group_info_start');
        $group_info = $this->gm->get_group_info($group_id);
        $this->benchmark->mark('get_group_info_end');
        // var_dump($group_info);
        $item_list = $group_info['item_list'];
        $options_list = $group_info['options_list'];
        if ($time_range && $time_range != $options_list['time_range']) {
            $this->gm->change_group_option($group_id, 'time_range', $time_range);
        } else {
            $time_range = $options_list['time_range'];
        }
        $time_basis = !$time_basis ? $options_list['time_basis'] : $time_basis;
        $start_date = !$start_date || !strtotime($options_list['start_time']) ? $options_list['start_time'] : $start_date;
        $end_date = !$end_date || !strtotime($options_list['end_time']) ? $options_list['end_time'] : $end_date;

        $object_id_list = array_values($item_list);
        $this->page_data['object_id_list'] = $object_id_list;
        $this->page_data["{$object_type}_id_list"] = $object_id_list;
        $this->page_data['object_type'] = $object_type;
        $this->page_data['group_id'] = $group_id;

        $this->benchmark->mark('get_earliest_latest_start');
        $available_time_range = $this->gm->earliest_latest_data_for_list($object_type, $object_id_list, $time_basis);
        $latest_data = is_array($available_time_range) && array_key_exists('latest', $available_time_range) ? $available_time_range['latest'] : false;
        $this->benchmark->mark('get_earliest_latest_end');

        if (!$latest_data) {
            $this->page_data['results_message'] = 'No Data Available for this group of '.plural(ucwords($object_type));
            $this->load->view('object_types/group_body_insert.html', $this->page_data);
            return;
        }

        $latest_data_object = new DateTime($latest_data);
        $time_range = str_replace(array('-', '_', '+'), ' ', $time_range);
        $this->page_data['results_message'] = '&nbsp;';
        $valid_tr = strtotime($time_range);
        $valid_st = strtotime($start_date);
        $valid_et = strtotime($end_date);

        $this->benchmark->mark('time_range_verify_start');
        if (!$valid_tr || ($valid_st && $valid_et)) {
            if ($time_range == 'custom' || ($valid_st && $valid_et)) {
                $earliest_available_object = new DateTime($available_time_range['earliest']);
                $latest_available_object = new DateTime($available_time_range['latest']);
                $start_date_object = new DateTime($start_date);
                $end_date_object = new DateTime($end_date);
                if ($start_date_object->getTimestamp() < $earliest_available_object->getTimestamp()) {
                    $start_date_object = clone $earliest_available_object;
                    $start_date = $start_date_object->format('Y-m-d');
                }
                if ($end_date_object->getTimestamp() > $latest_available_object->getTimestamp()) {
                    $end_date_object = clone $latest_available_object;
                    $end_date = $end_date_object->format('Y-m-d');
                }
                $times = array(
                    'start_date' => $start_date_object->format('Y-m-d H:i:s'),
                    'end_date' => $end_date_object->format('Y-m-d H:i:s'),
                    'earliest' => $earliest_available_object->format('Y-m-d H:i:s'),
                    'latest' => $latest_available_object->format('Y-m-d H:i:s'),
                    'start_date_object' => $start_date_object,
                    'end_date_object' => $end_date_object,
                    'time_range' => $time_range,
                    'earliest_available_object' => $earliest_available_object,
                    'latest_available_object' => $latest_available_object,
                    'message' => '<p>Using '.$end_date_object->format('Y-m-d').' as the new origin time</p>',
                );
                // var_dump($times);
            } else {
                $time_range = '1 week';
                $times = time_range_to_date_pair($time_range, $available_time_range);
            }
        } else {
            if (($valid_st || $valid_et) && !($valid_st && $valid_et)) {
                $times = time_range_to_date_pair($time_range, $available_time_range, $start_date, $end_date);
            } else {
                $times = time_range_to_date_pair($time_range, $available_time_range);
            }
        }
        $this->benchmark->mark('time_range_verify_end');

        extract($times);

        $transaction_retrieval_func = "summarize_uploads_by_{$object_type}_list";
        $transaction_info = array();
        $this->benchmark->mark("{$transaction_retrieval_func}_start");
        $transaction_info = $this->summary->$transaction_retrieval_func($object_id_list, $start_date, $end_date, $with_timeline, $time_basis);
        unset($transaction_info['transaction_list']);
        $this->page_data['transaction_info'] = $transaction_info;
        $this->page_data['times'] = $times;
        $this->page_data['include_timeline'] = $with_timeline;
        $this->benchmark->mark("{$transaction_retrieval_func}_end");

        // var_dump($this->page_data);
        if ($with_timeline) {
            $this->load->view('object_types/group_body_insert.html', $this->page_data);
        } else {
            $this->load->view('object_types/group_pie_scripts_insert.html', $this->page_data);
        }
    }

    public function get_group_timeline_data($object_type, $group_id, $start_date, $end_date)
    {
        if (!in_array($object_type, $this->accepted_object_types)) {
            return false;
        }
        $group_info = $this->gm->get_group_info($group_id);

        $object_list = $group_info['item_list'];
        $retrieval_func = "summarize_uploads_by_{$object_type}_list";
        $results = $this->summary->$retrieval_func($object_list, $start_date, $end_date, true, $group_info['options_list']['time_basis']);
        $downselect = $results['day_graph']['by_date'];
        $return_array = array(
            'file_volumes' => array_values($downselect['file_volume_array']),
            'transaction_counts' => array_values($downselect['transaction_count_array']),
        );
        $start_date_obj = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        $this->gm->change_group_option($group_id, 'start_time', $start_date_obj->format('Y-m-d'));
        $this->gm->change_group_option($group_id, 'end_time', $end_date_obj->format('Y-m-d'));
        send_json_array($return_array);
    }

    public function get_proposals($proposal_name_fragment, $active = 'active')
    {
        $results = $this->eus->get_proposals_by_name($proposal_name_fragment, $active);
        send_json_array($results);
    }

    public function add_objects_instructions($object_type)
    {
        $object_examples = array(
            'instrument' => array(),
            'proposal' => array(),
            'user' => array(),
        );
        $object_examples['instrument'] = array(
            "'nmr' returns a list of all instruments with 'nmr' somewhere in the name or description",
            "'34075' returns the instrument having an ID of '34075' in the EUS database",
            "'nmr nittany' returns anything with 'nmr' and 'nittany' somewhere in the name or description",
        );
        $object_examples['proposal'] = array(
            "'phos' returns a list of all proposals having the term 'phos' somewhere in the title or description",
            "'49164' returns a proposal having an ID of '49164' in the EUS database",
        );
        $object_examples['user'] = array(
            "'jones' returns a list of EUS users having 'jones' somewhere in their first name, last name or email",
            "'36846' returns a user having the ID of '36846' in the EUS database",
        );

        return $object_examples[$object_type];
    }
}
