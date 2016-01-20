<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Reporting Model                                                         */
/*                                                                             */
/*             functionality for summarizing upload and activity data.         */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Reporting_model extends CI_Model {
  
  protected $selected_objects;
  
  function __construct(){
    parent::__construct();
    define("TRANS_TABLE", 'transactions');
    define("FILES_TABLE", 'files');
    $this->load->database();
    $this->load->helper(array('item'));
    
    $this->selected_objects = $this->get_selected_objects($this->user_id);
  }
  
  
  
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 *    Publicly available API calls 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
  function summarize_uploads_by_user($eus_person_id,$start_date, $end_date,$unfiltered = false){
    //get user transactions
    $results = array('transactions' => array());
    $results['transactions'] = $this->get_transactions_for_user($eus_person_id, $start_date, $end_date, $unfiltered);
    
    //use the transaction list to retrieve summary info
    if(!empty($results['transactions'])){
      $results['transaction_info'] = $this->get_info_for_transactions($results['transactions']);
      $results = $this->generate_summary_data($results);
      $results['day_graph'] = $this->generate_day_graph_summary($results['transactions']);
    }else{
      return array();
    }
    return array($eus_person_id => $results);
  }
  
  
  
  
  function summarize_uploads_by_proposal($eus_proposal_id, $start_date, $end_date){
    //canonicalize start and end times (yields $start_time & $end_time)
    //extract($this->canonicalize_date_range($start_date, $end_date));
    
    $results = array('transactions' => array());
    
    //get proposal_group_list
    $group_collection = $this->get_proposal_group_list($eus_proposal_id);
    $group_list = array_keys($group_collection);
        
    //get transactions for time period & group_list
    $results['transactions'] = $this->get_transactions_from_group_list($group_list, $start_date, $end_date);

    //use the transaction list to retrieve summary info
    if(!empty($results['transactions'])){
      $results['transaction_info'] = $this->get_info_for_transactions($results['transactions']);      
      $results = $this->generate_summary_data($results);
      $results['day_graph'] = $this->generate_day_graph_summary($results['transactions']);
    }else{
      return array();
    }
    return array($eus_proposal_id => $results);
  }
    
 
 
  
  function summarize_uploads_by_instrument($eus_instrument_id, $start_date, $end_date = false){
    //get instrument group_id list
    $group_collection = $this->get_instrument_group_list($eus_instrument_id);
    $group_list = array_keys($group_collection);
    
    if(empty($group_list)){
      //no results returned for group list => bail out
    }
    
    //get transactions for time period & group_list
    $transactions = $this->get_transactions_from_group_list($group_list, $start_date, $end_date);
        
    //use the transaction list to retrieve summary info
    if(!empty($results['transactions'])){
      $results['transaction_info'] = $this->get_info_for_transactions($results['transactions']);      
      $results = $this->generate_summary_data($results);
      $day_graph = $this->generate_day_graph_summary($transactions);
    }else{
      return array();
    }
    return array($eus_instrument_id => $results);
  }
  
  
  

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 *    Private functionality for behind the scenes data retrieval 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
  private function get_transactions_from_group_list($group_list, $start_date, $end_date){
    extract($this->canonicalize_date_range($start_date, $end_date));
    $transactions = array();
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
        $transactions[$row->transaction] = array(
          'submit_time' => $stime->format('Y-m-d H:i:s')
        );
      }
    }
    return $transactions;
  }


  private function get_transactions_for_user($eus_user_id, $start_date, $end_date, $unfiltered = false){
    extract($this->canonicalize_date_range($start_date, $end_date));
    $transactions = array();
    $where_clause = array('stime >=' => $start_time->format('Y-m-d H:i:s'));
    if($end_time){
      $where_clause['stime <'] = $end_time->format('Y-m-d H:i:s');
    }
    $this->db->select(array('t.transaction','t.stime as submit_time','ing.person_id'))->where($where_clause);
    $this->db->from('transactions as t')->join('ingest_state as ing', 't.transaction = ing.trans_id');
    $this->db->where('ing.message','completed')->where('ing.person_id',$eus_user_id);
    $this->db->order_by('t.transaction desc');
    $transaction_query = $this->db->get();
    
    if($transaction_query && $transaction_query->num_rows() > 0){
      foreach($transaction_query->result() as $row){
        $stime = date_create($row->submit_time);
        $transactions[$row->transaction] = array(
          'submit_time' => $stime->format('Y-m-d H:i:s')
        );
      }
    }
    return $transactions;
  }
  
  
  
  private function get_info_for_transactions($transaction_info){
    //get proposals
    $transaction_list = array_keys($transaction_info);
    $this->db->select(array('f.transaction','g.name as proposal_id'));
    $this->db->where_in('f.transaction',$transaction_list)->where('g.type','proposal');
    $this->db->from('group_items gi');
    $this->db->join('files f','gi.item_id = f.item_id');
    $this->db->join('groups g','g.group_id = gi.group_id');
    $proposal_query = $this->db->get();
    
    $trans_prop_lookup = array();
    if($proposal_query && $proposal_query->num_rows() > 0){
      foreach($proposal_query->result() as $row){

        $trans_prop_lookup[$row->transaction]['eus_proposal_id'] = $row->proposal_id;
      }
    }
    
    //get instruments
    $this->db->select(array('f.transaction','g.name as group_name','g.type as group_type'));
    $this->db->where("(g.type = 'omics.dms.instrument_id' or g.type ilike 'instrument.%')");
    $this->db->where_in('f.transaction',$transaction_list);
    $this->db->from('group_items gi');
    $this->db->join('files f','gi.item_id = f.item_id');
    $this->db->join('groups g','g.group_id = gi.group_id');
    $inst_query = $this->db->get();
    
    if($inst_query && $inst_query->num_rows() > 0){
      foreach($inst_query->result() as $row){
        $instrument_id = $row->group_type == 'omics.dms.instrument_id' ? $row->group_name : str_ireplace('instrument.','',$row->group_type);
        $trans_prop_lookup[$row->transaction]['eus_instrument_id'] = $instrument_id + 0;
      }
    }
    
    return $trans_prop_lookup;
    
  }
  


  
  
  
  private function generate_summary_data($results){
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
          $results['transactions'][$file_row->transaction]['eus_instrument_id'] = 
            array_key_exists('eus_instrument_id',$results['transaction_info'][$file_row->transaction]) ? 
              $results['transaction_info'][$file_row->transaction]['eus_instrument_id'] : 0;
          $results['transactions'][$file_row->transaction]['eus_proposal_id'] = 
            array_key_exists('eus_proposal_id', $results['transaction_info'][$file_row->transaction]) ?
              $results['transaction_info'][$file_row->transaction]['eus_proposal_id'] : "0";          
          // $results['transactions'][$file_row->transaction]['instrument_id'] = $eus_instrument_id;
          // $results['transactions'][$file_row->transaction]['proposal_id'] = '';
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
    
    unset($results['transaction_info']);
    
    return $results;
  }
  
  
  
  
  /* 
   * $transaction list is a list of transaction id's, each with an array containing submit time
   * size_bytes, size_string (opt), and count
   * */
  private function generate_day_graph_summary($transaction_list,$start_date = false,$end_date = false){
    
    //parse requested date range (if specified)
    $start_date = strtotime($start_date) ? date_create($start_date) : false;
    $end_date = strtotime($end_date) ? date_create($end_date) : false;
    
    
    $date_list = array();
    $summary_list = array('by_date' => array(), 'by_hour_list' => array());
    
    //partition transaction entries into their appropriate upload days
    foreach($transaction_list as $trans_id => $info){
      if(strtotime($info['submit_time'])){  //is the submission time valid?
        $ds = date_create($info['submit_time']);
        $date_key = clone $ds;
        $date_key = $date_key->setTime(0,0,0)->format('Y-m-d');
        $time_key = clone $ds;
        $time_key = $time_key->setTime($time_key->format('H'),0,0)->format('H:i');
        if(!array_key_exists($date_key,$summary_list['by_date'])){
          $summary_list['by_date'][$date_key] = array(
            'file_size' => $info['size_bytes'] + 0, 
            'file_count' => $info['count'] + 0,
            'upload_count' => 1,
            'transaction_list' => array($trans_id)
          );
        }else{
          $summary_list['by_date'][$date_key]['file_size'] += ($info['size_bytes'] + 0);
          $summary_list['by_date'][$date_key]['file_count'] += ($info['count'] + 0);
          $summary_list['by_date'][$date_key]['upload_count'] += 1;
          $summary_list['by_date'][$date_key]['transaction_list'][] = $trans_id;
        }
        $summary_list['by_hour_list'][$time_key]['sizes'][] = $info['size_bytes'] + 0;
        $summary_list['by_hour_list'][$time_key]['counts'][] = $info['count'] + 0;
      }else{
        continue;
      }
      $date_list[] = $ds;
    }
    //$summary_times = range(0, 23, 1);
    ksort($summary_list['by_hour_list']);
    ksort($summary_list['by_date']);
    //foreach($summary_times as $key){
    foreach($summary_list['by_hour_list'] as $time_key => $info){
      //$time_key = date_create()->setTime($key,0,0)->format('H:i');
      // if(array_key_exists($time_key,$summary_list['by_hour_list'])){
        // $info = $summary_list['by_hour_list'][$time_key];
      // }else{
        // $info = array('sizes' => array(), 'counts' => array());
      // }
      $trans_count = sizeof($info['sizes']);
      $summary_list['by_hour'][$time_key] = array(
        'upload_count' => $trans_count,
        'avg_size_per_trans' => $trans_count > 0 ? floor(array_sum($info['sizes']) / $trans_count) : 0,
        'total_size_bytes' => array_sum($info['sizes']),
        'avg_files_per_trans' => $trans_count >0 ? floor(array_sum($info['counts']) / $trans_count) : 0,
        'total_file_count' => array_sum($info['counts'])
      );
    }
    unset($summary_list['by_hour_list']);
    
    return $summary_list;
    
  }
  
  
  private function canonicalize_date_range($start_date, $end_date){
    //both start and end times are filled in and valid
    $start_date = $this->convert_short_date($start_date);
    $end_date = $this->convert_short_date($end_date);
    
    $start_time = strtotime($start_date) ? date_create($start_date)->setTime(0,0,0) : date_create('1983-01-01 00:00:00');
    $end_time = strtotime($end_date) ? date_create($end_date)->setTime(11,59,59) : false;
    
    if($end_time < $start_time && !empty($end_time)){
      //flipped??
      $temp_start = strtotime($end_time) ? clone($end_time) : false;
      $end_time = clone($start_time);
      $start_time = $temp_start;
    }
    
    return array('start_time' => $start_time, 'end_time' => $end_time);
  }
  
  private function convert_short_date($date_string){
    if(preg_match('/(\d{4})$/',$date_string,$matches)){
      //looks like just a year
      $date_string = "{$matches[1]}-01-01";
    }elseif(preg_match('/^(\d{4})-(\d{1,2})$/',$date_string,$matches)){
      $date_string = "{$matches[1]}-{$matches[2]}-01";
    }
    return $date_string;
  }
  
  private function get_proposal_group_list($proposal_id_filter = ""){
    $this->db->select(array('group_id','name as proposal_id'))->where('type','proposal');
    if(!empty($proposal_id_filter)){
      $this->db->where('name',$proposal_id_filter);
    }
    $query = $this->db->get('groups');
    
    $results_by_proposal = array();
    if($query && $query->num_rows()){
      foreach($query->result() as $row){
        $results_by_proposal[$row->group_id] = $row->proposal_id;
      }
    }
    return $results_by_proposal;  
  }
  
  
  private function get_instrument_group_list($inst_id_filter = ""){
    $this->db->select(array('group_id','name','type'));
    // if(!empty($inst_id_filter) && intval($inst_id_filter) >= 0){
      // $where_clause = "(type = 'omics.dms.instrument' or type ilike 'instrument.%') and name not in ('foo') and (group_id = '{$inst_id_filter}' or type like 'Instrument.{$inst_id_filter}')";
    // }else{
      $where_clause = "(type = 'omics.dms.instrument_id' or type ilike 'instrument.%') and name not in ('foo')";
    // }
    
    $this->db->where($where_clause);
    $query = $this->db->order_by('name')->get('groups');
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
  
  
  public function get_selected_objects($eus_person_id){
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    $DB_prefs->select(array('eus_person_id','item_type','item_id'));
    $DB_prefs->where('deleted is null');
    $DB_prefs->order_by('item_type');
    $query = $DB_prefs->get_where('reporting_selection_prefs',array('eus_person_id' => $eus_person_id));
    
    $results = array();
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        switch($row->item_type){
          case 'instrument':
            $group_list = $this->get_instrument_group_list($row->item_id);
            break;
          case 'proposal':
            $group_list = $this->get_proposal_group_list($row->item_id);
            break;
          default:
            $group_list = array();
            
        }
        $results[$row->item_type][$row->item_id] = $group_list;
      }
    }
    return $results;
  }
  
  
  
  
  
}
?>