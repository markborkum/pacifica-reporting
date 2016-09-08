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
        $this->load->database('default');

        $this->load->model('Group_info_model','gm');
        $this->load->library('EUS', '', 'eus');
        $this->load->helper(array('item','time'));
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


    public function summarize_uploads_by_user_list($eus_person_id_list, $start_date, $end_date, $make_day_graph, $time_basis = false)
    {
        $group_type = 'user';
        return $this->summarize_uploads_general($eus_person_id_list, $start_date, $end_date, $make_day_graph, $time_basis, $group_type);
    }

    public function summarize_uploads_by_proposal_list($eus_proposal_id_list, $start_date, $end_date, $make_day_graph, $time_basis = false)
    {
        $group_type = 'proposal';
        return $this->summarize_uploads_general($eus_proposal_id_list, $start_date, $end_date, $make_day_graph, $time_basis, $group_type);
    }

    public function summarize_uploads_by_instrument_list($eus_instrument_id_list, $start_date, $end_date, $make_day_graph, $time_basis = false)
    {
        $group_type = 'instrument';
        return $this->summarize_uploads_general($eus_instrument_id_list, $start_date, $end_date, $make_day_graph, $time_basis, $group_type);
    }

    public function summarize_uploads_general($id_list, $start_date, $end_date, $make_day_graph, $time_basis = false, $group_type)
    {
        extract($this->canonicalize_date_range($start_date, $end_date));
        $start_date_obj = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        $available_dates = $this->generate_available_dates($start_date_obj, $end_date_obj);

        if($group_type == 'instrument' || $group_type == 'proposal'){
            $group_list_retrieval_fn_name = "get_{$group_type}_group_list";
            $group_collection = array();
            foreach ($id_list as $item_id) {
                $new_collection = $this->gm->$group_list_retrieval_fn_name($item_id);
                $group_collection = $group_collection + $new_collection;
            }
            $group_list = array_keys($group_collection);
            if (empty($group_list)) {
                //no results returned for group list => bail out
            }
            $temp_totals = $this->get_summary_totals_from_group_list($group_list,$start_date_obj,$end_date_obj,$time_basis,$group_type);
            $temp_results = $this->get_per_day_totals_from_group_list($group_list,$start_date_obj,$end_date_obj,$time_basis,$group_type);
        }elseif($group_type == 'user'){
            $temp_totals = $this->get_summary_totals_from_user_list($id_list,$start_date_obj,$end_date_obj,$time_basis,$group_type);
            $temp_results = $this->get_per_day_totals_from_user_list($id_list,$start_date_obj,$end_date_obj,$time_basis,$group_type);
        }

        $this->results['day_graph']['by_date']['available_dates'] = $available_dates;
        $this->results['day_graph']['by_date'] = $this->temp_stats_to_output($temp_results['aggregate'], $available_dates);
        $this->results['summary_totals'] = $temp_results['totals'];
        $this->results['summary_totals']['upload_stats'] = $temp_totals['results'];

        return $this->results;
    }

    public function get_per_day_totals_from_user_list($eus_user_id_list, $start_date, $end_date, $time_basis)
    {

        $start_date_object = is_object($start_date) ? $start_date : new DateTime($start_date);
        $end_date_object = is_object($end_date) ? $end_date : new DateTime($end_date);
        $time_basis = str_replace("_time","_date",$time_basis);

        //formulate subquery
        $this->db->where_in('person_id',$eus_user_id_list)->where('step',5);
        $this->db->select('trans_id')->from('ingest_state')->distinct();
        $subquery = '"i"."transaction" in ('.$this->db->get_compiled_select().')';

        //formulate main query
        $where_clause = array("i.{$time_basis} >=" => $start_date_object->format('Y-m-d'));
        if ($end_date) {
            $where_clause["i.{$time_basis} <="] = $end_date_object->format('Y-m-d');
        }
        $select_array = array(
            'COUNT(item_id) as file_count',
            'SUM(size_in_bytes) as file_volume',
            $time_basis
        );

        $this->db->select($select_array)->from(ITEM_CACHE." i")->where('group_type','instrument')->where($subquery);
        $query = $this->db->group_by($time_basis)->order_by($time_basis)->get();
        $temp_results = array(
            'aggregate' => array(),
            'totals' => array(
                'total_file_count' => 0,
                'total_size_bytes' => 0,
                'total_size_string' => ""
            )
        );
        if($query && $query->num_rows() > 0){
            foreach($query->result() as $row){
                $temp_results['aggregate'][$row->{$time_basis}] = array(
                    'file_count' => $row->file_count + 0,
                    'file_volume' => $row->file_volume + 0
                );
                $temp_results['totals']['total_file_count'] += $row->file_count;
                $temp_results['totals']['total_size_bytes'] += $row->file_volume;
            }
            $temp_results['totals']['total_size_string'] = format_bytes($temp_results['totals']['total_size_bytes']);
        }
        $where_array = array(
            "{$time_basis} >=" => $start_date_object->format('Y-m-d'),
            "{$time_basis} <=" => $end_date_object->format('Y-m-d'),
            "group_type" => 'instrument'
        );

        $transactions_by_day = $this->get_user_transactions_by_date($eus_user_id_list, $where_array, $time_basis);
        // var_dump($transactions_by_day);
        foreach($transactions_by_day as $date_key => $transaction_list){
            $temp_results['aggregate'][$date_key]['transactions'] = $transaction_list;
        }

        return $temp_results;
    }

    private function get_summary_totals_from_user_list($eus_user_id_list,$start_date,$end_date,$time_basis,$group_type){
        $start_date_object = is_object($start_date) ? $start_date : new DateTime($start_date);
        $end_date_object = is_object($end_date) ? $end_date : new DateTime($end_date);
        $time_basis = str_replace("_time","_date",$time_basis);

        $select_array = array(
            "g.name as group_name",
            "MIN(g.type) as group_type",
            "i.group_type as category",
            "COUNT(i.item_id) as item_count"
        );
        $where_array = array(
            "{$time_basis} >=" => $start_date_object->format('Y-m-d'),
            "{$time_basis} <=" => $end_date_object->format('Y-m-d')
        );

        $this->db->select($select_array)->from(ITEM_CACHE." i")->join('groups g','g.group_id = i.group_id');
        // $this->db->where('"i"."transaction" in '.$subquery)->group_by('g.name,i.group_type')->order_by('i.group_type,g.name');
        $this->db->where_in('i.submitter',$eus_user_id_list)->group_by('g.name,i.group_type')->order_by('i.group_type,g.name');
        $this->db->where_in('group_type',array('instrument','proposal'));
        $this->db->where($where_array);
        $query = $this->db->get();
        $results = array(
            'proposal' => array(),
            'instrument' => array(),
            'user' => array()
        );
        // echo $this->db->last_query();
        $available_proposals = !$this->is_emsl_staff ? $this->eus->get_proposals_for_user($this->user_id) : false;

        if($query && $query->num_rows() > 0){
            foreach($query->result() as $row){
                if($row->category == 'instrument'){
                    $row->group_name = $row->group_type == 'omics.dms.instrument_id' ? $row->group_name : str_ireplace('instrument.', '', $row->group_type);
                    $results[$row->category][$row->group_name] = $row->item_count;
                }
                if($this->is_emsl_staff || ($row->category == 'proposal' && in_array($row->group_name,$available_proposals))){
                    $results[$row->category][$row->group_name] = $row->item_count;
                }elseif($row->category == 'proposal' && !in_array($row->group_name,$available_proposals)){
                    if(!isset($results[$row->category]['Other'])){
                        $results[$row->category]['Other'] = $row->item_count;
                    }else{
                        $results[$row->category]['Other'] += $row->item_count;
                    }
                }
            }
        }

        $select_array = array(
            'submitter',
            'COUNT(item_id) as item_count'
        );
        $this->db->select($select_array)->from(ITEM_CACHE." i");
        $this->db->where_in('i.submitter',$eus_user_id_list)->group_by('i.submitter')->order_by('i.submitter');
        $this->db->where_in('group_type',array('instrument'));
        $user_query = $this->db->get();
        // echo $this->db->last_query();
        if($user_query && $user_query->num_rows() > 0){
            foreach($user_query->result() as $row){
                if(!array_key_exists($row->submitter,$results['user'])){
                    $results['user'][$row->submitter] = 0;
                }
                $results['user'][$row->submitter] += $row->item_count;
            }
        }



        return array('results' => $results);
    }


    private function get_summary_totals_from_group_list($group_list,$start_date,$end_date,$time_basis,$group_type){
        $start_date_object = is_object($start_date) ? $start_date : new DateTime($start_date);
        $end_date_object = is_object($end_date) ? $end_date : new DateTime($end_date);
        $time_basis = str_replace("_time","_date",$time_basis);
        $subquery_where_array = array(
            "{$time_basis} >=" => $start_date_object->format('Y-m-d'),
            "{$time_basis} <=" => $end_date_object->format('Y-m-d'),
            "group_type" => $group_type
        );
        $this->db->where_in('group_id',$group_list)->where($subquery_where_array)->distinct();
        $this->db->select('transaction')->from(ITEM_CACHE);
        $subquery = 'transaction in ('.$this->db->get_compiled_select().')';
        // echo $subquery;
        $select_array = array(
            "g.name as group_name","MIN(g.type) as group_type","i.group_type as category","COUNT(i.item_id) as item_count"
        );

        $this->db->select($select_array)->from(ITEM_CACHE." i")->join('groups g','g.group_id = i.group_id');
        $this->db->where_in('g.group_id',$group_list)->where($subquery_where_array);
        $this->db->group_by('g.name,i.group_type')->order_by('i.group_type,g.name');
        $this->db->where_in('group_type',array('instrument','proposal'));
        $query = $this->db->get();
        // echo $this->db->last_query();
        $results = array(
            'proposal' => array(),
            'instrument' => array(),
            'user' => array()
        );
        $available_proposals = !$this->is_emsl_staff ? $this->eus->get_proposals_for_user($this->user_id) : false;

        if($query && $query->num_rows() > 0){
            foreach($query->result() as $row){
                if($row->category == 'instrument'){
                    $row->group_name = $row->group_type == 'omics.dms.instrument_id' ? $row->group_name : str_ireplace('instrument.', '', $row->group_type);
                    $results[$row->category][$row->group_name] = $row->item_count;
                }
                if($this->is_emsl_staff || ($row->category == 'proposal' && in_array($row->group_name,$available_proposals))){
                    $results[$row->category][$row->group_name] = $row->item_count;
                }elseif($row->category == 'proposal' && !in_array($row->group_name,$available_proposals)){
                    if(!isset($results[$row->category]['Other'])){
                        $results[$row->category]['Other'] = $row->item_count;
                    }else{
                        $results[$row->category]['Other'] += $row->item_count;
                    }
                }
            }
        }

        $txn_select_array = array(
            'i.transaction as txn',
            'i.submitter as sub'
        );

        $this->db->select($txn_select_array)->distinct();
        $this->db->where_in('i.group_id',$group_list)->where($subquery_where_array);
        $txn_query = $this->db->from(ITEM_CACHE." i")->get();

        $transaction_list = array();
        if($txn_query && $txn_query->num_rows() > 0){
            foreach($txn_query->result() as $row){
                $transaction_list[$row->txn] = $row->sub;
            }
        }

        $this->db->select(array('transaction txn','COUNT(item_id) item_count', 'SUM(size_in_bytes) total_size'))->where($subquery)->where('group_type',$group_type);
        $user_query = $this->db->from(ITEM_CACHE." i")->group_by('transaction')->get();


        if($user_query && $user_query->num_rows() > 0){
            foreach($user_query->result() as $row){
                $user = $transaction_list[$row->txn];
                if(!array_key_exists($user,$results['user'])){
                    $results['user'][$user] = 0;
                }
                $results['user'][$user] += $row->item_count;
            }
        }



        return array('results' => $results, 'transaction_submitter_list' => $transaction_list);
    }

    private function get_per_day_totals_from_group_list($group_list,$start_date,$end_date,$time_basis,$group_type){
        $start_date_object = is_object($start_date) ? $start_date : new DateTime($start_date);
        $end_date_object = is_object($end_date) ? $end_date : new DateTime($end_date);
        $time_basis = str_replace("_time","_date",$time_basis);

        $select_array = array(
            'COUNT(item_id) as file_count',
            'SUM(size_in_bytes) as file_volume',
            $time_basis
        );
        $where_array = array(
            "{$time_basis} >=" => $start_date_object->format('Y-m-d'),
            "{$time_basis} <=" => $end_date_object->format('Y-m-d'),
            "group_type" => $group_type
        );

        $this->db->select($select_array);
        $this->db->from(ITEM_CACHE)->where_in('group_id',$group_list);
        $this->db->where($where_array)->group_by($time_basis);
        $query = $this->db->order_by($time_basis)->get();
        $temp_results = array(
            'aggregate' => array(),
            'totals' => array(
                'total_file_count' => 0,
                'total_size_bytes' => 0,
                'total_size_string' => ""
            )
        );
        // echo $this->db->last_query();
        if($query && $query->num_rows() > 0){
            foreach($query->result() as $row){
                $temp_results['aggregate'][$row->{$time_basis}] = array(
                    'file_count' => $row->file_count + 0,
                    'file_volume' => $row->file_volume + 0
                );
                $temp_results['totals']['total_file_count'] += $row->file_count;
                $temp_results['totals']['total_size_bytes'] += $row->file_volume;
            }
            $temp_results['totals']['total_size_string'] = format_bytes($temp_results['totals']['total_size_bytes']);
        }
        $transactions_by_day = $this->get_transactions_by_date($group_list, $where_array, $time_basis);
        // var_dump($transactions_by_day);
        foreach($transactions_by_day as $date_key => $transaction_list){
            $temp_results['aggregate'][$date_key]['transactions'] = $transaction_list;
        }
        return $temp_results;
    }

    private function get_transactions_by_date($group_id_list, $where_array, $time_basis){
        $select_array = array(
            'transaction',$time_basis
        );
        $results = array();
        $this->db->select($select_array)->from(ITEM_CACHE);
        $this->db->where($where_array)->where_in('group_id',$group_id_list);
        $query = $this->db->order_by("{$time_basis}, transaction")->distinct()->get();
        // var_dump($query->result_array());
        if($query && $query->num_rows() > 0){
            foreach($query->result() as $row){
                $results[$row->{$time_basis}][] = $row->transaction;
            }
        }
        return $results;
    }

    private function get_user_transactions_by_date($eus_id_list, $where_array, $time_basis){
        $select_array = array(
            'transaction',$time_basis
        );
        $results = array();
        $this->db->select($select_array)->from(ITEM_CACHE);
        $this->db->where($where_array)->where_in('submitter',$eus_id_list);
        $query = $this->db->order_by("{$time_basis}, transaction")->distinct()->get();
        // var_dump($query->result_array());
        // echo $this->db->last_query();
        if($query && $query->num_rows() > 0){
            foreach($query->result() as $row){
                $results[$row->{$time_basis}][] = $row->transaction;
            }
        }
        return $results;
    }

    private function temp_stats_to_output($temp_results,$available_dates){
        if(!isset($file_count)){
            $file_count = array();
        }
        if(!isset($transactions_by_day)){
            $transactions_by_day = array();
        }
        foreach($available_dates as $date_key => $date_string){
            $date_timestamp = intval(strtotime($date_key)) * 1000;
            if(array_key_exists($date_key,$temp_results)){
                $file_count[$date_key] = $temp_results[$date_key]['file_count'];
                $file_volume[$date_key] = $temp_results[$date_key]['file_volume'];
                $transaction_count_array[$date_key] = array($date_timestamp,$temp_results[$date_key]['file_count']);
                $file_volume_array[$date_key] = array($date_timestamp,$temp_results[$date_key]['file_volume']);
                $transactions_by_day[$date_key] = $temp_results[$date_key]['transactions'];
            }else{
                // $file_count[$date_key] = 0;
                $file_volume[$date_key] = 0;
                $transaction_count_array[$date_key] = array($date_timestamp,intval(0));
                $file_volume_array[$date_key] = array($date_timestamp,intval(0));
                // $transactions_by_day[$date_key] = array();
            }
        }
        $return_array = array(
            'available_dates' => $available_dates,
            'file_count' => $file_count,
            'file_volume' => $file_volume,
            'transaction_count_array' => $transaction_count_array,
            'file_volume_array' => $file_volume_array,
            'transactions_by_day' => $transactions_by_day
        );
        return $return_array;
    }

    public function fix_time_range($time_range, $start_date, $end_date, $valid_date_range = false)
    {
        if (!empty($start_date) && !empty($end_date)) {
            $times = $this->canonicalize_date_range($start_date, $end_date);

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

        $times = $this->canonicalize_date_range($start_date, $end_date);

        return $times;
    }


    private function generate_available_dates($start_date, $end_date){
        $results = array();
        $start_date_object = is_object($start_date) ? $start_date : new DateTime($start_date);
        $end_date_object = is_object($end_date) ? $end_date : new DateTime($end_date);
        $current_date = clone $start_date_object;
        while($current_date->getTimestamp() <= $end_date_object->getTimestamp()){
            $date_key = $current_date->format('Y-m-d');
            $date_code = $current_date->format('D M j');
            $results[$date_key] = $date_code;
            $current_date->modify('+1 day');
        }
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

    // public function get_files_from_group_list($group_list, $start_time, $end_time, $time_basis)
    // {
    //     $times = array(
    //         'submit' => array(),
    //         'create' => array(),
    //         'modified' => array(),
    //     );
    //     echo "time_basis => {$time_basis}";
    //     switch ($time_basis) {
    //       case 'create_time':
    //         $time_field = 'created_date';
    //         break;
    //       case 'modified_time':
    //         $time_field = 'modified_date';
    //         break;
    //       case 'submit_time':
    //         $time_field = 'submit_date';
    //         break;
    //       default:
    //         $time_field = 'submit_date';
    //     }
    //     $files = array();
    //     $this->db->select(array(
    //         'item_id',
    //         'transaction',
    //         'submit_date as submit_time',
    //         'created_date as create_time',
    //         'modified_date as modified_time',
    //         'size_in_bytes as size_bytes',
    //     ));
    //
    //     $this->db->where("{$time_field} >=", $start_time);
    //     if ($end_time) {
    //         $this->db->where("{$time_field} <=", $end_time);
    //     }
    //
    //     $this->db->from('item_time_cache_by_transaction');
    //
    //     // $this->db->from('transactions as t')->join('files as f', 'f.transaction = t.transaction');
    //     // $this->db->join('group_items as gi', 'gi.item_id = f.item_id');
    //     $this->db->where_in('group_id', $group_list);
    //     $this->db->order_by('transaction desc')->distinct();
    //     $query = $this->db->get();
    //     // echo $this->db->last_query();
    //     if ($query && $query->num_rows() > 0) {
    //         foreach ($query->result() as $row) {
    //             $stime = date_create($row->submit_time);
    //             $ctime = date_create($row->create_time);
    //             $mtime = date_create($row->modified_time);
    //             $files[$row->item_id] = array(
    //                 'submit_time' => $stime->format('Y-m-d H:i:s'),
    //                 'create_time' => $ctime->format('Y-m-d H:i:s'),
    //                 'modified_time' => $mtime->format('Y-m-d H:i:s'),
    //                 'transaction' => $row->transaction,
    //                 'size_bytes' => $row->size_bytes,
    //             );
    //         }
    //     }
    //     // var_dump($files);
    //     return $files;
    // }

    // public function files_to_results($results, $make_day_graph = true, $start_date = false, $end_date = false, $time_basis)
    // {
    //     echo "time_basis => {$time_basis}\n";
    //     $transactions = array();
    //     $results['day_graph'] = array('by_date' => array());
    //     if (!empty($results['files'])) {
    //         foreach ($results['files'] as $item_id => $item_info) {
    //             $transactions[$item_info['transaction']] = $item_info['transaction'];
    //         }
    //         $results['transactions'] = $transactions;
    //         // var_dump($transactions);
    //         $this->benchmark->mark('files_to_results--get_txn_info_start');
    //         $results['transaction_info'] = $this->gm->get_info_for_transactions($transactions);
    //         $this->benchmark->mark('files_to_results--get_txn_info_end');
    //         $this->benchmark->mark('files_to_results--gen_file_summary_data_start');
    //         $results = $this->generate_file_summary_data($results);
    //         $this->benchmark->mark('files_to_results--gen_file_summary_data_end');
    //         $this->benchmark->mark('files_to_results--gen_day_graph_summary_start');
    //         $results['day_graph'] = $this->generate_day_graph_summary_files($results['files'], $start_date, $end_date, $time_basis);
    //         $this->benchmark->mark('files_to_results--gen_day_graph_summary_end');
    //         unset($results['files']);
    //     }
    //
    //     return $results;
    // }

    // private function generate_file_summary_data($results)
    // {
    //     $upload_stats = array(
    //         'proposal' => array(),
    //         'instrument' => array(),
    //         'user' => array(),
    //     );
    //     $total_file_size_summary = 0;
    //     $total_file_count_summary = 0;
    //     if (!empty($results['files'])) {
    //         $transaction_records = array(
    //         'most_files' => array('transaction_id' => false, 'file_count' => 0),
    //         'largest_upload' => array('transaction_id' => false, 'file_size' => 0),
    //         'most_prolific' => array('person_id' => false, 'file_size' => 0),
    //     );
    //         $file_list = $results['files'];
    //         $output_list = array();
    //         foreach ($file_list as $item_id => $info) {
    //             // if(array_key_exists($info['transaction'],$results['transaction_info'])){
    //             $tx_i = $results['transaction_info'][$info['transaction']];
    //             // }
    //             if(!array_key_exists('eus_person_id',$tx_i)){
    //                 $tx_i['eus_person_id'] = '0';
    //             }
    //
    //             $new_info = array(
    //                 'size_string' => format_bytes($info['size_bytes']),
    //                 'eus_instrument_id' => array_key_exists('eus_instrument_id', $tx_i) ? $tx_i['eus_instrument_id'] : 0,
    //                 'eus_person_id' => array_key_exists('eus_person_id', $tx_i) ? $tx_i['eus_person_id'] : '0',
    //             );
    //             $results['files'][$item_id] += $new_info;
    //             ++$total_file_count_summary;
    //             $total_file_size_summary += $info['size_bytes'];
    //
    //             if (!array_key_exists($tx_i['eus_person_id'], $upload_stats['user'])) {
    //                 $upload_stats['user'][$tx_i['eus_person_id']] = 1;
    //             } else {
    //                 $upload_stats['user'][$tx_i['eus_person_id']] += 1;
    //             }
    //             if (array_key_exists('eus_instrument_id', $tx_i)) {
    //                 if (!array_key_exists($tx_i['eus_instrument_id'], $upload_stats['instrument'])) {
    //                     $upload_stats['instrument'][$tx_i['eus_instrument_id']] = 1;
    //                 } else {
    //                     $upload_stats['instrument'][$tx_i['eus_instrument_id']] += 1;
    //                 }
    //             }
    //             if (array_key_exists('eus_proposal_id', $tx_i)) {
    //                 if (!array_key_exists($tx_i['eus_proposal_id'], $upload_stats['proposal'])) {
    //                     $upload_stats['proposal'][$tx_i['eus_proposal_id']] = 1;
    //                 } else {
    //                     $upload_stats['proposal'][$tx_i['eus_proposal_id']] += 1;
    //                 }
    //             }
    //         }
    //     }
    //
    //     $results['summary_totals'] = array(
    //         'upload_stats' => $upload_stats,
    //         'total_file_count' => $total_file_count_summary,
    //         'total_size_bytes' => $total_file_size_summary,
    //         'total_size_string' => format_bytes($total_file_size_summary),
    //     );
    //     unset($results['transaction_info']);
    //     // var_dump($results);
    //     return $results;
    // }

    // private function generate_day_graph_summary_files($file_list, $start_date = false, $end_date = false, $time_basis = 'submit_time')
    // {
    //     // echo "start_date = {$start_date}";
    //     echo "time_basis => {$time_basis}\n";
    //     if (is_string($start_date)) {
    //         $start_date = is_string($start_date) ? date_create($start_date) : false;
    //     }
    //     if (is_string($end_date)) {
    //         $end_date = strtotime($end_date) ? date_create($end_date) : false;
    //     }
    //
    //     if ($start_date) {
    //         $padded_start = clone $start_date;
    //         $padded_start->modify('-2 days');
    //         $start_date = clone $padded_start;
    //     }
    //     if ($end_date) {
    //         $padded_end = clone $end_date;
    //         $padded_end->modify('+2 days');
    //         $end_date = clone $padded_end;
    //     }
    //
    //     $date_list = array();
    //     $summary_list = array('by_date' => array());
    //
    //     foreach ($file_list as $item_id => $info) {
    //         $info['count'] = 1;
    //         $trans_id = $info['transaction'];
    //         if (strtotime($info[$time_basis])) {
    //             $ds = date_create($info[$time_basis]);
    //             $date_key = clone $ds;
    //             $date_key = $date_key->setTime(0, 0, 0)->format('Y-m-d');
    //             $time_key = clone $ds;
    //             $time_key = $time_key->setTime($time_key->format('H'), 0, 0)->format('H:i');
    //             if (!array_key_exists($date_key, $summary_list['by_date'])) {
    //                 $summary_list['by_date'][$date_key] = array(
    //                     'file_size' => $info['size_bytes'] + 0,
    //                     'file_count' => $info['count'] + 0,
    //                     'upload_count' => 1,
    //                     'transaction_list' => array($trans_id),
    //                 );
    //             } else {
    //                 $summary_list['by_date'][$date_key]['file_size'] += ($info['size_bytes'] + 0);
    //                 $summary_list['by_date'][$date_key]['file_count'] += ($info['count'] + 0);
    //                 $summary_list['by_date'][$date_key]['upload_count'] += 1;
    //                 $summary_list['by_date'][$date_key]['transaction_list'][] = $trans_id;
    //             }
    //         } else {
    //             continue;
    //         }
    //         $date_list[] = $ds;
    //         $summary_list['by_date'][$date_key]['transaction_list'] = array_unique($summary_list['by_date'][$date_key]['transaction_list']);
    //     }
    //     ksort($summary_list['by_date']);
    //     $summary_list['by_date'] = day_graph_to_series($summary_list, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'));
    //
    //     return $summary_list;
    // }

}
