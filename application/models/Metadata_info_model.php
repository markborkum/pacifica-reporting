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

 defined('BASEPATH') OR exit('No direct script access allowed');

 /**
  *  Metadata Info Model
  *
  *  The **Metadata_info_model** class contains functionality for
  *  retrieving and updating information about object groups.
  *  It pulls data from both the Pacifica metadata server
  *  and the website_prefs databases.
  *
  * @category CI_Model
  * @package  Pacifica-reporting
  * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
  *
  * @license BSD https://opensource.org/licenses/BSD-3-Clause
  * @link    http://github.com/EMSL-MSC/Pacifica-reporting

  * @access public
  */
class Metadata_info_model extends CI_Model
{
    public $debug;
    public $group_id_list = FALSE;

    /**
     * Class constructor
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('item'));
        // $this->load->library('EUS', '', 'eus');
        $this->debug = $this->config->item('debug_enabled');
        $this->load->database('website_prefs');
    }//end __construct()

}
