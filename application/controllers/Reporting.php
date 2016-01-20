<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('Baseline_controller.php');

class Reporting extends Baseline_controller {
  function __construct() {
    parent::__construct();
    $this->load->model('Reporting_model','rep');
    // $this->load->model('EUS_model','eus');
    $this->load->library('myemsl-eus-library/EUS','','eus');
    $this->load->helper(array('network'));
  }



  public function index(){
    $this->rep->summarize_uploads_by_instrument(34218, '2015-12-01');
  }

  public function get_uploads_for_instrument($instrument_id,$start_date,$end_date = false){
    $results = $this->rep->summarize_uploads_by_instrument($instrument_id,$start_date,$end_date);
    $results_size = sizeof($results);
    $pluralizer = $results_size != 1 ? "s" : "";
    $status_message = '{$results_size} transaction{$pluralizer} returned';
    
    send_json_array($results);
  }
  
  public function get_uploads_for_proposal($proposal_id,$start_date,$end_date = false){
    $results = $this->rep->summarize_uploads_by_proposal($proposal_id,$start_date,$end_date);
    echo "<pre>";
    var_dump($results);
    echo "</pre>";
  }
  
  public function get_proposals($proposal_name_fragment,$active = 'active'){
    $results = $this->eus->get_proposals_by_name($proposal_name_fragment,$active);
    send_json_array($results);
  }
  
  
  
  public function test_get_proposals($proposal_name_fragment, $active = 'active'){
    $this->eus->get_proposals_by_name($proposal_name_fragment,$active);
  }
  
  public function test_get_uploads_for_user($eus_person_id,$start_date = false,$end_date = false){
    $results = $this->rep->summarize_uploads_by_user($eus_person_id,$start_date,$end_date);
    echo "<pre>";
    var_dump($results);
    echo "</pre>";
    
  }

}

?>
