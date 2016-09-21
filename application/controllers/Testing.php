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
defined('BASEPATH') or exit('No direct script access allowed');
require_once 'Baseline_controller.php';

/**
 *  Testing is a CI controller class that extends Baseline_controller.
 *
 *  The **Testing** class contains a smattering of browser-accessible
 *  snippets of code that return ugly var_dumps so the developer can
 *  make sure AJAX calls, etc. are working properly
 *
 * @category Page_Controller
 * @package  Pacifica-reporting
 *
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 *
 * @link http://github.com/EMSL-MSC/Pacifica-reporting
 */
class Testing extends Baseline_controller
{
    public $last_update_time;
    public $accepted_object_types;
    public $accepted_time_basis_types;
    public $local_resources_folder;

    /**
     * Class constructor
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Group_info_model', 'gm');
        $this->load->model('Summary_model', 'summary');
        $this->load->model('Myemsl_model', 'myemsl');
        $this->load->library('EUS', '', 'eus');
        $this->load->helper(array('network', 'file_info', 'inflector', 'time', 'item', 'search_term', 'cookie'));
        $this->accepted_object_types = array('instrument', 'user', 'proposal');
        $this->accepted_time_basis_types = array('submit_time', 'create_time', 'modified_time');
        $this->local_resources_folder = $this->config->item('local_resources_folder');
    }

    /**
     * Test proposal retrieval from EUS
     *
     * @param string $proposal_name_fragment search term for
     *                                       finding a proposal
     * @param string $active                 retrieve only
     *                                       active proposals?
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_proposals($proposal_name_fragment, $active = 'active')
    {
        $results = $this->eus->get_proposals_by_name($proposal_name_fragment, $active);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * What items have been contributed by this user?
     *
     * @param integer $eus_person_id EMSL user id of the person in question
     * @param string  $start_date    Date range start (as YYYY-MM-DD)
     * @param string  $end_date      Date range end (as YYYY-MM-DD)
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_uploads_for_user($eus_person_id, $start_date = FALSE, $end_date = FALSE)
    {
        $results = $this->summary->summarize_uploads_by_user($eus_person_id, $start_date, $end_date);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * What items have been contributed by this list of users?
     *
     * @param string $eus_person_id_list List of user id's,
     *                                   separated by '-'
     *                                   (i.e. 43751-50274-38991)
     * @param string $start_date         Date range start (as YYYY-MM-DD)
     * @param string $end_date           Date range end (as YYYY-MM-DD)
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_uploads_for_user_list($eus_person_id_list, $start_date = FALSE, $end_date = FALSE)
    {
        $eus_person_id_list = explode('-', $eus_person_id_list);
        $results = $this->summary->summarize_uploads_by_user_list($eus_person_id_list, $start_date, $end_date, TRUE);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * What items have been contributed from this instrument?
     *
     * @param string $eus_instrument_id_list List of instrument id's,
     *                                       separated by '-'
     *                                       (i.e. 34105-34075)
     * @param string $start_date             Date range start (as YYYY-MM-DD)
     * @param string $end_date               Date range end (as YYYY-MM-DD)
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_uploads_for_instrument($eus_instrument_id_list, $start_date = FALSE, $end_date = FALSE)
    {
        $eus_instrument_id_list = explode('-', $eus_instrument_id_list);
        $results = $this->summary->summarize_uploads_by_instrument_list($eus_instrument_id_list, $start_date, $end_date, TRUE, 'modified_time');
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * [test_get_selected_objects description].
     *
     * @param [type] $eus_person_id [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_selected_objects($eus_person_id)
    {
        $results = $this->gm->get_selected_objects($eus_person_id);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * [test_get_selected_groups description].
     *
     * @param [type] $eus_person_id [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_selected_groups($eus_person_id)
    {
        $results = $this->gm->get_selected_groups($eus_person_id);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * [test_get_object_list description].
     *
     * @param [type] $object_type [description]
     * @param string $filter      [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_object_list($object_type, $filter = '')
    {
        $filter = parse_search_term($filter);
        $results = $this->eus->get_object_list($object_type, $filter);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * [get_transactions_for_user_list description].
     *
     * @param [type] $eus_person_id [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_transactions_for_user_list($eus_person_id)
    {
        $eus_person_id_list = array($eus_person_id);
        $default_eus_person_id_list = array(
            '43751', '50724',
        );
        $eus_person_id_list = array_unique(
            array_merge($eus_person_id_list, $default_eus_person_id_list)
        );

        $start_time = '2015-09-01 00:00:00';
        $end_time = '2015-11-13 23:59:59';

        $results = $this->summary->get_transactions_for_user_list($eus_person_id_list, $start_time, $end_time);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * [test_get_user_info_myemsl description].
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_user_info_myemsl()
    {
        $ui = $this->myemsl->get_user_info();
        echo json_encode($ui);
    }

    /**
     * [test_get_earliest_latest_list description].
     *
     * @param [type] $object_type [description]
     * @param [type] $group_id    [description]
     * @param [type] $time_basis  [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_earliest_latest_list($object_type, $group_id, $time_basis)
    {
        $group_info = $this->gm->get_group_info($group_id);
        echo '<pre>';
        var_dump($group_info);
        echo '</pre>';
        $results = $this->gm->earliest_latest_data_for_list($object_type, $group_info['item_list'], $time_basis);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * [test_get_group_info description].
     *
     * @param [type] $group_id [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_group_info($group_id)
    {
        $results = $this->gm->get_group_info($group_id);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * [test_get_items_for_group description].
     *
     * @param [type] $group_id [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_items_for_group($group_id)
    {
        $results = $this->gm->get_items_for_group($group_id);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * [test_get_files_from_group_list description].
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_files_from_group_list()
    {
        $group_list = array(338690, 394300, 10081, 391971, 34124, 1142, 1004, 1005, 34072,
                            1000001, 34134, 34105, 34180, 34176, 1032, 34076, 1000010,
                            34110, 34132, 34078, 0, 34000, 1000011, 1176, 1002, 1003,
                            34135, 1145, 34075, 34218, 34121, 34136, 34181, 431561, );
        $start_time = '2015-09-01 00:00:00';
        $end_time = '2015-11-13 23:59:59';

        $results = $this->summary->get_files_from_group_list($group_list, $start_time, $end_time);
    }

    /**
     * [test_get_transaction_info description].
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function test_get_transaction_info()
    {
        $transaction_list = array(
        1895, 1894, 1893, 1888, //,1887,1886,1885,1884,1880,
        // 1879,1878,1877,1876,1875,1874,1873,1872,1871,
        // 1870,1869,1868,1867,1866,1865,1864,1862,1861
        );
        $results = $this->rep->detailed_transaction_list($transaction_list);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }
}
