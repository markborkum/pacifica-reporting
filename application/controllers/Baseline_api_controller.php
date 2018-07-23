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

ini_set('memory_limit', '2048M');
ini_set('set_time_limit', 120);
ini_set('max_execution_time', 120);

/**
 *  Baseline_controller is a CI controller class that extends CI_controller
 *
 *  The *Baseline Controller* class provides low-level common functionality that
 *  is used by all of the other page controllers in the site
 *
 * @category Page_Controller
 * @package  Pacifica-reporting
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 * @link    http://github.com/EMSL-MSC/Pacifica-reporting
 * @access  public
 */
class Baseline_api_controller extends CI_Controller
{
    /**
     * Sets up the basics, loads up some common variables, defines a few
     * constants, parses and translates user_info
     *
     * @method __construct
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set($this->config->item('local_timezone'));
        // $this->metadata_url_base = str_replace('tcp:', 'http:', getenv('METADATA_PORT'));
        // $this->policy_url_base = str_replace('tcp:', 'http:', getenv('POLICY_PORT'));
        $this->load->model('System_setup_model', 'setup');
        $this->application_version = $this->config->item('application_version');
        $this->metadata_url_base = $this->config->item('metadata_server_base_url');
        $this->policy_url_base = $this->config->item('policy_server_base_url');
        $this->load->helper(array('user', 'url', 'html', 'myemsl_api', 'file_info'));
        if (!$this->user_id = get_user()) {
            //something is wrong with the authentication system or the user's log in
            $message = 'Unable to retrieve username from [REMOTE_USER]';
            show_error($message, 500, 'User Authorization Error or Server Misconfiguration in Auth System');
        }
        $this->page_address = implode('/', $this->uri->rsegments);
        $user_info = get_user_details($this->user_id);
        if (!$user_info) {
            $message = "Could not find a user with an EUS Person ID of {$this->user_id}";
            show_error($message, 401, 'User Authorization Error');
        }
        $this->username = $user_info['first_name'] != null ? $user_info['first_name'] : 'Anonymous Stranger';
        $this->fullname = "{$this->username} {$user_info['last_name']}";
        $this->is_emsl_staff = $user_info['emsl_employee'] == 'Y' ? true : false;
        // $this->is_emsl_staff = FALSE;
        $this->site_color = $this->config->item('site_color');

        $this->email = $user_info['email_address'];
        $user_info['full_name'] = $this->fullname;
        $user_info['network_id'] = !empty($user_info['network_id']) ? $user_info['network_id'] : 'unknown';
        $current_path_info = isset($_SERVER['PATH_INFO']) ? ltrim($_SERVER['PATH_INFO'], '/') : './';
        $this->nav_info['current_page_info']['logged_in_user'] = "{$this->fullname}";

        $this->page_data = array();
        $this->page_data['navData'] = $this->nav_info;
        $this->page_data['infoData'] = array('current_credentials' => $this->user_id, 'full_name' => $this->fullname);
        $this->page_data['username'] = $this->username;
        $this->page_data['fullname'] = $this->fullname;
        $this->page_data['load_prototype'] = false;
        $this->page_data['load_jquery'] = true;
        $this->page_data['site_identifier'] = $this->config->item('site_identifier');
        $this->page_data['site_slogan'] = $this->config->item('site_slogan');
        $this->status_site_base_url = $this->config->item('status_server_base_url');
        $this->site_identifier = $this->config->item('site_identifier');
        $this->site_slogan = $this->config->item('site_slogan');
        $this->controller_name = $this->uri->rsegment(1);
    }
}
