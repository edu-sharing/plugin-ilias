<?php
/**
 * Extend PHP SoapClient with some header information
 *
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * modifications for ILIAS by Uwe Kohnle
 */

class SigSoapClient extends SoapClient {
    
    // private $appProperties;
    
    /**
     * Set app properties and soap headers
     *
     * @param string $wsdl
     * @param array $options
     */
    public function __construct($wsdl, $options = array()) {

        // fau: eduProxy - use proxy for eduSharing connection
        $proxy = ilProxySettings::_getInstance();

        if ($proxy->isActive()) {
            $options['proxy_host'] = $proxy->getHost();
            $options['proxy_port'] = (int) $proxy->getPort();

                $options['stream_context'] = stream_context_create(
                    array(
                       'ssl' => array(
                           'verify_peer'       => false,
                           'verify_peer_name'  => false,
                       ),
                       'http' => array (
                           'proxy' => 'http://' . $proxy->getHost() . ':' . $proxy->getPort(),
                           'request_fulluri' => true,
                           //'ignore_errors' => true
                       ),
                   )
               );
        }
        // fau.

        try {
			parent::__construct($wsdl, $options);
			$this -> edusharing_set_soap_headers();
		} catch (Exception $e) {
			ilLoggerFactory::getLogger('xesr')->debug('error_soap_connect_edusharing' . $e->getMessage());
        }
    }

    /**
     * Set soap headers
     *
     * @throws Exception
     */
    private function edusharing_set_soap_headers() {
		$settings = new ilSetting("xedus");
        try {
            $timestamp = round(microtime(true) * 1000);
            $signdata = $settings->get('application_appid') . $timestamp;
            $privkey = $settings->get('application_private_key');
            $pkeyid = openssl_get_privatekey($privkey);
            openssl_sign($signdata, $signature, $pkeyid);
            $signature = base64_encode($signature);
            openssl_free_key($pkeyid);
            $headers = array();
            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org',
                    'appId', $settings->get('application_appid'));
            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org', 'timestamp', $timestamp);
            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org', 'signature', $signature);
            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org', 'signed', $signdata);
            parent::__setSoapHeaders($headers);
        } catch (Exception $e) {
			ilLoggerFactory::getLogger('xesr')->debug('error_set_soap_headers' . $e->getMessage());
        }
    }

}
