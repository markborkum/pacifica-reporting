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
    $this->load->database();

  }
  
  
  
  
  function summarize_uploads_by_instrument($eus_instrument_id, $start_date, $end_date = false){
    //canonicalize start and end times (new short ternary operator form (see <http://php.net/manual/en/language.operators.comparison.php>))
    $start_date = false;
    $end_date = false;
    
    //both start and end times are filled in
    if(strtotime($start_date) && strtotime($end_date)){
      //check for validity and ordering
      $start_time = date_create($start_date);
      $end_time = date_create($end_date);
      if($start_time->getTimestamp() > $end_time->getTimestamp()){
        //out of order, try to flip
        $temp_start = clone $start_time;
        $temp_end = clone $end_time;
        $start_time = $temp_start;
        $end_time = $temp_end;
      }elseif($start_time->getTimestamp() == $end_time->getTimestamp()){
        //equal??? 
        $end_time->modify("+1 day");
      }
    }else{
      $start_time = date_create('00:00:00');
      $end_time = date_create('+1 day 00:00:00');
    }        
    
    //get transactions for time period
  }
  
  
  
  
  function summarize_uploads_by_user($eus_person_id){
    
  }
  
  
  
  
  function summarize_uploads_by_proposal($eus_proposal_id){
    
  }
  
  
  
  
  
  
}
?>