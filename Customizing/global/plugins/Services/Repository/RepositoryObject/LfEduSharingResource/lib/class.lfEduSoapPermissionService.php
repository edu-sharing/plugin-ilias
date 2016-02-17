<?php

/* Copyright (c) 2011-2012 Leifos GmbH, GPL2 */

/**
 * Edusharing configuration class
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$ 
 */
class lfEduSoapPermissionService
{
	// note: these hard-coded roles do not exist in ILIAS
	const ROLE_FACULTY = 'faculty';
	const ROLE_STUDENT = 'student';
	const ROLE_STAFF = 'staff';
	const ROLE_EMPLOYEE = 'employee';
	const ROLE_ALUM = 'alum';
	const ROLE_MEMBER = 'member';
	const ROLE_AFFILIATE = 'affiliate';

	/**
	 * Get permission
	 *
	 * @param object $a_request
	 *
	 * @return array
	 */
	public function getPermission($a_request)
	{

		// check session id
		$session_id = $a_request->session;
		if ($session_id == "")
		{
			return array("getPermissionReturn" => false);
		}

		// course id is the ILIAS parents ref id
		$course_id = (int) $a_request->courseid;
		
		// resource id is the ILIAS object ref id
		$resource_id = (int) $a_request->resourceid;
		
		// actions are configured "per LMS" on the repository side
		$action = $a_request->action;

		// check resource id
		if ($resource_id <= 0)
		{
			return array("getPermissionReturn" => false);
		}

		// init ILIAS and check session
		include_once("./webservice/soap/classes/class.ilSoapUserAdministration.php");
		$il_soap = new ilSoapUserAdministration();
		$il_soap->initAuth($session_id);
		$il_soap->initIlias();
global $ilLog;
$ilLog->write("Edusharing Permission Check for Action -".$action."-");

		// check if action is valid
		if ($action == "moodle/course:update")
		{
			$action = "write";
		}
		if (!in_array($action, array("read", "write", "delete", "create")))
		{
			return array("getPermissionReturn" => false);
		}

		if (!$this->__checkSession($session_id))
		{
			return array("getPermissionReturn" => false);
		}
		
//$ilLog->write("Edusharing Permission Check -after session check-");

		global $ilUser;

		// check user id
		if ($ilUser->getId() == 0)
		{
			return array("getPermissionReturn" => false);
		}

/*$ilLog->write("Edusharing Permission Check -after user check-");
if ($action != "create")
{
	$ilLog->write("Edusharing Permission Check, UserId:".$ilUser->getId().", Action:".$action.", ResourceID:".$resource_id.".");
}*/
		// check if user is an administrator
		global $rbacreview;
		if (in_array(SYSTEM_ROLE_ID, $rbacreview->assignedRoles($ilUser->getId())))
		{
			return array("getPermissionReturn" => true);
		}

		// check permission
		global $ilAccess;

		// not sure if we will use create permissions, but if we do so
		// we must check it against that parent id
		if ($action == "create")
		{
//$ilLog->write("Edusharing Permission Check -in create check-");
			if ($ilAccess->checkAccess("create_xesr", "", $course_id))
			{
//$ilLog->write("Edusharing Permission Check -got create permission-");
				return array("getPermissionReturn" => true);
			}
		}
		else if ($ilAccess->checkAccess($action, "", $resource_id))
	    {
//$ilLog->write("Edusharing Permission Check -got ".$action." permission-");
			return array("getPermissionReturn" => true);
		}
//$ilLog->write("Edusharing Permission Check -no permission-");

	    return array("getPermissionReturn" => false);
	}

	/**
	 * Implements PermissionService::getPrimaryRole().
	 *
	 * @param stdClass $getPrimaryRoleRequest
	 *
	 * @return array
	 */
	public function getPrimaryRole($getPrimaryRoleRequest)
	{
		// ILIAS has no primary role -> take student
		return array('primaryRole' => self::ROLE_STUDENT);
	}

	/**
	 * Check course is deprecated
	 */
	public function checkCourse($checkCourseRequest)
	{
		throw new SoapFault('Not implemented yet. Possibly deprecated.');
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

}
