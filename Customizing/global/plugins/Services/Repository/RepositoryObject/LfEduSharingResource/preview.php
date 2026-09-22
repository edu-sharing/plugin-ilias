<?php

use EduSharingApiClient\Usage;

$ilias_root = dirname(__DIR__, 7);
require_once $ilias_root . '/libs/composer/vendor/autoload.php';

require_once $ilias_root . '/Services/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_SCORM);

require_once $ilias_root . '/Services/Init/classes/class.ilInitialisation.php';
ilInitialisation::initILIAS();

global $DIC;

$resourceId = (int) $DIC->http()->wrapper()->query()->retrieve(
    'resourceId',
    $DIC->refinery()->kindlyTo()->int()
);
$containerId = (int) $DIC->http()->wrapper()->query()->retrieve(
    'containerId',
    $DIC->refinery()->kindlyTo()->int()
);
$db = $DIC->database();
$query = "SELECT * FROM rep_robj_xesp_usage WHERE id = " . $db->quote($resourceId, 'integer');
$result = $db->query($query);
$usageRow = $result->fetchAssoc();

if (empty($usageRow)) {
    exit('Usage not found');
}

$objectUrl = $usageRow['edus_uri'];
$version = $usageRow['object_version'];

$utils = new EduSharingUtilityFunctions();
$service = new EduSharingService();

$usage = new Usage(
    nodeId: $utils->getObjectIdFromUrl($objectUrl),
    nodeVersion: $version,
    containerId: $containerId,
    resourceId: $resourceId,
    usageId: ""
);

$result = $service->getPreview($usage);

header('Content-type: ' . $result->info['content_type']);
echo $result->content;






