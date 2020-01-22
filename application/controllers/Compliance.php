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
 ini_set('max_execution_time', 0);
 ini_set('memory_limit', '2048M');
 /**
  *  Group is a CI controller class that extends Baseline_controller
  *
  *  The *Compliance* class contains user-interaction logic for a set of CI pages.
  *  It interfaces with several different models to retrieve summary information
  *  for purposes of assuring compliance with DOE policy in regards to data
  *  retention and storage.
  *
  * @category Page_Controller
  * @package  Pacifica-reporting
  * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
  *
  * @license BSD https://opensource.org/licenses/BSD-3-Clause
  * @link    http://github.com/EMSL-MSC/Pacifica-reporting

  * @see    https://github.com/EMSL-MSC/pacifica-reporting
  * @access public
  */
class Compliance extends Baseline_api_controller
{
    /**
     * [__construct description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        // $this->load->model('Summary_api_model', 'summary');
        $this->load->model('Compliance_model', 'compliance');
        $this->load->helper(
            ['network', 'theme', 'search_term', 'form', 'time']
        );
        $this->accepted_object_types = ['instrument', 'user', 'project'];
        sort($this->accepted_object_types);
        $this->page_data['script_uris'] = [
           '/resources/scripts/spinner/spin.min.js',
           '/resources/scripts/spinner/jquery.spin.js',
           '/resources/scripts/select2-4/dist/js/select2.js',
           '/resources/scripts/moment.min.js',
           '/resources/scripts/js-cookie/src/js.cookie.js',
           '/project_resources/scripts/jsgrid/jsgrid.min.js',
           '/project_resources/scripts/compliance_common.js'
        ];
        $this->page_data['css_uris'] = [
           '/resources/scripts/select2-4/dist/css/select2.css',
           '/project_resources/stylesheets/combined.css',
           '/project_resources/stylesheets/selector.css',
           '/project_resources/stylesheets/compliance.css',
           '/project_resources/scripts/jsgrid/jsgrid.min.css'
        ];
        $this->page_data['load_prototype'] = false;
        $this->page_data['load_jquery'] = true;
        $this->last_update_time = get_last_update(APPPATH);
        $this->page_data['object_types'] = $this->accepted_object_types;
    }

    /**
     *  Grabs the base-level call to this controller and loads up
     *  the compliance report default page
     *
     * @param string $report_type The object type (project or instrument) to anchor the report with\
     *
     * @method index
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     *
     * @return none
     */
    public function index($report_type = 'project')
    {
        $this->page_data['page_header'] = "Compliance Reporting";
        $valid_report_types = array('project', 'instrument');
        $report_type = !in_array($report_type, $valid_report_types) ? 'project' : $report_type;
        $this->page_data['script_uris'][] = '/project_resources/scripts/jsgrid/jsgrid.min.js';
        $this->page_data['script_uris'][] = '/project_resources/scripts/compliance.js';
        $this->page_data['css_uris'][] = '/project_resources/scripts/jsgrid/jsgrid.min.css';
        $this->page_data['script_uris'] = load_scripts($this->page_data['script_uris']);
        $this->page_data['css_uris'] = load_stylesheets($this->page_data['css_uris']);

        $earliest_latest = $this->compliance->earliest_latest_booking_periods();
        $js = "var earliest_available = '{$earliest_latest['earliest']}'; var latest_available = '{$earliest_latest['latest']}'";
        $this->page_data['js'] = $js;
        $this->page_data['object_type'] = $report_type;
        $this->load->view("data_compliance_report_view.html", $this->page_data);
    }

    /**
     * [activity_report description]
     *
     * @param boolean $start_date Date to start reporting (YYYY-MM-DD ISO format)
     * @param boolean $end_date   Date to end reporting (YYYY-MM-DD ISO format)
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function activity_report($start_date = false, $end_date = false)
    {
        $this->page_data['page_header'] = "Project Activity Report";
        $this->page_data['script_uris'][] = '/project_resources/scripts/activity_report.js';
        $this->page_data['script_uris'] = load_scripts($this->page_data['script_uris']);
        $this->page_data['css_uris'] = load_stylesheets($this->page_data['css_uris']);
        $earliest_latest = $this->compliance->earliest_latest_booking_periods();
        $js = "var earliest_available = '{$earliest_latest['earliest']}'; var latest_available = '{$earliest_latest['latest']}'";
        $this->page_data['js'] = $js;
        // $this->page_data['js'] = $js;
        $this->load->view("activity_report_view.html", $this->page_data);
    }

    /**
     * Ajax catcher to generate the guts of the actual compliance report
     *
     * @param string $object_type The object type (project or instrument) to anchor the report with
     * @param string $start_time  [description] earliest date to grab
     * @param string $end_time    [description] latest date to grab
     * @param string $output_type should the output go to screen or csv file
     *
     * @return none
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_booking_report($object_type, $start_time, $end_time, $output_type = 'screen')
    {
        if (!in_array($object_type, array('instrument', 'project'))) {
            return false;
        }
        $valid_output_types = array('screen', 'csv');
        $output_type = !in_array($output_type, $valid_output_types) ? 'screen' : $output_type;

        $start_time_obj = strtotime($start_time) ? new DateTime($start_time) : new DateTime('first day of this month');
        $end_time_obj = strtotime($end_time) ? new DateTime($end_time) : new DateTime('last day of this month');

        $eus_booking_records
            = $this->compliance->retrieve_active_project_list_from_eus($start_time_obj, $end_time_obj);

        $group_name_lookup = $this->compliance->get_group_name_lookup();
        $mappings = $this->compliance->cross_reference_bookings_and_data($object_type, $eus_booking_records, clone $start_time_obj, clone $end_time_obj);
        ksort($mappings);

        $page_data = [
            'results_collection' => $mappings,
            'group_name_lookup' => $group_name_lookup,
            'object_type' => $object_type,
            'start_date' => $start_time_obj->format('Y-m-d'),
            'end_date' => $end_time_obj->format('Y-m-d')
        ];

        if ($output_type == 'csv') {
            $filename = "Compliance_report_by_project_".$start_time_obj->format('Y-m').".csv";

            header('Content-Type: application/octet-stream');
            header('Content-disposition: attachment; filename="'.$filename.'"');
            $export_data = array();
            $handle = fopen('php://output', 'w');
            $field_names = array(
                "project_id","instrument_id","instrument_name",
                "number_of_bookings","data_file_count"
            );
            fputcsv($handle, $field_names);
            foreach ($mappings as $project_id => $entry) {
                foreach ($entry as $instrument_id => $info) {
                    $data = [
                        $project_id, $instrument_id,
                        // $group_name_lookup[$info['instrument_group_id']],
                        $this->compliance->get_instrument_name($instrument_id),
                        $info['booking_count'], $info['file_count']
                    ];
                    fputcsv($handle, $data);
                }
            }
            fclose($handle);
            exit();
        } elseif ($output_type = 'json') {
            $booking_results = $this->compliance->format_bookings_for_jsgrid($mappings);
            header("Content-Type: text/json");
            $response = [
                'booking_results' => $booking_results,
                'start_time' => $start_time,
                'end_time' => $end_time
            ];
            print(json_encode($response));
        } else {
            $this->load->view('object_types/compliance_reporting/reporting_table_project.html', $page_data);
        }
    }

    public function get_activity_report($start_time, $end_time)
    {
        // $valid_output_types = array('screen', 'csv', 'json');
        $requested_type = $this->input->get_request_header('Accept', true);
        $valid_requested_types = [
            "text/csv" => "csv",
            "text/json" => "json",
            "application/json" => "json"
        ];
        $object_type = "project";
        $output_type = array_key_exists($requested_type, $valid_requested_types) ? $valid_requested_types[$requested_type] : 'screen';
        // $output_type = !in_array($output_type, $valid_output_types) ? 'screen' : $output_type;
        $start_time_obj = strtotime($start_time) ? new DateTime($start_time) : new DateTime('first day of this month');
        $end_time_obj = strtotime($end_time) ? new DateTime($end_time) : new DateTime('last day of this month');

        $eus_booking_records
            = $this->compliance->retrieve_active_project_list_from_eus($start_time_obj, $end_time_obj);

        $group_name_lookup = $this->compliance->get_group_name_lookup();
        $mappings = $this->compliance->cross_reference_bookings_and_data($object_type, $eus_booking_records, clone $start_time_obj, clone $end_time_obj);

        $exclusion_list = array_keys($mappings);

        $eus_records = $this->compliance->get_unbooked_projects($start_time_obj, $end_time_obj, $exclusion_list);

        $page_data = [
            'unused_projects' => $eus_records,
            'start_date' => $start_time_obj->format('Y-m-d'),
            'end_date' => $end_time_obj->format('Y-m-d')
        ];

        if ($output_type == 'csv') {
            $filename = "Project_activity_report_".$start_time_obj->format('Y-m')."-".$end_time_obj->format('Y-m').".csv";
            header('Content-Type: application/octet-stream');
            header('Content-disposition: attachment; filename="'.$filename.'"');
            $export_data = array();
            $handle = fopen('php://output', 'w');
            $field_names = array(
                "project_id","project_type","principal_investigator",
                "start_date","end_date","closing_date"
            );
            fputcsv($handle, $field_names);
            foreach ($mappings as $project_id => $entry) {
                foreach ($entry as $instrument_id => $info) {
                    $data = [
                        $project_id, $info['project_type'],
                        $info['principal_investigator'],
                        $info['actual_start_date'],
                        $info['actual_end_date'],
                        $info['closing_date']
                    ];
                    fputcsv($handle, $data);
                }
            }
            fclose($handle);
            exit();
        } elseif ($output_type = 'json') {
            $no_booking_results = $this->compliance->format_no_bookings_for_jsgrid($eus_records);
            header("Content-Type: text/json");
            print(json_encode($no_booking_results));
        } else {
            $this->load->view('object_types/compliance_reporting/project_activity_table.html', $page_data);
        }
    }
}
