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
        $this->accepted_object_types = array('instrument', 'user', 'proposal');
        sort($this->accepted_object_types);
        $this->page_data['script_uris'] = array(
           '/resources/scripts/spinner/spin.min.js',
           '/resources/scripts/spinner/jquery.spin.js',
           '/resources/scripts/select2-4/dist/js/select2.js',
           '/resources/scripts/moment.min.js',
           '/resources/scripts/js-cookie/src/js.cookie.js',
           '/project_resources/scripts/compliance.js'

        );
        $this->page_data['css_uris'] = array(
           '/resources/scripts/select2-4/dist/css/select2.css',
           '/project_resources/stylesheets/combined.css',
           '/project_resources/stylesheets/selector.css',
           '/project_resources/stylesheets/compliance.css'
        );
        $this->page_data['load_prototype'] = false;
        $this->page_data['load_jquery'] = true;
        $this->last_update_time = get_last_update(APPPATH);
        $this->page_data['object_types'] = $this->accepted_object_types;
    }

    /**
     *  Grabs the base-level call to this controller and loads up
     *  the compliance report default page
     *
     * @param string $report_type The object type (proposal or instrument) to anchor the report with\
     *
     * @method index
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     *
     * @return none
     */
    public function index($report_type = 'proposal')
    {
        $this->page_data['page_header'] = "Compliance Reporting";
        $valid_report_types = array('proposal', 'instrument');
        $report_type = !in_array($report_type, $valid_report_types) ? 'proposal' : $report_type;
        $this->page_data['script_uris'] = load_scripts($this->page_data['script_uris']);
        $this->page_data['css_uris'] = load_stylesheets($this->page_data['css_uris']);

        $earliest_latest = $this->compliance->earliest_latest_booking_periods();
        $js = "var earliest_available = '{$earliest_latest['earliest']}'; var latest_available = '{$earliest_latest['latest']}'";
        $this->page_data['js'] = $js;
        $this->page_data['object_type'] = $report_type;
        $this->load->view("data_compliance_report_view.html", $this->page_data);
    }

    /**
     * Ajax catcher to generate the guts of the actual compliance report
     *
     * @param string $object_type The object type (proposal or instrument) to anchor the report with
     * @param string $start_time  [description] earliest date to grab
     * @param string $end_time    [description] latest date to grab
     * @param string $output_type should the output go to screen or csv file
     *
     * @return none
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_report($object_type, $start_time, $end_time, $output_type = 'screen')
    {
        if (!in_array($object_type, array('instrument', 'proposal'))) {
            return false;
        }
        $valid_output_types = array('screen', 'csv');
        $output_type = !in_array($output_type, $valid_output_types) ? 'screen' : $output_type;

        $t_first_day = new DateTime('first day of this month');
        $t_last_day = new DateTime('last day of this month');

        // header('Content-Type: application/json');
        $start_time_obj = strtotime($start_time) ? new DateTime($start_time) : $t_first_day;
        $end_time_obj = strtotime($end_time) ? new DateTime($end_time) : $t_last_day;
        $eus_booking_records
            = $this->compliance->retrieve_active_proposal_list_from_eus($start_time_obj, $end_time_obj);

        $group_name_lookup = $this->compliance->get_group_name_lookup();
        $mappings = $this->compliance->cross_reference_bookings_and_data($object_type, $eus_booking_records, $start_time_obj, $end_time_obj);
        ksort($mappings);

        $page_data = array(
            'results_collection' => $mappings,
            'unused_proposals' => $eus_booking_records['unbooked_proposals'],
            'group_name_lookup' => $group_name_lookup,
            'object_type' => $object_type,
            'start_date' => $start_time_obj->format('Y-m-d'),
            'end_date' => $end_time_obj->format('Y-m-d')
        );

        if ($output_type == 'csv') {
            $filename = "Compliance_report_by_proposal_".$start_time_obj->format('Y-m').".csv";
            header('Content-Type: text/csv');
            header('Content-disposition: attachment; filename="'.$filename.'"');
            $export_data = array();
            $handle = fopen('php://output', 'w');
            $field_names = array(
                "proposal_id","instrument_id","group","instrument_name",
                "number_of_bookings","data_file_count"
            );
            fputcsv($handle, $field_names);
            foreach ($mappings as $proposal_id => $entry) {
                foreach ($entry as $instrument_id => $info) {
                    $data = array(
                        $proposal_id, $instrument_id,
                        $group_name_lookup[$info['instrument_group_id']],
                        $this->compliance->get_instrument_name($instrument_id),
                        $info['booking_count'], $info['file_count']
                    );
                    fputcsv($handle, $data);
                }
            }
            fclose($handle);
        } else {
            $this->load->view('object_types/compliance_reporting/reporting_table_proposal.html', $page_data);
        }
    }
}
