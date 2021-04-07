<?php
/**
 * Extend PHP SoapClient with some header information
 *
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * modifications for ILIAS by Uwe Kohnle
 */
// fau: eduProxy - trial implementation with nusoap.

require_once('./webservice/soap/lib/nusoap.php');
class SigSoapClient extends nusoap_client {
    
    // private $appProperties;
    
    /**
     * Set app properties and soap headers
     *
     * @param string $wsdl
     * @param array $options
     */
    public function __construct($endpoint, $wsdl = false, $proxyhost = false, $proxyport = false) {

        $proxy = ilProxySettings::_getInstance();

        if ($proxy->isActive()) {
            $proxyhost = $proxy->getHost();
            $proxyport = $proxy->getPort();
        }

        try {
			parent::__construct($wsdl, true, $proxyhost, $proxyport);
			$this->setUseCURL(true);
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

            $headers[] = new soapval('appId', 'string', $settings->get('application_appid'), 'http://webservices.edu_sharing.org');
            $headers[] = new soapval('timestamp', 'integer', $timestamp, 'http://webservices.edu_sharing.org');
            $headers[] = new soapval('signature', 'string',  $signature, 'http://webservices.edu_sharing.org');
            $headers[] = new soapval('signed', 'string',  $signdata, 'http://webservices.edu_sharing.org');

//            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org',
//                    'appId', $settings->get('application_appid'));
//            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org', 'timestamp', $timestamp);
//            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org', 'signature', $signature);
//            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org', 'signed', $signdata);
            parent::setHeaders($headers);
        } catch (Exception $e) {
			ilLoggerFactory::getLogger('xesr')->debug('error_set_soap_headers' . $e->getMessage());
        }
    }

    public function __call($name, $arguments) {
        log_var($name, 'name');
        log_var($arguments, 'arguments');
        $return = $this->call($name, $arguments);
        log_var($return, 'return');
        log_backtrace();
        return $return;
    }

}
