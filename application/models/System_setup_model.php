<?php
/**
 * Pacifica
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-upload-status module provides an interface to
 *  the ingester status reporting backend, allowing users to view
 *  the current state of any uploads they may have performed, as
 *  well as enabling the download and retrieval of that data.
 *
 * PHP Version 5
 *
 * @package Pacifica-upload-status
 * @author  Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */

 // if (!is_cli()) exit('No URL-based access allowed');

/**
 * System setup model
 *
 * The **System_setup_model** configures the database backend and gets the
 * underlying system architecture in place during deployment.
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class System_setup_model extends CI_Model
{
    /**
     *  Class constructor.
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        if (file_exists(APPPATH."db_create_completed.txt")) {
            return;
        }
        //quickly assess the current system status
        try {
            $this->setup_db_structure();
        } catch (Exception $e) {
            log_message('error', "Could not create database instance. {$e->message}");
            $this->output->set_status_header(500);
        }
        $this->global_try_count = 0;

    }

    /**
     *  Create the initial database entry
     *
     * @param string $db_name The name of the db to create
     *
     * @return [type]   [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _check_and_create_database($db_name)
    {
        if($this->db->platform() != 'sqlite3') {
            if(!$this->dbutil->database_exists($db_name)) {
                log_message('info', 'Attempting to create database structure...');
                //db doesn't already exist, so make it
                if($this->dbforge->create_database($db_name)) {
                    log_message('info', "Created {$db_name} database instance");
                }else{
                    log_message('error', "Could not create database instance.");
                    $this->output->set_status_header(500);
                }
            }
        }else{
            log_message('info', 'DB Type is sqlite3, so we don\'t have to explicitly make the db file');
        }
    }
    /**
     *  Replacement for malfunctioning CI database util
     *
     * @param string $table_name [description]
     *
     * @return bool  does the table in question exist?
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _table_exists($table_name)
    {
        $query = $this->db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '{$table_name}';");
        return $query->num_rows() == 1;
    }

    /**
     *  Configure the table structures in the database
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function setup_db_structure()
    {
        //check for database existence
        $this->load->database('default');
        $this->load->dbforge();
        $this->load->dbutil();

        $this->_check_and_create_database($this->db->database);

        $dt_now = new DateTime();
        $dt_string = $dt_now->format('Y-m-d H:i:s');
        //ok, the database should be there now. Let's make some tables
        $db_create_object = array(
            'keys' => array(
                'reporting_object_groups' => array('group_id'),
                'reporting_selection_prefs' => array('eus_person_id', 'item_type', 'item_id', 'group_id'),
                'reporting_object_group_option_defaults' => array('option_type', 'option_default'),
                'reporting_object_group_options' => array('group_id', 'option_type')
            ),
            'defaults' => array(
                'reporting_object_group_option_defaults' => array(
                    array(
                        'option_type' => 'time_range',
                        'option_default' => '3-months',
                        'created' => $dt_string,
                        'updated' => $dt_string,
                    ),
                    array(
                        'option_type' => 'start_time',
                        'option_default' => 0,
                        'created' => $dt_string,
                        'updated' => $dt_string,
                    ),
                    array(
                        'option_type' => 'end_time',
                        'option_default' => 0,
                        'created' => $dt_string,
                        'updated' => $dt_string,
                    ),
                    array(
                        'option_type' => 'time_basis',
                        'option_default' => 'modified_time',
                        'created' => $dt_string,
                        'updated' => $dt_string,
                    )
                )
            ),
            'tables' => array(
                'reporting_object_groups' => array(
                    'group_id' => array(
                        'type' => 'INTEGER',
                        'auto_increment' => TRUE,
                        'unsigned' => TRUE
                    ),
                    'person_id' => array(
                        'type' => 'INTEGER'
                    ),
                    'group_name' => array(
                        'type' => 'VARCHAR'
                    ),
                    'group_type' => array(
                        'type' => 'VARCHAR'
                    ),
                    'ordering' => array(
                        'type' => 'INTEGER',
                        'default' => 0
                    ),
                    'created' => array(
                        'type' => 'TIMESTAMP',
                        'default' => 'now()'
                    ),
                    'updated' => array(
                        'type' => 'TIMESTAMP'
                    ),
                    'deleted' => array(
                        'type' => 'TIMESTAMP',
                        'null' => TRUE
                    )
                ),
                'reporting_selection_prefs' => array(
                    'eus_person_id' => array(
                        'type' => 'INTEGER'
                    ),
                    'item_type' => array(
                        'type' => 'VARCHAR'
                    ),
                    'item_id' => array(
                        'type' => 'VARCHAR'
                    ),
                    'group_id' => array(
                        'type' => 'INTEGER',
                        'default' => 0
                    ),
                    'created' => array(
                        'type' => 'TIMESTAMP',
                        'default' => 'now()'
                    ),
                    'updated' => array(
                        'type' => 'TIMESTAMP'
                    ),
                    'deleted' => array(
                        'type' => 'TIMESTAMP',
                        'null' => TRUE
                    )
                ),
                'reporting_object_group_option_defaults' => array(
                    'option_type' => array(
                        'type' => 'VARCHAR'
                    ),
                    'option_default' => array(
                        'type' => 'VARCHAR'
                    ),
                    'created' => array(
                        'type' => 'TIMESTAMP',
                        'default' => 'now()'
                    ),
                    'updated' => array(
                        'type' => 'TIMESTAMP'
                    ),
                    'deleted' => array(
                        'type' => 'TIMESTAMP',
                        'null' => TRUE
                    )
                ),
                'reporting_object_group_options' => array(
                    'group_id' => array(
                        'type' => 'BIGINT'
                    ),
                    'option_type' => array(
                        'type' => 'VARCHAR'
                    ),
                    'option_value' => array(
                        'type' => 'VARCHAR'
                    ),
                    'created' => array(
                        'type' => 'TIMESTAMP',
                        'default' => 'now()'
                    ),
                    'updated' => array(
                        'type' => 'TIMESTAMP'
                    ),
                    'deleted' => array(
                        'type' => 'TIMESTAMP',
                        'null' => TRUE
                    )
                )
            )
        );

        foreach($db_create_object['tables'] as $table_name => $table_def){
            if(!$this->_table_exists($table_name)) {
                $this->dbforge->add_field($table_def);
                if(array_key_exists($table_name, $db_create_object['keys'])) {
                    foreach($db_create_object['keys'][$table_name] as $key){
                        $this->dbforge->add_key($key, TRUE);
                    }
                }
                if($this->dbforge->create_table($table_name)) {
                    log_message("info", "Created '{$table_name}' table...");
                }
                if(array_key_exists($table_name, $db_create_object['defaults'])) {
                    $this->db->insert_batch($table_name, $db_create_object['defaults'][$table_name]);
                }
            }
        }
        touch(APPPATH."db_create_completed.txt");
    }

}
