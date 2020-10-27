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
$NS_OAI_1_1_eprints = 'http://www.openarchives.org/OAI/1.1/eprints';
$NS_OAI_2_0 = 'http://www.openarchives.org/OAI/2.0/';
$NS_OAI_2_0_branding = 'http://www.openarchives.org/OAI/2.0/branding/';
$NS_OAI_2_0_friends = 'http://www.openarchives.org/OAI/2.0/friends/';
$NS_OAI_2_0_gateway = 'http://www.openarchives.org/OAI/2.0/gateway/';
$NS_OAI_2_0_oai_dc = 'http://www.openarchives.org/OAI/2.0/oai_dc/';
$NS_OAI_2_0_oai_identifier = 'http://www.openarchives.org/OAI/2.0/oai-identifier';
$NS_OAI_2_0_rights = 'http://www.openarchives.org/OAI/2.0/rights/';
$NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

$PREFIX_DC = 'dc';
$PREFIX_XSI = 'xsi';

$XSD_OAI_1_1_eprints = 'http://www.openarchives.org/OAI/1.1/eprints http://www.openarchives.org/OAI/1.1/eprints.xsd';
$XSD_OAI_2_0 = 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd';
$XSD_OAI_2_0_branding = 'http://www.openarchives.org/OAI/2.0/branding/ http://www.openarchives.org/OAI/2.0/branding.xsd';
$XSD_OAI_2_0_friends = 'http://www.openarchives.org/OAI/2.0/friends/ http://www.openarchives.org/OAI/2.0/friends.xsd';
$XSD_OAI_2_0_gateway = 'http://www.openarchives.org/OAI/2.0/gateway/ http://www.openarchives.org/OAI/2.0/gateway.xsd';
$XSD_OAI_2_0_oai_dc = 'http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd';
$XSD_OAI_2_0_oai_identifier = 'http://www.openarchives.org/OAI/2.0/oai-identifier http://www.openarchives.org/OAI/2.0/oai-identifier.xsd';
$XSD_OAI_2_0_rights = 'http://www.openarchives.org/OAI/2.0/rights/ http://www.openarchives.org/OAI/2.0/rightsManifest.xsd';

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

function addChild_branding($description, $array_for_branding) {
  $branding = $description->addChild('branding', NULL, $NS_OAI_2_0_branding);

  $branding->addAttribute(sprintf('%s:%s', $PREFIX_XSI, 'schemaLocation'), $XSD_OAI_2_0_branding, $NS_XSI);

  $array_for_collectionIcon = $array_for_branding['collectionIcon'];

  if (!empty($array_for_collectionIcon)) {
    $collectionIcon = $branding->addChild('collectionIcon', NULL, $NS_OAI_2_0_branding);

    addChildren($collectionIcon, array(
      'url' => NULL,
      'link' => NULL,
      'title' => NULL,
      'width' => NULL,
      'height' => NULL,
    ), $array_for_collectionIcon, $NS_OAI_2_0_branding);
  }

  if (!empty($array_for_branding['metadataRendering'])) {
    foreach ($array_for_branding['metadataRendering'] as $array_for_metadataRendering) {
      $metadataRendering = $branding->addChild('metadataRendering', $array_for_metadataRendering['url'], $NS_OAI_2_0_branding);

      foreach (array('metadataNamespace', 'mimeType') as $name) {
        $value = $array_for_metadataRendering[$name];

        if (!empty($value)) {
          $metadataRendering->addAttribute($name, $value, $NS_OAI_2_0_branding);
        }
      }
    }
  }

  return $branding;
}

function addChild_eprints($description, $array_for_eprints) {
  $eprints = $description->addChild('eprints', NULL, $NS_OAI_1_1_eprints);

  $eprints->addAttribute(sprintf('%s:%s', $PREFIX_XSI, 'schemaLocation'), $XSD_OAI_1_1_eprints, $NS_XSI);

  foreach (array('content', 'metadataPolicy', 'dataPolicy', 'submissionPolicy') as $name) {
    $array_for_eprints_name = $array_for_eprints[$name];

    if (!empty($array_for_eprints_name)) {
      addChildren($eprints->addChild($name, NULL, $NS_OAI_1_1_eprints), array(
        'URL' => NULL,
        'text' => NULL,
      ), $array_for_eprints_name, $NS_OAI_1_1_eprints);
    }
  }

  return addChildren($eprints, array(
    'comment' => NULL,
  ), $array_for_eprints, $NS_OAI_1_1_eprints);
}

function addChild_friends($description, $array_for_friends) {
  $friends = $description->addChild('friends', NULL, $NS_OAI_2_0_friends);

  $friends->addAttribute(sprintf('%s:%s', $PREFIX_XSI, 'schemaLocation'), $XSD_OAI_2_0_friends, $NS_XSI);

  return addChildren($friends, array(
    'baseURL' => NULL,
  ), $array_for_friends, $NS_OAI_2_0_friends);
}

function addChild_gateway($description, $array_for_gateway) {
  $gateway = $description->addChild('gateway', NULL, $NS_OAI_2_0_gateway);

  $gateway->addAttribute(sprintf('%s:%s', $PREFIX_XSI, 'schemaLocation'), $XSD_OAI_2_0_gateway, $NS_XSI);

  return addChildren($gateway, array(
    'source' => NULL,
    'gatewayDescription' => NULL,
    'gatewayAdmin' => NULL,
    'gatewayURL' => NULL,
    'gatewayNotes' => NULL,
  ), $array_for_gateway, $NS_OAI_2_0_gateway);
}

function addChild_oai_identifier($description, $array_for_oai_identifier) {
  $oai_identifier = $description->addChild('oai-identifier', NULL, $NS_OAI_2_0_oai_identifier);

  $oai_identifier->addAttribute(sprintf('%s:%s', $PREFIX_XSI, 'schemaLocation'), $XSD_OAI_2_0_oai_identifier, $NS_XSI);

  return addChildren($oai_identifier, array(
    'scheme' => NULL,
    'repositoryIdentifier' => NULL,
    'delimiter' => NULL,
    'sampleIdentifier' => NULL,
  ), $array_for_oai_identifier, $NS_OAI_2_0_oai_identifier);
}

function addChild_rightsManifest($description, $array_for_rightsManifest) {
  $rightsManifest = $description->addChild('rightsManifest', NULL, $NS_OAI_2_0_rights);

  $rightsManifest->addAttribute(sprintf('%s:%s', $PREFIX_XSI, 'schemaLocation'), $XSD_OAI_2_0_rights, $NS_XSI);

  if (!empty($array_for_rightsManifest['appliesTo'])) {
    $rightsManifest->addAttribute('appliesTo', $array_for_rightsManifest['appliesTo'], $NS_OAI_2_0_rights);
  }

  if (!empty($array_for_rightsManifest['rights'])) {
    foreach ($array_for_rightsManifest['rights'] as $array_for_rights) {
      $rights = $rightsManifest->addChild('rights', NULL, $NS_OAI_2_0_rights);

      if (!empty($array_for_rights['rightsReference'])) {
        foreach ($array_for_rights['rightsReference'] as $ref) {
          if (!empty($ref)) {
            $rightsReference = $rights->addChild('rightsReference', NULL, $NS_OAI_2_0_rights);

            $rightsReference->addAttribute('ref', $ref, $NS_OAI_2_0_rights);
          }
        }
      }

      if (!empty($array_for_rights['rightsDefinition'])) {
        $rightsDefinition = $rights->addChild('rightsDefinition', NULL, $NS_OAI_2_0_rights);

        $dc = $rightsDefinition->addChild(sprintf('%s:%s', 'oai_dc', $PREFIX_DC), NULL, $NS_OAI_2_0_oai_dc);

        $dc->addAttribute(sprintf('%s:%s', $PREFIX_XSI, 'schemaLocation'), $XSD_OAI_2_0_oai_dc, $NS_XSI);

        if (!empty($array_for_rights['rightsDefinition'][sprintf('%s:%s', 'oai_dc', $PREFIX_DC)])) {
          foreach ($array_for_rights['rightsDefinition'][sprintf('%s:%s', 'oai_dc', $PREFIX_DC)] as $name => $value) {
            if ((strpos($name, sprintf('%s:', $PREFIX_DC)) !== 0) || empty($value)) {
              continue;
            } elseif (is_array($value)) {
              foreach ($value as $new_value) {
                $dc->addChild($name, $new_value, $NS_DC_1_1);
              }
            } else {
              $dc->addChild($name, $value, $NS_DC_1_1);
            }
          }
        }
      }
    }
  }

  return $rightsManifest;
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

$Identify = $root->addChild('Identify', NULL, $NS_OAI_2_0);

addChildren($Identify, array(
  'repositoryName' => NULL,
  'baseURL' => NULL,
  'protocolVersion' => NULL,
  'adminEmail' => NULL,
  'earliestDatestamp' => NULL,
  'granularity' => NULL,
  'compression' => NULL,
), $oai2, $NS_OAI_2_0);

if (!empty($oai2['description'])) {
  foreach (array(
    'oai-identifier' => 'addChild_oai_identifier',
    'rightsManifest' => 'addChild_rightsManifest',
    'eprints' => 'addChild_eprints',
    'friends' => 'addChild_friends',
    'branding' => 'addChild_branding',
    'gateway' => 'addChild_gateway',
  ) as $name => $callback) {
    $value = $oai2['description'][$name];

    if (!empty($value)) {
      foreach ($value as $array_for_name) {
        $description = $Identify->addChild('description', NULL, $NS_OAI_2_0);

        $element_for_name = call_user_func($callback, $description, $array_for_name);
      }
    }
  }
}

echo $root->asXML();
