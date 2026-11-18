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

$nodeId = (string) $DIC->http()->wrapper()->query()->retrieve(
    'nodeId',
    $DIC->refinery()->kindlyTo()->string()
);
$resourceId = (string) $DIC->http()->wrapper()->query()->retrieve(
    'resourceId',
    $DIC->refinery()->kindlyTo()->string()
);
$version = (string) $DIC->http()->wrapper()->query()->retrieve(
    'version',
    $DIC->refinery()->kindlyTo()->string()
);
$containerId = (string) $DIC->http()->wrapper()->query()->retrieve(
    'containerId',
    $DIC->refinery()->kindlyTo()->string()
);

header('Content-Type: application/json; charset=utf-8');

$service = new EduSharingService();

$usageData              = new stdClass();
$usageData->ticket      = $service->getTicket();
$usageData->nodeId      = $nodeId;
$usageData->containerId = $containerId;
$usageData->resourceId  = $resourceId;
$usageId = $service->getUsageId($usageData);

if ($usageId === null) {
    echo json_encode([
        'ok' => false,
        'error' => 'No usage found for the requested node'
    ], JSON_THROW_ON_ERROR);
    exit;
}

$usage = new Usage(
    nodeId: $nodeId,
    nodeVersion: $version,
    containerId: $containerId,
    resourceId: $resourceId,
    usageId: $usageId
);

$securedNode = $service->getSecuredNode($usage);
$renderingUrl = $service->getRendering2Url();
$payload = [
    'node' => $securedNode->node,
    'securedNode' => $securedNode->securedNode,
    'signature' => $securedNode->signature,
    'jwt' => $securedNode->jwt,
    'renderingBaseUrl' => $renderingUrl,
    'previewUrl' => $securedNode->previewUrl,
    'signingAlgorithm' => $securedNode->signingAlgorithm,
];

echo json_encode([
    'ok' => true,
    'data' => $payload
], JSON_THROW_ON_ERROR);

exit;
