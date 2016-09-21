<?php
/**
 * Pacifica
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-Reporting module provides an interface for
 *  concerned and interested parties to view the current
 *  contribution status of any and all instruments in the
 *  system. The reporting interface can be customized and
 *  filtered streamline the report to fit any level of user,
 *  from managers through instrument operators.
 *
 * PHP version 5.5
 *
 * @package Pacifica-reporting
 *
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 *
 * @link http://github.com/EMSL-MSC/Pacifica-reporting
 */

 require_once APPPATH.'libraries/Requests.php';

 /**
  *  MyEMSL Model
  *
  *  The **Myemsl_model** class contains functionality
  *  dealing with MyEMSL API Access calls, etc.
  *  Uses the [**Requests** library](http://requests.ryanmccue.info)
  *  to allow easier calls into the web API stack
  *
  * @category CI_Model
  * @package  Pacifica-reporting
  * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
  *
  * @license BSD https://opensource.org/licenses/BSD-3-Clause
  * @link    http://github.com/EMSL-MSC/Pacifica-reporting

  * @uses EUS                EUS Database access library
  * @uses Libraries/Requests Requests Library from <http://requests.ryanmccue.info>
  *
  * @access public
  */
class Myemsl_model extends CI_Model
{
    /**
     *  Class constructor
     *
     *  @method __construct
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    function __construct()
    {
        parent::__construct();
        $this->load->helper('myemsl');
        Requests::register_autoloader();
        $this->myemsl_ini = read_myemsl_config_file('general');

    }//end __construct()

    /**
     *  Retrieve an array of information about the current
     *  user from the MyEMSL user API
     *
     *  @method get_user_info
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    function get_user_info()
    {
        // $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? "https" : "http";
        $protocol = "http";
        $basedir  = 'myemsl';
        // $url_base =  dirname(dirname($this->myemsl_ini['getuser']['prefix']));
        $url_base = "{$protocol}://localhost";
        $options  = array(
                     'verify'          => FALSE,
                     'timeout'         => 60,
                     'connect_timeout' => 30,
                    );
        $headers  = array();

        foreach($_COOKIE as $cookie_name => $cookie_value){
              $headers[] = "{$cookie_name}={$cookie_value}";
        }

        $headers = array('Cookie' => implode(';', $headers));
        $session = new Requests_Session($url_base, $headers, array(), $options);

        try{
              $response  = $session->get('/myemsl/userinfo');
              $user_info = json_decode($response->body, TRUE);
        }catch(Exception $e){
              $user_info = array('error' => 'Unable to retrieve User Information');
              return $user_info;
        }

        $DB_myemsl = $this->load->database('default', TRUE);

        // go retrieve the instrument/group lookup table
        $DB_myemsl->like('type', 'Instrument.')->or_like('type', 'omics.dms.instrument');
        $query = $DB_myemsl->get('groups');

        $inst_group_lookup = array();

        if($query && $query->num_rows() > 0) {
            foreach($query->result() as $row){
                if($row->type == 'omics.dms.instrument') {
                    $inst_id = intval($row->group_id);
                }else if(strpos($row->type, 'Instrument.') >= 0) {
                    $inst_id = intval(str_replace('Instrument.', '', $row->type));
                }else{
                    continue;
                }

                $inst_group_lookup[$inst_id]['groups'][] = intval($row->group_id);
            }
        }

        $new_instruments_list = array();

        foreach($user_info['instruments'] as $eus_instrument_id => $inst_info){
              $new_instruments_list[$eus_instrument_id] = $inst_info;
            if(array_key_exists($eus_instrument_id, $inst_group_lookup)) {
                $new_instruments_list[$eus_instrument_id]['groups'] = $inst_group_lookup[$eus_instrument_id]['groups'];
            }else{
                $new_instruments_list[$eus_instrument_id]['groups'] = array();
            }
        }

        $user_info['instruments'] = $new_instruments_list;

        return $user_info;

    }//end get_user_info()


}//end class
