<?php
/**
 * CI Default Pacifica Config
 *
 * PHP Version 5
 *
 * @category Configuration
 * @package  Default_Pacifica
 * @author   Ken Auberry <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-reporting
 */

defined('BASEPATH') or exit('No direct script access allowed');

$config['local_timezone'] = 'America/Los_Angeles';

$config['application_config_file_path'] = '/etc/myemsl/';

$config['template'] = 'emsl';
$config['theme_name'] = 'myemsl';
$config['site_identifier'] = 'MyEMSL';
$config['site_slogan'] = 'Data Management for Science';
// $config['theme_name'] = 'pacifica';
// $config['site_identifier'] = 'dēmos';
$config['site_color'] = 'orange';
$config['jquery_script'] = "jquery-1.11.2.js";

$config['application_version'] = '0.99.9';
$config['debug_enabled'] = true;

$config['metadata_server_base_url'] = str_replace('tcp://', 'http://', getenv('METADATA_PORT'));
$config['policy_server_base_url'] = str_replace('tcp://', 'http://', getenv('POLICY_PORT'));
$config['status_server_base_url'] = getenv('STATUS_SITE_NAME') ? getenv('STATUS_SITE_NAME') : "http://statusdev.my.emsl.pnl.gov";
