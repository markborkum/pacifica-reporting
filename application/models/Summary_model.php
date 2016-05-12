<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Summary Model                                                         */
/*                                                                             */
/*             functionality for summarizing upload and activity data.         */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Summary_model extends CI_Model
{
    private $debug;
    private $results;
    public function __construct()
    {
        parent::__construct();
        define('ITEM_CACHE', 'item_time_cache_by_transaction');
        $this->load->database();
        $this->load->model('Group_info_model','gm');
        $this->load->helper(array('item'));
        $this->debug = $this->config->item('debug_enabled');
        $this->results = array(
            'transactions' => array(),
            'time_range' => array('start_time' => '', 'end_time' => ''),
            'day_graph' => array(
                'by_date' => array(
                    'available_dates' => array(),
                    'file_count' => array(),
                    'file_volume' => array(),
                    'file_volume_array' => array(),
                    'transaction_count_array' => array()
                )
            ),
            'summary_totals' => array(
                'upload_stats' => array(
                    'proposal' => array(),
                    'instrument' => array(),
                    'user' => array()
                ),
                'total_file_count' => 0,
                'total_size_bytes' => 0,
                'total_size_string' => ""
            )
        );

    }


    public function summarize_uploads_by_user_list($eus_person_id_list, $start_date,
    $end_date, $make_day_graph, $time_basis = false)
    {
        extract($this->canonicalize_date_range($start_date, $end_date));

        $results = array(
          'transactions' => array(),
          'time_range' => array('start_time' => $start_time, 'end_time' => $end_time),
        );

        $results['files'] = $this->get_files_for_user_list($eus_person_id_list, $start_time, $end_time, true, $time_basis);
        $results = $this->files_to_results($results, $make_day_graph, $start_time, $end_time, $time_basis);

        return $results;
    }

    public function summarize_uploads_by_proposal_list($eus_proposal_id_list, $start_date, $end_date, $make_day_graph, $time_basis = false)
    {
        extract($this->canonicalize_date_range($start_date, $end_date));

        $results = array(
            'transactions' => array(),
            'time_range' => array('start_time' => $start_time, 'end_time' => $end_time),
        );

        $group_collection = array();
        foreach ($eus_proposal_id_list as $eus_proposal_id) {
            $new_collection = $this->get_proposal_group_list($eus_proposal_id);
            $group_collection = $group_collection + $new_collection;
        }

        $group_list = array_keys($group_collection);

        $results['files'] = $this->get_files_from_group_list($group_list, $start_time, $end_time, $time_basis);
        $results = $this->files_to_results($results, $make_day_graph, $start_time, $end_time, $time_basis);

        return $results;
    }

    public function summarize_uploads_by_instrument_list($eus_instrument_id_list, $start_date, $end_date, $make_day_graph, $time_basis = false)
    {
        extract($this->canonicalize_date_range($start_date, $end_date));
        $group_collection = array();
        foreach ($eus_instrument_id_list as $eus_instrument_id) {
            $new_collection = $this->gm->get_instrument_group_list($eus_instrument_id);
            $group_collection = $group_collection + $new_collection;
        }

        $start_date_obj = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);

        $group_list = array_keys($group_collection);
        if (empty($group_list)) {
            //no results returned for group list => bail out
        }
        $time_basis = str_replace("_time","_date",$time_basis);

        $select_array = array(
            'COUNT(item_id) as file_count',
            'SUM(size_in_bytes) as file_volume',
            $time_basis
        );
        $where_array = array(
            "{$time_basis} >=" => $start_date_obj->format('Y-m-d'),
            "{$time_basis} <=" => $end_date_obj->format('Y-m-d'),
            "group_type" => 'instrument'
        );

        $this->db->select($select_array);
        $this->db->from(ITEM_CACHE)->where_in('group_id',$group_list);
        $this->db->where($where_array)->group_by($time_basis);
        $query = $this->db->order_by($time_basis)->get();

        $temp_results = array();
        if($query && $query->num_rows() > 0){
            foreach($query->result() as $row){
                $temp_results[$row->{$time_basis}] = array(
                    $time_basis => new DateTime($row->{$time_basis}),
                    'file_count' => $row->file_count,
                    'file_volume' => $row->file_volume
                );
            }
        }
        ksort($temp_results);

        $first_entry = array_slice($temp_results, 0,1);
        $last_entry = array_slice($temp_results, -1,1);
        $first_entry = array_pop($first_entry);
        $last_entry = array_pop($last_entry);

        $first_entry_object = clone $first_entry[$time_basis];
        $last_entry_object = clone $last_entry[$time_basis];

        $current_date = clone $first_entry_object;
        while($current_date->getTimestamp() <= $last_entry_object->getTimestamp()){


            $current_date->modify("+1 day");
        }


        exit();
        $next_date = false;
        foreach($temp_results as $date_key => $item){
            if($next_date == false || $next_date->format('Y-m-d') == $item[$time_basis]->format('Y-m-d')){
                $this->results['day_graph']['by_date']['available_dates'][$date_key] = $item[$time_basis]->format('D M j');
                $next_date = clone $item[$time_basis];
            }else{
                // $
            }

            $next_date->modify('+1 day');
        }


        exit();

        $this->benchmark->mark('summarizer-get_files_from_group_list_start');
        $results['files'] = $this->get_files_from_group_list($group_list, $start_time, $end_time, $time_basis);
        $this->benchmark->mark('summarizer-get_files_from_group_list_end');

        $this->benchmark->mark('summarizer-files_to_results_start');
        $results = $this->files_to_results($results, $make_day_graph, $start_time, $end_time, $time_basis);
        var_dump($results);
        $this->benchmark->mark('summarizer-files_to_results_end');

        return $results;
    }

    public function canonicalize_date_range($start_date, $end_date)
    {
        $start_date = $this->convert_short_date($start_date);
        $end_date = $this->convert_short_date($end_date, 'end');
        $start_time = strtotime($start_date) ? date_create($start_date)->setTime(0, 0, 0) : date_create('1983-01-01 00:00:00');
        $end_time = strtotime($end_date) ? date_create($end_date) : new DateTime();
        $end_time->setTime(23, 59, 59);

        if ($end_time < $start_time && !empty($end_time)) {
            $temp_start = $end_time ? clone $end_time : false;
            $end_time = clone $start_time;
            $start_time = $temp_start;
        }

        return array(
            'start_time_object' => $start_time, 'end_time_object' => $end_time,
            'start_time' => $start_time->format('Y-m-d H:i:s'),
            'end_time' => $end_time ? $end_time->format('Y-m-d H:i:s') : false,
        );
    }

    private function convert_short_date($date_string, $type = 'start')
    {
        if (preg_match('/(\d{4})$/', $date_string, $matches)) {
            $date_string = $type == 'start' ? "{$matches[1]}-01-01" : "{$matches[1]}-12-31";
        } elseif (preg_match('/^(\d{4})-(\d{1,2})$/', $date_string, $matches)) {
            $date_string = $type == 'start' ? "{$matches[1]}-{$matches[2]}-01" : "{$matches[1]}-{$matches[2]}-31";
        }

        return $date_string;
    }

    public function get_files_from_group_list($group_list, $start_time, $end_time, $time_basis)
    {
        $times = array(
            'submit' => array(),
            'create' => array(),
            'modified' => array(),
        );
        switch ($time_basis) {
          case 'create_time':
            $time_field = 'created_date';
            break;
          case 'modified_time':
            $time_field = 'modified_date';
            break;
          case 'submit_time':
            $time_field = 'submit_date';
            break;
          default:
            $time_field = 'submit_date';
        }
        $files = array();
        $this->db->select(array(
            'item_id',
            'transaction',
            'submit_date as submit_time',
            'created_date as create_time',
            'modified_date as modified_time',
            'size_in_bytes as size_bytes',
        ));

        $this->db->where("{$time_field} >=", $start_time);
        if ($end_time) {
            $this->db->where("{$time_field} <", $end_time);
        }

        $this->db->from('item_time_cache_by_transaction');

        // $this->db->from('transactions as t')->join('files as f', 'f.transaction = t.transaction');
        // $this->db->join('group_items as gi', 'gi.item_id = f.item_id');
        $this->db->where_in('group_id', $group_list);
        $this->db->order_by('transaction desc')->distinct();
        $query = $this->db->get();
        // echo $this->db->last_query();
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
        // var_dump($files);
        return $files;
    }

    public function files_to_results($results, $make_day_graph = true, $start_date = false, $end_date = false, $time_basis)
    {
        $transactions = array();
        $results['day_graph'] = array('by_date' => array());
        if (!empty($results['files'])) {
            foreach ($results['files'] as $item_id => $item_info) {
                $transactions[$item_info['transaction']] = $item_info['transaction'];
            }
            $results['transactions'] = $transactions;
            $this->benchmark->mark('files_to_results--get_txn_info_start');
            $results['transaction_info'] = $this->gm->get_info_for_transactions($transactions);
            $this->benchmark->mark('files_to_results--get_txn_info_end');
            $this->benchmark->mark('files_to_results--gen_file_summary_data_start');
            $results = $this->generate_file_summary_data($results);
            $this->benchmark->mark('files_to_results--gen_file_summary_data_end');
            $this->benchmark->mark('files_to_results--gen_day_graph_summary_start');
            $results['day_graph'] = $this->generate_day_graph_summary_files($results['files'], $start_date, $end_date, $time_basis);
            $this->benchmark->mark('files_to_results--gen_day_graph_summary_end');
            unset($results['files']);
        }

        return $results;
    }

    private function generate_file_summary_data($results)
    {
        $upload_stats = array(
            'proposal' => array(),
            'instrument' => array(),
            'user' => array(),
        );
        $total_file_size_summary = 0;
        $total_file_count_summary = 0;
        if (!empty($results['files'])) {
            $transaction_records = array(
            'most_files' => array('transaction_id' => false, 'file_count' => 0),
            'largest_upload' => array('transaction_id' => false, 'file_size' => 0),
            'most_prolific' => array('person_id' => false, 'file_size' => 0),
        );
            $file_list = $results['files'];
            $output_list = array();
            foreach ($file_list as $item_id => $info) {
                // if(array_key_exists($info['transaction'],$results['transaction_info'])){
                $tx_i = $results['transaction_info'][$info['transaction']];
                // }
                if(!array_key_exists('eus_person_id',$tx_i)){
                    $tx_i['eus_person_id'] = '0';
                }

                $new_info = array(
                    'size_string' => format_bytes($info['size_bytes']),
                    'eus_instrument_id' => array_key_exists('eus_instrument_id', $tx_i) ? $tx_i['eus_instrument_id'] : 0,
                    'eus_person_id' => array_key_exists('eus_person_id', $tx_i) ? $tx_i['eus_person_id'] : '0',
                );
                $results['files'][$item_id] += $new_info;
                ++$total_file_count_summary;
                $total_file_size_summary += $info['size_bytes'];

                if (!array_key_exists($tx_i['eus_person_id'], $upload_stats['user'])) {
                    $upload_stats['user'][$tx_i['eus_person_id']] = 1;
                } else {
                    $upload_stats['user'][$tx_i['eus_person_id']] += 1;
                }
                if (array_key_exists('eus_instrument_id', $tx_i)) {
                    if (!array_key_exists($tx_i['eus_instrument_id'], $upload_stats['instrument'])) {
                        $upload_stats['instrument'][$tx_i['eus_instrument_id']] = 1;
                    } else {
                        $upload_stats['instrument'][$tx_i['eus_instrument_id']] += 1;
                    }
                }
                if (array_key_exists('eus_proposal_id', $tx_i)) {
                    if (!array_key_exists($tx_i['eus_proposal_id'], $upload_stats['proposal'])) {
                        $upload_stats['proposal'][$tx_i['eus_proposal_id']] = 1;
                    } else {
                        $upload_stats['proposal'][$tx_i['eus_proposal_id']] += 1;
                    }
                }
            }
        }

        $results['summary_totals'] = array(
            'upload_stats' => $upload_stats,
            'total_file_count' => $total_file_count_summary,
            'total_size_bytes' => $total_file_size_summary,
            'total_size_string' => format_bytes($total_file_size_summary),
        );
        unset($results['transaction_info']);
        // var_dump($results);
        return $results;
    }

    private function generate_day_graph_summary_files($file_list, $start_date = false, $end_date = false, $time_basis = 'submit_time')
    {
        // echo "start_date = {$start_date}";
        if (is_string($start_date)) {
            $start_date = is_string($start_date) ? date_create($start_date) : false;
        }
        if (is_string($end_date)) {
            $end_date = strtotime($end_date) ? date_create($end_date) : false;
        }

        if ($start_date) {
            $padded_start = clone $start_date;
            $padded_start->modify('-2 days');
            $start_date = clone $padded_start;
        }
        if ($end_date) {
            $padded_end = clone $end_date;
            $padded_end->modify('+2 days');
            $end_date = clone $padded_end;
        }

        $date_list = array();
        $summary_list = array('by_date' => array());

        foreach ($file_list as $item_id => $info) {
            $info['count'] = 1;
            $trans_id = $info['transaction'];
            if (strtotime($info[$time_basis])) {
                $ds = date_create($info[$time_basis]);
                $date_key = clone $ds;
                $date_key = $date_key->setTime(0, 0, 0)->format('Y-m-d');
                $time_key = clone $ds;
                $time_key = $time_key->setTime($time_key->format('H'), 0, 0)->format('H:i');
                if (!array_key_exists($date_key, $summary_list['by_date'])) {
                    $summary_list['by_date'][$date_key] = array(
                        'file_size' => $info['size_bytes'] + 0,
                        'file_count' => $info['count'] + 0,
                        'upload_count' => 1,
                        'transaction_list' => array($trans_id),
                    );
                } else {
                    $summary_list['by_date'][$date_key]['file_size'] += ($info['size_bytes'] + 0);
                    $summary_list['by_date'][$date_key]['file_count'] += ($info['count'] + 0);
                    $summary_list['by_date'][$date_key]['upload_count'] += 1;
                    $summary_list['by_date'][$date_key]['transaction_list'][] = $trans_id;
                }
            } else {
                continue;
            }
            $date_list[] = $ds;
            $summary_list['by_date'][$date_key]['transaction_list'] = array_unique($summary_list['by_date'][$date_key]['transaction_list']);
        }
        ksort($summary_list['by_date']);
        $summary_list['by_date'] = day_graph_to_series($summary_list, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'));

        return $summary_list;
    }

}
