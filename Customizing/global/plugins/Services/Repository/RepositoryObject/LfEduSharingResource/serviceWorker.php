<?php

$ilias_root = dirname(__DIR__, 8);
require_once $ilias_root . "/vendor/composer/vendor/autoload.php";

// ILIAS 11: initILIAS() no longer sets up the Refinery / UI framework itself; the
// legacy adapter entry point builds a fresh $DIC with them and then calls initILIAS().
require_once $ilias_root . '/artifacts/bootstrap_default.php';
entry_point('ILIAS Legacy Initialisation Adapter');
$settings = new ilSetting("xedus");

// The service worker is opt-in. While it is off, answer 404: a browser that still has
// it registered from an earlier opt-in unregisters it on its next update check.
if ($settings->get('service_worker_enabled', '0') !== '1') {
    http_response_code(404);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    exit;
}

header('Content-Type: text/javascript');
header('Service-Worker-Allowed: /');
header('Cache-Control: no-cache, no-store, must-revalidate');

$proxy = ilProxySettings::_getInstance();
$internalUrl = $settings->get('application_cc_gui_url');
$curlOptions = [];
if ($proxy->isActive()) {
    $curlOptions[] = [
        CURLOPT_HTTPPROXYTUNNEL => 1,
        CURLOPT_PROXY => $proxy->getHost(),
        CURLOPT_PROXYPORT => $proxy->getPort()
    ];
}
$url = $internalUrl . '/web-components/rendering-service-amd/edu-service-worker.js';
$curl = curl_init($url);
curl_setopt_array($curl, $curlOptions);
$content = curl_exec($curl);
$error = curl_errno($curl);
$info = curl_getinfo($curl);
curl_close($curl);
echo $content;
