<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");
require_once("./Services/Tracking/interfaces/interface.ilLPStatusPlugin.php");

/**
 * Application class for edusharing resource repository object.
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 *
 * $Id$
 */
class ilObjLfEduSharingResource extends ilObjectPlugin //implements ilLPStatusPluginInterface
{

	public $window_width=200;
	public $window_height=100;
	public $object_version = 0;
	protected $object_version_use_exact = 1;

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
	 * Get resource_id
	 * used in lib
	 * @return string uri
	 */
	function getResId()
	{
		return $this->getId();
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


	function setObjectVersion($a_val)
	{
		$this->object_version = $a_val;
	}

	function getObjectVersion()
	{
		return $this->object_version;
	}

	
	function setObjectVersionUseExact($a_val)
	{
		$this->object_version_use_exact = $a_val;
	}

	function getObjectVersionUseExact()
	{
		return $this->object_version_use_exact;
	}


	function getObjectVersionForUse()
	{
		if ($this->object_version_use_exact == 0) return 0;
		else return $this->object_version;
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
		global $DIC;
		// $DIC->database()->manipulate("INSERT INTO rep_robj_xesr_usage ".
			// "(id, edus_uri, is_online, parent_obj_id) VALUES (".
			// $DIC->database()->quote($this->getId(), "integer").",".
			// $DIC->database()->quote($this->getUri(), "text").",".
			// $DIC->database()->quote($this->getOnline(), "integer").",".
			// $DIC->database()->quote($this->getUpperCourse(), "integer").
			// ")");
		$db = $DIC->database();
		$db->insert('rep_robj_xesr_usage',
			array(
				'id'			=> array('integer', $this->getId()),
				'edus_uri'		=> array('text', $this->getUri()), //""
				'parent_obj_id'	=> array('integer',  $this->getId()),
				'is_online'		=> array('integer', $this->getOnline()),
				'object_version_use_exact'	=> array('integer', $this->getObjectVersionUseExact()),
				'timecreated'	=> array('timestamp', date('Y-m-d H:i:s')),
				'timemodified'	=> array('timestamp', date('Y-m-d H:i:s'))
			)
		);
	}
	
	// function afterCreateSetParentObj() {
		// global $DIC;
		// $db = $DIC->database();
		// $db->update('rep_robj_xesr_usage',
			// array(
				// 'parent_obj_id'	=> array('integer', $this->getUpperCourse())
			// ),
			// array(
				// 'id' => array('integer', $this->getId()),
				// 'parent_obj_id' => array('integer', $this->getId())
			// )
		// );
		
	// }
	
	/**
	 * Read data from db
	 */
	function doRead()
	{
		global $DIC;
		// $set = $DIC->database()->query("SELECT * FROM rep_robj_xesr_usage ".
			// " WHERE id = ".$DIC->database()->quote($this->getId(), "integer").
			// " AND parent_obj_id = ".$DIC->database()->quote($this->getUpperCourse(), "integer")
			// );
		// $rec = $DIC->database()->fetchAssoc($set);
		// $this->setUri($rec["edus_uri"]);
		// $this->setOnline($rec["is_online"]);
		// $this->setUri($rec["edus_uri"]);
		// $this->setObjectVersion($rec["object_version"]);
		// $this->setObjectVersionUseExact($rec["object_version_use_exact"]);
		$check_parent_obj_id = 0;

		$db = $DIC->database();
		$query = "SELECT * FROM rep_robj_xesr_usage WHERE id = " .$db->quote($this->getId(), 'integer'). 
			" AND parent_obj_id = ".$db->quote($this->getUpperCourse(), "integer");
		$result = $db->query($query);
		while (($row = $result->fetchAssoc()) !== false) {
			$this->setUri($row['edus_uri']);
			$this->setOnline($row["is_online"]);
			$this->setObjectVersion($row['object_version']);
			$this->setObjectVersionUseExact($row['object_version_use_exact']);
			// $this->set($row['timecreated']);
			// $this->set($row['timemodified']);
			$check_parent_obj_id = $row['parent_obj_id'];
		}
		
		if ($check_parent_obj_id != $this->getUpperCourse()) { //after creation or cloning
			$db->update('rep_robj_xesr_usage',
				array(
					'parent_obj_id'	=> array('integer', $this->getUpperCourse())
				),
				array(
					'id' => array('integer', $this->getId())//,
					// 'parent_obj_id' => array('integer', $check_parent_obj_id)
					// 'parent_obj_id' => array('integer', $this->getId())
				)
			);
			if ($this->getUri() != "") { //after cloning or moving
				$this->plugin->includeClass('../lib/class.lib.php');
				edusharing_add_instance($this);
			}
		}
	}
	
	/**
	 * Update data
	 */
	function doUpdate()
	{
		global $DIC;
		// die URI setzen
		$old_uri = self::lookupUri($this->getId(), $this->getUpperCourse());
		$new_uri = $this->getUri();
		
		// change of uri not allowed
		if ($old_uri != $new_uri && $old_uri != "")
		{
			$this->plugin->includeClass("../exceptions/class.ilLfEduSharingResourceException.php");
			throw new ilLfEduSharingResourceException("Update: Change of URI not supported.");
		}

		// if ($old_uri != $new_uri && $old_uri == "" && $new_uri != "")
		// {
			$this->plugin->includeClass('../lib/class.lib.php');
			edusharing_add_instance($this);
			// $this->setUsage();
		// }
		$db = $DIC->database();
		$db->update('rep_robj_xesr_usage',
			array(
				'edus_uri' 			=> array('text', $this->getUri()),
				'is_online'			=> array('integer', $this->getOnline()),
				'object_version'	=> array('text', $this->getObjectVersion()),
				'object_version_use_exact'	=> array('integer', $this->getObjectVersionUseExact()),
				'timemodified'	=> array('timestamp', date('Y-m-d H:i:s'))
			),
			array(
				'id' => array('integer', $this->getId()),
				'parent_obj_id' => array('integer', $this->getUpperCourse())
			)
		);

		return true;
	}
	
	/**
	 * Write uri
	 *
	 * @param
	 * @return
	 */
	function writeUri($a_id, $a_uri, $a_object_version, $a_object_version_use_exact, $a_is_online) {
		global $DIC;
		// $this->setUri($a_uri);
		$db = $DIC->database();
		$db->update('rep_robj_xesr_usage',
			array(
				'edus_uri' 			=> array('text', $a_uri),
				'object_version'	=> array('text', $a_object_version),
				'object_version_use_exact' => array('text', $a_object_version_use_exact),
				'is_online'			=> array('integer', $a_is_online),
				'timecreated'		=> array('timestamp', date('Y-m-d H:i:s')),
				'timemodified'		=> array('timestamp', date('Y-m-d H:i:s'))
			),
			array(
				'id' => array('integer', $a_id),
				'parent_obj_id' => array('integer', $a_id)
			)
		);
	}
	
	
	/**
	 * Delete data from db
	 */
	public function doDelete() {
		global $DIC;
		$this->plugin->includeClass('../lib/class.lib.php');
		// deleteAllUsages()
		$set = $DIC->database()->query("SELECT edus_uri, parent_obj_id FROM rep_robj_xesr_usage ".
			" WHERE id = ".$DIC->database()->quote($this->getId(), "integer")
			);
		while ($rec  = $DIC->database()->fetchAssoc($set)) {
			edusharing_delete_instance($this->getId(), $rec['edus_uri'], $rec['parent_obj_id']);
		}
		$DIC->database()->manipulate("DELETE FROM rep_robj_xesr_usage WHERE ".
			" id = ".$DIC->database()->quote($this->getId(), "integer")
			);
	}
	
	/**
	 * Lookup uri
	 *
	 * @param
	 * @return
	 */
	static function lookupUri($a_id, $a_parent_obj_id) {
		global $DIC;
		$set = $DIC->database()->query("SELECT edus_uri FROM rep_robj_xesr_usage ".
			" WHERE id = ".$DIC->database()->quote($a_id, "integer").
			" AND parent_obj_id = ".$DIC->database()->quote($a_parent_obj_id, "integer")
			);
		$rec  = $DIC->database()->fetchAssoc($set);
		return $rec["edus_uri"];
	}
	
	
	/**
	 * Do Cloning
	 */
	function doCloneObject($new_obj, $a_target_id, $a_copy_id = null){
		$new_obj->setOnline($this->getOnline());
		$new_obj->update();
		$new_obj->writeUri($new_obj->getId(), $this->getUri(), $this->getObjectVersion(), $this->getObjectVersionUseExact(), $this->getOnline());
	}
	
	
	/**
	 * Get ticket
	 *
	 * @param
	 * @return
	 */
	function getTicket() {
		$this->plugin->includeClass('../lib/class.cclib.php');
		$cclib = new mod_edusharing_web_service_factory();
		return $cclib->edusharing_authentication_get_ticket();
	}
	
	// /**
	 // * Delete usage
	 // *
	 // * @param
	 // * @return
	 // */
	// function deleteAllUsages()
	// {
		// // get edu sharing soap client and a ticket
		// // $this->plugin->includeClass("../lib/class.sigSoapClient.php");
		// $this->plugin->includeClass('../lib/class.lib.php');
		// $this->plugin->includeClass("../lib/class.lfEduUsage.php");
		
		// $usages = lfEduUsage::getUsagesOfObject($this->getId());
		// foreach ($usages as $u)
		// {
			// if ($u["edus_uri"] != "" && $u["crs_ref_id"] > 0)
			// {
				// edusharing_delete_instance($this);
			// }
		// }
	// }

	/**
	 * Set usage
	 *
	 * @param
	 * @return
	 */
	function setUsage()
	{
		$this->plugin->includeClass('../lib/class.lib.php');
		edusharing_add_instance($this);
		// return true;
	}
	
	/**
	 * Get upper object
	 *
	 * @param
	 * @return
	 */
	function getUpperCourse()
	{
		global $tree;
		$parent_ref_id = $tree->getParentId($this->getRefId());
		$parent_id = ilObject::_lookupObjId($parent_ref_id);
		return $parent_id;
		
		// if ($this->getRefId() > 0)
		// {
			// $path = $tree->getPathFull($this->getRefId());
			// for ($i = count($path) - 1; $i >= 0; $i--)
			// {
				// $p = $path[$i];
				// if ($p["type"] == "crs")
				// {
					// return $p["child"];
				// }
			// }
		// }
		
		// return 0;
	}
	
	/**
	 * Check registered usage
	 *
	 * @param
	 * @return
	 */
	function checkRegisteredUsage()
	{
		if ($this->getUri() == "") return false; 
		global $DIC;
		$db = $DIC->database();
		$query = "SELECT parent_obj_id FROM rep_robj_xesr_usage WHERE id = " .$db->quote($this->getId(), 'integer');
		$result = $db->query($query);
		while (($row = $result->fetchAssoc()) !== false) {
			if ($row['parent_obj_id'] == $this->getUpperCourse()) return true;
		}
		return false;
	}
	
		/**
	 * Delete usage
	 *
	 * @param
	 * @return
	 */
	static function deleteUsage($a_obj_id, $a_uri, $a_parent_obj_id)
	{
		global $DIC;		
		$DIC->database()->manipulate("DELETE FROM rep_robj_xesr_usage ".
			" WHERE id = ".$DIC->database()->quote($a_obj_id, "integer").
			" AND edus_uri = ".$DIC->database()->quote($a_uri, "text").
			" AND parent_obj_id = ".$DIC->database()->quote($a_parent_obj_id, "integer")
			);
		global $DIC;
		
	}

	
}
?>
