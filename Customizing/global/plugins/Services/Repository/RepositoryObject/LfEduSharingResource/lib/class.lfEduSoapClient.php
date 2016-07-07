<?php
/**
 * This product Copyright 2010 metaVentis GmbH.  For detailed notice,
 * see the "NOTICE" file with this distribution.
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

/* Rewritten based on edu sharing CCWebServiceFactory class, Leifos GmbH, 8.5.2012 */


/**
 * Edusharing soap client. Like the moodle plugin, which uses the
 * cclib class CCWebServiceFactory, we are using the Alfresco service classes
 * to communicate
 *
 * @author Alex Killing <killing@leifos.de>
 * @version $Id$ 
 */
class lfEduSoapClient
{
	private $soap_client;
	private $home_conf;
	private $ticket;
	
	/**
	 * Constructor
	 *
	 * @param
	 * @return
	 */
	function __construct($a_plugin, $a_app_conf)
	{
		global $ilPluginAdmin;
		
		$this->app_conf = $a_app_conf;
		$this->plugin = $a_plugin;
		$this->connection_url = $this->app_conf->getEntry("cc_webservice_url");
		$this->connection_path = $this->app_conf->getEntry("cc_authentication_path");
	}

	/**
	 * Get connection url
	 */
	private function getConnectionUrl()
	{
		return $this->connection_url;
	}

	/**
	 * Get connection path
	 */
	private function getConnectionPath()
	{
		return $this->connection_path;
	}

	/**
	 * Get authentication ticket
	 */
	public function getAuthenticationTicket()
	{
		global $ilUser;
		global $ilLog;
		
		$url  = $this->getConnectionUrl();
		$path = $this->getConnectionPath();

		try
		{
			require_once 'class.lfSigSoapClient.php';
			$alfservice =  new SigSoapClient($url.$path, array());

			
			if (isset($_SESSION["lf_edus_ticket"]))
			{
				$params = array("username" => $ilUser->getEmail(), "ticket" => $_SESSION["lf_edus_ticket"]);
				$alfReturn = $alfservice->checkTicket($params);
				if ( $alfReturn === true )
				{
					return $_SESSION["lf_edus_ticket"];
				}
			}

			// no or invalid ticket available
			// request new ticket
			
			$params = array("applicationId" => $this->app_conf->getEntry('appid'),
					"ssoData" => array(array('key' => 'userid','value' => $ilUser->getEmail())));
			$ilLog->write("(A) Calling authenticateByTrustedApp() with: ".print_r($params, true));
			$alfReturn = $alfservice->authenticateByTrustedApp($params);
			$ticket = $alfReturn->authenticateByTrustedAppReturn->ticket;
			$_SESSION["lf_edus_ticket"] = $ticket;
			
			return $ticket;
		}
		catch (Exception $e) {
			error_log($e);
			
			throw $e;
		}
	}
	

	
	/**
	 * Delete usage
	 *
	 * @param object $a_edu_obj ILIAS edusharing object
	 * @return
	 */
	function deleteUsage($a_edu_obj, $a_uri, $a_course_ref_id)
	{
		global $tree, $ilUser, $ilLog;
		
		$ilLog->write("A. Calling deleteUsage()");
		
		// since this is called during update we must read the uri from db
		//$uri = ilObjLfEduSharingResource::lookupUri($a_edu_obj->getId());
		$uri = $a_uri;
		
		$this->plugin->includeClass("../lib/class.lfEduUtil.php");
		
		$object_id = lfEduUtil::getObjectIdFromUri($uri);
		if ($object_id == "")
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException("delete Usage: no object id");
		}
		
		if ($a_course_ref_id == 0)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException("delete Usage: no course ref id given");
		}
		
		$repository_id = lfEduUtil::getRepIdFromUri($uri);
		if ($repository_id == "")
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException("delete Usage: no repository id");
		}
		
		// get home app conf
		$this->plugin->includeClass("../lib/class.lfEduAppConf.php");
		lfEduAppConf::initApps();
		$home_conf = lfEduAppConf::getHomeAppConf();

		if ($home_conf == null)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException('Missing home-config.');
		}

		$app_id = $home_conf->getEntry('appid');
		if (!$app_id)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException('Missing "appid" in home-conf.');
		}

		// get repository conf
		$rep_conf = lfEduAppConf::getAppConfById($repository_id);
		if ($rep_conf == null)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException('Missing config for repository "'.$repository_id.'"');
		}
		
		$ticket = $this->getAuthenticationTicket();

		// delete usage
		$url = $rep_conf->getEntry('usagewebservice_wsdl');
		if (!$url)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException('Missing config-param "usagewebservice_wsdl".');
		}

		// soap call
		$soap = new SigSoapClient($url);

		$params = array(
				'eduRef' => $a_edu_obj -> getUri(),
				'user' => $ilUser -> getEmail(),
				'lmsId' => $app_id,
				'courseId' => $a_course_ref_id,
				'resourceId' => $a_edu_obj->getRefId()
		);
		
		
		$ilLog->write("B. Calling deleteUsage() with: ".print_r($params, true));

		$soap->deleteUsage($params);
		
		// delete usage from db
		include_once("class.lfEduUsage.php");
		lfEduUsage::deleteUsage($a_edu_obj->getId(), $a_uri, $a_course_ref_id);

		// always return true, even if repository-call didn't succeed to avoid non-deleteable objects.
		return true;
	}

	/**
	 * Set usage
	 *
	 * @param
	 * @return
	 */
	function setUsage($a_edu_obj)
	{
		global $tree, $ilUser, $ilLog;
		
		//$ilLog->write("Running Set Usage.");
		
		// since this is called during update we must read the uri from db
		$uri = $a_edu_obj->getUri();

		// course id is parent ID object object in ILIAS tree
		$course_id = $a_edu_obj->getUpperCourse();
		if ($course_id == 0)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException("set usage: no upper course ref id given.");
		}
		
		$this->plugin->includeClass("../lib/class.lfEduUtil.php");
		
		$object_id = lfEduUtil::getObjectIdFromUri($uri);
		if ($object_id == "")
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException("set Usage: no object id");
		}
		
// todo: get object version (where from?)
		$version = "";
		
		$repository_id = lfEduUtil::getRepIdFromUri($uri);
		if ($repository_id == "")
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException("delete Usage: no repository id");
		}

		// get home app conf
		$this->plugin->includeClass("../lib/class.lfEduAppConf.php");
		lfEduAppConf::initApps();
		$home_conf = lfEduAppConf::getHomeAppConf();

		if ($home_conf == null)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException('Missing home-config.');
		}

		$app_id = $home_conf->getEntry('appid');
		if (!$app_id)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException('Missing "appid" in home-conf.');
		}

		// get repository conf
		$rep_conf = lfEduAppConf::getAppConfById($repository_id);
		if ($rep_conf == null)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException('Missing config for repository "'.$repository_id.'"');
		}
		
		$ticket = $this->getAuthenticationTicket();

		
		// put the data of the new cc-resource into an array and create a neat XML-file out of it
		$data4xml = array("ccrender");
		$data4xml[1]["ccuser"]["id"] = $ilUser->getEmail();
		$data4xml[1]["ccuser"]["name"] = $ilUser->getFirstname()." ".$ilUser->getLastname();
		$data4xml[1]["ccserver"]["ip"] = $_SERVER['SERVER_ADDR'];
		$data4xml[1]["ccserver"]["hostname"] = $_SERVER['SERVER_NAME'];
		$data4xml[1]["ccreferencen"]["reference"] = $object_id;
		$data4xml[1]["ccversion"]["version"] = $version;

		include_once("class.RenderParameter.php");
		$myXML= new RenderParameter();
		$xml = $myXML->getXML($data4xml);

		try
		{
			$url = $rep_conf->getEntry('usagewebservice_wsdl');
			if (!$url)
			{
				include_once("class.ilLfEduSharingLibException.php");
				throw new ilLfEduSharingLibException('Missing config-param "usagewebservice_wsdl".');
			}
	
			// soap call
			$soap = new SigSoapClient($url);

			$params = array(
					"eduRef" => $a_edu_obj -> getUri(),
					"user" => $ilUser->getEmail(),
					"lmsId" => $app_id,
					"courseId" => $course_id,
					"userMail" =>  $ilUser->getEmail(),
					"fromUsed" => '2002-05-30T09:00:00',
					"toUsed" => '2222-05-30T09:00:00',
					"distinctPersons" => '0',
					"version" => $version,
					"resourceId" => $a_edu_obj->getRefid(),
					"xmlParams" => $xml,
			);
			
			
			
			
			$ilLog->write("Calling setUsage() with: ".print_r($params, true));

			$soap->setUsage($params);
			
			// add usage to db
			include_once("class.lfEduUsage.php");
			lfEduUsage::addUsage($a_edu_obj->getId(), $uri, $course_id);

		}
		catch(SoapFault $exception)
		{
			throw ($exception);
		}

		return true;
	}
	
}

?>
