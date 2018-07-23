<?php
class MY_DB_postgre_driver extends CI_DB_postgre_driver {

  public function __construct($params){
    parent::__construct($params);
    log_message('debug', 'Extended DB driver class instantiated!');
  }
  
  public function insert_id()
  {
      $v = pg_version($this->conn_id);
      $v = isset($v['server']) ? intval($v['server']) : 0; // 'server' key is only available since PosgreSQL 7.4
      $table	= (func_num_args() > 0) ? func_get_arg(0) : NULL;
      $column	= (func_num_args() > 1) ? func_get_arg(1) : NULL;

      if ($table === NULL && $v >= 8.1)
      {
          $sql = 'SELECT LASTVAL() AS ins_id';
      }
      elseif ($table !== NULL)
      {
          if ($column !== NULL && $v >= 8.0)
          {
              $sql = "SELECT pg_get_serial_sequence('{$table}', '{$column}') as seq";
              $query = $this->query($sql);
              $query = $query->row();
              $seq = $query->seq;
          }
          else
          {
              // seq_name passed in table parameter
              $seq = $table;
          }

          $sql = "SELECT CURRVAL('{$seq}') AS ins_id";
      }
      else
      {
          return pg_last_oid($this->result_id);
      }

      $query = $this->query($sql);
      $query = $query->row();
      return (int) $query->ins_id;
  }

}
?>
