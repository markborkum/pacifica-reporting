<?php
defined('BASEPATH') or exit('No Direct script access allowed');

class Migration_Add_website_prefs_schema extends CI_Migration {
  public function up(){
    $db_prefs = $this->load->database('website_prefs');
    $this->load->dbforge();


  }

  public function down(){

  }
}
 ?>
