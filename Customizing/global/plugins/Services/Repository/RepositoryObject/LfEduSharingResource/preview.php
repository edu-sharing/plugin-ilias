<?php

use EduSharingApiClient\Usage;

$ilias_root = dirname(__DIR__, 8);
require_once $ilias_root . '/vendor/composer/vendor/autoload.php';

ilContext::init(ilContext::CONTEXT_SCORM);

// ILIAS 11: initILIAS() no longer sets up the Refinery / UI framework itself; the
// legacy adapter entry point builds a fresh $DIC with them and then calls initILIAS().
require_once $ilias_root . '/artifacts/bootstrap_default.php';
entry_point('ILIAS Legacy Initialisation Adapter');

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






