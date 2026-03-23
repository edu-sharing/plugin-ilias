<?php

$ilias_root = dirname(__DIR__, 8);
require_once $ilias_root . '/vendor/composer/vendor/autoload.php';

require_once $ilias_root . '/components/ILIAS/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_SCORM);

require_once $ilias_root . '/components/ILIAS/Init/classes/class.ilInitialisation.php';
ilInitialisation::initILIAS();

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

header('Content-Type: application/json; charset=utf-8');

$service = new EduSharingService();
$securedNode = $service->getSecuredNode($nodeId, $resourceId, $version);
$renderingUrl = $service->getRendering2Url();
$payload = [
    'node' => $securedNode->node,
    'securedNode' => $securedNode->securedNode,
    'signature' => $securedNode->signature,
    'jwt' => $securedNode->jwt,
    'renderingBaseUrl' => $renderingUrl,
    'previewUrl' => $securedNode->previewUrl,
];

echo json_encode([
    'ok' => true,
    'data' => $payload
], JSON_THROW_ON_ERROR);

exit;
