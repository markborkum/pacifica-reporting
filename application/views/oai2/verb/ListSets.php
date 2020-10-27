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

$NS_DC_1_1 = 'http://purl.org/dc/elements/1.1/';
$NS_OAI_2_0 = 'http://www.openarchives.org/OAI/2.0/';
$NS_OAI_2_0_oai_dc = 'http://www.openarchives.org/OAI/2.0/oai_dc/';
$NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

$PREFIX_DC = 'dc';
$PREFIX_OAI_DC = 'oai_dc';
$PREFIX_XSI = 'xsi';

$XSD_OAI_2_0 = 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd';
$XSD_OAI_2_0_oai_dc = 'http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd';

function addChildren($root, $children, $array, $ns = NULL) {
  foreach ($children as $name => $value) {
    if (is_null($value)) {
      $value = $array[$name];

      if (empty($value)) {
        continue;
      } elseif (is_array($value)) {
        foreach ($value as $new_value) {
          $root->addChild($name, $new_value, $ns);
        }
      } else {
        $root->addChild($name, $value, $ns);
      }
    } else {
      $root->addChild($name, $value, $ns);
    }
  }

  return $root;
}

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

$ListSets = $root->addChild('ListSets', NULL, $NS_OAI_2_0);

foreach ($oai2['set'] as $array_for_set) {
  $set = $ListSets->addChild('set', NULL, $NS_OAI_2_0);

  addChildren($set, array(
    'setSpec' => NULL,
    'setName' => NULL,
  ), $array_for_set, $NS_OAI_2_0);

  if (!empty($array_for_set['setDescription'])) {
    $setDescription = $set->addChild('setDescription', NULL, $NS_OAI_2_0);

    $oai_dc = $setDescription->addChild('dc', NULL, $NS_OAI_2_0_oai_dc);

    $oai_dc->addAttribute(sprintf('%s:%s', $PREFIX_XSI, 'schemaLocation'), $XSD_OAI_2_0_oai_dc, $NS_XSI);

    foreach ($array_for_set['setDescription'][sprintf('%s:%s', $PREFIX_OAI_DC, $PREFIX_DC)] as $key => $value) {
      $oai_dc->addChild($key, $value, $NS_DC_1_1);
    }
  }
}

echo $root->asXML();
