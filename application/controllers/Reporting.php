<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('Baseline_controller.php');

class Reporting extends Baseline_controller {
  // public $last_update_time;
  // public $accepted_object_types;
  // public $accepted_time_basis_types;
  // public $local_resources_folder;

  function __construct() {
    parent::__construct();
    // $this->load->model('Reporting_model','rep');
    // $this->load->library('EUS','','eus');
    // $this->load->helper(array('network','file_info','inflector','time','item','search_term','cookie'));
    // $this->last_update_time = get_last_update(APPPATH);
    // $this->accepted_object_types = array('instrument','user','proposal');
    // $this->accepted_time_basis_types = array('submit_time','create_time','modified_time');
    // $this->local_resources_folder = $this->config->item('local_resources_folder');
  }

  public function index(){
    redirect('reporting/group_view');
  }

  public function group_view($object_type, $time_range = false, $start_date = false, $end_date = false, $time_basis = false){
    $url = rtrim("group/view/{$object_type}/{$time_range}/{$start_date}/{$end_date}/{$time_basis}","/");
    redirect($url,'location',301);
  }

  public function view($object_type, $time_range = false, $start_date = false, $end_date = false, $time_basis = false){
    $url = rtrim("item/view/{$object_type}/{$time_range}/{$start_date}/{$end_date}/{$time_basis}","/");
    redirect($url,'location',301);
  }


  // public function get_object_container($object_type, $object_id, $time_range, $start_date = false, $end_date = false){
  //   $time_range = str_replace(array('-','_','+'),' ',$time_range);
  //   $times = $this->fix_time_range($time_range, $start_date, $end_date);
  //   $valid_date_range = $this->rep->earliest_latest_data($object_type,$object_id);
  //   $latest_available_date = new DateTime($valid_date_range['latest']);
  //   $earliest_available_date = new DateTime($valid_date_range['earliest']);
  //   $object_list = array();
  //
  //   $valid_range = array(
  //     'earliest' => $earliest_available_date->format('Y-m-d H:i:s'),
  //     'latest' => $latest_available_date->format('Y-m-d H:i:s'),
  //     'earliest_available_object' => $earliest_available_date,
  //     'latest_available_object' => $latest_available_date
  //   );
  //   $times = array_merge($times, $valid_range);
  //   extract($times);
  //   $object_type = singular($object_type);
  //   $accepted_object_types = array('instrument','proposal','user');
  //   $this->page_data['placeholder_info'][$object_id] = array(
  //     'object_type' => $object_type,
  //     'object_id' => $object_id,
  //     'time_range' => $time_range,
  //     'times' => $times
  //   );
  //   $object_list[] = $object_id;
  //   $object_info = $this->eus->get_object_info($object_list,$object_type);
  //   $this->page_data['my_object_type'] = $object_type;
  //   // $this->page_data['default_time_range'] = $times;
  //   $this->page_data['my_objects'] = $object_info;
  //
  //   $this->load->view("object_types/object.html", $this->page_data);
  // }
  //
  // private function fix_time_range($time_range, $start_date, $end_date, $valid_date_range = false){
  //   // echo "start_date => {$start_date}   end_date => {$end_date}";
  //   if(!empty($start_date) && !empty($end_date)){
  //     $times = $this->rep->canonicalize_date_range($start_date, $end_date);
  //     return $times;
  //   }
  //   $time_range = str_replace(array('-','_','+'),' ',$time_range);
  //   if(!strtotime($time_range)){
  //     if($time_range == 'custom' && strtotime($start_date) && strtotime($end_date)){
  //       //custom date_range, just leave them. Canonicalize will fix them
  //     }else{
  //       //looks like the time range is borked, pick the default
  //       $time_range = '1 week';
  //       $times = time_range_to_date_pair($time_range, $valid_date_range);
  //       extract($times);
  //     }
  //   }else{
  //     $times = time_range_to_date_pair($time_range, $valid_date_range);
  //     extract($times);
  //   }
  //
  //   $times = $this->rep->canonicalize_date_range($start_date, $end_date);
  //   return $times;
  // }

  // public function group_view($object_type, $time_range = false, $start_date = false, $end_date = false, $time_basis = 'submit_time'){
  //   $object_type = singular($object_type);
  //   $accepted_object_types = array('instrument','proposal','user');
  //   if(!in_array($object_type,$accepted_object_types)){
  //     redirect('reporting/group_view/instrument');
  //   }
  //   $this->page_data['page_header'] = "Aggregated MyEMSL Uploads by ".ucwords($object_type)." Grouping";
  //   $this->page_data['my_object_type'] = $object_type;
  //   $this->page_data['css_uris'] = array(
  //     "/resources/stylesheets/status_style.css",
  //     "/resources/scripts/select2/select2.css",
  //     "/resources/scripts/bootstrap/css/bootstrap.css",
  //     "/resources/scripts/bootstrap-daterangepicker/daterangepicker.css",
  //     base_url()."application/resources/stylesheets/reporting.css"
  //   );
  //   $this->page_data['script_uris'] = array(
  //     "/resources/scripts/spinner/spin.min.js",
  //     "/resources/scripts/spinner/jquery.spin.js",
  //     "/resources/scripts/moment.min.js",
  //     "/resources/scripts/select2/select2.min.js",
  //     // "/resources/scripts/bootstrap/js/bootstrap.min.js",
  //     "/resources/scripts/bootstrap-daterangepicker/daterangepicker.js",
  //     "/resources/scripts/jquery-typewatch/jquery.typewatch.js",
  //     "/resources/scripts/highcharts/js/highcharts.js",
  //     base_url()."application/resources/scripts/reporting.js"
  //   );
  //   $my_groups = $this->rep->get_selected_groups($this->user_id, $object_type);
  //   $object_list = array();
  //   if(empty($my_groups)){
  //     // $examples = $this->add_objects_instructions($object_type);
  //     // $this->page_data['examples'] = $examples;
  //     $this->page_data['content_view'] = 'object_types/select_some_groups_insert.html';
  //   }else{
  //     $this->page_data['my_groups'] = '';
  //     foreach($my_groups as $group_id => $group_info){
  //       $my_start_date = false;
  //       $my_end_date = false;
  //       $options_list = $group_info['options_list'];
  //       $my_start_date = strtotime($start_date) ? $start_date : $options_list['start_time'];
  //       $my_start_date = $my_start_date != 0 ? $my_start_date : false;
  //       $my_end_date = strtotime($end_date) ? $end_date : $options_list['end_time'];
  //       $my_end_date = $my_end_date != 0 ? $my_end_date : false;
  //       $time_basis = $options_list['time_basis'];
  //       $time_range = $time_range ? $time_range : $options_list['time_range'];
  //       // echo "time_range = {$time_range}"
  //       // echo "group {$group_id} time_range {$time_range} start => {$my_start_date} end => {$my_end_date}<br />";
  //       if($time_range && $time_range != $options_list['time_range'] && $time_range != 'custom'){
  //         $this->rep->change_group_option($group_id,'time_range',$time_range);
  //       }
  //       // echo "group {$group_id} time_range {$time_range}<br />";
  //       $object_list = array_merge($object_list,$group_info['item_list']);
  //
  //       $valid_date_range = $this->rep->earliest_latest_data_for_list($object_type,$group_info['item_list'],$time_basis);
  //       $my_times = $this->fix_time_range($time_range, $my_start_date, $my_end_date, $valid_date_range);
  //       $latest_available_date = new DateTime($valid_date_range['latest']);
  //       $earliest_available_date = new DateTime($valid_date_range['earliest']);
  //
  //       $valid_range = array(
  //         'earliest' => $earliest_available_date->format('Y-m-d H:i:s'),
  //         'latest' => $latest_available_date->format('Y-m-d H:i:s'),
  //         'earliest_available_object' => $earliest_available_date,
  //         'latest_available_object' => $latest_available_date
  //       );
  //       if($my_times['start_time_object']->getTimestamp() < $valid_range['earliest_available_object']->getTimestamp()){
  //         $my_times['start_time_object'] = clone($valid_range['earliest_available_object']);
  //       }
  //       if($my_times['end_time_object']->getTimestamp() > $valid_range['latest_available_object']->getTimestamp()){
  //         $my_times['end_time_object'] = clone($valid_range['latest_available_object']);
  //       }
  //       $my_times = array_merge($my_times, $valid_range);
  //       if(empty($group_info['item_list'])){
  //         $this->page_data['examples'] = $this->add_objects_instructions($object_type);
  //       }
  //       $this->page_data['placeholder_info'][$group_id] = array(
  //         'group_id' => $group_id,
  //         'object_type' => $object_type,
  //         'options_list' => $options_list,
  //         'group_name' => $group_info['group_name'],
  //         'item_list' => $group_info['item_list'],
  //         'time_basis' => $time_basis,
  //         'time_range' => $time_range,
  //         'times' => $my_times
  //       );
  //     }
  //     $object_info = $this->eus->get_object_info($object_list,$object_type);
  //
  //     // var_dump($object_info);
  //     $time_range = str_replace(array('-','_','+'),' ',$time_range);
  //     $this->page_data['my_objects'] = $object_info;
  //     $this->page_data['my_groups'] = $my_groups;
  //     $this->page_data['content_view'] = "object_types/group.html";
  //   }
  //   $this->page_data['js'] = "var object_type = '{$object_type}'; var time_range = '{$time_range}';";
  //   // var_dump($this->page_data['placeholder_info']);
  //   $this->load->view('reporting_view.html',$this->page_data);
  // }

  // public function view($object_type, $group_id, $time_range = '1-month', $start_date = false, $end_date = false){
  //   $object_type = singular($object_type);
  //   $accepted_object_types = array('instrument','proposal','user');
  //   if(!in_array($object_type,$accepted_object_types)){
  //     redirect('reporting/view/instrument/{$time_range}');
  //   }
  //   $this->page_data['page_header'] = "MyEMSL Uploads per ".ucwords($object_type);
  //   $this->page_data['my_object_type'] = $object_type;
  //   $this->page_data['css_uris'] = array(
  //     "/resources/stylesheets/status_style.css",
  //     "/resources/scripts/select2/select2.css",
  //     "/resources/scripts/bootstrap/css/bootstrap.css",
  //     "/resources/scripts/bootstrap-daterangepicker/daterangepicker.css",
  //     APPPATH."resources/stylesheets/reporting.css"
  //   );
  //   $this->page_data['script_uris'] = array(
  //     "/resources/scripts/spinner/spin.min.js",
  //     "/resources/scripts/spinner/jquery.spin.js",
  //     "/resources/scripts/moment.min.js",
  //     // "/resources/scripts/bootstrap/js/bootstrap.min.js",
  //     "/resources/scripts/bootstrap-daterangepicker/daterangepicker.js",
  //     "/resources/scripts/jquery-typewatch/jquery.typewatch.js",
  //     "/resources/scripts/highcharts/js/highcharts.js",
  //     APPPATH."resources/scripts/reporting.js"
  //   );
  //   $this->page_data['js'] = "var object_type = '{$object_type}'; var time_range = '{$time_range}'";
  //   $time_range = str_replace(array('-','_','+'),' ',$time_range);
  //
  //   $my_object_list = $this->rep->get_selected_objects($this->user_id,$object_type,$group_id);
  //   if(empty($my_object_list)){
  //     $examples = $this->add_objects_instructions($object_type);
  //     $this->page_data['examples'] = $examples;
  //     $this->page_data['js'] .= "
  //   $(function(){
  //     $('#object_search_box').focus();
  //   });
  //   ";
  //     $this->page_data['content_view'] = 'object_types/select_some_objects_insert.html';
  //   }else{
  //     $this->page_data['my_objects'] = '';
  //
  //     $object_list = array_map('strval', array_keys($my_object_list[$object_type]));
  //     if(!empty($default_object_id) && in_array($default_object_id,$object_list)){
  //       $object_list = array(strval($default_object_id));
  //     }
  //
  //     // $transaction_info = array();
  //     $object_info = $this->eus->get_object_info($object_list,$object_type);
  //     // $transaction_retrieval_func = "summarize_uploads_by_{$object_type}";
  //     foreach($object_list as $object_id){
  //       $valid_date_range = $this->rep->earliest_latest_data($object_type,$object_id);
  //       $my_times = $this->fix_time_range($time_range, $start_date, $end_date, $valid_date_range);
  //       $latest_available_date = new DateTime($valid_date_range['latest']);
  //       $earliest_available_date = new DateTime($valid_date_range['earliest']);
  //
  //       $valid_range = array(
  //         'earliest' => $earliest_available_date->format('Y-m-d H:i:s'),
  //         'latest' => $latest_available_date->format('Y-m-d H:i:s'),
  //         'earliest_available_object' => $earliest_available_date,
  //         'latest_available_object' => $latest_available_date
  //       );
  //       if($my_times['start_time_object']->getTimestamp() < $valid_range['earliest_available_object']->getTimestamp()){
  //         $my_times['start_time_object'] = clone($valid_range['earliest_available_object']);
  //       }
  //       if($my_times['end_time_object']->getTimestamp() > $valid_range['latest_available_object']->getTimestamp()){
  //         $my_times['end_time_object'] = clone($valid_range['latest_available_object']);
  //       }
  //       $my_times = array_merge($my_times, $valid_range);
  //       // var_dump(array_merge($times,$valid_range));
  //
  //       // $transaction_info[$object_id] = $this->rep->$transaction_retrieval_func($object_id,'2015-10-01','2015-12-01');
  //       $this->page_data['placeholder_info'][$object_id] = array(
  //         'object_type' => $object_type,
  //         'object_id' => $object_id,
  //         'time_range' => $time_range,
  //         'times' => $my_times
  //       );
  //       // var_dump($my_times);
  //     }
  //     // var_dump($object_info);
  //     $this->page_data['my_objects'] = $object_info;
  //     $this->page_data['content_view'] = "object_types/object.html";
  //   }
  //   // $this->page_data['default_time_range'] = $times;
  //   // $this->page_data['transaction_info'] = $transaction_info;
  //
  //   $this->load->view('reporting_view.html',$this->page_data);
  // }

  // public function get_reporting_info($object_type,$object_id,$time_range = '1-week', $start_date = false, $end_date = false, $with_timeline = true){
  //   $this->get_reporting_info_base($object_type, $object_id,$time_range,$start_date,$end_date,true);
  // }
  //
  // public function get_reporting_info_no_timeline($object_type,$object_id,$time_range = '1-week', $start_date = false, $end_date = false){
  //   $this->get_reporting_info_base($object_type, $object_id,$time_range,$start_date,$end_date,false);
  // }
  //
  // // Call to retrieve fill-in HTML for reporting block entries
  // private function get_reporting_info_base($object_type,$object_id,$time_range = '1-week', $start_date = false, $end_date = false, $with_timeline = true, $full_object = false){
  //   $this->page_data['object_id'] = $object_id;
  //   $this->page_data["{$object_type}_id"] = $object_id;
  //   $this->page_data['object_type'] = $object_type;
  //   $available_time_range = $this->rep->earliest_latest_data($object_type,$object_id);
  //   $latest_data = is_array($available_time_range) && array_key_exists('latest',$available_time_range) ? $available_time_range['latest'] : false;
  //   if(!$latest_data){
  //     //no data available for this object
  //     $this->page_data['results_message'] = "No Data Available for this ".ucwords($object_type);
  //     $this->load->view("object_types/object_body_insert.html", $this->page_data);
  //     return;
  //   }
  //   $latest_data_object = new DateTime($latest_data);
  //   $time_range = str_replace(array('-','_','+'),' ',$time_range);
  //   $this->page_data['results_message'] = "&nbsp;";
  //   $valid_tr = strtotime($time_range);
  //   $valid_st = strtotime($start_date);
  //   $valid_et = strtotime($end_date);
  //   if(!$valid_tr){
  //     if($time_range == 'custom' && $valid_st && $valid_et){
  //       //custom date_range, just leave them. Canonicalize will fix them
  //       $earliest_available_object = new DateTime($available_time_range['earliest']);
  //       $latest_available_object = new DateTime($available_time_range['latest']);
  //       $start_date_object = new DateTime($start_date);
  //       $end_date_object = new DateTime($end_date);
  //       if($start_date_object->getTimestamp() < $earliest_available_object->getTimestamp()){
  //         $start_date_object = clone $earliest_available_object;
  //         $start_date = $start_date_object->format('Y-m-d');
  //       }
  //       if($end_date_object->getTimestamp() > $latest_available_object->getTimestamp()){
  //         $end_date_object = clone $latest_available_object;
  //         $end_date = $end_date_object->format('Y-m-d');
  //       }
  //       $times = array(
  //         'start_date' => $start_date_object->format('Y-m-d H:i:s'),
  //         'end_date' => $end_date_object->format('Y-m-d H:i:s'),
  //         'earliest' => $earliest_available_object->format('Y-m-d H:i:s'),
  //         'latest' => $latest_available_object->format('Y-m-d H:i:s'),
  //         'start_date_object' => $start_date_object,
  //         'end_date_object' => $end_date_object,
  //         'time_range' => $time_range,
  //         'earliest_available_object' => $earliest_available_object,
  //         'latest_available_object' => $latest_available_object,
  //         'message' => "<p>Using ".$end_date_object->format('Y-m-d')." as the new origin time</p>"
  //       );
  //     }else{
  //       //looks like the time range is borked, pick the default
  //       $time_range = '1 week';
  //       $times = time_range_to_date_pair($time_range,$available_time_range);
  //     }
  //   }else{ //time_range is apparently valid
  //     if(($valid_st || $valid_et) && !($valid_st && $valid_et)){
  //       //looks like we want an offset time either start or finish
  //       $times = time_range_to_date_pair($time_range,$available_time_range,$start_date,$end_date);
  //     }else{
  //       $times = time_range_to_date_pair($time_range, $available_time_range);
  //     }
  //   }
  //   extract($times);
  //
  //   $transaction_retrieval_func = "summarize_uploads_by_{$object_type}";
  //   $transaction_info = array();
  //   $transaction_info = $this->rep->$transaction_retrieval_func($object_id,$start_date,$end_date, $with_timeline);
  //   // echo "<pre>";
  //   // var_dump($transaction_info);
  //   // echo "</pre>";
  //   // exit;
  //   $this->page_data['transaction_info'] = $transaction_info;
  //   $this->page_data['times'] = $times;
  //   $this->page_data['options_list'] = $options_list;
  //   $this->page_data['include_timeline'] = $with_timeline;
  //   // echo "<pre>";
  //   // var_dump($this->page_data);
  //   // echo "</pre>";
  //   // exit;
  //
  //   if($with_timeline){
  //     $this->load->view("object_types/object_body_insert.html", $this->page_data);
  //   }else{
  //     $this->load->view("object_types/object_pie_scripts_insert.html", $this->page_data);
  //   }
  // }

  // public function get_reporting_info_list($object_type,$group_id,$time_range = false, $start_date = false, $end_date = false, $with_timeline = true, $time_basis = false){
  //   $this->get_reporting_info_list_base($object_type, $group_id,$time_range,$start_date,$end_date,true, false, $time_basis);
  // }
  //
  // public function get_reporting_info_list_no_timeline($object_type,$group_id,$time_range = false, $start_date = false, $end_date = false, $time_basis = false){
  //   $this->get_reporting_info_list_base($object_type, $group_id,$time_range,$start_date,$end_date,false,false,$time_basis);
  // }
  //
  // private function get_reporting_info_list_base($object_type,$group_id,$time_range,$start_date = false, $end_date = false, $with_timeline = true, $full_object = false, $time_basis){
  //   // $time_basis = $this->set_time_basis_cookie($time_basis, $object_type, $group_id);
  //   $group_info = $this->rep->get_group_info($group_id);
  //   $item_list = $group_info['item_list'];
  //   $options_list = $group_info['options_list'];
  //   if($time_range && $time_range != $options_list['time_range']){
  //     //looks like they want to update the default
  //     $this->rep->change_group_option($group_id,'time_range',$time_range);
  //   }else{
  //     $time_range = $options_list['time_range'];
  //   }
  //   $time_basis = $options_list['time_basis'];
  //   $start_date = !$start_date || !strtotime($options_list['start_time']) ? $options_list['start_time'] : $start_date;
  //   $end_date = !$end_date || !strtotime($options_list['end_time']) ? $options_list['end_time'] : $end_date;
  //
  //   $object_id_list = array_values($item_list);
  //   $this->page_data['object_id_list'] = $object_id_list;
  //   // $this->page_data['object_id'] = $object_id;
  //   $this->page_data["{$object_type}_id_list"] = $object_id_list;
  //   $this->page_data['object_type'] = $object_type;
  //   $this->page_data['group_id'] = $group_id;
  //   $available_time_range = $this->rep->earliest_latest_data_for_list($object_type,$object_id_list, $time_basis);
  //
  //   $latest_data = is_array($available_time_range) && array_key_exists('latest',$available_time_range) ? $available_time_range['latest'] : false;
  //   if(!$latest_data){
  //     //no data available for this object
  //     $this->page_data['results_message'] = "No Data Available for this group of ".plural(ucwords($object_type));
  //     $this->load->view("object_types/group_body_insert.html", $this->page_data);
  //     return;
  //   }
  //   $latest_data_object = new DateTime($latest_data);
  //   $time_range = str_replace(array('-','_','+'),' ',$time_range);
  //   $this->page_data['results_message'] = "&nbsp;";
  //   $valid_tr = strtotime($time_range);
  //   $valid_st = strtotime($start_date);
  //   $valid_et = strtotime($end_date);
  //   if(!$valid_tr){
  //     if($time_range == 'custom' && $valid_st && $valid_et){
  //       //custom date_range, just leave them. Canonicalize will fix them
  //       $earliest_available_object = new DateTime($available_time_range['earliest']);
  //       $latest_available_object = new DateTime($available_time_range['latest']);
  //       $start_date_object = new DateTime($start_date);
  //       $end_date_object = new DateTime($end_date);
  //       if($start_date_object->getTimestamp() < $earliest_available_object->getTimestamp()){
  //         $start_date_object = clone $earliest_available_object;
  //         $start_date = $start_date_object->format('Y-m-d');
  //       }
  //       if($end_date_object->getTimestamp() > $latest_available_object->getTimestamp()){
  //         $end_date_object = clone $latest_available_object;
  //         $end_date = $end_date_object->format('Y-m-d');
  //       }
  //       $times = array(
  //         'start_date' => $start_date_object->format('Y-m-d H:i:s'),
  //         'end_date' => $end_date_object->format('Y-m-d H:i:s'),
  //         'earliest' => $earliest_available_object->format('Y-m-d H:i:s'),
  //         'latest' => $latest_available_object->format('Y-m-d H:i:s'),
  //         'start_date_object' => $start_date_object,
  //         'end_date_object' => $end_date_object,
  //         'time_range' => $time_range,
  //         'earliest_available_object' => $earliest_available_object,
  //         'latest_available_object' => $latest_available_object,
  //         'message' => "<p>Using ".$end_date_object->format('Y-m-d')." as the new origin time</p>"
  //       );
  //
  //     }else{
  //       //looks like the time range is borked, pick the default
  //       $time_range = '1 week';
  //       $times = time_range_to_date_pair($time_range,$available_time_range);
  //     }
  //   }else{ //time_range is apparently valid
  //     if(($valid_st || $valid_et) && !($valid_st && $valid_et)){
  //       //looks like we want an offset time either start or finish
  //       $times = time_range_to_date_pair($time_range,$available_time_range,$start_date,$end_date);
  //     }else{
  //       $times = time_range_to_date_pair($time_range, $available_time_range);
  //     }
  //   }
  //   // var_dump($times);
  //
  //   extract($times);
  //
  //   $transaction_retrieval_func = "summarize_uploads_by_{$object_type}_list";
  //   $transaction_info = array();
  //   $transaction_info = $this->rep->$transaction_retrieval_func($object_id_list,$start_date,$end_date, $with_timeline, $time_basis);
  //   $this->page_data['transaction_info'] = $transaction_info;
  //   $this->page_data['times'] = $times;
  //   $this->page_data['include_timeline'] = $with_timeline;
  //
  //   if($with_timeline){
  //     $this->load->view("object_types/group_body_insert.html", $this->page_data);
  //   }else{
  //     $this->load->view("object_types/group_pie_scripts_insert.html", $this->page_data);
  //   }
  // }

  // public function get_transaction_list_details(){
  //   if($this->input->post()){
  //     $transaction_list = $this->input->post();
  //   }elseif($this->input->is_ajax_request() || file_get_contents('php://input')){
  //     $HTTP_RAW_POST_DATA = file_get_contents('php://input');
  //     $transaction_list = json_decode($HTTP_RAW_POST_DATA,true);
  //   }
  //   $results = $this->rep->detailed_transaction_list($transaction_list);
  //   $this->page_data['transaction_info'] = $results;
  //
  //   $this->load->view('object_types/transaction_details_insert.html', $this->page_data);
  //
  // }

  //
  // public function get_timeline_data($object_type,$object_id,$start_date,$end_date){
  //   if(!in_array($object_type,$this->accepted_object_types)){
  //     //return an error
  //     return false;
  //   }
  //
  //   $retrieval_func = "summarize_uploads_by_{$object_type}";
  //   $results = $this->rep->$retrieval_func($object_id,$start_date,$end_date,true);
  //   $downselect = $results['day_graph']['by_date'];
  //   $return_array = array(
  //     'file_volumes' => array_values($downselect['file_volume_array']),
  //     'transaction_counts' => array_values($downselect['transaction_count_array'])
  //   );
  //   send_json_array($return_array);
  // }

  // public function get_group_timeline_data($object_type,$group_id,$start_date,$end_date){
  //   if(!in_array($object_type,$this->accepted_object_types)){
  //     //return an error
  //     return false;
  //   }
  //   $group_info = $this->rep->get_group_info($group_id);
  //
  //   $object_list = $group_info['item_list'];
  //   $retrieval_func = "summarize_uploads_by_{$object_type}_list";
  //   $results = $this->rep->$retrieval_func($object_list,$start_date,$end_date,true,$group_info['options_list']['time_basis']);
  //   $downselect = $results['day_graph']['by_date'];
  //   $return_array = array(
  //     'file_volumes' => array_values($downselect['file_volume_array']),
  //     'transaction_counts' => array_values($downselect['transaction_count_array'])
  //   );
  //   $start_date_obj = new DateTime($start_date);
  //   $end_date_obj = new DateTime($end_date);
  //   $this->rep->change_group_option($group_id, 'start_time', $start_date_obj->format('Y-m-d'));
  //   $this->rep->change_group_option($group_id, 'end_time', $end_date_obj->format('Y-m-d'));
  //   send_json_array($return_array);
  // }

  // public function get_proposals($proposal_name_fragment, $active = 'active'){
  //   $results = $this->eus->get_proposals_by_name($proposal_name_fragment,$active);
  //   send_json_array($results);
  // }

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* Functions for adding/removing objects from the report page  */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
  // public function get_object_lookup($object_type,$filter = ""){
  //   $my_objects = $this->rep->get_selected_objects($this->user_id);
  //   if(!array_key_exists($object_type, $my_objects)){
  //     $my_objects[$object_type] = array();
  //   }
  //   $filter = parse_search_term($filter);
  //   $results = $this->eus->get_object_list($object_type, $filter, $my_objects[$object_type]);
  //   $this->page_data['results'] = $results;
  //   $this->page_data['object_type'] = $object_type;
  //   $this->page_data['filter_text'] = $filter;
  //   $this->page_data['my_objects'] = $my_objects[$object_type];
  //   $this->page_data['js'] = '$(function(){ setup_search_checkboxes(); })';
  //   if(!empty($results)){
  //     $this->load->view("object_types/search_results/{$object_type}_results.html",$this->page_data);
  //   }else{
  //     $filter_string = implode("' '", $filter);
  //     print "<div class='info_message' style='margin-bottom:1.5em;'>No Results Returned for '{$filter_string}'</div>";
  //   }
  // }

  // public function get_object_group_lookup($object_type,$group_id,$filter = ""){
  //   $my_objects = $this->rep->get_selected_objects($this->user_id,$object_type,$group_id);
  //   if(!array_key_exists($object_type, $my_objects)){
  //     $my_objects[$object_type] = array();
  //   }
  //   $filter = parse_search_term($filter);
  //   $results = $this->eus->get_object_list($object_type, $filter, $my_objects[$object_type]);
  //   $this->page_data['results'] = $results;
  //   $this->page_data['object_type'] = $object_type;
  //   $this->page_data['filter_text'] = $filter;
  //   $this->page_data['my_objects'] = $my_objects[$object_type];
  //   $this->page_data['js'] = '$(function(){ setup_search_checkboxes(); })';
  //   if(!empty($results)){
  //     $this->load->view("object_types/search_results/{$object_type}_results.html",$this->page_data);
  //   }else{
  //     $filter_string = implode("' '", $filter);
  //     print "<div class='info_message' style='margin-bottom:1.5em;'>No Results Returned for '{$filter_string}'</div>";
  //   }
  // }

  // public function update_object_preferences($object_type, $group_id = false){
  //   if($this->input->post()){
  //     $object_list = $this->input->post();
  //   }elseif($this->input->is_ajax_request() || file_get_contents('php://input')){
  //     $HTTP_RAW_POST_DATA = file_get_contents('php://input');
  //     $object_list = json_decode($HTTP_RAW_POST_DATA,true);
  //   }else{
  //     //return a 404 error
  //   }
  //   $filter = $object_list[0]['current_search_string'];
  //   $new_set = array();
  //   if($this->rep->update_object_preferences($object_type,$object_list,$group_id)){
  //     $this->get_object_group_lookup($object_type, $group_id, $filter);
  //   }
  //   //send_json_array($new_set);
  // }

  // public function remove_group($group_id = false){
  //   if(!$group_id){
  //     $this->output->set_status_header(400, "No Group ID specified");
  //     return;
  //   }
  //   $group_info = $this->rep->get_group_info($group_id);
  //   if(!$group_info){
  //     $this->output->set_status_header(404, "Group ID {$group_id} was not found");
  //     return;
  //   }
  //   if($this->user_id != $group_info['person_id']){
  //     $this->output->set_status_header(401, "User {$this->eus_person_id} is not the owner of Group ID {$group_id}");
  //     return;
  //   }
  //   $results = $this->rep->remove_group_object($group_id, true);
  //
  //   $this->output->set_status_header(200);
  //   return;
  // }

  // public function add_objects_instructions($object_type){
  //   $object_examples = array(
  //     'instrument' => array(),
  //     'proposal' => array(),
  //     'user' => array()
  //   );
  //   $object_examples['instrument'] = array(
  //     "'nmr' returns a list of all instruments with 'nmr' somewhere in the name or description",
  //     "'34075' returns the instrument having an ID of '34075' in the EUS database",
  //     "'nmr nittany' returns anything with 'nmr' and 'nittany' somewhere in the name or description"
  //   );
  //   $object_examples['proposal'] = array(
  //     "'phos' returns a list of all proposals having the term 'phos' somewhere in the title or description",
  //     "'49164' returns a proposal having an ID of '49164' in the EUS database"
  //   );
  //   $object_examples['user'] = array(
  //     "'jones' returns a list of EUS users having 'jones' somewhere in their first name, last name or email",
  //     "'36846' returns a user having the ID of '36846' in the EUS database"
  //   );
  //   return $object_examples[$object_type];
  // }

}

?>
