<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv3, see LICENSE
 */

/**
 * edusharing plugin: 
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */
use EduSharingApiClient\EduSharingHelperBase;
chdir("../../../../../../../");

// Avoid redirection to start screen
// (see ilInitialisation::InitILIAS for details)
//$_GET["baseClass"] = "ilStartUpGUI";

require_once "./include/inc.header.php";
global $DIC;
$plugin = new ilLfEduSharingPageComponentPlugin($DIC->database(), $DIC["component.repository"], "xesp");

$settings = new ilSetting("xedus");

$plugin->setVars($DIC->http()->wrapper()->query()->retrieve('resId', $DIC->refinery()->kindlyTo()->int()));//$_GET['resId']

$eduObj = new ilObjLfEduSharingResource();
$eduObj->setUri($plugin->getUri());
$eduObj->setId($plugin->getResId());
$eduObj->setRefId($plugin->getRefId());
$eduSharingService = new EduSharingService();
$utils = new EduSharingUtilityFunctions();
$redirectUrl = $utils->getRedirectUrl($eduObj, 'window');
$ts = $timestamp = round(microtime(true) * 1000);
$redirectUrl .= '&ts=' . $ts;
$data = $settings->get('application_appid') . $ts . $utils->getObjectIdFromUrl($plugin->getUri());
$baseHelper = new EduSharingHelperBase(
    $settings->get('application_cc_gui_url'),
    $settings->get('application_private_key'),
    $settings->get('application_appid')
);
$redirectUrl .= '&sig=' . urlencode($baseHelper->sign($data));
$redirectUrl .= '&signed=' . urlencode($data);
$redirectUrl .= '&closeOnBack=true';
$ticket = $eduSharingService->getTicket();
$redirectUrl .= '&ticket=' . urlencode(base64_encode($utils->encryptWithRepoKey($ticket)));

ilUtil::redirect($redirectUrl);
exit;
?>
