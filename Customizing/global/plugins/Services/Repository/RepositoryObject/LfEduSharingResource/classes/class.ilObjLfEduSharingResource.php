<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");

/**
 * Application class for edusharing resource repository object.
 *
 * @author Alex Killing <alex.killing@gmx.de>
 *
 * $Id$
 */
class ilObjLfEduSharingResource extends ilObjectPlugin
{
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
	}

	/**
	 * Get type.
	 */
	final function initType()
	{
		$this->setType("xesr");
	}
	
	/**
	 * Set URI
	 *
	 * @param string $a_val uri	
	 */
	function setUri($a_val)
	{
		$this->uri = $a_val;
	}
	
	/**
	 * Get URI
	 *
	 * @return string uri
	 */
	function getUri()
	{
		return $this->uri;
	}
	
	/**
	 * Set online
	 *
	 * @param	boolean		online
	 */
	function setOnline($a_val)
	{
		$this->online = $a_val;
	}
	
	/**
	 * Get online
	 *
	 * @return	boolean		online
	 */
	function getOnline()
	{
		return (int) $this->online;
	}

	/**
	 * Create object
	 */
	function doCreate()
	{
		global $ilDB;
		
		$ilDB->manipulate("INSERT INTO rep_robj_xesr_data ".
			"(id, edus_uri, is_online) VALUES (".
			$ilDB->quote($this->getId(), "integer").",".
			$ilDB->quote($this->getUri(), "text").",".
			$ilDB->quote($this->getOnline(), "integer").
			")");
	}
	
	/**
	 * Read data from db
	 */
	function doRead()
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xesr_data ".
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			);
		$rec = $ilDB->fetchAssoc($set);
		$this->setUri($rec["edus_uri"]);
		$this->setOnline($rec["is_online"]);
	}
	
	/**
	 * Update data
	 */
	function doUpdate()
	{
		global $ilDB;

		// die URI setzen
		$old_uri = self::lookupUri($this->getId());
		$new_uri = $this->getUri();
		
		// change of uri not allowed
		if ($old_uri != $new_uri && $old_uri != "")
		{
			$this->plugin->includeClass("../exceptions/class.ilLfEdusharingResourceException.php");
			throw new ilLfEdusharingResourceException("Update: Change of URI not supported.");
		}

		$ilDB->manipulate($up = "UPDATE rep_robj_xesr_data SET ".
			" is_online = ".$ilDB->quote($this->getOnline(), "integer").",".
			" edus_uri = ".$ilDB->quote($this->getUri(), "text").
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			);
		
		if ($old_uri != $new_uri && $old_uri == "" && $new_uri != "")
		{
			$this->setUsage();
		}
	}
	
	/**
	 * Write uri
	 *
	 * @param
	 * @return
	 */
	function writeUri($a_uri)
	{
		global $ilDB;
		
		$this->setUri($a_uri);
		$ilDB->manipulate($up = "UPDATE rep_robj_xesr_data SET ".
			" edus_uri = ".$ilDB->quote($a_uri, "text").
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			); 
	}
	
	
	/**
	 * Delete data from db
	 */
	function doDelete()
	{
		global $ilDB;
		
		$this->deleteAllUsages();
		$ilDB->manipulate("DELETE FROM rep_robj_xesr_data WHERE ".
			" id = ".$ilDB->quote($this->getId(), "integer")
			);

	}
	
	/**
	 * Lookup uri
	 *
	 * @param
	 * @return
	 */
	static function lookupUri($a_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT edus_uri FROM rep_robj_xesr_data ".
			" WHERE id = ".$ilDB->quote($a_id, "integer")
			);
		$rec  = $ilDB->fetchAssoc($set);
		return $rec["edus_uri"];
	}
	
	
	/**
	 * Do Cloning
	 */
	function doCloneObject($new_obj, $a_target_id, $a_copy_id = null)
	{
		global $ilDB;
		
		$new_obj->setOnline($this->getOnline());
		$new_obj->update();
		$new_obj->writeUri($this->getUri());
	}
	
	/**
	 * Get home app conf
	 *
	 * @param
	 * @return
	 */
	function getHomeAppConf()
	{
		// get home app conf
		$this->plugin->includeClass("../lib/class.lfEduAppConf.php");
		lfEduAppConf::initApps();
		return lfEduAppConf::getHomeAppConf();
	}
	
	/**
	 * Get ticket
	 *
	 * @param
	 * @return
	 */
	function getTicket() {
		global $ilUser, $ilLog;
		// get edu sharing soap client and a ticket
		$this->plugin->includeClass("../lib/class.lfSigSoapClient.php");
		$hc = $this->getHomeAppConf();
		$authWsdl = $hc -> getEntry('authenticationwebservice_wsdl');
		$eduService = new SigSoapClient($authWsdl);
		$params = array("applicationId" => $hc -> getEntry('appid'), "ticket" => session_id(),
					"ssoData" => array(array('key' => 'userid','value' => $ilUser->getEmail())));
		$ilLog->write("(B) Calling authenticateByTrustedApp() with: ".print_r($params, true));
		$eduReturn = $eduService->authenticateByTrustedApp($params);
		$ticket = $eduReturn->authenticateByTrustedAppReturn->ticket;

		if (strpos($ticket, "TICKET_") !== 0)
		{
			$this->plugin->includeClass("../exceptions/class.ilLfEdusharingResourceException.php");
			throw new ilLfEdusharingResourceException("Ticket not starting with TICKET_ (".$ticket.").");
		}
		
		return $ticket;
	}
	
	/**
	 * Delete usage
	 *
	 * @param
	 * @return
	 */
	function deleteAllUsages()
	{
		// get edu sharing soap client and a ticket
		$this->plugin->includeClass("../lib/class.lfSigSoapClient.php");
		$this->plugin->includeClass("../lib/class.lfEduUsage.php");
		
		$usages = lfEduUsage::getUsagesOfObject($this->getId());
		if (count($usages) > 0)
		{
			$soap_client = new SigSoapClient($this->plugin, $this->getHomeAppConf());
		}
		foreach ($usages as $u)
		{
			if ($u["edus_uri"] != "" && $u["crs_ref_id"] > 0)
			{
				$soap_client->deleteUsage($this, $u["edus_uri"], $u["crs_ref_id"]);
			}
		}
	}

	/**
	 * Set usage
	 *
	 * @param
	 * @return
	 */
	function setUsage()
	{
		// get edu sharing soap client and a ticket
		$this->plugin->includeClass("../lib/class.lfEduSoapClient.php");
		$soap_client = new lfEduSoapClient($this->plugin, $this->getHomeAppConf());
		$soap_client->setUsage($this);
	}
	
	/**
	 * Get upper course
	 *
	 * @param
	 * @return
	 */
	function getUpperCourse()
	{
		global $tree;
		
		if ($this->getRefId() > 0)
		{
			$path = $tree->getPathFull($this->getRefId());
			for ($i = count($path) - 1; $i >= 0; $i--)
			{
				$p = $path[$i];
				if ($p["type"] == "crs")
				{
					return $p["child"];
				}
			}
		}
		
		return 0;
	}
	
	/**
	 * Check registered usage
	 *
	 * @param
	 * @return
	 */
	function checkRegisteredUsage()
	{
		$this->plugin->includeClass("../lib/class.lfEduUsage.php");
		$crs_ref_id = $this->getUpperCourse();
		if ($this->getUri() != "" && $crs_ref_id > 0)
		{
			$usages = lfEduUsage::getUsagesOfObject($this->getId());
			foreach ($usages as $u)
			{
				if ($u["crs_ref_id"] == $crs_ref_id &&
					$u["edus_uri"] == $this->getUri())
				{
					return true;
				}
			}
		}
		return false;
	}
	
}
?>
