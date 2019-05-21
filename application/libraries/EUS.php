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
 *  This file contains a number of common functions related to
 *  file info and handling.
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

 /**
 *  EUS Access Library
 *
 *  The **EUS** library contains functionality
 *  for retrieving data from the EMSL User System.
 *
 * @category CI_Library
 * @package  Pacifica-reporting
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 * @link    http://github.com/EMSL-MSC/Pacifica-reporting
 *
 * @access public
 */
class EUS
{
    /**
     *  Class level var for the existing
     *  CodeIgniter object instance
     *
     *  @var object
     */
    protected $CI;

    /**
     *  Configuration container array
     *
     *  @var array
     */
    protected $myemsl_array;

    /**
     *  Class constructor
     *
     *  Sets up the various table names and database
     *  connection information for talking to EUS
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        define('INST_TABLE', 'instruments');
        define('INST_PROJECT_XREF', 'project_instruments');
        define('PROJECTS_TABLE', 'projects');
        define('PROJECT_MEMBERS', 'project_members');
        define('USERS_TABLE', 'users');

        if (!$this->CI->load->database('eus_for_myemsl')) {
            $myemsl_array = parse_ini_file('/etc/myemsl/general.ini', TRUE);
            $db_config = array(
            'hostname' => $myemsl_array['metadata']['host'],
            'username' => $myemsl_array['metadata']['user'],
            'password' => $myemsl_array['metadata']['password'],
            'database' => $myemsl_array['metadata']['database'],
            'dbdriver' => 'postgre',
            'dbprefix' => 'eus.',
            'pconnect' => TRUE,
            'db_debug' => TRUE,
            'cache_on' => FALSE,
            'cachedir' => '',
            );
            $this->CI->load->database($db_config);
        }
    }

    /**
     *  Retrieve a filtered list of instruments as listed in
     *  the EMSL Resource System (ERS)
     *
     *  @param boolean $unused_only only return the inactive instruments
     *  @param string  $filter      search term for the search
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_ers_instruments_list($unused_only = FALSE, $filter = '')
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', TRUE);

        $select_array = array(
        'instrument_id',
        'instrument_name as instrument_description',
        'name_short as instrument_name_short',
        'COALESCE(`eus_display_name`,`instrument_name`) as display_name',
        'last_change_date as last_updated'
        );

        if (!empty($filter)) {
            //check for numeric only and use in instrument_id?
            $DB_ers->like('instrument_name', $filter);
            $DB_ers->or_like('name_short', $filter);
            $DB_ers->or_like('eus_display_name', $filter);
        }

        $DB_ers->select($select_array, TRUE)->where('active_sw', 'Y');
        $DB_ers->order_by('eus_display_name');

        $query = $DB_ers->get(INST_TABLE);
        $results = array();
        $categorized_results = array();

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $inst_id = $row['instrument_id'];
                unset($row['instrument_id']);
                $results[$inst_id] = $row;
                $display_name_info = explode(':', $row['display_name']);
                $display_name_type = sizeof($display_name_info) > 1 ? array_shift($display_name_info) : '';
                $row['display_name'] = trim($display_name_info[0]);
                $inst_info = explode(':', $row['instrument_description']);
                $inst_type = sizeof($inst_info) > 1 ? array_shift($inst_info) : 'Other';
                $inst_desc = trim($inst_info[0]);

                $row['instrument_description'] = $inst_desc;
                $categorized_results[$inst_type][$inst_id] = $row;
            }
        }

        return $categorized_results;
    }

    /**
     *  Retrieve a filtered list of users from EUS
     *
     *  @param string $filter search term string
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_eus_user_list($filter = '')
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', TRUE);

        $select_array = array(
        'person_id as eus_id', 'first_name', 'last_name', 'email_address'
        );

        if (!empty($filter)) {
            $DB_ers->like('first_name', $filter)->or_like('last_name', $filter)->or_like('email_address', $filter);
        }

        $query = $DB_ers->select($select_array)->get(USERS_TABLE);

        $results_array = array(
            'success' => FALSE,
            'message' => "No EUS users found using the filter '*{$filter}*'",
              'names' => array()
        );

        if ($query && $query->num_rows() > 0) {
            $plural_mod = $query->num_rows() > 1 ? 's' : '';
            $results_array['message'] = $query->num_rows()." EUS user{$plural_mod} found with filter '*{$filter}*'";
            $results_array['success'] = TRUE;
            foreach ($query->result() as $row) {
                $display_name = ucwords("{$row->first_name} {$row->last_name}");
                $display_name .= !empty($row->email_address) ? " <{$row->email_address}>" : '';
                $name_components = array(
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'email_address' => $row->email_address,
                );
                foreach (array_keys($name_components) as $key_name) {
                    $comp = $name_components[$key_name];
                    $comp = preg_replace("/(.*)({$filter})(.*)/i", '$1<span class="hilite">$2</span>$3', $comp);
                    $marked_components[$key_name] = $comp;
                }
                $marked_up_display_name = ucwords("{$marked_components['first_name']} {$marked_components['last_name']}");
                $marked_up_display_name .= !empty($name_components['email_address']) ? " <{$marked_components['email_address']}>" : '';

                $results_array['names'][$row->eus_id] = array(
                'eus_id' => $row->eus_id,
                'first_name' => ucfirst($row->first_name),
                'last_name' => ucfirst($row->last_name),
                'email_address' => $row->email_address,
                'display_name' => $display_name,
                'marked_up_display_name' => $marked_up_display_name
                );
            }
        }

        return $results_array;
    }

    /**
     *  Retrieve a filtered list of instruments that are associated
     *  with a given EUS project
     *
     *  @param string $eus_project_id project id in question
     *  @param string $filter          search term string
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_instruments_for_project($eus_project_id, $filter = '')
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', TRUE);

        $result_array = array(
        'success' => FALSE,
        'message' => '',
        'instruments' => array(),
        );

        $closing_date = new DateTime();
        $closing_date->modify('-6 months');

        $where_array = array(
        'project_id' => $eus_project_id,
        'actual_end_date <' => $closing_date->format('Y-m-d')
        );
        $DB_ers->where($where_array);

        $prop_exists = $DB_ers->count_all_results(PROJECTS_TABLE) > 0 ? TRUE : FALSE;

        if (!$prop_exists) {
            $result_array['message'] = "No project with ID = {$eus_project_id} was found";

            return $result_array;
        }

        $instrument_list = array();

        $select_array = array(
            'i.instrument_id', 'i.eus_display_name',
        );

        $DB_ers->select($select_array)->from(INST_TABLE.' i');
        $DB_ers->join(INST_PROJECT_XREF.' pi', 'i.instrument_id = pi.instrument_id');

        if (!empty($filter)) {
            $DB_ers->like('i.eus_display_name', $filter);
        }

        $inst_query = $DB_ers->get();

        if ($inst_query && $inst_query->num_rows() > 0) {
            $plural_mod = $inst_query->num_rows() > 1 ? 's' : '';
            $result_array['success'] = TRUE;
            $result_array['message'] = $inst_query->num_rows()." instrument{$plural_mod} located for project {$eus_project_id}";
            foreach ($inst_query->result() as $row) {
                $result_array['instruments'][$row->instrument_id] = $row->eus_display_name;
            }
        } else {
            $result_array['message'] = "No instruments located for project {$eus_project_id}";
        }

        return $result_array;
    }

    /**
     * Retrieve a filtered list of instruments that are associated
     *  with a given Instrument ID
     *  @param    [type]   $eus_instrument_id   [description]
     *  @param    string   $filter   [description]
     *
     *  @return   [type]   [description]
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_projects_for_instrument($eus_instrument_id, $filter = '')
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', TRUE);

        //check that instrument_id is legal and active
        $where_array = array(
        'active_sw' => 'Y',
        'instrument_id' => $eus_instrument_id,
        );
        $inst_exists = $DB_ers->where($where_array)->count_all_results(INST_TABLE) > 0 ? TRUE : FALSE;

        $result_array = array('success' => FALSE);

        if (!$inst_exists) {
            $result_array['message'] = 'No instrument with ID = '.$eus_instrument_id.' was found';
            $result_array['projects'] = array();

            return $result_array;
        }
        $today = new DateTime();

        $select_array = array('pi.project_id', 'p.title as project_name');
        $DB_ers->select($select_array)->where('pi.instrument_id', $eus_instrument_id)->order_by('p.title');
        $DB_ers->where('p.closed_date is null')->where('p.actual_end_date >=', $today->format('Y-m-d'));
        $DB_ers->from(INST_PROJECT_XREF.' as pi');
        $DB_ers->join(PROJECTS_TABLE.' as p', 'p.project_id = pi.project_id');
        if (!empty($filter)) {
            $DB_ers->like('p.title', $filter);
        }
        $project_query = $DB_ers->get();

        $project_list = array();
        if ($project_query && $project_query->num_rows() > 0) {
            $plural_mod = $project_query->num_rows > 1 ? 's' : '';
            $result_array['success'] = TRUE;
            $result_array['message'] = $project_query->num_rows()." project{$plural_mod} located for instrument {$eus_instrument_id}";
            foreach ($project_query->result() as $row) {
                $clean_project_name = trim(str_replace("\n", ' ', $row->project_name));
                $project_list[$row->project_id] = $clean_project_name;
            }
        } else {
            $result_array['message'] = 'No projects located for instrument '.$eus_instrument_id;
        }
        $result_array['items'] = $project_list;

        return $result_array;
    }

    /**
     *  Retrieve the title of the project having the specified ID
     *
     *  @param string $eus_project_id specified project id
     *
     *  @return string
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_project_name($eus_project_id)
    {
        $result = FALSE;
        $DB_ers = $this->CI->load->database('eus_for_myemsl', TRUE);
        $query = $DB_ers->select('title as project_name')->get_where(PROJECTS_TABLE, array('project_id' => strval($eus_project_id)), 1);
        if ($query && $query->num_rows() > 0) {
            $result = $query->row()->project_name;
        }
        return $result;
    }

    /**
     *  Retrieves a list of EUS items (instruments/projects/users)
     *  based on object type and a series of ID's to use in a
     *  *where_in* clause. Can also be restricted to only the set of
     *  objects that are directly associated with the current user
     *
     *  @param string  $object_type  the type of object to be retrieved
     *  @param array   $search_terms an array of object ID's to search
     *  @param boolean $my_objects   restrict results to current user's
     *                               objects only
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_object_list($object_type, $search_terms = FALSE, $my_objects = FALSE)
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', TRUE);
        $is_emsl_staff = $this->CI->is_emsl_staff;
        $projects_available = FALSE;
        if(!$is_emsl_staff) {
            $projects_available = $this->get_projects_for_user($this->CI->user_id);
        }


        if ($my_objects) {
            $DB_ers->where_in('id', array_map('strval', array_keys($my_objects)));
        }
        if ($search_terms && !empty($search_terms)) {
            foreach ($search_terms as $search_term) {
                $DB_ers->or_like('search_field', $search_term);
            }
        }

        $DB_ers->order_by('order_field');
        $query = $DB_ers->get("v_{$object_type}_search");
        $results = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                if(!$is_emsl_staff && $object_type == 'project') {
                    if(in_array($row['id'], $projects_available)) {
                        $results[$row['id']] = $row;
                    }
                }else{
                    $results[$row['id']] = $row;
                }
            }
        }

        return $results;
    }

    /**
     *  Retrieve information for a series of objects as specified
     *
     *  @param array  $object_id_list object id's to retrieve
     *  @param string $object_type    type of object to restrict the search by
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_object_info($object_id_list, $object_type)
    {
        $results = array();
        if (!empty($object_id_list)) {
            $DB_ers = $this->CI->load->database('eus_for_myemsl', TRUE);
            $DB_ers->where_in('id', $object_id_list);
            $query = $DB_ers->get("v_{$object_type}_search");

            if ($query && $query->num_rows() > 0) {
                foreach ($query->result_array() as $row) {
                    $results[$row['id']] = $row;
                }
            }
        }

        return $results;
    }

    /**
     *  Retrieve a display-ready name info array
     *  from a given person ID
     *
     *  @param integer $eus_id the person ID to retrieve
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_name_from_eus_id($eus_id)
    {
        // echo "eus_id => {$eus_id}";
        $DB_ers = $this->CI->load->database('eus_for_myemsl', TRUE);
        $select_array = array(
        'person_id as eus_id', 'first_name', 'last_name', 'email_address',
        );
        $result = array();
        $query = $DB_ers->select($select_array)->get_where(USERS_TABLE, array('person_id' => $eus_id), 1);
        // echo "name query for {$eus_id}\n";
        // echo $DB_ers->last_query();
        if ($query && $query->num_rows() > 0) {
            $result = $query->row_array();
            if(!empty($result['last_name']) && !empty($result['first_name'])) {
                $result['display_name'] = " {$result['first_name']} {$result['last_name']}";
            }elseif(!empty($result['last_name']) && empty($result['first_name'])) {
                $result['display_name'] = "{$result['last_name']}";
            }elseif(empty($result['last_name']) && !empty($result['first_name'])) {
                $result['display_name'] = "{$result['first_name']}";
            }elseif(empty($result['last_name']) && empty($result['first_name']) && !empty($result['email_address'])) {
                $result['display_name'] = "{$result['email_address']}";
            }else{
                $result['display_name'] = strval($eus_id);
            }
        }

        return $result;
    }

    /**
     *  Retrieve all the projects that are associated with
     *  a given person ID
     *
     *  @param integer $eus_user_id person id to search
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_projects_for_user($eus_user_id)
    {
        $is_emsl_staff = $this->CI->is_emsl_staff;
        $DB_ers = $this->CI->load->database('eus_for_myemsl', TRUE);
        $select_array = array('project_id');
        $DB_ers->select($select_array)->where('active', 'Y');
        if(!$is_emsl_staff) {
            $DB_ers->where('person_id', $eus_user_id);
        }

        $query = $DB_ers->distinct()->get(PROJECT_MEMBERS);
        $results = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $results[] = $row->project_id;
            }
        }

        return $results;
    }

    /**
     * Backing function that talks to the local clone
     * of the EUS database to get a current list
     * of projects and their info when given a
     * particular fragment of the project name.
     *
     * @param string $project_name_fragment the search term to use
     * @param string $active                 active/inactive project
     *                                       switch (active/inactive)
     *
     * @used-by Group::get_projects
     *
     * @return array hierarchical array of project_id's
     *               with their accompanying data underneath
     */
    public function get_projects_by_name($project_name_fragment, $is_active = 'active')
    {
        $DB_eus = $this->CI->load->database('eus_for_myemsl', TRUE);
        $DB_eus->select(
            array(
            'project_id', 'title', 'group_id',
            'actual_start_date as start_date',
            'actual_end_date as end_date')
        );
        $is_emsl_staff = $this->CI->is_emsl_staff;
        if(!$is_emsl_staff) {
            $projects_available = $this->get_projects_for_user($this->user_id);
            $DB_eus->where_in('project_id', $projects_available);
        }
        $DB_eus->where('closed_date');
        $DB_eus->where('title ILIKE', "%{$project_name_fragment}%");
        $query = $DB_eus->get('projects');

        $results = array();

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $start_date = strtotime($row->start_date) ? date_create($row->start_date) : FALSE;
                $end_date = strtotime($row->end_date) ? date_create($row->end_date) : FALSE;

                $currently_active = $start_date && $start_date->getTimestamp() < time() ? TRUE : FALSE;
                $currently_active = $currently_active && (!$end_date || $end_date->getTimestamp() >= time()) ? TRUE : FALSE;

                if ($is_active == 'active' && !$currently_active) {
                    continue;
                }

                $results[$row->project_id] = array(
                'title' => trim($row->title, '.'),
                'currently_active' => $currently_active ? 'yes' : 'no',
                'start_date' => $start_date ? $start_date->format('Y-m-d') : '---',
                'end_date' => $end_date ? $end_date->format('Y-m-d') : '---',
                'group_id' => $row->group_id,
                );
            }
        }

        return $results;
    }

    /**
     *  Retrieve a display-ready name for the instrument
     *  specified
     *
     *  @param integer $eus_instrument_id the instrument id to search
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_instrument_name($eus_instrument_id)
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', TRUE);
        $select_array = array('eus_display_name as display_name', 'instrument_name', 'name_short as short_name', 'instrument_id');
        $query = $DB_ers->select($select_array)->get_where(INST_TABLE, array('instrument_id' => $eus_instrument_id), 1);
        $results = array();
        if ($query && $query->num_rows()) {
            $results = $query->row_array();
        }

        return $results;
    }
}
