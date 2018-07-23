<?php

class MY_Loader extends CI_Loader
{
    /**
    * Database Loader
    *
    * @access    public
    * @param    string    the DB credentials
    * @param    bool    whether to return the DB object
    * @param    bool    whether to enable active record (this allows us to override the config setting)
    * @return    object
    */


    public function database($params = '', $return = false, $query_builder = null)
    {
        $ci =& get_instance();

        if ($return === false && $query_builder === null && isset($ci->db) && is_object($ci->db) && !empty($ci->db->conn_id)) {
            return false;
        }

        require_once(BASEPATH . 'database/DB.php');

        $db =& DB($params, $query_builder);

        $driver = config_item('subclass_prefix') . 'DB_' . $db->dbdriver . '_driver';
        $file = APPPATH . 'libraries/' . $driver . '.php';

        if (file_exists($file) === true && is_file($file) === true) {
            require_once($file);

            $dbo = new $driver(get_object_vars($db));
            $db = & $dbo;
        }

        if ($return === true) {
            return $db;
        }

        $ci->db = '';
        $ci->db = $db;

        return $this;
    }
}
