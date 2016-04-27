<?php

ini_set('memory_limit', '2048M');
class Baseline_controller extends CI_Controller
{
    public function __construct()
    {
        date_default_timezone_set('America/Los_Angeles');
        parent::__construct();
        $this->application_version = $this->config->item('application_version');
        $this->load->helper(array('user', 'url', 'html', 'myemsl', 'file_info'));
        $this->user_id = get_user();
    // $this->user_id = 43751;
    if (!$this->user_id) {
        //something is wrong with the authentication system or the user's log in
      $message = 'Unable to retrieve username from [REMOTE_USER]';
        show_error($message, 500, 'User Authorization Error or Server Misconfiguration in Auth System');
    }

        $this->page_address = implode('/', $this->uri->rsegments);

        $user_info = get_user_details_myemsl($this->user_id);
        if (!$user_info) {
            $message = "Could not find a user with an EUS Person ID of {$this->user_id}";
            show_error($message, 401, 'User Authorization Error');
        }
        $this->username = $user_info['first_name'] != null ? $user_info['first_name'] : 'Anonymous Stranger';
        $this->fullname = "{$this->username} {$user_info['last_name']}";
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
        $this->controller_name = $this->uri->rsegment(1);
    }
}
