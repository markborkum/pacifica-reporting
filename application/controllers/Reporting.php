<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
  This serves as a landing/redirect page for reporting functionality now
  Could probably just as easily do this with a routing command
*/


class Reporting extends CI_Controller {
  function __construct() {
    parent::__construct();
    $this->load->helper(array('url'));
  }

  public function index(){
    redirect('group/view');
  }

  public function group_view($object_type, $time_range = false, $start_date = false, $end_date = false, $time_basis = false){
    $url = rtrim("group/view/{$object_type}/{$time_range}/{$start_date}/{$end_date}/{$time_basis}","/");
    redirect($url,'location',301);
  }

  public function view($object_type, $time_range = false, $start_date = false, $end_date = false, $time_basis = false){
    $url = rtrim("item/view/{$object_type}/{$time_range}/{$start_date}/{$end_date}/{$time_basis}","/");
    redirect($url,'location',301);
  }

}

?>
