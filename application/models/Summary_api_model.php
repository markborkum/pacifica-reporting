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
  *  Summary Model
  *
  *  The **Summary_model** class contains functionality for
  *  summarizing upload and activity data. It pulls data from
  *  both the MyEMSL and website_prefs databases
  *
  * @category CI_Model
  * @package  Pacifica-reporting
  * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
  *
  * @license BSD https://opensource.org/licenses/BSD-3-Clause
  * @link    http://github.com/EMSL-MSC/Pacifica-reporting

  * @uses   EUS EUS Database access library
  * @access public
  */
class Summary_api_model extends CI_Model
{
    public $results;


    /**
     *  Class constructor
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('PHPRequests');
        $this->metadata_url_base = $this->config->item('metadata_server_base_url');
        $this->policy_url_base = $this->config->item('policy_server_base_url');
        $this->load->helper(array('item', 'time'));
        $this->results = array(
                          'transactions'   => array(),
                          'time_range'     => array(
                                               'start_time' => '',
                                               'end_time'   => '',
                                              ),
                          'day_graph'      => array(
                                               'by_date' => array(
                                                             'available_dates'         => array(),
                                                             'file_count'              => array(),
                                                             'file_volume'             => array(),
                                                             'file_volume_array'       => array(),
                                                             'transaction_count_array' => array(),
                                                            ),
                                              ),
                          'summary_totals' => array(
                                               'upload_stats'      => array(
                                                                       'proposal'   => array(),
                                                                       'instrument' => array(),
                                                                       'user'       => array(),
                                                                      ),
                                               'total_file_count'  => 0,
                                               'total_size_bytes'  => 0,
                                               'total_size_string' => "",
                                              ),
                         );

    }//end __construct()

    /**
     * Pull the metadata details for a given set of transactions
     *
     * @param array $transaction_list list of transactions to detail
     *
     * @return array transaction details
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function detailed_transaction_list($transaction_list)
    {
        $transaction_url = "{$this->policy_url_base}/reporting/transaction_details/{$this->user_id}";
        // echo $transaction_url;
        $query = Requests::post(
            $transaction_url,
            array('Content-Type' => 'application/json'),
            json_encode($transaction_list)
        );
        $results = json_decode($query->body, TRUE);
        return $results;
    }


    /**
     * Generate summary results for a set of objects
     *
     * @param string  $group_type     type of objects to summarize
     * @param array   $id_list        object id's to include
     * @param string  $iso_start_date datestring
     * @param string  $iso_end_date   datestring
     * @param boolean $make_day_graph should we generate the graph?
     * @param string  $time_basis     which date to use?
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function summarize_uploads($group_type, $id_list, $iso_start_date, $iso_end_date, $make_day_graph, $time_basis)
    {
        //returns array that extracts to $start_date_object, $end_date_object, $start_time, $end_time
        extract(canonicalize_date_range($iso_start_date, $iso_end_date));

        $summary_info = $this->_generate_summary_totals(
            $group_type, $id_list, $start_time_object, $end_time_object, $time_basis
        );

        $this->results['summary_totals'] = $summary_info['summary_totals'];
        $this->results['day_graph']['by_date'] = $summary_info['day_graph']['by_date'];
        $this->results['day_graph']['by_date']['available_dates'] = generate_available_dates(
            $start_time, $end_time
        );
        $this->results['transaction_info'] = $summary_info['transaction_info'];

        return $this->results;
    }

    /**
     * Generate overall totals from the summary above
     *
     * @param string $group_type      type of objects to summarize
     * @param array  $id_list         object id's to include
     * @param string $start_date      datestring
     * @param string $end_date        datestring
     * @param string $time_basis_type which date to use?
     *
     * @return array summary totals ready for parsing
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _generate_summary_totals($group_type, $id_list, $start_date, $end_date, $time_basis_type)
    {
        $time_basis = str_replace('_time', '', $time_basis_type);
        $allowed_group_types = array('instrument', 'proposal', 'user');
        if(in_array($group_type, $allowed_group_types)) {
            $transaction_url = "{$this->policy_url_base}/reporting/transaction_summary/{$time_basis}/";
            $transaction_url .= "{$group_type}/".$start_date->format('Y-m-d H:i:s')."/";
            $transaction_url .= $end_date->format('Y-m-d H:i:s')."?user={$this->user_id}";
            $query = Requests::post(
                $transaction_url,
                array('Content-Type' => 'application/json'),
                json_encode($id_list)
            );
            $results = json_decode($query->body, TRUE);
        }
        $available_dates = $this->_generate_available_dates($start_date, $end_date);

        $results['day_graph']['by_date']['available_dates'] = $available_dates;
        $results['day_graph']['by_date'] = $this->_temp_stats_to_output($results['day_graph']['by_date']);
        $results['summary_totals']['total_size_string'] = format_bytes($results['summary_totals']['total_size_bytes']);
        return $results;
    }

    /**
     *  Format all the retrieved statistical information
     *  into a more easily-parseable array block that also
     *  fills in any missing dates with zeroed out data
     *
     *  @param array $temp_results sparse gathered results
     *
     *  @return array partial collection of stats
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _temp_stats_to_output($temp_results)
    {
        $available_dates = $temp_results['available_dates'];
        if(!isset($file_count)) {
            $file_count = array();
        }

        if(!isset($transactions_by_day)) {
            $transactions_by_day = array();
        }
        foreach($available_dates as $date_key => $date_string){
            $date_timestamp = (intval(strtotime($date_key)) * 1000);
            if(array_key_exists($date_key, $temp_results['file_count'])) {
                $file_count[$date_key]  = $temp_results['file_count'][$date_key];
                $file_volume[$date_key] = $temp_results['file_volume'][$date_key];
                $transaction_count_array[$date_key] = array(
                                                       $date_timestamp,
                                                       $temp_results['file_count'][$date_key],
                                                      );
                $file_volume_array[$date_key]       = array(
                                                       $date_timestamp,
                                                       $temp_results['file_volume'][$date_key],
                                                      );
                $transactions_by_day[$date_key]     = $temp_results['transactions'][$date_key];
            }else{
                $file_volume[$date_key] = 0;
                $transaction_count_array[$date_key] = array(
                                                       $date_timestamp,
                                                       intval(0),
                                                      );
                $file_volume_array[$date_key]       = array(
                                                       $date_timestamp,
                                                       intval(0),
                                                      );
            }//end if
        }//end foreach

        $return_array = array(
                         'available_dates'         => $available_dates,
                         'file_count'              => $file_count,
                         'file_volume'             => $file_volume,
                         'transaction_count_array' => $transaction_count_array,
                         'file_volume_array'       => $file_volume_array,
                         'transactions_by_day'     => $transactions_by_day,
                        );
        return $return_array;

    }//end _temp_stats_to_output()


    /**
     *  Given a starting and ending date, generate all of the
     *  available dates between them, inclusive
     *
     *  @param string $start_date starting date (YYYY-MM-DD)
     *  @param string $end_date   ending date (YYYY-MM-DD)
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _generate_available_dates($start_date, $end_date)
    {
        $results           = array();
        $start_date_object = is_object($start_date) ? $start_date : new DateTime($start_date);
        $end_date_object   = is_object($end_date) ? $end_date : new DateTime($end_date);
        $current_date      = clone $start_date_object;
        while($current_date->getTimestamp() <= $end_date_object->getTimestamp()){
            $date_key           = $current_date->format('Y-m-d');
            $date_code          = $current_date->format('D M j');
            $results[$date_key] = $date_code;
            $current_date->modify('+1 day');
        }

        return $results;

    }//end _generate_available_dates()


}
