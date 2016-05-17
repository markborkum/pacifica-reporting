<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Reporting Model                                                         */
/*                                                                             */
/*             functionality for summarizing upload and activity data.         */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Reporting_model extends CI_Model
{
    private $debug;
    public function __construct()
    {
        parent::__construct();
        define('TRANS_TABLE', 'transactions');
        define('FILES_TABLE', 'files');
        $this->load->database();
        $this->load->helper(array('item'));
        $this->debug = $this->config->item('debug_enabled');
    }

    public function detailed_transaction_list($transaction_list)
    {
        $eus_info = $this->get_info_for_transactions(array_combine($transaction_list, $transaction_list));

        $select_array = array(
            'f.transaction', 'sum(f.size) as total_file_size',
            'count(f.size) as total_file_count',
            'max(t.stime) as submission_time',
            'min(f.mtime) as earliest_modified_time',
            'max(f.mtime) as latest_modified_time',
        );

        $this->db->select($select_array);
        $this->db->from('files f')->join('transactions t', 'f.transaction = t.transaction');
        $this->db->where_in('f.transaction', $transaction_list);
        $this->db->group_by('f.transaction')->order_by('f.transaction desc');
        $query = $this->db->get();

        $file_info = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $file_info[$row['transaction']] = $row;
            }
        }

        foreach ($file_info as $transaction_id => $file_entry) {
            $eus_entry = $eus_info[$transaction_id];
            foreach ($eus_entry as $key => $value) {
                $file_entry[$key] = $value;
            }
            $results[$transaction_id] = $file_entry;
        }

        return $results;
    }


    // private function get_transactions_for_user_list($eus_user_id_list, $start_date, $end_date, $unfiltered = false)
    // {
    //     extract($this->canonicalize_date_range($start_date, $end_date));
    //     $transactions = array();
    //     $where_clause = array('stime >=' => $start_time_object->format('Y-m-d H:i:s'));
    //     if ($end_time) {
    //         $where_clause['stime <'] = $end_time_object->format('Y-m-d H:i:s');
    //     }
    //     $this->db->select(array('t.transaction', 't.stime as submit_time', 'ing.person_id'))->where($where_clause);
    //     $this->db->from('transactions as t')->join('ingest_state as ing', 't.transaction = ing.trans_id');
    //     $this->db->where('ing.message', 'completed')->where_in('ing.person_id', $eus_user_id_list);
    //     $this->db->order_by('t.transaction desc');
    //     $transaction_query = $this->db->get();
    //     if ($transaction_query && $transaction_query->num_rows() > 0) {
    //         foreach ($transaction_query->result() as $row) {
    //             $stime = date_create($row->submit_time);
    //             $transactions[$row->transaction] = array(
    //                 'submit_time' => $stime->format('Y-m-d H:i:s'),
    //             );
    //         }
    //     }
    //
    //     return $transactions;
    // }

    private function get_files_for_user_list($eus_user_id_list, $start_date, $end_date, $unfiltered = false, $time_basis)
    {
        extract($this->canonicalize_date_range($start_date, $end_date));
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
        $this->db->select(array(
            'f.item_id',
            't.transaction',
            'date_trunc(\'minute\',t.stime) as submit_time',
            'date_trunc(\'minute\',f.ctime) as create_time',
            'date_trunc(\'minute\',f.mtime) as modified_time',
            'size as size_bytes',
        ));
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
                    'submit_time' => $stime->format('Y-m-d H:i:s'),
                    'create_time' => $ctime->format('Y-m-d H:i:s'),
                    'modified_time' => $mtime->format('Y-m-d H:i:s'),
                    'transaction' => $row->transaction,
                    'size_bytes' => $row->size_bytes,
                );
            }
        }

        return $files;
    }
}
