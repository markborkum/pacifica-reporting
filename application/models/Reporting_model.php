<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Reporting Model                                                         */
/*                                                                             */
/*             functionality for summarizing upload and activity data.         */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Reporting_model extends CI_Model {

  // protected $selected_objects;

  function __construct(){
    parent::__construct();
    define("TRANS_TABLE", 'transactions');
    define("FILES_TABLE", 'files');
    $this->load->database();
    $this->load->helper(array('item'));

    //$this->selected_objects = $this->get_selected_objects($this->user_id);
  }



/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *    Publicly available API calls
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
  function detailed_transaction_list($transaction_list){
    //this gets us the basic user/instrument/proposal info
    $eus_info = $this->get_info_for_transactions(array_combine($transaction_list,$transaction_list));

    // echo "<pre>";
    // var_dump($eus_info);
    // echo "</pre>";


    //now get the aggregated file info
    $select_array = array(
      'f.transaction', 'sum(f.size) as total_file_size',
      'count(f.size) as total_file_count',
      'max(t.stime) as submission_time'
    );

    $this->db->select($select_array);
    $this->db->from('files f')->join('transactions t', 'f.transaction = t.transaction');
    $this->db->where_in('f.transaction', $transaction_list);
    $this->db->group_by('f.transaction')->order_by('f.transaction desc');
    $query = $this->db->get();

    $file_info = array();
    if($query && $query->num_rows() > 0){
      foreach($query->result_array() as $row){
        $file_info[$row['transaction']] = $row;
      }
    }

    // echo "<pre>";
    // var_dump($file_info);
    // echo "</pre>";

    //combine the list of file info and eus info
    foreach($file_info as $transaction_id => $file_entry){
      $eus_entry = $eus_info[$transaction_id];
      foreach($eus_entry as $key => $value){
        $file_entry[$key] = $value;
      }
      $results[$transaction_id] = $file_entry;
    }
    return $results;

  }


  function transactions_to_results($results, $make_day_graph = true, $start_date, $end_date){
    //use the transaction list to retrieve summary info
    if(!empty($results['transactions'])){
      $results['transaction_info'] = $this->get_info_for_transactions($results['transactions']);
      $results = $this->generate_summary_data($results);
      $results['day_graph'] = $this->generate_day_graph_summary($results['transactions'], $start_date, $end_date);
    }else{
      return array();
    }
    return $results;
  }




  function summarize_uploads_by_user($eus_person_id, $start_date, $end_date, $make_day_graph){
    //canonicalize start and end times (yields $start_time & $end_time)
    extract($this->canonicalize_date_range($start_date, $end_date));

    //get user transactions
    $results = array(
      'transactions' => array(),
      'time_range' => array('start_time' => $start_time, 'end_time' => $end_time)
    );

    $results['transactions'] = $this->get_transactions_for_user($eus_person_id, $start_time, $end_time, false);
    $results = $this->transactions_to_results($results, $make_day_graph, $start_time, $end_time);

    return $results;
  }




  function summarize_uploads_by_proposal($eus_proposal_id, $start_date, $end_date, $make_day_graph){
    //canonicalize start and end times (yields $start_time & $end_time)
    extract($this->canonicalize_date_range($start_date, $end_date));

    $results = array(
      'transactions' => array(),
      'time_range' => array('start_time' => $start_time, 'end_time' => $end_time)
    );

    //get proposal_group_list
    $group_collection = $this->get_proposal_group_list($eus_proposal_id);
    $group_list = array_keys($group_collection);

    //get transactions for time period & group_list
    $results['transactions'] = $this->get_transactions_from_group_list($group_list, $start_time, $end_time);

    $results = $this->transactions_to_results($results, $make_day_graph, $start_time, $end_time);

    return $results;
  }




  function summarize_uploads_by_instrument($eus_instrument_id, $start_date, $end_date, $make_day_graph){
    extract($this->canonicalize_date_range($start_date, $end_date));

    //get instrument group_id list
    $group_collection = $this->get_instrument_group_list($eus_instrument_id);
    $group_list = array_keys($group_collection);
    if(empty($group_list)){
      //no results returned for group list => bail out
    }
    $results = array(
      'transactions' => array(),
      'time_range' => array('start_time' => $start_time, 'end_time' => $end_time)
    );
    //get transactions for time period & group_list
    $results['transactions'] = $this->get_transactions_from_group_list($group_list, $start_time, $end_time);
    $results = $this->transactions_to_results($results, $make_day_graph, $start_time, $end_time);

    return $results;
  }


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *    Private functionality for behind the scenes data retrieval
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
  private function get_transactions_from_group_list($group_list, $start_time, $end_time){
    // extract($this->canonicalize_date_range($start_date, $end_date));
    $transactions = array();
    $where_clause = array('stime >=' => $start_time);
    if($end_time){
      $where_clause['stime <'] = $end_time;
    }
    $this->db->select(array('t.transaction','t.stime as submit_time'))->where($where_clause);
    $this->db->from('transactions as t')->join('files as f','f.transaction = t.transaction');
    $this->db->join('group_items as gi','gi.item_id = f.item_id');
    $this->db->where_in('gi.group_id',$group_list);
    $this->db->order_by('t.transaction desc')->distinct();
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
    echo $start_time;
    $transactions = array();
    $where_clause = array('stime >=' => $start_time_object->format('Y-m-d H:i:s'));
    if($end_time){
      $where_clause['stime <'] = $end_time_object->format('Y-m-d H:i:s');
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




  public function get_info_for_transactions($transaction_info){
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

    //get_users
    $this->db->select(array('person_id','trans_id as transaction'))->where_in('trans_id',$transaction_list)->order_by('step desc');
    $user_query = $this->db->get('ingest_state');

    if($user_query && $user_query->num_rows() > 0){
      foreach($user_query->result() as $row){
        $trans_prop_lookup[$row->transaction]['eus_person_id'] = $row->person_id;
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
    $transaction_stats = array(
      'proposal' => array(),
      'instrument' => array(),
      'user' => array()
    );

    $file_info_query = $this->db->get(FILES_TABLE);
    if($file_info_query && $file_info_query->num_rows() > 0){
      $transaction_records = array(
        'most_files' => array('transaction_id' => false, 'file_count' => 0),
        'largest_upload' => array('transaction_id' => false, 'file_size' => 0),
        'most_prolific' => array('person_id' => FALSE, 'file_size' => 0)
      );
      foreach($file_info_query->result() as $file_row){
        if(array_key_exists($file_row->transaction, $results['transactions'])){
          $tx = $results['transactions'][$file_row->transaction];
          $tx_i = $results['transaction_info'][$file_row->transaction];

          $tx['size_bytes'] = $file_row->file_size_in_bytes;
          $tx['size_string'] = format_bytes($file_row->file_size_in_bytes);
          $tx['count'] = $file_row->file_count;

          if($file_row->file_size_in_bytes > $transaction_records['largest_upload']['file_size'] + 0){
            $transaction_records['largest_upload'] = array(
              'transaction_id' => $file_row->transaction,
              'file_size' => $file_row->file_size_in_bytes
            );
          }
          if($file_row->file_count > $transaction_records['most_files']['file_count']){
            $transaction_records['most_files'] = array(
              'transaction_id' => $file_row->transaction,
              'file_count' => $file_row->file_count
            );
          }

          $tx['eus_instrument_id'] = array_key_exists('eus_instrument_id',$tx_i) ? $tx_i['eus_instrument_id'] : 0;
          $tx['eus_proposal_id'] = array_key_exists('eus_proposal_id', $tx_i) ? $tx_i['eus_proposal_id'] : "0";
          $tx['eus_person_id'] = array_key_exists('eus_person_id', $tx_i) ? $tx_i['eus_person_id'] : "0";

          $results['transactions'][$file_row->transaction] = $tx;

          $total_file_count_summary += $file_row->file_count;
          $total_file_size_summary += $file_row->file_size_in_bytes;

          //transaction based stats for user/instrument/proposal
          if(!array_key_exists($tx_i['eus_person_id'], $transaction_stats['user'])){
            $transaction_stats['user'][$tx_i['eus_person_id']] = 1;
          }else{
            $transaction_stats['user'][$tx_i['eus_person_id']] += 1;
          }
          if(array_key_exists('eus_instrument_id', $tx_i)){
            if(!array_key_exists($tx_i['eus_instrument_id'], $transaction_stats['instrument'])){
              $transaction_stats['instrument'][$tx_i['eus_instrument_id']] = 1;
            }else{
              $transaction_stats['instrument'][$tx_i['eus_instrument_id']] += 1;
            }
          }
          if(array_key_exists('eus_proposal_id', $tx_i)){
            if(!array_key_exists($tx_i['eus_proposal_id'], $transaction_stats['proposal'])){
              $transaction_stats['proposal'][$tx_i['eus_proposal_id']] = 1;
            }else{
              $transaction_stats['proposal'][$tx_i['eus_proposal_id']] += 1;
            }
          }
        }
      }
    }

    // foreach($results['transaction_info'])

    $results['summary_totals'] = array(
      'transaction_count' => sizeof($results['transaction_info']),
      'transaction_records' => $transaction_records,
      'transaction_stats' => $transaction_stats,
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
    if(is_string($start_date)){
      $start_date = is_string($start_date) ? date_create($start_date) : false;
    }
    if(is_string($end_date)){
      $end_date = strtotime($end_date) ? date_create($end_date) : false;
    }

    if($start_date){
      $padded_start = clone $start_date;
      $padded_start->modify('-2 days');
      $start_date = clone $padded_start;
    }
    if($end_date){
      $padded_end = clone $end_date;
      $padded_end->modify('+2 days');
      $end_date = clone $padded_end;
    }

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
    $summary_list['by_date'] = day_graph_to_series($summary_list,$start_date->format('Y-m-d'), $end_date->format('Y-m-d'));
    //foreach($summary_times as $key){
    foreach($summary_list['by_hour_list'] as $time_key => $info){
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




  public function latest_available_data($object_type, $object_id){
    $latest_function_name = "latest_available_{$object_type}_data";
    return $this->$latest_function_name($object_id);
  }



  private function latest_available_instrument_data($instrument_id){
    $group_collection = $this->get_instrument_group_list($instrument_id);
    $group_list = array_keys($group_collection);
    $latest_time = false;
    $this->db->select('MAX(t.stime) as most_recent_upload');
    $this->db->from('group_items gi');
    $this->db->join('files f','gi.item_id = f.item_id');
    $this->db->join('transactions t', 'f.transaction = t.transaction');
    $this->db->where_in('gi.group_id',$group_list)->limit(1);
    $query = $this->db->get();

    if($query && $query->num_rows() > 0 || !empty($query->row()->most_recent_upload)){
      if(strtotime($query->row()->most_recent_upload)){
        $latest_time = $query->row()->most_recent_upload;
        $latest_time = new DateTime($latest_time);
        return $latest_time->format('Y-m-d H:i');
      }
      return false;
      // echo $this->db->last_query();
    }else{ // no records or null response
      return false;
    }
  }





  private function latest_available_proposal_data($proposal_id){
    $group_collection = $this->get_proposal_group_list($proposal_id);
    $group_list = array_keys($group_collection);

    $this->db->select('MAX(t.stime) as most_recent_upload');
    $this->db->from('group_items gi');
    $this->db->join('files f','gi.item_id = f.item_id');
    $this->db->join('transactions t', 'f.transaction = t.transaction');
    $this->db->where_in('gi.group_id',$group_list)->limit(1);
    $query = $this->db->get();

    if($query && $query->num_rows() > 0){
      if(strtotime($query->row()->most_recent_upload)){
        $latest_time = $query->row()->most_recent_upload;
        $latest_time = new DateTime($latest_time);
      }
      return $latest_time->format('Y-m-d H:i');
    }
  }





  private function latest_available_user_data($person_id){
    $this->db->select(array("t.stime as most_recent_upload","t.transaction"));
    $this->db->where('t.stime is not null')->order_by("t.transaction desc");
    $query = $this->db->get_where("transactions t", array('submitter' => $person_id),1);
    if($query && $query->num_rows() > 0){
      //blank stime in trans table
      $latest_time = new DateTime($query->row()->most_recent_upload);
    }else{
      $this->db->order_by('f.transaction desc');
      $file_query = $this->db->get_where('files f', array('f.transaction' => $query->row()->transaction),1);
      if($file_query && $file_query->num_rows()>0){
        $latest_time = new DateTime($file_query->row()->most_recent_upload);
      }else{
        $latest_time = new DateTime('1991-01-01 00:00:00');
      }
    }
    return $latest_time->format('Y-m-d H:i');
  }




  public function canonicalize_date_range($start_date, $end_date){
    //both start and end times are filled in and valid
    $start_date = $this->convert_short_date($start_date);
    $end_date = $this->convert_short_date($end_date, 'end');

    $start_time = strtotime($start_date) ? date_create($start_date)->setTime(0,0,0) : date_create('1983-01-01 00:00:00');
    $end_time = strtotime($end_date) ? date_create($end_date)->setTime(23,59,59) : false;

    if($end_time < $start_time && !empty($end_time)){
      //flipped??
      $temp_start = strtotime($end_time) ? clone($end_time) : false;
      $end_time = clone($start_time);
      $start_time = $temp_start;
    }

    return array(
      'start_time_object' => $start_time, 'end_time_object' => $end_time,
      'start_time' => $start_time->format('Y-m-d H:i:s'),
      'end_time' => $end_time->format('Y-m-d H:i:s')
    );
  }





  private function convert_short_date($date_string, $type = 'start'){
    if(preg_match('/(\d{4})$/',$date_string,$matches)){
      //looks like just a year
      $date_string = $type == 'start' ? "{$matches[1]}-01-01" : "{$matches[1]}-12-31";
    }elseif(preg_match('/^(\d{4})-(\d{1,2})$/',$date_string,$matches)){
      $date_string = $type == 'start' ? "{$matches[1]}-{$matches[2]}-01" : "{$matches[1]}-{$matches[2]}-31";
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
    if(!empty($inst_id_filter) && is_numeric($inst_id_filter) && array_key_exists($inst_id_filter,$results_by_inst_id)){
      // $inst_id_filter = strval($inst_id_filter);
      $results = $results_by_inst_id[$inst_id_filter];
    }else{
      $results = $results_by_inst_id;
    }

    return $results;
  }


  public function get_selected_objects($eus_person_id,$restrict_type = false){
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    $DB_prefs->select(array('eus_person_id','item_type','item_id'));
    $DB_prefs->where('deleted is null');
    if(!empty($restrict_type)){
      $DB_prefs->where('item_type',$restrict_type);
    }
    $DB_prefs->order_by('item_type');
    $query = $DB_prefs->get_where('reporting_selection_prefs',array('eus_person_id' => $eus_person_id));
    $results = array();
    // echo $DB_prefs->last_query();
    // var_dump($query->result_array());
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
            $group_list = $row->item_id;

        }
        $item_id = strval($row->item_id);
        $results[$row->item_type][$item_id] = $group_list;
      }
    }
    return $results;
  }



  function update_object_preferences($object_type,$object_list){
    //object list should look like array('object_id' => $object_id, 'action' => $action)
    $table = 'reporting_selection_prefs';
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    $additions = array();
    $removals = array();
    $existing = array();
    $where_clause = array('item_type' => $object_type, 'eus_person_id' => $this->user_id);
    $DB_prefs->select('item_id');
    $check_query = $DB_prefs->get_where($table, $where_clause);
    if($check_query && $check_query->num_rows() > 0){
      foreach($check_query->result() as $row){
        $existing[] = $row->item_id;
      }
    }
    // var_dump($object_list);
    foreach($object_list as $item){
      extract($item);
      if($action == 'add'){
        $additions[] = $object_id;
      }elseif($action == 'remove'){
        $removals[] = $object_id;
      }else{
        continue;
      }
      $additions = array_diff($additions,$existing);
      $removals = array_intersect($removals, $existing);

      if(!empty($additions)){
        foreach($additions as $object_id){
          $insert_object = array(
            'eus_person_id' => $this->user_id,
            'item_type' => $object_type,
            'item_id' => strval($object_id)
          );
          $DB_prefs->insert($table,$insert_object);
        }
      }
      if(!empty($removals)){
        $my_where = $where_clause;
        foreach($removals as $object_id){
          $my_where['item_id'] = strval($object_id);
          $DB_prefs->where($my_where)->delete($table);
        }
      }
    }
    return true;
  }



}
?>
