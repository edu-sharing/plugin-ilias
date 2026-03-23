<?php

$ilias_root = dirname(__DIR__, 8);
require_once $ilias_root . "/vendor/composer/vendor/autoload.php";

header('Content-Type: text/javascript');
header('Service-Worker-Allowed: /');
header('Cache-Control: no-cache, no-store, must-revalidate');

ilInitialisation::initILIAS();
$settings = new ilSetting("xedus");
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
