<?php

/* Copyright (c) 2011-2012 Leifos GmbH, GPL2 */

/**
 * Edusharing configuration class
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$ 
 */
class lfEduSoapAuthenticationService
{

	/**
	 * Test if ticket is bound to username.
	 *
	 * $wrappedParams must contain "ticket" and "username" as defined in
	 * "authenticationservice.wsdl".
	 *
	 * @param stdClass $wrappedParams
	 * @return array
	 */
	public function checkTicket($wrappedParams)
	{
	    
        
        
        
        //implement this
        
        return array("checkTicketReturn" => true);
        
        
        
        
        
        
        
        
        
        
        
        
//		error_log('AuthenticationService::checkTicket(): invoked', E_USER_NOTICE);

		// validate params
		if (empty($wrappedParams->ticket))
		{
//			error_log('AuthenticationService::checkTicket(): Missing param "ticket".', E_USER_ERROR);
			throw new SoapFault('Sender', 'Missing param "ticket".');
		}

		// still validating params
		if (empty($wrappedParams->username))
		{
//			error_log('AuthenticationService::checkTicket(): Missing param "username".', E_USER_ERROR);
//			throw new SoapFault('Sender', 'Missing param "username".');
		}

		$t = $wrappedParams->ticket;
		
		$exploded = explode('::', $t);
		// still validating params
		if (empty($exploded[1]))
		{
			throw new SoapFault('Sender', 'Ticket does not contain client id. ('.$wrappedParams->ticket.')');
		}
		
		include_once("./webservice/soap/classes/class.ilSoapUserAdministration.php");

		$il_soap = new ilSoapUserAdministration();
		$il_soap->initAuth($wrappedParams->ticket);

		$il_soap->initIlias();
		
		if (!$this->__checkSession($wrappedParams->ticket))
		{
			error_log('AuthenticationService::checkTicket(): __checkSession() not successful, ticket invalid.');
			return array("checkTicketReturn" => false);
		}
		
		error_log('AuthenticationService::checkTicket(): ticket valid.');
		return array("checkTicketReturn" => true);
		
		// @TODO check for requested username
		/*if (!empty($sessionData["USER"]->id))
		{
			error_log('AuthenticationService::checkTicket(): Ticket valid.', E_USER_NOTICE);
			return array("checkTicketReturn" => true);
		}*/

	}

	// PROTECTED
	protected function __checkSession($sid)
	{
		global $ilAuth;

		list($sid,$client) = explode("::", $sid);
		
		if(!strlen($sid))
		{
			return false;	
		}
		if(!$client)
		{
			return false;	
		}
		if(!$ilAuth->getAuth())
		{
			switch($ilAuth->getStatus())
			{
				case AUTH_EXPIRED:
					return false;
	
				case AUTH_IDLED:
					return false;
					
				case AUTH_WRONG_LOGIN:
					return false;
					
				default:
					return false;
			}
		}
		
		global $ilUser;
		
		if(!$ilUser->hasAcceptedUserAgreement() and $ilUser->getId() != ANONYMOUS_USER_ID)
		{
			return false;
		}

		return true;
	}

	
	/**
	 * Authenticate user from application "applicationId". Create user if
	 * requested by "createUser".
	 *
	 * string	applicationId
	 * string	username
	 * string	email
	 * string	ticket
	 * boolean	createUser
	 *
	 * @param stdClass $wrappedParams
	 * @return stdClass
	 */
	public function authenticateByTrustedApp($wrappedParams)
	{
		if (empty($wrappedParams->ticket))
		{
			return array("authenticateByTrustedAppReturn" => false);
		}

		$t = $wrappedParams->ticket;
		
		// check client in ticket
		$exploded = explode('::', $t);
		if (empty($exploded[1]))
		{
			throw new SoapFault('Sender', 'Ticket does not contain client id. ('.$wrappedParams->ticket.')');
		}
		
		include_once("./webservice/soap/classes/class.ilSoapUserAdministration.php");

		$il_soap = new ilSoapUserAdministration();
		$il_soap->initAuth($wrappedParams->ticket);

		$il_soap->initIlias();
		
		if (!$this->__checkSession($wrappedParams->ticket))
		{
			return array("authenticateByTrustedAppReturn" => false);
		}

		global $ilUser;
		
		$d = new stdClass();
		$d->sessionid = $wrappedParams->ticket;
		$d->ticket = $wrappedParams->ticket;

		$d->userid = $ilUser->getId();

		if ($ilUser->getEmail() == "")
		{
			throw new SoapFault('Server', 'No email-address available.');
		}
		else
		{
			$d->email = $ilUser->getEmail();
		}

		if ($ilUser->getFirstname() == "")
		{
			throw new SoapFault('Server', 'No firstname available.');
		}
		else
		{
			$d->givenname = $ilUser->getFirstname();
		}

		if ($ilUser->getLastname() == "")
		{
			throw new SoapFault('Server', 'No lastname available.');
		}
		else
		{
			$d->surname	= $ilUser->getLastname();
		}

		if ($ilUser->getLogin() == "")
		{
			throw new SoapFault('Server', 'No username available.');
		}
		else
		{
			$d->username = $ilUser->getLogin();
		}

		return array('authenticateByTrustedAppReturn' => $d);
	}

}
