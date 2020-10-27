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

defined('BASEPATH') OR exit('No direct script access allowed');

header('Content-type: text/xml');
header('Pragma: public');
header('Cache-control: private');
header('Expires: -1');

$NS_OAI_2_0 = 'http://www.openarchives.org/OAI/2.0/';
$NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

$PREFIX_XSI = 'xsi';

$XSD_OAI_2_0 = 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd';

function buildRoot() {
  $root = new SimpleXMLElement('<OAI-PMH/>', 0, FALSE, $NS_OAI_2_0, FALSE);

  $root->addAttribute(sprintf('%s:%s', $PREFIX_XSI, 'schemaLocation'), $XSD_OAI_2_0, $NS_XSI);

  $root->addChild('responseDate', new DateTime('now')->format('c'), $NS_OAI_2_0);

  $request = $root->addChild('request', $oai2['baseURL'], $NS_OAI_2_0);

  foreach ($oai2_args as $key => $value) {
    if (!empty($value)) {
      $request->addAttribute($key, $value, $NS_OAI_2_0);
    }
  }

  $request->addAttribute('verb', $oai2_verb, $NS_OAI_2_0);

  return $root;
}

$root = buildRoot();

$error = $root->addChild('error', NULL, $NS_OAI_2_0);

$error->addAttribute('code', 'noRecordsMatch', $NS_OAI_2_0);

echo $root->asXML();
