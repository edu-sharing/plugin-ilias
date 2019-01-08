<?php
// This file is part of ILIAS
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//

/**
 * Handle some webservice functions
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once 'class.sigSoapClient.php';

class mod_edusharing_web_service_factory {

    /**
     * The url to authentication-service's WSDL.
     *
     * @var string
     */
    private $authenticationservicewsdl = '';

    /**
     * Get repository properties and set auth service url
     *
     * @throws Exception
     */
    public function __construct() {
		$settings = new ilSetting("xedus");
        $this->authenticationservicewsdl = $settings->get('repository_authenticationwebservice_wsdl');
        if ( empty($this->authenticationservicewsdl) ) {
			ilLoggerFactory::getLogger('xesr')->warning('error_missing_authwsdl');
			ilUtil::sendFailure('error_missing_authwsdl');
        }
    }


    /**
     * Get repository ticket
     * Check existing ticket vor validity
     * Request a new one if existing ticket is invalid
     * @param string $homeappid
     */
    public function edusharing_authentication_get_ticket() {

        global $DIC;
		$settings = new ilSetting("xedus");
		require_once 'class.locallib.php';

        // Ticket available.
        if (isset($_SESSION['userticket'])) {

            // Ticket is younger than 10s, we must not check.
            if (isset($_SESSION['userticketvalidationts'])
                    && time() - $_SESSION['userticketvalidationts'] < 10) {
                return $_SESSION['userticket'];
            }
            try {
                $eduservice = new SigSoapClient($this->authenticationservicewsdl, array());
            } catch (Exception $e) {
				ilLoggerFactory::getLogger('xesr')->warning('error_authservice_not_reachable');
				ilUtil::sendFailure('error_authservice_not_reachable');
            }

            try {
                // Ticket is older than 10s.
                $params = array(
                    "username"  => edusharing_get_auth_key(), //UK
                    "ticket"  => $_SESSION['userticket']
                );

                $alfreturn = $eduservice->checkTicket($params);

                if ($alfreturn->checkTicketReturn) {
                    $_SESSION['userticketvalidationts'] = time();
                    return $_SESSION['userticket'];
                }
            } catch (Exception $e) {
				ilLoggerFactory::getLogger('xesr')->warning('error_invalid_ticket');
				ilUtil::sendFailure('error_invalid_ticket');
            }

        }
        // No or invalid ticket available - request new ticket.
        $paramstrusted = array("applicationId"  => $settings->get('application_appid'),
                        "ticket"  => session_id(), "ssoData"  => edusharing_get_auth_data());
		// var_dump($paramstrusted);
		// 'http://192.168.0.44:8080/edu-sharing/services/authbyapp?wsdl';
		// var_dump($this->authenticationservicewsdl);
		// die;
        try {
            $client = new SigSoapClient($this->authenticationservicewsdl);
            $return = $client->authenticateByTrustedApp($paramstrusted);
            $ticket = $return->authenticateByTrustedAppReturn->ticket;
            $_SESSION['userticket'] = $ticket;
            $_SESSION['userticketvalidationts'] = time();
            return $ticket;
        } catch (Exception $e) {
			ilLoggerFactory::getLogger('xesr')->warning('error_auth_failed');
        }
        return false;
    }
}

