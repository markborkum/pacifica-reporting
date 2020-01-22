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

/**
 *  Reporting Model
 *
 *  The **Search_model** class contains functionality
 *  for retrieving metadata entries from the policy server.
 *
 * @category CI_Model
 * @package  Pacifica-reporting
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 * @link    http://github.com/EMSL-MSC/Pacifica-reporting
 *
 * @access public
 */
class Compliance_model extends CI_Model
{
    /**
     *  Class constructor
     *
     * @method __construct
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('PHPRequests');
        $this->metadata_url_base = $this->config->item('metadata_server_base_url');
        $this->policy_url_base = $this->config->item('policy_server_base_url');
        $this->content_type = "application/json";
        $this->eusDB = $this->load->database('eus', true);
        $this->instrument_cache = array();
        $this->instrument_group_cache = array();
        $this->project_cache = array();
    }

    /**
     * Get information about specific transactions from metadata_server_base_url
     *
     * @param string   $object_type    project or instrument
     * @param array    $id_list        list of object id's to search framework
     * @param datetime $start_time_obj earliest time to retrieve
     * @param datetime $end_time_obj   latest time to retrieve
     *
     * @return array object containing transaction info
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function retrieve_uploads_for_object_list($object_type, $id_list, $start_time_obj = false, $end_time_obj = false)
    {
        $allowed_object_types = array('instrument', 'project');
        if (!in_array($object_type, $allowed_object_types)) {
            return false;
        }

        $json_blob = array(
            'id_list' => $id_list,
            'start_time' => $start_time_obj->format('Y-m-d'),
            'end_time' => $end_time_obj->format('Y-m-d')
        );


        $uploads_url = "{$this->metadata_url_base}/transactioninfo/search/";
        $uploads_url .= $object_type;
        $query = Requests::post(
            $uploads_url,
            array('Content-Type' => 'application/json'),
            json_encode($json_blob),
            array('timeout' => 120)
        );
        if ($query->success) {
            return json_decode($query->body, true);
        }
        return array();
    }

    /**
     * Get information regarding active projects from the EUS database
     *
     * @param datetime $start_date_obj the initial date in the period
     * @param datetime $end_date_obj   the final date in the period
     *
     * @return array list of activity, by project id and instrument_id
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function retrieve_active_project_list_from_eus($start_date_obj, $end_date_obj)
    {
        // get month boundaries
        $first_of_month = $start_date_obj->modify('first day of this month');
        $end_of_month = $end_date_obj->modify('last day of this month');

        //get booking stats
        $booking_stats_columns = [
            'bs.`BOOKING_STATS_ID` as booking_stats_id',
            'bs.`RESOURCE_ID` as instrument_id',
            'bs.`PROPOSAL_ID` as project_id',
            'IFNULL( REPLACE ( `up`.`PROPOSAL_TYPE`, \'_\', \' \' ), IFNULL( uct.CALL_TYPE, \'N/A\' ) ) AS project_type',
            'bs.`MONTH` as query_month',
            'bs.`DATE_START` as date_start',
            'bs.`DATE_FINISH` as date_finish',
            'users.`NAME_FM` as project_pi'
        ];
        $excluded_project_types = [
            'resource_owner'
        ];


        $booking_stats_query = $this->eusDB->select($booking_stats_columns)->from("ERS_BOOKING_STATS bs")
            ->join('UP_PROPOSALS up', 'up.PROPOSAL_ID = bs.PROPOSAL_ID')
            ->join('UP_CALLS uc', 'up.CALL_ID = uc.CALL_ID', 'left')
            ->join('UP_CALL_TYPES uct', 'uc.CALL_TYPE_ID = uct.CALL_TYPE_ID', 'left')
            ->join("(SELECT PERSON_ID, PROPOSAL_ID FROM UP_PROPOSAL_MEMBERS WHERE PROPOSAL_AUTHOR_SW = 'Y') pm", 'bs.`PROPOSAL_ID` = pm.`PROPOSAL_ID`')
            ->join('UP_USERS users', 'users.`PERSON_ID` = pm.`PERSON_ID`')
            ->where('NOT ISNULL(bs.`PROPOSAL_ID`)')
            ->group_start()
            ->where_not_in('up.`PROPOSAL_TYPE`', $excluded_project_types)
            ->or_where('up.`PROPOSAL_TYPE` IS NULL')
            ->group_end()
            ->where('bs.`MONTH`', $first_of_month->format('Y-m-d'))
            ->where_in('bs.`USAGE_CD`', ['REMOTE', 'ONSITE'])
            ->order_by('bs.`PROPOSAL_ID`, bs.`RESOURCE_ID`')
            ->get();

            // echo $this->eusDB->last_query();
            // exit();

        $usage = array(
            'by_project' => []
        );
        $counts = [];
        $row_info = [];
        $instrument_group_lookup = [];
        foreach ($booking_stats_query->result() as $row) {
            $inst_id = intval($row->instrument_id);
            if (!array_key_exists($inst_id, $instrument_group_lookup)) {
                $group_id = $this->get_group_id($inst_id);
                $instrument_group_lookup[$inst_id] = $group_id;
            }
            $group_id = $instrument_group_lookup[$inst_id];

            $record_start_date = new DateTime($row->date_start);
            $record_end_date = new DateTime($row->date_finish);

            if (!array_key_exists($row->project_id, $row_info)) {
                $row_info[$row->project_id] = [];
            }
            if (!array_key_exists($inst_id, $row_info[$row->project_id])) {
                $row_info[$row->project_id][$inst_id] = [];
            }
            $row_info[$row->project_id][$inst_id][$row->booking_stats_id] = $row;
        }
        ksort($counts);
        $entry_count = 0;
        $inst_group_comp = [];
        foreach ($row_info as $project_id => $inst_list) {
            $entry_count += 1;
            foreach ($inst_list as $inst_id_str => $booking_stats_info) {
                // var_dump($booking_stats_info);
                $inst_id = intval($inst_id_str);
                if (!array_key_exists($inst_id, $instrument_group_lookup)) {
                    $group_id = $this->get_group_id($inst_id);
                    $instrument_group_lookup[$inst_id] = $group_id;
                }
                $group_id = $instrument_group_lookup[$inst_id];

                $entry = [
                    'booking_count' => 0,
                    'instrument_id' => $inst_id,
                    'instrument_group_id' => $group_id,
                    'project_id' => $project_id,
                    'project_type' => '',
                    'date_start' => '',
                    'date_finish' => '',
                    'project_pi' => '',
                    'transactions_list' => [],
                    'file_count' => 0
                ];
                $inst_group_comp[] = $group_id;
                foreach ($booking_stats_info as $booking_stats_id => $row) {
                    $record_start_date = new DateTime($row->date_start);
                    $record_end_date = new DateTime($row->date_finish);
                    if (!$entry['date_start']) {
                        $entry['date_start'] = $record_start_date;
                    }
                    if (!$entry['date_finish']) {
                        $entry['date_finish'] = $record_end_date;
                    }

                    $entry['booking_count'] += 1;
                    $entry['project_pi'] = $row->project_pi;
                    $entry['project_type'] = strpos($row->project_type, 'EMSL') === false ? ucwords(strtolower($row->project_type), " ") : $row->project_type;
                    $entry['date_start'] = $record_start_date < $entry['date_start'] ? $record_start_date : $entry['date_start'];
                    $entry['date_finish'] = $record_end_date > $entry['date_finish'] ? $record_end_date : $entry['date_finish'];
                    // echo "\n\n\n* * * * *\n inst_id => ".$inst_id." proj_id => ".$project_id."\n";
                    // var_dump($entry);
                }
                $usage['by_project'][$project_id][$inst_id] = $entry;
            }
        }

        // $ungrouped = $usage['by_project'];
        // foreach ($ungrouped as $project_id => $inst_entries) {
        //     $new_entry = array();
        //     foreach ($inst_entries as $inst_id => $entry) {
        //         if (empty($new_entry)) {
        //             $new_entry = $entry;
        //             $new_entry['instruments_scheduled'] = array($new_entry['instrument_id']);
        //             unset($new_entry['instrument_id']);
        //         } else {
        //             $new_entry['booking_count'] += $entry['booking_count'];
        //             $new_entry['instruments_scheduled'][] = $entry['instrument_id'];
        //         };
        //     }
        //     $usage['by_project'][$project_id][$inst_id] = $new_entry;
        // }

        $usage['instrument_group_compilation'] = array_unique($inst_group_comp);
        // $usage['unbooked_projects'] = $prop_query->result_array();
        return $usage;
    }

    public function get_unbooked_projects($start_date_obj, $end_date_obj, $exclusion_list = [])
    {
        $excluded_project_types = [
            'resource_owner'
        ];
        // get month boundaries
        $first_of_month = $start_date_obj->modify('first day of this month');
        $end_of_month = $end_date_obj->modify('last day of this month');

        $excluded_project_types = array_map('strtolower', ['resource_owner']);
        //get active projects for MONTH
        $project_columns = [
            'prop.`PROPOSAL_ID` as project_id',
            'IFNULL( REPLACE ( `prop`.`PROPOSAL_TYPE`, \'_\', \' \' ), IFNULL( uct.CALL_TYPE, \'N/A\' ) ) AS project_type',
            'prop.`TITLE` as title',
            'prop.`ACTUAL_START_DATE` as actual_start_date',
            'prop.`ACTUAL_END_DATE` as actual_end_date',
            'prop.`CLOSED_DATE as closed_date',
            'users.`NAME_FM` as project_pi'
        ];

        $prop_query = $this->eusDB->select($project_columns)->from('UP_PROPOSALS prop')
            ->join("(SELECT PERSON_ID, PROPOSAL_ID FROM UP_PROPOSAL_MEMBERS WHERE PROPOSAL_AUTHOR_SW = 'Y') pm", 'prop.`PROPOSAL_ID` = pm.`PROPOSAL_ID`')
            ->join('UP_USERS users', 'users.`PERSON_ID` = pm.`PERSON_ID`')
            ->join('UP_CALLS uc', 'prop.CALL_ID = uc.CALL_ID', 'left')
            ->join('UP_CALL_TYPES uct', 'uc.CALL_TYPE_ID = uct.CALL_TYPE_ID', 'left')
            ->where_not_in('prop.`PROPOSAL_ID`', $exclusion_list)
            ->group_start()
            ->where_not_in('prop.`PROPOSAL_TYPE`', $excluded_project_types)
            ->or_where('prop.`PROPOSAL_TYPE` IS NULL')
            ->group_end()
            ->where('prop.`WITHDRAWN_DATE` IS NULL')
            ->where('prop.`DENIED_DATE` IS NULL')
            ->where('prop.`ACCEPTED_DATE` IS NOT NULL')
            ->where('prop.`ACCEPTED_DATE` <', $end_of_month->format('Y-m-d'))
            ->where('prop.`ACTUAL_START_DATE` <', $end_of_month->format('Y-m-d'))
            ->group_start()
            ->or_where('prop.`ACTUAL_END_DATE` >=', $end_of_month->format('Y-m-d'))
            ->or_where('prop.`ACTUAL_END_DATE` IS NULL')
            ->or_where('prop.`CLOSED_DATE` >=', $first_of_month->format('Y-m-d'))
            ->group_end()
            ->group_start()
            ->or_where('prop.`CLOSED_DATE` >=', $first_of_month->format('Y-m-d'))
            ->or_where('prop.`CLOSED_DATE` IS NULL')
            ->group_end()
            ->order_by('prop.`PROPOSAL_TYPE`, (prop.`PROPOSAL_ID` * 1) DESC')
            ->get();

        // echo $this->eusDB->last_query();
        // exit();

        return $prop_query->result_array();
    }

    /**
     * Get the instrument grouping list for all instruments
     *
     * @return integer The group id of the instrument in question

     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_group_id_cache()
    {
        $group_retrieval_url = "{$this->metadata_url_base}/instrument_group?";
        $url_args_array = array(
            'recursion_depth' => 0
        );
        $group_id_list = array();
        $group_retrieval_url .= http_build_query($url_args_array, '', '&');
        $query = Requests::get($group_retrieval_url, array('Accept' => 'application/json'));
        if ($query->status_code == 200 && $query->body != '[]') {
            $results = json_decode($query->body, true);
            foreach ($results as $inst_entry) {
                $group_id_list[$inst_entry['instrument']] = $inst_entry['group'];
            }
        }
        return $group_id_list;
    }


    /**
     * Get the instrument grouping id for a given instrument
     *
     * @param integer $instrument_id the instrument id to search
     *
     * @return integer The group id of the instrument in question
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_group_id($instrument_id)
    {
        if (array_key_exists($instrument_id, $this->instrument_group_cache)) {
            return $this->instrument_group_cache[$instrument_id];
        }
        $group_retrieval_url = "{$this->metadata_url_base}/instrument_group?";
        $url_args_array = array(
            'instrument_id' => $instrument_id,
            'recursion_depth' => 0
        );
        $group_id = 0;
        $group_retrieval_url .= http_build_query($url_args_array, '', '&');
        $query = Requests::get($group_retrieval_url, array('Accept' => 'application/json'));
        if ($query->status_code == 200 && $query->body != '[]') {
            $results = json_decode($query->body, true);
            $inst_entry = array_shift($results);
            $group_id = $inst_entry['group'];
            $this->instrument_group_cache[$instrument_id] = $group_id;
        }
        return $group_id;
    }

    /**
     * Get the full list of instrument group ids and name
     *
     * @return array list of instrument groups with id's
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_group_name_lookup()
    {
        $group_list = array();
        $group_retrieval_url = "{$this->metadata_url_base}/groups";
        $query = Requests::get($group_retrieval_url, array('Accept' => 'application/json'));
        if ($query->status_code == 200 && $query->body != '[]') {
            $results = json_decode($query->body, true);
            foreach ($results as $group_entry) {
                $group_list[$group_entry['_id']] = $group_entry['name'];
            }
        }
        $group_list[0] = "Unknown Instrument Group Type";
        return $group_list;
    }

    /**
     * Get the project name from the id
     *
     * @param integer $project_id the project_id to lookup
     *
     * @return string the name of the project
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_project_name($project_id)
    {
        if (array_key_exists($project_id, $this->project_cache)) {
            return $this->project_cache[$project_id];
        }
        $project_url = "{$this->metadata_url_base}/projects?";
        $url_args_array = array(
            '_id' => $project_id,
            'recursion_depth' => 0
        );
        $project_name = "Unknown Project {$project_id}";
        $project_url .= http_build_query($url_args_array, '', '&');
        $query = Requests::get($project_url, array('Accept' => 'application/json'));
        if ($query->status_code == 200 && $query->body != '[]') {
            $results = json_decode($query->body, true);
            $project_entry = array_shift($results);
            $project_name = $project_entry['title'];
            $this->project_cache[$project_id] = $project_name;
        }
        return $project_name;
    }

    /**
     * Get the project name from the id
     *
     * @param integer $instrument_id the instrument_id to lookup
     *
     * @return string the name of the instrument
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_instrument_name($instrument_id)
    {
        if (array_key_exists($instrument_id, $this->instrument_cache)) {
            return $this->instrument_cache[$instrument_id];
        }
        $instrument_url = "{$this->metadata_url_base}/instruments?";
        $url_args_array = array(
            '_id' => $instrument_id,
            'recursion_depth' => 0
        );
        $instrument_name = "Unknown Instrument {$instrument_id}";
        $instrument_url .= http_build_query($url_args_array, '', '&');
        $query = Requests::get($instrument_url, array('Accept' => 'application/json'));
        if ($query->status_code == 200 && $query->body != '[]') {
            $results = json_decode($query->body, true);
            $instrument_entry = array_shift($results);
            $instrument_name = $instrument_entry['name'];
            $this->instrument_cache[$instrument_id] = $instrument_name;
        }
        return $instrument_name;
    }

    /**
     * Get a full set of instrument id's for a given instrument grouping
     *
     * @param integer $group_id The group id to search
     *
     * @return array a list of the instrument id's for that group
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_instruments_for_group($group_id)
    {
        $instrument_list = array();
        $instruments_retrieval_url = "{$this->metadata_url_base}/instrument_group?";
        $url_args_array = array(
            'group_id' => $group_id,
            'recursion_depth' => 0
        );
        $instruments_retrieval_url .= http_build_query($url_args_array, '', '&');
        $inst_query = Requests::get($instruments_retrieval_url, array('Accept' => 'application/json'));
        if ($inst_query->status_code == 200) {
            $inst_results = json_decode($inst_query->body, true);
            foreach ($inst_results as $entry) {
                $instrument_list[] = $entry['instrument_id'];
            }
        }
        return $instrument_list;
    }

    /**
     * Compare EUS bookings and Pacifica data streams for compliance
     *
     * @param string   $object_type             object type to base report on (project or instrument)
     * @param array    $eus_object_type_records set of records from the ERS Booking table
     * @param datetime $start_time              earliest time to consider
     * @param datetime $end_time                latest time to consider
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function cross_reference_bookings_and_data(
        $object_type,
        $eus_object_type_records,
        $start_time,
        $end_time
    ) {
        $object_list = $eus_object_type_records["by_{$object_type}"];
        $inst_group_list = $eus_object_type_records["instrument_group_compilation"];
        $group_name_lookup = $this->get_group_name_lookup();
        $this->instrument_group_cache = $this->get_group_id_cache();
        $eus_objects = $object_list;

        $booking_stats_cache = array();
        $start_time->modify('-1 week');
        $end_time->modify('+3 weeks');

        $url_args_array = array(
            'start_time' => $start_time->format('Y-m-d'),
            'end_time' => $end_time->format('Y-m-d')
        );

        $url = "{$this->metadata_url_base}/transactioninfo/multisearch?";
        $url .= http_build_query($url_args_array, '', '&');
        $transactions_list_query = Requests::get($url, array('Accept' => 'application/json'));
        if ($transactions_list_query->status_code == 200) {
            $transactions_list = json_decode($transactions_list_query->body, true);
            $stats_template = array(
                'booking_count' => 0,
                'data_file_count' => 0,
                'instruments_scheduled' => array(),
                'transaction_list' => array()
            );
            foreach ($transactions_list as $transaction_id => $trans_info) {
                // $my_group_id = $this->get_group_id($trans_info['instrument_id']);
                $project_id = strval($trans_info['project_id']);
                $instrument_id = intval($trans_info['instrument_id']);
                if (!array_key_exists($project_id, $booking_stats_cache)) {
                    $booking_stats_cache[$project_id] = array();
                }
                if (!array_key_exists($instrument_id, $booking_stats_cache)) {
                    $booking_stats_cache[$project_id][$instrument_id] = $stats_template;
                }
                $booking_stats_cache[$project_id][$instrument_id]['data_file_count']
                    += $trans_info['file_count'];
                $booking_stats_cache[$project_id][$instrument_id]['transaction_list'][$trans_info['upload_date']][]
                    = array(
                        'transaction_id' => $transaction_id,
                        'file_count' => intval($trans_info['file_count']),
                        'upload_date_obj' => new DateTime($trans_info['upload_date'])
                    );
            }
        }

        foreach ($object_list as $project_id => $inst_id_list) {
            foreach ($inst_id_list as $inst_id => $record) {
                $inst_group_id = $record['instrument_group_id'];
                $project_id = strval($record['project_id']);
                $earliest_date = clone($record['date_start']);
                // $earliest_date->modify('-1 week');
                $latest_date = clone($record['date_finish']);
                $latest_date->modify('+3 weeks');
                //check the transaction record for matching entries
                $eus_objects[$project_id][$inst_id]['date_start'] = $record['date_start']->format('Y-m-d');
                $eus_objects[$project_id][$inst_id]['date_finish'] = $record['date_finish']->format('Y-m-d');
                if (isset($booking_stats_cache[$project_id][$inst_id])) {
                    $transactions = $booking_stats_cache[$project_id][$inst_id]['transaction_list'];
                    foreach ($transactions as $upload_date => $txn_entries) {
                        foreach ($txn_entries as $txn_entry) {
                            if ($txn_entry['upload_date_obj'] >= $earliest_date && $txn_entry['upload_date_obj'] <= $latest_date) {
                                $eus_objects[$project_id][$inst_id]['file_count'] += $txn_entry['file_count'];
                            }
                        }
                    }
                }
            }
        }
        return $eus_objects;
    }

    /**
     * Get the bookends for available ERS instrument booking dates
     *
     * @return array set of earliest/latest dates for the ERS booking stream_copy_to_stream
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function earliest_latest_booking_periods()
    {
        $column_array = array(
            'DATE(MIN(MONTH)) as earliest',
            'DATE(MAX(MONTH)) as latest'
        );
        $results_array = array(
            'earliest' => false,
            'latest' => false
        );
        $query = $this->eusDB->select($column_array)->from("ERS_BOOKING_STATS")->get();
        if ($query && $query->num_rows() > 0) {
            $results = $query->result_array();
            $result = array_pop($results);
        }
        return $result;
    }

    /**
     * [format_bookings_for_jsgrid description]
     *
     * @param [type] $mapping_data [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function format_bookings_for_jsgrid($mapping_data)
    {
        $instrument_group_cache = $this->compliance->get_group_id_cache();
        $group_name_lookup = $this->get_group_name_lookup();
        $booking_results = [];
        foreach ($mapping_data as $project_id => $booking_info) {
            $project_file_count = 0;
            $code_yellow = false;
            // pre-scan for project-level coloring
            foreach ($booking_info as $instrument_id => $info) {
                $code_yellow = empty($info['file_count']) || $code_yellow ? true : false;
                $project_file_count += $info['file_count'];
            }
            foreach ($booking_info as $instrument_id => $info) {
                $inst_color_class = $info['file_count'] > 0 ? "green" : "red";
                $project_color_class = "yellow";
                if ($code_yellow && $project_file_count <= 0) {
                    $project_color_class = "red";
                } elseif (!$code_yellow && $project_file_count > 0) {
                    $project_color_class = "green";
                }
                $instrument_group = array_key_exists($instrument_id, $instrument_group_cache) ?
                    $group_name_lookup[$instrument_group_cache[$instrument_id]] :
                    "None";
                $booking_results[] = [
                    'project_id' => $project_id,
                    'project_title' => $this->get_project_name($project_id),
                    'project_type' => $info['project_type'],
                    'instrument_id' => $instrument_id,
                    'instrument_group' => $instrument_group,
                    'project_pi' => $info['project_pi'],
                    'instrument_name' => $this->get_instrument_name($instrument_id),
                    'booking_count' => $info['booking_count'],
                    'file_count' => $info['file_count'],
                    'project_color_class' => $project_color_class,
                    'instrument_color_class' => $inst_color_class
                ];
            }
        }
        return $booking_results;
    }

    public function format_no_bookings_for_jsgrid($no_booking_data)
    {
        $pt_array = [];
        foreach ($no_booking_data as $entry) {
            $pt = $entry['project_type'];
            $entry['project_type'] = strpos($pt, 'EMSL') === false ? ucwords(strtolower($pt), " ") : $pt;
            $pt_array[] = $entry;
        }
        return $pt_array;
    }
}
