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
 require_once 'Baseline_api_controller.php';
 ini_set('max_execution_time', 0);
 ini_set('memory_limit', '2048M');

class Oai2Exception extends Exception
{
    function __construct($message) {
        parent::__construct($message);
    }
}

 /**
  *  Oai2 is a CI controller class that extends Baseline_controller
  *
  *  The *Oai2* class implements The Open Archives Initiative Protocol for
  *  Metadata Harvesting (OAI-PMH).
  *
  * @category Page_Controller
  * @package  Pacifica-reporting
  * @author   Mark Borkum <mark.borkum@pnnl.gov>
  *
  * @license BSD https://opensource.org/licenses/BSD-3-Clause
  * @link    http://github.com/EMSL-MSC/Pacifica-reporting

  * @see    https://github.com/EMSL-MSC/pacifica-reporting
  * @see    https://www.openarchives.org/OAI/openarchivesprotocol.html
  * @access public
  */
class Oai2 extends Baseline_api_controller
{
    /**
     * [__construct description]
     *
     * @author Mark Borkum <mark.borkum@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->helper(array('oai2'));

        $this->accepted_object_types = array();
        $this->last_update_time = get_last_update(APPPATH);
    }

    /**
     * Root route for this controller.
     *
     * @param string $from  specifies a lower bound for datestamp-based selective harvesting
     * @param string $identifier  specifies the unique identifier of the item in the repository from which the record must be disseminated
     * @param string $metadataPrefix  specifies the metadataPrefix of the format that should be included in the metadata part of the returned record
     * @param string $resumptionToken  the flow control token returned by a previous request that issued an incomplete list
     * @param string $set  specifies set criteria for selective harvesting
     * @param string $until  specifies a upper bound for datestamp-based selective harvesting
     * @param string $verb  one of the defined OAI-PMH requests
     *
     * @method index
     *
     * @author Mark Borkum <mark.borkum@pnnl.gov>
     *
     * @return none
     */
    public function index($from = '', $identifier = '', $metadataPrefix = '', $resumptionToken = '', $set = '', $until = '', $verb = '')
    {
        $now = new DateTime('now');

        $config = $this->config->item('oai2');

        $verb = safe_trim($verb);
        $this->page_data['oai2_verb'] = $verb;

        $args = array(
            'from' => safe_trim($from),
            'identifier' => safe_trim($identifier),
            'metadataPrefix' => safe_trim($metadataPrefix),
            'resumptionToken' => safe_trim($resumptionToken),
            'set' => safe_trim($set),
            'until' => safe_trim($until),
        );
        $this->page_data['oai2_args'] = $args;

        try {
            $this->page_data['oai2'] = $this->oai2_page_data($config, $verb, $args);

            $this->load->view(sprintf('oai2/verb/%s', $verb), $this->page_data);
        } catch (Oai2Exception $e) {
            $this->load->view(sprintf('oai2/error/%s', $e->getMessage()), $this->page_data);
        }
    }

    private function oai2_page_data($config, $verb, $args)
    {
        switch ($verb) {
            case 'GetRecord':
                if (!$this->test_all_not_empty($args, array('identifier', 'metadataPrefix')) || $this->test_any_not_empty($args, array('from', 'resumptionToken', 'set', 'until'))) {
                    throw new Oai2Exception('badArgument');
                }

                return $this->runGetRecord($config, $args['identifier'], $args['metadataPrefix']);
            case 'Identify':
                if ($this->test_any_not_empty($args, array('from', 'identifier', 'metadataPrefix', 'resumptionToken', 'set', 'until'))) {
                    throw new Oai2Exception('badArgument');
                }

                return $this->runIdentify($config);
            case 'ListIdentifiers':
                if (!$this->test_all_not_empty($args, array('metadataPrefix')) || $this->test_any_not_empty($args, array('identifier'))) {
                    throw new Oai2Exception('badArgument');
                }

                return $this->runListIdentifiers($config, $args['from'], $args['until'], $args['metadataPrefix'], $args['set'], $args['resumptionToken']);
            case 'ListMetadataFormats':
                if ($this->test_any_not_empty($args, array('from', 'metadataPrefix', 'resumptionToken', 'set', 'until'))) {
                    throw new Oai2Exception('badArgument');
                }

                return $this->runListMetadataFormats($config, $args['identifier']);
            case 'ListRecords':
                if (!$this->test_all_not_empty($args, array('metadataPrefix')) || $this->test_any_not_empty($args, array('identifier'))) {
                    throw new Oai2Exception('badArgument');
                }

                return $this->runListRecords($config, $args['from'], $args['until'], $args['metadataPrefix'], $args['set'], $args['resumptionToken']);
            case 'ListSets':
                if ($this->test_any_not_empty($args, array('from', 'identifier', 'metadataPrefix', 'set', 'until'))) {
                    throw new Oai2Exception('badArgument');
                }

                return $this->runListSets($config, $args['resumptionToken']);
            default:
                throw new Oai2Exception('badVerb');
        }
    }

    private function runGetRecord($config, $identifier, $metadataPrefix)
    {
        // return array(
        //     'header' => array(
        //         'identifier' => NULL,
        //         'datestamp' => NULL,
        //         'setSpec' => array(
        //             NULL,
        //         ),
        //     ),
        //     'metadata' => array(
        //         'oai_dc:dc' => array(
        //             'dc:title' => NULL,
        //             'dc:creator' => NULL,
        //             'dc:type' => NULL,
        //             'dc:source' => NULL,
        //             'dc:language' => NULL,
        //             'dc:identifier' => NULL,
        //         ),
        //     ),
        //     'about' => array(
        //         'oai_dc:dc' => array(
        //             'dc:publisher' => NULL,
        //             'dc:rights' => NULL,
        //         ),
        //     ),
        // );

        throw new Oai2Exception('idDoesNotExist');
    }

    private function runIdentify($config)
    {
        return $config['Identify'];
    }

    private function runListIdentifiers($config, $from, $until, $metadataPrefix, $set, $resumptionToken)
    {
        // return array(
        //     'header' => array(
        //         array(
        //             'identifier' => NULL,
        //             'datestamp' => NULL,
        //             'setSpec' => array(
        //               NULL,
        //             ),
        //         ),
        //     ),
        //     'resumptionToken' => array(
        //         'expirationDate' => NULL,
        //         'completeListSize' => NULL,
        //         'cursor' => NULL,
        //         'text' => NULL,
        //     ),
        // );

        return array(
            'header' => array(),
            'resumptionToken' => NULL,
        );
    }

    private function runListMetadataFormats($config, $identifier)
    {
        $oai2_record = empty($identifier) ? NULL : $this->runGetRecord($config, $identifier);

        $oai2_metadataFormat = $config['ListMetadataFormats']['metadataFormat'];

        if (empty($oai2_metadataFormat)) {
            throw new Oai2Exception('noMetadataFormats');
        }

        return array(
            'metadataFormat' => $oai2_metadataFormat,
        );
    }

    private function runListRecords($config, $from, $until, $metadataPrefix, $set, $resumptionToken)
    {
        return array(
            'record' => array(),
            'resumptionToken' => NULL,
        );
    }

    private function runListSets($config, $resumptionToken)
    {
        $oai2_set = $config['ListSets']['set'];

        if (empty($oai2_set)) {
            throw new Oai2Exception('noSetHierarchy');
        }

        return array(
            'set' => $oai2_set,
        );
    }
}
