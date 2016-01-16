<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Reporting Model                                                         */
/*                                                                             */
/*             functionality for summarizing upload and activity data.         */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class EUS_model extends CI_Model {
  
  function __construct(){
    parent::__construct();
    $this->load->helper(array('item'));

  }
  
  function get_proposals_by_name($proposal_name_fragment, $is_active = 'active'){
    $DB_eus = $this->load->database('eus_for_myemsl',true);
    $DB_eus->select(array(
      'proposal_id','title','group_id',
      'actual_start_date as start_date',
      'actual_end_date as end_date')
    );
    $DB_eus->where('closed_date');
    $DB_eus->where('title ILIKE',"%{$proposal_name_fragment}%");
    $query = $DB_eus->get('proposals');
    
    $results = array();
    
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $start_date = strtotime($row->start_date) ? date_create($row->start_date) : FALSE;
        $end_date = strtotime($row->end_date) ? date_create($row->end_date) : FALSE;
        
        $currently_active = $start_date && $start_date->getTimestamp() < time() ? TRUE : FALSE;
        $currently_active = $currently_active && (!$end_date || $end_date->getTimestamp() >= time()) ? TRUE : FALSE;
        
        if($is_active == 'active' && !$currently_active){
          continue;
        }
        
        $results[$row->proposal_id] = array(
          'title' => trim($row->title,'.'),
          'currently_active' => $currently_active ? "yes" : "no",
          'start_date' => $start_date ? $start_date->format('Y-m-d') : '---',
          'end_date' => $end_date ? $end_date->format('Y-m-d') : '---',
          'group_id' => $row->group_id
        );
      }
    }
    
    return $results;
    
    // echo "<pre>";
    // var_dump($results);
    // var_dump($query->result_array());
    // echo "</pre>";
  }
  

 
}
?>