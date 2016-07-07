<?php

class SigSoapClient extends SoapClient {
    
    private $appProperties;
    
    public function __construct($wsdl, $options = array()) {
        parent::__construct($wsdl, $options);
        $this -> mod_edusharing_set_soap_headers();
    }
    
    private function mod_edusharing_set_soap_headers() {
        try {
        	
        	$home_conf = lfEduAppConf::getHomeAppConf();
        	
            $timestamp = round(microtime(true) * 1000);     

            $signData = $home_conf->getEntry("appid") . $timestamp;
            $priv_key = $home_conf->getEntry("private_key");      
            $pkeyid = openssl_get_privatekey($priv_key);      
            openssl_sign($signData, $signature, $pkeyid);
            $signature = base64_encode($signature);
            openssl_free_key($pkeyid);
            $headers = array();
            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org', 'appId', $home_conf->getEntry("appid"));
            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org', 'timestamp', $timestamp); 
            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org', 'signature', $signature); 
            $headers[] = new SOAPHeader('http://webservices.edu_sharing.org', 'signed', $signData); 

            parent::__setSoapHeaders($headers);
            
        } catch (Exception $e) {
            throw new Exception('Could not set soap headers - ' . $e -> getMessage());
        }
    }
}
