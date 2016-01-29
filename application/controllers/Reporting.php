<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('Baseline_controller.php');

class Reporting extends Baseline_controller {
  public $last_update_time;
  public $accepted_object_types;
  
  function __construct() {
    parent::__construct();
    $this->load->model('Reporting_model','rep');
    $this->load->library('myemsl-eus-library/EUS','','eus');
    $this->load->helper(array('network','file_info','inflector'));
    $this->last_update_time = get_last_update(APPPATH);
    $this->accepted_object_types = array('instrument','user','proposal');
  }



  public function index(){
    redirect('reporting/view');
  }
  
  public function view($object_type, $default_object_id = false, $start_date = false, $end_date = false){
    $object_type = singular($object_type);
    $accepted_object_types = array('instrument','proposal','user');
    if(!in_array($object_type,$accepted_object_types)){
      redirect('reporting/view/instrument');
    }
    $this->page_data['page_header'] = "MyEMSL Uploads per ".ucwords($object_type);
    $this->page_data['css_uris'] = array(
      "/resources/stylesheets/status_style.css",
      "/resources/scripts/select2/select2.css",
      base_url()."resources/stylesheets/reporting.css"   
    );
    $this->page_data['script_uris'] = array(
      "/resources/scripts/spinner/spin.min.js",
      "/resources/scripts/spinner/jquery.spin.js",
      "/resources/scripts/moment.min.js",
      base_url()."resources/scripts/highcharts/js/highcharts.js"
    );
    
    $this->page_data['my_objects'] = '';
    $my_object_list = $this->rep->get_selected_objects($this->user_id,$object_type);
    $object_list = array_map('strval', array_keys($my_object_list[$object_type]));
    if(!empty($default_object_id) && in_array($default_object_id,$object_list)){
      $object_list = array(strval($default_object_id));
    }
    // $transaction_info = array();
    $object_info = $this->eus->get_object_info($object_list,$object_type);
    // $transaction_retrieval_func = "summarize_uploads_by_{$object_type}";
    foreach($object_list as $object_id){
      // $transaction_info[$object_id] = $this->rep->$transaction_retrieval_func($object_id,'2015-10-01','2015-12-01');
      $this->page_data['placeholder_info'][$object_id] = array(
        'object_type' => $object_type,
        'object_id' => $object_id
      );
    }
    
    $this->page_data['content_view'] = "object_types/{$object_type}.html";
    $this->page_data['my_objects'] = $object_info;
    // $this->page_data['transaction_info'] = $transaction_info;
    
    $this->load->view('reporting_view.html',$this->page_data);
  }
  
    
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* API functionality for Ajax calls from UI                  */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

  // Call to retrieve fill-in HTML for reporting block entries
  public function get_reporting_info($object_type,$object_id,$start_date = false, $end_date = false){
    $transaction_retrieval_func = "summarize_uploads_by_{$object_type}";
    $transaction_info = array();
    $transaction_info = $this->rep->$transaction_retrieval_func($object_id,$start_date,$end_date);
    $this->page_data['transaction_info'] = $transaction_info;
    $this->page_data["{$object_type}_id"] = $object_id;
    $this->page_data['object_type'] = $object_type;
    $this->load->view("object_types/{$object_type}_body_insert.html", $this->page_data);
  }




  public function get_uploads_for_instrument($instrument_id,$start_date = false,$end_date = false){
    $results = $this->rep->summarize_uploads_by_instrument($instrument_id,$start_date,$end_date);
    $results_size = sizeof($results);
    $pluralizer = $results_size != 1 ? "s" : "";
    $status_message = '{$results_size} transaction{$pluralizer} returned';
    send_json_array($results);
  }
  
  public function get_uploads_for_proposal($proposal_id,$start_date = false,$end_date = false){
    $results = $this->rep->summarize_uploads_by_proposal($proposal_id,$start_date,$end_date);
    send_json_array($results);
  }
  
  public function get_uploads_for_user($eus_person_id, $start_date = false, $end_date = false){
    $results = $this->rep->summarize_uploads_by_user($eus_person_id,$start_date,$end_date);
    send_json_array($results);
  }
  
  public function get_proposals($proposal_name_fragment, $active = 'active'){
    $results = $this->eus->get_proposals_by_name($proposal_name_fragment,$active);
    send_json_array($results);
  }
  
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* Testing functionality                                     */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
  public function test_get_proposals($proposal_name_fragment, $active = 'active'){
    $results = $this->eus->get_proposals_by_name($proposal_name_fragment,$active);
    echo "<pre>";
    var_dump($results);
    echo "</pre>";
  }
  
  public function test_get_uploads_for_user($eus_person_id,$start_date = false,$end_date = false){
    $results = $this->rep->summarize_uploads_by_user($eus_person_id,$start_date,$end_date);
    echo "<pre>";
    var_dump($results);
    echo "</pre>";
    
  }
  
  public function test_get_uploads_for_instrument($eus_instrument_id,$start_date = false,$end_date = false){
    $results = $this->rep->summarize_uploads_by_instrument($eus_instrument_id,$start_date,$end_date);
    echo "<pre>";
    var_dump($results);
    echo "</pre>";
    
  }

  
  public function test_get_selected_objects($eus_person_id){
    $results = $this->rep->get_selected_objects($eus_person_id);
    echo "<pre>";
    var_dump($results);
    echo "</pre>";
  }
  
  public function test_get_object_list($object_type,$filter = ""){
    $results = $this->eus->get_object_list($object_type,$filter);
    echo "<pre>";
    var_dump($results);
    echo "</pre>";
  }
  

}

?>
