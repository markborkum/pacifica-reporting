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
 *  Reporting is a CI controller class that extends Baseline_controller
 *
 *  The *Reporting* class contains largely deprecated functionality, and will likely
 *  be removed in a later release
 *
 * @category Page_Controller
 * @package  Pacifica-reporting
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 * @link    http://github.com/EMSL-MSC/Pacifica-reporting

 * @access public
 */
class Reporting extends CI_Controller
{

    /**
     * [__construct description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url'));

    }//end __construct()

    /**
     * Grabs root level calls to this controller and redirects them to
     * *Group::view*
     *
     * @return none
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function index()
    {
        redirect('group/view');

    }//end index()

    /**
     * [group_view description]
     *
     * @param string  $object_type [description]
     * @param boolean $time_range  [description]
     * @param boolean $start_date  [description]
     * @param boolean $end_date    [description]
     * @param boolean $time_basis  [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function group_view(
        $object_type,
        $time_range = FALSE,
        $start_date = FALSE,
        $end_date = FALSE,
        $time_basis = FALSE
    )
    {
        $url = "group/view/{$object_type}/{$time_range}/";
        $url += "{$start_date}/{$end_date}/{$time_basis}";
        $url = rtrim($url, "/");
        redirect($url, 'location', 301);

    }//end group_view()

    /**
     * [view description]
     *
     * @param string  $object_type [description]
     * @param boolean $time_range  [description]
     * @param boolean $start_date  [description]
     * @param boolean $end_date    [description]
     * @param boolean $time_basis  [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function view(
        $object_type,
        $time_range = FALSE,
        $start_date = FALSE,
        $end_date = FALSE,
        $time_basis = FALSE
    )
    {
        $url = "item/view/{$object_type}/{$time_range}/";
        $url += "{$start_date}/{$end_date}/{$time_basis}";
        $url = rtrim($url, "/");
    }//end view()


}//end class
