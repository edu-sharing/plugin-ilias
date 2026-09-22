<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

//use ILIAS\ilContext;

$ilias_root = dirname(__DIR__, 8);
require_once $ilias_root . "/vendor/composer/vendor/autoload.php";

/**
 * edusharing plugin: 
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 

// Avoid redirection to start screen
// (see ilInitialisation::InitILIAS for details)
//$_GET["baseClass"] = "ilStartUpGUI";
//require_once "./include/inc.header.php";
//error_log($_SERVER['DOCUMENT_ROOT']);
//error_log(ILIAS_WEB_DIR);
include_once $ilias_root . "/components/ILIAS/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_SCORM);

require_once($ilias_root . "/components/ILIAS/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();


$settings = new ilSetting("xedus");
// $appid = $settings->get('application_appid');
// $type = $settings->get('application_type');
// $host = $_SERVER['SERVER_ADDR'];
// $domain = gethostbyname($_SERVER['SERVER_NAME']);
// $key = $settings->get('application_public_key');
// $appcaption = '';
//$output =\EduSharingApiClient\EduSharingHelper::generateEduAppXMLData(
//    $settings->get('application_appid'),
//    $settings->get('application_public_key'),
//    'LMS',
//    '*');



$xml = new SimpleXMLElement(
        '<?xml version="1.0" encoding="utf-8" ?><!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd"><properties></properties>');

$entry = $xml->addChild('entry', $settings->get('application_appid'));
$entry->addAttribute('key', 'appid');
$entry = $xml->addChild('entry', $settings->get('application_type'));
$entry->addAttribute('key', 'type');
$entry = $xml->addChild('entry', 'ILIAS');
$entry->addAttribute('key', 'subtype');
$entry = $xml->addChild('entry', gethostbyname($_SERVER['SERVER_NAME']));
$entry->addAttribute('key', 'domain');
$entry = $xml->addChild('entry', $_SERVER['SERVER_ADDR']);
$entry->addAttribute('key', 'host');
$entry = $xml->addChild('entry', 'true');
$entry->addAttribute('key', 'trustedclient');
// $entry = $xml->addChild('entry', 'moodle:course/update');
// $entry->addAttribute('key', 'hasTeachingPermission');
$entry = $xml->addChild('entry', $settings->get('application_public_key'));
$entry->addAttribute('key', 'public_key');
$entry = $xml->addChild('entry', $settings->get('EDU_AUTH_AFFILIATION_NAME'));
$entry->addAttribute('key', 'appcaption');
// ToDo
//if ($this->utils->getConfigEntry('wlo_guest_option')) {
//    $entry = $xml->addChild('entry', $this->utils->getConfigEntry('edu_guest_guest_id'));
//    $entry->addAttribute('key', 'auth_by_app_user_whitelist');
//}

header('Content-type: text/xml');
print(html_entity_decode($xml->asXML()));




// $xmlstr=<<<XML
// <properties>
// <entry key="appid">$appid</entry>
// <entry key="type">$type</entry>
// <entry key="subtype">ILIAS</entry>
// <entry key="domain">$domain</entry>
// <entry key="host">$host</entry>
// <entry key="trustedclient">true</entry>
// <entry key="public_key">$key</entry>
// <entry key="appcaption">$appcaption</entry>
// </properties>
// XML;

// $xml = new SimpleXMLElement($xmlstr);
// echo $xml->asXML();

exit;
?>
