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
 *  The **Reporting_model** class contains functionality
 *  for summarizing upload and activity data.
 *
 * @category CI_Model
 * @package  Pacifica-reporting
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 * @link    http://github.com/EMSL-MSC/Pacifica-reporting

 * @uses EUS EUS Database access library
 *
 * @access public
 */
class Reporting_model extends CI_Model
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
        define('TRANS_TABLE', 'transactions');
        define('FILES_TABLE', 'files');
        $this->load->database();
        $this->load->helper(array('item'));
        $this->load->library('EUS', '', 'eus');

    }//end __construct()

    /**
     *  Retrieve a detailed array of data for a given
     *  set of upload transactions.
     *
     * @param array $transaction_list set of transactions to detail
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function detailed_transaction_list($transaction_list)
    {
        $available_proposals = !$this->is_emsl_staff ? $this->eus->get_proposals_for_user($this->user_id) : FALSE;
        $eus_select_array    = array(
                                'i.transaction',
                                'i.group_type as category',
                                'MIN(g.name) as group_name',
                                'MIN(g.type) as group_type',
                               );
        $this->db->select($eus_select_array)->from(ITEM_CACHE." i");
        $this->db->join('groups g', 'g.group_id = i.group_id');
        $this->db->where_in('i.transaction', $transaction_list);
        $this->db->group_by('i.transaction, i.group_type')->order_by('i.group_type,i.transaction');
        $eus_query = $this->db->get();

        $eus_lookup = array();
        if($eus_query && $eus_query->num_rows() > 0) {
            foreach($eus_query->result() as $row){
                if($row->category == 'instrument') {
                    $inst_id = FALSE;
                    if($row->group_type == 'omics.dms.instrument_id') {
                        $id = intval($row->group_name);
                    }else if(stristr($row->group_type, 'instrument.')) {
                        $id = intval(str_ireplace('instrument.', '', $row->group_type));
                    }
                }else if($row->group_type == 'proposal') {
                    $id = $row->group_name;
                }

                $eus_lookup[$row->transaction][$row->category] = $id;
            }
        }

        $select_array = array(
                         'i.transaction as upload_id',
                         'max(i.submit_date) as upload_date',
                         'min(i.modified_date) as file_date_start',
                         'max(i.modified_date) as file_date_end',
                         'min(i.submitter) as uploaded_by_id',
                         'sum(i.size_in_bytes) as bundle_size',
                         'count(i.item_id) as file_count',
                         'min(t.stime) as upload_datetime',
                        );
        $this->db->select($select_array)->group_by('i.transaction');
        $this->db->where('group_type', 'instrument');
        $this->db->from(ITEM_CACHE." i")->where_in('i.transaction', $transaction_list);
        $this->db->join('transactions t', 't.transaction = i.transaction');
        $query = $this->db->get();
        // echo $this->db->last_query();
        $results = array();
        if($query && $query->num_rows() > 0) {
            foreach($query->result_array() as $row){
                $row['proposal_id']   = array_key_exists('proposal', $eus_lookup[$row['upload_id']]) ? $eus_lookup[$row['upload_id']]['proposal'] : "Unknown";
                $row['instrument_id'] = array_key_exists('instrument', $eus_lookup[$row['upload_id']]) ? $eus_lookup[$row['upload_id']]['instrument'] : "Unknown";
                if($this->is_emsl_staff || in_array($row['proposal_id'], $available_proposals)) {
                    $results[$row['upload_id']] = $row;
                }
            }
        }

        return $results;

    }//end detailed_transaction_list()

    /**
     *  [_get_files_for_user_list description]
     *
     * @param array   $eus_user_id_list list of users
     * @param string  $start_date       [description]
     * @param string  $end_date         [description]
     * @param string  $time_basis       [description]
     * @param boolean $unfiltered       [description]
     *
     * @return array
     *
     * @deprecated
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _get_files_for_user_list($eus_user_id_list, $start_date, $end_date, $time_basis, $unfiltered = FALSE)
    {
        extract(canonicalize_date_range($start_date, $end_date));
        switch ($time_basis) {
        case 'create_time':
            $time_field = 'f.ctime';
            break;
        case 'modified_time':
            $time_field = 'f.mtime';
            break;
        case 'submit_time':
            $time_field = 't.stime';
            break;
        default:
            $time_field = 't.stime';
        }

        $files = array();
        if ($end_time) {
            $this->db->where("{$time_field} < ", $end_time_object->format('Y-m-d H:i:s'));
        }

        $this->db->select(
            array(
             'f.item_id',
             't.transaction',
             'date_trunc(\'minute\',t.stime) as submit_time',
             'date_trunc(\'minute\',f.ctime) as create_time',
             'date_trunc(\'minute\',f.mtime) as modified_time',
             'size as size_bytes',
            )
        );
        $this->db->from('transactions as t');
        $this->db->join('ingest_state as ing', 't.transaction = ing.trans_id');
        $this->db->join('files as f', 'f.transaction = t.transaction');
        $this->db->where('ing.message', 'completed')->where_in('ing.person_id', $eus_user_id_list);
        $this->db->order_by('t.transaction desc');
        $query = $this->db->get();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $stime = date_create($row->submit_time);
                $ctime = date_create($row->create_time);
                $mtime = date_create($row->modified_time);
                $files[$row->item_id] = array(
                                         'submit_time'   => $stime->format('Y-m-d H:i:s'),
                                         'create_time'   => $ctime->format('Y-m-d H:i:s'),
                                         'modified_time' => $mtime->format('Y-m-d H:i:s'),
                                         'transaction'   => $row->transaction,
                                         'size_bytes'    => $row->size_bytes,
                                        );
            }
        }

        return $files;

    }//end _get_files_for_user_list()


}//end class
