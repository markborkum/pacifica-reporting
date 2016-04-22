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


  /**
   * Retrieves the detailed upload info for a given set of transaction item_list
   *
   * @param array $transaction_list array of transaction id's to be retrieved
   * @return array $results array of results from the db
  */
  function detailed_transaction_list($transaction_list){
    // this gets us the basic user/instrument/proposal info
    $eus_info = $this->get_info_for_transactions(array_combine($transaction_list,$transaction_list));

    // echo "<pre>";
    // var_dump($eus_info);
    // echo "</pre>";


    //now get the aggregated file info
    $select_array = array(
      'f.transaction', 'sum(f.size) as total_file_size',
      'count(f.size) as total_file_count',
      'max(t.stime) as submission_time',
      'min(f.mtime) as earliest_modified_time',
      'max(f.mtime) as latest_modified_time'
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

  function files_to_results($results, $make_day_graph = true, $start_date = false, $end_date = false, $time_basis){
    $transactions = array();
    // var_dump($results);
    $results['day_graph'] = array();
    if(!empty($results['files'])){
      foreach($results['files'] as $item_id => $item_info){
        $transactions[$item_info['transaction']] = $item_info['transaction'];
      }
      $results['transactions'] = $transactions;
      $results['transaction_info'] = $this->get_info_for_transactions($transactions);
      $results = $this->generate_file_summary_data($results);
      $results['day_graph'] = $this->generate_day_graph_summary_files($results['files'],$start_date,$end_date,$time_basis);
    }
    return $results;
  }


    // function files_to_results($results, $make_day_graph = true, $start_date = false, $end_date = false, $time_basis){
    //   $transactions = array();
    //   // var_dump($results);
    //   $results['day_graph'] = array('by_date' => array());
    //   $results['transactions'] = $transactions;
    //   if(!empty($transactions)){
    //     $results['transaction_info'] = $this->get_info_for_transactions($transactions);
    //     if(!empty($results['files'])){
    //       foreach($results['files'] as $item_id => $item_info){
    //         $transactions[$item_info['transaction']] = $item_info['transaction'];
    //       }
    //       $results = $this->generate_file_summary_data($results);
    //       $results['day_graph'] = $this->generate_day_graph_summary_files($results['files'],$start_date,$end_date,$time_basis);
    //     }
    //   }
    //   return $results;
    // }


  function transactions_to_results($results, $make_day_graph = true, $start_date, $end_date, $time_basis){
    //use the transaction list to retrieve summary info
    if(!empty($results['transactions'])){
      $results['transaction_info'] = $this->get_info_for_transactions($results['transactions']);
      // var_dump($results);
      $results = $this->generate_summary_data($results);
      $results['day_graph'] = $this->generate_day_graph_summary($results['transactions'], $start_date, $end_date, $time_basis);
    }else{
      return array();
    }
    return $results;
  }




  function summarize_uploads_by_user($eus_person_id, $start_date, $end_date, $make_day_graph){
    //canonicalize start and end times (yields $start_time & $end_time)
    // echo "start_date => {$start_date}   end_date => {$end_date}";

    extract($this->canonicalize_date_range($start_date, $end_date));

    //get user transactions
    $results = array(
      'transactions' => array(),
      'time_range' => array('start_time' => $start_time, 'end_time' => $end_time)
    );

    $results['transactions'] = $this->get_transactions_for_user($eus_person_id, $start_time, $end_time, false);
    $results = $this->transactions_to_results($results, $make_day_graph, $start_time, $end_time, 'submit_time');

    return $results;
  }

  function summarize_uploads_by_user_list($eus_person_id_list, $start_date,
    $end_date, $make_day_graph, $time_basis = false){
    //canonicalize start and end times (yields $start_time & $end_time)
    // echo "start_date => {$start_date}   end_date => {$end_date}";
    extract($this->canonicalize_date_range($start_date, $end_date));

    //get user transactions
    $results = array(
      'transactions' => array(),
      'time_range' => array('start_time' => $start_time, 'end_time' => $end_time)
    );

    // $results['transactions'] = $this->get_transactions_for_user_list($eus_person_id_list, $start_time, $end_time, false);
    // $results = $this->transactions_to_results($results, $make_day_graph, $start_time, $end_time, $time_basis);
    $results['files'] = $this->get_files_for_user_list($eus_person_id_list, $start_time, $end_time, true, $time_basis);
    $results = $this->files_to_results($results, $make_day_graph, $start_time, $end_time, $time_basis);


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

    $results = $this->transactions_to_results($results, $make_day_graph, $start_time, $end_time, 'submit_time');

    return $results;
  }


  function summarize_uploads_by_proposal_list($eus_proposal_id_list, $start_date, $end_date, $make_day_graph, $time_basis = false){
    //canonicalize start and end times (yields $start_time & $end_time)
    extract($this->canonicalize_date_range($start_date, $end_date));

    $results = array(
      'transactions' => array(),
      'time_range' => array('start_time' => $start_time, 'end_time' => $end_time)
    );

    //get proposal_group_list
    $group_collection = array();
    foreach($eus_proposal_id_list as $eus_proposal_id){
      $new_collection = $this->get_proposal_group_list($eus_proposal_id);
      $group_collection = $group_collection + $new_collection;
    }

    $group_list = array_keys($group_collection);

    //get transactions for time period & group_list
    // $results['transactions'] = $this->get_transactions_from_group_list($group_list, $start_time, $end_time);
    //
    // $results = $this->transactions_to_results($results, $make_day_graph, $start_time, $end_time, $time_basis);
    $results['files'] = $this->get_files_from_group_list($group_list, $start_time, $end_time, $time_basis);
    $results = $this->files_to_results($results, $make_day_graph, $start_time, $end_time, $time_basis);


    return $results;
  }


  function summarize_uploads_by_instrument_list($eus_instrument_id_list, $start_date, $end_date, $make_day_graph, $time_basis = false){
    extract($this->canonicalize_date_range($start_date, $end_date));
    $group_collection = array();
    //get instrument group_id list
    // var_dump($eus_instrument_id_list);
    foreach($eus_instrument_id_list as $eus_instrument_id){
      // echo "instrument_id => ". $eus_instrument_id;
      $new_collection = $this->get_instrument_group_list($eus_instrument_id);
      $group_collection = $group_collection + $new_collection;
    }
    // var_dump($group_collection);
    $group_list = array_keys($group_collection);
    if(empty($group_list)){
      //no results returned for group list => bail out
    }
    $results = array(
      'transactions' => array(),
      'time_range' => array('start_time' => $start_time, 'end_time' => $end_time)
    );
    //get transactions for time period & group_list
    // $results['transactions'] = $this->get_transactions_from_group_list($group_list, $start_time, $end_time);
    $results['files'] = $this->get_files_from_group_list($group_list, $start_time, $end_time, $time_basis);
    $results = $this->files_to_results($results, $make_day_graph, $start_time, $end_time, $time_basis);

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
    // $results['transactions'] = $this->get_transactions_from_group_list($group_list, $start_time, $end_time);
    $results['transactions'] = $this->get_files_from_group_list($group_list, $start_time, $end_time);

    $results = $this->transactions_to_results($results, $make_day_graph, $start_time, $end_time, 'submit_time');

    return $results;
  }

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *    Private functionality for behind the scenes data retrieval
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
  private function get_transactions_from_group_list($group_list, $start_time, $end_time){
    // var_dump($group_list);
    // extract($this->canonicalize_date_range($start_date, $end_date));
    $transactions = array();
    $where_clause = array('stime >=' => $start_time);
    if($end_time){
      $where_clause['stime <'] = $end_time;
    }
    $this->db->select(array(
      't.transaction',
      'date_trunc(\'minute\',t.stime) as submit_time',
      'MIN(date_trunc(\'minute\',f.ctime)) as create_time',
      'MIN(date_trunc(\'minute\',f.mtime)) as modified_time'
    ))->where($where_clause);
    $this->db->from('transactions as t')->join('files as f','f.transaction = t.transaction');
    $this->db->join('group_items as gi','gi.item_id = f.item_id');
    $this->db->where_in('gi.group_id',$group_list);
    $this->db->group_by('t.transaction');
    $this->db->order_by('t.transaction desc')->distinct();
    $transaction_query = $this->db->get();
    // echo $this->db->last_query();
    if($transaction_query && $transaction_query->num_rows()>0){
      foreach($transaction_query->result() as $row){
        $stime = date_create($row->submit_time);
        $ctime = date_create($row->create_time);
        $mtime = date_create($row->modified_time);
        $transactions[$row->transaction] = array(
          'submit_time' => $stime->format('Y-m-d H:i:s'),
          'create_time' => $ctime->format('Y-m-d H:i:s'),
          'modified_time' => $mtime->format('Y-m-d H:i:s')
        );
      }
    }
    return $transactions;
  }

  public function get_files_from_group_list($group_list, $start_time, $end_time, $time_basis){
    $times = array(
      'submit' => array(),
      'create' => array(),
      'modified' => array()
    );
    switch($time_basis){
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
    $this->db->select(array(
      'f.item_id',
      't.transaction',
      'date_trunc(\'minute\',t.stime) as submit_time',
      'date_trunc(\'minute\',f.ctime) as create_time',
      'date_trunc(\'minute\',f.mtime) as modified_time',
      'size as size_bytes'
    ));

    $this->db->where("{$time_field} >=", $start_time);
    if($end_time){
      $this->db->where("{$time_field} <",$end_time);
    }
    // $where_string = "(t.stime >= '{$start_time}'";
    // $where_string .= " OR ";
    // $where_string .= "f.ctime >= '{$start_time}'";
    // $where_string .= " OR ";
    // $where_string .= "f.mtime >= '{$start_time}')";
    // $where_string .= " AND ";
    // $where_string .= "(t.stime < '{$end_time}'";
    // $where_string .= " OR ";
    // $where_string .= "f.ctime < '{$end_time}'";
    // $where_string .= " OR ";
    // $where_string .= "f.mtime < '{$end_time}')";
    $this->db->from('transactions as t')->join('files as f','f.transaction = t.transaction');
    $this->db->join('group_items as gi','gi.item_id = f.item_id');
    // $this->db->where("t.stime <", $end_time);
    $this->db->where_in('gi.group_id',$group_list);
    $this->db->order_by('t.transaction desc')->distinct();
    $query = $this->db->get();
    // echo $this->db->last_query();
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $stime = date_create($row->submit_time);
        $ctime = date_create($row->create_time);
        $mtime = date_create($row->modified_time);
        $files[$row->item_id] = array(
          'submit_time' => $stime->format('Y-m-d H:i:s'),
          'create_time' => $ctime->format('Y-m-d H:i:s'),
          'modified_time' => $mtime->format('Y-m-d H:i:s'),
          'transaction' => $row->transaction,
          'size_bytes' => $row->size_bytes
        );
      }
    }
    return $files;
  }




  private function get_transactions_for_user($eus_user_id, $start_date, $end_date, $unfiltered = false){
    extract($this->canonicalize_date_range($start_date, $end_date));
    // echo $start_time;
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

  private function get_transactions_for_user_list($eus_user_id_list, $start_date, $end_date, $unfiltered = false){
    extract($this->canonicalize_date_range($start_date, $end_date));
    // echo $start_time;
    $transactions = array();
    $where_clause = array('stime >=' => $start_time_object->format('Y-m-d H:i:s'));
    if($end_time){
      $where_clause['stime <'] = $end_time_object->format('Y-m-d H:i:s');
    }
    $this->db->select(array('t.transaction','t.stime as submit_time','ing.person_id'))->where($where_clause);
    $this->db->from('transactions as t')->join('ingest_state as ing', 't.transaction = ing.trans_id');
    $this->db->where('ing.message','completed')->where_in('ing.person_id',$eus_user_id_list);
    $this->db->order_by('t.transaction desc');
    $transaction_query = $this->db->get();
    // echo $this->db->last_query();
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


  private function get_files_for_user_list($eus_user_id_list, $start_date, $end_date, $unfiltered = false, $time_basis){
    extract($this->canonicalize_date_range($start_date, $end_date));
    // echo $start_time;
    switch($time_basis){
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
    // $where_string = "(t.stime >= '{$start_time_object->format('Y-m-d H:i:s')}'";
    // $where_string .= " OR ";
    // $where_string .= "f.ctime >= '{$start_time_object->format('Y-m-d H:i:s')}'";
    // $where_string .= " OR ";
    // $where_string .= "f.mtime >= '{$start_time_object->format('Y-m-d H:i:s')}')";
    // if($end_time){
    //   $where_string .= " AND ";
    //   $where_string .= "(t.stime < '{$end_time_object->format('Y-m-d H:i:s')}'";
    //   $where_string .= " OR ";
    //   $where_string .= "f.ctime < '{$end_time_object->format('Y-m-d H:i:s')}'";
    //   $where_string .= " OR ";
    //   $where_string .= "f.mtime < '{$end_time_object->format('Y-m-d H:i:s')}')";
    // }
    $this->db->where("{$time_field} >= ", $start_time_object->format('Y-m-d H:i:s'));
    if($end_time){
      $this->db->where("{$time_field} < ", $end_time_object->format('Y-m-d H:i:s'));
    }
    $this->db->select(array(
      'f.item_id',
      't.transaction',
      'date_trunc(\'minute\',t.stime) as submit_time',
      'date_trunc(\'minute\',f.ctime) as create_time',
      'date_trunc(\'minute\',f.mtime) as modified_time',
      'size as size_bytes'
    ));
    // $this->db->where($where_string);
    $this->db->from('transactions as t');
    $this->db->join('ingest_state as ing', 't.transaction = ing.trans_id');
    $this->db->join('files as f','f.transaction = t.transaction');
    $this->db->where('ing.message','completed')->where_in('ing.person_id',$eus_user_id_list);
    $this->db->order_by('t.transaction desc');
    $query = $this->db->get();
    // echo $this->db->last_query();
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $stime = date_create($row->submit_time);
        $ctime = date_create($row->create_time);
        $mtime = date_create($row->modified_time);
        $files[$row->item_id] = array(
          'submit_time' => $stime->format('Y-m-d H:i:s'),
          'create_time' => $ctime->format('Y-m-d H:i:s'),
          'modified_time' => $mtime->format('Y-m-d H:i:s'),
          'transaction' => $row->transaction,
          'size_bytes' => $row->size_bytes
        );
      }
    }

    return $files;
  }



  public function get_info_for_transactions($transaction_info){
    //get proposals
    $chunked_transaction_list = array_chunk(array_keys($transaction_info),1000);

    foreach($chunked_transaction_list as $transaction_list){
      $this->db->select(array('f.transaction','g.name as proposal_id'));
      $this->db->where("f.transaction IN (".implode(',',$transaction_list).") AND g.type = 'proposal'");
      // $this->db->where_in('f.transaction',$transaction_list)->where('g.type','proposal');
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
    }

    //get instruments
    foreach($chunked_transaction_list as $transaction_list){
      $this->db->select(array('f.transaction','g.name as group_name','g.type as group_type'));
      $this->db->where("(g.type = 'omics.dms.instrument_id' or g.type ilike 'instrument.%')");
      $this->db->where_in('f.transaction',$transaction_list);
      $this->db->from('group_items gi');
      $this->db->join('files f','gi.item_id = f.item_id');
      $this->db->join('groups g','g.group_id = gi.group_id');
      $inst_query = $this->db->get();
      echo $this->db->last_query();
      return $trans_prop_lookup;
      if($inst_query && $inst_query->num_rows() > 0){
        foreach($inst_query->result() as $row){
          $instrument_id = $row->group_type == 'omics.dms.instrument_id' ? $row->group_name : str_ireplace('instrument.','',$row->group_type);
          $trans_prop_lookup[$row->transaction]['eus_instrument_id'] = $instrument_id + 0;
        }
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


  private function generate_file_summary_data($results){
    $upload_stats = array(
      'proposal' => array(),
      'instrument' => array(),
      'user' => array()
    );
    $total_file_size_summary = 0;
    $total_file_count_summary = 0;
    if(!empty($results['files'])){
      $transaction_records = array(
        'most_files' => array('transaction_id' => false, 'file_count' => 0),
        'largest_upload' => array('transaction_id' => false, 'file_size' => 0),
        'most_prolific' => array('person_id' => FALSE, 'file_size' => 0)
      );
      $file_list = $results['files'];
      $output_list = array();
      foreach($file_list as $item_id => $info){
        // var_dump($info);
        $tx_i = $results['transaction_info'][$info['transaction']];
        $new_info = array(
          'size_string' => format_bytes($info['size_bytes']),
          'eus_instrument_id' => array_key_exists('eus_instrument_id',$tx_i) ? $tx_i['eus_instrument_id'] : 0,
          'eus_person_id' => array_key_exists('eus_person_id', $tx_i) ? $tx_i['eus_person_id'] : "0"
        );
        $results['files'][$item_id] += $new_info;
        $total_file_count_summary++;
        $total_file_size_summary += $info['size_bytes'];

        if(!array_key_exists($tx_i['eus_person_id'], $upload_stats['user'])){
          $upload_stats['user'][$tx_i['eus_person_id']] = 1;
        }else{
          $upload_stats['user'][$tx_i['eus_person_id']] += 1;
        }
        if(array_key_exists('eus_instrument_id', $tx_i)){
          if(!array_key_exists($tx_i['eus_instrument_id'], $upload_stats['instrument'])){
            $upload_stats['instrument'][$tx_i['eus_instrument_id']] = 1;
          }else{
            $upload_stats['instrument'][$tx_i['eus_instrument_id']] += 1;
          }
        }
        if(array_key_exists('eus_proposal_id', $tx_i)){
          if(!array_key_exists($tx_i['eus_proposal_id'], $upload_stats['proposal'])){
            $upload_stats['proposal'][$tx_i['eus_proposal_id']] = 1;
          }else{
            $upload_stats['proposal'][$tx_i['eus_proposal_id']] += 1;
          }
        }

      }

    }

    // foreach($results['transaction_info'])

    $results['summary_totals'] = array(
      'upload_stats' => $upload_stats,
      'total_file_count' => $total_file_count_summary,
      'total_size_bytes' => $total_file_size_summary,
      'total_size_string' => format_bytes($total_file_size_summary)
    );
    // var_dump($results);
    unset($results['transaction_info']);

    return $results;
  }




  /*
   * $transaction list is a list of transaction id's, each with an array containing submit time
   * size_bytes, size_string (opt), and count
   * */
  private function generate_day_graph_summary($transaction_list,$start_date = false,$end_date = false, $time_basis = 'submit_time'){

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
      if(strtotime($info[$time_basis])){  //is the submission time valid?
        $ds = date_create($info[$time_basis]);
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


  private function generate_day_graph_summary_files($file_list,$start_date = false,$end_date = false, $time_basis = 'submit_time'){

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
    $summary_list = array('by_date' => array());

    //partition transaction entries into their appropriate upload days
    foreach($file_list as $item_id => $info){
      $info['count'] = 1;
      $trans_id = $info['transaction'];
      if(strtotime($info[$time_basis])){  //is the submission time valid?
        $ds = date_create($info[$time_basis]);
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
      }else{
        continue;
      }
      $date_list[] = $ds;
    }
    //$summary_times = range(0, 23, 1);
    ksort($summary_list['by_date']);
    $summary_list['by_date'] = day_graph_to_series($summary_list,$start_date->format('Y-m-d'), $end_date->format('Y-m-d'));
    //foreach($summary_times as $key){

    return $summary_list;

  }





  public function latest_available_data($object_type, $object_id){
    $latest_function_name = "latest_available_{$object_type}_data";
    $spread =  $this->$latest_function_name($object_id);
    $latest_time = is_array($spread) && array_key_exists('latest',$spread) ? $spread['latest'] : false;
    return $latest_time;
  }

  public function earliest_latest_data($object_type, $object_id){
    $spread_function_name = "available_{$object_type}_data_spread";
    $object_id_list = array($object_id);
    $spread =  $this->$spread_function_name($object_id_list);
    return $spread;
  }


  public function earliest_latest_data_for_list($object_type,$object_id_list, $time_basis){
    $spread_function_name = "available_{$object_type}_data_spread";
    switch($time_basis){
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
    $spread = $this->$spread_function_name($object_id_list,$time_field);
    return $spread;
  }



  private function available_instrument_data_spread($object_id_list, $time_field){
    $return_array = false;
    if(empty($object_id_list)) return false;

    $group_collection = array();
    foreach($object_id_list as $object_id){
      $group_collection += $this->get_instrument_group_list($object_id);
    }
    $group_list = array_keys($group_collection);
    $latest_time = false;
    // $this->db->select(array(
    //   "MIN(DATE_TRUNC('day', t.stime)) as earliest_upload",
    //   "MIN(DATE_TRUNC('day', f.ctime)) as earliest_create",
    //   "MIN(DATE_TRUNC('day', f.mtime)) as earliest_modified",
    //   "MAX(DATE_TRUNC('day', t.stime)) as latest_upload",
    //   "MAX(DATE_TRUNC('day', f.ctime)) as latest_create",
    //   "MAX(DATE_TRUNC('day', f.mtime)) as latest_modified"
    // ));
    $this->db->select(array(
      "MIN(DATE_TRUNC('day', $time_field)) as earliest",
      "MAX(DATE_TRUNC('day', $time_field)) as latest"
    ));
    $this->db->from('group_items gi');
    $this->db->join('files f','gi.item_id = f.item_id');
    $this->db->join('transactions t', 'f.transaction = t.transaction');
    $this->db->where_in('gi.group_id',$group_list)->limit(1);
    $query = $this->db->get();

    if($query && $query->num_rows() > 0 || !empty($query->row()->latest_upload)){
      $row = $query->row_array();
      $earliest_time = !empty($row['earliest']) ? new DateTime($row['earliest']) : false;
      $latest_time = !empty ($row['latest']) ? new DateTime($row['latest']) : false;
      if(!$earliest_time && !$latest_time){
        return false;
      }
      // foreach($row as $field_name => $value){
      //   $check_time = new DateTime($value);
      //   if(stristr($field_name,'earliest') && $check_time){
      //     $earliest_time = $check_time->getTimestamp() < $earliest_time->getTimestamp() ? $check_time : $earliest_time;
      //   }elseif(stristr($field_name,'latest') && $check_time){
      //     $latest_time = $check_time->getTimestamp() > $latest_time->getTimestamp() ? $check_time : $latest_time;
      //   }
      // }
      $return_array = array(
        'earliest' => $earliest_time->format('Y-m-d H:i'),
        'latest' => $latest_time->format('Y-m-d H:i')
      );
    }
    return $return_array;
  }


  private function available_proposal_data_spread($object_id_list, $time_field){
    $return_array = false;
    if(empty($object_id_list)) return false;
    $group_collection = array();
    foreach($object_id_list as $object_id){
      $group_collection += $this->get_proposal_group_list($object_id);
    }
    $group_list = array_keys($group_collection);

    // $this->db->select(array(
    //   "MIN(DATE_TRUNC('day', t.stime)) as earliest_upload",
    //   "MIN(DATE_TRUNC('day', f.ctime)) as earliest_create",
    //   "MIN(DATE_TRUNC('day', f.mtime)) as earliest_modified",
    //   "MAX(DATE_TRUNC('day', t.stime)) as latest_upload",
    //   "MAX(DATE_TRUNC('day', f.ctime)) as latest_create",
    //   "MAX(DATE_TRUNC('day', f.mtime)) as latest_modified"
    // ));
    $this->db->select(array(
      "MIN(DATE_TRUNC('day', $time_field)) as earliest",
      "MAX(DATE_TRUNC('day', $time_field)) as latest"
    ));

    $this->db->from('group_items gi');
    $this->db->join('files f','gi.item_id = f.item_id');
    $this->db->join('transactions t', 'f.transaction = t.transaction');
    $this->db->where_in('gi.group_id',$group_list)->limit(1);
    $query = $this->db->get();
    if($query && $query->num_rows() > 0 || !empty($query->row()->latest_upload)){
      $row = $query->row_array();
      $earliest_time = !empty($row['earliest']) ? new DateTime($row['earliest']) : false;
      $latest_time = !empty ($row['latest']) ? new DateTime($row['latest']) : false;
      if(!$earliest_time && !$latest_time){
        return false;
      }
      // foreach($row as $field_name => $value){
      //   $check_time = new DateTime($value);
      //   if(stristr($field_name,'earliest') && $check_time){
      //     $earliest_time = $check_time->getTimestamp() < $earliest_time->getTimestamp() ? $check_time : $earliest_time;
      //   }elseif(stristr($field_name,'latest') && $check_time){
      //     $latest_time = $check_time->getTimestamp() > $latest_time->getTimestamp() ? $check_time : $latest_time;
      //   }
      // }
      $return_array = array(
        'earliest' => $earliest_time->format('Y-m-d H:i'),
        'latest' => $latest_time->format('Y-m-d H:i')
      );
      // echo $this->db->last_query();
    }
    return $return_array;
  }


  private function available_user_data_spread($object_id_list,$time_field){
    $return_array = false;
    if(empty($object_id_list)) return false;
    // $this->db->select(array(
    //   "MIN(DATE_TRUNC('day', t.stime)) as earliest_upload",
    //   "MIN(DATE_TRUNC('day', f.ctime)) as earliest_create",
    //   "MIN(DATE_TRUNC('day', f.mtime)) as earliest_modified",
    //   "MAX(DATE_TRUNC('day', t.stime)) as latest_upload",
    //   "MAX(DATE_TRUNC('day', f.ctime)) as latest_create",
    //   "MAX(DATE_TRUNC('day', f.mtime)) as latest_modified"
    // ));
    $this->db->select(array(
      "MIN(DATE_TRUNC('day', $time_field)) as earliest",
      "MAX(DATE_TRUNC('day', $time_field)) as latest"
    ));

    $this->db->where('t.stime is not null');
    $this->db->where_in('submitter',$object_id_list);
    $this->db->from('transactions t')->limit(1);
    $this->db->join('files f','t.transaction = f.transaction');
    $query = $this->db->get();

    if($query && $query->num_rows() > 0 || !empty($query->row()->latest_upload)){
      $row = $query->row_array();
      $earliest_time = !empty($row['earliest']) ? new DateTime($row['earliest']) : false;
      $latest_time = !empty ($row['latest']) ? new DateTime($row['latest']) : false;
      if(!$earliest_time && !$latest_time){
        return false;
      }
      // foreach($row as $field_name => $value){
      //   $check_time = new DateTime($value);
      //   if(stristr($field_name,'earliest') && $check_time){
      //     $earliest_time = $check_time->getTimestamp() < $earliest_time->getTimestamp() ? $check_time : $earliest_time;
      //   }elseif(stristr($field_name,'latest') && $check_time){
      //     $latest_time = $check_time->getTimestamp() > $latest_time->getTimestamp() ? $check_time : $latest_time;
      //   }
      // }
      $return_array = array(
        'earliest' => $earliest_time->format('Y-m-d H:i'),
        'latest' => $latest_time->format('Y-m-d H:i')
      );
      // echo $this->db->last_query();
    }
    return $return_array;
  }




  public function canonicalize_date_range($start_date, $end_date){
    //both start and end times are filled in and valid
    // echo "start_date => {$start_date}   end_date => {$end_date}";
    $start_date = $this->convert_short_date($start_date);
    $end_date = $this->convert_short_date($end_date, 'end');
    $start_time = strtotime($start_date) ? date_create($start_date)->setTime(0,0,0) : date_create('1983-01-01 00:00:00');
    $end_time = strtotime($end_date) ? date_create($end_date) : new DateTime;
    $end_time->setTime(23,59,59);

    if($end_time < $start_time && !empty($end_time)){
      //flipped??
      $temp_start = $end_time ? clone($end_time) : false;
      $end_time = clone($start_time);
      $start_time = $temp_start;
    }

    return array(
      'start_time_object' => $start_time, 'end_time_object' => $end_time,
      'start_time' => $start_time->format('Y-m-d H:i:s'),
      'end_time' => $end_time ? $end_time->format('Y-m-d H:i:s') : false
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
      if(is_array($proposal_id_filter)){
        $this->db->where_in('name',$proposal_id_filter);
      }else{
        $this->db->where('name',$proposal_id_filter);
      }
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


  public function get_selected_objects($eus_person_id,$restrict_type = false,$group_id = false){
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    $DB_prefs->select(array('eus_person_id','item_type','item_id','group_id'));
    $DB_prefs->where('deleted is null');
    if(!empty($group_id)){
      $DB_prefs->where('group_id',$group_id);
    }
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
        // $results[{$row->item_type}_group][$row->group_id][$item_id] =
      }
    }
    return $results;
  }


  public function get_selected_groups($eus_person_id,$restrict_type = false){
    $results = array();
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    // $select_array = array(
    //   'g.group_id','g.group_name','g.group_type','g.person_id as user','p.item_id'
    // );
    $DB_prefs->select('g.group_id');
    $person_array = array($eus_person_id);
    $DB_prefs->where_in('g.person_id',$person_array);
    $DB_prefs->where('g.deleted is NULL');
    if($restrict_type){
      $DB_prefs->where('g.group_type',$restrict_type);
    }
    $DB_prefs->order_by('ordering ASC');
    $query = $DB_prefs->get('reporting_object_groups g');
    $group_id_list = array();
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $group_info = $this->get_group_info($row->group_id);
        $results[$row->group_id] = $group_info;
      }
    }
    return $results;
  }

  public function remove_group_object($group_id, $full_delete = false){
    //cascade remove all the objects associated with this...
    $tables = array(
      'reporting_object_group_options','reporting_selection_prefs','reporting_object_groups'
    );
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    $where_clause = array('group_id' => $group_id);

    if($full_delete){ //permanently remove
      $DB_prefs->delete($tables, $where_clause);
    }else{ //just update deleted_at column
      foreach($tables as $table_name){
        $DB_prefs->update($table_name, array('deleted' => 'now()'), $where_clause);
      }
    }

  }

  function update_object_preferences($object_type,$object_list,$group_id = false){
    //object list should look like array('object_id' => $object_id, 'action' => $action)
    $table = 'reporting_selection_prefs';
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    $additions = array();
    $removals = array();
    $existing = array();
    $where_clause = array('item_type' => $object_type, 'eus_person_id' => $this->user_id);
    if($group_id && is_numeric($group_id)){
      $where_clause['group_id'] = $group_id;
    }
    $DB_prefs->select('item_id');
    $check_query = $DB_prefs->get_where($table, $where_clause);
    if($check_query && $check_query->num_rows() > 0){
      foreach($check_query->result() as $row){
        $existing[] = $row->item_id;
      }
    }
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
            'item_id' => strval($object_id),
            'group_id' => $group_id
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

  public function get_items_for_group($group_id){
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    $DB_prefs->select(array('item_type','item_id'));
    $query = $DB_prefs->get_where('reporting_selection_prefs', array('group_id' => $group_id));
    $results = array();
    if($query && $query->num_rows() > 0){
      foreach($query->result() as $row){
        $results[$row->item_type][] = $row->item_id;
      }
    }
    return $results;
  }

  /**
    * *make_new_group* adds an object grouping entity to the website_prefs/reporting_object_groups
    * table
    *
    * @param $object_type string the type of object grouping to create (instrument/user/proposal)
    * @param $eus_person_id int the EUS identifier of the group owner
    * @param $group_name string the name of the group to be created (optional) (set false for default name)
    *
    * @return array|bool array of group information items or false on error
  */
  public function make_new_group($object_type, $eus_person_id, $group_name = false){
    $DB_prefs = $this->load->database('website_prefs', TRUE);
    $table_name = 'reporting_object_groups';
    //check the name and make sure it's unique for this user_id
    if(!$group_name){
      $group_name = "New ".ucwords($object_type)." Group";
    }
    $where_array = array(
      'person_id' => $eus_person_id,
      'group_name' => $group_name
    );
    $check_query = $DB_prefs->where($where_array)->get($table_name);
    if($check_query && $check_query->num_rows() > 0){
      $d = new DateTime();
      $group_name .= " [". $d->format('Y-m-d H:i:s') ."]";
    }
    //ok, group name is clear. make the group_name
    $insert_data = array(
      'person_id' => $eus_person_id,
      'group_name' => $group_name,
      'group_type' => $object_type
    );
    $DB_prefs->insert($table_name,$insert_data);
    if($DB_prefs->affected_rows() > 0){
      //insert went ok, return success
      $group_id = $DB_prefs->insert_id();
      $group_info = $this->get_group_info($group_id);
      return $group_info;
    }
    return false;
  }

  public function change_group_name($group_id,$group_name){
    $new_group_info = false;
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    $update_array = array('group_name' => $group_name);
    $DB_prefs->where('group_id',$group_id)->set('group_name',$group_name)->update('reporting_object_groups',$update_array);
    if($DB_prefs->affected_rows() > 0){
      $new_group_info = $this->get_group_info($group_id);
    }
    return $new_group_info;
  }

  public function change_group_option($group_id, $option_type, $value){
    $DB_prefs = $this->load->database('website_prefs', TRUE);
    $table_name = 'reporting_object_group_options';
    $where_array = array('group_id' => $group_id, 'option_type' => $option_type);
    $update_array = array('option_value' => $value);
    $query = $DB_prefs->where($where_array)->get($table_name);
    if($query && $query->num_rows() > 0){
      //row present, just update
      $DB_prefs->where($where_array)->update($table_name, $update_array);
    }else{
      $DB_prefs->insert($table_name, $update_array + $where_array);
    }
    //$DB_prefs->replace('reporting_object_group_options',$update_array);
    if($DB_prefs->affected_rows() > 0){
      return $update_array + $where_array;
    }
    return false;
    // $DB_prefs->where('group_id',$group_id)->where('option_type',$option_type);
    // $DB_prefs->update()
  }

  public function get_group_info($group_id){
    $option_defaults = $this->get_group_option_defaults();
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    $query = $DB_prefs->get_where('reporting_object_groups',array('group_id' => $group_id),1);
    $group_info = false;
    $options = array();
    if($query && $query->num_rows()>0){
      $options_query = $DB_prefs->get_where('reporting_object_group_options', array('group_id' => $group_id));
      if($options_query && $options_query->num_rows()>0){
        foreach($options_query->result() as $option_row){
          $options[$option_row->option_type] = $option_row->option_value;
        }
      }
      $group_info = $query->row_array();
      $member_query = $DB_prefs->select('item_id')->get_where('reporting_selection_prefs', array('group_id' => $group_id));
      if($member_query && $member_query->num_rows()>0){
        foreach($member_query->result() as $row){
          $group_info['item_list'][] = $row->item_id;
        }
      }else{
        $group_info['item_list'] = array();
      }
      $group_info['options_list'] = $options + $option_defaults;
    }
    $earliest_latest = $this->earliest_latest_data_for_list(
      $group_info['group_type'],
      $group_info['item_list'],
      $group_info['options_list']['time_basis']);

    if($earliest_latest){
      extract($earliest_latest);
      $earliest_obj = new DateTime($earliest);
      $latest_obj = new DateTime($latest);

      $start_time_obj = strtotime($group_info['options_list']['start_time']) ? new DateTime($group_info['options_list']['start_time']) : clone $earliest_obj;
      $end_time_obj = strtotime($group_info['options_list']['end_time']) ? new DateTime($group_info['options_list']['end_time']) : clone $latest_obj;

      if($end_time_obj > $latest_obj){
        $end_time_obj = clone $latest_obj;
        $this->change_group_option($group_id, 'end_time', $end_time_obj->format('Y-m-d'));
        if($start_time_obj < $earliest_obj || $start_time_obj > $latest_obj){
          $start_time_obj = clone($latest_obj);
          $start_time_obj->modify('-1 month');
          $this->change_group_option($group_id, 'start_time', $start_time_obj->format('Y-m-d'));
        }
      }elseif($start_time_obj < $earliest_obj){
        $start_time_obj = clone $earliest_obj;
        $this->change_group_option($group_id, 'start_time', $start_time_obj->format('Y-m-d'));
        if($end_time_obj < $start_time_obj || $end_time_obj > $latest_obj){
          $end_time_obj = clone($start_time_obj);
          $end_time_obj->modify('+1 month');
          $this->change_group_option($group_id, 'end_time', $end_time_obj->format('Y-m-d'));
        }
      }
      $group_info['options_list']['start_time'] = $start_time_obj->format('Y-m-d');
      $group_info['options_list']['end_time'] = $end_time_obj->format('Y-m-d');
    }


    return $group_info;
  }

  public function get_group_option_defaults(){
    $DB_prefs = $this->load->database('website_prefs',TRUE);
    $query = $DB_prefs->get('reporting_object_group_option_defaults');
    $defaults = array();
    if($query && $query->num_rows() > 0){
      foreach($query->result() as $row){
        $defaults[$row->option_type] = $row->option_default;
      }
    }
    return $defaults;
  }

}
?>
