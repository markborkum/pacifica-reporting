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
  *  Group Info Model
  *
  *  The **Group_info_model** class contains functionality for
  *  retrieving and updating information about object groups.
  *  It pulls data from both the MyEMSL and website_prefs databases.
  *
  * @category CI_Model
  * @package  Pacifica-reporting
  * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
  *
  * @license BSD https://opensource.org/licenses/BSD-3-Clause
  * @link    http://github.com/EMSL-MSC/Pacifica-reporting

  * @uses   EUS EUS Database access library
  * @access public
  */
class Group_info_model extends CI_Model
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
        $this->load->library('EUS', '', 'eus');
        $this->debug = $this->config->item('debug_enabled');

    }//end __construct()

    /**
     * Retrieves various pieces of information on a given
     * group object. These include the last selected time_range,
     * last selected aggregation type (time_basis) [can be
     * submit_time, create_time, modified_time], and start/end
     * times for reporting period. If no values are found
     * in the reporting_object_group_options table, then suitable
     * default values are pulled from the
     * reporting_object_group_option_defaults table and merged.
     *
     * @param integer $group_id [description]
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_group_options($group_id)
    {
        $option_defaults = $this->get_group_option_defaults();
        $DB_prefs        = $this->load->database('website_prefs', TRUE);
        $query           = $DB_prefs->get_where('reporting_object_groups', array('group_id' => $group_id), 1);
        $options         = array();
        if ($query && $query->num_rows() > 0) {
            $options_query = $DB_prefs->get_where('reporting_object_group_options', array('group_id' => $group_id));
            if ($options_query && $options_query->num_rows() > 0) {
                foreach ($options_query->result() as $option_row) {
                    $options[$option_row->option_type] = $option_row->option_value;
                }
            }

            $group_info   = $query->row_array();
            $member_query = $DB_prefs->select('item_id')->get_where('reporting_selection_prefs', array('group_id' => $group_id));
            // var_dump($member_query->result_array());
            // echo "<br /><br />";
            if ($member_query && $member_query->num_rows() > 0) {
                foreach ($member_query->result() as $row) {
                    $group_info['item_list'][] = $row->item_id;
                }
            } else {
                $group_info['item_list'] = array();
            }

            $group_info['options_list'] = ($options + $option_defaults);
        }//end if

        return $group_info;

    }//end get_group_options()

    /**
     * Retrieves the full set of information about a specified group.
     * Combines options (from Group_info_model::get_group_options, above)
     * with earliest/latest item data from
     * Group_info_model::earliest_latest_data_for_list and
     * corrects the stored option start/end times if they
     * cross outside the boundaries defined by earliest/latest
     *
     * @param integer $group_id [description]
     *
     * @return array [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_group_info($group_id)
    {
        $group_info = $this->get_group_options($group_id);

        $earliest_latest = $this->earliest_latest_data_for_list(
            $group_info['group_type'],
            $group_info['item_list'],
            $group_info['options_list']['time_basis']
        );

        if ($earliest_latest) {
            extract($earliest_latest);
            $earliest_obj = new DateTime($earliest);
            $latest_obj   = new DateTime($latest);
            $group_info['time_list'] = $earliest_latest;
            $start_time_obj          = strtotime($group_info['options_list']['start_time']) ? new DateTime($group_info['options_list']['start_time']) : clone $earliest_obj;
            $end_time_obj            = strtotime($group_info['options_list']['end_time']) ? new DateTime($group_info['options_list']['end_time']) : clone $latest_obj;

            if ($end_time_obj > $latest_obj) {
                $end_time_obj = clone $latest_obj;
                $this->change_group_option($group_id, 'end_time', $end_time_obj->format('Y-m-d'));
                if ($start_time_obj < $earliest_obj OR $start_time_obj > $latest_obj) {
                    $start_time_obj = clone $latest_obj;
                    $start_time_obj->modify('-1 month');
                    $this->change_group_option($group_id, 'start_time', $start_time_obj->format('Y-m-d'));
                }
            } else if ($start_time_obj < $earliest_obj) {
                $start_time_obj = clone $earliest_obj;
                $this->change_group_option($group_id, 'start_time', $start_time_obj->format('Y-m-d'));
                if ($end_time_obj < $start_time_obj OR $end_time_obj > $latest_obj) {
                    $end_time_obj = clone $start_time_obj;
                    $end_time_obj->modify('+1 month');
                    $this->change_group_option($group_id, 'end_time', $end_time_obj->format('Y-m-d'));
                }
            }

            $group_info['options_list']['start_time'] = $start_time_obj->format('Y-m-d');
            $group_info['options_list']['end_time']   = $end_time_obj->format('Y-m-d');
        }//end if

        return $group_info;

    }//end get_group_info()


    /**
     * Retrieve a reasonable set of default options to be used for
     * newly defined groups until the user sets their own options
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_group_option_defaults()
    {
        $DB_prefs = $this->load->database('website_prefs', TRUE);
        $query    = $DB_prefs->get('reporting_object_group_option_defaults');
        $defaults = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                if($row->option_type == 'start_time' && $row->option_default = 0) {
                    $start_time          = new Datetime();
                    $row->option_default = $start_time->format('Y-m-d');
                }

                if($row->option_type == 'end_time' && $row->option_default = 0) {
                    $end_time = new Datetime();
                    $end_time->modify('-1 week');
                    $row->option_default = $end_time->format('Y-m-d');
                }

                $defaults[$row->option_type] = $row->option_default;
            }
        }

        return $defaults;

    }//end get_group_option_defaults()

    /**
     * Retrieve a list of instruments/proposals/users
     * for a specifid group.
     *
     * @param integer $group_id group_id to retrieve
     *
     * @return array [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_items_for_group($group_id)
    {
        $DB_prefs = $this->load->database('website_prefs', TRUE);
        $DB_prefs->select(array('item_type', 'item_id'));
        $query   = $DB_prefs->get_where('reporting_selection_prefs', array('group_id' => $group_id));
        $results = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $results[$row->item_type][] = $row->item_id;
            }
        }

        return $results;

    }//end get_items_for_group()


    /**
     * Backend database function to cause the creation
     * of a new, empty grouping object of a specific type,
     * owned by the specified user. If no group name is
     * supplied, generate a generic name using the object type.
     *
     * @param string  $object_type   instrument/proposal/user
     * @param integer $eus_person_id person_id for ownership
     * @param string  $group_name    optional group name for
     *                               later identification/display
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function make_new_group($object_type, $eus_person_id, $group_name = FALSE)
    {
        $DB_prefs   = $this->load->database('website_prefs', TRUE);
        $table_name = 'reporting_object_groups';
        // check the name and make sure it's unique for this user_id
        if (!$group_name) {
            $group_name = 'New '.ucwords($object_type).' Group';
        }

        $where_array = array(
                        'person_id'  => $eus_person_id,
                        'group_name' => $group_name,
                       );
        $check_query = $DB_prefs->where($where_array)->get($table_name);
        if ($check_query && $check_query->num_rows() > 0) {
            $d           = new DateTime();
            $group_name .= ' ['.$d->format('Y-m-d H:i:s').']';
        }

        $insert_data = array(
                        'person_id'  => $eus_person_id,
                        'group_name' => $group_name,
                        'group_type' => $object_type,
                       );
        $DB_prefs->insert($table_name, $insert_data);
        if ($DB_prefs->affected_rows() > 0) {
            $group_id   = $DB_prefs->insert_id();
            $group_info = $this->get_group_info($group_id);

            return $group_info;
        }

        return FALSE;

    }//end make_new_group()

    /**
     * Backend database function to change the name of
     * the specified group.
     *
     * @param integer $group_id   group_id to change
     * @param string  $group_name new group name
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function change_group_name($group_id, $group_name)
    {
        $new_group_info = FALSE;
        $DB_prefs       = $this->load->database('website_prefs', TRUE);
        $update_array   = array('group_name' => $group_name);
        $DB_prefs->where('group_id', $group_id)->set('group_name', $group_name);
        $DB_prefs->update('reporting_object_groups', $update_array);
        if ($DB_prefs->affected_rows() > 0) {
            $new_group_info = $this->get_group_info($group_id);
        }

        return $new_group_info;

    }//end change_group_name()

    /**
     * Backend database function to set/update options
     * for the specified group object.
     *
     * @param integer $group_id    Group id to be acted upon
     * @param string  $option_type The option to be changed
     * @param string  $value       The new value to be set
     *
     * @return array summary of group, value name, changed value
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function change_group_option($group_id, $option_type, $value)
    {
        $DB_prefs     = $this->load->database('website_prefs', TRUE);
        $table_name   = 'reporting_object_group_options';
        $where_array  = array(
                         'group_id'    => $group_id,
                         'option_type' => $option_type,
                        );
        $update_array = array('option_value' => $value);
        $query        = $DB_prefs->where($where_array)->get($table_name);
        if ($query && $query->num_rows() > 0) {
            $DB_prefs->where($where_array)->update($table_name, $update_array);
        } else {
            $DB_prefs->insert($table_name, ($update_array + $where_array));
        }

        if ($DB_prefs->affected_rows() > 0) {
            return ($update_array + $where_array);
        }

        return FALSE;

    }//end change_group_option()

    /**
     * Retrieve a set of objects associated with a given
     * combination of person/type/group_id
     *
     * @param integer $eus_person_id person_id to search
     * @param string  $restrict_type specified if only a certain
     *                               item type if to be returned
     * @param integer $group_id      further limit by group_id
     *
     * @return array collection of items, separated by item_type
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_selected_objects($eus_person_id, $restrict_type = FALSE, $group_id = FALSE)
    {
        $DB_prefs = $this->load->database('website_prefs', TRUE);
        $DB_prefs->select(array('eus_person_id', 'item_type', 'item_id', 'group_id'));
        $DB_prefs->where('deleted is null');
        if (!empty($group_id)) {
            $DB_prefs->where('group_id', $group_id);
        }

        if (!empty($restrict_type)) {
            $DB_prefs->where('item_type', $restrict_type);
        }

        $DB_prefs->order_by('item_type');
        $query   = $DB_prefs->get_where('reporting_selection_prefs', array('eus_person_id' => $eus_person_id));
        $results = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                switch ($row->item_type) {
                case 'instrument':
                    $group_list = $this->get_instrument_group_list($row->item_id);
                    break;
                case 'proposal':
                    $group_list = $this->get_proposal_group_list($row->item_id);
                    break;
                default:
                    $group_list = $row->item_id;
                }

                $item_id = strval($row->item_id);
                $results[$row->item_type][$item_id] = $group_list;
            }
        }

        return $results;

    }//end get_selected_objects()

    /**
     * Retrieve group info for all the group objects owned
     * by a specified user. Can be filtered by type, as
     * well as limited in scope to exclude full group info
     *
     * @param integer $eus_person_id  person_id to search
     * @param string  $restrict_type  instrument/proposal/user
     * @param boolean $get_group_info toggle to control depth
     *                                of information returned
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_selected_groups($eus_person_id, $restrict_type = FALSE, $get_group_info = TRUE)
    {
        $this->benchmark->mark('get_selected_groups_start');
        $results  = array();
        $DB_prefs = $this->load->database('website_prefs', TRUE);
        $DB_prefs->select('g.group_id');
        $person_array = array($eus_person_id);
        $DB_prefs->where_in('g.person_id', $person_array);
        $DB_prefs->where('g.deleted is NULL');
        if ($restrict_type) {
            $DB_prefs->where('g.group_type', $restrict_type);
        }

        $DB_prefs->order_by('ordering ASC');
        $query         = $DB_prefs->get('reporting_object_groups g');
        $group_id_list = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                if($get_group_info) {
                    $group_info = $this->get_group_info($row->group_id);
                }else{
                    $group_info = $this->get_group_options($row->group_id);
                }

                $results[$row->group_id] = $group_info;
            }
        }

        $this->benchmark->mark('get_selected_groups_end');
        $this->group_id_list = $results;

        return $results;

    }//end get_selected_groups()

    /**
     * Backend database function to remove access to a
     * specified group object. Can be soft-deleted
     * (disabled by adding a *deleted* date) or completely
     * removed from the table.
     *
     * @param integer $group_id    [description]
     * @param boolean $full_delete Set FALSE for soft delete
     *                             Set TRUE for row drop by DELETE
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function remove_group_object($group_id, $full_delete = FALSE)
    {
        $tables       = array(
                         'reporting_object_group_options',
                         'reporting_selection_prefs',
                         'reporting_object_groups',
                        );
        $DB_prefs     = $this->load->database('website_prefs', TRUE);
        $where_clause = array('group_id' => $group_id);

        if ($full_delete) {
            $DB_prefs->delete($tables, $where_clause);
        } else {
            // just update deleted_at column
            foreach ($tables as $table_name) {
                $DB_prefs->update($table_name, array('deleted' => 'now()'), $where_clause);
            }
        }

    }//end remove_group_object()

    /**
     * Backend database function to to add/remove objects
     * as part of a specified group.
     *
     * @param string  $object_type instrument/proposal/user
     * @param array   $object_list contains items with
     *                             associated actions
     * @param integer $group_id    group on which to operate
     *
     * @return boolean success/failure code
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function update_object_preferences($object_type, $object_list, $group_id)
    {
        $table        = 'reporting_selection_prefs';
        $DB_prefs     = $this->load->database('website_prefs', TRUE);
        $additions    = array();
        $removals     = array();
        $existing     = array();
        $where_clause = array(
                         'item_type'     => $object_type,
                         'eus_person_id' => $this->user_id,
                        );
        $status = FALSE;
        if ($group_id && is_numeric($group_id)) {
            $where_clause['group_id'] = $group_id;
        }

        $DB_prefs->select('item_id');
        $check_query = $DB_prefs->get_where($table, $where_clause);
        if ($check_query && $check_query->num_rows() > 0) {
            foreach ($check_query->result() as $row) {
                $existing[] = $row->item_id;
            }
        }

        foreach ($object_list as $item) {
            extract($item);
            if ($action == 'add') {
                $additions[] = $object_id;
            } else if ($action == 'remove') {
                $removals[] = $object_id;
            } else {
                continue;
            }

            $additions = array_diff($additions, $existing);
            $removals  = array_intersect($removals, $existing);

            if (!empty($additions)) {
                foreach ($additions as $object_id) {
                    $insert_object = array(
                                      'eus_person_id' => $this->user_id,
                                      'item_type'     => $object_type,
                                      'item_id'       => strval($object_id),
                                      'group_id'      => $group_id,
                                     );
                    $DB_prefs->insert($table, $insert_object);
                    if($DB_prefs->affected_rows() > 0) {
                        $status = TRUE;
                    }
                }
            }

            if (!empty($removals)) {
                $my_where = $where_clause;
                foreach ($removals as $object_id) {
                    $my_where['item_id'] = strval($object_id);
                    $DB_prefs->where($my_where)->delete($table);
                    if($DB_prefs->affected_rows() > 0) {
                        $status = TRUE;
                    }
                }
            }
        }//end foreach

        return $status;

    }//end update_object_preferences()


    /**
     * Retrieve the earliest and latest objects of a given type
     * and time_basis within a group of object_id's
     *
     * @param string $object_type    the type of object to retrieve
     *                               [instrument/proposal/user]
     * @param array  $object_id_list array of object id's to affect
     * @param string $time_basis     one of created_date, modified_date
     *                               submitted_date
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function earliest_latest_data_for_list($object_type, $object_id_list, $time_basis)
    {
        $group_list_retrieval_fn_name = "get_{$object_type}_group_list";
        $time_basis = str_replace('_time', '_date', $time_basis);

        $spread = $this->_available_item_spread_general(
            $object_id_list,
            $time_basis,
            $object_type,
            $group_list_retrieval_fn_name
        );

        return $spread;

    }//end earliest_latest_data_for_list()


    /**
     * Private worker function to get the earliest/latest items
     * in a given list of objects
     *
     * @param array  $object_id_list               [description]
     * @param string $time_basis                   one of created_date, modified_date
     *                                             submitted_date
     * @param string $group_type                   instrument/proposal/user
     * @param string $group_list_retrieval_fn_name the name of the function to calls
     *                                             upon to get the myemsl internal
     *                                             groups associated with a given
     *                                             object
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _available_item_spread_general($object_id_list, $time_basis, $group_type, $group_list_retrieval_fn_name = FALSE)
    {
        $return_array = FALSE;
        if (empty($object_id_list)) {
            return FALSE;
        }

        $latest_time   = FALSE;
        $earliest_time = FALSE;
        if (in_array($group_type, array('instrument', 'proposal'))) {
            $group_collection = array();
            // echo "\n* * * * * * * group_list * * * * * * \n\n";
            foreach ($object_id_list as $object_id) {
                $group_collection += $this->$group_list_retrieval_fn_name($object_id);
                // var_dump($group_collection);
            }

            $group_list = array_keys($group_collection);
            if(empty($group_list)) {
                return FALSE;
            }

            $this->db->where_in('group_id', $group_list);
        } else if ($group_type == 'user') {
            $this->db->where_in('submitter', $object_id_list);
        }

        $this->db->select(
            array(
            // "'2000-01-01' as earliest",
             "MIN({$time_basis}) as earliest",
             "MAX({$time_basis}) as latest",
            )
        );
        $query = $this->db->get(ITEM_CACHE);
        // echo $this->db->last_query();
        if ($query && $query->num_rows() > 0 OR !empty($query->row()->latest_upload)) {
            $row           = $query->row_array();
            $earliest_time = !empty($row['earliest']) ? new DateTime($row['earliest']) : FALSE;
            $latest_time   = !empty($row['latest']) ? new DateTime($row['latest']) : FALSE;
            if (!$earliest_time && !$latest_time) {
                return FALSE;
            }

            $return_array = array(
                             'earliest' => $earliest_time->format('Y-m-d H:i'),
                             'latest'   => $latest_time->format('Y-m-d H:i'),
                            );
        }

        return $return_array;

    }//end _available_item_spread_general()


    /**
     * Helper function to get the earliest/latest
     * spread for a given set of instruments.
     *
     * @param array  $object_id_list list of instruments to scan
     * @param string $time_basis     one of created_date, modified_date
     *                                submitted_date
     *
     * @uses   Group_info_model::_available_item_spread_general
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _available_instrument_data_spread($object_id_list, $time_basis)
    {
        $group_list_retrieval_fn_name = 'get_instrument_group_list';

        return $this->_available_item_spread_general($object_id_list, $time_basis, 'instrument', $group_list_retrieval_fn_name);

    }//end _available_instrument_data_spread()


    /**
     * Helper function to get the earliest/latest
     * spread for a given set of proposals.
     *
     * @param array  $object_id_list list of proposals to scan
     * @param string $time_basis     one of created_date, modified_date
     *                                submitted_date
     *
     * @uses   Group_info_model::_available_item_spread_general
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _available_proposal_data_spread($object_id_list, $time_basis)
    {
        $group_list_retrieval_fn_name = 'get_proposal_group_list';

        return $this->_available_item_spread_general($object_id_list, $time_basis, 'proposal', $group_list_retrieval_fn_name);

    }//end _available_proposal_data_spread()


    /**
     * Helper function to get the earliest/latest
     * spread for a given set of users.
     *
     * @param array  $object_id_list list of users to scan
     * @param string $time_basis     one of created_date, modified_date
     *                                submitted_date
     *
     * @uses   Group_info_model::_available_item_spread_general
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _available_user_data_spread($object_id_list, $time_basis)
    {
        $return_array = FALSE;
        if (empty($object_id_list)) {
            return FALSE;
        }

        $this->db->select(
            array(
             "earliest_{$time_basis} as earliest",
             "latest_{$time_basis} as latest",
            )
        );

        $this->db->where('t.stime is not null');
        $this->db->where_in('submitter', $object_id_list);
        $this->db->from('transactions t')->limit(1);
        $this->db->join('files f', 't.transaction = f.transaction');
        $query = $this->db->get();

        if ($query && $query->num_rows() > 0 OR !empty($query->row()->latest_upload)) {
            $row           = $query->row_array();
            $earliest_time = !empty($row['earliest']) ? new DateTime($row['earliest']) : FALSE;
            $latest_time   = !empty($row['latest']) ? new DateTime($row['latest']) : FALSE;
            if (!$earliest_time && !$latest_time) {
                return FALSE;
            }

            $return_array = array(
                             'earliest' => $earliest_time->format('Y-m-d H:i'),
                             'latest'   => $latest_time->format('Y-m-d H:i'),
                            );
        }

        return $return_array;

    }//end _available_user_data_spread()


    /**
     * Retrieve a list of pertinent Myemsl internal groups
     * for a given proposal name search term.
     * Further limits based on staff/non-staff classifications
     *
     * @param string $proposal_id_filter search term to filter on
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_proposal_group_list($proposal_id_filter = '')
    {
        $is_emsl_staff = $this->is_emsl_staff;
        $DB_myemsl = $this->load->database('default', TRUE);
        $DB_myemsl->select(array('group_id', 'name as proposal_id'))->where('type', 'proposal');
        $proposals_available = FALSE;
        if(!$is_emsl_staff) {
            $proposals_available = $this->eus->get_proposals_for_user($this->user_id);
        }

        if (!empty($proposal_id_filter)) {
            if (is_array($proposal_id_filter)) {
                $DB_myemsl->where_in('name', $proposal_id_filter);
            } else {
                $DB_myemsl->where('name', $proposal_id_filter);
            }
        }

        $query = $DB_myemsl->get('groups');

        $results_by_proposal = array();
        if ($query && $query->num_rows()) {
            foreach ($query->result() as $row) {
                if(!$is_emsl_staff && in_array($row->proposal_id, $proposals_available)) {
                    $results_by_proposal[$row->group_id] = $row->proposal_id;
                }else if($is_emsl_staff) {
                    $results_by_proposal[$row->group_id] = $row->proposal_id;
                }
            }
        }

        $this->group_id_list = $results_by_proposal;

        return $results_by_proposal;

    }//end get_proposal_group_list()


    /**
     * Retrieve a list of pertinent Myemsl internal groups
     * for a given instrument name search term.
     * Further limits based on staff/non-staff classifications
     *
     * @param string $inst_id_filter search term to filter on
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_instrument_group_list($inst_id_filter = '')
    {
        // $e = new Exception();
        // var_dump($e->getTraceAsString());
        $DB_myemsl = $this->load->database('default', TRUE);
        $DB_myemsl->select(array('group_id', 'name', 'type'));
        if (!empty($inst_id_filter)) {
            $where_clause = array(
                             'type' => 'omics.dms.instrument_id',
                             'name' => $inst_id_filter,
                            );
            $DB_myemsl->where($where_clause);
            $DB_myemsl->or_where('type', "Instrument.{$inst_id_filter}");
        } else {
            $where_clause = "(type = 'omics.dms.instrument_id' or type ilike 'instrument.%') and name not in ('foo')";
            $DB_myemsl->where($where_clause);
        }

        $query = $DB_myemsl->order_by('name')->get('groups');
        $results_by_inst_id = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                if ($row->type == 'omics.dms.instrument_id') {
                    $inst_id = intval($row->name);
                } else if (strpos($row->type, 'Instrument.') >= 0) {
                    $inst_id = intval(str_replace('Instrument.', '', $row->type));
                } else {
                    continue;
                }

                $results_by_inst_id[$inst_id][$row->group_id] = $row->name;
            }
        }

        if (!empty($inst_id_filter) && is_numeric($inst_id_filter) && array_key_exists($inst_id_filter, $results_by_inst_id)) {
            $results = $results_by_inst_id[$inst_id_filter];
        } else {
            $results = $results_by_inst_id;
        }

        $this->group_id_list = $results;

        return $results;

    }//end get_instrument_group_list()

}//end class
