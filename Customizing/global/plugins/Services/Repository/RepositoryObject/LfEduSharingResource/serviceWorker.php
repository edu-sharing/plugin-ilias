<?php

$ilias_root = dirname(__DIR__, 7);
chdir($ilias_root);
require_once $ilias_root . "/libs/composer/vendor/autoload.php";

header('Content-Type: text/javascript');
header('Service-Worker-Allowed: /');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once $ilias_root . '/Services/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_SCORM);

require_once $ilias_root . '/Services/Init/classes/class.ilInitialisation.php';
ilInitialisation::initILIAS();
$settings = new ilSetting("xedus");
if ($settings->get('service_worker_enabled') != '1') {
    http_response_code(404);
    exit;
}
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
