<?php
/**
 * Default OAI-PMH 2.0 Config
 *
 * PHP Version 5
 *
 * @category Configuration
 * @package  Default_Oai2
 * @author   Mark Borkum <mark.borkum@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-reporting
 */

defined('BASEPATH') or exit('No direct script access allowed');

$config['oai2'] = array(
    'GetRecord' => array(),
    'Identify' => array(
        'repositoryName' => 'MyEMSL',
        'baseURL' => rtrim(base_url() . $this->config->item('index_page'), '/') . '/oai2',
        'protocolVersion' => '2.0',
        'adminEmail' => array(
            'kenneth.auberry@pnnl.gov',
        ),
        'earliestDatestamp' => '1990-02-01T12:00:00Z',
        'deletedRecord' => 'no',
        'granularity' => 'YYYY-MM-DDThh:mm:ssZ',
        'compression' => array(
            'deflate',
        ),
        'description' => array(
            'oai-identifier' => array(
                array(
                    'scheme' => 'oai',
                    'repositoryIdentifier' => base_url(),
                    'delimiter' => ':',
                    'sampleIdentifier' => 'oai' . ':' . base_url() . ':' . 'instrument/1', // TODO What is a sample identifier for each "setSpec"?
                ),
            ),
            // 'rightsManifest' => array(
            //     array(
            //         'appliesTo' => NULL,
            //         'rights' => array(
            //             array(
            //                 'rightsDefinition' => array(
            //                     'oai_dc:dc' => array(
            //                         'dc:title' => NULL,
            //                         'dc:date' => NULL,
            //                         'dc:creator' => NULL,
            //                         'dc:description' => NULL,
            //                         'dc:identifier' => NULL,
            //                     ),
            //                 ),
            //             ),
            //             array(
            //                 'rightsReference' => NULL,
            //             ),
            //         ),
            //     ),
            // ),
            // 'eprints' => array(
            //     array(
            //         'content' => array(
            //             'URL' => NULL,
            //             'text' => NULL,
            //         ),
            //         'metadataPolicy' => array(
            //             'URL' => NULL,
            //             'text' => NULL,
            //         ),
            //         'dataPolicy' => array(
            //             'URL' => NULL,
            //             'text' => NULL,
            //         ),
            //         'submissionPolicy' => array(
            //             'URL' => NULL,
            //             'text' => NULL,
            //         ),
            //         'comment' => NULL,
            //     ),
            // ),
            // 'friends' => array(
            //     array(
            //         'baseURL' => array(
            //             NULL,
            //         ),
            //     ),
            // ),
            // 'branding' => array(
            //     array(
            //         'collectionIcon' => array(
            //             'url' => NULL,
            //             'link' => NULL,
            //             'title' => NULL,
            //             'width' => NULL,
            //             'height' => NULL,
            //         ),
            //         'metadataRendering' => array(
            //             array(
            //                 'metadataNamespace' => NULL,
            //                 'mimeType' => NULL,
            //                 'url' => NULL,
            //             ),
            //         ),
            //     ),
            // ),
            // 'gateway' => array(
            //     array(
            //         'source' => NULL,
            //         'gatewayDescription' => NULL,
            //         'gatewayAdmin' => array(
            //           NULL,
            //         ),
            //         'gatewayURL' => NULL,
            //         'gatewayNotes' => NULL,
            //     ),
            // ),
        ),
    ),
    'ListIdentifiers' => array(),
    'ListMetadataFormats' => array(
        'metadataFormat' => array(
            array(
                'metadataPrefix' => 'oai_dc',
                'schema' => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
                'metadataNamespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
            ),
        ),
    ),
    'ListRecords' => array(),
    'ListSets' => array(
        'set' => array(
            array(
                'setSpec' => 'instrument',
                'setName' => 'Instruments',
                'setDescription' => array(
                    'oai_dc:dc' => array(
                        'dc:description' => 'This set contains metadata describing instruments',
                    ),
                ),
            ),
            array(
                'setSpec' => 'project',
                'setName' => 'Projects',
                'setDescription' => array(
                    'oai_dc:dc' => array(
                        'dc:description' => 'This set contains metadata describing projects',
                    ),
                ),
            ),
            array(
                'setSpec' => 'user',
                'setName' => 'Users',
                'setDescription' => array(
                    'oai_dc:dc' => array(
                        'dc:description' => 'This set contains metadata describing users',
                    ),
                ),
            ),
        ),
    ),
);
