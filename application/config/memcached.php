<?php
/**
 * CI Default Memcache Config
 *
 * PHP Version 5
 *
 * @category Configuration
 * @package  Default_Memcached
 * @author   Ken Auberry <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-reporting
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Memcached settings
| -------------------------------------------------------------------------
| Your Memcached servers can be specified below.
|
|	See: http://codeigniter.com/user_guide/libraries/caching.html#memcached
|
*/
$config = array(
    'default' => array(
        'hostname' => '127.0.0.1',
        'port'     => '11211',
        'weight'   => '1',
    ),
);
