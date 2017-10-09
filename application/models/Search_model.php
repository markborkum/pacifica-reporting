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

/**
 *  Reporting Model
 *
 *  The **Search_model** class contains functionality
 *  for retrieving metadata entries from the policy server.
 *
 * @category CI_Model
 * @package  Pacifica-reporting
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 * @link    http://github.com/EMSL-MSC/Pacifica-reporting

 * @uses EUS EUS Database access library
 *
 * @access public
 */
class Search_model extends CI_Model
{
    /**
     *  Class constructor
     *
     * @method __construct
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('PHPRequests');
        $this->metadata_url_base = $this->config->item('metadata_server_base_url');
        $this->policy_url_base = $this->config->item('policy_server_base_url');
        $this->content_type = "application/json";
    }

    /**
     * Aggregator function to streamline pulling data from the metadata system
     *
     * @param string $object_type type of object to query for
     * @param string $search_term string for keyword search
     *
     * @return string JSON document with search results
     * 
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function retrieve_metadata_with_search_terms($object_type, $search_term)
    {
        if(!in_array($object_type, $this->accepted_object_types)) {
            format_array_for_select2(array());
        }
        // $object_url_component = plural(strtolower($object_type));
        $search_terms = parse_search_term($search_term);
        if(is_array($search_terms)) {
            $search_term_string = implode('+', $search_terms);
        }else{
            $search_term_string = $search_terms;
        }
        $metadata_url = "{$this->metadata_url_base}/{$object_type}info/search/";
        $metadata_url .= $search_term_string;

        $query = Requests::get(
            $metadata_url,
            array("Content-Type" => $this->content_type)
        );
        return $query->body;
    }
}
