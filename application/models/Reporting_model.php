<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Reporting Model                                                         */
/*                                                                             */
/*             functionality for summarizing upload and activity data.         */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Reporting_model extends CI_Model {
  
  function __construct(){
    parent::__construct();
    define("TRANS_TABLE", 'transactions');
    define("FILES_TABLE", 'files');
    $this->load->database();
    $this->load->helper(array('item'));

  }
  
  
  
  
  function summarize_uploads_by_instrument($eus_instrument_id, $start_date, $end_date = false){
    //canonicalize start and end times (yields $start_time & $end_time)
    extract($this->canonicalize_date_range($start_date, $end_date));
    
    $results = array('transactions' => array());
    
    //get instrument group_id list
    $group_collection = $this->get_instrument_group_list($eus_instrument_id);
    $group_list = array_keys($group_collection);
    
    //get transactions for time period & instrument combination
    $where_clause = array('stime >=' => $start_time->format('Y-m-d H:i:s'));
    if($end_time){
      $where_clause['stime <'] = $end_time->format('Y-m-d H:i:s');
    }
    $this->db->select(array('t.transaction','gi.group_id','t.stime as submit_time'))->where($where_clause);
    $this->db->from('transactions as t')->join('files as f','f.transaction = t.transaction');
    $this->db->join('group_items as gi','gi.item_id = f.item_id');
    $this->db->where_in('gi.group_id',$group_list);
    $this->db->order_by('t.transaction desc');
    $transaction_query = $this->db->get();
    
    if($transaction_query && $transaction_query->num_rows()>0){
      foreach($transaction_query->result() as $row){
        $stime = date_create($row->submit_time);
        $results['transactions'][$row->transaction] = array(
          'submit_time' => $stime->format('Y-m-d H:i:s')
        );
      }
    }
    
    
    $this->db->where_in('transaction', array_keys($results['transactions']));
    $this->db->group_by('transaction');
    $this->db->select(
      array(
        'transaction',
        'SUM(size) as file_size_in_bytes',
        'COUNT(transaction) as file_count'
        )
      );
    $total_file_size_summary = 0;
    $total_file_count_summary = 0;  
    
    $file_info_query = $this->db->get(FILES_TABLE);
    if($file_info_query && $file_info_query->num_rows() > 0){
      foreach($file_info_query->result() as $file_row){
        if(array_key_exists($file_row->transaction, $results['transactions'])){
          $results['transactions'][$file_row->transaction]['size_bytes'] = $file_row->file_size_in_bytes;
          $results['transactions'][$file_row->transaction]['size_string'] = format_bytes($file_row->file_size_in_bytes);
          $results['transactions'][$file_row->transaction]['count'] = $file_row->file_count;
          $total_file_count_summary += $file_row->file_count;
          $total_file_size_summary += $file_row->file_size_in_bytes;
        }
      }
    }
    $results['summary_totals'] = array(
      'total_file_count' => $total_file_count_summary,
      'total_size_bytes' => $total_file_size_summary,
      'total_size_string' => format_bytes($total_file_size_summary)
    );
    
    $day_graph = $this->generate_day_graph_summary($results['transactions'], '2015-07-15','2015-11-01');
    
    return array($eus_instrument_id => $results);
  }
  
  
  
  
  function summarize_uploads_by_user($eus_person_id){
    
  }
  
  
  
  
  function summarize_uploads_by_proposal($eus_proposal_id){
    
  }
  
  
  /* 
   * $transaction list is a list of transaction id's, each with an array containing submit time
   * size_bytes, size_string (opt), and count
   * */
  private function generate_day_graph_summary($transaction_list,$start_date = false,$end_date = false){
    $start_date = strtotime($start_date) ? date_create($start_date) : false;
    $end_date = strtotime($end_date) ? date_create($end_date) : false;
    $date_list = array();
    $summary_list = array('by_date' => array(), 'by_hour_list' => array());
    foreach($transaction_list as $trans_id => $info){
      if(strtotime($info['submit_time'])){
        $ds = date_create($info['submit_time']);
        $date_key = clone $ds;
        $date_key = $date_key->setTime(0,0,0)->format('Y-m-d');
        $time_key = clone $ds;
        $time_key = $time_key->setTime($time_key->format('H'),0,0)->format('H:i');
        if(!array_key_exists($date_key,$summary_list['by_date'])){
          $summary_list['by_date'][$date_key] = array('file_size' => $info['size_bytes'] + 0, 'file_count' => $info['count'] + 0);
        }else{
          $summary_list['by_date'][$date_key]['file_size'] += ($info['size_bytes'] + 0);
          $summary_list['by_date'][$date_key]['file_count'] += ($info['count'] + 0);
        }
        $summary_list['by_hour_list'][$time_key]['sizes'][] = $info['size_bytes'] + 0;
        $summary_list['by_hour_list'][$time_key]['counts'][] = $info['count'] + 0;
      }else{
        continue;
      }
      $date_list[] = $ds;
    }
    ksort($summary_list['by_hour_list']);
    foreach($summary_list['by_hour_list'] as $time_key => $info){
      $trans_count = sizeof($info['sizes']);
      $summary_list['by_hour'][$time_key] = array(
        'avg_size_bytes' => floor(array_sum($info['sizes']) / $trans_count),
        'total_size_bytes' => array_sum($info['sizes']),
        'avg_file_count' => floor(array_sum($info['counts']) / $trans_count),
        'total_file_count' => array_sum($info['counts'])
      );
    }
    unset($summary_list['by_hour_list']);
    
    echo "<pre>";
    var_dump($summary_list['by_hour']);
    echo "</pre>";
    
    // var_dump($summary_list);
    sort($date_list);
    $dl = $date_list;
    $first_date = $start_date ? $start_date : array_shift($dl);
    $last_date = $end_date ? $end_date : array_pop($dl);
    $first_date->setTime(0,0,0);
    $last_date->setTime(11,59,59);
    
    
    //ok, got the earliest and latest dates we care about, now to partition the transactions...
    //build the full range of dates...
    $date_collection = array();
    $new_date = clone $first_date;
    while($new_date->getTimestamp() <= $last_date->getTimestamp()){
      $date_collection[$new_date->format('Y-m-d')] = array(
        'file_size' => 0,
        'file_count' => 0
      );
      $new_date->modify('+1 day');
    }
    
    foreach($transaction_list as $trans_id => $info)
    
    
    
    echo "<pre>";
    var_dump($date_collection);
    echo "</pre>";
    
  }
  
  
  private function canonicalize_date_range($start_date, $end_date){
    //both start and end times are filled in and valid
    $start_time = strtotime($start_date) ? date_create($start_date) : date_create('00:00:00');
    $end_time = strtotime($end_date) ? date_create($end_date) : false;
    
    if($end_time < $start_time && !empty($end_time)){
      //flipped??
      $temp_start = strtotime($end_time) ? clone($end_time) : false;
      $end_time = clone($start_time);
      $start_time = $temp_start;
    }
    
    return array('start_time' => $start_time, 'end_time' => $end_time);
  }
  
  
  private function get_instrument_group_list($inst_id_filter = ""){
    $DB_myemsl = $this->load->database('default',TRUE);
    
    $DB_myemsl->select(array('group_id','name','type'));
    // if(!empty($inst_id_filter) && intval($inst_id_filter) >= 0){
      // $where_clause = "(type = 'omics.dms.instrument' or type ilike 'instrument.%') and name not in ('foo') and (group_id = '{$inst_id_filter}' or type like 'Instrument.{$inst_id_filter}')";
    // }else{
      $where_clause = "(type = 'omics.dms.instrument_id' or type ilike 'instrument.%') and name not in ('foo')";
    // }
    
    $DB_myemsl->where($where_clause);
    $query = $DB_myemsl->order_by('name')->get('groups');
    $results_by_group = array();
    $results_by_inst_id = array();
    if($query && $query->num_rows() > 0){
      foreach($query->result() as $row){
        if($row->type == 'omics.dms.instrument_id'){
          $inst_id = intval($row->name);
        }elseif(strpos($row->type, 'Instrument.') >= 0){
          $inst_id = intval(str_replace('Instrument.','',$row->type));
        }else{
          continue;
        }
        $results_by_inst_id[$inst_id][$row->group_id] = $row->name;
      }
    }
    if(!empty($inst_id_filter) && is_numeric($inst_id_filter)){
      $results = $results_by_inst_id[$inst_id_filter];
    }else{
      $results = $results_by_inst_id;
    }
    
    return $results;
  }
  
  
  
  
  
}
?>