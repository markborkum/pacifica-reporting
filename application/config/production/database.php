<?php
/**
 * CI Default Database
 *
 * PHP Version 5
 *
 * @category Configuration
 * @package  Default_Database
 * @author   Ken Auberry <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-reporting
 */

 if (! defined('BASEPATH')) exit('No direct script access allowed');
$db['default'] = array(
  'hostname' => "",
  'username' => "",
  'password' => "",
  'database' => "/exports/cart/sqlite/reporting.sqlite3",
  'dbdriver' => "sqlite3",
  'dbprefix' => "",
  'pconnect' => TRUE,
  'db_debug' => FALSE,
  'cache_on' => FALSE,
  'cachedir' => ""
);
