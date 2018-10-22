<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

/**
 * edusharing plugin: 
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 
chdir("../../../../../../../");

// Avoid redirection to start screen
// (see ilInitialisation::InitILIAS for details)
$_GET["baseClass"] = "ilStartUpGUI";

require_once "./include/inc.header.php";

require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/LfEduSharingResource/lib/class.lib.php');
require_once('./Customizing/global/plugins/Services/COPage/PageComponent/LfEduSharingPageComponent/classes/class.ilLfEduSharingPageComponentPlugin.php');

$plugin = new ilLfEduSharingPageComponentPlugin();

$settings = new ilSetting("xedus");

$plugin->setVars($_GET['resId']);

$redirecturl = edusharing_get_redirect_url($plugin, 'window');
$ts = $timestamp = round(microtime(true) * 1000);
$redirecturl .= '&ts=' . $ts;
$data = $settings->get('application_appid') . $ts . edusharing_get_object_id_from_url($plugin->getUri());//object_url
$redirecturl .= '&sig=' . urlencode(edusharing_get_signature($data));
$redirecturl .= '&signed=' . urlencode($data);
$redirecturl .= '&closeOnBack=true';
$plugin->includeClass('../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.cclib.php');
$cclib = new mod_edusharing_web_service_factory();
$redirecturl .= '&ticket=' . urlencode(base64_encode(edusharing_encrypt_with_repo_public($cclib -> edusharing_authentication_get_ticket())));

ilUtil::redirect($redirecturl);
exit;
?>
