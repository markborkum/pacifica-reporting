<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('Baseline_controller.php');

class Reporting extends Baseline_controller {
  public $last_update_time;
  
  
  function __construct() {
    parent::__construct();
    $this->load->model('Reporting_model','rep');
    // $this->load->model('EUS_model','eus');
    $this->load->library('myemsl-eus-library/EUS','','eus');
    $this->load->helper(array('network','file_info'));
    $this->last_update_time = get_last_update(APPPATH);
  }



  public function index(){
    redirect('reporting/view');
  }
  
  public function view(){
    $this->page_data['page_header'] = "Instrument Overview";
    $this->page_data['css_uris'] = array(
      "/resources/stylesheets/status.css",
      "/resources/stylesheets/status_style.css",
      "/resources/scripts/select2/select2.css"//,
      // "/resources/scripts/fancytree/skin-lion/ui.fancytree.css",
      // "/resources/stylesheets/file_directory_styling.css",
      // "/resources/stylesheets/bread_crumbs.css"      
    );
    $this->page_data['script_uris'] = array(
      "/resources/scripts/spinner/spin.min.js",
      "/resources/scripts/jquery-crypt/jquery.crypt.js",
      "/resources/scripts/status_common.js",
      "/resources/scripts/select2/select2.js",
      "/resources/scripts/moment.min.js"//,
      // "/resources/scripts/fancytree/jquery.fancytree-all.js",
      // "/resources/scripts/myemsl_file_download.js",
      // "/resources/scripts/emsl_mgmt_view.js",
    );

    $this->load->view('reporting_view.html',$this->page_data);
  }
  
  
  
  
  
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* API functionality for Ajax calls from UI                  */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
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
  
  
  public function test_get_selected_objects($eus_person_id){
    $results = $this->rep->get_selected_objects($eus_person_id);
    echo "<pre>";
    var_dump($results);
    echo "</pre>";
  }
  

}

?>
